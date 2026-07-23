<?php

namespace Cycsa\Modulos\Clientes\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Clientes\Modelos\ClienteModelo;

class ClientesControlador extends ControladorBase {
    
    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    // Obtener la lista de cuentas de detalle del catálogo contable para los autocompletados
    private function obtenerCuentasContables(): array {
        try {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->query("SELECT codigo, nombre FROM cuentas_contables WHERE tipo = 'DETALLE' AND activo = 1 ORDER BY codigo ASC");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener catálogo de cuentas en Clientes: " . $e->getMessage());
            return [];
        }
    }

    // 🔍 INDEX CON BUSCADOR
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
        
        $modelo = new ClienteModelo();
        $busqueda = $_GET['q'] ?? ''; 

        $bitacora_logs = obtenerBitacoraModulo('clientes');

        $this->renderizar('clientes/vistas/index', [
            'titulo' => 'Módulo de Clientes - Cycsa',
            'clientes' => $modelo->obtenerTodos($busqueda),
            'busqueda' => $busqueda,
            'bitacora_logs' => $bitacora_logs
        ]);
    }

    // ➕ FORMULARIO DE CREACIÓN
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        if (empty($_SESSION['csrf_token'])) { 
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        }

        $this->renderizar('clientes/vistas/crear', [
            'titulo' => 'Registrar Cliente - Cycsa',
            'cuentas_contables' => $this->obtenerCuentasContables()
        ]);
    }

    // 💾 GUARDAR NUEVO CLIENTE
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ClienteModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $this->renderizar('clientes/vistas/crear', [
                    'titulo' => 'Registrar Cliente', 
                    'error' => 'Error: Token CSRF inválido.', 
                    'valores' => $datos,
                    'cuentas_contables' => $this->obtenerCuentasContables()
                ]); 
                return;
            }
            if (empty(trim($datos['nombre_cliente']))) {
                $this->renderizar('clientes/vistas/crear', [
                    'titulo' => 'Registrar Cliente', 
                    'error' => 'El nombre del cliente es obligatorio.', 
                    'valores' => $datos,
                    'cuentas_contables' => $this->obtenerCuentasContables()
                ]); 
                return;
            }
            if (!empty($datos['email']) && $modelo->emailExiste($datos['email'])) {
                $this->renderizar('clientes/vistas/crear', [
                    'titulo' => 'Registrar Cliente', 
                    'error' => 'El correo electrónico ya está registrado.', 
                    'valores' => $datos,
                    'cuentas_contables' => $this->obtenerCuentasContables()
                ]); 
                return;
            }

            // Identificación fiscal principal (RUC o Cédula)
            $identificacion = '';
            if (!empty($datos['numero_ruc'])) {
                $identificacion = trim($datos['numero_ruc']);
            } elseif (!empty($datos['numero_cedula'])) {
                $identificacion = trim($datos['numero_cedula']);
            }

            if (!empty($identificacion) && $modelo->identificacionExiste($identificacion)) {
                $this->renderizar('clientes/vistas/crear', [
                    'titulo' => 'Registrar Cliente', 
                    'error' => 'La identificación fiscal (RUC/Cédula) ya está registrada.', 
                    'valores' => $datos,
                    'cuentas_contables' => $this->obtenerCuentasContables()
                ]); 
                return;
            }

            if ($modelo->guardar($datos)) {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $lastId = $db->lastInsertId();
                
                // Formar nombre para la bitácora
                $nombre_razon_social = trim($datos['nombre_cliente']);
                if (($datos['tipo_cliente'] ?? '') === 'Natural') {
                    $parts = [trim($datos['nombre_cliente'])];
                    if (!empty($datos['primer_apellido'])) $parts[] = trim($datos['primer_apellido']);
                    if (!empty($datos['segundo_apellido'])) $parts[] = trim($datos['segundo_apellido']);
                    $nombre_razon_social = implode(' ', $parts);
                }

                registrarBitacora('clientes', 'crear', 'Creado cliente: ' . $nombre_razon_social, $lastId);
                $respuesta->redirigir('/Cycsa/publico/clientes');
                return;
            } else {
                $this->renderizar('clientes/vistas/crear', [
                    'titulo' => 'Registrar Cliente', 
                    'error' => 'Error al guardar en la base de datos.', 
                    'valores' => $datos,
                    'cuentas_contables' => $this->obtenerCuentasContables()
                ]);
            }
        }
    }

    // ✏️ FORMULARIO DE EDICIÓN
    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) { 
            $respuesta->redirigir('/Cycsa/publico/clientes'); 
            return; 
        }

        $modelo = new ClienteModelo();
        $cliente = $modelo->obtenerPorId((int)$id);

        if (!$cliente) { 
            $respuesta->redirigir('/Cycsa/publico/clientes'); 
            return; 
        }
        if (empty($_SESSION['csrf_token'])) { 
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        }

        $this->renderizar('clientes/vistas/editar', [
            'titulo' => 'Editar Cliente - Cycsa',
            'cliente' => $cliente,
            'cuentas_contables' => $this->obtenerCuentasContables()
        ]);
    }

    // ✏️ GUARDAR LOS CAMBIOS DE EDICIÓN
    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        
        $datos = $peticion->obtenerDatos();
        $id = $datos['id'] ?? $_GET['id'] ?? null;
        
        if (!$id || !$peticion->esPost()) { 
            $respuesta->redirigir('/Cycsa/publico/clientes'); 
            return; 
        }

        $modelo = new ClienteModelo();
        $currentCliente = $modelo->obtenerPorId((int)$id);
        
        if (!$currentCliente) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            return;
        }

        if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $this->renderizar('clientes/vistas/editar', [
                'titulo' => 'Editar Cliente', 
                'error' => 'Error: Token CSRF inválido.', 
                'cliente' => $datos,
                'cuentas_contables' => $this->obtenerCuentasContables()
            ]); 
            return;
        }
        if (empty(trim($datos['nombre_cliente']))) {
            $this->renderizar('clientes/vistas/editar', [
                'titulo' => 'Editar Cliente', 
                'error' => 'El nombre del cliente es obligatorio.', 
                'cliente' => $datos,
                'cuentas_contables' => $this->obtenerCuentasContables()
            ]); 
            return;
        }
        
        // Verificar duplicado de email solo si cambió
        $emailNuevo = trim($datos['email'] ?? '');
        $emailOriginal = trim($currentCliente['email'] ?? '');
        if ($emailNuevo !== '' && $emailNuevo !== $emailOriginal && $modelo->emailExiste($emailNuevo, (int)$id)) {
            $this->renderizar('clientes/vistas/editar', [
                'titulo' => 'Editar Cliente', 
                'error' => 'Este correo ya pertenece a otro cliente.', 
                'cliente' => $datos,
                'cuentas_contables' => $this->obtenerCuentasContables()
            ]); 
            return;
        }

        // Identificación fiscal principal (RUC o Cédula)
        $identificacion = '';
        if (!empty($datos['numero_ruc'])) {
            $identificacion = trim($datos['numero_ruc']);
        } elseif (!empty($datos['numero_cedula'])) {
            $identificacion = trim($datos['numero_cedula']);
        }
        
        $identOriginal = trim($currentCliente['identificacion'] ?? '');

        // Verificar duplicado de identificación fiscal solo si cambió
        if ($identificacion !== '' && $identificacion !== $identOriginal && $modelo->identificacionExiste($identificacion, (int)$id)) {
            $this->renderizar('clientes/vistas/editar', [
                'titulo' => 'Editar Cliente', 
                'error' => 'Esta identificación fiscal ya pertenece a otro cliente.', 
                'cliente' => $datos,
                'cuentas_contables' => $this->obtenerCuentasContables()
            ]); 
            return;
        }

        if ($modelo->actualizar((int)$id, $datos)) {
            // Formar nombre para la bitácora
            $nombre_razon_social = trim($datos['nombre_cliente']);
            if (($datos['tipo_cliente'] ?? '') === 'Natural') {
                $parts = [trim($datos['nombre_cliente'])];
                if (!empty($datos['primer_apellido'])) $parts[] = trim($datos['primer_apellido']);
                if (!empty($datos['segundo_apellido'])) $parts[] = trim($datos['segundo_apellido']);
                $nombre_razon_social = implode(' ', $parts);
            }

            registrarBitacora('clientes', 'editar', 'Actualizado cliente: ' . $nombre_razon_social, (int)$id);
            $respuesta->redirigir('/Cycsa/publico/clientes');
            return;
        } else {
            $this->renderizar('clientes/vistas/editar', [
                'titulo' => 'Editar Cliente', 
                'error' => 'Error al guardar los cambios en la base de datos.', 
                'cliente' => $datos,
                'cuentas_contables' => $this->obtenerCuentasContables()
            ]);
        }
    }

    // 🔍 BUSCAR CLIENTES VÍA AJAX (Para autocompletado en cotizaciones)
    public function buscarAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $busqueda = $_GET['q'] ?? '';
        $modelo = new ClienteModelo();
        $clientes = $modelo->obtenerTodos($busqueda);
        
        $resultados = [];
        foreach ($clientes as $cli) {
            if ($cli['activo'] == 1) {
                $resultados[] = [
                    'id' => $cli['id'],
                    'nombre' => $cli['nombre_razon_social'],
                    'identificacion' => $cli['identificacion'] ?? 'Sin RUC'
                ];
            }
        }
        $respuesta->enviarJson($resultados);
    }

    // 🔍 BUSCAR CLIENTE POR RUC O CÉDULA VÍA AJAX (Público - Sin sesión)
    public function buscarPorIdentificacionPublico(Peticion $peticion, Respuesta $respuesta): void {
        $identificacion = trim($_GET['identificacion'] ?? '');
        if ($identificacion === '') {
            $respuesta->enviarJson(['existe' => false]);
            return;
        }
        $modelo = new ClienteModelo();
        $cliente = $modelo->obtenerPorIdentificacion($identificacion);
        if ($cliente) {
            $respuesta->enviarJson([
                'existe' => true,
                'cliente' => [
                    'id' => $cliente['id'],
                    'nombre_razon_social' => $cliente['nombre_razon_social'],
                    'nombre_cliente' => $cliente['nombre_cliente'] ?? '',
                    'primer_apellido' => $cliente['primer_apellido'] ?? '',
                    'segundo_apellido' => $cliente['segundo_apellido'] ?? '',
                    'tipo_cliente' => $cliente['tipo_cliente'] ?? 'Jurídico',
                    'direccion' => $cliente['direccion'] ?? '',
                    'telefono' => $cliente['telefono'] ?? '',
                    'email' => $cliente['email'] ?? '',
                    'numero_ruc' => $cliente['numero_ruc'] ?? '',
                    'numero_cedula' => $cliente['numero_cedula'] ?? ''
                ]
            ]);
        } else {
            $respuesta->enviarJson(['existe' => false]);
        }
    }
}