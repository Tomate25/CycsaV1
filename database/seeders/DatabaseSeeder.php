<?php
namespace Database\Seeders;

/**
 * Clase principal para la ejecución de Seeders de la base de datos.
 */
class DatabaseSeeder
{
    /**
     * Ejecuta los seeders.
     *
     * @return void
     */
    public function run()
    {
        $rolesSeeder = new RolesSeeder();
        $rolesSeeder->run();

        $permisosSeeder = new PermisosSeeder();
        $permisosSeeder->run();

        $normasSeeder = new NormasAstmSeeder();
        $normasSeeder->run();

        $configSeeder = new ConfiguracionSeeder();
        $configSeeder->run();
    }
}
