<?php

namespace Cycsa\Nucleo;

use PDO;

abstract class ModeloBase {
    protected PDO $db;

    public function __construct() {
        // Obtenemos la conexión segura que creamos al principio
        $this->db = Conexion::obtenerInstancia();
    }
}