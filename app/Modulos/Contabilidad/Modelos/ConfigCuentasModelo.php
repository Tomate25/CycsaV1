<?php

namespace Cycsa\Modulos\Contabilidad\Modelos;

use Cycsa\Nucleo\ModeloBase;
use Cycsa\Nucleo\Conexion;
use PDO;
use Throwable;

class ConfigCuentasModelo extends ModeloBase {

    protected ?string $tablaCuentasDetectada = null;

    public function __construct(?PDO $db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    /**
     * Detecta dinámicamente el nombre de la tabla de catálogo contable ('cuentas_contables' o 'cuentas').
     */
    protected function obtenerTablaCuentas(): ?string {
        if ($this->tablaCuentasDetectada !== null) {
            return $this->tablaCuentasDetectada;
        }

        foreach (['cuentas_contables', 'cuentas'] as $tabla) {
            try {
                $check = $this->db->query("SHOW TABLES LIKE '{$tabla}'")->fetchAll();
                if (!empty($check)) {
                    $this->tablaCuentasDetectada = $tabla;
                    return $this->tablaCuentasDetectada;
                }
            } catch (Throwable $e) {
                // Fallback en entornos que no admitan SHOW TABLES
                try {
                    $this->db->query("SELECT 1 FROM `{$tabla}` LIMIT 1");
                    $this->tablaCuentasDetectada = $tabla;
                    return $this->tablaCuentasDetectada;
                } catch (Throwable $e2) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Obtiene la configuración completa de una cuenta por su clave mnemónica.
     * Retorna array asociativo con la configuración y datos contables enriquecidos, o null si no existe.
     */
    public function obtenerCuentaPorClave(string $clave): ?array {
        $clave = trim($clave);
        if ($clave === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM `config_cuentas` WHERE `clave` = :clave LIMIT 1");
        $stmt->execute(['clave' => $clave]);
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cfg) {
            return null;
        }

        $tablaCuentas = $this->obtenerTablaCuentas();
        if ($tablaCuentas !== null) {
            $cuenta = null;

            if (!empty($cfg['id_cuenta'])) {
                $stmtC = $this->db->prepare("SELECT * FROM `{$tablaCuentas}` WHERE `id` = :id LIMIT 1");
                $stmtC->execute(['id' => $cfg['id_cuenta']]);
                $cuenta = $stmtC->fetch(PDO::FETCH_ASSOC);
            }

            // Si id_cuenta estaba nulo o no se encontró, resolver por código de cuenta
            if (!$cuenta && !empty($cfg['codigo_cuenta'])) {
                $stmtC = $this->db->prepare("SELECT * FROM `{$tablaCuentas}` WHERE `codigo` = :codigo LIMIT 1");
                $stmtC->execute(['codigo' => $cfg['codigo_cuenta']]);
                $cuenta = $stmtC->fetch(PDO::FETCH_ASSOC);

                if ($cuenta && empty($cfg['id_cuenta'])) {
                    $cfg['id_cuenta'] = (int)$cuenta['id'];
                    try {
                        $upd = $this->db->prepare("UPDATE `config_cuentas` SET `id_cuenta` = :id WHERE `clave` = :clave");
                        $upd->execute(['id' => $cuenta['id'], 'clave' => $clave]);
                    } catch (Throwable $e) {
                        // Ignorar fallo de auto-curación silenciosa
                    }
                }
            }

            if ($cuenta) {
                $cfg['nombre_cuenta'] = $cuenta['nombre'] ?? null;
                $cfg['tipo_cuenta'] = $cuenta['tipo'] ?? null;
                $cfg['categoria_cuenta'] = $cuenta['categoria'] ?? null;
            }
        }

        if ($cfg['id_cuenta'] !== null && $cfg['id_cuenta'] !== '') {
            $cfg['id_cuenta'] = (int)$cfg['id_cuenta'];
        } else {
            $cfg['id_cuenta'] = null;
        }

        if (isset($cfg['id'])) {
            $cfg['id'] = (int)$cfg['id'];
        }
        if (isset($cfg['activo'])) {
            $cfg['activo'] = (int)$cfg['activo'];
        }

        return $cfg;
    }

    /**
     * Obtiene directamente el ID de la cuenta contable asociada a la clave mnemónica.
     * Retorna int o null si no está mapeada o no existe la clave.
     * Soporta llamada estática (ConfigCuentasModelo::obtenerIdPorClave) o de instancia ($modelo->obtenerIdPorClave).
     */
    public static function obtenerIdPorClave(string $clave, ?PDO $db = null): ?int {
        $instancia = new self($db ?? Conexion::obtenerInstancia());
        return $instancia->consultarIdPorClaveInterno($clave);
    }

    /**
     * Lógica interna de consulta y auto-resolución de ID por clave mnemónica.
     */
    public function consultarIdPorClaveInterno(string $clave): ?int {
        $clave = trim($clave);
        if ($clave === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT `id_cuenta`, `codigo_cuenta` FROM `config_cuentas` WHERE `clave` = :clave LIMIT 1");
        $stmt->execute(['clave' => $clave]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if ($row['id_cuenta'] !== null && $row['id_cuenta'] !== '') {
            return (int)$row['id_cuenta'];
        }

        // Resolución dinámica si id_cuenta no estaba asignado
        $tablaCuentas = $this->obtenerTablaCuentas();
        if ($tablaCuentas !== null && !empty($row['codigo_cuenta'])) {
            $stmtC = $this->db->prepare("SELECT `id` FROM `{$tablaCuentas}` WHERE `codigo` = :codigo LIMIT 1");
            $stmtC->execute(['codigo' => $row['codigo_cuenta']]);
            $idReal = $stmtC->fetchColumn();

            if ($idReal !== false && $idReal !== null) {
                $idInt = (int)$idReal;
                try {
                    $upd = $this->db->prepare("UPDATE `config_cuentas` SET `id_cuenta` = :id WHERE `clave` = :clave");
                    $upd->execute(['id' => $idInt, 'clave' => $clave]);
                } catch (Throwable $e) {
                    // Ignorar fallo de auto-curación silenciosa
                }
                return $idInt;
            }
        }

        return null;
    }

    /**
     * Obtiene el código contable (cadena) asociado a la clave mnemónica.
     */
    public function obtenerCodigoPorClave(string $clave): ?string {
        $clave = trim($clave);
        if ($clave === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT `codigo_cuenta` FROM `config_cuentas` WHERE `clave` = :clave LIMIT 1");
        $stmt->execute(['clave' => $clave]);
        $codigo = $stmt->fetchColumn();

        return ($codigo !== false && $codigo !== null) ? (string)$codigo : null;
    }

    /**
     * Lista todas las configuraciones mapeadas, enriquecidas con los metadatos de las cuentas.
     */
    public function listarConfiguracion(): array {
        $stmt = $this->db->query("SELECT * FROM `config_cuentas` ORDER BY `id` ASC");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($registros)) {
            return [];
        }

        $tablaCuentas = $this->obtenerTablaCuentas();
        $cuentasPorId = [];
        $cuentasPorCodigo = [];

        if ($tablaCuentas !== null) {
            try {
                $stmtC = $this->db->query("SELECT `id`, `codigo`, `nombre`, `tipo`, `categoria` FROM `{$tablaCuentas}`");
                while ($c = $stmtC->fetch(PDO::FETCH_ASSOC)) {
                    $cuentasPorId[$c['id']] = $c;
                    $cuentasPorCodigo[$c['codigo']] = $c;
                }
            } catch (Throwable $e) {
                // Continuar sin enriquecimiento si falla la consulta
            }
        }

        foreach ($registros as &$r) {
            $c = null;
            if (!empty($r['id_cuenta']) && isset($cuentasPorId[$r['id_cuenta']])) {
                $c = $cuentasPorId[$r['id_cuenta']];
            } elseif (!empty($r['codigo_cuenta']) && isset($cuentasPorCodigo[$r['codigo_cuenta']])) {
                $c = $cuentasPorCodigo[$r['codigo_cuenta']];
                if (empty($r['id_cuenta'])) {
                    $r['id_cuenta'] = (int)$c['id'];
                }
            }

            if ($c) {
                $r['nombre_cuenta'] = $c['nombre'] ?? null;
                $r['tipo_cuenta'] = $c['tipo'] ?? null;
                $r['categoria_cuenta'] = $c['categoria'] ?? null;
            }

            if ($r['id_cuenta'] !== null && $r['id_cuenta'] !== '') {
                $r['id_cuenta'] = (int)$r['id_cuenta'];
            } else {
                $r['id_cuenta'] = null;
            }

            if (isset($r['id'])) {
                $r['id'] = (int)$r['id'];
            }
            if (isset($r['activo'])) {
                $r['activo'] = (int)$r['activo'];
            }
        }
        unset($r);

        return $registros;
    }

    /**
     * Actualiza el mapeo de una clave mnemónica hacia un nuevo id_cuenta.
     * Sincroniza también el codigo_cuenta correspondiente si existe en el catálogo contable.
     * Retorna true si la clave existe y fue actualizada exitosamente, o false si la clave no existe.
     */
    public function actualizarMapeo(string $clave, int $idCuenta): bool {
        $clave = trim($clave);
        if ($clave === '') {
            return false;
        }

        $stmtCheck = $this->db->prepare("SELECT `id` FROM `config_cuentas` WHERE `clave` = :clave LIMIT 1");
        $stmtCheck->execute(['clave' => $clave]);
        if (!$stmtCheck->fetch()) {
            return false;
        }

        $codigoCuenta = null;
        $tablaCuentas = $this->obtenerTablaCuentas();
        if ($tablaCuentas !== null) {
            try {
                $stmtC = $this->db->prepare("SELECT `codigo` FROM `{$tablaCuentas}` WHERE `id` = :id LIMIT 1");
                $stmtC->execute(['id' => $idCuenta]);
                $codigoCuenta = $stmtC->fetchColumn();
            } catch (Throwable $e) {
                // Ignorar
            }
        }

        if ($codigoCuenta !== false && $codigoCuenta !== null) {
            $stmt = $this->db->prepare("
                UPDATE `config_cuentas`
                SET `id_cuenta` = :id_cuenta,
                    `codigo_cuenta` = :codigo_cuenta,
                    `updated_at` = CURRENT_TIMESTAMP
                WHERE `clave` = :clave
            ");
            return $stmt->execute([
                'id_cuenta'     => $idCuenta,
                'codigo_cuenta' => (string)$codigoCuenta,
                'clave'         => $clave
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE `config_cuentas`
                SET `id_cuenta` = :id_cuenta,
                    `updated_at` = CURRENT_TIMESTAMP
                WHERE `clave` = :clave
            ");
            return $stmt->execute([
                'id_cuenta' => $idCuenta,
                'clave'     => $clave
            ]);
        }
    }
}
