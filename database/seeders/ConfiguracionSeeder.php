<?php
namespace Database\Seeders;

use PDO;

/**
 * Seeder para la configuración general de la empresa.
 */
class ConfiguracionSeeder
{
    /**
     * Ejecuta el seeder.
     *
     * @return void
     */
    public function run()
    {
        global $pdo;

        $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            clave VARCHAR(100) NOT NULL UNIQUE,
            valor TEXT,
            descripcion TEXT
        )");

        $configuraciones = [
            ['clave' => 'empresa_nombre', 'valor' => 'CYCSA ERP & LIMS', 'descripcion' => 'Nombre de la empresa'],
            ['clave' => 'iva_porcentaje', 'valor' => '16', 'descripcion' => 'Porcentaje de IVA por defecto'],
            ['clave' => 'moneda_defecto', 'valor' => 'MXN', 'descripcion' => 'Moneda base del sistema'],
            ['clave' => 'logo_ruta', 'valor' => '/assets/img/logo.png', 'descripcion' => 'Ruta del logo de la empresa'],
            ['clave' => 'ruta_pdf', 'valor' => '/storage/pdfs/', 'descripcion' => 'Ruta de guardado para PDFs generados']
        ];

        $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (:clave, :valor, :descripcion)");

        foreach ($configuraciones as $config) {
            $stmt->execute([
                ':clave' => $config['clave'],
                ':valor' => $config['valor'],
                ':descripcion' => $config['descripcion']
            ]);
        }
    }
}
