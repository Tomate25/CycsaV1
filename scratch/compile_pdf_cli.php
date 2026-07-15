<?php
// scratch/compile_pdf_cli.php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \Cycsa\Nucleo\Aplicacion();

$idDetalle = (int)($argv[1] ?? 0);
$idLote = (int)($argv[2] ?? 0);
$codigoReporte = $argv[3] ?? 'INF-2026-TEST';
$version = (int)($argv[4] ?? 0);

try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    
    // Fetch detail
    $stmtDet = $db->prepare("SELECT cd.*, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre
                             FROM cotizacion_detalles cd
                             LEFT JOIN productos p ON cd.id_producto = p.id
                             LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                             WHERE cd.id = :id");
    $stmtDet->execute(['id' => $idDetalle]);
    $detalle = $stmtDet->fetch(PDO::FETCH_ASSOC);
    if (!$detalle) throw new Exception("Detalle $idDetalle no encontrado.");

    // Fetch quote
    $stmtCot = $db->prepare("SELECT c.*, cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc, cli.telefono AS cliente_telefono
                             FROM cotizaciones c
                             JOIN clientes cli ON c.id_cliente = cli.id
                             WHERE c.id = :id");
    $stmtCot->execute(['id' => $detalle['id_cotizacion']]);
    $cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

    // Columns & Filas
    $archivoMd = $detalle['archivo_markdown'] ?? '';
    
    // Check if it's Concrete breakages
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM ensayo_edades WHERE id_lote = :id_lote AND identificador_especimen != 'Muestra' AND edad_dias > 0");
    $stmtCount->execute(['id_lote' => $idLote]);
    $esEnsayoEdades = ((int)$stmtCount->fetchColumn() > 0);

    if ($esEnsayoEdades) {
        $columnas = [
            "Cilindro", "Edad Evaluada", "Fecha Programada", "Fecha de Ensayo",
            "Carga Última (Lbs)", "Área Transversal (in²)", "Esfuerzo PSI",
            "Esfuerzo Kg/cm²", "% Diseño", "Estado / Alerta"
        ];
        
        // Fetch specs
        $stmtLote = $db->prepare("SELECT lm.*, rm.codigo_muestra, rm.codigo_campo 
                                  FROM lotes_muestras lm
                                  JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                                  WHERE lm.id = :id_lote");
        $stmtLote->execute(['id_lote' => $idLote]);
        $loteData = $stmtLote->fetch(PDO::FETCH_ASSOC);

        $stmtEsp = $db->prepare("SELECT * FROM ensayo_edades WHERE id_lote = :id_lote ORDER BY edad_dias ASC, identificador_especimen ASC");
        $stmtEsp->execute(['id_lote' => $idLote]);
        $especimenesList = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);

        $filas = [];
        foreach ($especimenesList as $esp) {
            $fila = [];
            foreach ($columnas as $col) {
                $colLower = mb_strtolower(trim($col));
                $val = '';
                if (strpos($colLower, 'cilindro') !== false) {
                    $val = $esp['identificador_especimen'];
                } else if (strpos($colLower, 'edad') !== false) {
                    $val = $esp['edad_dias'] . 'd';
                } else if (strpos($colLower, 'programada') !== false) {
                    $val = $esp['fecha_programada'] ? date('d/m/Y', strtotime($esp['fecha_programada'])) : '';
                } else if (strpos($colLower, 'ensayo') !== false || strpos($colLower, 'ensaye') !== false) {
                    $val = $esp['fecha_ensaye_real'] ? date('d/m/Y', strtotime($esp['fecha_ensaye_real'])) : '';
                } else if (strpos($colLower, 'carga') !== false) {
                    $val = $esp['carga_lbs'] !== null ? number_format($esp['carga_lbs'], 1, '.', '') : '';
                } else if (strpos($colLower, 'rea') !== false) {
                    $val = $esp['area_in2'] !== null ? number_format($esp['area_in2'], 3, '.', '') : '';
                } else if (strpos($colLower, 'psi') !== false) {
                    $val = $esp['resistencia_psi'] !== null ? number_format($esp['resistencia_psi'], 0, '.', '') : '';
                } else if (strpos($colLower, 'kg') !== false) {
                    $val = $esp['resistencia_kgcm2'] !== null ? number_format($esp['resistencia_kgcm2'], 1, '.', '') : '';
                } else if (strpos($colLower, 'dise') !== false) {
                    $val = $esp['porcentaje_diseno'] !== null ? number_format($esp['porcentaje_diseno'], 1, '.', '') . '%' : '';
                } else if (strpos($colLower, 'estado') !== false || strpos($colLower, 'alerta') !== false) {
                    $val = $esp['cumple_norma'] ? 'Cumple' : 'No cumple';
                }
                $fila[$col] = $val;
            }
            $filas[] = $fila;
        }
    } else {
        if ($archivoMd === 'formato_de_granulometria_de_suelo.md' || $archivoMd === 'granulomnetria_de_agregados.md') {
            $columnas = ["Malla", "P. Retenido parcial (gr)", "% Retenido parcial", "% Acumulativo", "% que pasa la malla", "Límite Mín", "Límite Máx"];
        } else {
            $columnas = ["Código laboratorio", "Nombre muestra", "Resultado"];
        }
        $filas = json_decode($detalle['resultados_json'], true) ?: [];
    }

    require_once __DIR__ . '/../ayudantes/funciones.php';
    $pdfContenido = generarReporteEnsayoPDF($cotizacion, $detalle, $columnas, $filas, $codigoReporte, $version);

    // Save to Downloads
    $destPath = "C:/Users/abdia/Downloads/" . $codigoReporte . ".pdf";
    file_put_contents($destPath, $pdfContenido);
    echo "✓ PDF generated successfully: $destPath\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
