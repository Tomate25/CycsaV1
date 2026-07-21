<?php
/**
 * Script de diagnóstico para CYCSA ERP & LIMS
 * Permite verificar los requisitos del sistema y diagnosticar errores 500 en producción.
 */

// Desactivar almacenamiento en caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Mostrar todos los errores únicamente en este script de diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico del Sistema - CYCSA</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; padding: 40px 20px; line-height: 1.5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
        h1 { color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-top: 0; display: flex; align-items: center; justify-content: space-between; }
        h2 { color: #1e293b; margin-top: 30px; margin-bottom: 15px; font-size: 1.25rem; border-left: 4px solid #3b82f6; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background-color: #f8fafc; font-weight: 600; color: #475569; }
        .badge { display: inline-block; padding: 4px 8px; font-size: 0.85rem; font-weight: 600; border-radius: 9999px; }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .alert { padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 0.95rem; }
        .alert-info { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .alert-danger { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-warning { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        pre { background-color: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; overflow-x: auto; font-size: 0.9rem; font-family: monospace; }
        .btn { display: inline-block; background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 20px; text-align: center; }
        .btn:hover { background-color: #2563eb; }
    </style>
</head>
<body>
<div class='container'>
    <h1>
        <span>🔍 Diagnóstico del Entorno CYCSA</span>
        <span style='font-size: 0.9rem; color: #64748b;'>Versión 1.1</span>
    </h1>

    <div class='alert alert-info'>
        Este archivo te ayuda a identificar por qué ocurre un Error 500 en Bluehost. Una vez resueltos los problemas y verificado el acceso, <strong>debes eliminar este archivo por seguridad</strong>.
    </div>";

// 1. Verificar Versión de PHP
echo "<h2>1. Versión de PHP</h2>";
$phpVersion = PHP_VERSION;
$versionValida = version_compare($phpVersion, '7.4.0', '>=');
echo "<table>
    <tr>
        <th>Requisito</th>
        <th>Tu Versión</th>
        <th>Estado</th>
    </tr>
    <tr>
        <td>PHP >= 7.4.0</td>
        <td>$phpVersion</td>
        <td>" . ($versionValida ? "<span class='badge badge-success'>Correcto</span>" : "<span class='badge badge-danger'>Incompatible (Actualizar en cPanel)</span>") . "</td>
    </tr>
</table>";

// 2. Verificar Extensiones Requeridas
echo "<h2>2. Extensiones PHP Obligatorias</h2>";
$extensions = [
    'pdo' => 'Conexión a Base de Datos genérica',
    'pdo_mysql' => 'Conexión a MySQL/MariaDB',
    'gd' => 'Manipulación de imágenes (necesario para reportes/gráficos)',
    'mbstring' => 'Manejo de textos UTF-8 y caracteres especiales',
    'openssl' => 'Seguridad y encriptación de datos',
    'xml' => 'Procesamiento de XML (requerido por Dompdf)',
    'dom' => 'Estructura de documentos HTML/XML (requerido por Dompdf)',
    'zip' => 'Descompresión y empaquetado de archivos'
];

echo "<table>
    <tr>
        <th>Extensión</th>
        <th>Propósito</th>
        <th>Estado</th>
    </tr>";
foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    echo "<tr>
        <td><strong>$ext</strong></td>
        <td>$desc</td>
        <td>" . ($loaded ? "<span class='badge badge-success'>Activa</span>" : "<span class='badge badge-danger'>Desactivada (Activar en cPanel)</span>") . "</td>
    </tr>";
}
echo "</table>";

// 3. Verificar Archivos Críticos
echo "<h2>3. Archivos y Autoload (v2.0)</h2>";
$archivos = [
    '.env' => 'Archivo de variables de entorno global',
    '.env.local' => 'Archivo de variables locales (NO debe existir en producción)',
    'vendor/autoload.php' => 'Cargador de clases de Composer (PHPMailer, Dompdf, etc.)',
    'app/Core/Aplicacion.php' => 'Inicializador del núcleo MVC v2.0',
    'rutas/web.php' => 'Definición de rutas del enrutador'
];

echo "<table>
    <tr>
        <th>Archivo</th>
        <th>Descripción</th>
        <th>Estado</th>
    </tr>";
foreach ($archivos as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    
    $badge = "";
    if ($file === '.env.local') {
        if ($exists) {
            $badge = "<span class='badge badge-danger'>¡ATENCIÓN! Detectado (.env.local sobrescribirá a .env)</span>";
        } else {
            $badge = "<span class='badge badge-success'>No presente (Correcto para producción)</span>";
        }
    } else {
        $badge = ($exists ? "<span class='badge badge-success'>Encontrado</span>" : "<span class='badge badge-danger'>No encontrado (Falta subirlo)</span>");
    }
    
    echo "<tr>
        <td><code>$file</code></td>
        <td>$desc</td>
        <td>$badge</td>
    </tr>";
}
echo "</table>";

// Mapeo dinámico de lo que realmente existe en este directorio en el servidor
$itemsEnDir = array_diff(scandir(__DIR__), ['.', '..']);
echo "<h3>📂 Elementos detectados físicamente en esta carpeta en Bluehost:</h3>";
echo "<code>" . implode(', ', $itemsEnDir) . "</code><br><br>";

if (file_exists(__DIR__ . '/.env.local')) {
    echo "<div class='alert alert-danger'>
        <strong>⚠️ ERROR CRÍTICO DETECTADO:</strong> El archivo <code>.env.local</code> está en el servidor de producción. 
        Este archivo sobrescribirá los datos del archivo <code>.env</code> principal.
        <strong>Por favor, elimina el archivo <code>.env.local</code> de tu servidor.</strong>
    </div>";
}

// 4. Verificar Permisos de Directorios de Escritura
echo "<h2>4. Permisos de Directorios de Escritura (storage/)</h2>";
$directorios = [
    'storage' => 'Carpeta raíz de almacenamiento',
    'storage/logs' => 'Carpeta para logs de errores e historial',
    'storage/pdf' => 'Almacén de PDFs generados',
    'storage/uploads' => 'Almacén de archivos subidos',
    'storage/cache' => 'Caché del sistema'
];

echo "<table>
    <tr>
        <th>Directorio</th>
        <th>Descripción</th>
        <th>Permisos</th>
        <th>Estado de Escritura</th>
    </tr>";
foreach ($directorios as $dir => $desc) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    if ($exists) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        echo "<tr>
            <td><code>$dir/</code></td>
            <td>$desc</td>
            <td><code>$perms</code></td>
            <td>" . ($writable ? "<span class='badge badge-success'>Escritura Permitida</span>" : "<span class='badge badge-danger'>No Escribible (Cambiar a 755 o 775)</span>") . "</td>
        </tr>";
    } else {
        echo "<tr>
            <td><code>$dir/</code></td>
            <td>$desc</td>
            <td><code>-</code></td>
            <td><span class='badge badge-danger'>Directorio No Creado</span></td>
        </tr>";
    }
}
echo "</table>";

// Helper para parsear archivos .env tal como lo hace Aplicacion.php
function diagnosticoCargarEnv($ruta, &$config) {
    if (!file_exists($ruta)) return;
    $lines = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#' || $line[0] === ';') continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $k = trim($parts[0]);
                $v = trim($parts[1]);
                $v = trim($v, "\"'");
                if ($k === 'DB_HOST') $config['host'] = $v;
                if ($k === 'DB_NAME') $config['name'] = $v;
                if ($k === 'DB_USER') $config['user'] = $v;
                if ($k === 'DB_PASS') $config['pass'] = $v;
                if ($k === 'APP_ENV') $config['env'] = $v;
            }
        }
    }
}

// 5. Cargar Variables de Entorno y Validar Conexión de Base de Datos
echo "<h2>5. Prueba de Conexión a Base de Datos (Simulando la Aplicación)</h2>";
$dbConfig = ['host' => '', 'name' => '', 'user' => '', 'pass' => '', 'env' => 'produccion'];

diagnosticoCargarEnv(__DIR__ . '/.env', $dbConfig);
$cargoLocal = false;
if (file_exists(__DIR__ . '/.env.local')) {
    diagnosticoCargarEnv(__DIR__ . '/.env.local', $dbConfig);
    $cargoLocal = true;
}

if (empty($dbConfig['host']) || empty($dbConfig['name']) || empty($dbConfig['user'])) {
    echo "<div class='alert alert-danger'>
        <strong>Advertencia:</strong> Las variables de conexión de base de datos están incompletas en tus archivos de entorno.
    </div>";
} else {
    echo "<p>Intentando conectar a la base de datos simulando el flujo de carga real de <code>Aplicacion.php</code>:</p>";
    echo "<ul>
        <li><strong>Host:</strong> <code>" . htmlspecialchars($dbConfig['host']) . "</code></li>
        <li><strong>Base de Datos:</strong> <code>" . htmlspecialchars($dbConfig['name']) . "</code></li>
        <li><strong>Usuario:</strong> <code>" . htmlspecialchars($dbConfig['user']) . "</code></li>
        <li><strong>Modo (APP_ENV):</strong> <code>" . htmlspecialchars($dbConfig['env']) . "</code> " . ($cargoLocal ? "<span class='badge badge-danger' style='font-size:0.8rem;'>Cargado desde .env.local</span>" : "<span class='badge badge-success' style='font-size:0.8rem;'>Cargado desde .env</span>") . "</li>
    </ul>";

    try {
        $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ];
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
        echo "<div class='badge badge-success' style='font-size:1.1rem; padding: 8px 16px; margin-bottom:15px;'>
            ¡Conexión Exitosa a la Base de Datos!
        </div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>
            <strong>Error de Conexión:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>
            <em>Sugerencia: Revisa que las credenciales coincidan con las de tu cPanel. Si se cargó desde .env.local, elimina el archivo .env.local del servidor.</em>
        </div>";
    }
}

echo "<div style='margin-top:40px; text-align:center;'>
    <a href='publico/' class='btn'>Ir a la Aplicación Principal</a>
</div>
</div>
</body>
</html>";
?>
