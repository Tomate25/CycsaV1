<?php

namespace Cycsa\Modulos\Reportes\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Reportes\Modelos\ReporteModelo;
use Cycsa\Modulos\Reportes\Servicios\ExportadorExcelXlsx;

class ReportesControlador extends ControladorBase {

    private function autorizar(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            return;
        }
        if (!tienePermiso('reportes', 'ver')) {
            $_SESSION['error'] = 'No tiene permisos para acceder al Módulo de Reportes.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
    }

    private function puedeVerMiniModulo(array $meta): bool {
        if (($_SESSION['usuario_rol'] ?? 0) == 1) {
            return true;
        }
        $slug = (string)($meta['slug'] ?? '');
        if ($slug === 'rrhh_finanzas') {
            return tienePermiso('nomina', 'ver')
                || tienePermiso('activos_fijos', 'ver')
                || tienePermiso('prestamos', 'ver')
                || tienePermiso('proyectos', 'ver');
        }
        if ($slug === 'calidad_equipos') {
            return tienePermiso('equipos_bodegas', 'ver') || tienePermiso('control_calidad', 'ver');
        }
        $perm = (string)($meta['permiso'] ?? '');
        return $perm === '' ? true : tienePermiso($perm, 'ver');
    }

    /**
     * Cajón principal de mini-módulos de Reportes + KPIs globales.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->autorizar($respuesta);
        if (!isset($_SESSION['usuario_id']) || !tienePermiso('reportes', 'ver')) {
            return;
        }

        $modelo = new ReporteModelo();
        $catalogo = $modelo->obtenerCatalogoMiniModulos();
        $autorizados = [];
        foreach ($catalogo as $slug => $meta) {
            if ($this->puedeVerMiniModulo($meta)) {
                $autorizados[$slug] = $meta;
            }
        }

        $resumen = $modelo->obtenerResumenGlobalHub(array_keys($autorizados));

        $grupos = [];
        foreach ($autorizados as $slug => $meta) {
            $grupo = (string)($meta['grupo'] ?? 'General');
            $meta['total_registros'] = $resumen['conteos'][$slug] ?? 0;
            $grupos[$grupo][] = $meta;
        }

        $this->renderizar('reportes/vistas/index', [
            'titulo' => 'Centro de Reportes CYCSA',
            'grupos' => $grupos,
            'kpis_globales' => $resumen['kpis_globales'],
            'total_modulos' => count($autorizados),
        ]);
    }

    /**
     * 2.21: Construye filtros de reporte resolviendo `?mes=YYYY-MM` -> `desde`/`hasta` del mes completo.
     *
     * @return array{mes: string, desde: string, hasta: string, estado: string, q: string, subvista: string}
     */
    private function construirFiltrosDesdeGet(): array {
        $mes = trim((string)($_GET['mes'] ?? ''));
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));

        if (preg_match('/^\d{4}-\d{2}$/', $mes) === 1) {
            $ts = strtotime($mes . '-01');
            if ($ts !== false) {
                $desde = date('Y-m-01', $ts);
                $hasta = date('Y-m-t', $ts);
            }
        } else {
            $mes = '';
        }

        return [
            'mes' => $mes,
            'desde' => $desde,
            'hasta' => $hasta,
            'estado' => trim((string)($_GET['estado'] ?? '')),
            'q' => trim((string)($_GET['q'] ?? '')),
            'subvista' => trim((string)($_GET['subvista'] ?? '')),
        ];
    }

    /**
     * Pantalla de reporte por mini-módulo con tarjetas KPI, filtros, tabla de seguimiento y PDFs.
     */
    public function ver(Peticion $peticion, Respuesta $respuesta): void {
        $this->autorizar($respuesta);
        if (!isset($_SESSION['usuario_id']) || !tienePermiso('reportes', 'ver')) {
            return;
        }

        $modelo = new ReporteModelo();
        $catalogo = $modelo->obtenerCatalogoMiniModulos();

        $slug = trim((string)($_GET['modulo'] ?? 'cotizaciones'));
        if (!isset($catalogo[$slug])) {
            $slug = 'cotizaciones';
        }

        if (!$this->puedeVerMiniModulo($catalogo[$slug])) {
            $_SESSION['error'] = 'No tiene permisos sobre el módulo origen de este reporte.';
            $respuesta->redirigir('/Cycsa/publico/reportes');
            return;
        }

        $filtros = $this->construirFiltrosDesdeGet();

        $reporte = $modelo->generarReporteModulo($slug, $filtros);

        $navegacionModulos = [];
        foreach ($catalogo as $s => $m) {
            if ($this->puedeVerMiniModulo($m)) {
                $navegacionModulos[$s] = $m;
            }
        }

        $this->renderizar('reportes/vistas/ver', [
            'titulo' => 'Reporte: ' . $reporte['meta']['nombre'],
            'slug' => $slug,
            'reporte' => $reporte,
            'navegacionModulos' => $navegacionModulos,
        ]);
    }

    /**
     * Exporta a Excel real (.xlsx) exactamente los registros filtrados del mini-módulo.
     */
    public function exportarExcel(Peticion $peticion, Respuesta $respuesta): void {
        $this->autorizar($respuesta);
        if (!isset($_SESSION['usuario_id']) || !tienePermiso('reportes', 'ver')) {
            return;
        }

        $modelo = new ReporteModelo();
        $catalogo = $modelo->obtenerCatalogoMiniModulos();

        $slug = trim((string)($_GET['modulo'] ?? 'cotizaciones'));
        if (!isset($catalogo[$slug]) || !$this->puedeVerMiniModulo($catalogo[$slug])) {
            $_SESSION['error'] = 'Reporte no autorizado o inexistente.';
            $respuesta->redirigir('/Cycsa/publico/reportes');
            return;
        }

        $filtros = $this->construirFiltrosDesdeGet();

        $reporte = $modelo->generarReporteModulo($slug, $filtros);
        $titulo = (string)$reporte['meta']['nombre'];
        if (!empty($filtros['subvista']) && !empty($reporte['subvistas'][$filtros['subvista']])) {
            $titulo .= ' - ' . $reporte['subvistas'][$filtros['subvista']];
        }

        $descFiltros = [];
        if ($filtros['mes'] !== '') $descFiltros[] = 'Mes: ' . $filtros['mes'];
        if ($filtros['desde'] !== '') $descFiltros[] = 'Desde: ' . $filtros['desde'];
        if ($filtros['hasta'] !== '') $descFiltros[] = 'Hasta: ' . $filtros['hasta'];
        if ($filtros['estado'] !== '') $descFiltros[] = 'Estado: ' . $filtros['estado'];
        if ($filtros['q'] !== '') $descFiltros[] = 'Búsqueda: ' . $filtros['q'];
        $subtitulo = 'Generado: ' . date('Y-m-d H:i') . ' | Total registros: ' . count($reporte['rows'])
            . (!empty($descFiltros) ? ' | Filtros: ' . implode(' · ', $descFiltros) : '');

        $xlsx = ExportadorExcelXlsx::generar($titulo, $subtitulo, $reporte['columns'], $reporte['rows']);
        $nombreArchivo = 'reporte_' . $slug . '_' . date('Ymd_His') . '.xlsx';

        $this->emitirBinarioExcel($nombreArchivo, $xlsx);
    }

    protected function emitirBinarioExcel(string $nombreArchivo, string $contenidoXlsx): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . strlen($contenidoXlsx));
        header('Cache-Control: max-age=0');
        echo $contenidoXlsx;
    }
}
