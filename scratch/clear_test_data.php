<?php
require_once 'C:/xampp/htdocs/Cycsa/nucleo/Conexion.php';
use Cycsa\Nucleo\Conexion;

function loadEnvFile($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $clave = trim($parts[0]);
                $valor = trim($parts[1]);
                $len = strlen($valor);
                if ($len >= 2 && (
                    ($valor[0] === '"' && $valor[$len - 1] === '"') ||
                    ($valor[0] === "'" && $valor[$len - 1] === "'")
                )) {
                    $valor = substr($valor, 1, -1);
                }
                $_ENV[$clave] = $valor;
            }
        }
    }
}

loadEnvFile('C:/xampp/htdocs/Cycsa/.env');
loadEnvFile('C:/xampp/htdocs/Cycsa/.env.local');

try {
    $pdo = Conexion::obtenerInstancia();
    echo "Conexión a la base de datos de Cycsa establecida con éxito.\n\n";

    // 1. Obtener todas las tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $dbTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Definir las tablas que queremos limpiar
    // Excluyendo productos, usuarios, roles, permisos y catálogo de cuentas contables básico
    $tablasALimpiar = [
        'partidas_diario_detalles',
        'partidas_diario',
        'cuentas_por_cobrar',
        'cuentas_por_pagar',
        'bancos_transacciones',
        'informes_control',
        'hojas_campo',
        'hojas_solicitud',
        'ensayo_edades',
        'resultados_ensayos',
        'ensayos_concreto',
        'ensayos_suelo',
        'lotes_muestras',
        'recepcion_muestras',
        'ordenes_servicio',
        'cotizacion_detalles',
        'cotizaciones',
        'clientes',
        'bitacora'
    ];

    echo "Iniciando limpieza de datos de prueba contables y operativos...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    foreach ($tablasALimpiar as $tabla) {
        if (in_array($tabla, $dbTables)) {
            $pdo->exec("TRUNCATE TABLE `$tabla`");
            echo "✓ Tabla `$tabla` vaciada con éxito (TRUNCATE).\n";
        } else {
            echo "⚠ La tabla `$tabla` no existe en la base de datos (se omite).\n";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n¡Éxito! Base de datos de contabilidad y operaciones reiniciada para pruebas limpias. Se conservó el catálogo de cuentas contables, productos y usuarios.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
