<?php

namespace Cycsa\Modulos\Autenticacion\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class UsuarioModelo extends ModeloBase {
    
    public function buscarPorEmail(string $email) {
        $sql = "SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = LOWER(TRIM(:email)) LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => trim($email)]);
        
        // Retorna un arreglo asociativo con los datos del usuario, o 'false' si no existe
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registrarIntentoFallido(int $id, int $actuales) {
        $nuevos = $actuales + 1;
        $bloquear = $nuevos >= 5 ? 1 : 0;
        
        $sql = "UPDATE usuarios SET intentos_fallidos = :intentos, bloqueado = :bloqueado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'intentos' => $nuevos,
            'bloqueado' => $bloquear,
            'id' => $id
        ]);
        
        return [
            'intentos_fallidos' => $nuevos,
            'bloqueado' => $bloquear
        ];
    }
    
    public function restablecerIntentos(int $id) {
        $sql = "UPDATE usuarios SET intentos_fallidos = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
    }
}