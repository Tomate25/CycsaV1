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
use Cycsa\Modulos\Operaciones\Controladores\LaboratorioControlador;




$app = Aplicacion::$app;

// Rutas de Autenticación
$app->enrutador->get('/login', [AutenticacionControlador::class, 'mostrarLogin']);
$app->enrutador->post('/login', [AutenticacionControlador::class, 'procesarLogin']);
$app->enrutador->get('/logout', [AutenticacionControlador::class, 'cerrarSesion']);
$app->enrutador->get('/verificar-sesion-activa', [AutenticacionControlador::class, 'verificarSesionActiva']);
$app->enrutador->get('/cambiar-password-obligatorio', [AutenticacionControlador::class, 'mostrarCambiarPasswordObligatorio']);
$app->enrutador->post('/cambiar-password-obligatorio', [AutenticacionControlador::class, 'procesarCambiarPasswordObligatorio']);
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
$app->enrutador->get('/usuarios/desbloquear', [UsuariosControlador::class, 'desbloquear']);

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
$app->enrutador->post('/configuracion/actualizar-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'actualizarAjax']);
$app->enrutador->post('/configuracion/eliminar-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'eliminarAjax']);
$app->enrutador->post('/configuracion/agregar-tecnico-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'agregarTecnicoAjax']);
$app->enrutador->post('/configuracion/actualizar-tecnico-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'actualizarTecnicoAjax']);
$app->enrutador->post('/configuracion/eliminar-tecnico-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'eliminarTecnicoAjax']);
$app->enrutador->post('/configuracion/agregar-vehiculo-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'agregarVehiculoAjax']);
$app->enrutador->post('/configuracion/actualizar-vehiculo-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'actualizarVehiculoAjax']);
$app->enrutador->post('/configuracion/eliminar-vehiculo-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'eliminarVehiculoAjax']);

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
$app->enrutador->get('/cotizaciones/imprimir', [CotizacionesControlador::class, 'imprimir']);
$app->enrutador->post('/cotizaciones/guardar-resultados-item', [CotizacionesControlador::class, 'guardarResultadosItem']);
$app->enrutador->get('/cotizaciones/imprimir-reporte-item', [CotizacionesControlador::class, 'imprimirReporteItem']);

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
$app->enrutador->get('/contabilidad/diario', [ContabilidadControlador::class, 'diario']);
$app->enrutador->post('/contabilidad/guardar-partida', [ContabilidadControlador::class, 'guardarPartida']);
$app->enrutador->post('/contabilidad/sincronizar-diario', [ContabilidadControlador::class, 'sincronizarDiario']);
$app->enrutador->get('/contabilidad/balance', [ContabilidadControlador::class, 'balance']);
$app->enrutador->get('/contabilidad/resultados', [ContabilidadControlador::class, 'resultados']);

// Rutas del Módulo de Operaciones
$app->enrutador->get('/operaciones', [OperacionesControlador::class, 'index']);
$app->enrutador->post('/operaciones/crear-os', [OperacionesControlador::class, 'crearOS']);
$app->enrutador->get('/operaciones/recepcion', [OperacionesControlador::class, 'recepcionForm']);
$app->enrutador->post('/operaciones/guardar-recepcion', [OperacionesControlador::class, 'guardarRecepcion']);
$app->enrutador->get('/operaciones/detalle-lote', [OperacionesControlador::class, 'detalleLote']);
$app->enrutador->post('/operaciones/guardar-ruptura', [OperacionesControlador::class, 'guardarRuptura']);
$app->enrutador->get('/operaciones/detalle-ajax', [OperacionesControlador::class, 'detalleAjax']);
$app->enrutador->get('/operaciones/calendario', [OperacionesControlador::class, 'calendario']);
$app->enrutador->post('/operaciones/generar-informe', [OperacionesControlador::class, 'generarInforme']);
$app->enrutador->post('/operaciones/cambiar-estado-informe', [OperacionesControlador::class, 'cambiarEstadoInforme']);
$app->enrutador->get('/informes/descargar', [OperacionesControlador::class, 'descargarInforme']);
$app->enrutador->post('/operaciones/actualizar-estado', [OperacionesControlador::class, 'actualizarEstado']);
$app->enrutador->post('/operaciones/programar-muestreo', [OperacionesControlador::class, 'procesarProgramarMuestreo']);
$app->enrutador->post('/operaciones/guardar-hoja-campo', [OperacionesControlador::class, 'guardarHojaCampo']);
$app->enrutador->get('/operaciones/hoja-solicitud', [OperacionesControlador::class, 'hojaSolicitudForm']);
$app->enrutador->get('/operaciones/hoja-solicitud-datos', [OperacionesControlador::class, 'hojaSolicitudDatosAjax']);
$app->enrutador->get('/operaciones/descargar-solicitud', [OperacionesControlador::class, 'descargarSolicitudPDF']);
$app->enrutador->post('/operaciones/guardar-hoja-solicitud', [OperacionesControlador::class, 'guardarHojaSolicitud']);
$app->enrutador->post('/operaciones/emitir-solicitud', [OperacionesControlador::class, 'emitirSolicitud']);
$app->enrutador->post('/operaciones/enviar-revision-resultados', [OperacionesControlador::class, 'enviarRevisionResultados']);
$app->enrutador->post('/operaciones/procesar-revision-resultados', [OperacionesControlador::class, 'procesarRevisionResultados']);

// Módulo de Laboratorio Aislado (Operación Ciega total ISO 17025)
$app->enrutador->get('/laboratorio', [LaboratorioControlador::class, 'index']);
$app->enrutador->get('/laboratorio/detalle-muestra', [LaboratorioControlador::class, 'detalleMuestra']);
$app->enrutador->post('/laboratorio/guardar-ruptura', [LaboratorioControlador::class, 'guardarRuptura']);

// Redirección de Raíz
$app->enrutador->get('/', function($peticion, $respuesta) {
    $respuesta->redirigir('/Cycsa/publico/panel');
});