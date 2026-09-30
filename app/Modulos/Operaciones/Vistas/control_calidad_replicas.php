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

    /* Tabla Maestra de Controles (Estilo Cartera Clientes CYCSA) */
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

    .btn-ver-control {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #103487;
        color: #ffffff;
        border: 1px solid #103487;
        padding: 7px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(16, 52, 135, 0.15);
    }
    .btn-ver-control:hover {
        background: #0c2766;
        border-color: #0c2766;
        color: #ffffff;
        transform: translateY(-1px);
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
                Cartera y supervisión de controles de ensayo analíticos: toque cualquier control para inspeccionar su matriz técnica y dictamen en pantalla completa.
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

    <!-- 5 Tarjetas de Resumen KPI -->
    <div class="qc-summary">
        <div class="qc-kpi" style="border-left-color:#0284c7;">
            <span>Total Controles Activos</span>
            <strong><?= count($informes) ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#0f766e;">
            <span>Total Pares de Muestras</span>
            <strong><?= $totalPares ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#d97706;">
            <span>Pendientes de Dictamen</span>
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
        </div>
    </div>

    <?php if (empty($informes)): ?>
        <div class="qc-empty-state">
            <i class="fa-solid fa-vials" style="font-size:44px; color:#94a3b8; margin-bottom:14px;"></i>
            <h3 style="margin:0 0 8px 0; color:#334155; font-size:18px;">No hay informes con réplicas registradas</h3>
            <p style="margin:0; font-size:13.5px;">Cuando marque réplicas durante la captura de una matriz en Operaciones, el informe completo del cliente aparecerá aquí automáticamente para su análisis técnico.</p>
        </div>
    <?php else: ?>

        <!-- Tabla Maestra de Controles (Tipo Cartera de Clientes con Redirección a Detalle) -->
        <div class="tabla-cycsa-container">
            <div style="padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-list-check" style="color:#103487;"></i> Listado de Controles de Calidad Activos
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    Haga clic en cualquier fila para abrir su matriz e informe oficial a pantalla completa
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
                            <th style="width: 160px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($informes as $inf): 
                            $det = $inf['detalle'];
                            $meta = $inf['metaOficial'];
                            $filas = $inf['filas'];
                            $pares = $inf['pares'];
                            $idDet = (int)($det['id'] ?? 0);
                            $urlDetalle = "/Cycsa/publico/control-calidad/ver?id_detalle={$idDet}";

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
                        ?>
                            <!-- Fila Principal del Control (Clicable con Redirección a Detalle) -->
                            <tr class="row-main row-control" 
                                id="row-control-<?= $idDet ?>" 
                                onclick="window.location.href='<?= $urlDetalle ?>'"
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
                                    <a href="<?= $urlDetalle ?>" class="btn-ver-control" onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Informe
                                    </a>
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

            const coincideTexto = !texto || searchData.includes(texto);
            const coincideEstado = (estado === 'todos') || (filaEstado === estado);

            if (coincideTexto && coincideEstado) {
                fila.style.display = 'table-row';
            } else {
                fila.style.display = 'none';
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
</script>
