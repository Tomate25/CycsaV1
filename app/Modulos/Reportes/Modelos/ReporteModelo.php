<?php

namespace Cycsa\Modulos\Reportes\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

/**
 * Modelo central de inteligencia y trazabilidad del Módulo de Reportes.
 * Consolida información, KPIs, enlaces a PDFs oficiales y datos tabulares exportables
 * de todos los módulos operativos, documentales, contables y financieros de CYCSA.
 */
class ReporteModelo extends ModeloBase {

    /**
     * Catálogo maestro de mini-módulos mostrados en el Cajón de Reportes.
     *
     * @return array<string, array<string, mixed>>
     */
    public function obtenerCatalogoMiniModulos(): array {
        return [
            'cotizaciones' => [
                'slug' => 'cotizaciones',
                'nombre' => 'Cotizaciones (RG-FM-31)',
                'grupo' => 'Comercial & Facturación',
                'icono' => 'fa-solid fa-file-invoice-dollar',
                'color' => 'linear-gradient(135deg, #d97706, #b45309)',
                'permiso' => 'cotizaciones',
                'desc' => 'Todas las cotizaciones emitidas, montos, estados, versiones y descarga directa de PDF oficial.',
            ],
            'ventas' => [
                'slug' => 'ventas',
                'nombre' => 'Facturas, Ventas y Cobros',
                'grupo' => 'Comercial & Facturación',
                'icono' => 'fa-solid fa-cash-register',
                'color' => 'linear-gradient(135deg, #059669, #047857)',
                'permiso' => 'ventas',
                'desc' => 'Control de facturas emitidas, saldos por cobrar, abonos y acceso directo al PDF de cada factura.',
            ],
            'clientes' => [
                'slug' => 'clientes',
                'nombre' => 'Clientes & Tarifario Ensayos',
                'grupo' => 'Comercial & Facturación',
                'icono' => 'fa-solid fa-address-book',
                'color' => 'linear-gradient(135deg, #2563eb, #1d4ed8)',
                'permiso' => 'clientes',
                'desc' => 'Resumen comercial por cliente (cotizaciones, O/S y montos) y catálogo técnico de productos/ensayos.',
            ],
            'ordenes_servicio' => [
                'slug' => 'ordenes_servicio',
                'nombre' => 'Órdenes de Servicio (RG-FM-39)',
                'grupo' => 'Órdenes & Hojas Oficiales',
                'icono' => 'fa-solid fa-file-contract',
                'color' => 'linear-gradient(135deg, #103487, #1e40af)',
                'permiso' => 'ordenes_servicio',
                'desc' => 'Trazabilidad completa de órdenes, estados operativos, hojas vinculadas y PDF oficial RG-FM-39.',
            ],
            'rtfm13' => [
                'slug' => 'rtfm13',
                'nombre' => 'Hojas RT-FM-13 y Clientes',
                'grupo' => 'Órdenes & Hojas Oficiales',
                'icono' => 'fa-solid fa-file-lines',
                'color' => 'linear-gradient(135deg, #ea580c, #c2410c)',
                'permiso' => 'ordenes_servicio',
                'desc' => 'Todas las hojas RT-FM-13 emitidas vinculadas a su cliente, proyecto, O/S, cotización, recepción y PDF.',
            ],
            'fm40b' => [
                'slug' => 'fm40b',
                'nombre' => 'Hojas Muestreo RT-FM-40 B',
                'grupo' => 'Órdenes & Hojas Oficiales',
                'icono' => 'fa-solid fa-truck-pickup',
                'color' => 'linear-gradient(135deg, #0284c7, #0369a1)',
                'permiso' => 'ordenes_servicio',
                'desc' => 'Salidas a campo, checklists RT-FM-40 B, técnicos, vehículos, fechas y formato imprimible PDF.',
            ],
            'laboratorio' => [
                'slug' => 'laboratorio',
                'nombre' => 'Laboratorio Hojas RT-FM-60',
                'grupo' => 'Operaciones, Lab & Calidad',
                'icono' => 'fa-solid fa-flask',
                'color' => 'linear-gradient(135deg, #7c3aed, #5b21b6)',
                'permiso' => 'laboratorio',
                'desc' => 'Hojas RT-FM-60, probetas en lote, roturas programadas/reales e informes parciales/consolidados PDF.',
            ],
            'operaciones' => [
                'slug' => 'operaciones',
                'nombre' => 'Operaciones & Matrices RT-FM-22',
                'grupo' => 'Operaciones, Lab & Calidad',
                'icono' => 'fa-solid fa-gears',
                'color' => 'linear-gradient(135deg, #0ea5e9, #0284c7)',
                'permiso' => 'operaciones',
                'desc' => 'Estado de matrices técnicas por O/S y ensayo, revisiones, hojas de campo HC y PDFs RT-FM-22.',
            ],
            'calidad_equipos' => [
                'slug' => 'calidad_equipos',
                'nombre' => 'Calidad (-CR), Equipos y Traslados',
                'grupo' => 'Operaciones, Lab & Calidad',
                'icono' => 'fa-solid fa-code-compare',
                'color' => 'linear-gradient(135deg, #0f766e, #115e59)',
                'permiso' => 'equipos_bodegas',
                'desc' => 'Muestras réplica -CR de control de calidad, catálogo de calibración metrológica y traslados de equipos.',
            ],
            'contabilidad' => [
                'slug' => 'contabilidad',
                'nombre' => 'Contabilidad y Registros Diarios',
                'grupo' => 'Administración, Compras & Finanzas',
                'icono' => 'fa-solid fa-calculator',
                'color' => 'linear-gradient(135deg, #db2777, #be185d)',
                'permiso' => 'contabilidad',
                'desc' => 'Todos los asientos contables / libro diario (debe y haber), CxC, CxP y transacciones bancarias.',
            ],
            'compras' => [
                'slug' => 'compras',
                'nombre' => 'Compras, Órdenes y Recepciones',
                'grupo' => 'Administración, Compras & Finanzas',
                'icono' => 'fa-solid fa-cart-shopping',
                'color' => 'linear-gradient(135deg, #f97316, #ea580c)',
                'permiso' => 'compras',
                'desc' => 'Todas las órdenes de compra, montos con IVA, recepciones de factura y directorio de proveedores.',
            ],
            'inventario' => [
                'slug' => 'inventario',
                'nombre' => 'Inventario y Kárdex Diario',
                'grupo' => 'Administración, Compras & Finanzas',
                'icono' => 'fa-solid fa-boxes-stacked',
                'color' => 'linear-gradient(135deg, #0d9488, #0f766e)',
                'permiso' => 'inventario',
                'desc' => 'Existencias actuales de insumos, alertas de stock mínimo y movimientos históricos de kárdex.',
            ],
            'rrhh_finanzas' => [
                'slug' => 'rrhh_finanzas',
                'nombre' => 'RRHH, Activos, Préstamos y Obras',
                'grupo' => 'Administración, Compras & Finanzas',
                'icono' => 'fa-solid fa-building-columns',
                'color' => 'linear-gradient(135deg, #4f46e5, #3730a3)',
                'permiso' => 'nomina',
                'desc' => 'Reportes ejecutivos de planillas de nómina, activos fijos y depreciación, préstamos y proyectos.',
            ],
        ];
    }

    /**
     * Resumen rápido (KPIs globales y conteos por tarjeta) para el Cajón de Reportes.
     *
     * @param string[] $slugsAutorizados
     * @return array<string, mixed>
     */
    public function obtenerResumenGlobalHub(array $slugsAutorizados): array {
        $conteos = [
            'cotizaciones' => $this->contarSeguro("SELECT COUNT(*) FROM cotizaciones"),
            'ventas' => $this->contarSeguro("SELECT COUNT(*) FROM ordenes_servicio"),
            'clientes' => $this->contarSeguro("SELECT COUNT(*) FROM clientes"),
            'ordenes_servicio' => $this->contarSeguro("SELECT COUNT(*) FROM ordenes_servicio"),
            'rtfm13' => $this->contarSeguro("SELECT COUNT(*) FROM hojas_solicitud"),
            'fm40b' => $this->contarSeguro("SELECT COUNT(*) FROM programacion_muestreo"),
            'laboratorio' => $this->contarSeguro("SELECT COUNT(*) FROM recepcion_muestras"),
            'operaciones' => $this->contarSeguro("SELECT COUNT(*) FROM cotizacion_detalles cd JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion"),
            'calidad_equipos' => $this->contarSeguro("SELECT COUNT(*) FROM equipos_traslados"),
            'contabilidad' => $this->contarSeguro("SELECT COUNT(*) FROM partidas_diario"),
            'compras' => $this->contarSeguro("SELECT COUNT(*) FROM ordenes_compra"),
            'inventario' => $this->contarSeguro("SELECT COUNT(*) FROM inventario_insumos"),
            'rrhh_finanzas' => $this->contarSeguro("SELECT (SELECT COUNT(*) FROM rrhh_nominas) + (SELECT COUNT(*) FROM activos_fijos) + (SELECT COUNT(*) FROM prestamos_bancarios) + (SELECT COUNT(*) FROM proyectos)"),
        ];

        $kpisGlobales = [];
        if (in_array('cotizaciones', $slugsAutorizados, true)) {
            $totCot = (float)$this->escalarSeguro("SELECT COALESCE(SUM(total), 0) FROM cotizaciones WHERE estado = 'Aprobada por Cliente'");
            $kpisGlobales[] = [
                'titulo' => 'Cotizaciones Emitidas',
                'valor' => (string)$conteos['cotizaciones'],
                'subtitulo' => 'Aprobado: C$ ' . number_format($totCot, 2),
                'icono' => 'fa-solid fa-file-invoice-dollar',
                'clase' => 'kpi-1',
            ];
        }
        if (in_array('rtfm13', $slugsAutorizados, true) || in_array('fm40b', $slugsAutorizados, true) || in_array('laboratorio', $slugsAutorizados, true)) {
            $kpisGlobales[] = [
                'titulo' => 'Hojas Oficiales Emitidas',
                'valor' => (string)($conteos['rtfm13'] + $conteos['fm40b']),
                'subtitulo' => "RT-FM-13: {$conteos['rtfm13']} · FM-40 B: {$conteos['fm40b']} · Lab (60): {$conteos['laboratorio']}",
                'icono' => 'fa-solid fa-folder-open',
                'clase' => 'kpi-2',
            ];
        }
        if (in_array('ventas', $slugsAutorizados, true)) {
            $saldoCxc = (float)$this->escalarSeguro("SELECT COALESCE(SUM(saldo), 0) FROM cuentas_por_cobrar WHERE estado <> 'Pagado'");
            $kpisGlobales[] = [
                'titulo' => 'Facturas / O/S Facturables',
                'valor' => (string)$conteos['ventas'],
                'subtitulo' => 'Saldo CxC pendiente: C$ ' . number_format($saldoCxc, 2),
                'icono' => 'fa-solid fa-cash-register',
                'clase' => 'kpi-3',
            ];
        }
        if (in_array('compras', $slugsAutorizados, true) || in_array('contabilidad', $slugsAutorizados, true)) {
            $totCompras = (float)$this->escalarSeguro("SELECT COALESCE(SUM(total), 0) FROM ordenes_compra WHERE estado <> 'Anulada'");
            $kpisGlobales[] = [
                'titulo' => 'Registros Diarios & Compras',
                'valor' => (string)($conteos['contabilidad'] + $conteos['compras']),
                'subtitulo' => "Asientos: {$conteos['contabilidad']} · Compras: C$ " . number_format($totCompras, 2),
                'icono' => 'fa-solid fa-calculator',
                'clase' => 'kpi-4',
            ];
        }

        return [
            'conteos' => $conteos,
            'kpis_globales' => $kpisGlobales,
        ];
    }

    /**
     * Ejecuta y construye el reporte completo de un mini-módulo con filtros.
     *
     * @return array<string, mixed>
     */
    public function generarReporteModulo(string $slug, array $filtros = []): array {
        $catalogo = $this->obtenerCatalogoMiniModulos();
        if (!isset($catalogo[$slug])) {
            throw new \InvalidArgumentException("Mini-módulo de reporte desconocido: {$slug}");
        }

        $meta = $catalogo[$slug];
        $normFiltros = [
            'desde' => trim((string)($filtros['desde'] ?? '')),
            'hasta' => trim((string)($filtros['hasta'] ?? '')),
            'estado' => trim((string)($filtros['estado'] ?? '')),
            'q' => trim((string)($filtros['q'] ?? '')),
            'subvista' => trim((string)($filtros['subvista'] ?? '')),
        ];

        $data = match ($slug) {
            'cotizaciones' => $this->reporteCotizaciones($normFiltros),
            'ventas' => $this->reporteVentasFacturas($normFiltros),
            'clientes' => $this->reporteClientesYCatalogo($normFiltros),
            'ordenes_servicio' => $this->reporteOrdenesServicio($normFiltros),
            'rtfm13' => $this->reporteHojasRtFm13($normFiltros),
            'fm40b' => $this->reporteHojasFm40B($normFiltros),
            'laboratorio' => $this->reporteLaboratorioRtFm60($normFiltros),
            'operaciones' => $this->reporteOperacionesMatrices($normFiltros),
            'calidad_equipos' => $this->reporteCalidadYEquipos($normFiltros),
            'contabilidad' => $this->reporteContabilidad($normFiltros),
            'compras' => $this->reporteCompras($normFiltros),
            'inventario' => $this->reporteInventario($normFiltros),
            'rrhh_finanzas' => $this->reporteRrhhYFinanzas($normFiltros),
        };

        return array_merge([
            'meta' => $meta,
            'filtros' => $normFiltros,
            'subvistas' => [],
            'estados_filtro' => [],
        ], $data);
    }

    private function contarSeguro(string $sql): int {
        try {
            return (int)$this->db->query($sql)->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function escalarSeguro(string $sql): mixed {
        try {
            return $this->db->query($sql)->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function aplicarFiltroFechas(array &$where, array &$params, string $colFecha, array $filtros, string $pref = 'f'): void {
        if ($filtros['desde'] !== '') {
            $where[] = "DATE({$colFecha}) >= :{$pref}_desde";
            $params["{$pref}_desde"] = $filtros['desde'];
        }
        if ($filtros['hasta'] !== '') {
            $where[] = "DATE({$colFecha}) <= :{$pref}_hasta";
            $params["{$pref}_hasta"] = $filtros['hasta'];
        }
    }

    // =========================================================================
    // 1. COTIZACIONES (RG-FM-31)
    // =========================================================================
    private function reporteCotizaciones(array $f): array {
        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'cot.fecha_creacion', $f, 'cot');
        if ($f['estado'] !== '') {
            $where[] = "cot.estado = :cot_est";
            $params['cot_est'] = $f['estado'];
        }
        if ($f['q'] !== '') {
            $where[] = "(cot.codigo LIKE :q1 OR cli.nombre_razon_social LIKE :q2 OR cot.nombre_proyecto LIKE :q3 OR cot.atencion_a LIKE :q4)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }

        $sql = "SELECT cot.id, cot.codigo, cot.version, cot.fecha_creacion, cot.estado, cot.prioridad,
                       cot.nombre_proyecto, cot.atencion_a, cot.condicion_pago, cot.subtotal, cot.descuento, cot.impuesto, cot.total,
                       cli.nombre_razon_social AS cliente_nombre,
                       COALESCE(cli_inf.nombre_razon_social, cli.nombre_razon_social) AS cliente_informe,
                       u.nombre AS creador_nombre,
                       (SELECT COUNT(*) FROM cotizacion_detalles cd WHERE cd.id_cotizacion = cot.id) AS total_items
                FROM cotizaciones cot
                LEFT JOIN clientes cli ON cot.id_cliente = cli.id
                LEFT JOIN clientes cli_inf ON cli_inf.id = COALESCE(cot.id_cliente_informe, cot.id_cliente)
                LEFT JOIN usuarios u ON cot.id_usuario_creador = u.id";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY cot.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aprobadas = 0;
        $montoTotal = 0.0;
        $montoAprobado = 0.0;
        $pendientes = 0;
        $rows = [];

        foreach ($raw as $r) {
            $tot = (float)($r['total'] ?? 0);
            $montoTotal += $tot;
            if (($r['estado'] ?? '') === 'Aprobada por Cliente') {
                $aprobadas++;
                $montoAprobado += $tot;
            }
            if (in_array($r['estado'] ?? '', ['Borrador', 'En Revision', 'Observada', 'Aprobada Internamente', 'Enviada al Cliente'], true)) {
                $pendientes++;
            }

            $rows[] = [
                'codigo' => $r['codigo'],
                'version' => 'v' . (int)($r['version'] ?? 1),
                'fecha' => !empty($r['fecha_creacion']) ? date('Y-m-d', strtotime($r['fecha_creacion'])) : '',
                'cliente' => $r['cliente_nombre'] ?? '—',
                'proyecto' => $r['nombre_proyecto'] ?: '—',
                'items' => (int)($r['total_items'] ?? 0),
                'estado' => $r['estado'] ?? 'Borrador',
                'subtotal' => round((float)($r['subtotal'] ?? 0), 2),
                'descuento' => round((float)($r['descuento'] ?? 0), 2),
                'impuesto' => round((float)($r['impuesto'] ?? 0), 2),
                'total' => round($tot, 2),
                'creador' => $r['creador_nombre'] ?? '—',
                '_pdfs' => [
                    ['label' => 'PDF Cotización', 'url' => '/Cycsa/publico/cotizaciones/imprimir?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-file-pdf', 'color' => '#dc2626'],
                    ['label' => 'Ver Detalle', 'url' => '/Cycsa/publico/cotizaciones/detalle?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-eye', 'color' => '#103487'],
                ],
            ];
        }

        return [
            'estados_filtro' => ['Borrador', 'En Revision', 'Observada', 'Aprobada Internamente', 'Enviada al Cliente', 'Aprobada por Cliente', 'Rechazada por Cliente'],
            'kpis' => [
                ['titulo' => 'Total Cotizaciones', 'valor' => (string)count($rows), 'subtitulo' => 'Registros en el filtro actual', 'icono' => 'fa-solid fa-file-invoice', 'color' => '#103487'],
                ['titulo' => 'Aprobadas por Cliente', 'valor' => (string)$aprobadas, 'subtitulo' => 'C$ ' . number_format($montoAprobado, 2), 'icono' => 'fa-solid fa-circle-check', 'color' => '#15803d'],
                ['titulo' => 'En Proceso / Gestión', 'valor' => (string)$pendientes, 'subtitulo' => 'Borrador, revisión o enviadas', 'icono' => 'fa-solid fa-hourglass-half', 'color' => '#d97706'],
                ['titulo' => 'Monto Total Cotizado', 'valor' => 'C$ ' . number_format($montoTotal, 2), 'subtitulo' => 'Suma acumulada con IVA', 'icono' => 'fa-solid fa-coins', 'color' => '#0369a1'],
            ],
            'columns' => [
                ['key' => 'codigo', 'label' => 'Código', 'type' => 'text'],
                ['key' => 'version', 'label' => 'Versión', 'type' => 'text'],
                ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'date'],
                ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                ['key' => 'proyecto', 'label' => 'Proyecto', 'type' => 'text'],
                ['key' => 'items', 'label' => 'Ensayos', 'type' => 'number'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'money'],
                ['key' => 'descuento', 'label' => 'Descuento', 'type' => 'money'],
                ['key' => 'impuesto', 'label' => 'IVA', 'type' => 'money'],
                ['key' => 'total', 'label' => 'Total', 'type' => 'money'],
                ['key' => 'creador', 'label' => 'Elaborado Por', 'type' => 'text'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 2. VENTAS, FACTURAS Y CXC
    // =========================================================================
    private function reporteVentasFacturas(array $f): array {
        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'COALESCE(cxc.fecha_emision, os.fecha_emision, os.created_at)', $f, 'vta');
        if ($f['estado'] !== '') {
            if ($f['estado'] === 'Pendiente') {
                $where[] = "(cxc.id IS NULL OR cxc.estado = 'Pendiente')";
            } else {
                $where[] = "cxc.estado = :v_est";
                $params['v_est'] = $f['estado'];
            }
        }
        if ($f['q'] !== '') {
            $where[] = "(os.codigo_os LIKE :q1 OR cot.codigo LIKE :q2 OR cli.nombre_razon_social LIKE :q3 OR cxc.factura_numero LIKE :q4)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }

        $sql = "SELECT os.id AS id_os, os.codigo_os, os.estado AS estado_os, os.fecha_emision AS fecha_os,
                       cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.total AS cot_total, cot.nombre_proyecto, cot.condicion_pago,
                       cli.nombre_razon_social AS cliente_nombre,
                       cxc.id AS cxc_id, cxc.factura_numero, cxc.monto AS cxc_monto, cxc.saldo, cxc.estado AS estado_pago,
                       cxc.fecha_emision AS fecha_factura, cxc.fecha_vencimiento
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON COALESCE(os.id_cliente_factura, cot.id_cliente_factura, cot.id_cliente) = cli.id
                LEFT JOIN cuentas_por_cobrar cxc ON cxc.factura_numero = CONCAT('FAC-', cot.codigo)";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY os.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalFacturado = 0.0;
        $totalSaldo = 0.0;
        $pagadas = 0;
        $rows = [];

        foreach ($raw as $r) {
            $monto = (float)($r['cxc_monto'] ?? $r['cot_total'] ?? 0);
            $saldo = $r['cxc_id'] ? (float)($r['saldo'] ?? 0) : $monto;
            $cobrado = max(0.0, $monto - $saldo);
            $estPago = $r['estado_pago'] ?: 'Pendiente';
            $facNum = $r['factura_numero'] ?: ('FAC-' . $r['cot_codigo']);

            $totalFacturado += $monto;
            $totalSaldo += $saldo;
            if ($estPago === 'Pagado' || $saldo <= 0.01) {
                $pagadas++;
            }

            $rows[] = [
                'factura_numero' => $facNum,
                'codigo_os' => $r['codigo_os'],
                'cotizacion' => $r['cot_codigo'],
                'cliente' => $r['cliente_nombre'],
                'proyecto' => $r['nombre_proyecto'] ?: '—',
                'fecha_emision' => !empty($r['fecha_factura']) ? $r['fecha_factura'] : (!empty($r['fecha_os']) ? $r['fecha_os'] : ''),
                'condicion_pago' => $r['condicion_pago'] ?? '—',
                'monto_factura' => round($monto, 2),
                'cobrado' => round($cobrado, 2),
                'saldo' => round($saldo, 2),
                'estado_pago' => $estPago,
                '_pdfs' => [
                    ['label' => 'Factura PDF', 'url' => '/Cycsa/publico/ventas/imprimir-factura?id_os=' . (int)$r['id_os'], 'icon' => 'fa-solid fa-file-pdf', 'color' => '#dc2626'],
                    ['label' => 'O/S PDF', 'url' => '/Cycsa/publico/ordenes-servicio/descargar-pdf?id=' . (int)$r['id_os'], 'icon' => 'fa-solid fa-file-contract', 'color' => '#103487'],
                    ['label' => 'Cotización PDF', 'url' => '/Cycsa/publico/cotizaciones/imprimir?id=' . (int)$r['id_cotizacion'], 'icon' => 'fa-solid fa-file-invoice-dollar', 'color' => '#d97706'],
                ],
            ];
        }

        $totalCobrado = max(0.0, $totalFacturado - $totalSaldo);
        return [
            'estados_filtro' => ['Pendiente', 'Parcial', 'Pagado', 'Vencido'],
            'kpis' => [
                ['titulo' => 'Total Facturas / O/S', 'valor' => (string)count($rows), 'subtitulo' => "{$pagadas} pagadas al 100%", 'icono' => 'fa-solid fa-receipt', 'color' => '#103487'],
                ['titulo' => 'Monto Total Facturable', 'valor' => 'C$ ' . number_format($totalFacturado, 2), 'subtitulo' => 'Incluye O/S por facturar', 'icono' => 'fa-solid fa-file-invoice-dollar', 'color' => '#0369a1'],
                ['titulo' => 'Total Cobrado', 'valor' => 'C$ ' . number_format($totalCobrado, 2), 'subtitulo' => 'Ingresos recaudados', 'icono' => 'fa-solid fa-circle-check', 'color' => '#15803d'],
                ['titulo' => 'Saldo Pendiente CxC', 'valor' => 'C$ ' . number_format($totalSaldo, 2), 'subtitulo' => 'Por recuperar', 'icono' => 'fa-solid fa-clock', 'color' => '#dc2626'],
            ],
            'columns' => [
                ['key' => 'factura_numero', 'label' => 'Nº Factura', 'type' => 'text'],
                ['key' => 'codigo_os', 'label' => 'O/S', 'type' => 'text'],
                ['key' => 'cotizacion', 'label' => 'Cotización', 'type' => 'text'],
                ['key' => 'cliente', 'label' => 'Cliente Factura', 'type' => 'text'],
                ['key' => 'proyecto', 'label' => 'Proyecto', 'type' => 'text'],
                ['key' => 'fecha_emision', 'label' => 'Fecha', 'type' => 'date'],
                ['key' => 'condicion_pago', 'label' => 'Condición', 'type' => 'text'],
                ['key' => 'monto_factura', 'label' => 'Monto', 'type' => 'money'],
                ['key' => 'cobrado', 'label' => 'Cobrado', 'type' => 'money'],
                ['key' => 'saldo', 'label' => 'Saldo', 'type' => 'money'],
                ['key' => 'estado_pago', 'label' => 'Estado Pago', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 3. CLIENTES & CATÁLOGO DE PRODUCTOS
    // =========================================================================
    private function reporteClientesYCatalogo(array $f): array {
        $sub = $f['subvista'] === 'productos' ? 'productos' : 'clientes';
        $subvistas = [
            'clientes' => 'Cartera de Clientes',
            'productos' => 'Catálogo de Ensayos y Tarifas',
        ];

        $totalClientes = $this->contarSeguro("SELECT COUNT(*) FROM clientes");
        $clientesConOS = $this->contarSeguro("SELECT COUNT(DISTINCT id_cliente) FROM ordenes_servicio");
        $totalProductos = $this->contarSeguro("SELECT COUNT(*) FROM productos WHERE activo = 1");
        $conFormato = $this->contarSeguro("SELECT COUNT(*) FROM productos WHERE activo = 1 AND formato_id IS NOT NULL");

        $kpis = [
            ['titulo' => 'Clientes Registrados', 'valor' => (string)$totalClientes, 'subtitulo' => "{$clientesConOS} con Órdenes de Servicio", 'icono' => 'fa-solid fa-users', 'color' => '#103487'],
            ['titulo' => 'Clientes Activos en O/S', 'valor' => (string)$clientesConOS, 'subtitulo' => 'Con actividad operativa', 'icono' => 'fa-solid fa-building-user', 'color' => '#15803d'],
            ['titulo' => 'Ensayos en Catálogo', 'valor' => (string)$totalProductos, 'subtitulo' => 'Productos activos', 'icono' => 'fa-solid fa-flask-vial', 'color' => '#0284c7'],
            ['titulo' => 'Con Formato RT-FM-22', 'valor' => (string)$conFormato, 'subtitulo' => 'Vinculados a hoja técnica', 'icono' => 'fa-solid fa-file-shield', 'color' => '#7c3aed'],
        ];

        if ($sub === 'productos') {
            $where = ['p.activo = 1'];
            $params = [];
            if ($f['q'] !== '') {
                $where[] = "(p.codigo_servicio LIKE :q1 OR p.nombre_comercial LIKE :q2 OR p.norma_astm LIKE :q3 OR fe.nombre LIKE :q4)";
                $term = '%' . $f['q'] . '%';
                $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
            }
            $sql = "SELECT p.id, p.no_item, p.codigo_servicio, p.nombre_comercial, p.ensayo_servicio, p.norma_astm,
                           p.tipo_muestra, p.unidad_medida, p.precio, fe.codigo_formato, fe.nombre AS formato_nombre
                    FROM productos p
                    LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY CAST(p.no_item AS UNSIGNED) ASC, p.id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'no_item' => $r['no_item'] ?: (string)$r['id'],
                    'codigo_servicio' => $r['codigo_servicio'] ?: '—',
                    'nombre' => $r['nombre_comercial'] ?: ($r['ensayo_servicio'] ?: '—'),
                    'norma' => $r['norma_astm'] ?: '—',
                    'tipo_muestra' => $r['tipo_muestra'] ?: '—',
                    'formato' => trim(($r['codigo_formato'] ?? '') . ' ' . ($r['formato_nombre'] ?? '')) ?: 'Sin asignar',
                    'unidad' => $r['unidad_medida'] ?: 'Ensayo',
                    'precio' => round((float)($r['precio'] ?? 0), 2),
                    '_pdfs' => [
                        ['label' => 'Editar Producto', 'url' => '/Cycsa/publico/productos/editar?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-pen', 'color' => '#0284c7'],
                    ],
                ];
            }

            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'no_item', 'label' => 'Ítem', 'type' => 'text'],
                    ['key' => 'codigo_servicio', 'label' => 'Código', 'type' => 'text'],
                    ['key' => 'nombre', 'label' => 'Ensayo / Servicio', 'type' => 'text'],
                    ['key' => 'norma', 'label' => 'Norma ASTM/AASHTO', 'type' => 'text'],
                    ['key' => 'tipo_muestra', 'label' => 'Tipo Muestra', 'type' => 'text'],
                    ['key' => 'formato', 'label' => 'Formato RT-FM-22', 'type' => 'text'],
                    ['key' => 'unidad', 'label' => 'Unidad', 'type' => 'text'],
                    ['key' => 'precio', 'label' => 'Tarifa Base', 'type' => 'money'],
                ],
                'rows' => $rows,
            ];
        }

        $where = [];
        $params = [];
        if ($f['q'] !== '') {
            $where[] = "(c.nombre_razon_social LIKE :q1 OR c.numero_ruc LIKE :q2 OR c.email LIKE :q3 OR c.codigo_cliente LIKE :q4)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }
        $sql = "SELECT c.id, c.codigo_cliente, c.nombre_razon_social, c.numero_ruc, c.email, c.telefono, c.clasificacion, c.activo,
                       (SELECT COUNT(*) FROM cotizaciones q WHERE q.id_cliente = c.id) AS total_cots,
                       (SELECT COUNT(*) FROM ordenes_servicio o WHERE o.id_cliente = c.id) AS total_os,
                       (SELECT COUNT(*) FROM hojas_solicitud hs JOIN ordenes_servicio o2 ON hs.id_os = o2.id WHERE o2.id_cliente = c.id) AS total_hojas,
                       (SELECT COALESCE(SUM(q2.total), 0) FROM cotizaciones q2 WHERE q2.id_cliente = c.id AND q2.estado = 'Aprobada por Cliente') AS monto_aprobado
                FROM clientes c";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($raw as $r) {
            $rows[] = [
                'codigo_cliente' => $r['codigo_cliente'] ?: ('CLI-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT)),
                'nombre' => $r['nombre_razon_social'],
                'ruc' => $r['numero_ruc'] ?: '—',
                'telefono' => $r['telefono'] ?: '—',
                'email' => $r['email'] ?: '—',
                'cotizaciones' => (int)($r['total_cots'] ?? 0),
                'ordenes' => (int)($r['total_os'] ?? 0),
                'hojas_rt13' => (int)($r['total_hojas'] ?? 0),
                'monto_aprobado' => round((float)($r['monto_aprobado'] ?? 0), 2),
                'estado' => !empty($r['activo']) ? 'Activo' : 'Inactivo',
                '_pdfs' => [
                    ['label' => 'Ficha Cliente', 'url' => '/Cycsa/publico/clientes/editar?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-address-card', 'color' => '#2563eb'],
                ],
            ];
        }

        return [
            'subvistas' => $subvistas,
            'kpis' => $kpis,
            'columns' => [
                ['key' => 'codigo_cliente', 'label' => 'Código', 'type' => 'text'],
                ['key' => 'nombre', 'label' => 'Razón Social / Cliente', 'type' => 'text'],
                ['key' => 'ruc', 'label' => 'RUC / Cédula', 'type' => 'text'],
                ['key' => 'telefono', 'label' => 'Teléfono', 'type' => 'text'],
                ['key' => 'email', 'label' => 'Correo', 'type' => 'text'],
                ['key' => 'cotizaciones', 'label' => 'Cotiz.', 'type' => 'number'],
                ['key' => 'ordenes', 'label' => 'O/S', 'type' => 'number'],
                ['key' => 'hojas_rt13', 'label' => 'Hojas RT-13', 'type' => 'number'],
                ['key' => 'monto_aprobado', 'label' => 'Monto Aprobado', 'type' => 'money'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 4. ÓRDENES DE SERVICIO (RG-FM-39 V1)
    // =========================================================================
    private function reporteOrdenesServicio(array $f): array {
        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'COALESCE(os.fecha_emision, os.created_at)', $f, 'os');
        if ($f['estado'] !== '') {
            $where[] = "os.estado = :os_est";
            $params['os_est'] = $f['estado'];
        }
        if ($f['q'] !== '') {
            $where[] = "(os.codigo_os LIKE :q1 OR cot.codigo LIKE :q2 OR cli.nombre_razon_social LIKE :q3 OR os.nombre_proyecto LIKE :q4)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }

        $sql = "SELECT os.id, os.codigo_os, os.estado, os.tipo_contrato, os.es_multimes, os.duracion_meses,
                       os.fecha_inicio, os.fecha_fin, os.periodicidad, os.requiere_muestreo, os.fecha_emision, os.created_at,
                       os.nombre_proyecto, os.condicion_comercial,
                       cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.total AS cot_total,
                       cli.nombre_razon_social AS cliente_nombre,
                       (SELECT COUNT(*) FROM hojas_solicitud hs WHERE hs.id_os = os.id) AS total_rt13,
                       (SELECT COUNT(*) FROM programacion_muestreo pm WHERE pm.id_orden_servicio = os.id) AS tiene_fm40b
                FROM ordenes_servicio os
                LEFT JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                LEFT JOIN clientes cli ON os.id_cliente = cli.id";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY os.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conMuestreo = 0;
        $conHojas = 0;
        $finalizadas = 0;
        $rows = [];

        foreach ($raw as $r) {
            if (!empty($r['requiere_muestreo'])) $conMuestreo++;
            if ((int)($r['total_rt13'] ?? 0) > 0) $conHojas++;
            if (in_array($r['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true)) $finalizadas++;

            $pdfs = [
                ['label' => 'O/S PDF (RG-FM-39)', 'url' => '/Cycsa/publico/ordenes-servicio/descargar-pdf?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-file-pdf', 'color' => '#dc2626'],
            ];
            if (!empty($r['id_cotizacion'])) {
                $pdfs[] = ['label' => 'Cotización PDF', 'url' => '/Cycsa/publico/cotizaciones/imprimir?id=' . (int)$r['id_cotizacion'], 'icon' => 'fa-solid fa-file-invoice-dollar', 'color' => '#d97706'];
            }
            if ((int)($r['tiene_fm40b'] ?? 0) > 0) {
                $pdfs[] = ['label' => 'FM-40 B', 'url' => '/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-truck-pickup', 'color' => '#0284c7'];
            }

            $vigenciaInfo = function_exists('calcularVigenciaContratoOS') ? calcularVigenciaContratoOS($r) : [];
            $vigenciaTexto = !empty($vigenciaInfo['es_contrato_largo'])
                ? ($vigenciaInfo['etiqueta_corta'] ?? 'Contrato Largo')
                : 'Puntual';

            $rows[] = [
                'codigo_os' => $r['codigo_os'],
                'cotizacion' => $r['cot_codigo'] ?? '—',
                'fecha' => !empty($r['fecha_emision']) ? $r['fecha_emision'] : (!empty($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'])) : ''),
                'cliente' => $r['cliente_nombre'] ?? '—',
                'proyecto' => $r['nombre_proyecto'] ?: '—',
                'tipo_ingreso' => !empty($r['requiere_muestreo']) ? 'Muestreo Campo (MC)' : 'Ingreso Directo (MS)',
                'vigencia' => $vigenciaTexto,
                'condicion' => ucfirst((string)($r['condicion_comercial'] ?? 'contado')),
                'hojas_rt13' => (int)($r['total_rt13'] ?? 0),
                'monto' => round((float)($r['cot_total'] ?? 0), 2),
                'estado' => $r['estado'] ?? '—',
                '_pdfs' => $pdfs,
            ];
        }

        $estados = $this->db->query("SELECT DISTINCT estado FROM ordenes_servicio WHERE estado IS NOT NULL AND estado <> '' ORDER BY estado")->fetchAll(PDO::FETCH_COLUMN);

        return [
            'estados_filtro' => $estados,
            'kpis' => [
                ['titulo' => 'Total Órdenes (O/S)', 'valor' => (string)count($rows), 'subtitulo' => 'Órdenes registradas', 'icono' => 'fa-solid fa-file-contract', 'color' => '#103487'],
                ['titulo' => 'Con Muestreo en Campo', 'valor' => (string)$conMuestreo, 'subtitulo' => 'Requieren brigada MC', 'icono' => 'fa-solid fa-truck-pickup', 'color' => '#0284c7'],
                ['titulo' => 'Con Hojas RT-FM-13', 'valor' => (string)$conHojas, 'subtitulo' => 'Cadenas de custodia emitidas', 'icono' => 'fa-solid fa-file-lines', 'color' => '#ea580c'],
                ['titulo' => 'Cerradas / Finalizadas', 'valor' => (string)$finalizadas, 'subtitulo' => 'Ciclo operativo completado', 'icono' => 'fa-solid fa-circle-check', 'color' => '#15803d'],
            ],
            'columns' => [
                ['key' => 'codigo_os', 'label' => 'Código O/S', 'type' => 'text'],
                ['key' => 'cotizacion', 'label' => 'Cotización', 'type' => 'text'],
                ['key' => 'fecha', 'label' => 'Fecha Emisión', 'type' => 'date'],
                ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                ['key' => 'proyecto', 'label' => 'Proyecto', 'type' => 'text'],
                ['key' => 'tipo_ingreso', 'label' => 'Modalidad', 'type' => 'text'],
                ['key' => 'vigencia', 'label' => 'Período / Vigencia', 'type' => 'text'],
                ['key' => 'condicion', 'label' => 'Condición', 'type' => 'text'],
                ['key' => 'hojas_rt13', 'label' => 'RT-FM-13', 'type' => 'number'],
                ['key' => 'monto', 'label' => 'Monto O/S', 'type' => 'money'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 5. HOJAS RT-FM-13 VINCULADAS A CLIENTES Y O/S
    // =========================================================================
    private function reporteHojasRtFm13(array $f): array {
        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'COALESCE(hs.fecha_hora_toma_muestra, hs.fecha_creacion)', $f, 'hs');
        if ($f['q'] !== '') {
            $where[] = "(hs.numero_registro LIKE :q1 OR os.codigo_os LIKE :q2 OR cot.codigo LIKE :q3 OR cli.nombre_razon_social LIKE :q4 OR cd.descripcion_ensayo LIKE :q5 OR hs.muestras_json LIKE :q6)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term, 'q5' => $term, 'q6' => $term];
        }

        $sql = "SELECT hs.id, hs.id_os, hs.id_detalle_cotizacion, hs.id_producto, hs.numero_registro, hs.codigo_documento,
                       hs.fecha_creacion, hs.fecha_hora_toma_muestra, hs.fecha_hora_llegada_laboratorio,
                       hs.nombre_empresa_o_cliente, hs.procedencia_punto_muestreo, hs.nombre_persona_toma_muestra,
                       hs.muestras_json,
                       os.codigo_os, os.estado AS estado_os, os.nombre_proyecto,
                       cot.id AS id_cotizacion, cot.codigo AS cot_codigo,
                       COALESCE(cli_inf.nombre_razon_social, cli.nombre_razon_social, hs.nombre_empresa_o_cliente) AS cliente_vinculado,
                       COALESCE(cd.descripcion_ensayo, p.nombre_comercial, 'Ensayo General') AS ensayo_nombre,
                       (SELECT COUNT(*) FROM hojas_solicitud_detalles hsd WHERE hsd.id_hoja_solicitud = hs.id) AS det_total,
                       (SELECT COUNT(*) FROM hojas_solicitud_detalles hsd2 WHERE hsd2.id_hoja_solicitud = hs.id AND hsd2.datos_recepcion_json IS NOT NULL AND hsd2.datos_recepcion_json <> '') AS det_con_recepcion
                FROM hojas_solicitud hs
                JOIN ordenes_servicio os ON hs.id_os = os.id
                LEFT JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                LEFT JOIN clientes cli ON os.id_cliente = cli.id
                LEFT JOIN clientes cli_inf ON cli_inf.id = COALESCE(os.id_cliente_informe, cot.id_cliente_informe)
                LEFT JOIN cotizacion_detalles cd ON hs.id_detalle_cotizacion = cd.id
                LEFT JOIN productos p ON COALESCE(hs.id_producto, cd.id_producto) = p.id";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY hs.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalMuestras = 0;
        $recepcionCompleta = 0;
        $clientesUnicos = [];
        $rows = [];

        foreach ($raw as $r) {
            $mArr = json_decode((string)($r['muestras_json'] ?? '[]'), true) ?: [];
            $codigosMuestras = [];
            foreach ($mArr as $mItem) {
                $cod = trim((string)($mItem['nombre_muestra'] ?? ($mItem['codigo_muestra'] ?? '')));
                if ($cod !== '') $codigosMuestras[] = $cod;
            }
            $cantM = max((int)($r['det_total'] ?? 0), count($mArr));
            $conRec = (int)($r['det_con_recepcion'] ?? 0);
            $totalMuestras += $cantM;

            $cliKey = mb_strtoupper(trim((string)($r['cliente_vinculado'] ?? '')));
            if ($cliKey !== '') $clientesUnicos[$cliKey] = true;

            $estadoRec = ($cantM > 0 && $conRec >= $cantM)
                ? "Completa ({$conRec}/{$cantM})"
                : ($conRec > 0 ? "Parcial ({$conRec}/{$cantM})" : "Pendiente (0/{$cantM})");

            if ($cantM > 0 && $conRec >= $cantM) {
                $recepcionCompleta++;
            }

            if ($f['estado'] !== '') {
                if ($f['estado'] === 'Recepcion Completa' && !($cantM > 0 && $conRec >= $cantM)) continue;
                if ($f['estado'] === 'Recepcion Pendiente' && ($cantM > 0 && $conRec >= $cantM)) continue;
            }

            $pdfs = [
                ['label' => 'RT-FM-13 PDF', 'url' => '/Cycsa/publico/hojas-servicio/descargar?id_os=' . (int)$r['id_os'] . '&id_hoja=' . (int)$r['id'], 'icon' => 'fa-solid fa-file-pdf', 'color' => '#ea580c'],
                ['label' => 'O/S PDF', 'url' => '/Cycsa/publico/ordenes-servicio/descargar-pdf?id=' . (int)$r['id_os'], 'icon' => 'fa-solid fa-file-contract', 'color' => '#103487'],
            ];
            if (!empty($r['id_cotizacion'])) {
                $pdfs[] = ['label' => 'Cotización PDF', 'url' => '/Cycsa/publico/cotizaciones/imprimir?id=' . (int)$r['id_cotizacion'], 'icon' => 'fa-solid fa-file-invoice-dollar', 'color' => '#d97706'];
            }

            $rows[] = [
                'numero_registro' => $r['numero_registro'] ?: ('#' . $r['id']),
                'cliente' => $r['cliente_vinculado'] ?: '—',
                'proyecto' => $r['nombre_proyecto'] ?: '—',
                'codigo_os' => $r['codigo_os'],
                'cotizacion' => $r['cot_codigo'] ?? '—',
                'ensayo' => $r['ensayo_nombre'],
                'cant_muestras' => $cantM,
                'codigos_muestras' => !empty($codigosMuestras) ? implode(', ', $codigosMuestras) : '—',
                'recepcion' => $estadoRec,
                'fecha_toma' => !empty($r['fecha_hora_toma_muestra']) ? date('Y-m-d', strtotime($r['fecha_hora_toma_muestra'])) : (!empty($r['fecha_creacion']) ? date('Y-m-d', strtotime($r['fecha_creacion'])) : ''),
                'estado_os' => $r['estado_os'],
                '_pdfs' => $pdfs,
            ];
        }

        return [
            'estados_filtro' => ['Recepcion Completa', 'Recepcion Pendiente'],
            'kpis' => [
                ['titulo' => 'Hojas RT-FM-13 Emitidas', 'valor' => (string)count($rows), 'subtitulo' => 'Consecutivo único por producto', 'icono' => 'fa-solid fa-file-lines', 'color' => '#ea580c'],
                ['titulo' => 'Clientes Vinculados', 'valor' => (string)count($clientesUnicos), 'subtitulo' => 'Clientes con hojas activas', 'icono' => 'fa-solid fa-building-user', 'color' => '#103487'],
                ['titulo' => 'Muestras Totales', 'valor' => (string)$totalMuestras, 'subtitulo' => 'Especímenes MC/MS declarados', 'icono' => 'fa-solid fa-cubes', 'color' => '#0284c7'],
                ['titulo' => 'Datos Recepción Completos', 'valor' => (string)$recepcionCompleta, 'subtitulo' => 'Hojas listas para Operaciones', 'icono' => 'fa-solid fa-circle-check', 'color' => '#15803d'],
            ],
            'columns' => [
                ['key' => 'numero_registro', 'label' => 'Reg. RT-FM-13', 'type' => 'text'],
                ['key' => 'cliente', 'label' => 'Cliente Vinculado', 'type' => 'text'],
                ['key' => 'proyecto', 'label' => 'Proyecto / Obra', 'type' => 'text'],
                ['key' => 'codigo_os', 'label' => 'O/S', 'type' => 'text'],
                ['key' => 'cotizacion', 'label' => 'Cotización', 'type' => 'text'],
                ['key' => 'ensayo', 'label' => 'Producto / Ensayo', 'type' => 'text'],
                ['key' => 'cant_muestras', 'label' => 'Muestras', 'type' => 'number'],
                ['key' => 'codigos_muestras', 'label' => 'Códigos MC/MS', 'type' => 'text'],
                ['key' => 'recepcion', 'label' => 'Datos Recepción', 'type' => 'badge'],
                ['key' => 'fecha_toma', 'label' => 'Fecha Toma', 'type' => 'date'],
                ['key' => 'estado_os', 'label' => 'Estado O/S', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 6. HOJAS DE MUESTREO CYCSA-RT-FM-40 B
    // =========================================================================
    private function reporteHojasFm40B(array $f): array {
        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'COALESCE(pm.fecha_ida, pm.created_at)', $f, 'pm');
        if ($f['estado'] !== '') {
            $where[] = "pm.estado_muestreo = :pm_est";
            $params['pm_est'] = $f['estado'];
        }
        if ($f['q'] !== '') {
            $where[] = "(os.codigo_os LIKE :q1 OR cli.nombre_razon_social LIKE :q2 OR pm.lugar_muestreo LIKE :q3 OR t.nombre LIKE :q4)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }

        $sql = "SELECT pm.id, pm.id_orden_servicio, pm.id_hoja_solicitud, pm.fecha_ida, pm.fecha_llegada, pm.fecha_finalizacion,
                       pm.estado_muestreo, pm.lugar_muestreo, pm.cantidad_muestras_est, pm.num_muestreadores, pm.checklist_json,
                       hs.numero_registro AS reg_rt13,
                       os.codigo_os, os.nombre_proyecto, os.estado AS estado_os,
                       cli.nombre_razon_social AS cliente_nombre,
                       t.nombre AS tecnico_nombre,
                       CONCAT(COALESCE(v.placa, ''), ' ', COALESCE(v.marca, ''), ' ', COALESCE(v.modelo, '')) AS vehiculo_desc
                FROM programacion_muestreo pm
                JOIN ordenes_servicio os ON pm.id_orden_servicio = os.id
                LEFT JOIN hojas_solicitud hs ON pm.id_hoja_solicitud = hs.id
                LEFT JOIN clientes cli ON os.id_cliente = cli.id
                LEFT JOIN tecnicos t ON pm.id_tecnico = t.id
                LEFT JOIN vehiculos v ON pm.id_vehiculo = v.id";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY pm.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $finalizadas = 0;
        $enCampo = 0;
        $programadas = 0;
        $rows = [];

        foreach ($raw as $r) {
            $est = $r['estado_muestreo'] ?? 'Programado';
            if ($est === 'Finalizado') $finalizadas++;
            elseif ($est === 'En Campo') $enCampo++;
            else $programadas++;

            $chk = json_decode((string)($r['checklist_json'] ?? '{}'), true) ?: [];
            $itemsMarcados = 0;
            foreach ($chk as $val) {
                if (!empty($val) && $val !== '0') $itemsMarcados++;
            }

            $idHojaPm = (int)($r['id_hoja_solicitud'] ?? 0);
            $pdfsPm = [
                ['label' => 'Hoja RT-FM-40 B', 'url' => '/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=' . (int)$r['id_orden_servicio'] . ($idHojaPm > 0 ? '&id_hoja=' . $idHojaPm : ''), 'icon' => 'fa-solid fa-file-pdf', 'color' => '#0284c7'],
                ['label' => 'O/S PDF', 'url' => '/Cycsa/publico/ordenes-servicio/descargar-pdf?id=' . (int)$r['id_orden_servicio'], 'icon' => 'fa-solid fa-file-contract', 'color' => '#103487'],
            ];
            if ($idHojaPm > 0) {
                $pdfsPm[] = ['label' => 'RT-FM-13 PDF', 'url' => '/Cycsa/publico/hojas-servicio/descargar?id_os=' . (int)$r['id_orden_servicio'] . '&id_hoja=' . $idHojaPm, 'icon' => 'fa-solid fa-file-lines', 'color' => '#ea580c'];
            }

            $rows[] = [
                'codigo_os' => $r['codigo_os'],
                'reg_rt13' => $r['reg_rt13'] ?: 'General O/S',
                'cliente' => $r['cliente_nombre'] ?? '—',
                'proyecto' => $r['nombre_proyecto'] ?: '—',
                'lugar' => $r['lugar_muestreo'] ?: '—',
                'tecnico' => $r['tecnico_nombre'] ?: 'Sin asignar',
                'vehiculo' => trim((string)($r['vehiculo_desc'] ?? '')) ?: '—',
                'fecha_ida' => !empty($r['fecha_ida']) ? date('Y-m-d H:i', strtotime($r['fecha_ida'])) : '—',
                'fecha_fin' => !empty($r['fecha_finalizacion']) ? date('Y-m-d H:i', strtotime($r['fecha_finalizacion'])) : 'Pendiente',
                'muestras_est' => $r['cantidad_muestras_est'] ?: '—',
                'checklist_checks' => $itemsMarcados,
                'estado_muestreo' => $est,
                '_pdfs' => $pdfsPm,
            ];
        }

        return [
            'estados_filtro' => ['Programado', 'En Campo', 'Finalizado'],
            'kpis' => [
                ['titulo' => 'Total Hojas RT-FM-40 B', 'valor' => (string)count($rows), 'subtitulo' => 'Listas de chequeo campo', 'icono' => 'fa-solid fa-truck-pickup', 'color' => '#0284c7'],
                ['titulo' => 'Finalizadas', 'valor' => (string)$finalizadas, 'subtitulo' => 'Muestreo completado', 'icono' => 'fa-solid fa-circle-check', 'color' => '#15803d'],
                ['titulo' => 'En Campo', 'valor' => (string)$enCampo, 'subtitulo' => 'Brigadas activas en obra', 'icono' => 'fa-solid fa-helmet-safety', 'color' => '#d97706'],
                ['titulo' => 'Programadas', 'valor' => (string)$programadas, 'subtitulo' => 'Por ejecutar', 'icono' => 'fa-solid fa-calendar-check', 'color' => '#4f46e5'],
            ],
            'columns' => [
                ['key' => 'codigo_os', 'label' => 'O/S', 'type' => 'text'],
                ['key' => 'reg_rt13', 'label' => 'RT-FM-13', 'type' => 'text'],
                ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                ['key' => 'proyecto', 'label' => 'Proyecto', 'type' => 'text'],
                ['key' => 'lugar', 'label' => 'Lugar Muestreo', 'type' => 'text'],
                ['key' => 'tecnico', 'label' => 'Técnico Asignado', 'type' => 'text'],
                ['key' => 'vehiculo', 'label' => 'Vehículo', 'type' => 'text'],
                ['key' => 'fecha_ida', 'label' => 'Salida', 'type' => 'text'],
                ['key' => 'fecha_fin', 'label' => 'Finalización', 'type' => 'text'],
                ['key' => 'muestras_est', 'label' => 'Muestras Est.', 'type' => 'text'],
                ['key' => 'checklist_checks', 'label' => 'Checks 40 B', 'type' => 'number'],
                ['key' => 'estado_muestreo', 'label' => 'Estado', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 7. LABORATORIO: HOJAS RT-FM-60, LOTES, ROTURAS E INFORMES
    // =========================================================================
    private function reporteLaboratorioRtFm60(array $f): array {
        $sub = $f['subvista'] === 'informes' ? 'informes' : 'muestras';
        $subvistas = [
            'muestras' => 'Hojas RT-FM-60 & Probetas Recibidas',
            'informes' => 'Informes de Rotura & Consolidados',
        ];

        $totalMuestras = $this->contarSeguro("SELECT COUNT(*) FROM recepcion_muestras");
        $totalLotes = $this->contarSeguro("SELECT COUNT(*) FROM lotes_muestras");
        $totalRoturas = $this->contarSeguro("SELECT COUNT(*) FROM roturas");
        $totalInformes = $this->contarSeguro("SELECT COUNT(*) FROM informes_rotura") + $this->contarSeguro("SELECT COUNT(*) FROM informes_control");

        $kpis = [
            ['titulo' => 'Probetas / Hojas RT-FM-60', 'valor' => (string)$totalMuestras, 'subtitulo' => "{$totalLotes} lotes registrados", 'icono' => 'fa-solid fa-flask', 'color' => '#7c3aed'],
            ['titulo' => 'Lotes en Laboratorio', 'valor' => (string)$totalLotes, 'subtitulo' => 'Agrupados por RT-FM-13', 'icono' => 'fa-solid fa-layer-group', 'color' => '#0284c7'],
            ['titulo' => 'Roturas Ejecutadas', 'valor' => (string)$totalRoturas, 'subtitulo' => 'Ensayos físicos registrados', 'icono' => 'fa-solid fa-hammer', 'color' => '#d97706'],
            ['titulo' => 'Informes Emitidos', 'valor' => (string)$totalInformes, 'subtitulo' => 'Parciales + Consolidados', 'icono' => 'fa-solid fa-file-shield', 'color' => '#15803d'],
        ];

        if ($sub === 'informes') {
            $where = [];
            $params = [];
            $this->aplicarFiltroFechas($where, $params, 'ir.created_at', $f, 'ir');
            if ($f['estado'] !== '') {
                $where[] = "ir.estado = :ir_est";
                $params['ir_est'] = $f['estado'];
            }
            if ($f['q'] !== '') {
                $where[] = "(ir.numero LIKE :q1 OR rm.codigo_muestra LIKE :q2 OR os.codigo_os LIKE :q3 OR cli.nombre_razon_social LIKE :q4)";
                $term = '%' . $f['q'] . '%';
                $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
            }

            $sql = "SELECT ir.id, ir.tipo, ir.numero, ir.version, ir.estado, ir.sha256, ir.created_at,
                           rm.id AS muestra_id, rm.codigo_muestra, rm.id_hoja, rm.id_os,
                           hs.numero_registro AS reg_rt13, os.codigo_os,
                           COALESCE(cli_inf.nombre_razon_social, cli.nombre_razon_social) AS cliente_nombre
                    FROM informes_rotura ir
                    LEFT JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
                    LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                    LEFT JOIN ordenes_servicio os ON rm.id_os = os.id
                    LEFT JOIN clientes cli ON os.id_cliente = cli.id
                    LEFT JOIN clientes cli_inf ON cli_inf.id = os.id_cliente_informe";
            if ($where !== []) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY ir.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rows = [];
            foreach ($raw as $r) {
                $pdfs = [
                    ['label' => 'Ver Informe PDF', 'url' => '/Cycsa/publico/laboratorio/ver-informe?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-file-pdf', 'color' => '#dc2626'],
                ];
                if (!empty($r['id_os'])) {
                    $pdfs[] = ['label' => 'Hoja RT-FM-60', 'url' => '/Cycsa/publico/laboratorio/imprimir-solicitud?id_os=' . (int)$r['id_os'] . '&id_hoja=' . (int)($r['id_hoja'] ?? 0), 'icon' => 'fa-solid fa-print', 'color' => '#7c3aed'];
                }
                $rows[] = [
                    'numero' => $r['numero'] ?: ('INF-' . $r['id']),
                    'tipo' => ucfirst((string)($r['tipo'] ?? 'parcial')),
                    'version' => 'v' . (int)($r['version'] ?? 1),
                    'muestra' => $r['codigo_muestra'] ?? '—',
                    'reg_rt13' => $r['reg_rt13'] ?? '—',
                    'codigo_os' => $r['codigo_os'] ?? '—',
                    'cliente' => $r['cliente_nombre'] ?? '—',
                    'fecha' => !empty($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'])) : '',
                    'sha256' => $r['sha256'] ? substr((string)$r['sha256'], 0, 12) . '...' : 'Pendiente',
                    'estado' => $r['estado'] ?? 'Borrador',
                    '_pdfs' => $pdfs,
                ];
            }

            return [
                'subvistas' => $subvistas,
                'estados_filtro' => ['En Revisión', 'Aprobado', 'Devuelto'],
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'numero', 'label' => 'Código Informe', 'type' => 'text'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'type' => 'text'],
                    ['key' => 'version', 'label' => 'Versión', 'type' => 'text'],
                    ['key' => 'muestra', 'label' => 'Muestra', 'type' => 'text'],
                    ['key' => 'reg_rt13', 'label' => 'RT-FM-13', 'type' => 'text'],
                    ['key' => 'codigo_os', 'label' => 'O/S', 'type' => 'text'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                    ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'date'],
                    ['key' => 'sha256', 'label' => 'Hash SHA-256', 'type' => 'text'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'rm.fecha_recepcion', $f, 'rm');
        if ($f['estado'] !== '') {
            $where[] = "rm.estado = :rm_est";
            $params['rm_est'] = $f['estado'];
        }
        if ($f['q'] !== '') {
            $where[] = "(rm.codigo_muestra LIKE :q1 OR hs.numero_registro LIKE :q2 OR os.codigo_os LIKE :q3 OR cli.nombre_razon_social LIKE :q4 OR lm.nombre_lote LIKE :q5)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term, 'q5' => $term];
        }

        $sql = "SELECT rm.id, rm.id_os, rm.id_hoja, rm.codigo_muestra, rm.tipo_muestra, rm.fecha_recepcion, rm.estado, rm.is_qa_qc,
                       lm.nombre_lote, lm.diseno_resistencia, lm.fecha_moldeo, lm.revenimiento_in, lm.temperatura_c,
                       hs.numero_registro AS reg_rt13,
                       os.codigo_os, os.nombre_proyecto,
                       COALESCE(cli_inf.nombre_razon_social, cli.nombre_razon_social) AS cliente_nombre,
                       esp.id AS especimen_id,
                       (SELECT COUNT(*) FROM roturas rt WHERE rt.especimen_id = esp.id) AS total_roturas,
                       (SELECT COUNT(*) FROM informes_rotura ir WHERE ir.muestra_id = rm.id) AS total_informes
                FROM recepcion_muestras rm
                LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                LEFT JOIN especimenes esp ON esp.muestra_id = rm.id
                LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                LEFT JOIN ordenes_servicio os ON rm.id_os = os.id
                LEFT JOIN clientes cli ON os.id_cliente = cli.id
                LEFT JOIN clientes cli_inf ON cli_inf.id = os.id_cliente_informe";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY rm.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($raw as $r) {
            $pdfs = [
                ['label' => 'Hoja RT-FM-60', 'url' => '/Cycsa/publico/laboratorio/imprimir-solicitud?id_os=' . (int)$r['id_os'] . '&id_hoja=' . (int)($r['id_hoja'] ?? 0), 'icon' => 'fa-solid fa-file-pdf', 'color' => '#7c3aed'],
            ];
            if (!empty($r['id_hoja'])) {
                $pdfs[] = ['label' => 'Ver Lote', 'url' => '/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . (int)$r['id_hoja'], 'icon' => 'fa-solid fa-layer-group', 'color' => '#0284c7'];
            }
            if (!empty($r['especimen_id'])) {
                $pdfs[] = ['label' => 'Etiqueta', 'url' => '/Cycsa/publico/laboratorio/etiqueta-probeta?id=' . (int)$r['especimen_id'], 'icon' => 'fa-solid fa-barcode', 'color' => '#475569'];
            }

            $rows[] = [
                'codigo_muestra' => $r['codigo_muestra'],
                'reg_rt13' => $r['reg_rt13'] ?: '—',
                'codigo_os' => $r['codigo_os'] ?? '—',
                'cliente' => $r['cliente_nombre'] ?? '—',
                'lote' => $r['nombre_lote'] ?: '—',
                'diseno' => $r['diseno_resistencia'] ?: '—',
                'fecha_moldeo' => $r['fecha_moldeo'] ?: '—',
                'fecha_recepcion' => !empty($r['fecha_recepcion']) ? date('Y-m-d', strtotime($r['fecha_recepcion'])) : '',
                'roturas' => (int)($r['total_roturas'] ?? 0),
                'informes' => (int)($r['total_informes'] ?? 0),
                'estado' => $r['estado'] ?? 'Registrado',
                '_pdfs' => $pdfs,
            ];
        }

        return [
            'subvistas' => $subvistas,
            'estados_filtro' => ['Registrado', 'En Laboratorio', 'Finalizado'],
            'kpis' => $kpis,
            'columns' => [
                ['key' => 'codigo_muestra', 'label' => 'Muestra', 'type' => 'text'],
                ['key' => 'reg_rt13', 'label' => 'Reg. RT-FM-13', 'type' => 'text'],
                ['key' => 'codigo_os', 'label' => 'O/S', 'type' => 'text'],
                ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                ['key' => 'lote', 'label' => 'Lote / Descripción', 'type' => 'text'],
                ['key' => 'diseno', 'label' => 'Diseño', 'type' => 'text'],
                ['key' => 'fecha_moldeo', 'label' => 'Moldeo', 'type' => 'date'],
                ['key' => 'fecha_recepcion', 'label' => 'Ingreso Lab (60)', 'type' => 'date'],
                ['key' => 'roturas', 'label' => 'Roturas', 'type' => 'number'],
                ['key' => 'informes', 'label' => 'Informes', 'type' => 'number'],
                ['key' => 'estado', 'label' => 'Estado Lab', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 8. OPERACIONES LIMS: MATRICES TÉCNICAS RT-FM-22 Y HOJAS DE CAMPO HC
    // =========================================================================
    private function reporteOperacionesMatrices(array $f): array {
        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'COALESCE(os.fecha_emision, os.created_at)', $f, 'op');
        if ($f['q'] !== '') {
            $where[] = "(os.codigo_os LIKE :q1 OR cli.nombre_razon_social LIKE :q2 OR cd.descripcion_ensayo LIKE :q3 OR fe.nombre LIKE :q4)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }

        $sql = "SELECT cd.id AS id_detalle, cd.descripcion_ensayo, cd.cantidad, cd.resultados_json,
                       os.id AS id_os, os.codigo_os, os.nombre_proyecto, os.estado AS estado_os, os.fecha_emision, os.created_at,
                       COALESCE(cli_inf.nombre_razon_social, cli.nombre_razon_social) AS cliente_nombre,
                       COALESCE(fe.codigo_formato, 'RT-FM-22') AS codigo_formato,
                       COALESCE(fe.nombre, p.nombre_comercial, '') AS formato_nombre,
                       (SELECT hs.id FROM hojas_solicitud hs WHERE hs.id_os = os.id AND (hs.id_detalle_cotizacion = cd.id OR hs.id_detalle_cotizacion IS NULL) ORDER BY (hs.id_detalle_cotizacion = cd.id) DESC, hs.id ASC LIMIT 1) AS id_hoja,
                       (SELECT hs2.numero_registro FROM hojas_solicitud hs2 WHERE hs2.id_os = os.id AND (hs2.id_detalle_cotizacion = cd.id OR hs2.id_detalle_cotizacion IS NULL) ORDER BY (hs2.id_detalle_cotizacion = cd.id) DESC, hs2.id ASC LIMIT 1) AS reg_rt13
                FROM cotizacion_detalles cd
                JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
                LEFT JOIN clientes cli ON os.id_cliente = cli.id
                LEFT JOIN clientes cli_inf ON cli_inf.id = os.id_cliente_informe
                LEFT JOIN productos p ON cd.id_producto = p.id
                LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id";
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY os.id DESC, cd.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aprobadas = 0;
        $enRevision = 0;
        $pendientes = 0;
        $rows = [];

        foreach ($raw as $r) {
            $rev = function_exists('obtenerEstadoRevisionMatriz')
                ? obtenerEstadoRevisionMatriz($r['resultados_json'] ?? null)
                : ['estado' => 'pendiente', 'estado_label' => 'PENDIENTE MATRIZ'];

            $estCode = $rev['estado'] ?? 'pendiente';
            if ($estCode === 'aprobada') $aprobadas++;
            elseif ($estCode === 'en_revision') $enRevision++;
            else $pendientes++;

            if ($f['estado'] !== '' && strcasecmp($f['estado'], $estCode) !== 0) {
                continue;
            }

            $dec = json_decode((string)($r['resultados_json'] ?? ''), true) ?: [];
            $filasMat = isset($dec['filas']) && is_array($dec['filas']) ? $dec['filas'] : (isset($dec[0]) && is_array($dec[0]) ? $dec : []);

            $pdfs = [
                ['label' => 'Matriz PDF', 'url' => '/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=' . (int)$r['id_detalle'], 'icon' => 'fa-solid fa-file-pdf', 'color' => '#dc2626'],
                ['label' => 'Imprimir Matriz', 'url' => '/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=' . (int)$r['id_detalle'], 'icon' => 'fa-solid fa-print', 'color' => '#0284c7'],
            ];
            if (!empty($r['id_hoja'])) {
                $pdfs[] = ['label' => 'RT-FM-13', 'url' => '/Cycsa/publico/hojas-servicio/descargar?id_os=' . (int)$r['id_os'] . '&id_hoja=' . (int)$r['id_hoja'], 'icon' => 'fa-solid fa-file-lines', 'color' => '#ea580c'];
            }

            $rows[] = [
                'codigo_os' => $r['codigo_os'],
                'reg_rt13' => $r['reg_rt13'] ?: 'Sin hoja',
                'cliente' => $r['cliente_nombre'] ?? '—',
                'ensayo' => $r['descripcion_ensayo'],
                'formato' => trim($r['codigo_formato'] . ' ' . $r['formato_nombre']),
                'filas_capturadas' => count($filasMat),
                'fecha' => !empty($r['fecha_emision']) ? $r['fecha_emision'] : (!empty($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'])) : ''),
                'estado_matriz' => $rev['estado_label'] ?? strtoupper($estCode),
                'estado_os' => $r['estado_os'],
                '_pdfs' => $pdfs,
            ];
        }

        return [
            'estados_filtro' => ['pendiente', 'en_revision', 'aprobada', 'devuelta'],
            'kpis' => [
                ['titulo' => 'Total Matrices Ensayo', 'valor' => (string)count($rows), 'subtitulo' => 'Partidas de O/S operativas', 'icono' => 'fa-solid fa-table-cells', 'color' => '#103487'],
                ['titulo' => 'Matrices Aprobadas', 'valor' => (string)$aprobadas, 'subtitulo' => 'Listas para informe final', 'icono' => 'fa-solid fa-circle-check', 'color' => '#15803d'],
                ['titulo' => 'En Revisión Calidad', 'valor' => (string)$enRevision, 'subtitulo' => 'Capturadas por técnico', 'icono' => 'fa-solid fa-magnifying-glass', 'color' => '#0284c7'],
                ['titulo' => 'Pendientes / Devueltas', 'valor' => (string)$pendientes, 'subtitulo' => 'Por completar captura', 'icono' => 'fa-solid fa-hourglass', 'color' => '#d97706'],
            ],
            'columns' => [
                ['key' => 'codigo_os', 'label' => 'O/S', 'type' => 'text'],
                ['key' => 'reg_rt13', 'label' => 'RT-FM-13', 'type' => 'text'],
                ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                ['key' => 'ensayo', 'label' => 'Ensayo', 'type' => 'text'],
                ['key' => 'formato', 'label' => 'Formato Técnico', 'type' => 'text'],
                ['key' => 'filas_capturadas', 'label' => 'Filas', 'type' => 'number'],
                ['key' => 'fecha', 'label' => 'Fecha O/S', 'type' => 'date'],
                ['key' => 'estado_matriz', 'label' => 'Estado Matriz', 'type' => 'badge'],
                ['key' => 'estado_os', 'label' => 'Estado O/S', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 9. CONTROL DE CALIDAD (-CR), EQUIPOS, BODEGAS Y TRASLADOS
    // =========================================================================
    private function reporteCalidadYEquipos(array $f): array {
        $sub = in_array($f['subvista'], ['calibracion', 'replicas'], true) ? $f['subvista'] : 'traslados';
        $subvistas = [
            'traslados' => 'Traslados de Equipos y Bodegas',
            'calibracion' => 'Equipos y Calibración ISO 17025',
            'replicas' => 'Réplicas de Control de Calidad (-CR)',
        ];

        $totReplicas = $this->contarSeguro("SELECT COUNT(*) FROM recepcion_muestras WHERE is_qa_qc = 1 OR replica_codigo IS NOT NULL");
        $totEquipos = $this->contarSeguro("SELECT COUNT(*) FROM equipos_calibracion WHERE activo = 1");
        $totBodegas = $this->contarSeguro("SELECT COUNT(*) FROM equipos_bodegas WHERE activo = 1");
        $totTraslados = $this->contarSeguro("SELECT COUNT(*) FROM equipos_traslados");

        $kpis = [
            ['titulo' => 'Traslados Registrados', 'valor' => (string)$totTraslados, 'subtitulo' => "{$totBodegas} bodegas activas", 'icono' => 'fa-solid fa-dolly', 'color' => '#0f766e'],
            ['titulo' => 'Equipos en Calibración', 'valor' => (string)$totEquipos, 'subtitulo' => 'Registro metrológico', 'icono' => 'fa-solid fa-scale-balanced', 'color' => '#0284c7'],
            ['titulo' => 'Bodegas Activas', 'valor' => (string)$totBodegas, 'subtitulo' => 'Central / Obras / Talleres', 'icono' => 'fa-solid fa-warehouse', 'color' => '#103487'],
            ['titulo' => 'Réplicas QC (-CR)', 'valor' => (string)$totReplicas, 'subtitulo' => 'Muestras duplicadas CC', 'icono' => 'fa-solid fa-code-compare', 'color' => '#7c3aed'],
        ];

        if ($sub === 'calibracion') {
            $sql = "SELECT id, codigo, nombre, categoria, marca_modelo, norma, vigente_desde, vigente_hasta, activo FROM equipos_calibracion ORDER BY id DESC";
            $raw = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                if ($f['q'] !== '' && stripos($r['codigo'] . ' ' . $r['nombre'] . ' ' . $r['marca_modelo'], $f['q']) === false) continue;
                $rows[] = [
                    'codigo' => $r['codigo'],
                    'nombre' => $r['nombre'],
                    'categoria' => $r['categoria'] ?: '—',
                    'marca' => $r['marca_modelo'] ?: '—',
                    'norma' => $r['norma'] ?: '—',
                    'vigente_desde' => $r['vigente_desde'] ?: '—',
                    'vigente_hasta' => $r['vigente_hasta'] ?: '—',
                    'estado' => !empty($r['activo']) ? 'Vigente' : 'Inactivo',
                    '_pdfs' => [['label' => 'Ver Calibración', 'url' => '/Cycsa/publico/control-calidad/calibracion', 'icon' => 'fa-solid fa-eye', 'color' => '#0f766e']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'codigo', 'label' => 'Código', 'type' => 'text'],
                    ['key' => 'nombre', 'label' => 'Equipo / Instrumento', 'type' => 'text'],
                    ['key' => 'categoria', 'label' => 'Categoría', 'type' => 'text'],
                    ['key' => 'marca', 'label' => 'Marca / Modelo', 'type' => 'text'],
                    ['key' => 'norma', 'label' => 'Norma', 'type' => 'text'],
                    ['key' => 'vigente_desde', 'label' => 'Vigente Desde', 'type' => 'date'],
                    ['key' => 'vigente_hasta', 'label' => 'Vence', 'type' => 'date'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        if ($sub === 'replicas') {
            $sql = "SELECT rm.id, rm.id_os, rm.id_hoja, rm.codigo_muestra, rm.replica_codigo, rm.fecha_recepcion, rm.estado, os.codigo_os, cli.nombre_razon_social AS cliente_nombre
                    FROM recepcion_muestras rm
                    LEFT JOIN ordenes_servicio os ON rm.id_os = os.id
                    LEFT JOIN clientes cli ON os.id_cliente = cli.id
                    WHERE rm.is_qa_qc = 1 OR rm.replica_codigo IS NOT NULL
                    ORDER BY rm.id DESC";
            $raw = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'codigo_muestra' => $r['codigo_muestra'],
                    'replica_codigo' => $r['replica_codigo'] ?: ($r['codigo_muestra'] . '-CR'),
                    'codigo_os' => $r['codigo_os'] ?? '—',
                    'cliente' => $r['cliente_nombre'] ?? '—',
                    'fecha' => !empty($r['fecha_recepcion']) ? date('Y-m-d', strtotime($r['fecha_recepcion'])) : '',
                    'estado' => $r['estado'] ?? 'Registrado',
                    '_pdfs' => [['label' => 'Control Calidad', 'url' => '/Cycsa/publico/control-calidad', 'icon' => 'fa-solid fa-code-compare', 'color' => '#0f766e']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'codigo_muestra', 'label' => 'Muestra', 'type' => 'text'],
                    ['key' => 'replica_codigo', 'label' => 'Réplica -CR', 'type' => 'text'],
                    ['key' => 'codigo_os', 'label' => 'O/S', 'type' => 'text'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                    ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'date'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'et.fecha_traslado', $f, 'et');
        if ($f['q'] !== '') {
            $where[] = "(ec.codigo LIKE :q1 OR ec.nombre LIKE :q2 OR bo.nombre LIKE :q3 OR bd.nombre LIKE :q4 OR et.documento_ref LIKE :q5)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term, 'q5' => $term];
        }
        $sql = "SELECT et.id, et.equipo_id, et.fecha_traslado, et.motivo, et.documento_ref,
                       COALESCE(ec.codigo, CONCAT('EQ-', et.equipo_id)) AS eq_cod,
                       COALESCE(ec.nombre, 'Equipo') AS eq_nom,
                       COALESCE(bo.nombre, '—') AS origen,
                       COALESCE(bd.nombre, '—') AS destino,
                       COALESCE(u.nombre, '—') AS responsable
                FROM equipos_traslados et
                LEFT JOIN equipos_calibracion ec ON et.equipo_id = ec.id
                LEFT JOIN equipos_bodegas bo ON et.bodega_origen_id = bo.id
                LEFT JOIN equipos_bodegas bd ON et.bodega_destino_id = bd.id
                LEFT JOIN usuarios u ON et.usuario_id = u.id";
        if ($where !== []) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY et.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($raw as $r) {
            $rows[] = [
                'fecha' => !empty($r['fecha_traslado']) ? date('Y-m-d H:i', strtotime($r['fecha_traslado'])) : '',
                'equipo_cod' => $r['eq_cod'],
                'equipo' => $r['eq_nom'],
                'origen' => $r['origen'],
                'destino' => $r['destino'],
                'motivo' => $r['motivo'] ?: '—',
                'documento' => $r['documento_ref'] ?: '—',
                'responsable' => $r['responsable'],
                '_pdfs' => [['label' => 'Ver Bodegas', 'url' => '/Cycsa/publico/equipos-bodegas', 'icon' => 'fa-solid fa-warehouse', 'color' => '#0369a1']],
            ];
        }

        return [
            'subvistas' => $subvistas,
            'kpis' => $kpis,
            'columns' => [
                ['key' => 'fecha', 'label' => 'Fecha Traslado', 'type' => 'text'],
                ['key' => 'equipo_cod', 'label' => 'Código', 'type' => 'text'],
                ['key' => 'equipo', 'label' => 'Equipo', 'type' => 'text'],
                ['key' => 'origen', 'label' => 'Bodega Origen', 'type' => 'text'],
                ['key' => 'destino', 'label' => 'Bodega Destino', 'type' => 'text'],
                ['key' => 'motivo', 'label' => 'Motivo', 'type' => 'text'],
                ['key' => 'documento', 'label' => 'Ref. Doc.', 'type' => 'text'],
                ['key' => 'responsable', 'label' => 'Responsable', 'type' => 'text'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 10. CONTABILIDAD: REGISTROS DIARIOS (ASIENTOS), CXC, CXP Y BANCOS
    // =========================================================================
    private function reporteContabilidad(array $f): array {
        $sub = in_array($f['subvista'], ['cxc', 'cxp', 'bancos'], true) ? $f['subvista'] : 'asientos';
        $subvistas = [
            'asientos' => 'Registros Diarios (Asientos Contables)',
            'cxc' => 'Cuentas por Cobrar (CxC)',
            'cxp' => 'Cuentas por Pagar (CxP)',
            'bancos' => 'Cuentas Bancarias y Movimientos',
        ];

        $totPartidas = $this->contarSeguro("SELECT COUNT(*) FROM partidas_diario");
        $totDebe = (float)$this->escalarSeguro("SELECT COALESCE(SUM(debe), 0) FROM partidas_diario_detalles");
        $totHaber = (float)$this->escalarSeguro("SELECT COALESCE(SUM(haber), 0) FROM partidas_diario_detalles");
        $saldoCxc = (float)$this->escalarSeguro("SELECT COALESCE(SUM(saldo), 0) FROM cuentas_por_cobrar WHERE estado <> 'Pagado'");
        $saldoCxp = (float)$this->escalarSeguro("SELECT COALESCE(SUM(saldo), 0) FROM cuentas_por_pagar WHERE estado <> 'Pagado'");

        $kpis = [
            ['titulo' => 'Asientos / Registros Diarios', 'valor' => (string)$totPartidas, 'subtitulo' => 'Partidas en Libro Diario', 'icono' => 'fa-solid fa-book', 'color' => '#db2777'],
            ['titulo' => 'Total Debe / Haber', 'valor' => 'C$ ' . number_format($totDebe, 2), 'subtitulo' => 'Haber: C$ ' . number_format($totHaber, 2), 'icono' => 'fa-solid fa-scale-balanced', 'color' => '#103487'],
            ['titulo' => 'Saldo CxC Activo', 'valor' => 'C$ ' . number_format($saldoCxc, 2), 'subtitulo' => 'Por cobrar a clientes', 'icono' => 'fa-solid fa-arrow-trend-up', 'color' => '#15803d'],
            ['titulo' => 'Saldo CxP Activo', 'valor' => 'C$ ' . number_format($saldoCxp, 2), 'subtitulo' => 'Por pagar a proveedores', 'icono' => 'fa-solid fa-arrow-trend-down', 'color' => '#d97706'],
        ];

        if ($sub === 'cxc') {
            $sql = "SELECT cxc.id, cxc.factura_numero, cxc.monto, cxc.saldo, cxc.estado, cxc.fecha_emision, cxc.fecha_vencimiento, c.nombre_razon_social AS cliente
                    FROM cuentas_por_cobrar cxc LEFT JOIN clientes c ON cxc.id_cliente = c.id ORDER BY cxc.id DESC";
            $raw = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'factura' => $r['factura_numero'] ?: ('CXC-' . $r['id']),
                    'cliente' => $r['cliente'] ?? '—',
                    'fecha_emision' => $r['fecha_emision'] ?? '',
                    'fecha_vencimiento' => $r['fecha_vencimiento'] ?? '—',
                    'monto' => round((float)($r['monto'] ?? 0), 2),
                    'saldo' => round((float)($r['saldo'] ?? 0), 2),
                    'estado' => $r['estado'] ?? 'Pendiente',
                    '_pdfs' => [['label' => 'Ver CxC', 'url' => '/Cycsa/publico/contabilidad/cxc', 'icon' => 'fa-solid fa-eye', 'color' => '#db2777']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'factura', 'label' => 'Documento', 'type' => 'text'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                    ['key' => 'fecha_emision', 'label' => 'Emisión', 'type' => 'date'],
                    ['key' => 'fecha_vencimiento', 'label' => 'Vencimiento', 'type' => 'date'],
                    ['key' => 'monto', 'label' => 'Monto', 'type' => 'money'],
                    ['key' => 'saldo', 'label' => 'Saldo', 'type' => 'money'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        if ($sub === 'cxp') {
            $sql = "SELECT cxp.id, cxp.factura_numero, cxp.proveedor_nombre, cxp.monto, cxp.saldo, cxp.estado, cxp.fecha_emision, cxp.fecha_vencimiento
                    FROM cuentas_por_pagar cxp ORDER BY cxp.id DESC";
            $raw = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'factura' => $r['factura_numero'] ?: ('CXP-' . $r['id']),
                    'proveedor' => $r['proveedor_nombre'] ?? '—',
                    'fecha_emision' => $r['fecha_emision'] ?? '',
                    'fecha_vencimiento' => $r['fecha_vencimiento'] ?? '—',
                    'monto' => round((float)($r['monto'] ?? 0), 2),
                    'saldo' => round((float)($r['saldo'] ?? 0), 2),
                    'estado' => $r['estado'] ?? 'Pendiente',
                    '_pdfs' => [['label' => 'Ver CxP', 'url' => '/Cycsa/publico/contabilidad/cxp', 'icon' => 'fa-solid fa-eye', 'color' => '#db2777']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'factura', 'label' => 'Documento', 'type' => 'text'],
                    ['key' => 'proveedor', 'label' => 'Proveedor', 'type' => 'text'],
                    ['key' => 'fecha_emision', 'label' => 'Emisión', 'type' => 'date'],
                    ['key' => 'fecha_vencimiento', 'label' => 'Vencimiento', 'type' => 'date'],
                    ['key' => 'monto', 'label' => 'Monto', 'type' => 'money'],
                    ['key' => 'saldo', 'label' => 'Saldo', 'type' => 'money'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        if ($sub === 'bancos') {
            $sql = "SELECT bt.id, bt.tipo_transaccion, bt.numero_documento, bt.beneficiario, bt.monto, bt.fecha, bt.estado, bt.descripcion,
                           bc.banco_nombre, bc.numero_cuenta
                    FROM bancos_transacciones bt
                    LEFT JOIN bancos_cuentas bc ON bt.id_banco_cuenta = bc.id
                    ORDER BY bt.id DESC";
            $raw = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'fecha' => $r['fecha'] ?? '',
                    'banco' => trim(($r['banco_nombre'] ?? '') . ' (' . ($r['numero_cuenta'] ?? '') . ')'),
                    'tipo' => $r['tipo_transaccion'] ?? '—',
                    'documento' => $r['numero_documento'] ?: '—',
                    'beneficiario' => $r['beneficiario'] ?: '—',
                    'descripcion' => $r['descripcion'] ?: '—',
                    'monto' => round((float)($r['monto'] ?? 0), 2),
                    'estado' => $r['estado'] ?? 'Registrado',
                    '_pdfs' => [['label' => 'Ver Bancos', 'url' => '/Cycsa/publico/contabilidad/bancos', 'icon' => 'fa-solid fa-building-columns', 'color' => '#103487']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'date'],
                    ['key' => 'banco', 'label' => 'Banco / Cuenta', 'type' => 'text'],
                    ['key' => 'tipo', 'label' => 'Tipo', 'type' => 'text'],
                    ['key' => 'documento', 'label' => 'Nº Doc', 'type' => 'text'],
                    ['key' => 'beneficiario', 'label' => 'Beneficiario', 'type' => 'text'],
                    ['key' => 'descripcion', 'label' => 'Descripción', 'type' => 'text'],
                    ['key' => 'monto', 'label' => 'Monto', 'type' => 'money'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        // Default: Registros Diarios (Asientos Contables detallados)
        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'pd.fecha', $f, 'pd');
        if ($f['q'] !== '') {
            $where[] = "(pd.num_partida LIKE :q1 OR pd.concepto LIKE :q2 OR pd.origen LIKE :q3 OR cc.codigo LIKE :q4 OR cc.nombre LIKE :q5)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term, 'q5' => $term];
        }

        $sql = "SELECT pd.id AS id_partida, pd.num_partida, pd.fecha, pd.concepto, pd.origen, pd.origen_id,
                       cc.codigo AS cuenta_codigo, cc.nombre AS cuenta_nombre,
                       pdd.debe, pdd.haber
                FROM partidas_diario pd
                JOIN partidas_diario_detalles pdd ON pdd.id_partida = pd.id
                LEFT JOIN cuentas_contables cc ON pdd.id_cuenta_contable = cc.id";
        if ($where !== []) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY pd.fecha DESC, pd.id DESC, pdd.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($raw as $r) {
            $rows[] = [
                'num_partida' => $r['num_partida'] ?: ('AS-' . $r['id_partida']),
                'fecha' => $r['fecha'] ?? '',
                'origen' => $r['origen'] ?: 'Manual',
                'concepto' => $r['concepto'] ?: '—',
                'cuenta_codigo' => $r['cuenta_codigo'] ?? '—',
                'cuenta_nombre' => $r['cuenta_nombre'] ?? '—',
                'debe' => round((float)($r['debe'] ?? 0), 2),
                'haber' => round((float)($r['haber'] ?? 0), 2),
                '_pdfs' => [['label' => 'Ver Libro Diario', 'url' => '/Cycsa/publico/contabilidad/diario', 'icon' => 'fa-solid fa-book', 'color' => '#db2777']],
            ];
        }

        return [
            'subvistas' => $subvistas,
            'kpis' => $kpis,
            'columns' => [
                ['key' => 'num_partida', 'label' => 'Nº Partida', 'type' => 'text'],
                ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'date'],
                ['key' => 'origen', 'label' => 'Origen', 'type' => 'badge'],
                ['key' => 'concepto', 'label' => 'Concepto', 'type' => 'text'],
                ['key' => 'cuenta_codigo', 'label' => 'Cta. Código', 'type' => 'text'],
                ['key' => 'cuenta_nombre', 'label' => 'Cuenta Contable', 'type' => 'text'],
                ['key' => 'debe', 'label' => 'Debe', 'type' => 'money'],
                ['key' => 'haber', 'label' => 'Haber', 'type' => 'money'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 11. COMPRAS, ÓRDENES DE COMPRA, RECEPCIONES Y PROVEEDORES
    // =========================================================================
    private function reporteCompras(array $f): array {
        $sub = in_array($f['subvista'], ['recepciones', 'proveedores'], true) ? $f['subvista'] : 'ordenes';
        $subvistas = [
            'ordenes' => 'Órdenes de Compra',
            'recepciones' => 'Recepciones de Compra',
            'proveedores' => 'Proveedores ISO 17025',
        ];

        $totOC = $this->contarSeguro("SELECT COUNT(*) FROM ordenes_compra");
        $totRec = $this->contarSeguro("SELECT COUNT(*) FROM recepciones_compra");
        $totProv = $this->contarSeguro("SELECT COUNT(*) FROM proveedores");
        $montoCompras = (float)$this->escalarSeguro("SELECT COALESCE(SUM(total), 0) FROM ordenes_compra WHERE estado <> 'Anulada'");

        $kpis = [
            ['titulo' => 'Órdenes de Compra', 'valor' => (string)$totOC, 'subtitulo' => 'Emitidas en el sistema', 'icono' => 'fa-solid fa-cart-shopping', 'color' => '#ea580c'],
            ['titulo' => 'Monto Total Comprado', 'valor' => 'C$ ' . number_format($montoCompras, 2), 'subtitulo' => 'Órdenes vigentes (c/IVA)', 'icono' => 'fa-solid fa-coins', 'color' => '#103487'],
            ['titulo' => 'Recepciones de Compra', 'valor' => (string)$totRec, 'subtitulo' => 'Facturas / ingresos almacén', 'icono' => 'fa-solid fa-truck-ramp-box', 'color' => '#15803d'],
            ['titulo' => 'Proveedores Registrados', 'valor' => (string)$totProv, 'subtitulo' => 'Catálogo comercial/ISO', 'icono' => 'fa-solid fa-handshake', 'color' => '#0284c7'],
        ];

        if ($sub === 'recepciones') {
            $sql = "SELECT rc.id, rc.orden_compra_id, rc.numero_factura, rc.fecha_recepcion, rc.estado, rc.observaciones,
                           COALESCE(p.razon_social, oc.proveedor, '—') AS proveedor,
                           u.nombre AS recibido_por_nombre
                    FROM recepciones_compra rc
                    LEFT JOIN ordenes_compra oc ON rc.orden_compra_id = oc.id
                    LEFT JOIN proveedores p ON rc.proveedor_id = p.id
                    LEFT JOIN usuarios u ON rc.recibido_por = u.id
                    ORDER BY rc.id DESC";
            $raw = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'codigo_oc' => !empty($r['orden_compra_id']) ? ('OC-' . str_pad((string)$r['orden_compra_id'], 4, '0', STR_PAD_LEFT)) : 'Directa',
                    'numero_factura' => $r['numero_factura'] ?: '—',
                    'proveedor' => $r['proveedor'],
                    'fecha' => !empty($r['fecha_recepcion']) ? date('Y-m-d', strtotime($r['fecha_recepcion'])) : '',
                    'recibido_por' => $r['recibido_por_nombre'] ?? '—',
                    'observaciones' => $r['observaciones'] ?: '—',
                    'estado' => $r['estado'] ?? 'Recibida',
                    '_pdfs' => !empty($r['orden_compra_id']) ? [['label' => 'Ver OC', 'url' => '/Cycsa/publico/compras/ordenes/detalle?id=' . (int)$r['orden_compra_id'], 'icon' => 'fa-solid fa-eye', 'color' => '#ea580c']] : [],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'codigo_oc', 'label' => 'Orden Compra', 'type' => 'text'],
                    ['key' => 'numero_factura', 'label' => 'Nº Factura', 'type' => 'text'],
                    ['key' => 'proveedor', 'label' => 'Proveedor', 'type' => 'text'],
                    ['key' => 'fecha', 'label' => 'Fecha Recepción', 'type' => 'date'],
                    ['key' => 'recibido_por', 'label' => 'Recibido Por', 'type' => 'text'],
                    ['key' => 'observaciones', 'label' => 'Observaciones', 'type' => 'text'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        if ($sub === 'proveedores') {
            $sql = "SELECT id, identificacion, razon_social, contacto, telefono, email, categoria, evaluado, estado FROM proveedores ORDER BY id DESC";
            $raw = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'identificacion' => $r['identificacion'] ?: '—',
                    'razon_social' => $r['razon_social'],
                    'contacto' => $r['contacto'] ?: '—',
                    'telefono' => $r['telefono'] ?: '—',
                    'email' => $r['email'] ?: '—',
                    'categoria' => $r['categoria'] ?: 'General',
                    'evaluado' => !empty($r['evaluado']) ? 'Calificado ISO' : 'Pendiente',
                    'estado' => $r['estado'] ?: 'Activo',
                    '_pdfs' => [['label' => 'Proveedores', 'url' => '/Cycsa/publico/compras/proveedores', 'icon' => 'fa-solid fa-eye', 'color' => '#ea580c']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'identificacion', 'label' => 'RUC / ID', 'type' => 'text'],
                    ['key' => 'razon_social', 'label' => 'Proveedor', 'type' => 'text'],
                    ['key' => 'contacto', 'label' => 'Contacto', 'type' => 'text'],
                    ['key' => 'telefono', 'label' => 'Teléfono', 'type' => 'text'],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'text'],
                    ['key' => 'categoria', 'label' => 'Categoría', 'type' => 'text'],
                    ['key' => 'evaluado', 'label' => 'Evaluación ISO', 'type' => 'badge'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'oc.fecha', $f, 'oc');
        if ($f['estado'] !== '') {
            $where[] = "oc.estado = :oc_est";
            $params['oc_est'] = $f['estado'];
        }
        if ($f['q'] !== '') {
            $where[] = "(COALESCE(p.razon_social, oc.proveedor) LIKE :q1 OR pr.nombre LIKE :q2 OR oc.id LIKE :q3)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term];
        }
        $sql = "SELECT oc.id, oc.fecha, oc.subtotal, oc.iva, oc.total, oc.estado,
                       COALESCE(p.razon_social, oc.proveedor, '—') AS proveedor_nombre,
                       pr.nombre AS proyecto_nombre,
                       (SELECT COUNT(*) FROM ordenes_compra_detalle ocd WHERE ocd.orden_compra_id = oc.id) AS total_items
                FROM ordenes_compra oc
                LEFT JOIN proveedores p ON oc.proveedor_id = p.id
                LEFT JOIN proyectos pr ON oc.proyecto_id = pr.id";
        if ($where !== []) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY oc.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($raw as $r) {
            $rows[] = [
                'codigo_oc' => 'OC-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT),
                'fecha' => $r['fecha'] ?? '',
                'proveedor' => $r['proveedor_nombre'],
                'proyecto' => $r['proyecto_nombre'] ?: 'General',
                'items' => (int)($r['total_items'] ?? 0),
                'subtotal' => round((float)($r['subtotal'] ?? 0), 2),
                'iva' => round((float)($r['iva'] ?? 0), 2),
                'total' => round((float)($r['total'] ?? 0), 2),
                'estado' => $r['estado'] ?? 'Borrador',
                '_pdfs' => [
                    ['label' => 'Ver Detalle OC', 'url' => '/Cycsa/publico/compras/ordenes/detalle?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-eye', 'color' => '#ea580c'],
                ],
            ];
        }

        return [
            'subvistas' => $subvistas,
            'estados_filtro' => ['Borrador', 'Aprobada', 'Recibida Parcial', 'Recibida', 'Anulada'],
            'kpis' => $kpis,
            'columns' => [
                ['key' => 'codigo_oc', 'label' => 'Código OC', 'type' => 'text'],
                ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'date'],
                ['key' => 'proveedor', 'label' => 'Proveedor', 'type' => 'text'],
                ['key' => 'proyecto', 'label' => 'Proyecto / Obra', 'type' => 'text'],
                ['key' => 'items', 'label' => 'Ítems', 'type' => 'number'],
                ['key' => 'subtotal', 'label' => 'Subtotal', 'type' => 'money'],
                ['key' => 'iva', 'label' => 'IVA', 'type' => 'money'],
                ['key' => 'total', 'label' => 'Total', 'type' => 'money'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 12. INVENTARIO Y KÁRDEX
    // =========================================================================
    private function reporteInventario(array $f): array {
        $sub = $f['subvista'] === 'insumos' ? 'insumos' : 'kardex';
        $subvistas = [
            'kardex' => 'Movimientos Diarios (Kárdex)',
            'insumos' => 'Existencias por Insumo',
        ];

        $totInsumos = $this->contarSeguro("SELECT COUNT(*) FROM inventario_insumos");
        $bajoMin = $this->contarSeguro("SELECT COUNT(*) FROM inventario_insumos WHERE cantidad <= stock_minimo");
        $totMovs = $this->contarSeguro("SELECT COUNT(*) FROM inventario_movimientos");
        $totLotes = $this->contarSeguro("SELECT COUNT(*) FROM inventario_lotes");

        $kpis = [
            ['titulo' => 'Total Insumos', 'valor' => (string)$totInsumos, 'subtitulo' => "{$totLotes} lotes registrados", 'icono' => 'fa-solid fa-boxes-stacked', 'color' => '#0d9488'],
            ['titulo' => 'Bajo Stock Mínimo', 'valor' => (string)$bajoMin, 'subtitulo' => 'Requieren reposición', 'icono' => 'fa-solid fa-triangle-exclamation', 'color' => '#dc2626'],
            ['titulo' => 'Movimientos Kárdex', 'valor' => (string)$totMovs, 'subtitulo' => 'Entradas / Salidas / Ajustes', 'icono' => 'fa-solid fa-arrows-rotate', 'color' => '#103487'],
            ['titulo' => 'Lotes Activos', 'valor' => (string)$totLotes, 'subtitulo' => 'Control PEPS / FIFO', 'icono' => 'fa-solid fa-layer-group', 'color' => '#0284c7'],
        ];

        if ($sub === 'insumos') {
            $raw = $this->db->query("SELECT id, codigo, nombre, cantidad, unidad_medida, stock_minimo FROM inventario_insumos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $alerta = ((float)$r['cantidad'] <= (float)$r['stock_minimo']) ? 'Bajo Mínimo' : 'Disponible';
                $rows[] = [
                    'codigo' => $r['codigo'],
                    'nombre' => $r['nombre'],
                    'cantidad' => round((float)$r['cantidad'], 2),
                    'unidad' => $r['unidad_medida'] ?: 'Und',
                    'minimo' => round((float)$r['stock_minimo'], 2),
                    'estado' => $alerta,
                    '_pdfs' => [['label' => 'Ver Inventario', 'url' => '/Cycsa/publico/inventario', 'icon' => 'fa-solid fa-eye', 'color' => '#0d9488']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'codigo', 'label' => 'Código', 'type' => 'text'],
                    ['key' => 'nombre', 'label' => 'Insumo / Reactivo', 'type' => 'text'],
                    ['key' => 'cantidad', 'label' => 'Existencia', 'type' => 'number'],
                    ['key' => 'unidad', 'label' => 'Unidad', 'type' => 'text'],
                    ['key' => 'minimo', 'label' => 'Stock Mín.', 'type' => 'number'],
                    ['key' => 'estado', 'label' => 'Nivel Stock', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        $where = [];
        $params = [];
        $this->aplicarFiltroFechas($where, $params, 'im.created_at', $f, 'im');
        if ($f['q'] !== '') {
            $where[] = "(ii.codigo LIKE :q1 OR ii.nombre LIKE :q2 OR im.tipo_movimiento LIKE :q3 OR im.observaciones LIKE :q4)";
            $term = '%' . $f['q'] . '%';
            $params += ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }
        $sql = "SELECT im.id, im.tipo_movimiento, im.cantidad, im.costo_unitario, im.saldo_anterior, im.saldo_nuevo,
                       im.referencia_tipo, im.observaciones, im.created_at,
                       ii.codigo AS insumo_cod, ii.nombre AS insumo_nom, ii.unidad_medida,
                       u.nombre AS usuario_nombre
                FROM inventario_movimientos im
                JOIN inventario_insumos ii ON im.insumo_id = ii.id
                LEFT JOIN usuarios u ON im.usuario_id = u.id";
        if ($where !== []) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY im.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rows = [];
        foreach ($raw as $r) {
            $rows[] = [
                'fecha' => !empty($r['created_at']) ? date('Y-m-d H:i', strtotime($r['created_at'])) : '',
                'codigo' => $r['insumo_cod'],
                'insumo' => $r['insumo_nom'],
                'tipo' => $r['tipo_movimiento'],
                'cantidad' => round((float)$r['cantidad'], 2),
                'costo_u' => round((float)($r['costo_unitario'] ?? 0), 2),
                'saldo_nuevo' => round((float)($r['saldo_nuevo'] ?? 0), 2),
                'observaciones' => $r['observaciones'] ?: ($r['referencia_tipo'] ?: '—'),
                'usuario' => $r['usuario_nombre'] ?? '—',
                '_pdfs' => [['label' => 'Ver Kárdex', 'url' => '/Cycsa/publico/inventario/kardex', 'icon' => 'fa-solid fa-eye', 'color' => '#0d9488']],
            ];
        }

        return [
            'subvistas' => $subvistas,
            'kpis' => $kpis,
            'columns' => [
                ['key' => 'fecha', 'label' => 'Fecha', 'type' => 'text'],
                ['key' => 'codigo', 'label' => 'Código', 'type' => 'text'],
                ['key' => 'insumo', 'label' => 'Insumo', 'type' => 'text'],
                ['key' => 'tipo', 'label' => 'Movimiento', 'type' => 'badge'],
                ['key' => 'cantidad', 'label' => 'Cantidad', 'type' => 'number'],
                ['key' => 'costo_u', 'label' => 'Costo Unit.', 'type' => 'money'],
                ['key' => 'saldo_nuevo', 'label' => 'Saldo Nuevo', 'type' => 'number'],
                ['key' => 'observaciones', 'label' => 'Referencia / Nota', 'type' => 'text'],
                ['key' => 'usuario', 'label' => 'Usuario', 'type' => 'text'],
            ],
            'rows' => $rows,
        ];
    }

    // =========================================================================
    // 13. RRHH (NÓMINAS), ACTIVOS FIJOS, PRÉSTAMOS Y PROYECTOS
    // =========================================================================
    private function reporteRrhhYFinanzas(array $f): array {
        $sub = in_array($f['subvista'], ['activos', 'prestamos', 'proyectos'], true) ? $f['subvista'] : 'nominas';
        $subvistas = [
            'nominas' => 'Planillas de Nómina RRHH',
            'activos' => 'Activos Fijos & Depreciación',
            'prestamos' => 'Préstamos Bancarios',
            'proyectos' => 'Proyectos & Órdenes de Obra',
        ];

        $totNominas = $this->contarSeguro("SELECT COUNT(*) FROM rrhh_nominas");
        $netoNominas = (float)$this->escalarSeguro("SELECT COALESCE(SUM(total_neto), 0) FROM rrhh_nominas WHERE estado <> 'Anulada'");
        $totActivos = $this->contarSeguro("SELECT COUNT(*) FROM activos_fijos");
        $saldoPrestamos = (float)$this->escalarSeguro("SELECT COALESCE(SUM(saldo_capital), 0) FROM prestamos_bancarios WHERE estado <> 'Cancelado'");
        $totProyectos = $this->contarSeguro("SELECT COUNT(*) FROM proyectos");

        $kpis = [
            ['titulo' => 'Planillas Procesadas', 'valor' => (string)$totNominas, 'subtitulo' => 'Neto: C$ ' . number_format($netoNominas, 2), 'icono' => 'fa-solid fa-id-card-clip', 'color' => '#0284c7'],
            ['titulo' => 'Activos Fijos', 'valor' => (string)$totActivos, 'subtitulo' => 'Maquinaria y equipo', 'icono' => 'fa-solid fa-truck-ramp-box', 'color' => '#4f46e5'],
            ['titulo' => 'Saldo Préstamos', 'valor' => 'C$ ' . number_format($saldoPrestamos, 2), 'subtitulo' => 'Capital pendiente', 'icono' => 'fa-solid fa-building-columns', 'color' => '#6366f1'],
            ['titulo' => 'Proyectos / Obras', 'valor' => (string)$totProyectos, 'subtitulo' => 'Control presupuestario', 'icono' => 'fa-solid fa-helmet-safety', 'color' => '#334155'],
        ];

        if ($sub === 'activos') {
            $raw = $this->db->query("SELECT id, codigo, descripcion, categoria, fecha_adquisicion, valor_adquisicion, valor_residual, depreciacion_acumulada, estado FROM activos_fijos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $valLibros = max(0, (float)$r['valor_adquisicion'] - (float)$r['depreciacion_acumulada']);
                $rows[] = [
                    'codigo' => $r['codigo'],
                    'descripcion' => $r['descripcion'],
                    'categoria' => $r['categoria'] ?: '—',
                    'fecha' => $r['fecha_adquisicion'] ?? '',
                    'valor_adquisicion' => round((float)$r['valor_adquisicion'], 2),
                    'depreciacion_acumulada' => round((float)$r['depreciacion_acumulada'], 2),
                    'valor_libros' => round($valLibros, 2),
                    'estado' => $r['estado'] ?? 'Activo',
                    '_pdfs' => [['label' => 'Ver Activo', 'url' => '/Cycsa/publico/activos-fijos/detalle?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-eye', 'color' => '#4f46e5']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'codigo', 'label' => 'Código', 'type' => 'text'],
                    ['key' => 'descripcion', 'label' => 'Descripción Activo', 'type' => 'text'],
                    ['key' => 'categoria', 'label' => 'Categoría', 'type' => 'text'],
                    ['key' => 'fecha', 'label' => 'Adquisición', 'type' => 'date'],
                    ['key' => 'valor_adquisicion', 'label' => 'Valor Compra', 'type' => 'money'],
                    ['key' => 'depreciacion_acumulada', 'label' => 'Dep. Acum.', 'type' => 'money'],
                    ['key' => 'valor_libros', 'label' => 'Valor Libros', 'type' => 'money'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        if ($sub === 'prestamos') {
            $raw = $this->db->query("SELECT id, codigo, institucion_financiera, monto_prestamo, tasa_interes_anual, plazo_meses, fecha_desembolso, saldo_capital, estado FROM prestamos_bancarios ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'codigo' => $r['codigo'],
                    'banco' => $r['institucion_financiera'],
                    'fecha' => $r['fecha_desembolso'] ?? '',
                    'monto' => round((float)$r['monto_prestamo'], 2),
                    'tasa' => round((float)$r['tasa_interes_anual'], 2),
                    'plazo' => (int)$r['plazo_meses'],
                    'saldo' => round((float)$r['saldo_capital'], 2),
                    'estado' => $r['estado'] ?? 'Activo',
                    '_pdfs' => [['label' => 'Ver Préstamo', 'url' => '/Cycsa/publico/prestamos/detalle?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-eye', 'color' => '#6366f1']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'codigo', 'label' => 'Código', 'type' => 'text'],
                    ['key' => 'banco', 'label' => 'Institución', 'type' => 'text'],
                    ['key' => 'fecha', 'label' => 'Desembolso', 'type' => 'date'],
                    ['key' => 'monto', 'label' => 'Monto Préstamo', 'type' => 'money'],
                    ['key' => 'tasa', 'label' => 'Tasa %', 'type' => 'number'],
                    ['key' => 'plazo', 'label' => 'Meses', 'type' => 'number'],
                    ['key' => 'saldo', 'label' => 'Saldo Capital', 'type' => 'money'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        if ($sub === 'proyectos') {
            $raw = $this->db->query("SELECT pr.id, pr.codigo, pr.nombre, pr.ubicacion, pr.presupuesto_estimado, pr.fecha_inicio, pr.fecha_fin, pr.estado, c.nombre_razon_social AS cliente
                                     FROM proyectos pr LEFT JOIN clientes c ON pr.cliente_id = c.id ORDER BY pr.id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $rows = [];
            foreach ($raw as $r) {
                $rows[] = [
                    'codigo' => $r['codigo'],
                    'nombre' => $r['nombre'],
                    'cliente' => $r['cliente'] ?: 'Interno',
                    'ubicacion' => $r['ubicacion'] ?: '—',
                    'fecha_inicio' => $r['fecha_inicio'] ?? '',
                    'fecha_fin' => $r['fecha_fin'] ?? '—',
                    'presupuesto' => round((float)$r['presupuesto_estimado'], 2),
                    'estado' => $r['estado'] ?? 'Activo',
                    '_pdfs' => [['label' => 'Ver Proyecto', 'url' => '/Cycsa/publico/proyectos/detalle?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-eye', 'color' => '#334155']],
                ];
            }
            return [
                'subvistas' => $subvistas,
                'kpis' => $kpis,
                'columns' => [
                    ['key' => 'codigo', 'label' => 'Código', 'type' => 'text'],
                    ['key' => 'nombre', 'label' => 'Proyecto / Obra', 'type' => 'text'],
                    ['key' => 'cliente', 'label' => 'Cliente', 'type' => 'text'],
                    ['key' => 'ubicacion', 'label' => 'Ubicación', 'type' => 'text'],
                    ['key' => 'fecha_inicio', 'label' => 'Inicio', 'type' => 'date'],
                    ['key' => 'fecha_fin', 'label' => 'Fin', 'type' => 'date'],
                    ['key' => 'presupuesto', 'label' => 'Presupuesto', 'type' => 'money'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                ],
                'rows' => $rows,
            ];
        }

        // Default: Nóminas RRHH
        $raw = $this->db->query("SELECT n.id, n.codigo, n.periodo_inicio, n.periodo_fin, n.total_devengado, n.total_deducciones, n.total_neto, n.estado, n.id_asiento
                                 FROM rrhh_nominas n ORDER BY n.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $rows = [];
        foreach ($raw as $r) {
            $rows[] = [
                'codigo' => $r['codigo'],
                'periodo_inicio' => $r['periodo_inicio'] ?? '',
                'periodo_fin' => $r['periodo_fin'] ?? '',
                'devengado' => round((float)$r['total_devengado'], 2),
                'deducciones' => round((float)$r['total_deducciones'], 2),
                'neto' => round((float)$r['total_neto'], 2),
                'estado' => $r['estado'] ?? 'Procesada',
                '_pdfs' => [['label' => 'Ver Planilla', 'url' => '/Cycsa/publico/nomina/detalle?id=' . (int)$r['id'], 'icon' => 'fa-solid fa-eye', 'color' => '#0284c7']],
            ];
        }

        return [
            'subvistas' => $subvistas,
            'kpis' => $kpis,
            'columns' => [
                ['key' => 'codigo', 'label' => 'Código Planilla', 'type' => 'text'],
                ['key' => 'periodo_inicio', 'label' => 'Inicio Período', 'type' => 'date'],
                ['key' => 'periodo_fin', 'label' => 'Fin Período', 'type' => 'date'],
                ['key' => 'devengado', 'label' => 'Total Devengado', 'type' => 'money'],
                ['key' => 'deducciones', 'label' => 'Deducciones', 'type' => 'money'],
                ['key' => 'neto', 'label' => 'Neto a Pagar', 'type' => 'money'],
                ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
            ],
            'rows' => $rows,
        ];
    }
}
