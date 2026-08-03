<?php

namespace Cycsa\Nucleo;

class Aplicacion {
    public static Aplicacion $app;
    public Enrutador $enrutador;
    public Peticion $peticion;
    public Respuesta $respuesta;

    public function __construct() {
        self::$app = $this;
        
        // 0. Configurar la zona horaria oficial de Nicaragua
        date_default_timezone_set('America/Managua');
        
        // 1. Iniciamos las sesiones con configuración de seguridad para producción
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_start([
            'cookie_httponly' => true,   // Impide acceso a la cookie desde JavaScript (anti-XSS)
            'cookie_secure'   => $secure,  // Solo envía la cookie por HTTPS si está habilitado
            'cookie_samesite' => 'Strict', // Bloquea envío de cookie desde sitios externos (anti-CSRF)
            'use_strict_mode' => true,   // Rechaza IDs de sesión no generados por el servidor
        ]); 

        // 2. Cargamos las variables de entorno (.env)
        $this->cargarEntorno();
        
        // 2b. Decodificar automáticamente los parámetros de IDs ofuscados (AES-128)
        $this->decodificarPeticionIds();

        // 3. Validar sesión única (si hay un usuario logueado en la sesión)
        if (isset($_SESSION['usuario_id'])) {
            try {
                $db = Conexion::obtenerInstancia();
                $stmt = $db->prepare("SELECT session_id FROM usuarios WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['usuario_id']]);
                $dbSessionId = $stmt->fetchColumn();
                
                $currentSessionId = session_id();
                if ($dbSessionId && $dbSessionId !== $currentSessionId) {
                    // La sesión fue invalidada por un inicio de sesión más nuevo en otro dispositivo
                    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
                    registrarBitacora('autenticacion', 'session_kicked', 'Sesión cerrada por inicio de sesión en otro dispositivo para: ' . $usuarioNombre);
                    
                    // Limpiar y desloguear (guardamos solo el mensaje de error para mostrar en el login)
                    $_SESSION = [
                        'login_error' => 'Tu sesión ha sido cerrada porque se inició sesión en otro dispositivo.'
                    ];
                    
                    // Si la petición es del validador de sesión AJAX, devolvemos JSON en vez de redireccionar en el servidor
                    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
                    if (strpos($requestUri, 'verificar-sesion-activa') !== false) {
                        header('Content-Type: application/json');
                        echo json_encode(['status' => 'kicked']);
                        exit;
                    }
                    
                    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                    $basePath = dirname($scriptName);
                    $basePath = str_replace('\\', '/', $basePath);
                    $basePath = rtrim($basePath, '/');
                    if ($basePath === '/') {
                        $basePath = '';
                    }
                    header('Location: ' . $basePath . '/login');
                    exit;
                }
            } catch (\Exception $e) {
                error_log("Error al validar sesión única en Aplicacion: " . $e->getMessage());
            }
        }
        
        $this->peticion = new Peticion();
        $this->respuesta = new Respuesta();
        $this->enrutador = new Enrutador($this->peticion, $this->respuesta);
    }

    // Función que lee los archivos .env y los guarda en $_ENV
    private function cargarEntorno(): void {
        $root = dirname(__DIR__, 2);
        $rutaEnv = $root . '/.env';
        $rutaEnvLocal = $root . '/.env.local';
        $cargado = false;

        if (!file_exists($rutaEnv)) {
            $rutaEnv = dirname(__DIR__, 1) . '/.env';
        }
        if (!file_exists($rutaEnvLocal)) {
            $rutaEnvLocal = dirname(__DIR__, 1) . '/.env.local';
        }

        if (file_exists($rutaEnv)) {
            $this->cargarArchivoEnv($rutaEnv);
            $cargado = true;
        }

        // .env.local sobreescribe .env si existe (para desarrollo local)
        if (file_exists($rutaEnvLocal)) {
            $this->cargarArchivoEnv($rutaEnvLocal);
            $cargado = true;
        }

        if (!$cargado) {
            error_log("Error Crítico: No se encontró ningún archivo de entorno (.env o .env.local) en {$root}");
            die("Error 500: Fallo interno de entorno.");
        }
    }

    private function cargarArchivoEnv(string $ruta): void {
        $lines = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                    continue;
                }
                
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $clave = trim($parts[0]);
                    $valor = trim($parts[1]);
                    
                    // Quitar comillas si están presentes al inicio y final
                    $len = strlen($valor);
                    if ($len >= 2 && (
                        ($valor[0] === '"' && $valor[$len - 1] === '"') ||
                        ($valor[0] === "'" && $valor[$len - 1] === "'")
                    )) {
                        $valor = substr($valor, 1, -1);
                    }
                    
                    $_ENV[$clave] = $valor;
                }
            }
        }
    }

    private function decodificarPeticionIds(): void {
        foreach (['id', 'id_lote', 'id_informe'] as $key) {
            if (isset($_GET[$key]) && is_string($_GET[$key]) && !is_numeric($_GET[$key])) {
                $dec = \Cycsa\App\Helpers\HashHelper::decodificar($_GET[$key]);
                if ($dec !== null) {
                    $_GET[$key] = $dec;
                    if (isset($_REQUEST[$key])) {
                        $_REQUEST[$key] = $dec;
                    }
                }
            }
            if (isset($_POST[$key]) && is_string($_POST[$key]) && !is_numeric($_POST[$key])) {
                $dec = \Cycsa\App\Helpers\HashHelper::decodificar($_POST[$key]);
                if ($dec !== null) {
                    $_POST[$key] = $dec;
                    if (isset($_REQUEST[$key])) {
                        $_REQUEST[$key] = $dec;
                    }
                }
            }
        }
    }

    public function correr(): void {
        $contenido = $this->enrutador->resolver();

        if (is_string($contenido)) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            if ($scriptDir === '/' || $scriptDir === '\\') {
                $scriptDir = '';
            }

            // Adaptar dinámicamente rutas /Cycsa/publico al entorno de producción VPS
            if (strpos($scriptDir, '/publico') === false) {
                $baseTarget = $scriptDir !== '' ? $scriptDir . '/' : '/';
                $contenido = str_replace('/Cycsa/publico/', $baseTarget, $contenido);
                $contenido = str_replace('/Cycsa/publico"', rtrim($baseTarget, '/') . '"', $contenido);
                $contenido = str_replace("/Cycsa/publico'", rtrim($baseTarget, '/') . "'", $contenido);
            }
        }

        echo $contenido;
    }
}