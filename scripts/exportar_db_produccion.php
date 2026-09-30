<?php
/**
 * Script de Exportación Oficial de Base de Datos para Producción (CYCSA ERP / LIMS)
 *
 * Genera un volcado SQL 100% limpio y optimizado para importar directamente
 * en phpMyAdmin de Bluehost (cycsanic_cycsa_db).
 *
 * Características:
 * - Sin CREATE DATABASE ni USE cycsa_db (evita errores de permisos en cPanel).
 * - Sin DEFINER (evita errores de privilegios de usuario en MariaDB).
 * - UTF-8mb4 universal sin pérdida de caracteres.
 * - Incluye catálogos maestros completos (112 productos con precios, 456 clientes,
 *   21 formatos de ensayos con esquemas JSON, 361 cuentas contables, 19 usuarios, roles, etc.).
 * - Tablas transaccionales limpias con AUTO_INCREMENT en 1 (cotizaciones, órdenes, hojas de servicio, etc.).
 */

$host = '127.0.0.1';
$dbname = 'cycsa_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage() . "\n");
}

// Tablas de catálogo / configuración (se exportan estructura + datos completos)
$tablasCatalogos = [
    'roles',
    'usuarios',
    'bancos_cuentas',
    'clientes',
    'configuracion_comercial',
    'cuentas_contables',
    'formatos_ensayos',
    'productos',
    'tecnicos',
    'vehiculos',
    'secuencias_muestras'
];

// Tablas operativas / transaccionales (se exporta solo la estructura limpia, 0 datos)
$tablasOperativas = [
    'cotizaciones',
    'cotizacion_detalles',
    'cotizacion_versiones',
    'ordenes_servicio',
    'hojas_solicitud',
    'programacion_muestreo',
    'recepcion_muestras',
    'lotes_muestras',
    'informes_control',
    'cuentas_por_cobrar',
    'cuentas_por_pagar',
    'partidas_diario',
    'partidas_diario_detalles',
    'bancos_transacciones',
    'bitacora',
    'ensayo_edades',
    'ensayos_parametros'
];

// Orden canónico para respetar integridad referencial
$todasLasTablas = [
    'roles',
    'usuarios',
    'cuentas_contables',
    'bancos_cuentas',
    'clientes',
    'formatos_ensayos',
    'productos',
    'tecnicos',
    'vehiculos',
    'secuencias_muestras',
    'configuracion_comercial',
    'cotizaciones',
    'cotizacion_detalles',
    'cotizacion_versiones',
    'ordenes_servicio',
    'hojas_solicitud',
    'programacion_muestreo',
    'recepcion_muestras',
    'lotes_muestras',
    'informes_control',
    'ensayo_edades',
    'ensayos_parametros',
    'cuentas_por_cobrar',
    'cuentas_por_pagar',
    'partidas_diario',
    'partidas_diario_detalles',
    'bancos_transacciones',
    'bitacora'
];

$fechaGeneracion = date('Y-m-d H:i:s');
$header = <<<SQL
-- ==============================================================================
-- BASE DE DATOS OFICIAL CYCSA ERP & LIMS (PRODUCCIÓN LIMPIA)
-- Generado automáticamente: {$fechaGeneracion}
-- Base de datos destino: cycsanic_cycsa_db (Bluehost cPanel / phpMyAdmin)
-- Total de tablas: 28
--
-- CARACTERÍSTICAS DEL SCRIPT:
-- 1. Sin directivas CREATE DATABASE ni USE cycsa_db (importación directa infalible).
-- 2. Sin cláusulas DEFINER (compatibilidad total con privilegios cPanel).
-- 3. Catálogos maestros completos preservados:
--    - 112 Productos oficiales (precios, normas ASTM, procedimientos de muestreo).
--    - 456 Clientes registrados.
--    - 21 Formatos de Ensayos (esquemas JSON dinámicos y plantillas markdown).
--    - 361 Cuentas Contables y 8 Cuentas Bancarias.
--    - 19 Usuarios oficiales de CYCSA y 7 Roles del sistema.
--    - 8 Técnicos y 5 Vehículos.
-- 4. Tablas transaccionales limpias con correlativo reiniciado a 1.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

SQL;

$outSql = $header . "\n";

foreach ($todasLasTablas as $tabla) {
    echo "Procesando tabla: $tabla...";
    $outSql .= "\n-- --------------------------------------------------------\n";
    $outSql .= "-- Estructura de tabla para `$tabla`\n";
    $outSql .= "-- --------------------------------------------------------\n\n";
    $outSql .= "DROP TABLE IF EXISTS `$tabla`;\n";

    $createStmt = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch();
    $createTableSql = $createStmt['Create Table'] ?? '';

    // Si es tabla operativa, resetear AUTO_INCREMENT a 1
    if (in_array($tabla, $tablasOperativas)) {
        $createTableSql = preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=1', $createTableSql);
    }

    $outSql .= $createTableSql . ";\n\n";

    // Si es catálogo, exportar sus datos
    if (in_array($tabla, $tablasCatalogos)) {
        $rows = $pdo->query("SELECT * FROM `$tabla`")->fetchAll();
        $totalRows = count($rows);
        echo " ($totalRows registros exportados)\n";

        if ($totalRows > 0) {
            $outSql .= "--\n-- Volcado de datos para la tabla `$tabla` ($totalRows registros)\n--\n\n";
            $outSql .= "LOCK TABLES `$tabla` WRITE;\n";
            $outSql .= "/*!40000 ALTER TABLE `$tabla` DISABLE KEYS */;\n";

            // Obtener columnas
            $cols = array_keys($rows[0]);
            $colList = implode('`, `', $cols);

            // Generar inserts por lotes de 50 filas
            $batchSize = 50;
            $chunks = array_chunk($rows, $batchSize);

            foreach ($chunks as $chunk) {
                $valuesArr = [];
                foreach ($chunk as $row) {
                    $rowVals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $rowVals[] = 'NULL';
                        } elseif (is_numeric($val) && !preg_match('/^0[0-9]+/', $val)) {
                            $rowVals[] = $val;
                        } else {
                            $rowVals[] = $pdo->quote($val);
                        }
                    }
                    $valuesArr[] = "(" . implode(', ', $rowVals) . ")";
                }
                $outSql .= "INSERT INTO `$tabla` (`$colList`) VALUES\n" . implode(",\n", $valuesArr) . ";\n";
            }

            $outSql .= "/*!40000 ALTER TABLE `$tabla` ENABLE KEYS */;\n";
            $outSql .= "UNLOCK TABLES;\n\n";
        }
    } else {
        echo " (Estructura limpia para producción)\n";
    }
}

$footer = <<<SQL
-- ==============================================================================
-- FINALIZACIÓN Y CIERRE DE TRANSACCIÓN
-- ==============================================================================

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Fin del volcado de producción limpia CYCSA ERP / LIMS
SQL;

$outSql .= $footer . "\n";

// Guardar archivos
$archivoLimpia = __DIR__ . '/../database/cycsa_db_produccion_limpia.sql';
$archivoParaProd = __DIR__ . '/../database/cycsa_db_para_produccion.sql';

file_put_contents($archivoLimpia, $outSql);
file_put_contents($archivoParaProd, $outSql);

echo "\n========================================================\n";
echo "EXPORTACIÓN EXITOSA:\n";
echo "1. " . realpath($archivoLimpia) . " (" . number_format(filesize($archivoLimpia) / 1024, 2) . " KB)\n";
echo "2. " . realpath($archivoParaProd) . " (" . number_format(filesize($archivoParaProd) / 1024, 2) . " KB)\n";
echo "========================================================\n";
