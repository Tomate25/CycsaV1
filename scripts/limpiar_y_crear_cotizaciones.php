<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
require_once __DIR__ . '/../app/Helpers/funciones.php';
require_once __DIR__ . '/../app/Modulos/Cotizaciones/Modelos/CotizacionModelo.php';

use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;

$db = Conexion::obtenerInstancia();

// 🔒 GUARDIA CRÍTICA DE SEGURIDAD: Prohibido ejecutar en entornos de producción
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'local';
if ($appEnv === 'produccion' || $appEnv === 'production') {
    die("⛔ ERROR CRÍTICO DE SEGURIDAD: Este script es destructivo y está terminantemente prohibido ejecutarlo en el entorno de producción (cycsanic_cycsa_db).\n");
}

echo "========================================================\n";
echo "1. LIMPIEZA TOTAL DE TABLAS TRANSACCIONALES EN CYCSA_DB\n";
echo "========================================================\n";

$db->exec("SET FOREIGN_KEY_CHECKS = 0");

$tablasTruncar = [
    'cotizacion_versiones',
    'cotizacion_detalles',
    'cotizaciones',
    'programacion_muestreo',
    'hojas_solicitud',
    'ensayo_edades',
    'lotes_muestras',
    'recepcion_muestras',
    'informes_control',
    'ordenes_servicio',
    'cuentas_por_cobrar',
    'cuentas_por_pagar',
    'partidas_diario_detalles',
    'partidas_diario',
    'bancos_transacciones',
    'secuencias_muestras',
    'bitacora'
];

foreach ($tablasTruncar as $tabla) {
    $db->exec("TRUNCATE TABLE `$tabla`");
    echo "  -> Tabla `$tabla` truncada con éxito (AUTO_INCREMENT reiniciado a 1).\n";
}

$db->exec("SET FOREIGN_KEY_CHECKS = 1");

// Limpiar archivos PDF de prueba temporales
$archivosLimpiar = glob(__DIR__ . '/../almacenamiento/solicitudes/*.pdf');
foreach ($archivosLimpiar as $archivo) {
    if (is_file($archivo)) {
        @unlink($archivo);
        echo "  -> PDF temporal eliminado: " . basename($archivo) . "\n";
    }
}

echo "\n========================================================\n";
echo "2. CREACIÓN DE 2 COTIZACIONES DE DENSÍMETRO NUCLEAR\n";
echo "========================================================\n";

$modelo = new CotizacionModelo();

// COTIZACIÓN 1
$cabecera1 = [
    'codigo' => 'COT-2026-0001',
    'id_cliente' => 1, // IALSA CONSTRUCCIONES
    'tipo_moneda' => 1, // C$
    'id_usuario_creador' => 1, // Abdias
    'atencion_a' => 'Ing. Abdías López',
    'nombre_proyecto' => 'Proyecto Pistas y Terraplenes - Tramo 1',
    'direccion_proyecto' => 'Carretera Norte Km 14.5, Managua',
    'prioridad' => 'Normal',
    'fecha_limite' => date('Y-m-d', strtotime('+15 days')),
    'condicion_pago' => 'Pago contra entrega',
    'tiempo_entrega' => '3 a 5 días hábiles',
    'vigencia_oferta' => '15 días calendario',
    'configuracion_notas' => json_encode(['nota' => 'Cotización oficial de ensayo in situ de densímetro nuclear Troxler 3440']),
    'contactos' => 'Ing. Abdías López - 8888-8888',
    'incluir_anexo_tecnico' => 0,
    'anexo_tecnico' => null,
    'archivo_adjunto' => null,
    'subtotal' => 6500.00,
    'descuento' => 0.00,
    'exonerado' => 0,
    'exoneracion_no' => null,
    'impuesto' => 975.00,
    'total' => 7475.00,
    'fecha_entrega' => date('Y-m-d', strtotime('+5 days')),
    'fecha_seguimiento' => date('Y-m-d', strtotime('+2 days'))
];

$detalles1 = [
    [
        'id_producto' => 29,
        'descripcion' => 'Densidad y Humedad In Situ (Densímetro Nuclear) – ASTM D6938-23',
        'condiciones_muestra' => 'Muestreo en obra / in situ',
        'procedimiento' => 'CYCSA-PE-25',
        'unidad_medida' => 'Ensayo',
        'codigo_servicio' => 'MC-D6938',
        'norma_astm' => 'ASTM D6938-23',
        'formato_reporte' => 'CYCSA-RT-FM-22 B',
        'observaciones' => '5 pruebas in situ de control de compactación y humedad en sub-base',
        'descripcion_adicional' => 'Troxler 3440',
        'cantidad' => 5,
        'precio' => 1300.00,
        'subtotal' => 6500.00
    ]
];

$ok1 = $modelo->guardarCotizacionCompleta($cabecera1, $detalles1);
echo $ok1 ? "  -> Cotización 1 creada en Borrador: COT-2026-0001\n" : "  [ERROR] Falló crear Cotización 1\n";

// Aprobar formalmente Cotización 1
$aprob1 = $modelo->registrarDecisionCliente(1, 'Aprobada por Cliente', 'Aprobada por cliente formalmente', 'Efectivo', null, 'REC-001', 100, 0, 7475.00);
echo $aprob1 ? "  -> Cotización 1 APROBADA (Estado: Aprobada por Cliente / O/S OS-2026-0001 inicializada)\n" : "  [ERROR] Falló aprobar Cotización 1\n";

// COTIZACIÓN 2
$cabecera2 = [
    'codigo' => 'COT-2026-0002',
    'id_cliente' => 2, // OLAM NICARAGUA, S.A.
    'tipo_moneda' => 1, // C$
    'id_usuario_creador' => 1, // Abdias
    'atencion_a' => 'Ing. Ervin López',
    'nombre_proyecto' => 'Proyecto Urbanización Los Laureles - Sector B',
    'direccion_proyecto' => 'Sébaco, Matagalpa',
    'prioridad' => 'Normal',
    'fecha_limite' => date('Y-m-d', strtotime('+15 days')),
    'condicion_pago' => 'Pago contra entrega',
    'tiempo_entrega' => '3 a 5 días hábiles',
    'vigencia_oferta' => '15 días calendario',
    'configuracion_notas' => json_encode(['nota' => 'Cotización oficial de control de compactación y densidad Troxler']),
    'contactos' => 'Ervin López - 8765-4321',
    'incluir_anexo_tecnico' => 0,
    'anexo_tecnico' => null,
    'archivo_adjunto' => null,
    'subtotal' => 6500.00,
    'descuento' => 0.00,
    'exonerado' => 0,
    'exoneracion_no' => null,
    'impuesto' => 975.00,
    'total' => 7475.00,
    'fecha_entrega' => date('Y-m-d', strtotime('+5 days')),
    'fecha_seguimiento' => date('Y-m-d', strtotime('+2 days'))
];

$detalles2 = [
    [
        'id_producto' => 29,
        'descripcion' => 'Densidad y Humedad In Situ (Densímetro Nuclear) – ASTM D6938-23',
        'condiciones_muestra' => 'Muestreo en obra / in situ',
        'procedimiento' => 'CYCSA-PE-25',
        'unidad_medida' => 'Ensayo',
        'codigo_servicio' => 'MC-D6938',
        'norma_astm' => 'ASTM D6938-23',
        'formato_reporte' => 'CYCSA-RT-FM-22 B',
        'observaciones' => '5 pruebas in situ de control de compactación y humedad en terraplén',
        'descripcion_adicional' => 'Troxler 3440',
        'cantidad' => 5,
        'precio' => 1300.00,
        'subtotal' => 6500.00
    ]
];

$ok2 = $modelo->guardarCotizacionCompleta($cabecera2, $detalles2);
echo $ok2 ? "  -> Cotización 2 creada en Borrador: COT-2026-0002\n" : "  [ERROR] Falló crear Cotización 2\n";

// Aprobar formalmente Cotización 2
$aprob2 = $modelo->registrarDecisionCliente(2, 'Aprobada por Cliente', 'Aprobada por cliente formalmente', 'Efectivo', null, 'REC-002', 100, 0, 7475.00);
echo $aprob2 ? "  -> Cotización 2 APROBADA (Estado: Aprobada por Cliente / O/S OS-2026-0002 inicializada)\n" : "  [ERROR] Falló aprobar Cotización 2\n";

echo "\n========================================================\n";
echo "3. RESUMEN DE BASE DE DATOS TRAS LIMPIEZA Y GENERACIÓN\n";
echo "========================================================\n";

$cotizaciones = $db->query("SELECT id, codigo, nombre_proyecto, total, estado FROM cotizaciones ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "COTIZACIONES EN BD:\n";
foreach ($cotizaciones as $c) {
    echo "  - ID {$c['id']} | {$c['codigo']} | Proyecto: {$c['nombre_proyecto']} | Total: C$ " . number_format($c['total'], 2) . " | Estado: {$c['estado']}\n";
}

$ordenes = $db->query("SELECT id, codigo_os, id_cotizacion, estado, requiere_muestreo FROM ordenes_servicio ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "\nORDENES DE SERVICIO INICIALIZADAS:\n";
foreach ($ordenes as $o) {
    echo "  - ID {$o['id']} | {$o['codigo_os']} | Cotización ID: {$o['id_cotizacion']} | Estado: {$o['estado']} | ReqMuestreo: " . var_export($o['requiere_muestreo'], true) . "\n";
}

$hojas = $db->query("SELECT COUNT(*) FROM hojas_solicitud")->fetchColumn();
echo "\nHOJAS RT-FM-13 REGISTRADAS: $hojas (Totalmente limpias)\n";

$matrices = $db->query("SELECT COUNT(*) FROM cotizacion_detalles WHERE resultados_json IS NOT NULL")->fetchColumn();
echo "MATRICES TÉCNICAS REGISTRADAS: $matrices (Totalmente limpias)\n";

echo "\n[LISTO] Base de datos limpia y preparada para que Abdia realice el flujo operativo.\n";
