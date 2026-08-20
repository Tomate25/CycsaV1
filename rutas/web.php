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
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;

// Importar Middlewares
use Cycsa\App\Middleware\AuthMiddleware;
use Cycsa\App\Middleware\AdminMiddleware;
use Cycsa\App\Middleware\ContabilidadMiddleware;

$app = Aplicacion::$app;

// 🌐 RUTAS PÚBLICAS DE AUTENTICACIÓN
$app->enrutador->get('/', [AutenticacionControlador::class, 'mostrarLogin']);
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

// 🌐 RUTAS PÚBLICAS DE CLIENTES (SIN LOGIN)
$app->enrutador->get('/solicitar-cotizacion', [CotizacionesControlador::class, 'mostrarSolicitudPublica']);
$app->enrutador->post('/solicitar-cotizacion', [CotizacionesControlador::class, 'procesarSolicitudPublica']);
$app->enrutador->get('/api/clientes/buscar-por-identificacion', [ClientesControlador::class, 'buscarPorIdentificacionPublico']);
$app->enrutador->get('/cotizaciones/decision-cliente', [CotizacionesControlador::class, 'decisionCliente']);
$app->enrutador->post('/cotizaciones/decision-cliente', [CotizacionesControlador::class, 'procesarDecisionCliente']);

// 🔒 RUTA SEGURA DEL PANEL DE CONTROL
$app->enrutador->get('/panel', [PanelControlador::class, 'index'], [AuthMiddleware::class]);
$app->enrutador->get('/panel/bitacora', [PanelControlador::class, 'bitacora'], [AuthMiddleware::class]);

// 🔒 RUTAS DE USUARIOS (REQUERIDO: SESIÓN Y ROL ADMINISTRADOR)
$app->enrutador->get('/usuarios', [UsuariosControlador::class, 'index'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->get('/usuarios/crear', [UsuariosControlador::class, 'crear'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/usuarios/crear', [UsuariosControlador::class, 'guardar'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->get('/usuarios/editar', [UsuariosControlador::class, 'editar'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/usuarios/editar', [UsuariosControlador::class, 'actualizar'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->get('/usuarios/eliminar', [UsuariosControlador::class, 'eliminar'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->get('/usuarios/desbloquear', [UsuariosControlador::class, 'desbloquear'], [AuthMiddleware::class, AdminMiddleware::class]);

// 🔒 RUTAS DE ROLES Y PERMISOS (REQUERIDO: SESIÓN Y ROL ADMINISTRADOR)
$app->enrutador->get('/roles', [RolesControlador::class, 'index'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->get('/roles/crear', [RolesControlador::class, 'crear'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/roles/crear', [RolesControlador::class, 'guardar'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->get('/roles/editar', [RolesControlador::class, 'editar'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/roles/editar', [RolesControlador::class, 'actualizar'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->get('/roles/eliminar', [RolesControlador::class, 'eliminar'], [AuthMiddleware::class, AdminMiddleware::class]);

// 🔒 RUTAS DE CONFIGURACIÓN COMERCIAL (REQUERIDO: SESIÓN Y ROL ADMINISTRADOR)
$app->enrutador->get('/configuracion', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'index'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/agregar-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'agregarAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/actualizar-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'actualizarAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/eliminar-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'eliminarAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/agregar-tecnico-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'agregarTecnicoAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/actualizar-tecnico-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'actualizarTecnicoAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/eliminar-tecnico-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'eliminarTecnicoAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/agregar-vehiculo-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'agregarVehiculoAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/actualizar-vehiculo-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'actualizarVehiculoAjax'], [AuthMiddleware::class, AdminMiddleware::class]);
$app->enrutador->post('/configuracion/eliminar-vehiculo-ajax', [\Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador::class, 'eliminarVehiculoAjax'], [AuthMiddleware::class, AdminMiddleware::class]);

// 🔒 RUTAS DE CLIENTES (REQUERIDO: SESIÓN ACTIVA)
$app->enrutador->get('/clientes', [ClientesControlador::class, 'index'], [AuthMiddleware::class]);
$app->enrutador->get('/clientes/crear', [ClientesControlador::class, 'crear'], [AuthMiddleware::class]);
$app->enrutador->post('/clientes/crear', [ClientesControlador::class, 'guardar'], [AuthMiddleware::class]);
$app->enrutador->get('/clientes/editar', [ClientesControlador::class, 'editar'], [AuthMiddleware::class]);
$app->enrutador->post('/clientes/editar', [ClientesControlador::class, 'actualizar'], [AuthMiddleware::class]);
$app->enrutador->get('/clientes/buscar-ajax', [ClientesControlador::class, 'buscarAjax'], [AuthMiddleware::class]);

// 🔒 RUTAS DE PRODUCTOS / ENSAYOS (REQUERIDO: SESIÓN ACTIVA)
$app->enrutador->get('/productos', [ProductosControlador::class, 'index'], [AuthMiddleware::class]);
$app->enrutador->get('/productos/crear', [ProductosControlador::class, 'crear'], [AuthMiddleware::class]);
$app->enrutador->post('/productos/crear', [ProductosControlador::class, 'guardar'], [AuthMiddleware::class]);
$app->enrutador->get('/productos/editar', [ProductosControlador::class, 'editar'], [AuthMiddleware::class]);
$app->enrutador->post('/productos/editar', [ProductosControlador::class, 'actualizar'], [AuthMiddleware::class]);
$app->enrutador->get('/productos/eliminar', [ProductosControlador::class, 'eliminar'], [AuthMiddleware::class]);

// 🔒 RUTAS DE COTIZACIONES (REQUERIDO: SESIÓN ACTIVA)
$app->enrutador->get('/cotizaciones', [CotizacionesControlador::class, 'index'], [AuthMiddleware::class]);
$app->enrutador->get('/cotizaciones/crear', [CotizacionesControlador::class, 'crear'], [AuthMiddleware::class]);
$app->enrutador->post('/cotizaciones/crear', [CotizacionesControlador::class, 'guardar'], [AuthMiddleware::class]);
$app->enrutador->get('/cotizaciones/detalle', [CotizacionesControlador::class, 'detalle'], [AuthMiddleware::class]);
$app->enrutador->get('/cotizaciones/bitacora-ajax', [CotizacionesControlador::class, 'obtenerBitacoraAjax'], [AuthMiddleware::class]);
$app->enrutador->post('/cotizaciones/revision', [CotizacionesControlador::class, 'procesarRevision'], [AuthMiddleware::class]);
$app->enrutador->get('/cotizaciones/editar', [CotizacionesControlador::class, 'editar'], [AuthMiddleware::class]);
$app->enrutador->post('/cotizaciones/editar', [CotizacionesControlador::class, 'actualizar'], [AuthMiddleware::class]);
$app->enrutador->post('/cotizaciones/enviar', [CotizacionesControlador::class, 'enviarCliente'], [AuthMiddleware::class]);
$app->enrutador->post('/cotizaciones/enviar-revision', [CotizacionesControlador::class, 'enviarRevision'], [AuthMiddleware::class]);
$app->enrutador->post('/cotizaciones/decision-administrativa', [CotizacionesControlador::class, 'procesarDecisionAdministrativa'], [AuthMiddleware::class]);
$app->enrutador->get('/cotizaciones/imprimir', [CotizacionesControlador::class, 'imprimir'], [AuthMiddleware::class]);
$app->enrutador->post('/cotizaciones/guardar-resultados-item', [CotizacionesControlador::class, 'guardarResultadosItem'], [AuthMiddleware::class]);
$app->enrutador->get('/cotizaciones/imprimir-reporte-item', [CotizacionesControlador::class, 'imprimirReporteItem'], [AuthMiddleware::class]);

// 🔒 RUTAS DEL MÓDULO DE ÓRDENES DE SERVICIO (CYCSA-RG-FM-39 V1)
$app->enrutador->get('/ordenes-servicio', [OrdenesServicioControlador::class, 'index'], [AuthMiddleware::class]);
$app->enrutador->get('/ordenes-servicio/crear', [OrdenesServicioControlador::class, 'crear'], [AuthMiddleware::class]);
$app->enrutador->post('/ordenes-servicio/guardar', [OrdenesServicioControlador::class, 'guardar'], [AuthMiddleware::class]);
$app->enrutador->get('/ordenes-servicio/detalle', [OrdenesServicioControlador::class, 'detalle'], [AuthMiddleware::class]);
$app->enrutador->get('/ordenes-servicio/programar-muestreo', [OrdenesServicioControlador::class, 'programarMuestreo'], [AuthMiddleware::class]);
$app->enrutador->post('/ordenes-servicio/guardar-muestreo', [OrdenesServicioControlador::class, 'guardarProgramacionMuestreo'], [AuthMiddleware::class]);
$app->enrutador->post('/ordenes-servicio/finalizar-muestreo', [OrdenesServicioControlador::class, 'finalizarMuestreo'], [AuthMiddleware::class]);
$app->enrutador->post('/ordenes-servicio/marcar-ingreso-directo', [OrdenesServicioControlador::class, 'marcarIngresoDirectoAjax'], [AuthMiddleware::class]);

// 🔒 RUTAS DEL MÓDULO CONTABLE (REQUERIDO: SESIÓN Y PERMISO DE CONTABILIDAD)
$app->enrutador->get('/contabilidad', function($peticion, $respuesta) {
    $respuesta->redirigir('/Cycsa/publico/contabilidad/cuentas');
}, [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->get('/contabilidad/cuentas', [ContabilidadControlador::class, 'cuentas'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/guardar-cuenta', [ContabilidadControlador::class, 'guardarCuenta'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->get('/contabilidad/cxc', [ContabilidadControlador::class, 'cxc'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/guardar-cxc', [ContabilidadControlador::class, 'guardarCxc'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/pagar-cxc', [ContabilidadControlador::class, 'pagarCxc'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->get('/contabilidad/cxp', [ContabilidadControlador::class, 'cxp'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/guardar-cxp', [ContabilidadControlador::class, 'guardarCxp'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/pagar-cxp', [ContabilidadControlador::class, 'pagarCxp'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->get('/contabilidad/bancos', [ContabilidadControlador::class, 'bancos'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/guardar-banco', [ContabilidadControlador::class, 'guardarBanco'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/guardar-transaccion', [ContabilidadControlador::class, 'guardarTransaccion'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->get('/contabilidad/diario', [ContabilidadControlador::class, 'diario'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/guardar-partida', [ContabilidadControlador::class, 'guardarPartida'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->post('/contabilidad/sincronizar-diario', [ContabilidadControlador::class, 'sincronizarDiario'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->get('/contabilidad/balance', [ContabilidadControlador::class, 'balance'], [AuthMiddleware::class, ContabilidadMiddleware::class]);
$app->enrutador->get('/contabilidad/resultados', [ContabilidadControlador::class, 'resultados'], [AuthMiddleware::class, ContabilidadMiddleware::class]);

// 🔒 RUTAS DEL MÓDULO DE OPERACIONES (REQUERIDO: SESIÓN ACTIVA)
$app->enrutador->get('/operaciones', [OperacionesControlador::class, 'index'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/crear-os', [OperacionesControlador::class, 'crearOS'], [AuthMiddleware::class]);
$app->enrutador->get('/operaciones/recepcion', [OperacionesControlador::class, 'recepcionForm'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/guardar-recepcion', [OperacionesControlador::class, 'guardarRecepcion'], [AuthMiddleware::class]);
$app->enrutador->get('/operaciones/detalle-lote', [OperacionesControlador::class, 'detalleLote'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/guardar-ruptura', [OperacionesControlador::class, 'guardarRuptura'], [AuthMiddleware::class]);
$app->enrutador->get('/operaciones/detalle-ajax', [OperacionesControlador::class, 'detalleAjax'], [AuthMiddleware::class]);
$app->enrutador->get('/operaciones/calendario', [OperacionesControlador::class, 'calendario'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/generar-informe', [OperacionesControlador::class, 'generarInforme'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/cambiar-estado-informe', [OperacionesControlador::class, 'cambiarEstadoInforme'], [AuthMiddleware::class]);
$app->enrutador->get('/informes/descargar', [OperacionesControlador::class, 'descargarInforme'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/actualizar-estado', [OperacionesControlador::class, 'actualizarEstado'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/programar-muestreo', [OperacionesControlador::class, 'procesarProgramarMuestreo'], [AuthMiddleware::class]);
$app->enrutador->get('/operaciones/obtener-matriz-os', [OperacionesControlador::class, 'obtenerMatrizOSAjax'], [AuthMiddleware::class]);
$app->enrutador->get('/operaciones/captura-matriz', [OperacionesControlador::class, 'capturaMatrizProducto'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/guardar-matriz-producto', [OperacionesControlador::class, 'guardarMatrizProductoPOST'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/guardar-hoja-campo', [OperacionesControlador::class, 'guardarHojaCampo'], [AuthMiddleware::class]);
$app->enrutador->post('/operaciones/omitir-espera', [OperacionesControlador::class, 'omitirEsperaMuestreo'], [AuthMiddleware::class]);
    // 🔒 RUTAS DEL MÓDULO NUEVO HOJAS DE SERVICIO (CYCSA-RT-FM-13)
    $app->enrutador->get('/hojas-servicio', [HojasServicioControlador::class, 'index'], [AuthMiddleware::class]);
    $app->enrutador->get('/hojas-servicio/datos', [HojasServicioControlador::class, 'hojaSolicitudDatosAjax'], [AuthMiddleware::class]);
    $app->enrutador->post('/hojas-servicio/guardar', [HojasServicioControlador::class, 'guardarHojaSolicitud'], [AuthMiddleware::class]);
    $app->enrutador->post('/hojas-servicio/enviar-revision', [HojasServicioControlador::class, 'enviarRevision'], [AuthMiddleware::class]);
    $app->enrutador->post('/hojas-servicio/procesar-revision', [HojasServicioControlador::class, 'procesarRevision'], [AuthMiddleware::class]);
    $app->enrutador->get('/hojas-servicio/descargar', [HojasServicioControlador::class, 'descargarSolicitudPDF'], [AuthMiddleware::class]);

    $app->enrutador->post('/operaciones/emitir-solicitud', [OperacionesControlador::class, 'emitirSolicitud'], [AuthMiddleware::class]);
    $app->enrutador->post('/operaciones/enviar-revision-resultados', [OperacionesControlador::class, 'enviarRevisionResultados'], [AuthMiddleware::class]);
    $app->enrutador->post('/operaciones/procesar-revision-resultados', [OperacionesControlador::class, 'procesarRevisionResultados'], [AuthMiddleware::class]);

// 🔒 MÓDULO DE LABORATORIO AISLADO (REQUERIDO: SESIÓN ACTIVA)
$app->enrutador->get('/laboratorio', [LaboratorioControlador::class, 'index'], [AuthMiddleware::class]);
$app->enrutador->get('/laboratorio/detalle-muestra', [LaboratorioControlador::class, 'detalleMuestra'], [AuthMiddleware::class]);
$app->enrutador->post('/laboratorio/guardar-ruptura', [LaboratorioControlador::class, 'guardarRuptura'], [AuthMiddleware::class]);

// 🌐 REDIRECCIÓN DE RAÍZ
$app->enrutador->get('/', function($peticion, $respuesta) {
    $respuesta->redirigir('/Cycsa/publico/panel');
});