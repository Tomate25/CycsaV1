<?php
namespace Database\Seeders;

use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Seeder para la configuración general de la empresa.
 */
class ConfiguracionSeeder
{
    public function run()
    {
        $pdo = Conexion::obtenerInstancia();

        $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            clave VARCHAR(100) NOT NULL UNIQUE,
            valor TEXT,
            descripcion TEXT
        )");

        $configuraciones = [
            ['clave' => 'empresa_nombre', 'valor' => 'CYCSA ERP & LIMS', 'descripcion' => 'Nombre oficial de la empresa'],
            ['clave' => 'iva_porcentaje', 'valor' => '15', 'descripcion' => 'Porcentaje de IVA por defecto (Nicaragua)'],
            ['clave' => 'moneda_defecto', 'valor' => 'NIO', 'descripcion' => 'Moneda base del sistema (Córdobas C$)'],
            ['clave' => 'logo_ruta', 'valor' => '/Cycsa/publico/img/logo.png', 'descripcion' => 'Ruta del logo institucional'],
            ['clave' => 'ruta_pdf', 'valor' => '/storage/pdf/', 'descripcion' => 'Ruta de guardado para PDFs generados']
        ];

        $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (:clave, :valor, :descripcion) ON DUPLICATE KEY UPDATE valor = VALUES(valor), descripcion = VALUES(descripcion)");

        foreach ($configuraciones as $config) {
            $stmt->execute([
                ':clave' => $config['clave'],
                ':valor' => $config['valor'],
                ':descripcion' => $config['descripcion']
            ]);
        }
    }
}
