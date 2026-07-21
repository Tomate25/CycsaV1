<?php
namespace Database\Seeders;

use PDO;

/**
 * Seeder para las Normas ASTM utilizadas en laboratorio.
 */
class NormasAstmSeeder
{
    /**
     * Ejecuta el seeder.
     *
     * @return void
     */
    public function run()
    {
        global $pdo;

        $pdo->exec("CREATE TABLE IF NOT EXISTS normas_astm (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) NOT NULL,
            descripcion TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $normas = [
            ['codigo' => 'ASTM C39', 'descripcion' => 'Método de prueba estándar para resistencia a la compresión de especímenes cilíndricos de concreto'],
            ['codigo' => 'AASHTO T22', 'descripcion' => 'Resistencia a la compresión de especímenes cilíndricos de concreto (Equivalente)'],
            ['codigo' => 'ASTM D422', 'descripcion' => 'Método de prueba estándar para el análisis de tamaño de partícula de suelos'],
            ['codigo' => 'ASTM C140', 'descripcion' => 'Métodos de prueba estándar para muestreo y prueba de unidades de mampostería de concreto']
        ];

        $stmt = $pdo->prepare("INSERT INTO normas_astm (codigo, descripcion) VALUES (:codigo, :descripcion)");

        foreach ($normas as $norma) {
            $stmt->execute([
                ':codigo' => $norma['codigo'],
                ':descripcion' => $norma['descripcion']
            ]);
        }
    }
}
