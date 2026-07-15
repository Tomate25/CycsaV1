<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Cycsa\Nucleo\Aplicacion();
try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

    // 1. Fetch details
    $stmtDet = $db->prepare("SELECT cd.*, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre
                             FROM cotizacion_detalles cd
                             LEFT JOIN productos p ON cd.id_producto = p.id
                             LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                             WHERE cd.id = 1");
    $stmtDet->execute();
    $detalle = $stmtDet->fetch(PDO::FETCH_ASSOC);

    if (!$detalle) {
        throw new Exception("Detalle de cotización 1 no encontrado.");
    }

    // 2. Fetch quote
    $stmtCot = $db->prepare("SELECT c.*, cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc, cli.telefono AS cliente_telefono
                             FROM cotizaciones c
                             JOIN clientes cli ON c.id_cliente = cli.id
                             WHERE c.id = :id");
    $stmtCot->execute(['id' => $detalle['id_cotizacion']]);
    $cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

    // 3. Columns
    $columnas = ["Malla", "P. Retenido parcial (gr)", "% Retenido parcial", "% Acumulativo", "% que pasa la malla", "Límite Mín", "Límite Máx"];
    
    // 4. Filas
    $filas = json_decode($detalle['resultados_json'], true) ?: [];

    // 5. Generate PDF
    require_once __DIR__ . '/../ayudantes/funciones.php';
    $pdfContenido = generarReporteEnsayoPDF($cotizacion, $detalle, $columnas, $filas, "INF-2026-0001-00", 0);

    // 6. Save to Downloads
    $destPath = "C:/Users/abdia/Downloads/Formato de Granulometria de Suelo.pdf";
    file_put_contents($destPath, $pdfContenido);

    echo "✓ PDF de Granulometría de Suelo generado y guardado en: $destPath\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
