<?php

use Cycsa\Nucleo\Aplicacion;
use Cycsa\Modulos\Autenticacion\Controladores\AutenticacionControlador;
use Cycsa\Modulos\Usuarios\Controladores\PanelControlador;
use Cycsa\Modulos\Usuarios\Controladores\UsuariosControlador;
use Cycsa\Modulos\Usuarios\Controladores\RolesControlador;
use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Modulos\Clientes\Controladores\ClientesControlador;
use Cycsa\Modulos\Productos\Controladores\ProductosControlador;
use Cycsa\Modulos\Contabilidad\Controladores\ContabilidadControlador;
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;




$app = Aplicacion::$app;

// Rutas de Autenticación
$app->enrutador->get('/login', [AutenticacionControlador::class, 'mostrarLogin']);
$app->enrutador->post('/login', [AutenticacionControlador::class, 'procesarLogin']);
$app->enrutador->get('/logout', [AutenticacionControlador::class, 'cerrarSesion']);
$app->enrutador->get('/verificar-sesion-activa', [AutenticacionControlador::class, 'verificarSesionActiva']);
$app->enrutador->get('/recuperar-password', [AutenticacionControlador::class, 'mostrarRecuperarPassword']);
$app->enrutador->post('/recuperar-password', [AutenticacionControlador::class, 'procesarRecuperarPassword']);
$app->enrutador->get('/restablecer-password', [AutenticacionControlador::class, 'mostrarRestablecerPassword']);
$app->enrutador->post('/restablecer-password', [AutenticacionControlador::class, 'procesarRestablecerPassword']);

// Ruta Segura del Panel de Control
$app->enrutador->get('/panel', [PanelControlador::class, 'index']);
$app->enrutador->get('/panel/bitacora', [PanelControlador::class, 'bitacora']);

// Rutas de Usuarios
$app->enrutador->get('/usuarios', [UsuariosControlador::class, 'index']);
$app->enrutador->get('/usuarios/crear', [UsuariosControlador::class, 'crear']);
$app->enrutador->post('/usuarios/crear', [UsuariosControlador::class, 'guardar']);
$app->enrutador->get('/usuarios/editar', [UsuariosControlador::class, 'editar']);
$app->enrutador->post('/usuarios/editar', [UsuariosControlador::class, 'actualizar']);
$app->enrutador->get('/usuarios/eliminar', [UsuariosControlador::class, 'eliminar']);

// Rutas de Roles y Permisos (Solo Admin)
$app->enrutador->get('/roles', [RolesControlador::class, 'index']);
$app->enrutador->get('/roles/crear', [RolesControlador::class, 'crear']);
$app->enrutador->post('/roles/crear', [RolesControlador::class, 'guardar']);
$app->enrutador->get('/roles/editar', [RolesControlador::class, 'editar']);
$app->enrutador->post('/roles/editar', [RolesControlador::class, 'actualizar']);
$app->enrutador->get('/roles/eliminar', [RolesControlador::class, 'eliminar']);

// Rutas de Clientes
$app->enrutador->get('/clientes', [ClientesControlador::class, 'index']);
$app->enrutador->get('/clientes/crear', [ClientesControlador::class, 'crear']);
$app->enrutador->post('/clientes/crear', [ClientesControlador::class, 'guardar']);
$app->enrutador->get('/clientes/editar', [ClientesControlador::class, 'editar']);
$app->enrutador->post('/clientes/editar', [ClientesControlador::class, 'actualizar']);
$app->enrutador->get('/clientes/buscar-ajax', [ClientesControlador::class, 'buscarAjax']);

// Rutas de Configuración Comercial (Solo Admin)
$app->enrutador->get('/configuracion', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'index']);
$app->enrutador->post('/configuracion/agregar-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'agregarAjax']);
$app->enrutador->post('/configuracion/eliminar-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'eliminarAjax']);

// Rutas de Productos / Ensayos
$app->enrutador->get('/productos', [ProductosControlador::class, 'index']);
$app->enrutador->get('/productos/crear', [ProductosControlador::class, 'crear']);
$app->enrutador->post('/productos/crear', [ProductosControlador::class, 'guardar']);
$app->enrutador->get('/productos/editar', [ProductosControlador::class, 'editar']);
$app->enrutador->post('/productos/editar', [ProductosControlador::class, 'actualizar']);
$app->enrutador->get('/productos/eliminar', [ProductosControlador::class, 'eliminar']);

// Rutas de Cotizaciones
$app->enrutador->get('/cotizaciones', [CotizacionesControlador::class, 'index']);
$app->enrutador->get('/cotizaciones/crear', [CotizacionesControlador::class, 'crear']);
$app->enrutador->post('/cotizaciones/crear', [CotizacionesControlador::class, 'guardar']);
$app->enrutador->get('/cotizaciones/detalle', [CotizacionesControlador::class, 'detalle']);
$app->enrutador->get('/cotizaciones/bitacora-ajax', [CotizacionesControlador::class, 'obtenerBitacoraAjax']);
$app->enrutador->post('/cotizaciones/revision', [CotizacionesControlador::class, 'procesarRevision']);
$app->enrutador->get('/cotizaciones/editar', [CotizacionesControlador::class, 'editar']);
$app->enrutador->post('/cotizaciones/editar', [CotizacionesControlador::class, 'actualizar']);
$app->enrutador->post('/cotizaciones/enviar', [CotizacionesControlador::class, 'enviarCliente']);
$app->enrutador->post('/cotizaciones/enviar-revision', [CotizacionesControlador::class, 'enviarRevision']);
$app->enrutador->post('/cotizaciones/decision-administrativa', [CotizacionesControlador::class, 'procesarDecisionAdministrativa']);

// Rutas Públicas de Decisión de Cliente
$app->enrutador->get('/cotizaciones/decision-cliente', [CotizacionesControlador::class, 'decisionCliente']);
$app->enrutador->post('/cotizaciones/decision-cliente', [CotizacionesControlador::class, 'procesarDecisionCliente']);

// Rutas del Módulo Contable
$app->enrutador->get('/contabilidad', function($peticion, $respuesta) {
    $respuesta->redirigir('/Cycsa/publico/contabilidad/cuentas');
});
$app->enrutador->get('/contabilidad/cuentas', [ContabilidadControlador::class, 'cuentas']);
$app->enrutador->post('/contabilidad/guardar-cuenta', [ContabilidadControlador::class, 'guardarCuenta']);
$app->enrutador->get('/contabilidad/cxc', [ContabilidadControlador::class, 'cxc']);
$app->enrutador->post('/contabilidad/guardar-cxc', [ContabilidadControlador::class, 'guardarCxc']);
$app->enrutador->post('/contabilidad/pagar-cxc', [ContabilidadControlador::class, 'pagarCxc']);
$app->enrutador->get('/contabilidad/cxp', [ContabilidadControlador::class, 'cxp']);
$app->enrutador->post('/contabilidad/guardar-cxp', [ContabilidadControlador::class, 'guardarCxp']);
$app->enrutador->post('/contabilidad/pagar-cxp', [ContabilidadControlador::class, 'pagarCxp']);
$app->enrutador->get('/contabilidad/bancos', [ContabilidadControlador::class, 'bancos']);
$app->enrutador->post('/contabilidad/guardar-banco', [ContabilidadControlador::class, 'guardarBanco']);
$app->enrutador->post('/contabilidad/guardar-transaccion', [ContabilidadControlador::class, 'guardarTransaccion']);

// Rutas del Módulo de Operaciones
$app->enrutador->get('/operaciones', [OperacionesControlador::class, 'index']);
$app->enrutador->post('/operaciones/guardar', [OperacionesControlador::class, 'guardar']);
$app->enrutador->get('/operaciones/detalle-ajax', [OperacionesControlador::class, 'detalleAjax']);
$app->enrutador->get('/operaciones/calendario', [OperacionesControlador::class, 'calendario']);


// Redirección de Raíz
$app->enrutador->get('/', function($peticion, $respuesta) {
    $respuesta->redirigir('/Cycsa/publico/panel');
});