<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use PDO;

class LaboratorioControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('laboratorio', $accion)) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    /**
     * Dashboard del laboratorio: listado de muestras activas y rupturas programadas (vista 100% ciega).
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

        // 1. Obtener listado de muestras ingresadas (sin información de cliente ni precios)
        $sqlMuestras = "SELECT lm.id AS id_lote, rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote,
                               lm.fecha_moldeo, rm.fecha_recepcion, rm.estado
                        FROM lotes_muestras lm
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        ORDER BY lm.id DESC LIMIT 50";
        $stmtMuestras = $db->query($sqlMuestras);
        $muestras = $stmtMuestras->fetchAll(PDO::FETCH_ASSOC);

        // 2. Obtener rupturas programadas para los próximos 7 días (ciego)
        $sqlProximas = "SELECT ee.id, ee.identificador_especimen, ee.edad_dias, ee.fecha_programada,
                               rm.codigo_muestra, rm.codigo_campo, lm.id AS id_lote,
                               cd.descripcion_ensayo AS nombre_ensayo
                        FROM ensayo_edades ee
                        JOIN lotes_muestras lm ON ee.id_lote = lm.id
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        LEFT JOIN cotizacion_detalles cd ON ee.id_detalle_cotizacion = cd.id
                        WHERE ee.estado IN ('Programado', 'Listo para Ensaye')
                          AND ee.edad_dias > 0
                          AND ee.fecha_programada BETWEEN CURRENT_DATE - INTERVAL 2 DAY AND CURRENT_DATE + INTERVAL 7 DAY
                        ORDER BY ee.fecha_programada ASC, rm.codigo_muestra ASC";
        $stmtProx = $db->query($sqlProximas);
        $rupturasProgramadas = $stmtProx->fetchAll(PDO::FETCH_ASSOC);

        // 3. Obtener rupturas para el calendario (rango de 60 días antes y después)
        $sqlCalendario = "SELECT ee.id, ee.identificador_especimen, ee.edad_dias, ee.fecha_programada,
                                 ee.estado, rm.codigo_muestra, rm.codigo_campo, lm.id AS id_lote,
                                 cd.descripcion_ensayo AS nombre_ensayo,
                                 os.codigo_os
                          FROM ensayo_edades ee
                          JOIN lotes_muestras lm ON ee.id_lote = lm.id
                          JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                          JOIN ordenes_servicio os ON rm.id_os = os.id
                          LEFT JOIN cotizacion_detalles cd ON ee.id_detalle_cotizacion = cd.id
                          WHERE ee.fecha_programada BETWEEN CURRENT_DATE - INTERVAL 60 DAY AND CURRENT_DATE + INTERVAL 60 DAY
                          ORDER BY ee.fecha_programada ASC";
        $stmtCal = $db->query($sqlCalendario);
        $eventosCalendario = $stmtCal->fetchAll(PDO::FETCH_ASSOC);

        $this->renderizar('operaciones/vistas/laboratorio_dashboard', [
            'titulo' => 'Portal de Laboratorio LIMS (Operación Ciega)',
            'muestras' => $muestras,
            'rupturas' => $rupturasProgramadas,
            'eventosCalendario' => $eventosCalendario,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Detalle ciego de una muestra en laboratorio para cargar datos.
     */
    public function detalleMuestra(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $idLote = (int)($_GET['id_lote'] ?? 0);
        if ($idLote <= 0) {
            $_SESSION['error'] = 'Muestra inválida.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

        // 1. Obtener datos del lote y recepción (estrictamente técnicos)
        $sqlLote = "SELECT lm.*, rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion
                    FROM lotes_muestras lm
                    JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                    WHERE lm.id = :id_lote";
        $stmt = $db->prepare($sqlLote);
        $stmt->execute(['id_lote' => $idLote]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lote) {
            $_SESSION['error'] = 'Muestra no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        // 2. Obtener especímenes del lote
        $modelo = new OperacionModelo();
        $especimenes = $modelo->obtenerDetallesLote($idLote);
        $historial = $modelo->obtenerHistorialInformes($idLote);

        // 3. Obtener ensayos cotizados relacionados (ciego)
        $sqlItems = "SELECT cd.id, cd.descripcion_ensayo, cd.norma_astm, fe.archivo_markdown, 
                            fe.nombre AS formato_nombre, cd.resultados_json, cd.id_cotizacion
                     FROM cotizacion_detalles cd
                     JOIN lotes_muestras lm ON lm.id = :id_lote
                     JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                     JOIN ordenes_servicio os ON rm.id_os = os.id
                     LEFT JOIN productos p ON cd.id_producto = p.id
                     LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                     WHERE cd.id_cotizacion = os.id_cotizacion";
        $stmtItems = $db->prepare($sqlItems);
        $stmtItems->execute(['id_lote' => $idLote]);
        $itemsOS = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Cargar esquemas JSON
        $schemaPath = dirname(__DIR__, 4) . '/database/ensayos/formatos_schema.json';
        $formatosSchemaJson = file_exists($schemaPath) ? file_get_contents($schemaPath) : '{}';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/laboratorio_detalle', [
            'titulo' => 'Hoja de Trabajo Ciega - Muestra ' . $lote['codigo_muestra'],
            'lote' => $lote,
            'especimenes' => $especimenes,
            'historial' => $historial,
            'itemsOS' => $itemsOS,
            'formatosSchemaJson' => $formatosSchemaJson,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda la carga de rotura de un cilindro.
     */
    public function guardarRuptura(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $idEnsayo = (int)($datos['id_ensayo'] ?? 0);
            $idLote = (int)($datos['id_lote'] ?? 0);
            $carga = (float)($datos['carga_lbs'] ?? 0);
            $area = (float)($datos['area_in2'] ?? 28.274);

            if ($idEnsayo <= 0) {
                $_SESSION['error'] = 'Identificador de especímen inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $modelo = new OperacionModelo();
            $resultado = $modelo->guardarResultadoRuptura($idEnsayo, [
                'carga_lbs' => $carga,
                'area_in2' => $area
            ]);

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?id_lote=' . $idLote);
        }
    }
}
