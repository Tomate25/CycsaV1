<?php
/**
 * ============================================================================
 * CYCSA ERP / LIMS - Script Seguro de Limpieza para Pase a Producción
 * ============================================================================
 * 
 * Este script purga la data transaccional y de pruebas acumulada durante el
 * ciclo de desarrollo y auditoría QA, preservando de forma 100% íntegra:
 *   - Catálogo maestro de Clientes
 *   - Catálogo maestro de Ensayos / Productos
 *   - Catálogo de Cuentas Contables y configuraciones
 *   - Plantillas oficiales ISO 17025 y parámetros de cálculo
 *   - Vehículos, Técnicos, Unidades de Medida y Departamentos
 *   - Cuentas de Usuarios y Roles de Seguridad
 * 
 * MODOS DE USO:
 *   1. MODO SIMULACIÓN (DRY-RUN - No modifica nada):
 *      php scripts/limpiar_datos_produccion.php
 * 
 *   2. MODO EJECUCIÓN REAL (Aplica el vaciado seguro):
 *      php scripts/limpiar_datos_produccion.php --ejecutar
 * 
 *   3. MODO EJECUCIÓN + PERMISOS TOTALES EN ROLES:
 *      php scripts/limpiar_datos_produccion.php --ejecutar --roles-completos
 * 
 *   4. OPCIONAL: Limpiar también la bitácora de auditoría histórica:
 *      php scripts/limpiar_datos_produccion.php --ejecutar --limpiar-bitacora
 * ============================================================================
 */

require_once __DIR__ . '/../app/Core/Conexion.php';

$esEjecucion = in_array('--ejecutar', $argv, true);
$rolesCompletos = in_array('--roles-completos', $argv, true);
$limpiarBitacora = in_array('--limpiar-bitacora', $argv, true);

echo PHP_EOL;
echo "=====================================================================" . PHP_EOL;
echo "   CYCSA ERP / LIMS - PURGA Y PREPARACIÓN PARA PRODUCCIÓN            " . PHP_EOL;
echo "=====================================================================" . PHP_EOL;

if (!$esEjecucion) {
    echo " [AVISO] MODO SIMULACIÓN (DRY-RUN) ACTIVO." . PHP_EOL;
    echo "         No se modificará ningún registro en la base de datos." . PHP_EOL;
    echo "         Para ejecutar los cambios reales, ejecuta con: --ejecutar" . PHP_EOL . PHP_EOL;
} else {
    echo " [ALERTA] MODO EJECUCIÓN REAL ACTIVO." . PHP_EOL;
    echo "          Se procederá al vaciado de tablas transaccionales." . PHP_EOL . PHP_EOL;
}

$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

// Tablas transaccionales a purgar (con orden seguro)
$tablasTransaccionales = [
    // 1. Rupturas y ensayos de laboratorio
    'roturas',
    'plan_rotura_asignaciones',
    'plan_rotura_edades',
    'planes_rotura',
    'informes_rotura',
    'informes_control',
    'documentos_reemisiones',
    'especimenes',
    'hojas_campo_registros',
    'hojas_solicitud_detalles',
    'hojas_solicitud',
    'programacion_muestreo',
    'secuencias_muestras',
    'lotes_muestras',
    'recepcion_muestras',
    
    // 2. Órdenes de Servicio y Cotizaciones
    'orden_servicio_ensayos_permitidos',
    'ordenes_servicio',
    'cotizacion_versiones',
    'cotizacion_detalles',
    'cotizaciones',

    // 3. Ventas, Facturación y Cartera
    'cuentas_por_cobrar',

    // 4. Compras y Cuentas por Pagar
    'recepciones_compra_detalle',
    'recepciones_compra',
    'ordenes_compra_detalle',
    'ordenes_compra',
    'cuentas_por_pagar_detalle',
    'cuentas_por_pagar',

    // 5. Inventario Kárdex y Movimientos
    'inventario_movimientos',
    'inventario_lotes',
    'inventario_insumos',
    'equipos_traslados',

    // 6. Contabilidad (Pólizas y Movimientos Diarios)
    'partidas_diario_detalles',
    'partidas_diario',
    'bancos_transacciones',

    // 7. Préstamos, Activos Fijos y Proyectos
    'prestamos_cuotas',
    'prestamos_bancarios',
    'activos_fijos_depreciaciones',
    'activos_fijos',
    'proyectos',

    // 8. Nómina & RRHH
    'rrhh_nomina_detalles',
    'rrhh_nominas',
    'rrhh_anticipos',
    'rrhh_empleados'
];

if ($limpiarBitacora) {
    $tablasTransaccionales[] = 'bitacora_reidentificacion';
    $tablasTransaccionales[] = 'bitacora';
}

// Tablas protegidas estrictamente
$tablasProtegidas = [
    'usuarios',
    'roles',
    'clientes',
    'productos',
    'plantillas',
    'plantilla_versiones',
    'plantilla_secciones',
    'plantilla_campos',
    'formatos_ensayos',
    'cuentas_contables',
    'config_cuentas',
    'configuracion_comercial',
    'configuracion_correcciones_hc',
    'unidades_medida',
    'tecnicos',
    'vehiculos',
    'equipos_bodegas',
    'equipos_calibracion',
    'equipos_calibracion_constantes',
    'equipos_calibracion_picnometros',
    'equipos_ubicacion',
    'bancos_cuentas',
    'rrhh_departamentos',
    'variables'
];

echo "--- TABLAS TRANSACCIONALES A LIMPIAR ---" . PHP_EOL;
$totalRegistrosPurgados = 0;

foreach ($tablasTransaccionales as $tabla) {
    // Verificar si la tabla existe en la BD
    $existe = $db->query("SHOW TABLES LIKE '{$tabla}'")->rowCount() > 0;
    if (!$existe) {
        continue;
    }

    $conteo = (int)$db->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
    $totalRegistrosPurgados += $conteo;

    printf("  %-38s : %5d registros", $tabla, $conteo);

    if ($esEjecucion && $conteo > 0) {
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->query("TRUNCATE TABLE `{$tabla}`");
        $db->query("ALTER TABLE `{$tabla}` AUTO_INCREMENT = 1");
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
        echo " -> PURGADO [OK]" . PHP_EOL;
    } else {
        echo " -> " . ($esEjecucion ? "YA ESTÁ VACÍA" : "PENDIENTE") . PHP_EOL;
    }
}

echo PHP_EOL . "Total de registros transaccionales procesados: {$totalRegistrosPurgados}" . PHP_EOL . PHP_EOL;

// Verificación de integridad de catálogos protegidos
echo "--- CATÁLOGOS MAESTROS PRESERVADOS (INTEGRIDAD) ---" . PHP_EOL;
foreach ($tablasProtegidas as $tabla) {
    $existe = $db->query("SHOW TABLES LIKE '{$tabla}'")->rowCount() > 0;
    if ($existe) {
        $conteo = (int)$db->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
        printf("  %-38s : %5d registros PRESERVADOS" . PHP_EOL, $tabla, $conteo);
    }
}

// Configuración opcional o requerida de permisos completos
if ($rolesCompletos) {
    echo PHP_EOL . "--- ACTUALIZACIÓN DE PERMISOS COMPLETOS EN ROLES ---" . PHP_EOL;
    $permisosTodos = [
        'clientes'         => ['ver' => 1, 'crear_editar' => 1],
        'productos'        => ['ver' => 1, 'crear_editar' => 1],
        'cotizaciones'     => ['ver' => 1, 'crear_editar' => 1, 'aprobar' => 1],
        'ordenes_servicio' => ['ver' => 1, 'crear_editar' => 1, 'aprobar' => 1],
        'ventas'           => ['ver' => 1, 'crear_editar' => 1],
        'operaciones'      => ['ver' => 1, 'crear_editar' => 1],
        'control_calidad'  => ['ver' => 1, 'crear_editar' => 1],
        'laboratorio'      => ['ver' => 1, 'crear_editar' => 1],
        'equipos_bodegas'  => ['ver' => 1, 'crear_editar' => 1],
        'plantillas'       => ['ver' => 1, 'crear_editar' => 1],
        'inventario'       => ['ver' => 1, 'crear_editar' => 1],
        'compras'          => ['ver' => 1, 'crear_editar' => 1],
        'contabilidad'     => ['ver' => 1, 'crear_editar' => 1],
        'nomina'           => ['ver' => 1, 'crear_editar' => 1],
        'prestamos'        => ['ver' => 1, 'crear_editar' => 1],
        'activos_fijos'    => ['ver' => 1, 'crear_editar' => 1],
        'proyectos'        => ['ver' => 1, 'crear_editar' => 1],
    ];
    $jsonTodos = json_encode($permisosTodos, JSON_UNESCAPED_UNICODE);

    if ($esEjecucion) {
        // Asignar permisos completos a roles no administradores (ID != 1)
        $stmt = $db->prepare("UPDATE roles SET permisos = :permisos WHERE id != 1");
        $stmt->execute(['permisos' => $jsonTodos]);
        echo "  [OK] Permisos completos asignados a todos los roles operativos." . PHP_EOL;

        // Limpiar overrides antiguos en usuarios no admin para que hereden 100% del rol
        $db->query("UPDATE usuarios SET permisos = NULL WHERE id_rol != 1");
        echo "  [OK] Overrides de permisos en usuarios eliminados (herencia limpia del rol activa)." . PHP_EOL;
    } else {
        echo "  [DRY-RUN] Se asignarían permisos completos a todos los roles y se limpiaría la herencia en usuarios." . PHP_EOL;
    }
}

echo PHP_EOL;
if ($esEjecucion) {
    echo "=====================================================================" . PHP_EOL;
    echo "   ¡LIMPIEZA DE PRODUCCIÓN COMPLETADA EXITOSAMENTE!                  " . PHP_EOL;
    echo "=====================================================================" . PHP_EOL;
} else {
    echo "=====================================================================" . PHP_EOL;
    echo "   SIMULACIÓN FINALIZADA SIN ALTERACIONES.                           " . PHP_EOL;
    echo "   Para ejecutar realmente, ejecuta:                                " . PHP_EOL;
    echo "   php scripts/limpiar_datos_produccion.php --ejecutar               " . PHP_EOL;
    echo "=====================================================================" . PHP_EOL;
}
