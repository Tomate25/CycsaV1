<?php

use Cycsa\Nucleo\Aplicacion;
use Cycsa\Modulos\Autenticacion\Controladores\AutenticacionControlador;
use Cycsa\Modulos\Usuarios\Controladores\PanelControlador;
use Cycsa\Modulos\Usuarios\Controladores\UsuariosControlador;
use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;

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

// Rutas de Cotizaciones
$app->enrutador->get('/cotizaciones', [CotizacionesControlador::class, 'index']);

// Si entran a la raíz, los mandamos al panel directamente
$app->enrutador->get('/', function($peticion, $respuesta) {
    $respuesta->redirigir('/Cycsa/publico/panel');
});