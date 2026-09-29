<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

// 1. Corregir Hoja #60
$muestrasH60 = [
    [
        'nombre_muestra' => 'MC-0001-26',
        'descripcion' => 'PK 0+020 Carril Der - Sub-base granular',
        'info_importante' => 'Capa 1 Espesor 20cm'
    ],
    [
        'nombre_muestra' => 'MC-0002-26',
        'descripcion' => 'PK 0+040 Carril Der - Sub-base granular',
        'info_importante' => 'Capa 1 Espesor 20cm'
    ],
    [
        'nombre_muestra' => 'MC-0003-26',
        'descripcion' => 'PK 0+060 Eje Central - Sub-base granular',
        'info_importante' => 'Capa 1 Espesor 20cm'
    ],
    [
        'nombre_muestra' => 'MC-0004-26',
        'descripcion' => 'PK 0+080 Carril Izq - Sub-base granular',
        'info_importante' => 'Capa 1 Espesor 20cm'
    ],
    [
        'nombre_muestra' => 'MC-0005-26',
        'descripcion' => 'PK 0+100 Carril Izq - Sub-base granular',
        'info_importante' => 'Capa 1 Espesor 20cm'
    ]
];

$stmtH60 = $db->prepare("UPDATE hojas_solicitud SET muestras_json = :m WHERE id = 60");
$stmtH60->execute(['m' => json_encode($muestrasH60)]);
echo "Hoja #60 actualizada con códigos MC-0001-26 a MC-0005-26.\n";

// 2. Corregir Detalle #30 (Cotización 9 / OS 6)
$stmtD30 = $db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = 30");
$stmtD30->execute();
$res30 = json_decode($stmtD30->fetchColumn(), true);

if (isset($res30['filas'])) {
    $consecutivos = ['MC-0001-26', 'MC-0002-26', 'MC-0003-26', 'MC-0004-26', 'MC-0005-26'];
    foreach ($res30['filas'] as $i => &$fila) {
        $fila['Código laboratorio'] = $consecutivos[$i] ?? "MC-000" . ($i+1) . "-26";
        $fila['Nombre muestra'] = "Estación " . ($i + 1) . " - Sub-base";
    }
    unset($fila);

    $stmtUpd30 = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :res WHERE id = 30");
    $stmtUpd30->execute(['res' => json_encode($res30)]);
    echo "Detalle #30 actualizado con códigos MC-0001-26 a MC-0005-26.\n";
}

// 3. Limpiar cualquier texto 'Punto:' en Hoja #64
$stmtH64 = $db->prepare("SELECT procedencia_punto_muestreo, muestras_json FROM hojas_solicitud WHERE id = 64");
$stmtH64->execute();
$row64 = $stmtH64->fetch(PDO::FETCH_ASSOC);
if ($row64) {
    $proc64 = str_ireplace('puntos', 'estaciones', $row64['procedencia_punto_muestreo']);
    $m64 = json_decode($row64['muestras_json'], true) ?: [];
    foreach ($m64 as &$item) {
        $item['info_importante'] = str_ireplace(['Punto:', 'puntos'], ['Ubicación:', 'estaciones'], $item['info_importante'] ?? '');
    }
    unset($item);
    $stmtUpdH64 = $db->prepare("UPDATE hojas_solicitud SET procedencia_punto_muestreo = :proc, muestras_json = :m WHERE id = 64");
    $stmtUpdH64->execute(['proc' => $proc64, 'm' => json_encode($m64)]);
    echo "Hoja #64 limpiada de referencias a 'Punto'.\n";
}
