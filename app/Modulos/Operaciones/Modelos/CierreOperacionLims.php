<?php

namespace Cycsa\Modulos\Operaciones\Modelos;

final class CierreOperacionLims {
    public static function validar(array $items, ?array $cxc): ?string {
        if (!$items) {
            return 'No se puede cerrar una orden sin ensayos registrados.';
        }
        foreach ($items as $item) {
            $json = $item['resultados_json'] ?? '';
            $datos = json_decode($json, true);
            $filas = is_array($datos) ? ($datos['filas'] ?? (array_is_list($datos) ? $datos : [])) : [];
            if (!is_array($filas) || !$filas) {
                return "El ensayo '" . ($item['descripcion_ensayo'] ?? '') . "' no tiene resultados registrados.";
            }
            $revision = obtenerEstadoRevisionMatriz($json);
            if ($revision['estado'] !== 'aprobada') {
                return "El ensayo '" . ($item['descripcion_ensayo'] ?? '') . "' está en estado '" . $revision['estado_label'] . "'. Todos los ensayos deben estar APROBADOS.";
            }
        }
        if ($cxc === null || (float)($cxc['saldo'] ?? INF) > 0.01 || ($cxc['estado'] ?? '') !== 'Pagado') {
            return 'La factura debe estar Pagada y tener saldo C$ 0.00 antes del cierre.';
        }
        return null;
    }

    public static function cerrar(array $items, ?array $cxc, callable $actualizar, callable $auditar): ?string {
        $error = self::validar($items, $cxc);
        if ($error !== null) {
            return $error;
        }
        if (!$actualizar('Finalizado')) {
            return 'No se pudo actualizar el estado de la orden.';
        }
        if (!$auditar()) {
            return 'No se pudo registrar el cierre en la bitácora.';
        }
        return null;
    }
}
