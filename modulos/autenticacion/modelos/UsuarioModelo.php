<?php

namespace Cycsa\Modulos\Autenticacion\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class UsuarioModelo extends ModeloBase {
    
    public function buscarPorEmail(string $email) {
        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        // Retorna un arreglo asociativo con los datos del usuario, o 'false' si no existe
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}