<?php
/**
 * Script de Limpieza Integral de Base de Datos para Pruebas Operativas (CYCSA ERP / LIMS)
 *
 * Trunca todas las tablas operativas/transaccionales reiniciando AUTO_INCREMENT a 1
 * y preservando intactos todos los catálogos maestros (usuarios, clientes, productos,
 * plantillas dinámicas, formatos de ensayos, cuentas contables, bancos, etc.).
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';

use Cycsa\Nucleo\Conexion;

$db = Conexion::obtenerInstancia();

echo "====================================================================\n";
echo "   LIMPIEZA DE BASE DE DATOS CYCSA ERP / LIMS (MODO PRUEBAS)\n";
echo "====================================================================\n\n";

$db->exec("SET FOREIGN_KEY_CHECKS = 0");

$tablasOperativas = [
    'bitacora_reidentificacion',
    'bitacora',
    'roturas',
    'informes_rotura',
    'plan_rotura_asignaciones',
    'plan_rotura_edades',
    'planes_rotura',
    'especimenes',
    'ensayos_parametros',
    'ensayo_edades',
    'lotes_muestras',
    'recepcion_muestras',
    'hojas_solicitud_detalles',
    'hojas_solicitud',
    'programacion_muestreo',
    'informes_control',
    'cuentas_por_cobrar',
    'cuentas_por_pagar',
    'partidas_diario_detalles',
    'partidas_diario',
    'bancos_transacciones',
    'ordenes_servicio',
    'cotizacion_versiones',
    'cotizacion_detalles',
    'cotizaciones'
];

echo "1. Truncando tablas transaccionales (AUTO_INCREMENT -> 1):\n";
foreach ($tablasOperativas as $tabla) {
    try {
        $db->exec("TRUNCATE TABLE `$tabla`");
        echo "   [OK] Tabla `$tabla` truncada.\n";
    } catch (Exception $e) {
        echo "   [ERROR] En `$tabla`: " . $e->getMessage() . "\n";
    }
}

// 2. Reiniciar secuencias de muestras a 0
echo "\n2. Reiniciando correlativos de secuencias_muestras a 0:\n";
$db->exec("UPDATE secuencias_muestras SET ultimo_correlativo = 0");
$stmtSeq = $db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras");
foreach ($stmtSeq->fetchAll(PDO::FETCH_ASSOC) as $seq) {
    echo "   [OK] Año {$seq['anio']} | Tipo: {$seq['tipo_muestra']} | Correlativo: {$seq['ultimo_correlativo']}\n";
}

$db->exec("SET FOREIGN_KEY_CHECKS = 1");

// 3. Limpiar PDFs temporales de solicitudes e informes
echo "\n3. Limpieza de PDFs generados en almacenamiento:\n";
$directorios = [
    __DIR__ . '/../almacenamiento/solicitudes/*.pdf',
    __DIR__ . '/../almacenamiento/informes/*.pdf'
];
$totalBorrados = 0;
foreach ($directorios as $patron) {
    foreach (glob($patron) as $archivo) {
        if (is_file($archivo)) {
            @unlink($archivo);
            $totalBorrados++;
        }
    }
}
echo "   [OK] $totalBorrados archivo(s) PDF temporal(es) eliminado(s).\n";

// 4. Verificación de integridad de catálogos maestros
echo "\n4. Verificación de Catálogos Maestros (Preservados al 100%):\n";
$catalogos = [
    'usuarios' => 'Usuarios activos',
    'roles' => 'Roles del sistema',
    'clientes' => 'Clientes registrados',
    'productos' => 'Productos / Ensayos',
    'formatos_ensayos' => 'Formatos de Ensayos oficiales',
    'plantillas' => 'Plantillas dinámicas',
    'plantilla_secciones' => 'Secciones de plantillas',
    'plantilla_campos' => 'Campos dinámicos de plantillas',
    'cuentas_contables' => 'Cuentas contables',
    'bancos_cuentas' => 'Cuentas bancarias',
    'tecnicos' => 'Técnicos de laboratorio/campo',
    'vehiculos' => 'Vehículos de muestreo',
    'variables' => 'Variables globales de cálculo'
];

foreach ($catalogos as $cat => $desc) {
    try {
        $cant = (int)$db->query("SELECT COUNT(*) FROM `$cat`")->fetchColumn();
        echo "   [PRESERVADO] $desc (`$cat`): $cant registros\n";
    } catch (Exception $e) {
        echo "   [ERROR] `$cat`: " . $e->getMessage() . "\n";
    }
}

echo "\n====================================================================\n";
echo "   BASE DE DATOS 100% LIMPIA Y LISTA PARA PRUEBAS\n";
echo "====================================================================\n";
