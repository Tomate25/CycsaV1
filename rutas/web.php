<?php

use Cycsa\Nucleo\Aplicacion;
use Cycsa\Modulos\Autenticacion\Controladores\AutenticacionControlador;
use Cycsa\Modulos\Usuarios\Controladores\PanelControlador;
use Cycsa\Modulos\Usuarios\Controladores\UsuariosControlador;
use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Modulos\Clientes\Controladores\ClientesControlador;


$app = Aplicacion::$app;

// Rutas de Autenticación
$app->enrutador->get('/login', [AutenticacionControlador::class, 'mostrarLogin']);
$app->enrutador->post('/login', [AutenticacionControlador::class, 'procesarLogin']);
$app->enrutador->get('/logout', [AutenticacionControlador::class, 'cerrarSesion']);

// Ruta Segura del Panel de Control
$app->enrutador->get('/panel', [PanelControlador::class, 'index']);

// Rutas de Usuarios
$app->enrutador->get('/usuarios', [UsuariosControlador::class, 'index']);
$app->enrutador->get('/usuarios/crear', [UsuariosControlador::class, 'crear']);
$app->enrutador->post('/usuarios/crear', [UsuariosControlador::class, 'guardar']);

$app->enrutador->get('/usuarios/editar', [UsuariosControlador::class, 'editar']);
$app->enrutador->post('/usuarios/editar', [UsuariosControlador::class, 'actualizar']);
$app->enrutador->get('/usuarios/eliminar', [UsuariosControlador::class, 'eliminar']);

// Rutas de Clientes
$app->enrutador->get('/clientes', [ClientesControlador::class, 'index']);
$app->enrutador->get('/clientes/crear', [ClientesControlador::class, 'crear']);
$app->enrutador->post('/clientes/crear', [ClientesControlador::class, 'guardar']);
// ✏️ NUEVAS RUTAS DE EDICIÓN
$app->enrutador->get('/clientes/editar', [ClientesControlador::class, 'editar']);
$app->enrutador->post('/clientes/editar', [ClientesControlador::class, 'actualizar']);

// Rutas de Cotizaciones
$app->enrutador->get('/cotizaciones', [CotizacionesControlador::class, 'index']);
$app->enrutador->get('/cotizaciones/crear', [CotizacionesControlador::class, 'crear']);
$app->enrutador->post('/cotizaciones/crear', [CotizacionesControlador::class, 'guardar']);
$app->enrutador->get('/cotizaciones/detalle', [CotizacionesControlador::class, 'detalle']);
$app->enrutador->post('/cotizaciones/revision', [CotizacionesControlador::class, 'procesarRevision']);
$app->enrutador->get('/cotizaciones/editar', [CotizacionesControlador::class, 'editar']);
$app->enrutador->post('/cotizaciones/editar', [CotizacionesControlador::class, 'actualizar']);
$app->enrutador->post('/cotizaciones/enviar', [CotizacionesControlador::class, 'enviarCliente']);
$app->enrutador->post('/cotizaciones/enviar-revision', [CotizacionesControlador::class, 'enviarRevision']);

// Rutas Públicas de Decisión de Cliente
$app->enrutador->get('/cotizaciones/decision-cliente', [CotizacionesControlador::class, 'decisionCliente']);
$app->enrutador->post('/cotizaciones/decision-cliente', [CotizacionesControlador::class, 'procesarDecisionCliente']);

// Redirección de Raíz
$app->enrutador->get('/', function($peticion, $respuesta) {
    $respuesta->redirigir('/Cycsa/publico/panel');
});