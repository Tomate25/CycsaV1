<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;

/**
 * Módulo independiente "Captura Técnica" (/captura-tecnica).
 *
 * Mismo diseño sobrio que Operaciones, 100% ciego (ISO/IEC 17025):
 * el técnico únicamente visualiza códigos de O/S y ensayos asignados para llenar
 * sus mediciones técnicas y enviarlas a revisión, sin acceso a cliente, proyecto,
 * datos de Recepción, impresión, PDF ni envío por correo.
 */
class CapturaTecnicaControlador extends ControladorBase {

    private function verificarAccesoCaptura(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
        $puede = tienePermiso('operaciones', 'ver')
            || tienePermiso('operaciones', 'crear_editar')
            || tienePermiso('laboratorio', 'ver')
            || (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025());
        if (!$puede) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    /**
     * Bandeja principal de Captura Técnica (mismo diseño sobrio de Operaciones, 100% ciego).
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarAccesoCaptura($respuesta);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $busqueda = trim((string)($_GET['q'] ?? ''));
        $filtro = trim((string)($_GET['filtro'] ?? 'pendientes'));
        if (!in_array($filtro, ['pendientes', 'devueltas', 'en_revision', 'todas'], true)) {
            $filtro = 'pendientes';
        }

        $modelo = new OperacionModelo();
        $db = Conexion::obtenerInstancia();
        $ordenesRaw = $modelo->obtenerOSActivas($busqueda, 'activas');

        $conteos = [
            'pendientes' => 0,
            'devueltas' => 0,
            'en_revision' => 0,
            'todas' => 0,
        ];

        $ordenesCiegas = [];
        foreach ($ordenesRaw as $o) {
            $idOS = (int)($o['id'] ?? 0);
            if ($idOS <= 0) {
                continue;
            }
            $items = $modelo->obtenerItemsOS($idOS);
            $items = array_values(array_filter($items, static function ($it): bool {
                if (!is_array($it)) {
                    return false;
                }
                if (array_key_exists('permitido', $it) && (int)$it['permitido'] !== 1) {
                    return false;
                }
                // Los ensayos de compactación (Densímetro, Cono de Arena, Reemplazo de Agua)
                // se llenan directamente en Órdenes de Servicio; no los llenan los técnicos.
                if (function_exists('esItemCompactacion') && esItemCompactacion($it)) {
                    return false;
                }
                return true;
            }));
            if (empty($items)) {
                continue;
            }

            $total = count($items);
            $llenadas = 0;
            $aprobadas = 0;
            $devueltas = 0;
            $enRevision = 0;
            $primerPendienteId = null;
            $primerDevueltoId = null;
            $primerItemId = null;

            $itemsLimpios = [];
            foreach ($items as $it) {
                $idDet = (int)($it['id'] ?? 0);
                $rev = obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
                $est = (string)($rev['estado'] ?? 'pendiente');
                $tieneRes = !empty($rev['tiene_resultados']);
                if ($tieneRes) {
                    $llenadas++;
                }
                if ($est === 'aprobada') {
                    $aprobadas++;
                } elseif ($est === 'devuelta') {
                    $devueltas++;
                    if ($primerDevueltoId === null && $idDet > 0) {
                        $primerDevueltoId = $idDet;
                    }
                } elseif ($est === 'en_revision') {
                    $enRevision++;
                }
                if ($idDet > 0) {
                    if ($primerItemId === null) {
                        $primerItemId = $idDet;
                    }
                    if (!$tieneRes && $primerPendienteId === null) {
                        $primerPendienteId = $idDet;
                    }
                }

                // Contar muestras sin exponer ningún dato de recepción/cliente
                $filasRaw = [];
                if (!empty($it['resultados_json'])) {
                    $dec = json_decode((string)$it['resultados_json'], true);
                    if (is_array($dec)) {
                        $filasRaw = isset($dec['filas']) && is_array($dec['filas']) ? $dec['filas'] : (array_is_list($dec) ? $dec : []);
                    }
                }
                $cantMuestras = max(1, (int)($it['cantidad'] ?? 1), count($filasRaw));

                $itemsLimpios[] = [
                    'id' => $idDet,
                    'descripcion_ensayo' => (string)($it['descripcion_ensayo'] ?? 'Ensayo técnico'),
                    'norma_astm' => (string)($it['norma_astm'] ?? ''),
                    'cantidad' => $cantMuestras,
                    'estado' => $est,
                    'tiene_resultados' => $tieneRes,
                    'motivo_devolucion' => (string)($rev['motivo_devolucion'] ?? ''),
                    'usuario_revisor' => (string)($rev['usuario_revisor'] ?? 'Supervisor'),
                    'fecha_revision' => (string)($rev['fecha_revision'] ?? ''),
                ];
            }

            $tienePendientes = ($aprobadas < $total && ($llenadas < $total || $devueltas > 0));
            $soloEnRevision = ($devueltas === 0 && $enRevision > 0 && ($aprobadas + $enRevision) >= $total && $aprobadas < $total);

            $conteos['todas']++;
            if ($devueltas > 0) {
                $conteos['devueltas']++;
            }
            if ($tienePendientes) {
                $conteos['pendientes']++;
            }
            if ($enRevision > 0) {
                $conteos['en_revision']++;
            }

            $pasaFiltro = match ($filtro) {
                'pendientes' => $tienePendientes,
                'devueltas' => $devueltas > 0,
                'en_revision' => $enRevision > 0,
                default => true,
            };
            if (!$pasaFiltro) {
                continue;
            }

            $idAccion = $primerDevueltoId ?? $primerPendienteId ?? $primerItemId;
            $ordenesCiegas[] = [
                'id' => $idOS,
                'codigo_os' => (string)($o['codigo_os'] ?? ('OS-' . $idOS)),
                'items' => $itemsLimpios,
                'total_ensayos' => $total,
                'llenadas' => $llenadas,
                'aprobadas' => $aprobadas,
                'devueltas' => $devueltas,
                'en_revision' => $enRevision,
                'solo_en_revision' => $soloEnRevision,
                'id_accion_principal' => $idAccion,
            ];
        }

        $this->renderizar('operaciones/vistas/captura_tecnica_index', [
            'titulo' => 'Captura Técnica - Cycsa',
            'ordenes' => $ordenesCiegas,
            'filtroActivo' => $filtro,
            'conteos' => $conteos,
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Pantalla dedicada exclusivamente a llenar datos técnicos del ensayo (100% ciego ISO/IEC 17025).
     */
    public function llenar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarAccesoCaptura($respuesta);
        $idDetalle = (int)($_GET['id_detalle'] ?? 0);
        if ($idDetalle > 0) {
            try {
                $db = Conexion::obtenerInstancia();
                $stmtC = $db->prepare("
                    SELECT cd.id, cd.descripcion_ensayo, cd.norma_astm,
                           p.formato_id, p.nombre_comercial, p.ensayo_servicio,
                           fe.nombre AS formato_nombre, fe.archivo_markdown
                    FROM cotizacion_detalles cd
                    LEFT JOIN productos p ON cd.id_producto = p.id
                    LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                    WHERE cd.id = :id LIMIT 1
                ");
                $stmtC->execute(['id' => $idDetalle]);
                $detRow = $stmtC->fetch(\PDO::FETCH_ASSOC);
                if ($detRow && function_exists('esItemCompactacion') && esItemCompactacion($detRow)) {
                    $_SESSION['error'] = 'Los ensayos de compactación se capturan directamente en Órdenes de Servicio (Recepción), no en Captura Técnica.';
                    $respuesta->redirigir('/Cycsa/publico/captura-tecnica');
                    return;
                }
            } catch (\Throwable $e) {
            }
        }
        $opCtrl = new OperacionesControlador();
        $opCtrl->capturaMatrizProducto($peticion, $respuesta, true);
    }
}
