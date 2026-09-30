<?php
$informes = $informes ?? [];
$comparaciones = $comparaciones ?? [];

// Si se recibe $comparaciones de pruebas unitarias y no $informes, generar estructura para renderizar
if (empty($informes) && !empty($comparaciones)) {
    $agrupados = [];
    foreach ($comparaciones as $c) {
        $idDet = (int)($c['id'] ?? 0);
        if (!isset($agrupados[$idDet])) {
            $agrupados[$idDet] = [
                'detalle' => $c,
                'metaOficial' => [
                    'cliente_nombre' => $c['cliente_nombre'] ?? 'Cliente Confidencial',
                    'proyecto' => $c['nombre_proyecto'] ?? 'Proyecto General',
                    'cliente_direccion' => $c['cliente_direccion'] ?? 'Nicaragua',
                    'fecha_muestreo' => date('Y-m-d'),
                    'fecha_ingreso' => date('Y-m-d'),
                    'fecha_ejecucion' => date('Y-m-d'),
                    'fecha_emision' => date('Y-m-d'),
                    'tipo_muestra' => 'Especímenes',
                    'procedimiento_muestreo' => 'CYCSA-PE Oficial',
                    'muestra_tomada_por' => 'CYCSA Laboratorio',
                    'ubicacion' => 'Sitio de Proyecto',
                    'metodo_muestreo' => $c['norma_astm'] ?? 'Norma ASTM',
                    'codigo_formato' => 'CYCSA-RT-FM-22',
                    'ensayo_realizado' => $c['descripcion_ensayo'] ?? 'Ensayo Técnico'
                ],
                'codigoInformeConsecutivo' => 'CYCSA-INF-' . ($c['codigo_original'] ?? 'MS-0001-26'),
                'columnas' => ['Código laboratorio', 'Carga (lb)', 'Observaciones'],
                'filas' => [
                    ['Código laboratorio' => $c['codigo_original'] ?? 'MS-0001-26', 'Carga (lb)' => $c['diferencias']['Carga (lb)']['original'] ?? '—'],
                    ['Código laboratorio' => $c['codigo_replica'] ?? 'MS-0001-26-CR', '_es_replica' => true, '_muestra_origen' => $c['codigo_original'] ?? 'MS-0001-26', 'Carga (lb)' => $c['diferencias']['Carga (lb)']['replica'] ?? '—']
                ],
                'pares' => [$c]
            ];
        } else {
            $agrupados[$idDet]['pares'][] = $c;
        }
    }
    $informes = array_values($agrupados);
}

// Estadísticas generales
$totalPares = 0;
$conteos = ['pendiente' => 0, 'conforme' => 0, 'no_conforme' => 0];
foreach ($informes as $inf) {
    foreach ($inf['pares'] as $par) {
        $totalPares++;
        $est = $par['evaluacion']['estado'] ?? 'pendiente';
        $conteos[$est] = ($conteos[$est] ?? 0) + 1;
    }
}
$abrirId = isset($_GET['abrir']) ? (int)$_GET['abrir'] : 0;
?>

<style>
    .qc-page { max-width: 1400px; margin: 0 auto; padding-bottom: 60px; }
    
    /* Topbar & Header */
    .qc-head { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
    .qc-title { margin:0; color:#0f172a; font-family:'Outfit',sans-serif; font-size:26px; font-weight:800; display:flex; align-items:center; gap:12px; }
    .qc-subtitle { margin:6px 0 0; color:#64748b; font-size:13.5px; }
    
    /* Resumen KPI */
    .qc-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:24px; }
    .qc-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 2px 10px rgba(15,23,42,.03); border-left:4px solid #0f766e; }
    .qc-kpi span { display:block; color:#64748b; font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; }
    .qc-kpi strong { display:block; color:#0f172a; font-size:26px; font-weight:800; margin-top:4px; font-family:'Outfit', sans-serif; }

    /* Barra de Búsqueda y Filtros de Controles */
    .qc-toolbar-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .qc-search-box {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 280px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 8px 14px;
        transition: border-color 0.2s;
    }
    .qc-search-box:focus-within {
        border-color: #103487;
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08);
        background: #ffffff;
    }
    .qc-search-input {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        font-family: 'Inter', sans-serif;
        font-size: 13.5px;
        color: #0f172a;
    }
    .qc-filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .btn-qc-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-qc-action:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    /* Tabla Maestra de Controles (Estilo Clientes CYCSA) */
    .tabla-cycsa-container {
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(15,23,42,0.03);
        overflow: hidden;
        margin-bottom: 30px;
    }
    .tabla-cycsa {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
        background: white;
    }
    .tabla-cycsa th {
        background-color: #f8fafc;
        color: #475569;
        padding: 14px 18px;
        text-align: left;
        font-weight: 700;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }
    .tabla-cycsa td {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #1e293b;
    }
    .tabla-cycsa tbody tr.row-main {
        transition: background-color 0.15s ease;
        cursor: pointer;
    }
    .tabla-cycsa tbody tr.row-main:hover {
        background-color: #f8fafc;
    }
    .tabla-cycsa tbody tr.row-main.is-open {
        background-color: #eff6ff !important;
        border-left: 4px solid #103487;
    }

    /* Badges */
    .badge-premium {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-conforme { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-pendiente { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-no-conforme { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .badge-os { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-family: monospace; font-weight: 700; }
    .badge-formato { background-color: #0f172a; color: #ffffff; font-family: monospace; font-size: 11px; padding: 2px 7px; border-radius: 4px; }

    /* Fila Desplegable (Accordion/Detail Row) */
    .detail-row {
        display: none;
        background-color: #f8fafc;
    }
    .detail-container {
        padding: 16px 20px 24px 20px;
        border-bottom: 2px solid #cbd5e1;
        animation: slideDown 0.25s ease-out;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Chevron rotativo */
    .chevron-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #475569;
        transition: transform 0.25s ease, background-color 0.2s, color 0.2s;
    }
    .row-main.is-open .chevron-toggle {
        transform: rotate(180deg);
        background: #103487;
        color: #ffffff;
    }

    /* Tarjeta Oficial de Hoja de Ensayo Completa (Dentro del Detalle) */
    .informe-sheet-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-top: 5px solid #103487;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .informe-sheet-header {
        background: #ffffff;
        border-bottom: 2px solid #0f172a;
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .informe-sheet-title {
        font-family: 'Outfit', sans-serif;
        font-size: 18.5px;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        margin: 0;
        letter-spacing: 0.5px;
    }
    .informe-consecutivo-box {
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 14px;
        font-weight: 800;
        color: #103487;
        margin-top: 4px;
        display: inline-block;
    }
    .btn-sheet-action {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 7px 14px;
        border-radius: 7px;
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-sheet-action:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    /* Grilla de Metadatos Oficiales */
    .sheet-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 24px;
        padding: 16px 24px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        font-size: 12.5px;
    }
    @media(max-width: 900px) {
        .sheet-meta-grid { grid-template-columns: 1fr; gap: 8px; }
    }
    .sheet-meta-item {
        display: grid;
        grid-template-columns: 180px 1fr;
        align-items: baseline;
        gap: 10px;
        padding: 2px 0;
    }
    .sheet-meta-label {
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.4px;
    }
    .sheet-meta-val {
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
    }

    /* Sección de Tabla de Matriz Oficial */
    .sheet-table-section {
        padding: 20px 24px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
    }
    .sheet-table-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .sheet-table-heading h4 {
        margin: 0;
        font-size: 14.5px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tabla-matriz-qc {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
    }
    .tabla-matriz-qc th {
        background: #0f172a;
        color: #ffffff;
        font-weight: 700;
        padding: 9px 12px;
        text-align: left;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid #1e293b;
        white-space: nowrap;
    }
    .tabla-matriz-qc td {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        vertical-align: middle;
    }
    .tabla-matriz-qc tr.tr-replica-row {
        background-color: #f0fdfa !important;
        border-left: 4px solid #0f766e;
    }
    .badge-replica-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ccfbf1;
        color: #0f766e;
        border: 1px solid #99f6e4;
        font-weight: 800;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* Sección de Comparación Técnica & Dictamen */
    .sheet-qa-section {
        padding: 22px 24px;
        background: #fafaf9;
    }
    .sheet-qa-heading {
        margin-bottom: 16px;
    }
    .sheet-qa-heading h4 {
        margin: 0 0 4px 0;
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sheet-qa-heading p {
        margin: 0;
        font-size: 12.5px;
        color: #64748b;
    }

    .qc-pairs-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }
    .qc-pair-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 16px 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }
    .qc-pair-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 10px;
    }
    .qc-pair-codes {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: monospace;
        font-size: 13px;
    }
    .qc-diff-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        margin-bottom: 14px;
    }
    .qc-diff-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 7px 10px;
        border: 1px solid #e2e8f0;
        font-size: 11px;
        text-transform: uppercase;
        text-align: left;
    }
    .qc-diff-table td {
        padding: 7px 10px;
        border: 1px solid #e2e8f0;
        color: #1e293b;
    }

    .qc-eval-form {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
    }
    .qc-eval-form label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 5px;
    }
    .qc-eval-form select, .qc-eval-form textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 13px;
        margin-bottom: 10px;
        font-family: inherit;
        background: #ffffff;
    }
    .qc-eval-form select:focus, .qc-eval-form textarea:focus {
        outline: none;
        border-color: #103487;
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08);
    }
    .btn-save-qc {
        background: #0f766e;
        color: #ffffff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }
    .btn-save-qc:hover {
        background: #115e59;
    }

    .qc-empty-state {
        background: #ffffff;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 50px 30px;
        text-align: center;
        color: #64748b;
        margin-top: 15px;
    }
</style>

<div class="qc-page">
    <!-- Encabezado de la Página -->
    <div class="qc-head">
        <div>
            <h1 class="qc-title">
                <i class="fa-solid fa-microscope" style="color:#0f766e;"></i>
                Control de Calidad de Réplicas (ISO/IEC 17025)
            </h1>
            <p class="qc-subtitle">
                Supervisión analítica de repetibilidad y precisión técnica de muestras contra réplicas de control interno (-CR).
            </p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="/Cycsa/publico/operaciones" class="btn-qc-action">
                <i class="fa-solid fa-arrow-left"></i> Volver a Operaciones
            </a>
        </div>
    </div>

    <!-- Mensajes Flash de Sesión -->
    <?php if (!empty($_SESSION['exito'])): ?>
        <div style="background:#ecfdf5; border-left:4px solid #10b981; color:#065f46; padding:12px 18px; border-radius:8px; margin-bottom:18px; font-size:13.5px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= htmlspecialchars($_SESSION['exito']) ?></span>
            <?php unset($_SESSION['exito']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div style="background:#fef2f2; border-left:4px solid #ef4444; color:#991b1b; padding:12px 18px; border-radius:8px; margin-bottom:18px; font-size:13.5px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- 4 Tarjetas de Resumen KPI -->
    <div class="qc-summary">
        <div class="qc-kpi" style="border-left-color:#0284c7;">
            <span>Total Controles / Informes</span>
            <strong><?= count($informes) ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#0f766e;">
            <span>Total Pares de Muestras</span>
            <strong><?= $totalPares ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#d97706;">
            <span>Pendientes de Evaluación</span>
            <strong style="color:#d97706;"><?= $conteos['pendiente'] ?? 0 ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#16a34a;">
            <span>Conformes (Tolerancia OK)</span>
            <strong style="color:#16a34a;"><?= $conteos['conforme'] ?? 0 ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#dc2626;">
            <span>No Conformes</span>
            <strong style="color:#dc2626;"><?= $conteos['no_conforme'] ?? 0 ?></strong>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda Interactiva de Controles -->
    <div class="qc-toolbar-card">
        <div class="qc-search-box">
            <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <input type="text" id="filtroTextoControles" class="qc-search-input" placeholder="Buscar por cliente, proyecto, O/S, código de informe o ensayo..." oninput="filtrarControles()">
            <button type="button" onclick="limpiarFiltro()" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:12px; display:none;" id="btnLimpiarFiltro" title="Limpiar búsqueda">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="qc-filter-group">
            <select id="filtroEstadoControles" class="qc-search-input" style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:7px 10px; width:auto;" onchange="filtrarControles()">
                <option value="todos">Todos los estados</option>
                <option value="pendiente">Solo Pendientes</option>
                <option value="conforme">Solo Conformes</option>
                <option value="no_conforme">Solo No Conformes</option>
            </select>
            <button type="button" class="btn-qc-action" onclick="expandirTodos()" title="Desplegar todas las matrices">
                <i class="fa-solid fa-angles-down"></i> Expandir todos
            </button>
            <button type="button" class="btn-qc-action" onclick="colapsarTodos()" title="Colapsar todas las matrices">
                <i class="fa-solid fa-angles-up"></i> Colapsar todos
            </button>
        </div>
    </div>

    <?php if (empty($informes)): ?>
        <div class="qc-empty-state">
            <i class="fa-solid fa-vials" style="font-size:44px; color:#94a3b8; margin-bottom:14px;"></i>
            <h3 style="margin:0 0 8px 0; color:#334155; font-size:18px;">No hay informes con réplicas registradas</h3>
            <p style="margin:0; font-size:13.5px;">Cuando marque réplicas durante la captura de una matriz en Operaciones, el informe completo del cliente aparecerá aquí automáticamente para su análisis técnico.</p>
        </div>
    <?php else: ?>

        <!-- Tabla Maestra de Controles (Tipo Cartera de Clientes / Expandible) -->
        <div class="tabla-cycsa-container">
            <div style="padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-list-check" style="color:#103487;"></i> Listado de Controles de Calidad Activos
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    Haga clic en cualquier fila para expandir/colapsar su matriz completa e informe oficial
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="tabla-cycsa" id="tablaMaestraControles">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Control / O/S</th>
                            <th style="width: 220px;">Código Informe</th>
                            <th>Cliente / Proyecto</th>
                            <th>Ensayo & Formato</th>
                            <th style="width: 120px; text-align:center;">Muestras</th>
                            <th style="width: 160px; text-align:center;">Dictamen Calidad</th>
                            <th style="width: 130px; text-align: right;">Ver Matriz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $idxControl = 0;
                        foreach ($informes as $inf): 
                            $idxControl++;
                            $det = $inf['detalle'];
                            $meta = $inf['metaOficial'];
                            $cols = $inf['columnas'];
                            $filas = $inf['filas'];
                            $pares = $inf['pares'];
                            $idDet = (int)($det['id'] ?? 0);

                            // Determinar estado consolidado del control
                            $estadosPar = array_map(fn($p) => $p['evaluacion']['estado'] ?? 'pendiente', $pares);
                            if (in_array('no_conforme', $estadosPar, true)) {
                                $estadoConsolidado = 'no_conforme';
                                $badgeClass = 'badge-no-conforme';
                                $badgeIcon = 'fa-triangle-exclamation';
                                $badgeTexto = 'No Conforme';
                            } elseif (in_array('pendiente', $estadosPar, true)) {
                                $estadoConsolidado = 'pendiente';
                                $badgeClass = 'badge-pendiente';
                                $badgeIcon = 'fa-clock';
                                $pendientesCount = count(array_filter($estadosPar, fn($e) => $e === 'pendiente'));
                                $badgeTexto = "Pendiente ({$pendientesCount}/" . count($pares) . ")";
                            } else {
                                $estadoConsolidado = 'conforme';
                                $badgeClass = 'badge-conforme';
                                $badgeIcon = 'fa-circle-check';
                                $badgeTexto = '100% Conforme (' . count($pares) . ')';
                            }

                            // Texto para filtrado rápido en JS
                            $searchHaystack = strtolower(
                                ($det['codigo_os'] ?? '') . ' ' .
                                ($inf['codigoInformeConsecutivo'] ?? '') . ' ' .
                                ($meta['cliente_nombre'] ?? '') . ' ' .
                                ($meta['proyecto'] ?? '') . ' ' .
                                ($meta['ensayo_realizado'] ?? '') . ' ' .
                                ($det['descripcion_ensayo'] ?? '') . ' ' .
                                ($meta['codigo_formato'] ?? '')
                            );

                            // Por defecto se abre el que viene en ?abrir=ID, o el primero si no hay parámetro
                            $debeAbrir = ($abrirId > 0 && $abrirId === $idDet) || ($abrirId === 0 && $idxControl === 1);
                        ?>
                            <!-- Fila Principal del Control (Clicable) -->
                            <tr class="row-main row-control <?= $debeAbrir ? 'is-open' : '' ?>" 
                                id="row-control-<?= $idDet ?>" 
                                onclick="toggleControlRow(<?= $idDet ?>)"
                                data-control-id="<?= $idDet ?>"
                                data-estado="<?= $estadoConsolidado ?>"
                                data-search="<?= htmlspecialchars($searchHaystack, ENT_QUOTES, 'UTF-8') ?>">
                                
                                <td>
                                    <span class="badge-premium badge-os">
                                        <i class="fa-solid fa-file-contract"></i>
                                        <?= htmlspecialchars($det['codigo_os'] ?? 'OS') ?>
                                    </span>
                                    <div style="font-size:11px; color:#64748b; margin-top:3px; font-family:monospace;">
                                        ID #<?= $idDet ?>
                                    </div>
                                </td>

                                <td>
                                    <strong style="color:#103487; font-family:monospace; font-size:12.5px;">
                                        <?= htmlspecialchars($inf['codigoInformeConsecutivo']) ?>
                                    </strong>
                                    <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                        <?= htmlspecialchars($meta['fecha_emision'] ?? date('Y-m-d')) ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight:700; color:#0f172a; font-size:13px;">
                                        <?= htmlspecialchars($meta['cliente_nombre'] ?? 'Cliente Confidencial') ?>
                                    </div>
                                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                        <?= htmlspecialchars($meta['proyecto'] ?? 'Proyecto General') ?>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight:600; color:#1e293b; font-size:12.5px;">
                                        <?= htmlspecialchars($meta['ensayo_realizado'] ?? ($det['descripcion_ensayo'] ?? 'Ensayo')) ?>
                                    </div>
                                    <div style="margin-top:3px; display:flex; align-items:center; gap:6px;">
                                        <span class="badge-formato">
                                            <?= htmlspecialchars($meta['codigo_formato'] ?? 'FM-22') ?>
                                        </span>
                                        <span style="font-size:11px; color:#64748b;">
                                            <?= htmlspecialchars($meta['metodo_muestreo'] ?? ($det['norma_astm'] ?? '')) ?>
                                        </span>
                                    </div>
                                </td>

                                <td style="text-align:center;">
                                    <span style="font-weight:700; color:#0f172a; font-size:13px;">
                                        <?= count($filas) ?>
                                    </span>
                                    <div style="font-size:11px; color:#0f766e; font-weight:700; margin-top:2px;">
                                        <?= count($pares) ?> réplica(s)
                                    </div>
                                </td>

                                <td style="text-align:center;">
                                    <span class="badge-premium <?= $badgeClass ?>">
                                        <i class="fa-solid <?= $badgeIcon ?>"></i>
                                        <?= $badgeTexto ?>
                                    </span>
                                </td>

                                <td style="text-align:right;">
                                    <button type="button" class="btn-qc-action" style="padding: 5px 10px; font-size: 11.5px; border-radius: 20px;" onclick="event.stopPropagation(); toggleControlRow(<?= $idDet ?>);">
                                        <span class="toggle-text"><?= $debeAbrir ? 'Ocultar' : 'Ver Matriz' ?></span>
                                        <span class="chevron-toggle">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Fila de Detalle Desplegable con la Matriz Completa y Formulario de Control -->
                            <tr id="detail-row-<?= $idDet ?>" class="detail-row" style="display: <?= $debeAbrir ? 'table-row' : 'none' ?>;" data-parent-control="<?= $idDet ?>">
                                <td colspan="7" style="padding: 0; background: #f8fafc;">
                                    <div class="detail-container">
                                        
                                        <!-- Tarjeta Oficial Completa de la Hoja de Ensayo -->
                                        <article class="informe-sheet-card" id="control-<?= $idDet ?>">
                                            
                                            <!-- 1. Cabecera Oficial del Informe -->
                                            <div class="informe-sheet-header">
                                                <div>
                                                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                                                        <span class="badge-premium badge-os">
                                                            <i class="fa-solid fa-file-contract"></i> <?= htmlspecialchars($det['codigo_os'] ?? 'O/S') ?>
                                                        </span>
                                                        <span class="badge-formato" style="padding:4px 9px; font-size:12px;">
                                                            <?= htmlspecialchars($meta['codigo_formato'] ?? 'CYCSA-RT-FM-22') ?>
                                                        </span>
                                                        <span style="font-size:11.5px; font-weight:700; color:#0f766e; background:#ccfbf1; padding:3px 8px; border-radius:4px;">
                                                            <i class="fa-solid fa-certificate"></i> Control Interno ISO/IEC 17025:2017
                                                        </span>
                                                    </div>
                                                    <h3 class="informe-sheet-title">
                                                        <?= htmlspecialchars($meta['ensayo_realizado'] ?? ($det['descripcion_ensayo'] ?? 'INFORME DE ENSAYO')) ?>
                                                    </h3>
                                                    <div class="informe-consecutivo-box">
                                                        <i class="fa-solid fa-hashtag"></i> <?= htmlspecialchars($inf['codigoInformeConsecutivo']) ?>
                                                    </div>
                                                </div>
                                                
                                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                                    <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= (int)$det['id'] ?>" class="btn-sheet-action" target="_blank" title="Abrir matriz en pestaña nueva">
                                                        <i class="fa-solid fa-pen-to-square"></i> Abrir Matriz
                                                    </a>
                                                    <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= (int)$det['id'] ?>" class="btn-sheet-action" target="_blank" title="Ver formato de impresión oficial">
                                                        <i class="fa-solid fa-print"></i> Vista Imprimible
                                                    </a>
                                                    <button type="button" class="btn-sheet-action" onclick="toggleControlRow(<?= $idDet ?>)" title="Colapsar esta matriz">
                                                        <i class="fa-solid fa-chevron-up"></i> Colapsar
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- 2. Metadatos Oficiales de la Hoja de Servicio / Cliente -->
                                            <div class="sheet-meta-grid">
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Cliente:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['cliente_nombre'] ?? 'Sin especificar') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Proyecto:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['proyecto'] ?? 'Sin especificar') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Dirección / Sitio:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['cliente_direccion'] ?? '—') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Ubicación Muestreo:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['ubicacion'] ?? '—') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Fecha de Muestreo:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_muestreo'] ?? '—') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Fecha de Ingreso:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_ingreso'] ?? '—') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Fecha de Ejecución:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_ejecucion'] ?? '—') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Fecha de Emisión:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_emision'] ?? '—') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Tipo de Muestra:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['tipo_muestra'] ?? '—') ?></span>
                                                </div>
                                                <div class="sheet-meta-item">
                                                    <span class="sheet-meta-label">Norma / Método:</span>
                                                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['metodo_muestreo'] ?? ($det['norma_astm'] ?? '—')) ?></span>
                                                </div>
                                            </div>

                                            <!-- 3. Tabla Matriz Técnica Completa con TODAS las Muestras y Réplicas -->
                                            <div class="sheet-table-section">
                                                <div class="sheet-table-heading">
                                                    <h4>
                                                        <i class="fa-solid fa-table-cells" style="color:#103487;"></i>
                                                        Resultados de la Matriz Técnica Oficial (Todas las muestras del lote y sus réplicas)
                                                    </h4>
                                                    <span style="font-size:12px; color:#64748b;">
                                                        Total muestras en matriz: <strong><?= count($filas) ?></strong>
                                                    </span>
                                                </div>

                                                <div style="overflow-x:auto;">
                                                    <table class="tabla-matriz-qc">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:40px; text-align:center;">#</th>
                                                                <?php foreach ($cols as $col): ?>
                                                                    <th><?= htmlspecialchars($col) ?></th>
                                                                <?php endforeach; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php 
                                                            $rIdx = 0;
                                                            foreach ($filas as $fila): 
                                                                $rIdx++;
                                                                $esRep = esFilaReplicaMatriz($fila);
                                                                $origen = $fila['_muestra_origen'] ?? '';
                                                            ?>
                                                                <tr class="<?= $esRep ? 'tr-replica-row' : '' ?>">
                                                                    <td style="text-align:center; font-weight:700; color:<?= $esRep ? '#0f766e' : '#64748b' ?>;">
                                                                        <?= $rIdx ?>
                                                                    </td>
                                                                    <?php foreach ($cols as $col): 
                                                                        $val = $fila[$col] ?? '';
                                                                        $isCodCol = ($col === 'Código laboratorio' || $col === 'No. Muestra' || $col === 'Muestra');
                                                                    ?>
                                                                        <td>
                                                                            <?php if ($isCodCol && $esRep): ?>
                                                                                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                                                                    <strong style="color:#0f766e;"><?= htmlspecialchars((string)$val) ?></strong>
                                                                                    <span class="badge-replica-tag">
                                                                                        <i class="fa-solid fa-clone"></i> RÉPLICA DE <?= htmlspecialchars($origen) ?>
                                                                                    </span>
                                                                                </div>
                                                                            <?php elseif ($isCodCol): ?>
                                                                                <strong><?= htmlspecialchars((string)$val) ?></strong>
                                                                            <?php else: ?>
                                                                                <?= htmlspecialchars((string)$val) ?>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- 4. Análisis de Tolerancias y Formulario de Dictamen -->
                                            <div class="sheet-qa-section">
                                                <div class="sheet-qa-heading">
                                                    <h4>
                                                        <i class="fa-solid fa-chart-line" style="color:#0f766e;"></i>
                                                        Evaluación Analítica de Precisión / Repetibilidad (Muestras Originales vs Réplicas -CR)
                                                    </h4>
                                                    <p>
                                                        Cálculo automático de diferencias absolutas y variación porcentual para auditoría y aseguramiento de calidad (ISO/IEC 17025).
                                                    </p>
                                                </div>

                                                <div class="qc-pairs-container">
                                                    <?php foreach ($pares as $par): 
                                                        $eval = $par['evaluacion'] ?? ['estado' => 'pendiente', 'observaciones' => '', 'usuario' => '', 'fecha' => ''];
                                                        $estado = $eval['estado'] ?? 'pendiente';
                                                    ?>
                                                        <div class="qc-pair-card">
                                                            <div class="qc-pair-header">
                                                                <div class="qc-pair-codes">
                                                                    <span style="font-weight:700; color:#1e293b;">Original:</span>
                                                                    <span style="background:#f1f5f9; padding:2px 8px; border-radius:4px; font-weight:700;"><?= htmlspecialchars($par['codigo_original']) ?></span>
                                                                    <i class="fa-solid fa-arrow-right" style="color:#94a3b8; font-size:11px;"></i>
                                                                    <span style="font-weight:700; color:#0f766e;">Réplica:</span>
                                                                    <span style="background:#ccfbf1; color:#0f766e; padding:2px 8px; border-radius:4px; font-weight:800;"><?= htmlspecialchars($par['codigo_replica']) ?></span>
                                                                </div>

                                                                <div>
                                                                    <?php if ($estado === 'conforme'): ?>
                                                                        <span class="badge-premium badge-conforme"><i class="fa-solid fa-circle-check"></i> Conforme</span>
                                                                    <?php elseif ($estado === 'no_conforme'): ?>
                                                                        <span class="badge-premium badge-no-conforme"><i class="fa-solid fa-triangle-exclamation"></i> No Conforme</span>
                                                                    <?php else: ?>
                                                                        <span class="badge-premium badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Tabla de Variaciones Numéricas -->
                                                            <div style="overflow-x:auto;">
                                                                <table class="qc-diff-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Campo medible</th>
                                                                            <th>Original</th>
                                                                            <th>Réplica</th>
                                                                            <th>Diferencia</th>
                                                                            <th>Variación</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php if (empty($par['diferencias'])): ?>
                                                                            <tr>
                                                                                <td colspan="5" style="text-align:center; color:#94a3b8; padding:12px;">
                                                                                    Sin datos numéricos comparables o idénticos.
                                                                                </td>
                                                                            </tr>
                                                                        <?php else: ?>
                                                                            <?php foreach ($par['diferencias'] as $campo => $dif): ?>
                                                                                <tr>
                                                                                    <td style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($campo) ?></td>
                                                                                    <td><?= htmlspecialchars((string)$dif['original']) ?></td>
                                                                                    <td><?= htmlspecialchars((string)$dif['replica']) ?></td>
                                                                                    <td><?= number_format((float)$dif['diferencia'], 4, '.', ',') ?></td>
                                                                                    <td>
                                                                                        <?php if ($dif['diferencia_porcentual'] === null): ?>
                                                                                            N/A
                                                                                        <?php else: 
                                                                                            $porc = (float)$dif['diferencia_porcentual'];
                                                                                            $colorVar = abs($porc) <= 5.0 ? '#15803d' : (abs($porc) <= 10.0 ? '#b45309' : '#b91c1c');
                                                                                        ?>
                                                                                            <strong style="color:<?= $colorVar ?>;">
                                                                                                <?= ($porc > 0 ? '+' : '') . number_format($porc, 2, '.', ',') ?>%
                                                                                            </strong>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <!-- Formulario de Dictamen -->
                                                            <form class="qc-eval-form" method="POST" action="/Cycsa/publico/control-calidad/evaluar-replica">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                                <input type="hidden" name="id_detalle" value="<?= (int)$det['id'] ?>">
                                                                <input type="hidden" name="codigo_original" value="<?= htmlspecialchars($par['codigo_original'], ENT_QUOTES, 'UTF-8') ?>">
                                                                
                                                                <label>Dictamen del Responsable de Calidad:</label>
                                                                <select name="estado" required>
                                                                    <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente de análisis</option>
                                                                    <option value="conforme" <?= $estado === 'conforme' ? 'selected' : '' ?>>Conforme (Dentro de tolerancias)</option>
                                                                    <option value="no_conforme" <?= $estado === 'no_conforme' ? 'selected' : '' ?>>No conforme (Discrepancia crítica)</option>
                                                                </select>
                                                                
                                                                <label>Conclusiones / Observaciones de Repetibilidad:</label>
                                                                <textarea name="observaciones" maxlength="2000" placeholder="Documente cumplimiento de tolerancias de la norma técnica..."><?= htmlspecialchars($eval['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                                                
                                                                <?php if (!empty($eval['fecha'])): ?>
                                                                    <div style="font-size:11px; color:#64748b; margin-bottom:8px;">
                                                                        Última evaluación: <strong><?= htmlspecialchars($eval['usuario'] ?? '') ?></strong> (<?= htmlspecialchars($eval['fecha']) ?>)
                                                                    </div>
                                                                <?php endif; ?>

                                                                <button type="submit" class="btn-save-qc">
                                                                    <i class="fa-solid fa-shield-halved"></i> Guardar evaluación
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleControlRow(id) {
        const detailRow = document.getElementById('detail-row-' + id);
        const mainRow = document.getElementById('row-control-' + id);
        if (!detailRow || !mainRow) return;

        const isCurrentlyOpen = detailRow.style.display === 'table-row';
        const toggleBtn = mainRow.querySelector('.toggle-text');

        if (isCurrentlyOpen) {
            detailRow.style.display = 'none';
            mainRow.classList.remove('is-open');
            if (toggleBtn) toggleBtn.textContent = 'Ver Matriz';
        } else {
            detailRow.style.display = 'table-row';
            mainRow.classList.add('is-open');
            if (toggleBtn) toggleBtn.textContent = 'Ocultar';
        }
    }

    function expandirTodos() {
        document.querySelectorAll('.detail-row').forEach(row => {
            row.style.display = 'table-row';
        });
        document.querySelectorAll('.row-control').forEach(row => {
            row.classList.add('is-open');
            const toggleBtn = row.querySelector('.toggle-text');
            if (toggleBtn) toggleBtn.textContent = 'Ocultar';
        });
    }

    function colapsarTodos() {
        document.querySelectorAll('.detail-row').forEach(row => {
            row.style.display = 'none';
        });
        document.querySelectorAll('.row-control').forEach(row => {
            row.classList.remove('is-open');
            const toggleBtn = row.querySelector('.toggle-text');
            if (toggleBtn) toggleBtn.textContent = 'Ver Matriz';
        });
    }

    function filtrarControles() {
        const texto = (document.getElementById('filtroTextoControles').value || '').toLowerCase().trim();
        const estado = document.getElementById('filtroEstadoControles').value;
        const btnLimpiar = document.getElementById('btnLimpiarFiltro');
        if (btnLimpiar) {
            btnLimpiar.style.display = texto.length > 0 ? 'inline-block' : 'none';
        }

        const filas = document.querySelectorAll('.row-control');
        filas.forEach(fila => {
            const searchData = fila.getAttribute('data-search') || '';
            const filaEstado = fila.getAttribute('data-estado') || '';
            const controlId = fila.getAttribute('data-control-id');
            const detailRow = document.getElementById('detail-row-' + controlId);

            const coincideTexto = !texto || searchData.includes(texto);
            const coincideEstado = (estado === 'todos') || (filaEstado === estado);

            if (coincideTexto && coincideEstado) {
                fila.style.display = 'table-row';
            } else {
                fila.style.display = 'none';
                if (detailRow) {
                    detailRow.style.display = 'none';
                }
                fila.classList.remove('is-open');
                const toggleBtn = fila.querySelector('.toggle-text');
                if (toggleBtn) toggleBtn.textContent = 'Ver Matriz';
            }
        });
    }

    function limpiarFiltro() {
        const input = document.getElementById('filtroTextoControles');
        if (input) {
            input.value = '';
            filtrarControles();
            input.focus();
        }
    }

    // Al cargar la página, auto-abrir si se especificó parámetro ?abrir=ID o #control-ID
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        let abrirId = urlParams.get('abrir');
        if (!abrirId && window.location.hash) {
            const hash = window.location.hash.replace('#control-', '').replace('#', '');
            if (hash && !isNaN(hash)) {
                abrirId = hash;
            }
        }

        if (abrirId) {
            const targetDetail = document.getElementById('detail-row-' + abrirId);
            const targetMain = document.getElementById('row-control-' + abrirId);
            if (targetDetail && targetMain) {
                targetDetail.style.display = 'table-row';
                targetMain.classList.add('is-open');
                const toggleBtn = targetMain.querySelector('.toggle-text');
                if (toggleBtn) toggleBtn.textContent = 'Ocultar';
                targetMain.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });
</script>
