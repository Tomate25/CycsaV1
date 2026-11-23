<?php
// Vista principal - Listado Unificado de Órdenes de Servicio & Hojas de Recepción (CYCSA ERP Premium Style)
$csrfToken = (string)($csrfToken ?? ($_SESSION['csrf_token'] ?? ''));
?>
<link rel="stylesheet" href="/Cycsa/publico/css/rtfm13_fullscreen.css">
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-dark: #0c2766;
        --cycsa-azul-light: #eff6ff;
    }

    /* TOOLBAR Y PESTAÑAS RÁPIDAS (ESTILO LIMS/OPERACIONES) */
    .lims-toolbar-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 2px solid #f1f5f9;
    }
    .lims-quick-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .quick-tab-link {
        padding: 8px 14px;
        min-height: 36px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        color: #475569;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: all 0.2s;
    }
    .quick-tab-link:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .quick-tab-link.active {
        background: #103487;
        color: white;
        border-color: #103487;
        box-shadow: 0 2px 6px rgba(16, 52, 135, 0.2);
    }
    .quick-tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        background: rgba(0, 0, 0, 0.08);
        color: inherit;
    }
    .quick-tab-link.active .quick-tab-badge {
        background: rgba(255, 255, 255, 0.25);
        color: white;
    }
    .os-table-container {
        overflow-x: auto;
        min-height: 340px;
    }
    .os-acciones-menu {
        font-size: 13px;
        min-width: 220px;
        max-height: 380px;
        overflow-y: auto;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 6px 0;
        z-index: 1065 !important;
    }

    /* MÓDULO O/S: TOKENS DE DISEÑO Y TIPOGRAFÍA (100% ANCHO DE PANTALLA) */
    .os-modulo-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background: white;
        padding: 28px;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        margin-bottom: 35px;
    }

    .tabla-cycsa { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0; 
        margin-top: 15px; 
        font-size: 14px; 
        background: white; 
        border-radius: 10px; 
        overflow: visible; 
        border: 1px solid #e2e8f0; 
    }
    .tabla-cycsa th { 
        background-color: #f8fafc; 
        color: #475569; 
        padding: 14px 18px; 
        text-align: left; 
        font-weight: 700; 
        border-bottom: 2px solid #e2e8f0; 
        text-transform: uppercase; 
        font-size: 12px; 
        letter-spacing: 0.5px; 
    }
    .tabla-cycsa td { 
        padding: 16px 18px; 
        border-bottom: 1px solid #f1f5f9; 
        vertical-align: middle; 
        color: #1e293b; 
    }
    
    .fila-principal { cursor: pointer; transition: background-color 0.2s; }
    .fila-principal:hover { background-color: #f8fafc; }
    .fila-principal.expandida { background-color: #f0f7ff !important; }
    
    /* BADGES Y PILLS ESTANDARIZADAS */
    .badge-premium { 
        padding: 6px 12px; 
        border-radius: 6px; 
        font-size: 12px; 
        font-weight: 700; 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
        line-height: 1.2;
    }
    .badge-muestreo-no { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .badge-muestreo-si { background-color: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }
    .badge-muestreo-fin { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    
    .badge-hoja-sin { background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
    .badge-hoja-borrador { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-hoja-revision { background-color: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }
    .badge-hoja-observada { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .badge-hoja-aprobada { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }

    /* BOTONES ESTANDARIZADOS (ALTURA MÍNIMA 38-40PX) */
    .btn-cycsa { 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        gap: 8px; 
        border: 1px solid transparent; 
        min-height: 38px;
        padding: 8px 16px; 
        border-radius: 6px; 
        font-size: 13px; 
        font-weight: 600; 
        font-family: 'Inter', sans-serif; 
        cursor: pointer; 
        transition: all 0.2s; 
        text-decoration: none; 
        box-sizing: border-box;
    }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; border-color: var(--cycsa-azul); }
    .btn-cycsa-primary:hover { background: var(--cycsa-azul-dark); color: white; border-color: var(--cycsa-azul-dark); transform: translateY(-1px); }
    .btn-cycsa-secondary { background: #f8fafc; color: #334155; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #e2e8f0; color: #0f172a; border-color: #94a3b8; }
    .btn-cycsa-warning { background: #f59e0b; color: white; border-color: #f59e0b; }
    .btn-cycsa-warning:hover { background: #d97706; color: white; border-color: #d97706; }
    .btn-cycsa-success { background: #10b981; color: white; border-color: #10b981; }
    .btn-cycsa-success:hover { background: #059669; color: white; border-color: #059669; }
    .btn-cycsa-danger { background: #ef4444; color: white; border-color: #ef4444; }
    .btn-cycsa-danger:hover { background: #dc2626; color: white; border-color: #dc2626; }

    /* DROPDOWN MENU DE ACCIONES SECUNDARIAS */
    .dropdown-toggle-no-caret::after { display: none !important; }
    .dropdown-btn-mas {
        min-height: 38px;
        min-width: 38px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .dropdown-btn-mas:hover, .dropdown-btn-mas:focus {
        background: #f1f5f9;
        color: var(--cycsa-azul);
        border-color: #94a3b8;
    }

    .search-bar-premium { 
        display: flex; 
        gap: 12px; 
        align-items: center; 
        background: #f8fafc; 
        padding: 14px 18px; 
        border-radius: 10px; 
        border: 1px solid #e2e8f0; 
        margin-bottom: 22px; 
        flex-wrap: wrap; 
    }
    .form-control-cycsa { 
        padding: 9px 14px; 
        border: 1px solid #cbd5e1; 
        border-radius: 6px; 
        font-family: 'Inter', sans-serif; 
        font-size: 14px; 
        transition: border-color 0.2s; 
        width: 100%; 
        box-sizing: border-box; 
    }
    .form-control-cycsa:focus { 
        outline: none; 
        border-color: var(--cycsa-azul); 
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); 
    }

    /* FILA DESPLEGABLE CON ACORDEÓN EN 2 COLUMNAS (CERO DUPLICADOS) */
    .fila-desplegable { display: none; background-color: #f8fafc; }
    .fila-desplegable.mostrar { display: table-row; }
    .acordeon-contenido { padding: 22px 26px; border-bottom: 2px solid #cbd5e1; }
    
    .grid-info-os { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 22px; 
        margin-bottom: 0; 
    }
    @media (max-width: 992px) {
        .grid-info-os { grid-template-columns: 1fr; }
    }

    .tarjeta-detalle-os { 
        background: white; 
        border-radius: 10px; 
        padding: 18px 20px; 
        border: 1px solid #e2e8f0; 
        box-shadow: 0 2px 6px rgba(0,0,0,0.02); 
    }
    .tarjeta-detalle-titulo { 
        font-family: 'Outfit', sans-serif; 
        font-size: 13.5px; 
        font-weight: 700; 
        color: var(--cycsa-azul); 
        border-bottom: 1.5px solid #f1f5f9; 
        padding-bottom: 10px; 
        margin-bottom: 14px; 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
    }

    .dato-fila { display: flex; font-size: 13px; margin-bottom: 9px; color: #334155; line-height: 1.45; }
    .dato-etiqueta { font-weight: 700; color: #64748b; min-width: 125px; }
    .dato-valor { flex: 1; color: #0f172a; }

    .chevron-icon { transition: transform 0.25s ease; color: #94a3b8; font-size: 13px; }
    .fila-principal.expandida .chevron-icon { transform: rotate(90deg); color: var(--cycsa-azul); }

    /* ESTILOS DE MODAL Y FORMULARIO */
    .modal-premium { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
    .modal-premium-content { width: 96%; max-width: 1540px; max-height: 92vh; overflow-y: auto; padding: 22px 28px; background: white; margin: 1.5% auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
</style>

<div class="os-modulo-container">
    
    <?php if (!empty($_SESSION['exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius:10px; font-size:14px; font-weight:600; box-shadow:0 2px 6px rgba(16,185,129,0.15);">
            <i class="fa-solid fa-circle-check me-2"></i> <?= $_SESSION['exito'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['exito']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius:10px; font-size:14px; font-weight:600; box-shadow:0 2px 6px rgba(239,68,68,0.15);">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- CABECERA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 24px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-file-contract" style="color: var(--cycsa-azul);"></i> Órdenes de Servicio & Hojas de Recepción
            </h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px; margin-bottom: 0;">
                Gestión integral del formato oficial <strong>CYCSA-RG-FM-39 V1</strong>, logística de muestreo en campo y registro de especímenes <strong>CYCSA-RT-FM-13</strong>.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/Cycsa/publico/cotizaciones" class="btn-cycsa btn-cycsa-secondary">
                <i class="fa-solid fa-arrow-left"></i> Ir a Cotizaciones
            </a>
        </div>
    </div>

    <!-- PESTAÑAS DE ESTADO (QUICK TABS SPRINT F) -->
    <div class="lims-toolbar-top">
        <div class="lims-quick-tabs">
            <a href="/Cycsa/publico/ordenes-servicio?tab=activas<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" class="quick-tab-link <?= ($tabActiva === 'activas') ? 'active' : '' ?>">
                <i class="fa-solid fa-bolt"></i> Órdenes Activas
                <span class="quick-tab-badge"><?= $conteosTabs['activas'] ?? 0 ?></span>
            </a>
            <a href="/Cycsa/publico/ordenes-servicio?tab=muestreo<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" class="quick-tab-link <?= ($tabActiva === 'muestreo') ? 'active' : '' ?>">
                <i class="fa-solid fa-truck-pickup"></i> Requiere Muestreo
                <span class="quick-tab-badge"><?= $conteosTabs['muestreo'] ?? 0 ?></span>
            </a>
            <a href="/Cycsa/publico/ordenes-servicio?tab=historico<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" class="quick-tab-link <?= ($tabActiva === 'historico') ? 'active' : '' ?>">
                <i class="fa-solid fa-box-archive"></i> Histórico / Finalizadas
                <span class="quick-tab-badge"><?= $conteosTabs['historico'] ?? 0 ?></span>
            </a>
            <a href="/Cycsa/publico/ordenes-servicio?tab=todas<?= !empty($busqueda) ? '&q=' . urlencode($busqueda) : '' ?>" class="quick-tab-link <?= ($tabActiva === 'todas') ? 'active' : '' ?>">
                <i class="fa-solid fa-list-ul"></i> Todas
                <span class="quick-tab-badge"><?= $conteosTabs['todas'] ?? 0 ?></span>
            </a>
        </div>
    </div>

    <!-- BUSCADOR Y FILTROS -->
    <form method="GET" action="/Cycsa/publico/ordenes-servicio" class="search-bar-premium" style="display:flex; flex-direction:column; gap:12px;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActiva ?? 'activas', ENT_QUOTES, 'UTF-8') ?>">
        <div style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 280px; position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                <input type="text" name="q" placeholder="Buscar por código O/S, cotización, cliente o proyecto..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control-cycsa" style="padding-left: 38px; min-height: 40px;">
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="min-height: 40px; padding: 8px 18px;"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                <?php if(!empty($busqueda) || !empty($filtros['estado']) || !empty($filtros['desde']) || !empty($filtros['hasta'])): ?>
                    <a href="/Cycsa/publico/ordenes-servicio?tab=<?= urlencode($tabActiva ?? 'activas') ?>" class="btn-cycsa btn-cycsa-secondary" style="min-height: 40px; padding: 8px 16px;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
                <?php endif; ?>
            </div>
        </div>
        <!-- FILTROS SECUNDARIOS SPRINT F -->
        <div style="display: flex; gap: 14px; width: 100%; flex-wrap: wrap; align-items: center; font-size: 13px; color: #475569; padding-top: 4px;">
            <div style="display:flex; align-items:center; gap:6px;">
                <label for="filtro_estado" style="font-weight:600; white-space:nowrap;"><i class="fa-solid fa-filter me-1 text-secondary"></i> Estado:</label>
                <select name="estado" id="filtro_estado" class="form-control-cycsa" style="width: auto; padding: 6px 12px; min-height: 36px; font-size: 13px;">
                    <option value="">-- Todos los estados --</option>
                    <option value="Pendiente" <?= (($filtros['estado'] ?? '') === 'Pendiente') ? 'selected' : '' ?>>Pendiente / Por Aprobar</option>
                    <option value="En Proceso" <?= (($filtros['estado'] ?? '') === 'En Proceso') ? 'selected' : '' ?>>En Proceso</option>
                    <option value="Finalizado" <?= (($filtros['estado'] ?? '') === 'Finalizado') ? 'selected' : '' ?>>Finalizado</option>
                    <option value="Archivado" <?= (($filtros['estado'] ?? '') === 'Archivado') ? 'selected' : '' ?>>Archivado</option>
                </select>
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <label for="filtro_desde" style="font-weight:600; white-space:nowrap;"><i class="fa-regular fa-calendar me-1 text-secondary"></i> Desde:</label>
                <input type="date" name="desde" id="filtro_desde" value="<?= htmlspecialchars($filtros['desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control-cycsa" style="width: auto; padding: 6px 10px; min-height: 36px; font-size: 13px;">
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <label for="filtro_hasta" style="font-weight:600; white-space:nowrap;">Hasta:</label>
                <input type="date" name="hasta" id="filtro_hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control-cycsa" style="width: auto; padding: 6px 10px; min-height: 36px; font-size: 13px;">
            </div>
        </div>
    </form>

    <!-- TABLA DE REGISTROS CON DESPLIEGUE INTELIGENTE (5 COLUMNAS DESDENSIFICADAS) -->
    <div class="os-table-container">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 230px;">Código O/S / Cotización</th>
                    <th>Cliente / Proyecto</th>
                    <th style="width: 125px;">Emisión</th>
                    <th style="width: 270px;">Estado Operativo</th>
                    <th style="text-align: right; width: 240px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ordenes)): ?>
                    <?php foreach ($ordenes as $os): 
                        $esFinalizadaOArchivo = in_array($os['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true);
                    ?>
                        <?php
                        // Detectar si alguna muestra de las hojas registradas tiene prefijo MC o MS
                        $tieneHojasOS = !empty($os['id_hoja']) || !empty($os['hojas_servicio']);
                        $tieneMuestrasMC = false;
                        $tieneMuestrasMS = false;
                        if (!empty($os['muestras_json'])) {
                            $mArr = json_decode($os['muestras_json'], true) ?: [];
                            foreach ($mArr as $m) {
                                $c = strtoupper(trim($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? '')));
                                if (str_starts_with($c, 'MC-')) $tieneMuestrasMC = true;
                                if (str_starts_with($c, 'MS-')) $tieneMuestrasMS = true;
                            }
                        }
                        if (!empty($os['hojas_servicio'])) {
                            foreach ($os['hojas_servicio'] as $hServ) {
                                $mArr = json_decode($hServ['muestras_json'] ?? '[]', true) ?: [];
                                foreach ($mArr as $m) {
                                    $c = strtoupper(trim($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? '')));
                                    if (str_starts_with($c, 'MC-')) $tieneMuestrasMC = true;
                                    if (str_starts_with($c, 'MS-')) $tieneMuestrasMS = true;
                                }
                            }
                        }

                        // Determinar estado de muestreo para Casos A, B y C
                        $estadoMuestreo = 'sin_decidir';
                        if (!empty($os['id_pm'])) {
                            if (in_array($os['estado_muestreo'], ['Programado', 'En Proceso', 'En Campo'])) {
                                $estadoMuestreo = 'en_proceso';
                            } else {
                                $estadoMuestreo = 'muestreo_campo_finalizado';
                            }
                        } elseif ($tieneMuestrasMC || $os['requiere_muestreo'] === 1 || $os['requiere_muestreo'] === '1') {
                            if ($tieneHojasOS) {
                                $estadoMuestreo = 'muestreo_campo_finalizado';
                            } else {
                                $estadoMuestreo = 'pendiente_programar';
                            }
                        } elseif ($tieneMuestrasMS || $os['requiere_muestreo'] === 0 || $os['requiere_muestreo'] === '0') {
                            $estadoMuestreo = 'no_aplica';
                        }

                        // Estado de la Hoja RT-FM-13 y aprobación general
                        $tieneHoja = !empty($os['id_hoja']);
                        $estadoOS = $os['estado'];
                        $cantHojas = count($os['hojas_servicio'] ?? []);

                        // Gate duro: La O/S debe estar formalmente aprobada (o en flujo operativo posterior)
                        $esOSAprobada = esOrdenServicioAprobada($os['estado'] ?? null);

                        $ensayosAprobarModal = [];
                        foreach (($os['items'] ?? []) as $itEns) {
                            $idDetalleEns = (int)($itEns['id'] ?? 0);
                            if ($idDetalleEns <= 0) {
                                continue;
                            }
                            $ensayosAprobarModal[] = [
                                'id' => $idDetalleEns,
                                'nombre' => (string)($itEns['descripcion_ensayo'] ?? 'Ensayo'),
                                'norma' => (string)($itEns['norma_astm'] ?? ''),
                                'codigo' => (string)($itEns['codigo_servicio'] ?? ''),
                            ];
                        }
                        $ensayosAprobarJson = htmlspecialchars(
                            json_encode($ensayosAprobarModal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                        <!-- FILA PRINCIPAL -->
                        <tr class="fila-principal" id="fila-os-<?= $os['id'] ?>" onclick="toggleFilaDesplegable(<?= $os['id'] ?>, event)">
                            
                            <!-- COLUMNA 1: CÓDIGO O/S Y COTIZACIÓN -->
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: var(--cycsa-azul); flex-shrink: 0;" title="Clic para expandir detalles">
                                        <i class="fa-solid fa-chevron-right chevron-icon" id="icon-chevron-<?= $os['id'] ?>"></i>
                                    </span>
                                    <div>
                                        <strong style="font-family: monospace; font-size: 14.5px; color: var(--cycsa-azul); display: block; letter-spacing: 0.3px;">
                                            <?= htmlspecialchars($os['codigo_os']) ?>
                                            <span style="background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; font-family: 'Inter', sans-serif; font-size: 10.5px; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: 4px; vertical-align: middle;">v<?= (int)($os['version'] ?? 1) ?></span>
                                        </strong>
                                        <span style="font-family: monospace; font-size: 11.5px; color: #475569; background: #f8fafc; padding: 2px 7px; border-radius: 4px; border: 1px solid #e2e8f0; display: inline-block; margin-top: 3px;">
                                            <i class="fa-solid fa-file-invoice" style="font-size: 10.5px; color:#64748b;"></i> <?= htmlspecialchars($os['cotizacion_codigo'] ?? 'N/A') ?>
                                        </span>
                                        <?php
                                        $vigOS = $os['vigencia_contrato'] ?? [];
                                        $esLargoOS = !empty($os['es_multimes']) || !empty($vigOS['es_contrato_largo']);
                                        if ($esLargoOS):
                                            $estVig = $vigOS['estado_vigencia'] ?? 'vigente';
                                            $bgVig = $estVig === 'vencido' ? '#fee2e2' : ($estVig === 'por_vencer' ? '#fef3c7' : '#e0e7ff');
                                            $colVig = $estVig === 'vencido' ? '#991b1b' : ($estVig === 'por_vencer' ? '#92400e' : '#3730a3');
                                            $brdVig = $estVig === 'vencido' ? '#fecaca' : ($estVig === 'por_vencer' ? '#fde68a' : '#c7d2fe');
                                            $txtPlazo = !empty($vigOS['duracion_meses']) ? ((int)$vigOS['duracion_meses'] . 'm') : ($os['periodicidad'] ?? 'Mensual');
                                            $txtFinCorto = !empty($vigOS['fecha_fin']) ? date('d/m/y', strtotime($vigOS['fecha_fin'])) : '';
                                            $diasRest = $vigOS['dias_restantes'] ?? null;
                                        ?>
                                            <button type="button"
                                                    onclick="event.stopPropagation(); abrirModalPeriodoContrato(<?= $os['id'] ?>)"
                                                    class="badge-premium"
                                                    style="background: <?= $bgVig ?>; color: <?= $colVig ?>; border: 1px solid <?= $brdVig ?>; font-size: 10.5px; padding: 2px 7px; margin-top: 3px; display: inline-flex; cursor: pointer;"
                                                    title="<?= htmlspecialchars(($vigOS['etiqueta_corta'] ?? 'Contrato Largo') . ($diasRest !== null ? " ({$diasRest} días restantes)" : '')) ?>">
                                                <i class="fa-solid fa-repeat"></i> Contrato <?= htmlspecialchars($txtPlazo) ?><?= $txtFinCorto !== '' ? ' · Fin ' . $txtFinCorto : '' ?>
                                            </button>
                                        <?php elseif (!$esFinalizadaOArchivo): ?>
                                            <button type="button"
                                                    onclick="event.stopPropagation(); abrirModalPeriodoContrato(<?= $os['id'] ?>)"
                                                    style="background: #f8fafc; color: #475569; border: 1px dashed #cbd5e1; font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 5px; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer;"
                                                    title="Definir período mensual / contrato largo">
                                                <i class="fa-regular fa-calendar-plus"></i> Período
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- COLUMNA 2: CLIENTE Y PROYECTO -->
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 14px; line-height: 1.3;">
                                    <?= htmlspecialchars($os['cliente_nombre']) ?>
                                </div>
                                <div style="color: #64748b; font-size: 12.5px; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-map-pin" style="color: #94a3b8; font-size: 11px;"></i>
                                    <span><?= htmlspecialchars($os['nombre_proyecto'] ?? 'Sin nombre de proyecto') ?></span>
                                </div>
                            </td>

                            <!-- COLUMNA 3: EMISIÓN -->
                            <td style="color: #334155; font-weight: 600; font-size: 13.5px; white-space: nowrap;">
                                <i class="fa-regular fa-calendar" style="color: #94a3b8; margin-right: 4px;"></i>
                                <?= date('d/m/Y', strtotime($os['fecha_emision'])) ?>
                            </td>

                            <!-- COLUMNA 4: ESTADO OPERATIVO (VISITA + HOJA RT-FM-13) -->
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <!-- Pill 1: Modalidad / Muestreo -->
                                    <div>
                                        <?php if ($estadoMuestreo === 'en_proceso'): ?>
                                            <span class="badge-premium badge-muestreo-si" title="Técnico en campo">
                                                <i class="fa-solid fa-truck-pickup"></i> En Campo
                                            </span>
                                            <?php if (!empty($os['tecnico_nombre'])): ?>
                                                <span style="color: #475569; font-size: 11.5px; font-weight: 500; margin-left: 4px;">(<?= htmlspecialchars($os['tecnico_nombre']) ?>)</span>
                                            <?php endif; ?>
                                        <?php elseif ($estadoMuestreo === 'muestreo_campo_finalizado' || $estadoMuestreo === 'finalizado'): ?>
                                            <span class="badge-premium badge-muestreo-fin" title="Muestreo en obra registrado y recibido en laboratorio">
                                                <i class="fa-solid fa-circle-check"></i> <?= !empty($os['id_pm']) ? 'Muestreo Finalizado' : 'Muestreo en Campo (MC)' ?>
                                            </span>
                                            <?php 
                                            $tecBadge = !empty($os['tecnico_nombre']) ? $os['tecnico_nombre'] : (!empty($os['nombre_persona_toma_muestra']) ? $os['nombre_persona_toma_muestra'] : '');
                                            if (!empty($tecBadge)): ?>
                                                <span style="color: #475569; font-size: 11.5px; font-weight: 500; margin-left: 4px;">(<?= htmlspecialchars($tecBadge) ?>)</span>
                                            <?php endif; ?>
                                        <?php elseif ($estadoMuestreo === 'pendiente_programar'): ?>
                                            <span class="badge-premium badge-hoja-borrador" title="Requiere asignación de salida a campo">
                                                <i class="fa-solid fa-calendar-plus"></i> Requiere Muestreo
                                            </span>
                                        <?php elseif ($estadoMuestreo === 'no_aplica'): ?>
                                            <span class="badge-premium badge-muestreo-no" title="Entrega de muestras directamente en ventanilla (MS)">
                                                <i class="fa-solid fa-flask"></i> Ingreso Directo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-premium" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;" title="Modalidad por definir al emitir hoja">
                                                <i class="fa-solid fa-circle-question"></i> Por Definir
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Pill 2: Estado de Hoja RT-FM-13 -->
                                    <div>
                                        <?php if ($esFinalizadaOArchivo): ?>
                                            <?php if ($cantHojas > 0): ?>
                                                <span class="badge-premium badge-hoja-aprobada">
                                                    <i class="fa-solid fa-file-circle-check"></i> <?= $cantHojas ?> Hoja(s) Registrada(s) (<?= htmlspecialchars($os['estado']) ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-premium badge-hoja-aprobada">
                                                    <i class="fa-solid fa-box-archive"></i> <?= htmlspecialchars($os['estado']) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif (!$esOSAprobada): ?>
                                            <span class="badge-premium" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                                <i class="fa-solid fa-hourglass-half"></i> O/S Por Aprobar
                                            </span>
                                        <?php elseif ($cantHojas === 0): ?>
                                            <span class="badge-premium badge-hoja-sin">
                                                <i class="fa-solid fa-file-circle-plus"></i> Sin Hoja RT-FM-13
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-premium badge-hoja-aprobada">
                                                <i class="fa-solid fa-file-circle-check"></i> <?= $cantHojas ?> Hoja(s) Registrada(s)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- COLUMNA 5: ACCIÓN PRIMARIA + MENÚ DESPLEGABLE MÁS -->
                            <td style="text-align: right;" onclick="event.stopPropagation();">
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                    
                                    <!-- BOTÓN PRIMARIO SEGÚN EL SIGUIENTE PASO LÓGICO -->
                                    <?php if ($esFinalizadaOArchivo): ?>
                                        <?php if ($cantHojas > 0): ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; font-weight:600; border-color:#cbd5e1;" onclick="toggleFilaDesplegable(<?= $os['id'] ?>, event)" title="Ver Hojas RT-FM-13 registradas">
                                                <i class="fa-solid fa-layer-group" style="color:var(--cycsa-azul);"></i> Ver Hojas (<?= $cantHojas ?>)
                                            </button>
                                        <?php else: ?>
                                            <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; font-weight:600; border-color:#cbd5e1;">
                                                <i class="fa-solid fa-file-lines" style="color:var(--cycsa-azul);"></i> Ver O/S
                                            </a>
                                        <?php endif; ?>
                                    <?php elseif (!$esOSAprobada): ?>
                                        <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-primary" style="background:#059669; border-color:#059669; min-height:38px; font-weight:700;" 
                                                    data-ensayos="<?= $ensayosAprobarJson ?>"
                                                    onclick="abrirModalAprobarOS(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>', '<?= htmlspecialchars(addslashes($os['cliente_nombre'])) ?>', '<?= htmlspecialchars(addslashes($os['nombre_proyecto'] ?? '')) ?>', this)">
                                                <i class="fa-solid fa-check-double"></i> Aprobar O/S
                                            </button>
                                        <?php else: ?>
                                            <span class="btn-cycsa btn-cycsa-secondary" style="opacity:0.75; cursor:not-allowed; min-height:38px;" title="Pendiente de aprobación formal por supervisor">
                                                <i class="fa-solid fa-lock"></i> Por Aprobar
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($cantHojas === 0): ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-success" style="min-height:38px; font-weight:700;"
                                                    data-id-os="<?= $os['id'] ?>"
                                                    data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                    data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                    onclick="abrirModalHojaSolicitud(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0, true)">
                                                <i class="fa-solid fa-file-signature"></i> Registrar Hoja
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; font-weight:600; border-color:#cbd5e1;" onclick="toggleFilaDesplegable(<?= $os['id'] ?>, event)" title="Ver Hojas RT-FM-13 registradas">
                                                <i class="fa-solid fa-layer-group" style="color:var(--cycsa-azul);"></i> Ver Hojas (<?= $cantHojas ?>)
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- MENÚ DESPLEGABLE MÁS ACCIONES (BOOTSTRAP 5 DROPDOWN) -->
                                    <div class="dropdown" style="display: inline-block;">
                                        <button class="dropdown-btn-mas dropdown-toggle-no-caret" type="button" id="dropdownMenuOS<?= $os['id'] ?>" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Más opciones de la Orden de Servicio">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm os-acciones-menu" aria-labelledby="dropdownMenuOS<?= $os['id'] ?>">
                                            <?php if (!in_array($os['estado'], ['Finalizado', 'Archivado', 'Cerrado'], true)): ?>
                                                <li>
                                                    <a class="dropdown-item py-2" href="/Cycsa/publico/ordenes-servicio/editar?id_os=<?= $os['id'] ?>">
                                                        <i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Editar Orden de Servicio
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item py-2" onclick="abrirModalPeriodoContrato(<?= $os['id'] ?>)">
                                                        <i class="fa-solid fa-calendar-days me-2 text-primary"></i> Configurar Período / Contrato Largo
                                                    </button>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item py-2" href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $os['id'] ?>">
                                                    <i class="fa-solid fa-file-lines me-2" style="color:var(--cycsa-azul);"></i> Ver Documento O/S (FM-39)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="/Cycsa/publico/ordenes-servicio/descargar-pdf?id=<?= $os['id'] ?>" target="_blank">
                                                    <i class="fa-solid fa-file-pdf me-2 text-danger"></i> Descargar PDF O/S (RG-FM-39)
                                                </a>
                                            </li>
                                            <?php if (!empty($os['id_pm'])): ?>
                                                <li>
                                                    <a class="dropdown-item py-2" href="/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=<?= $os['id'] ?>" target="_blank">
                                                        <i class="fa-solid fa-print me-2 text-secondary"></i> Checklist Campo (FM-40 B)
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>">
                                                        <i class="fa-solid fa-pen-to-square me-2 text-secondary"></i> Ver/Editar Datos Muestreo
                                                    </a>
                                                </li>
                                            <?php elseif ($os['requiere_muestreo'] === 1 || $os['requiere_muestreo'] === '1'): ?>
                                                <li>
                                                    <a class="dropdown-item py-2" href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>">
                                                        <i class="fa-solid fa-truck-pickup me-2 text-primary"></i> Programar Muestreo en Campo
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($esOSAprobada && !$esFinalizadaOArchivo): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item py-2"
                                                            data-id-os="<?= $os['id'] ?>"
                                                            data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                            data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                            data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                            data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                            data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                            onclick="abrirModalHojaSolicitud(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>', 0, true)">
                                                        <i class="fa-solid fa-plus-circle me-2 text-success"></i> + Nueva Hoja RT-FM-13
                                                    </button>
                                                </li>
                                                <?php if ($cantHojas > 0): ?>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank">
                                                            <i class="fa-solid fa-file-pdf me-2 text-danger"></i> Descargar PDF Oficial
                                                        </a>
                                                    </li>
                                                    <?php if (!empty($os['id_hoja'])): ?>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= (int)$os['id_hoja'] ?>" target="_blank">
                                                            <i class="fa-solid fa-flask me-2" style="color:#7c3aed;"></i> Hoja RT-FM-60 (Laboratorio)
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php elseif ($esFinalizadaOArchivo && $cantHojas > 0): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank">
                                                        <i class="fa-solid fa-file-pdf me-2 text-danger"></i> Descargar PDF Oficial
                                                    </a>
                                                </li>
                                            <?php elseif (!$esFinalizadaOArchivo): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item py-2 text-muted disabled" disabled style="opacity: 0.6; cursor: not-allowed;" title="La Orden de Servicio debe estar en estado Aprobada para registrar Hojas de Solicitud (CYCSA-RT-FM-13)">
                                                        <i class="fa-solid fa-lock me-2 text-muted"></i> + Nueva Hoja RT-FM-13 (Requiere Aprobación)
                                                    </button>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <a class="dropdown-item py-2" href="/Cycsa/publico/operaciones/ver?id_os=<?= $os['id'] ?>">
                                                    <i class="fa-solid fa-arrow-up-right-from-square me-2 text-primary"></i> Ver en Operaciones (Página Completa)
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                </div>
                            </td>
                        </tr>

                        <!-- FILA DESPLEGABLE CON DETALLES COMPLETOS (ACORDEÓN RESPONSIVO EN 2 COLUMNAS) -->
                        <tr class="fila-desplegable" id="desplegable-os-<?= $os['id'] ?>">
                            <td colspan="5" style="padding: 0;">
                                <div class="acordeon-contenido">
                                    <div class="grid-info-os">
                                        
                                        <!-- COLUMNA IZQUIERDA: COMERCIAL Y LOGÍSTICA DE MUESTREO -->
                                        <div style="display: flex; flex-direction: column; gap: 20px;">
                                            
                                            <!-- TARJETA 1: DATOS COMERCIALES Y PROYECTO -->
                                            <div class="tarjeta-detalle-os">
                                                <div class="tarjeta-detalle-titulo">
                                                    <i class="fa-solid fa-building"></i> 1. Información Comercial
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Cliente:</span>
                                                    <span class="dato-valor"><strong><?= htmlspecialchars($os['cliente_nombre']) ?></strong></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">RUC/Cédula:</span>
                                                    <span class="dato-valor" style="font-family:monospace;"><?= htmlspecialchars($os['cliente_rfc'] ?: 'No registrado') ?></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Atención a:</span>
                                                    <span class="dato-valor"><?= htmlspecialchars($os['atencion_a'] ?: 'N/A') ?></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Proyecto:</span>
                                                    <span class="dato-valor"><?= htmlspecialchars($os['nombre_proyecto'] ?: 'N/A') ?></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Cotización:</span>
                                                    <span class="dato-valor"><a href="/Cycsa/publico/cotizaciones" style="color:var(--cycsa-azul); font-weight:700; text-decoration:none; font-family:monospace;"><?= htmlspecialchars($os['cotizacion_codigo'] ?? 'N/A') ?></a></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Forma Pago:</span>
                                                    <span class="dato-valor"><?= htmlspecialchars($os['forma_pago'] ?? 'Pago contra entrega') ?></span>
                                                </div>
                                                <div class="dato-fila" style="align-items:center;">
                                                    <span class="dato-etiqueta">Vigencia / Período:</span>
                                                    <span class="dato-valor" style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                                                        <?php if (!empty($vigOS['es_contrato_largo'])): ?>
                                                            <span style="font-weight:700; color:#3730a3; font-size:12.5px;">
                                                                <i class="fa-solid fa-repeat me-1"></i><?= htmlspecialchars($vigOS['etiqueta_corta'] ?? 'Contrato Largo') ?>
                                                                <?php if (isset($vigOS['dias_restantes']) && $vigOS['dias_restantes'] !== null): ?>
                                                                    <small style="font-weight:600; color:<?= $vigOS['dias_restantes'] < 0 ? '#dc2626' : ($vigOS['dias_restantes'] <= 15 ? '#d97706' : '#059669') ?>;">
                                                                        (<?= $vigOS['dias_restantes'] >= 0 ? 'Faltan ' . (int)$vigOS['dias_restantes'] . ' días' : 'Vencido hace ' . abs((int)$vigOS['dias_restantes']) . ' días' ?>)
                                                                    </small>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="color:#64748b;">Puntual (Sin período mensual asignado)</span>
                                                        <?php endif; ?>
                                                        <?php if (!$esFinalizadaOArchivo): ?>
                                                            <button type="button" onclick="abrirModalPeriodoContrato(<?= $os['id'] ?>)" class="btn-cycsa btn-cycsa-secondary" style="min-height:28px; padding:3px 9px; font-size:11.5px;">
                                                                <i class="fa-solid fa-calendar-check text-primary"></i> Configurar Período
                                                            </button>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- TARJETA 2: LOGÍSTICA DE MUESTREO EN CAMPO -->
                                            <div class="tarjeta-detalle-os">
                                                <div class="tarjeta-detalle-titulo" style="display:flex; justify-content:space-between; align-items:center;">
                                                    <span><i class="fa-solid fa-truck-pickup"></i> 2. Logística de Muestreo / Visita</span>
                                                    <?php if (!empty($os['id_pm'])): ?>
                                                        <a href="/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary" style="min-height:30px; padding:4px 10px; font-size:11.5px;" title="Ver Formato CYCSA-RT-FM-40 B">
                                                            <i class="fa-solid fa-print"></i> FM-40 B
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($os['id_pm'])): ?>
                                                    <div class="dato-fila">
                                                        <span class="dato-etiqueta">Estado Visita:</span>
                                                        <span class="dato-valor">
                                                            <?php if ($os['estado_muestreo'] === 'Finalizado'): ?>
                                                                <strong style="color: #15803d; display: inline-flex; align-items: center; gap: 5px;">
                                                                    <i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Finalizado (Retornó al Lab)
                                                                </strong>
                                                            <?php else: ?>
                                                                <strong style="color: #0369a1; display: inline-flex; align-items: center; gap: 5px;">
                                                                    <i class="fa-solid fa-truck-pickup"></i> <?= htmlspecialchars($os['estado_muestreo'] ?? 'En Campo') ?>
                                                                </strong>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <div class="dato-fila">
                                                        <span class="dato-etiqueta">Técnico Muestreador:</span>
                                                        <span class="dato-valor"><strong><?= htmlspecialchars($os['tecnico_nombre'] ?? 'No asignado') ?></strong></span>
                                                    </div>
                                                    <div class="dato-fila">
                                                        <span class="dato-etiqueta">Vehículo:</span>
                                                        <span class="dato-valor"><?= htmlspecialchars($os['vehiculo_info'] ?? 'No asignado') ?></span>
                                                    </div>
                                                    <div class="dato-fila">
                                                        <span class="dato-etiqueta">Salida a Campo:</span>
                                                        <span class="dato-valor"><?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : 'N/A' ?></span>
                                                    </div>
                                                    <div class="dato-fila">
                                                        <span class="dato-etiqueta">Retorno al Lab:</span>
                                                        <span class="dato-valor"><?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : 'N/A' ?></span>
                                                    </div>
                                                    <?php if (!empty($os['lugar_muestreo'])): ?>
                                                        <div class="dato-fila">
                                                            <span class="dato-etiqueta">Lugar Muestreo:</span>
                                                            <span class="dato-valor"><?= htmlspecialchars($os['lugar_muestreo']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($os['observaciones_campo'])): ?>
                                                        <div style="font-size: 12px; background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 8px; color: #475569;">
                                                            <strong>Notas de Campo:</strong> <?= htmlspecialchars($os['observaciones_campo']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div style="margin-top: 12px; display:flex; gap:8px; flex-wrap:wrap;">
                                                        <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; font-size:12px; padding:6px 14px;"
                                                                onclick="abrirModalFm40B(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0)">
                                                            <i class="fa-solid fa-pen-to-square"></i> Gestionar / Cerrar 40 B
                                                        </button>
                                                        <a href="/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-primary" style="min-height:34px; font-size:12px; padding:6px 12px;">
                                                            <i class="fa-solid fa-print"></i> Imprimir FM-40 B
                                                        </a>
                                                    </div>
                                                <?php elseif ($tieneMuestrasMC || (($os['requiere_muestreo'] === 1 || $os['requiere_muestreo'] === '1') && $tieneHojasOS)): ?>
                                                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px; color: #1e40af; font-size: 13px;">
                                                        <i class="fa-solid fa-truck-pickup me-1"></i> <strong>Muestreo en Obra (Prefijo MC):</strong> Muestras recolectadas en campo y registradas en Hoja CYCSA-RT-FM-13.
                                                        <?php if (!empty($os['nombre_persona_toma_muestra'])): ?>
                                                            <div style="margin-top: 6px; font-size: 12px; color: #1e3a8a;">
                                                                <strong>Muestreador:</strong> <?= htmlspecialchars($os['nombre_persona_toma_muestra']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div style="margin-top: 10px; display:flex; gap:8px; flex-wrap:wrap;">
                                                            <button type="button" class="btn-cycsa btn-cycsa-primary" style="min-height:32px; padding: 4px 12px; font-size: 12px;"
                                                                    onclick="abrirModalFm40B(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0)">
                                                                <i class="fa-solid fa-truck-pickup"></i> Crear / Cerrar Doc. FM-40 B aquí
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php elseif ($os['requiere_muestreo'] === 1 || $os['requiere_muestreo'] === '1'): ?>
                                                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px; color: #1e40af; font-size: 13px;">
                                                        <i class="fa-solid fa-calendar-plus me-1"></i> <strong>Requiere salida a campo:</strong> Puede generar el documento FM-40 B aquí mismo y asociarlo a su RT-FM-13.
                                                        <div style="margin-top: 10px;">
                                                            <button type="button" class="btn-cycsa btn-cycsa-primary" style="min-height:36px; padding: 6px 14px; font-size: 12.5px;"
                                                                    onclick="abrirModalFm40B(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0)">
                                                                <i class="fa-solid fa-truck-pickup"></i> Crear Documento FM-40 B
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php elseif ($tieneMuestrasMS || $os['requiere_muestreo'] === 0 || $os['requiere_muestreo'] === '0'): ?>
                                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 14px; border-radius: 8px; color: #166534; font-size: 13px;">
                                                        <i class="fa-solid fa-flask me-1"></i> <strong>Ingreso Directo (Prefijo MS):</strong> Los especímenes son entregados por el cliente en ventanilla del laboratorio central.
                                                        <div style="margin-top: 8px;">
                                                            <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:30px; padding: 4px 10px; font-size: 11.5px;"
                                                                    onclick="abrirModalFm40B(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0)">
                                                                <i class="fa-solid fa-truck-pickup"></i> Crear Doc. FM-40 B (Opcional)
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 14px; border-radius: 8px; color: #92400e; font-size: 13px;">
                                                        <i class="fa-solid fa-circle-question me-1"></i> <strong>Modalidad Abierta:</strong> Elija MS o MC directamente dentro de cada Hoja RT-FM-13 o cree un documento FM-40 B.
                                                        <div style="margin-top: 8px;">
                                                            <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:30px; padding: 4px 10px; font-size: 11.5px;"
                                                                    onclick="abrirModalFm40B(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0)">
                                                                <i class="fa-solid fa-truck-pickup"></i> Crear Doc. FM-40 B
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                        </div>

                                        <!-- COLUMNA DERECHA: HOJAS RT-FM-13 Y MATRIZ TÉCNICA LIMS -->
                                        <div style="display: flex; flex-direction: column; gap: 20px;">
                                            
                                            <!-- TARJETA 3: HOJA TÉCNICA CYCSA-RT-FM-13 -->
                                            <div class="tarjeta-detalle-os">
                                                <div class="tarjeta-detalle-titulo" style="display:flex; justify-content:space-between; align-items:center;">
                                                    <span><i class="fa-solid fa-file-signature"></i> 3. Hojas de Servicio (RT-FM-13)</span>
                                                    <div style="display:flex; align-items:center; gap:8px;">
                                                        <?php if (!empty($os['tipo_contrato']) && in_array($os['tipo_contrato'], ['Marco', 'Contrato Activo', 'Abierto'])): ?>
                                                            <span style="font-size:10.5px; background:#fef3c7; color:#92400e; padding:3px 8px; border-radius:6px; text-transform:uppercase; font-weight:700;">
                                                                <i class="fa-solid fa-calendar-days"></i> Contrato Activo
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (($esOSAprobada || $esFinalizadaOArchivo) && $cantHojas > 0): ?>
                                                            <?php if (!$esFinalizadaOArchivo): ?>
                                                                <button type="button" class="btn-cycsa btn-cycsa-success" style="min-height:30px; padding:4px 10px; font-size:11.5px; font-weight:700;"
                                                                        onclick="abrirModalHojaSolicitud(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0, true)">
                                                                    <i class="fa-solid fa-plus"></i> Nueva Hoja
                                                                </button>
                                                            <?php endif; ?>
                                                            <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary" style="min-height:30px; padding:4px 10px; font-size:11.5px;" title="Descargar PDF Oficial">
                                                                <i class="fa-solid fa-file-pdf text-danger"></i> PDF
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- ESTADOS DE REVISIÓN Y OBSERVACIÓN SUPERVISORIA -->
                                                <?php if ($os['estado'] === 'Estado 2: Observada'): ?>
                                                    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                                                        <div style="color:#991b1b; font-size:12.5px; font-weight:600;">
                                                            <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Hoja Observada por Supervisión
                                                        </div>
                                                        <div style="display:flex; gap:6px;">
                                                            <button type="button" class="btn-cycsa btn-cycsa-warning" style="min-height:32px; padding:4px 10px; font-size:12px;"
                                                                    data-id-os="<?= $os['id'] ?>"
                                                                    data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                                    data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                                    data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                                    data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                                    data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                                    onclick="iniciarRegistroHojaRTFM13(this)">
                                                                <i class="fa-solid fa-pen"></i> Corregir
                                                            </button>
                                                            <form method="POST" action="/Cycsa/publico/hojas-servicio/enviar-revision" style="margin: 0;">
                                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                                <input type="hidden" name="id_os" value="<?= $os['id'] ?>">
                                                                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#4f46e5; border-color:#4f46e5; min-height:32px; padding:4px 10px; font-size:12px;">
                                                                    <i class="fa-solid fa-paper-plane"></i> Reenviar a Revisión
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php elseif ($os['estado'] === 'Estado 2: Revision'): ?>
                                                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                                                        <div style="color:#1e40af; font-size:12.5px; font-weight:600;">
                                                            <i class="fa-solid fa-clock me-1"></i> En Revisión por Supervisión
                                                        </div>
                                                        <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                                            <button type="button" class="btn-cycsa btn-cycsa-primary" style="background:#059669; border-color:#059669; min-height:32px; padding:4px 12px; font-size:12px;" onclick="abrirModalRevision(<?= $os['id'] ?>, '<?= $os['codigo_os'] ?>')">
                                                                <i class="fa-solid fa-check-double"></i> Decisión Supervisor
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php 
                                                $listaHojas = $os['hojas_servicio'] ?? [];
                                                if (empty($listaHojas) && $tieneHoja) {
                                                    $listaHojas = [[
                                                        'id' => $os['id_hoja'],
                                                        'numero_registro' => '01',
                                                        'codigo_documento' => $os['hoja_codigo'] ?? 'CYCSA-RT-FM-13',
                                                        'nombre_persona_toma_muestra' => $os['nombre_persona_toma_muestra'] ?? 'Cliente',
                                                        'fecha_hora_toma_muestra' => $os['fecha_hora_toma_muestra'] ?? '',
                                                        'total_muestras' => !empty($os['muestras_json']) ? count(json_decode($os['muestras_json'], true) ?: []) : 1
                                                    ]];
                                                }
                                                ?>
                                                <?php if (!$esOSAprobada && !$esFinalizadaOArchivo): ?>
                                                    <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 18px; border-radius: 8px; text-align: center; color: #92400e; font-size: 13px;">
                                                        <i class="fa-solid fa-lock" style="font-size: 26px; margin-bottom: 8px; color: #d97706; display: block;"></i>
                                                        <strong>Aprobación de O/S Requerida</strong><br>
                                                        Esta orden de servicio aún no ha sido aprobada. Debe aprobarse formalmente antes de registrar especímenes RT-FM-13.
                                                        <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                                            <div style="margin-top:12px;">
                                                                <button type="button" class="btn-cycsa btn-cycsa-primary" style="background:#059669; border-color:#059669; font-size:12.5px; padding:6px 16px;"
                                                                        data-ensayos="<?= $ensayosAprobarJson ?>"
                                                                        onclick="abrirModalAprobarOS(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>', '<?= htmlspecialchars(addslashes($os['cliente_nombre'])) ?>', '<?= htmlspecialchars(addslashes($os['nombre_proyecto'] ?? '')) ?>', this)">
                                                                    <i class="fa-solid fa-check-double"></i> Aprobar Orden de Servicio Ahora
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif (!empty($listaHojas)): ?>
                                                    <div style="display:flex; flex-direction:column; gap:8px; max-height:240px; overflow-y:auto; padding-right:4px;">
                                                        <?php foreach ($listaHojas as $idxH => $hItem): ?>
                                                            <?php
                                                            $reqDatosRecH = array_key_exists('requiere_datos_recepcion', $hItem) ? (bool)$hItem['requiere_datos_recepcion'] : true;
                                                            $totMuestrasH = (int)($hItem['total_muestras'] ?? 0);
                                                            $recCompletasH = (int)($hItem['recepcion_muestras_completas'] ?? 0);
                                                            $recAlDiaH = ($totMuestrasH > 0 && $recCompletasH >= $totMuestrasH);
                                                            ?>
                                                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap;">
                                                                <div>
                                                                    <div style="font-size:13px; font-weight:700; color:var(--cycsa-azul); display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                                                        <i class="fa-solid fa-file-invoice"></i> <?= htmlspecialchars($hItem['codigo_documento'] ?? 'CYCSA-RT-FM-13') ?> 
                                                                        <span style="font-family:monospace; color:#0f172a; background:#e2e8f0; padding:2px 7px; border-radius:4px; font-size:12px;">Reg: <?= htmlspecialchars($hItem['numero_registro'] ?? ('#' . $hItem['id'])) ?></span>
                                                                        <?php if (!empty($hItem['descripcion_ensayo'])): ?>
                                                                            <span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-size:11px; font-weight:600;">
                                                                                <i class="fa-solid fa-flask"></i> <?= htmlspecialchars($hItem['descripcion_ensayo']) ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                        <?php if ($reqDatosRecH && $totMuestrasH > 0): ?>
                                                                            <span class="badge" style="background:<?= $recAlDiaH ? '#dcfce7' : '#ffedd5' ?>; color:<?= $recAlDiaH ? '#166534' : '#9a3412' ?>; border:1px solid <?= $recAlDiaH ? '#bbf7d0' : '#fed7aa' ?>; font-size:10.5px; font-weight:700;" title="Estado de casillas de Recepción para esta RT-FM-13">
                                                                                <i class="fa-solid <?= $recAlDiaH ? 'fa-circle-check' : 'fa-clipboard-list' ?>"></i> Recepción: <?= $recCompletasH ?>/<?= $totMuestrasH ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                        <?php
                                                                        $fm40bH = $hItem['fm40b'] ?? null;
                                                                        $fm40bEstH = $fm40bH['estado_muestreo'] ?? '';
                                                                        ?>
                                                                        <?php if ($fm40bH): ?>
                                                                            <span class="badge" style="background:<?= $fm40bEstH === 'Finalizado' ? '#dcfce7' : '#dbeafe' ?>; color:<?= $fm40bEstH === 'Finalizado' ? '#166534' : '#1e40af' ?>; border:1px solid <?= $fm40bEstH === 'Finalizado' ? '#86efac' : '#93c5fd' ?>; font-size:10.5px; font-weight:700;" title="Estado de documento CYCSA-RT-FM-40 B asociado">
                                                                                <i class="fa-solid fa-truck-pickup"></i> 40 B: <?= htmlspecialchars($fm40bEstH ?: 'Activo') ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div style="font-size:11.5px; color:#64748b; margin-top:3px;">
                                                                        <i class="fa-solid fa-vials"></i> <?= $totMuestrasH ?> espécimen(es) • 
                                                                        <i class="fa-regular fa-clock"></i> <?= !empty($hItem['fecha_hora_toma_muestra']) ? date('d/m/Y H:i', strtotime($hItem['fecha_hora_toma_muestra'])) : (!empty($hItem['fecha_creacion']) ? date('d/m/Y H:i', strtotime($hItem['fecha_creacion'])) : 'N/A') ?>
                                                                    </div>
                                                                </div>
                                                                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                                                    <?php if ($reqDatosRecH): ?>
                                                                        <button type="button" class="btn-cycsa <?= $recAlDiaH ? 'btn-cycsa-secondary' : 'btn-cycsa-warning' ?>" style="min-height:34px; padding:4px 10px; font-size:11.5px; font-weight:700; <?= $recAlDiaH ? '' : 'background:#ea580c; border-color:#ea580c; color:#fff;' ?>"
                                                                                onclick="abrirModalDatosRecepcion(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', <?= $hItem['id'] ?>)"
                                                                                title="Llenar / revisar casillas de Recepción de esta RT-FM-13">
                                                                            <i class="fa-solid fa-table-list"></i> Datos Recepción
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    <?php if (!$esFinalizadaOArchivo): ?>
                                                                        <button type="button" class="btn-cycsa <?= $fm40bH ? 'btn-cycsa-secondary' : 'btn-cycsa-primary' ?>" style="min-height:34px; padding:4px 10px; font-size:11.5px; font-weight:700; <?= $fm40bH ? 'border-color:#0284c7; color:#0369a1; background:#e0f2fe;' : 'background:#0284c7; border-color:#0284c7;' ?>"
                                                                                onclick="abrirModalFm40B(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', <?= $hItem['id'] ?>)"
                                                                                title="<?= $fm40bH ? 'Editar o cerrar documento CYCSA-RT-FM-40 B asociado' : 'Crear documento CYCSA-RT-FM-40 B para esta RT-FM-13' ?>">
                                                                            <i class="fa-solid fa-truck-pickup"></i> <?= $fm40bH ? '40 B' : '+ 40 B' ?>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    <?php if ($fm40bH): ?>
                                                                        <a href="/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=<?= $os['id'] ?>&id_hoja=<?= $hItem['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; padding:4px 10px; font-size:11.5px;" title="Imprimir CYCSA-RT-FM-40 B">
                                                                            <i class="fa-solid fa-print" style="color:#0284c7;"></i>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; padding:4px 10px; font-size:12px;"
                                                                            onclick="abrirModalHojaSolicitud(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>', <?= $hItem['id'] ?>, false)"
                                                                            title="Editar Hoja RT-FM-13">
                                                                        <i class="fa-solid fa-pen"></i>
                                                                    </button>
                                                                    <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>&id_hoja=<?= $hItem['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-primary" style="min-height:34px; padding:4px 10px; font-size:12px;" title="Descargar PDF Oficial RT-FM-13">
                                                                        <i class="fa-solid fa-file-pdf"></i>
                                                                    </a>
                                                                    <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= (int)$hItem['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; padding:4px 10px; font-size:11.5px; border-color:#7c3aed; color:#6d28d9; background:#f5f3ff; font-weight:700;" title="Ver / Imprimir Hoja RT-FM-60 (Solicitud de Ensayo a Laboratorio)">
                                                                        <i class="fa-solid fa-flask"></i> Hoja RT-FM-60
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($esFinalizadaOArchivo): ?>
                                                    <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; padding: 20px; border-radius: 8px; text-align: center; color: #64748b; font-size: 13px;">
                                                        <i class="fa-solid fa-box-archive" style="font-size: 28px; margin-bottom: 8px; color: #94a3b8; display: block;"></i>
                                                        Esta orden de servicio se encuentra finalizada o archivada y no tiene Hojas RT-FM-13 registradas.
                                                    </div>
                                                <?php else: ?>
                                                    <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; padding: 20px; border-radius: 8px; text-align: center; color: #64748b; font-size: 13px;">
                                                        <i class="fa-solid fa-file-circle-question" style="font-size: 28px; margin-bottom: 8px; color: #94a3b8; display: block;"></i>
                                                        Aún no se ha registrado la primera Hoja de Servicio técnica para esta orden.
                                                        <div style="margin-top:12px;">
                                                            <button type="button" class="btn-cycsa btn-cycsa-success" style="min-height:36px; padding:6px 16px; font-size:12.5px; font-weight:700;"
                                                                    onclick="abrirModalHojaSolicitud(<?= $os['id'] ?>, '<?= htmlspecialchars(addslashes($os['codigo_os'])) ?>', 0, true)">
                                                                <i class="fa-solid fa-file-signature"></i> Registrar Primera Hoja RT-FM-13
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- TARJETA 4: ENSAYOS E INFORMES PARA CLIENTE (ATENCIÓN AL CLIENTE / RECEPCIÓN) -->
                                            <div class="tarjeta-detalle-os">
                                                <div class="tarjeta-detalle-titulo" style="display:flex; justify-content:space-between; align-items:center;">
                                                    <span><i class="fa-solid fa-file-contract"></i> 4. Ensayos e Informes para Cliente</span>
                                                    <?php if (function_exists('tienePermiso') && tienePermiso('operaciones', 'ver') && !(function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025())): ?>
                                                    <a href="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$os['id'] ?>" class="btn-cycsa btn-cycsa-secondary" style="min-height:30px; padding:4px 10px; font-size:11.5px;" title="Ver orden en Operaciones">
                                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Operaciones
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                                <?php 
                                                $itemsEnsayos = $os['items'] ?? [];
                                                $osCliNombre = (string)($os['cliente_informe_nombre'] ?? ($os['cliente_nombre'] ?? 'Cliente'));
                                                $osCliEmail = (string)($os['cliente_email'] ?? '');
                                                $esSupOS = function_exists('esSupervisorOperacionesCYCSA') ? esSupervisorOperacionesCYCSA() : in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3], true);
                                                $esTecCiegoOS = function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025();
                                                ?>
                                                <?php if (!empty($itemsEnsayos)): ?>
                                                    <div style="display:flex; flex-direction:column; gap:10px; max-height:300px; overflow-y:auto; padding-right:4px;">
                                                        <?php foreach ($itemsEnsayos as $it): ?>
                                                            <?php 
                                                            $idDetOS = (int)($it['id'] ?? 0);
                                                            $tieneMatriz = !empty($it['resultados_json']);
                                                            $revInfo = ($tieneMatriz && function_exists('obtenerEstadoRevisionMatriz')) ? obtenerEstadoRevisionMatriz($it['resultados_json']) : null;
                                                            $estadoRevOS = (string)($revInfo['estado'] ?? '');
                                                            $estaAprobadaOS = ($estadoRevOS === 'aprobada');
                                                            $codInf = ($tieneMatriz && function_exists('generarCodigoInformeEnsayo')) ? generarCodigoInformeEnsayo($it['resultados_json'], $os['fecha_muestreo'] ?? null, $it['descripcion_ensayo']) : null;
                                                            ?>
                                                            <div style="background:#f8fafc; border:1px solid <?= $estaAprobadaOS ? '#bbf7d0' : '#e2e8f0' ?>; border-radius:8px; padding:10px 14px; display:flex; flex-direction:column; gap:8px;">
                                                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                                                                    <div style="font-size:13px; font-weight:700; color:#0f172a;">
                                                                        <i class="fa-solid fa-vial-virus" style="color:var(--cycsa-azul);"></i> <?= htmlspecialchars($it['descripcion_ensayo']) ?>
                                                                    </div>
                                                                    <?php if ($tieneMatriz && $revInfo): ?>
                                                                        <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; background:<?= $revInfo['badge_bg'] ?>; color:<?= $revInfo['badge_color'] ?>; border:1px solid <?= $revInfo['badge_border'] ?>; white-space:nowrap;">
                                                                            <?= htmlspecialchars($revInfo['estado_label']) ?>
                                                                        </span>
                                                                    <?php elseif ($tieneMatriz): ?>
                                                                        <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; background:#dcfce7; color:#15803d; border:1px solid #86efac;">
                                                                            Registrada
                                                                        </span>
                                                                    <?php elseif ($cantHojas === 0): ?>
                                                                        <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; background:#fef2f2; color:#991b1b; border:1px solid #fecaca;">
                                                                            <i class="fa-solid fa-lock"></i> Requiere RT-FM-13
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1;">
                                                                            En Laboratorio
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                                                                    <?php if ($tieneMatriz && !empty($codInf)): ?>
                                                                        <div style="font-size:11.5px; font-family:monospace; color:#0369a1; background:#e0f2fe; padding:2px 8px; border-radius:4px; border:1px solid #bae6fd;">
                                                                            <i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($codInf) ?>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <span style="font-size:11.5px; color:#64748b;">Sin código de informe aún</span>
                                                                    <?php endif; ?>

                                                                    <?php if (!$esTecCiegoOS && $idDetOS > 0 && $estaAprobadaOS): ?>
                                                                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                                                            <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $idDetOS ?>" target="_blank"
                                                                               class="btn-cycsa btn-cycsa-secondary" style="min-height:28px; padding:3px 9px; font-size:11.5px; font-weight:600;" title="Ver e imprimir hoja oficial RT-FM-22">
                                                                                <i class="fa-solid fa-print"></i> Ver / Imprimir
                                                                            </a>
                                                                            <a href="/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=<?= $idDetOS ?>" target="_blank"
                                                                               class="btn-cycsa btn-cycsa-secondary" style="min-height:28px; padding:3px 9px; font-size:11.5px; font-weight:600;" title="Descargar PDF oficial">
                                                                                <i class="fa-solid fa-file-pdf text-danger"></i> PDF
                                                                            </a>
                                                                            <button type="button"
                                                                                    onclick="abrirModalEnviarInformeOS(<?= $idDetOS ?>, '<?= htmlspecialchars(addslashes((string)$it['descripcion_ensayo']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($osCliNombre), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($osCliEmail), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes((string)$os['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')"
                                                                                    class="btn-cycsa btn-cycsa-primary" style="min-height:28px; padding:3px 10px; font-size:11.5px; font-weight:600;" title="Enviar informe PDF al correo del cliente">
                                                                                <i class="fa-solid fa-paper-plane"></i> Enviar al Cliente
                                                                            </button>
                                                                        </div>
                                                                    <?php elseif (!$esTecCiegoOS && $idDetOS > 0 && $tieneMatriz && $esSupOS): ?>
                                                                        <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $idDetOS ?>" target="_blank"
                                                                           class="btn-cycsa btn-cycsa-secondary" style="min-height:28px; padding:3px 9px; font-size:11.5px;" title="Vista previa (pendiente de aprobación)">
                                                                            <i class="fa-solid fa-eye"></i> Vista previa
                                                                        </a>
                                                                    <?php elseif (!$esTecCiegoOS && $tieneMatriz): ?>
                                                                        <span style="font-size:11px; color:#64748b; font-style:italic;">Disponible al aprobar Supervisión</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="text-align: center; color: #94a3b8; padding: 25px; font-size: 13px;">
                                                        <i class="fa-solid fa-vials" style="font-size: 24px; margin-bottom: 6px; display: block; opacity: 0.5;"></i>
                                                        No hay ensayos registrados para esta cotización/orden.
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                        </div>

                                    </div>

                                    <!-- BARRA INFORMATIVA INFERIOR DE ESTADO TÉCNICO -->
                                    <div style="margin-top: 18px; padding: 12px 18px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                        <div style="font-size: 13px; color: #475569;">
                                            <i class="fa-solid fa-circle-info" style="color:var(--cycsa-azul);"></i> Estado técnico del flujo: <strong style="color:#0f172a;"><?= htmlspecialchars($os['estado']) ?></strong>
                                        </div>
                                        <div style="font-size: 12.5px; color: #64748b; font-family: monospace;">
                                            Orden Ref: <?= htmlspecialchars($os['codigo_os']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 45px 20px;">
                            <div style="font-size: 38px; color: #cbd5e1; margin-bottom: 12px;"><i class="fa-solid fa-folder-open"></i></div>
                            <div style="font-size: 16px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                                <?= ($tabActiva === 'historico') ? 'Histórico de Órdenes Vacío' : (($tabActiva === 'muestreo') ? 'No hay órdenes pendientes de muestreo' : 'No se encontraron Órdenes de Servicio activas') ?>
                            </div>
                            <p style="font-size: 13.5px; color: #64748b; margin-bottom: 14px;">
                                <?= !empty($busqueda) ? 'No hay resultados que coincidan con la búsqueda actual.' : 'Explore las demás pestañas o genere una nueva Orden de Servicio desde Cotizaciones.' ?>
                            </p>
                            <?php if ($tabActiva !== 'todas'): ?>
                                <a href="/Cycsa/publico/ordenes-servicio?tab=todas" class="btn-cycsa btn-cycsa-secondary" style="font-size: 12.5px;"><i class="fa-solid fa-list-ul"></i> Ver Todas las Órdenes</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =========================================================================
     MODAL DE REGISTRO / EDICIÓN DE HOJA DE SERVICIO (CYCSA-RT-FM-13)
     ========================================================================= -->
<div id="modalHojaSolicitud" class="modal-premium">
    <div class="modal-premium-content modal-rtfm13-content">
        
        <!-- ENCABEZADO STICKY FIJO SUPERIOR (RT-FM-13 VIEWPORT AMPLIO) -->
        <div class="rtfm13-header-sticky">
            <div class="rtfm13-header-left">
                <div class="rtfm13-icon-badge">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div class="rtfm13-title-group">
                    <h3>Hoja de Solicitud de Servicio · CYCSA-RT-FM-13</h3>
                    <div class="rtfm13-badges-row">
                        <span class="badge-rtfm13-doc"><i class="fa-solid fa-certificate"></i> RT-FM-13 V1 · ISO/IEC 17025</span>
                        <span class="badge-rtfm13-os"><i class="fa-solid fa-file-contract"></i> O/S: <strong id="hs_codigo_os_label">--</strong></span>
                        <span id="hs_badge_prefijo_header" class="badge-rtfm13-prefix"><i class="fa-solid fa-tag"></i> Muestras: <strong id="hs_prefijo_indicador">MC</strong></span>
                        <span id="hs_badge_status_hoja" class="badge-rtfm13-status badge-rtfm13-status-borrador"><i class="fa-solid fa-clock-rotate-left"></i> Borrador</span>
                    </div>
                </div>
            </div>
            <div class="rtfm13-header-right">
                <button type="button" onclick="cerrarModalHojaSolicitud()" class="rtfm13-btn-close" title="Cerrar modal (Esc)">&times;</button>
            </div>
        </div>

        <!-- CUERPO PRINCIPAL CON SCROLL INDEPENDIENTE (VIEWPORT FLUIDO) -->
        <div class="rtfm13-body-scroll">
        
        <div id="loading-hoja-solicitud" style="text-align:center; padding: 60px 20px; display:none;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:38px; color:var(--rtfm13-azul);"></i>
            <p style="color:#64748b; margin-top:14px; font-size:14px; font-weight:600;">Cargando datos de la O/S y plantilla oficial RT-FM-13...</p>
        </div>

        <!-- SELECTOR DE HOJAS RT-FM-13 (Para Contratos Activos / Múltiples Entregas) -->
        <div id="hs_selector_hojas_container" class="rtfm13-tabs-container" style="display:none;">
            <div class="rtfm13-tabs-left">
                <span class="rtfm13-tabs-title">
                    <i class="fa-solid fa-layer-group" style="color:var(--rtfm13-azul);"></i> Hojas / Entregas:
                </span>
                <div id="hs_lista_tabs_hojas" style="display:flex; gap:6px; flex-wrap:wrap;">
                    <!-- Tabs dinámicos inyectados por JS -->
                </div>
            </div>
            <div>
                <button type="button" id="hs_btn_nueva_entrega" onclick="cargarNuevaHojaEnModal()" class="btn-cycsa btn-cycsa-primary" style="font-size:12px; padding:6px 14px; border-radius:6px; font-weight:600;">
                    <i class="fa-solid fa-plus-circle"></i> + Nueva Hoja RT-FM-13
                </button>
            </div>
        </div>

        <!-- ALERTA DE ALCANCE / VIGENCIA MENSUAL (2.13 / 2.14) -->
        <div id="hs_alerta_alcance_mensual" style="display:none; margin-bottom:14px; padding:12px 16px; border-radius:8px; font-size:13px;"></div>

        <div id="wrapper-split-rt-fm-13" class="rtfm13-split-layout" style="display:none;">
            
            <!-- PANEL LATERAL IZQUIERDO: REFERENCIA VISUAL DE LA ORDEN DE SERVICIO (SOLO LECTURA) -->
            <div class="rtfm13-sidebar-ref">
                <div class="rtfm13-sidebar-ref-header">
                    <span class="rtfm13-sidebar-ref-title">
                        <i class="fa-solid fa-file-contract"></i> Referencia O/S
                    </span>
                    <span id="ref_os_badge_estado" style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; text-transform:uppercase;">Solo Lectura</span>
                </div>

                <div class="rtfm13-sidebar-data-list">
                    <div class="rtfm13-sidebar-data-item"><span class="label">Cliente:</span> <span class="val" id="ref_os_cliente">--</span></div>
                    <div class="rtfm13-sidebar-data-item"><span class="label">RUC / Cédula:</span> <span class="val font-monospace" id="ref_os_rfc">--</span></div>
                    <div class="rtfm13-sidebar-data-item"><span class="label">Atención a:</span> <span class="val" id="ref_os_atencion">--</span></div>
                    <div class="rtfm13-sidebar-data-item"><span class="label">Proyecto:</span> <span class="val" id="ref_os_proyecto">--</span></div>
                    <div class="rtfm13-sidebar-data-item"><span class="label">Dirección:</span> <span class="val" id="ref_os_direccion">--</span></div>
                    <div class="rtfm13-sidebar-data-item"><span class="label">Cotización:</span> <span class="val font-monospace" id="ref_os_cotizacion">--</span></div>
                    <div class="rtfm13-sidebar-data-item"><span class="label">Forma de Pago:</span> <span class="val" id="ref_os_pago">--</span></div>
                </div>

                <!-- Logística de Campo si aplica -->
                <div id="ref_os_logistica_box" class="rtfm13-sidebar-logistica" style="display:none;">
                    <div class="rtfm13-sidebar-logistica-title">
                        <i class="fa-solid fa-truck-pickup"></i> Logística de Muestreo en Campo
                    </div>
                    <div style="font-size:11.5px; color:#1e293b; display:flex; flex-direction:column; gap:4px;">
                        <div><strong>Técnico:</strong> <span id="ref_os_tecnico"></span></div>
                        <div><strong>Vehículo:</strong> <span id="ref_os_vehiculo"></span></div>
                        <div><strong>Fechas:</strong> <span id="ref_os_fechas"></span></div>
                    </div>
                </div>

                <!-- Tabla de Ensayos Solicitados por el Cliente -->
                <div class="rtfm13-sidebar-ensayos-box">
                    <strong style="font-size:11px; color:#0f172a; text-transform:uppercase; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                        <i class="fa-solid fa-vials" style="color:var(--rtfm13-azul);"></i> Ensayos Solicitados (O/S)
                    </strong>
                    <div style="overflow-x:auto;">
                        <table class="rtfm13-sidebar-ensayos-table">
                            <thead>
                                <tr>
                                    <th style="width:20px; text-align:center;">#</th>
                                    <th>Descripción</th>
                                    <th style="width:38px; text-align:center;">Cant</th>
                                </tr>
                            </thead>
                            <tbody id="ref_os_tbody_ensayos">
                                <!-- Filas inyectadas por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL DERECHO: FORMULARIO EDITABLE RT-FM-13 -->
            <form method="POST" action="/Cycsa/publico/hojas-servicio/guardar" id="form-hoja-solicitud" style="display:block; min-width:0;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_os" id="hs_id_os">
                <input type="hidden" name="id_hoja" id="hs_id_hoja" value="0">
                <input type="hidden" name="id_detalle_cotizacion" id="id_detalle_cotizacion_modal" value="">

                <!-- RESUMEN COMPACTO DE MUESTRAS (SIN FORZAR 1 SOLO PRODUCTO POR HOJA) -->
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; padding:10px 14px; margin-bottom:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:9px;">
                    <div style="font-size:12.5px; color:#334155;">
                        <i class="fa-solid fa-layer-group text-primary me-1"></i>
                        <strong>Hoja multi-muestra:</strong> Registre todas las muestras aquí y defina a qué producto/ensayo de la O/S va cada muestra por fila o después en <strong>Datos de Recepción</strong>.
                    </div>
                    <span id="hs_ensayo_muestras_count_pill" class="rtfm13-ensayo-pill">
                        <i class="fa-solid fa-vials text-primary"></i> <span id="hs_cnt_especimenes_label">0 especímenes</span>
                    </span>
                </div>

                <!-- 1. METADATOS Y CONTROL INTERNO -->
                <div class="rtfm13-section-card">
                    <div class="rtfm13-section-header">
                        <h4 class="rtfm13-section-title"><i class="fa-solid fa-clipboard-check"></i> A rellenar por el laboratorio (Control Interno)</h4>
                        <span class="badge bg-light text-muted border font-monospace" style="font-size:11px;">Trazabilidad Interna</span>
                    </div>
                    <div class="rtfm13-grid-3">
                        <div class="rtfm13-form-group">
                            <label>No. Registro Laboratorio</label>
                            <input type="text" name="numero_registro" id="hs_numero_registro" class="rtfm13-input rtfm13-input-mono" placeholder="Ej: 23896">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Fecha/Hora de Llegada al Lab</label>
                            <input type="datetime-local" name="fecha_hora_llegada_laboratorio" id="hs_fecha_llegada" required class="rtfm13-input">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Código del Documento</label>
                            <input type="text" name="codigo_documento" id="hs_codigo_documento" readonly class="rtfm13-input rtfm13-input-mono" value="CYCSA-RT-FM-13">
                        </div>
                    </div>
                </div>

                <!-- 2. DATOS DEL CLIENTE -->
                <div class="rtfm13-section-card">
                    <div class="rtfm13-section-header">
                        <h4 class="rtfm13-section-title"><i class="fa-solid fa-user-tie"></i> 1. Empresa o Cliente que Solicita el Servicio</h4>
                    </div>
                    <div class="rtfm13-grid-3">
                        <div class="rtfm13-form-group">
                            <label>Nombre Empresa / Cliente</label>
                            <input type="text" name="nombre_empresa_o_cliente" id="hs_nombre_empresa" required class="rtfm13-input">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Razón Social</label>
                            <input type="text" name="razon_social" id="hs_razon_social" class="rtfm13-input" placeholder="Ej: CYCSA S.A. / Persona Natural">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Dirección Proyecto / Obra</label>
                            <input type="text" name="direccion_proyecto" id="hs_direccion" required class="rtfm13-input">
                        </div>
                    </div>
                    <div class="rtfm13-grid-3">
                        <div class="rtfm13-form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" id="hs_telefono" class="rtfm13-input" placeholder="Teléfono de contacto">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" name="correo_electronico" id="hs_email" class="rtfm13-input" placeholder="correo@ejemplo.com">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Persona que entrega la muestra</label>
                            <input type="text" name="nombre_persona_entrega_muestra" id="hs_persona_entrega" required class="rtfm13-input">
                        </div>
                    </div>
                </div>

                <!-- 3. DATOS DE LA MUESTRA -->
                <div class="rtfm13-section-card">
                    <div class="rtfm13-section-header">
                        <h4 class="rtfm13-section-title"><i class="fa-solid fa-flask-vial"></i> 1. Datos de la Muestra (1.1 y 1.2)</h4>
                    </div>
                    <div class="rtfm13-form-group" style="margin-bottom: 12px;">
                        <label>1.1 Denominación-Descripción e Identificación de la Muestra &mdash; Naturaleza de la muestra:</label>
                        <div class="rtfm13-nat-pills-wrap">
                            <?php foreach (['Concreto', 'Bloques', 'Suelo', 'Adoquines', 'Agregados', 'Otros materiales'] as $nat): ?>
                                <label class="rtfm13-nat-pill-label">
                                    <input type="checkbox" name="naturaleza_muestra[]" value="<?= $nat ?>" class="hs-nat-checkbox"> <?= $nat ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="rtfm13-grid-3">
                        <div class="rtfm13-form-group">
                            <label title="Describir la ubicación del punto donde se tomó la muestra así como la comarca.">Procedencia / Punto de muestreo:</label>
                            <input type="text" name="procedencia_punto_muestreo" id="hs_procedencia" required class="rtfm13-input" placeholder="Punto de toma y comarca">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Persona quien tomó la muestra:</label>
                            <input type="text" name="nombre_persona_toma_muestra" id="hs_persona_toma" list="lista_tecnicos_hs_os" required class="rtfm13-input" placeholder="Nombre (Ej: Juan / Cliente)">
                            <datalist id="lista_tecnicos_hs_os">
                                <option value="Cliente / Entregada por Cliente"></option>
                                <?php if (!empty($tecnicos)): ?>
                                    <?php foreach ($tecnicos as $t): ?>
                                        <?php $nomTec = htmlspecialchars($t['nombre'] ?? $t['nombre_tecnico'] ?? ''); ?>
                                        <option value="<?= $nomTec ?>"></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </datalist>
                        </div>
                        <div class="rtfm13-form-group">
                            <label>1.2 Fecha y hora en que se tomó:</label>
                            <input type="datetime-local" name="fecha_hora_toma_muestra" id="hs_fecha_toma" required class="rtfm13-input">
                        </div>
                    </div>
                </div>

                <!-- 4. IDENTIFICACIONES PROPIAS (TABLA DINÁMICA DE ESPECÍMENES) -->
                <div class="rtfm13-section-card">
                    <div class="rtfm13-section-header">
                        <h4 class="rtfm13-section-title"><i class="fa-solid fa-list-ol"></i> 2. Identificaciones Propias de la Muestra</h4>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="hidden" name="prefijo_seleccionado" id="hs_prefijo_seleccionado" value="MC">
                            <div class="rtfm13-prefix-segmented" style="background:#f1f5f9; border:1.5px solid #cbd5e1; padding:3px 6px; border-radius:8px; display:inline-flex; align-items:center; gap:5px;">
                                <span class="label" style="font-size:11px; font-weight:700; color:#475569; padding:0 4px;"><i class="fa-solid fa-tag me-1"></i>Tipo Muestra:</span>
                                <button type="button" id="btn_prefijo_mc" onclick="seleccionarPrefijoMuestraModal('MC')" class="rtfm13-prefix-btn active" style="cursor:pointer; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:700; display:inline-flex; align-items:center; gap:5px; border:1.5px solid #0284c7; background:#0284c7; color:#ffffff;">
                                    <i class="fa-solid fa-truck-pickup"></i> MC (Campo)
                                </button>
                                <button type="button" id="btn_prefijo_ms" onclick="seleccionarPrefijoMuestraModal('MS')" class="rtfm13-prefix-btn" style="cursor:pointer; padding:4px 10px; border-radius:6px; font-size:11.5px; font-weight:700; display:inline-flex; align-items:center; gap:5px; border:1.5px solid #cbd5e1; background:#ffffff; color:#475569;">
                                    <i class="fa-solid fa-flask"></i> MS (Sede/Lab)
                                </button>
                            </div>
                            <button type="button" class="btn-cycsa btn-cycsa-primary" style="padding:6px 16px; font-size:12.5px;" onclick="agregarFilaMuestraModal()">
                                <i class="fa-solid fa-plus"></i> Agregar Muestra
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="select_plantilla_global" name="plantilla_global_id" value="">

                    <div class="rtfm13-table-wrap">
                        <table class="rtfm13-table-muestras" id="hs-tabla-muestras">
                            <thead>
                                <tr>
                                    <th style="width:20%;"><i class="fa-solid fa-barcode me-1 text-primary"></i> # Muestra / Código</th>
                                    <th style="width:27%;"><i class="fa-solid fa-location-dot me-1 text-primary"></i> Ubicación / Identificación</th>
                                    <th style="width:18%;"><i class="fa-solid fa-calendar-days me-1 text-primary"></i> Observaciones / Edad</th>
                                    <th style="width:28%;"><i class="fa-solid fa-flask-vial me-1 text-primary"></i> Producto / Ensayo Destino</th>
                                    <th style="width:7%; text-align:center;"><i class="fa-solid fa-trash me-1"></i></th>
                                </tr>
                            </thead>
                            <tbody id="hs-tbody-muestras">
                                <!-- Filas inyectadas dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 5. ANÁLISIS REQUERIDOS (CHECKBOXES) -->
                <div class="rtfm13-section-card">
                    <div class="rtfm13-section-header">
                        <h4 class="rtfm13-section-title"><i class="fa-solid fa-vial"></i> 3. Parámetros Solicitados</h4>
                    </div>
                    
                    <div class="rtfm13-grid-2">
                        <!-- Columna Concreto -->
                        <div id="hs_col_concreto" class="rtfm13-tests-card">
                            <h4><i class="fa-solid fa-cube text-primary"></i> 3.1 Muestra de Concreto, Adoquines, Bloques</h4>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_resistencia_concreto" value="1" id="hs_req_concreto"> Resistencia de concreto</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_resistencia_adoquin" value="1" id="hs_req_adoquin"> Resistencia de adoquín</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_resistencia_bloques" value="1" id="hs_req_bloques"> Resistencia bloques</label>
                                <div style="margin-top:8px;">
                                    <label style="font-size:12px; color:var(--rtfm13-slate-600); font-weight:600;">Otros análisis en concreto:</label>
                                    <input type="text" name="req_otros_concreto" id="hs_req_otros_concreto" class="rtfm13-input" style="height:36px; font-size:12.5px;" placeholder="Otros análisis en concreto...">
                                </div>
                            </div>
                        </div>

                        <!-- Columna Suelos -->
                        <div id="hs_col_suelo" class="rtfm13-tests-card">
                            <h4><i class="fa-solid fa-mountain text-primary"></i> 3.2 Muestras de Suelo</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_granulometria" value="1" id="hs_req_granulometria"> Granulometría</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_limites_atterberg" value="1" id="hs_req_limites"> Límites de Atterberg</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_humedad" value="1" id="hs_req_humedad"> Humedad</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_resistencia_corte" value="1" id="hs_req_resistencia_corte"> Resistencia al corte</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_clasificacion_sucs_hr" value="1" id="hs_req_clasificacion_sucs_hr"> Clasificación SUCS/HR</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_proctor_sm" value="1" id="hs_req_proctor"> PROCTOR S/M</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_infiltracion" value="1" id="hs_req_infiltracion"> Infiltración</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_cbr" value="1" id="hs_req_cbr"> CBR</label>
                                <label class="rtfm13-checkbox-label"><input type="checkbox" name="req_densidad" value="1" id="hs_req_densidad"> Densidad</label>
                            </div>
                            <div style="margin-top:8px;">
                                <label style="font-size:12px; color:var(--rtfm13-slate-600); font-weight:600;">Otros análisis en suelos:</label>
                                <input type="text" name="req_otros_suelo" id="hs_req_otros_suelo" class="rtfm13-input" style="height:36px; font-size:12.5px;" placeholder="Otros análisis en suelos...">
                            </div>
                        </div>
                    </div>

                    <!-- 3.3 OTROS MATERIALES -->
                    <div class="rtfm13-tests-card" style="margin-top:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #cbd5e1; padding-bottom:6px; margin-bottom:10px;">
                            <label class="rtfm13-checkbox-label" style="font-weight:700; margin:0;">
                                <input type="checkbox" name="req_otros_materiales" value="1" id="hs_req_otros_materiales"> 3.3 Otros Materiales &mdash; Otro
                            </label>
                        </div>
                        <div class="rtfm13-form-group">
                            <label style="font-size:12px; color:var(--rtfm13-slate-600); font-weight:600;">* Si seleccionó la casilla otros, favor indicar qué análisis necesita:</label>
                            <input type="text" name="descripcion_otros_analisis" id="hs_descripcion_otros" class="rtfm13-input" style="height:36px; font-size:12.5px;" placeholder="Detalle el análisis requerido...">
                        </div>
                    </div>
                </div>

                <!-- 6. ANÁLISIS ADICIONALES Y OBSERVACIONES -->
                <div class="rtfm13-section-card">
                    <div class="rtfm13-grid-2">
                        <div class="rtfm13-form-group">
                            <label>Análisis adicionales</label>
                            <textarea name="analisis_adicionales" id="hs_analisis_adicionales" class="rtfm13-input" style="height:76px; resize:vertical;" placeholder="Análisis adicionales requeridos..."></textarea>
                        </div>
                        <div class="rtfm13-form-group">
                            <label>Observaciones</label>
                            <textarea name="observaciones" id="hs_observaciones" class="rtfm13-input" style="height:76px; resize:vertical;" placeholder="Observaciones de recepción de muestra..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 7. FIRMAS Y RECEPCIÓN CYCSA -->
                <div class="rtfm13-section-card">
                    <div class="rtfm13-section-header">
                        <h4 class="rtfm13-section-title"><i class="fa-solid fa-signature"></i> Recepción y Conformidad</h4>
                    </div>
                    <div class="rtfm13-grid-3" style="align-items:center;">
                        <div class="rtfm13-form-group">
                            <label>Persona de CYCSA quien recibe la muestra</label>
                            <input type="text" name="nombre_recibe_cycsa" id="hs_nombre_recibe" required class="rtfm13-input" placeholder="Nombre completo">
                        </div>
                        <div class="rtfm13-form-group">
                            <label>&nbsp;</label>
                            <label class="rtfm13-checkbox-label" style="margin-top:6px;">
                                <input type="checkbox" name="firma_recibe_cycsa" value="1" id="hs_firma_recibe_cycsa"> ¿Firma digital receptor?
                            </label>
                        </div>
                        <div class="rtfm13-form-group">
                            <label>&nbsp;</label>
                            <label class="rtfm13-checkbox-label" style="margin-top:6px;">
                                <input type="checkbox" name="firma_cliente" value="1" id="hs_firma_cliente"> ¿Firma digital cliente?
                            </label>
                        </div>
                    </div>

                    <!-- ESPACIO DE FIRMA ADICIONAL VINCULADO AL INFORME OFICIAL (ABAJO IZQUIERDA) -->
                    <div style="margin-top:12px; padding:12px 14px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px;">
                        <input type="hidden" name="incluir_firma_adicional" id="hs_incluir_firma_adicional" value="0">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <div style="font-size:12.5px; color:#334155;">
                                <strong><i class="fa-solid fa-pen-nib me-1 text-primary"></i> Firma adicional en el informe oficial</strong>
                                <div style="font-size:11.5px; color:#64748b;">Si la activa, al imprimir/PDF se agregará un espacio de firma abajo a la izquierda junto a la firma oficial (abajo a la derecha).</div>
                            </div>
                            <button type="button" id="btn_toggle_firma_adicional_rtfm13" onclick="toggleFirmaAdicionalRTFM13()" class="btn btn-outline-primary btn-sm" style="font-weight:600;">
                                <i class="fa-solid fa-plus me-1"></i> Agregar espacio de firma
                            </button>
                        </div>
                        <div id="panel_firma_adicional_rtfm13" style="display:none; margin-top:10px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                            <div class="rtfm13-grid-2">
                                <div class="rtfm13-form-group">
                                    <label>Nombre firmante adicional (abajo izquierda)</label>
                                    <input type="text" name="firma_adicional_nombre" id="hs_firma_adicional_nombre" class="rtfm13-input" placeholder="Opcional — dejar vacío para línea en blanco">
                                </div>
                                <div class="rtfm13-form-group">
                                    <label>Cargo / Etiqueta de la firma adicional</label>
                                    <input type="text" name="firma_adicional_cargo" id="hs_firma_adicional_cargo" class="rtfm13-input" placeholder="Ej. Revisado por / Supervisor de Obra">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 14px; margin-top:12px; margin-bottom:15px; font-size:12px; color:#1e40af; line-height:1.45;">
                        <strong>*.- Con este documento doy fe</strong>, que todo lo escrito lo he revisado y que he quedado de mutuo acuerdo de los servicios que me ofrecerá CYCSA en la muestra. Cualquier cambio deberá ser notificado previo a CYCSA de cualquier forma escrita para su debido registro.
                    </div>

                    <!-- CONTROL DE RECEPCIÓN FÍSICA Y CONGELAMIENTO ISO 17025 -->
                    <div class="rtfm13-lims-card">
                        <div>
                            <label style="display:flex; align-items:center; gap:8px; font-weight:700; color:#92400e; font-size:13px; cursor:pointer; margin:0;">
                                <input type="checkbox" name="confirmar_recepcion_lims" value="1" id="chk_confirmar_recepcion_lims" style="transform:scale(1.25); accent-color:#d97706;">
                                <span><i class="fa-solid fa-lock text-warning me-1"></i> Confirmar Recepción Física y Congelar Plantillas (LIMS / ISO 17025)</span>
                            </label>
                            <div style="font-size:11.5px; color:#b45309; margin-left:26px; margin-top:3px;">
                                Al marcar esta opción, las plantillas de ensayo seleccionadas quedarán congeladas permanentemente para trazabilidad de auditoría.
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark border border-warning" style="font-size:11px; padding:5px 10px; font-weight:700;">
                            <i class="fa-solid fa-shield-halved me-1"></i> ISO/IEC 17025
                        </span>
                    </div>
                </div>
            </form>
        </div>
        </div>

        <!-- FOOTER STICKY INFERIOR FLOTANTE SIEMPRE VISIBLE -->
        <div class="rtfm13-footer-sticky">
            <div class="rtfm13-footer-left">
                <i class="fa-solid fa-file-invoice text-primary" style="font-size:18px;"></i>
                <div>
                    <span>CYCSA-RT-FM-13 · Especímenes en hoja: <strong id="hs_footer_cant_muestras" class="text-primary font-monospace" style="font-size:14px;">0</strong></span>
                    <span class="mx-2 text-muted">|</span>
                    <span>O/S: <strong id="hs_footer_os_code" class="font-monospace text-dark">--</strong></span>
                </div>
            </div>
            <div class="rtfm13-footer-right">
                <button type="button" class="rtfm13-btn-action rtfm13-btn-cancel" onclick="cerrarModalHojaSolicitud()">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </button>
                <button type="button" id="hs_btn_guardar_borrador" class="rtfm13-btn-action rtfm13-btn-draft" onclick="guardarBorradorHoja()">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Borrador
                </button>
                <button type="button" id="hs_btn_emitir_oficial" class="rtfm13-btn-action rtfm13-btn-submit" onclick="validarYEnviarHojaOficial(event)">
                    <i class="fa-solid fa-circle-check"></i> Emitir Hoja Oficial (RT-FM-13)
                </button>
            </div>
        </div>

    </div>
</div>

<!-- =========================================================================
     MODAL DE CAPTURA DE DATOS DE RECEPCIÓN POR RT-FM-13 Y SUS N MUESTRAS
     ========================================================================= -->
<div id="modalDatosRecepcion" class="modal-premium" style="display:none; position:fixed; z-index:9998; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(15,23,42,0.58);">
    <div class="modal-premium-content" style="width:94%; max-width:1180px; background:#fff; margin:2.5% auto; border-radius:14px; overflow:hidden; box-shadow:0 24px 54px rgba(15,23,42,0.32); display:flex; flex-direction:column; max-height:92vh; border:1px solid #e2e8f0;">
        <div style="background:#ffffff; color:#0f172a; border-bottom:1px solid #e2e8f0; padding:16px 22px; display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:10px; background:#eff6ff; color:#103487; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="fa-solid fa-table-list"></i>
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <h4 style="margin:0; font-size:16px; font-weight:800; color:#103487;">Datos de Recepción por Hoja RT-FM-13 (Casillas "Llena Recepción")</h4>
                        <span id="rec_badge_etapa" style="display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;">
                            Recepción en borrador
                        </span>
                    </div>
                    <div style="font-size:13px; color:#475569; margin-top:3px;">
                        O/S: <strong id="rec_os_codigo" style="color:#0f172a;">--</strong> · Hoja: <strong id="rec_hoja_reg" style="color:#0f172a;">--</strong> · <span id="rec_ensayo_nombre">--</span>
                    </div>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDatosRecepcion()" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; width:34px; height:34px; border-radius:8px; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <div style="padding:18px 22px; overflow-y:auto; flex:1;">
            <div id="rec_loading" style="display:none; text-align:center; padding:45px 20px; color:#64748b;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size:32px; color:#103487;"></i>
                <div style="margin-top:10px; font-weight:600; font-size:13.5px;">Cargando muestras y campos de recepción de la RT-FM-13...</div>
            </div>

            <div id="rec_contenido" style="display:none;">
                <!-- Pestañas para navegar entre todas las RT-FM-13 de la O/S -->
                <div id="rec_tabs_hojas_wrap" style="margin-bottom:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; display:none;">
                    <div style="font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                        <i class="fa-solid fa-layer-group me-1" style="color:#103487;"></i> Hojas RT-FM-13 de esta O/S (seleccione para llenar sus datos):
                    </div>
                    <div id="rec_tabs_hojas_list" style="display:flex; gap:8px; flex-wrap:wrap;"></div>
                </div>

                <!-- Distribución Recepción vs Técnico -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div style="background:#f8fafc; border:1px solid #cbd5e1; border-left:4px solid #103487; border-radius:9px; padding:10px 14px;">
                        <div style="font-size:13px; font-weight:800; color:#103487; margin-bottom:4px;">
                            <i class="fa-solid fa-inbox me-1"></i> LLENA RECEPCIÓN (En esta pantalla · por muestra de la RT-FM-13)
                        </div>
                        <div id="rec_lista_campos_recepcion" style="font-size:13px; color:#334155;"></div>
                    </div>
                    <div style="background:#f8fafc; border:1px solid #cbd5e1; border-left:4px solid #0284c7; border-radius:9px; padding:10px 14px;">
                        <div style="font-size:13px; font-weight:800; color:#0369a1; margin-bottom:4px;">
                            <i class="fa-solid fa-microscope me-1"></i> RELLENA TÉCNICO / LABORATORIO (Operaciones y Roturas)
                        </div>
                        <div id="rec_lista_campos_tecnico" style="font-size:13px; color:#334155;"></div>
                    </div>
                </div>

                <div id="rec_sin_campos_notice" style="display:none; background:#f1f5f9; border:1px dashed #94a3b8; border-radius:10px; padding:22px; text-align:center; color:#475569; font-size:13.5px;">
                    <i class="fa-solid fa-circle-info" style="font-size:24px; color:#0284c7; display:block; margin-bottom:8px;"></i>
                    El formato de ensayo de esta RT-FM-13 no solicita casillas adicionales de Recepción por espécimen; todos sus parámetros se capturan en Operaciones.
                </div>

                <div id="rec_tabla_section">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                        <div style="font-size:13px; color:#334155;">
                            Complete las casillas para las <strong id="rec_total_muestras_lbl">0</strong> muestra(s) de esta hoja RT-FM-13.
                            <span style="color:#103487; font-weight:600;">Reven. (cm) se calcula automáticamente a 0.5 cm al capturar Reven. (in).</span>
                        </div>
                        <button type="button" id="rec_btn_copiar_fila1" onclick="copiarFilaUnoDatosRecepcion()" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; padding:4px 12px; font-size:13px; font-weight:700;">
                            <i class="fa-solid fa-copy me-1"></i> Copiar Fila 1 a todas las muestras
                        </button>
                    </div>

                    <div style="overflow-x:auto; border:1px solid #cbd5e1; border-radius:10px;">
                        <table id="rec_tabla_muestras" style="width:100%; border-collapse:collapse; font-size:13px; min-width:820px;">
                            <thead id="rec_thead_muestras" style="background:#f8fafc; color:#0f172a; border-bottom:1.5px solid #cbd5e1;"></thead>
                            <tbody id="rec_tbody_muestras"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding:14px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <div id="rec_feedback_msg" style="font-size:13px; font-weight:600; color:#475569;"></div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <button type="button" onclick="cerrarModalDatosRecepcion()" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; font-size:13px;">
                    Cerrar
                </button>
                <button type="button" id="rec_btn_reabrir" onclick="reabrirRecepcionModal()" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; font-size:13px; border-color:#b45309; color:#b45309; font-weight:700; display:none;">
                    <i class="fa-solid fa-unlock-keyhole me-1"></i> Reabrir Recepción
                </button>
                <button type="button" id="rec_btn_guardar" onclick="guardarDatosRecepcionModal(false)" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; font-size:13px; border-color:#103487; color:#103487; font-weight:700;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Borrador Recepción
                </button>
                <button type="button" id="rec_btn_completar" onclick="guardarDatosRecepcionModal(true)" class="btn-cycsa btn-cycsa-primary" style="min-height:38px; font-size:13px; background:#103487; border-color:#103487; font-weight:700;">
                    <i class="fa-solid fa-circle-check me-1"></i> Guardar y Marcar Recepción Completa (Ingresar a Laboratorio)
                </button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL CYCSA-RT-FM-40 B EN LÍNEA ASOCIADO A RT-FM-13 -->
<div id="modalFm40B" class="modal-overlay" style="display:none; z-index:1060; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.72); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; width:100%; max-width:1100px; max-height:92vh; border-radius:14px; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);">
        <div style="background:linear-gradient(135deg, #0f172a 0%, #0369a1 100%); color:#fff; padding:16px 22px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="width:40px; height:40px; border-radius:10px; background:rgba(255,255,255,0.16); display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="fa-solid fa-clipboard-check"></i>
                </span>
                <div>
                    <div style="font-size:16px; font-weight:800; letter-spacing:-0.2px;">
                        CYCSA-RT-FM-40 B · Lista de Chequeo de Muestreo
                    </div>
                    <div id="fm40b_subtitulo" style="font-size:12px; color:#bae6fd; font-weight:500;">
                        Asociado a hoja RT-FM-13 dentro de la Orden de Servicio
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span id="fm40b_badge_estado" style="padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:700; background:rgba(255,255,255,0.18); color:#fff;">
                    Pendiente
                </span>
                <button type="button" onclick="cerrarModalFm40B()" style="background:rgba(255,255,255,0.15); border:none; color:#fff; width:34px; height:34px; border-radius:8px; cursor:pointer; font-size:16px;">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>

        <form id="formFm40BModal" style="flex:1; overflow-y:auto; padding:20px 24px; background:#f8fafc;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? $csrfToken), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="fm40b_id_os" value="">

            <div id="fm40b_loader" style="display:none; text-align:center; padding:36px 20px; color:#0369a1; font-weight:600;">
                <i class="fa-solid fa-spinner fa-spin fa-2x mb-2"></i>
                <div>Cargando datos de CYCSA-RT-FM-40 B...</div>
            </div>

            <div id="fm40b_contenido">
                <!-- Selector de Hoja RT-FM-13 y resumen de la OS -->
                <div style="background:#fff; border:1px solid #cbd5e1; border-left:4px solid #0284c7; border-radius:10px; padding:14px 16px; margin-bottom:16px; display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between;">
                    <div style="flex:1; min-width:260px;">
                        <label for="fm40b_id_hoja_solicitud" style="display:block; font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:5px;">
                            <i class="fa-solid fa-link me-1 text-primary"></i> Hoja RT-FM-13 Asociada
                        </label>
                        <select name="id_hoja_solicitud" id="fm40b_id_hoja_solicitud" onchange="cambiarHojaModalFm40B(this.value)" style="width:100%; padding:8px 11px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-weight:600; color:#0f172a; background:#fff;">
                            <option value="">— Sin hoja RT-FM-13 específica (General O/S) —</option>
                        </select>
                    </div>
                    <div style="min-width:220px; font-size:12.5px; color:#334155;">
                        <div><strong>O/S:</strong> <span id="fm40b_info_os">-</span></div>
                        <div><strong>Cliente:</strong> <span id="fm40b_info_cliente">-</span></div>
                        <div><strong>Proyecto:</strong> <span id="fm40b_info_proyecto">-</span></div>
                    </div>
                </div>

                <!-- Datos logísticos de campo -->
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:16px;">
                    <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                        <i class="fa-solid fa-truck-pickup me-1 text-primary"></i> Datos Logísticos y Personal de Campo
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px;">
                        <div>
                            <label for="fm40b_fecha_ida" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Fecha y Hora Salida</label>
                            <input type="datetime-local" name="fecha_ida" id="fm40b_fecha_ida" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px;">
                        </div>
                        <div>
                            <label for="fm40b_fecha_llegada" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Fecha y Hora Regreso</label>
                            <input type="datetime-local" name="fecha_llegada" id="fm40b_fecha_llegada" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px;">
                        </div>
                        <div>
                            <label for="fm40b_id_tecnico" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Técnico Responsable *</label>
                            <select name="id_tecnico" id="fm40b_id_tecnico" required style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px; background:#fff;">
                                <option value="">— Seleccionar técnico —</option>
                            </select>
                        </div>
                        <div>
                            <label for="fm40b_id_vehiculo" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Vehículo Asignado *</label>
                            <select name="id_vehiculo" id="fm40b_id_vehiculo" required style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px; background:#fff;">
                                <option value="">— Seleccionar vehículo —</option>
                            </select>
                        </div>
                        <div style="grid-column:span 2;">
                            <label for="fm40b_lugar_muestreo" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Lugar de Muestreo / Dirección</label>
                            <input type="text" name="lugar_muestreo" id="fm40b_lugar_muestreo" placeholder="Ubicación del frente de trabajo..." style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px;">
                        </div>
                        <div>
                            <label for="fm40b_num_muestreadores" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">N° Muestreadores</label>
                            <input type="number" name="num_muestreadores" id="fm40b_num_muestreadores" min="1" value="1" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px;">
                        </div>
                        <div>
                            <label for="fm40b_cantidad_muestras_est" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Cant. Muestras Estimada</label>
                            <input type="number" name="cantidad_muestras_est" id="fm40b_cantidad_muestras_est" min="1" value="1" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px;">
                        </div>
                    </div>
                </div>

                <!-- Botón rápido de chequeo -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                    <div style="font-size:12.5px; color:#475569; font-weight:600;">
                        <i class="fa-solid fa-list-check me-1 text-primary"></i> Lista de Chequeo de Salida (Antes) y Regreso (Posterior)
                    </div>
                    <button type="button" onclick="marcarTodoFm40bModal()" class="btn-cycsa btn-cycsa-secondary" style="padding:5px 12px; font-size:12px; min-height:30px;">
                        <i class="fa-solid fa-check-double me-1 text-success"></i> Marcar Todo para Salida
                    </button>
                </div>

                <!-- Tablas de Checklist -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(430px, 1fr)); gap:14px; margin-bottom:16px;">
                    <!-- Equipos de Seguridad -->
                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                        <div style="background:#0f172a; color:#fff; padding:9px 14px; font-size:12.5px; font-weight:700;">
                            <i class="fa-solid fa-hard-hat me-1"></i> Equipos de Protección y Seguridad
                        </div>
                        <table style="width:100%; border-collapse:collapse; font-size:12px;">
                            <thead>
                                <tr style="background:#f1f5f9; color:#334155;">
                                    <th style="padding:7px 10px; text-align:left; border-bottom:1px solid #e2e8f0;">Elemento</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:64px;">Cant. A</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:50px;">Sal.</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:64px;">Cant. P</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:50px;">Reg.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $itemsSeguridadModal = [
                                    'Mascarilla', 'Lentes de Seguridad', 'Casco', 'Chaleco',
                                    'Guantes Nitrilo', 'Botas de Cuero', 'Botas de Hule', 'Tapones de oído',
                                    'Documentos de acceso al lugar', 'Carnet de Seguro', 'Colilla de INSS',
                                    'Cuña de vehículo', 'Cinta luminosa de vehículo'
                                ];
                                foreach ($itemsSeguridadModal as $idxSeg => $labelSeg):
                                    $kSeg = 'seg_' . $idxSeg;
                                ?>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:6px 10px; color:#1e293b; font-weight:500;"><?= htmlspecialchars($labelSeg, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="text" name="chk[<?= $kSeg ?>_cant_a]" id="fm40b_chk_<?= $kSeg ?>_cant_a" style="width:46px; text-align:center; border:1px solid #cbd5e1; border-radius:4px; padding:2px; font-size:11.5px;"></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="checkbox" class="fm40b-chk-salida" name="chk[<?= $kSeg ?>_chk_a]" id="fm40b_chk_<?= $kSeg ?>_chk_a" value="1"></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="text" name="chk[<?= $kSeg ?>_cant_p]" id="fm40b_chk_<?= $kSeg ?>_cant_p" style="width:46px; text-align:center; border:1px solid #cbd5e1; border-radius:4px; padding:2px; font-size:11.5px;"></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="checkbox" class="fm40b-chk-regreso" name="chk[<?= $kSeg ?>_chk_p]" id="fm40b_chk_<?= $kSeg ?>_chk_p" value="1"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Equipos y Materiales de Muestreo -->
                    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                        <div style="background:#0369a1; color:#fff; padding:9px 14px; font-size:12.5px; font-weight:700;">
                            <i class="fa-solid fa-toolbox me-1"></i> Equipos y Materiales para Toma de Muestras
                        </div>
                        <table style="width:100%; border-collapse:collapse; font-size:12px;">
                            <thead>
                                <tr style="background:#f1f5f9; color:#334155;">
                                    <th style="padding:7px 10px; text-align:left; border-bottom:1px solid #e2e8f0;">Equipo / Material</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:64px;">Cant. A</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:50px;">Sal.</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:64px;">Cant. P</th>
                                    <th style="padding:7px 6px; text-align:center; border-bottom:1px solid #e2e8f0; width:50px;">Reg.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $itemsMuestrasModal = [
                                    'Densímetro Nuclear', 'Base de parafina', 'Placa guía para pin', 'Mazo de hierro',
                                    'Pin de penetración', 'Mariposa para extraer pin', 'Cargador de densímetro', 'Caja de densímetro',
                                    'Mecate para aseguran caja', 'Dosímetro personal', 'Dosímetro de equipo', 'Conos de seguridad',
                                    'Rótulos de advertencia radiación', 'Hoja de campo', 'Tabla de apoyo', 'Proctor a utilizar'
                                ];
                                foreach ($itemsMuestrasModal as $idxMue => $labelMue):
                                    $kMue = 'mue_' . $idxMue;
                                ?>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:6px 10px; color:#1e293b; font-weight:500;"><?= htmlspecialchars($labelMue, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="text" name="chk[<?= $kMue ?>_cant_a]" id="fm40b_chk_<?= $kMue ?>_cant_a" style="width:46px; text-align:center; border:1px solid #cbd5e1; border-radius:4px; padding:2px; font-size:11.5px;"></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="checkbox" class="fm40b-chk-salida" name="chk[<?= $kMue ?>_chk_a]" id="fm40b_chk_<?= $kMue ?>_chk_a" value="1"></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="text" name="chk[<?= $kMue ?>_cant_p]" id="fm40b_chk_<?= $kMue ?>_cant_p" style="width:46px; text-align:center; border:1px solid #cbd5e1; border-radius:4px; padding:2px; font-size:11.5px;"></td>
                                    <td style="padding:4px 6px; text-align:center;"><input type="checkbox" class="fm40b-chk-regreso" name="chk[<?= $kMue ?>_chk_p]" id="fm40b_chk_<?= $kMue ?>_chk_p" value="1"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Observaciones de campo -->
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px;">
                    <label for="fm40b_observaciones_campo" style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:6px;">
                        Observaciones de Campo / Cierre 40 B
                    </label>
                    <textarea name="observaciones_campo" id="fm40b_observaciones_campo" rows="2" placeholder="Observaciones del técnico, novedades de equipo o cierre del documento 40 B..." style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:12.5px;"></textarea>
                </div>
            </div>
        </form>

        <div style="padding:14px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <div id="fm40b_feedback" style="font-size:12.5px; font-weight:600; color:#475569;"></div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <a id="fm40b_btn_imprimir" href="#" target="_blank" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; display:none; text-decoration:none; align-items:center;">
                    <i class="fa-solid fa-print me-1"></i> Imprimir 40 B
                </a>
                <button type="button" onclick="cerrarModalFm40B()" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px;">
                    Cancelar
                </button>
                <button type="button" id="fm40b_btn_guardar" onclick="guardarFm40bModal('guardar')" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; border-color:#0284c7; color:#0369a1; font-weight:700;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar (En Campo)
                </button>
                <button type="button" id="fm40b_btn_cerrar_40b" onclick="guardarFm40bModal('finalizar')" class="btn-cycsa btn-cycsa-primary" style="min-height:38px; background:#059669; border-color:#059669; font-weight:700;">
                    <i class="fa-solid fa-lock me-1"></i> Guardar y Cerrar 40 B (Finalizado)
                </button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL PERÍODO MENSUAL / CONTRATO LARGO EN LÍNEA (Sin perder aprobación) -->
<div id="modalPeriodoContrato" class="modal-overlay" style="display:none; z-index:1065; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.72); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; width:100%; max-width:640px; border-radius:14px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);">
        <div style="background:linear-gradient(135deg, #1e1b4b 0%, #3730a3 100%); color:#fff; padding:16px 22px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="width:38px; height:38px; border-radius:10px; background:rgba(255,255,255,0.16); display:flex; align-items:center; justify-content:center; font-size:17px;">
                    <i class="fa-solid fa-calendar-days"></i>
                </span>
                <div>
                    <div style="font-size:16px; font-weight:800;">Período Mensual / Contrato Largo</div>
                    <div id="pc_subtitulo" style="font-size:12px; color:#c7d2fe;">Determinar duración y fecha de cierre de la O/S</div>
                </div>
            </div>
            <button type="button" onclick="cerrarModalPeriodoContrato()" style="background:rgba(255,255,255,0.15); border:none; color:#fff; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:15px;">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <form id="formPeriodoContratoModal" style="padding:20px 22px; background:#f8fafc;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? $csrfToken), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="pc_id_os" value="">

            <div id="pc_alerta_aprobada" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; border-radius:8px; padding:10px 12px; font-size:12px; font-weight:600; margin-bottom:14px;">
                <i class="fa-solid fa-shield-check me-1"></i> Esta Orden de Servicio ya está aceptada (<span id="pc_estado_actual_lbl"></span>). Definir o ajustar su período mensual conservará su aprobación intacta.
            </div>

            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:14px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:700; color:#0f172a; font-size:13.5px; margin:0;">
                    <input type="checkbox" name="es_multimes" id="pc_es_multimes" value="1" onchange="togglePeriodoContratoTipo(this.checked)" style="width:18px; height:18px; accent-color:#3730a3; cursor:pointer;">
                    <span>Activar como Contrato Largo (Período Mensual / Entregas Continuas)</span>
                </label>
                <div style="font-size:12px; color:#64748b; margin-top:4px; padding-left:28px;">
                    Permite definir cuántos meses durará la orden y en qué fecha finaliza.
                </div>
            </div>

            <div id="pc_campos_multimes" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">
                <div style="font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:8px;">
                    Plazo Rápido (Meses)
                </div>
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px;">
                    <?php foreach ([1, 2, 3, 6, 9, 12, 18, 24] as $mesOpt): ?>
                        <button type="button" onclick="seleccionarDuracionMesesModal(<?= $mesOpt ?>)" class="btn-cycsa btn-cycsa-secondary pc-mes-pill" data-meses="<?= $mesOpt ?>" style="min-height:30px; padding:4px 10px; font-size:12px;">
                            <?= $mesOpt ?> <?= $mesOpt === 1 ? 'mes' : 'meses' ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(175px, 1fr)); gap:12px;">
                    <div>
                        <label for="pc_duracion_meses" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Duración (Meses)</label>
                        <input type="number" name="duracion_meses" id="pc_duracion_meses" min="1" max="120" value="3" onchange="recalcularFechaFinPeriodoModal()" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:13px; font-weight:700;">
                    </div>
                    <div>
                        <label for="pc_periodicidad" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Periodicidad</label>
                        <select name="periodicidad" id="pc_periodicidad" onchange="recalcularResumenPeriodoModal()" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:13px; background:#fff;">
                            <option value="Mensual">Mensual</option>
                            <option value="Quincenal">Quincenal</option>
                            <option value="Bimestral">Bimestral</option>
                            <option value="Trimestral">Trimestral</option>
                            <option value="Semestral">Semestral</option>
                        </select>
                    </div>
                    <div>
                        <label for="pc_fecha_inicio" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="pc_fecha_inicio" onchange="recalcularFechaFinPeriodoModal()" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:13px;">
                    </div>
                    <div>
                        <label for="pc_fecha_fin" style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Termina el (Fecha Fin)</label>
                        <input type="date" name="fecha_fin" id="pc_fecha_fin" onchange="recalcularMesesDesdeFechaFinModal()" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:7px; font-size:13px;">
                    </div>
                </div>

                <div id="pc_resumen_vigencia" style="margin-top:12px; padding:10px 12px; border-radius:8px; background:#eef2ff; border:1px solid #c7d2fe; color:#312e81; font-size:12.5px; font-weight:600;">
                    Seleccione la duración en meses para calcular la fecha de cierre.
                </div>
            </div>
        </form>

        <div style="padding:14px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <div id="pc_feedback" style="font-size:12.5px; font-weight:600; color:#475569;"></div>
            <div style="display:flex; gap:8px;">
                <button type="button" onclick="cerrarModalPeriodoContrato()" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px;">
                    Cancelar
                </button>
                <button type="button" id="pc_btn_guardar" onclick="guardarPeriodoContratoModal()" class="btn-cycsa btn-cycsa-primary" style="min-height:38px; background:#3730a3; border-color:#3730a3; font-weight:700;">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Período
                </button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL MOTIVO CAMBIO DE PLANTILLA SUGERIDA -->
<div id="modalMotivoCambioPlantilla" class="modal-premium" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-premium-content" style="width: 440px; max-width:90%; background:white; margin:12% auto; padding:22px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1.5px solid #e2e8f0; padding-bottom:8px;">
            <h5 style="margin:0; font-size:15px; font-weight:700; color:#b45309;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Cambio de Plantilla Sugerida</h5>
            <button type="button" onclick="cancelarCambioPlantillaModal()" style="background:none; border:none; font-size:22px; cursor:pointer;">&times;</button>
        </div>
        <p style="font-size:12.5px; color:#334155; margin-bottom:12px;">
            Ha seleccionado una plantilla distinta a la sugerida por defecto. Según las directrices de trazabilidad <strong>ISO/IEC 17025</strong>, debe registrar el motivo técnico del cambio:
        </p>
        <div class="form-group mb-3">
            <textarea id="txtMotivoCambioModal" class="form-control-cycsa" rows="3" style="font-size:12.5px; width:100%;" placeholder="Ej: Cliente solicitó variante con columna de tamiz 1 1/4..."></textarea>
            <div id="errMotivoCambio" class="text-danger small mt-1" style="display:none; font-size:11px; color:#dc2626;">El motivo debe tener al menos 10 caracteres.</div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="cancelarCambioPlantillaModal()">Cancelar</button>
            <button type="button" class="btn-cycsa btn-cycsa-primary" onclick="confirmarCambioPlantillaModal()">Confirmar Cambio</button>
        </div>
    </div>
</div>

<!-- MODAL VISTA PREVIA PLANTILLA DE ENSAYO (READ ONLY) -->
<div id="modalVerPlantillaReadOnly" class="modal-premium" style="display:none; position:fixed; z-index:10001; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(3px);">
    <div class="modal-premium-content" style="width: 1000px; max-width:95%; max-height:90vh; background:white; margin:2% auto; padding:22px; border-radius:12px; box-shadow:0 15px 40px rgba(0,0,0,0.3); display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1.5px solid #e2e8f0; padding-bottom:10px;">
            <h5 style="margin:0; font-size:16px; font-weight:800; color:var(--cycsa-azul, #103487); font-family:'Outfit';">
                <i class="fa-solid fa-eye me-2"></i>Maqueta y Formato de Plantilla de Ensayo (ISO/IEC 17025)
            </h5>
            <button type="button" onclick="cerrarVerPlantillaReadOnly()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        <div id="contenidoVerPlantillaReadOnly" style="overflow-y:auto; flex:1;">
            <div style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>
        </div>
        <div style="display:flex; justify-content:flex-end; border-top:1px solid #e2e8f0; padding-top:10px; margin-top:10px;">
            <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="cerrarVerPlantillaReadOnly()">Cerrar Vista Previa</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL DE REVISIÓN Y DECISIÓN DE SUPERVISOR
     ========================================================================= -->
<div id="modalRevision" class="modal-premium">
    <div class="modal-premium-content" style="max-width: 600px;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #e2e8f0; padding-bottom:12px; margin-bottom:18px;">
            <h3 style="margin:0; font-family:'Outfit'; font-size:18px; color:#0f172a;">
                <i class="fa-solid fa-clipboard-check" style="color:var(--cycsa-azul);"></i> Revisión Técnica de Supervisor
            </h3>
            <button onclick="cerrarModalRevision()" class="btn-cerrar" style="font-size:24px; border:none; background:none; cursor:pointer; color:#64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/hojas-servicio/procesar-revision" id="form-revision-os">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="rev_id_os">
            <input type="hidden" name="estado" id="rev_nuevo_estado">

            <p style="font-size:13.5px; color:#475569; margin-bottom:15px;">
                Orden de Servicio: <strong id="rev_codigo_os" style="color:var(--cycsa-azul); font-family:monospace;"></strong>
            </p>

            <div id="wrapper-observacion" style="display:none; margin-bottom:15px;">
                <label style="font-size:12px; font-weight:700; color:#dc2626; display:block; margin-bottom:4px;">Motivo de la Observación (Se notificará al emisor):</label>
                <textarea name="motivo_observacion" id="rev_motivo" class="form-control-cycsa" rows="3" placeholder="Detalle las correcciones que debe realizar el emisor..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="cerrarModalRevision()">Cancelar</button>
                <button type="button" class="btn-cycsa btn-cycsa-danger" id="btn-observar-os" onclick="mostrarCampoObservacion()">
                    <i class="fa-solid fa-xmark"></i> Observar Hoja
                </button>
                <button type="button" class="btn-cycsa btn-cycsa-success" id="btn-aprobar-os" onclick="ejecutarAprobacionOS()">
                    <i class="fa-solid fa-check"></i> Aprobar Hoja RT-FM-13
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL DE APROBACIÓN DE ORDEN DE SERVICIO (PASO OBLIGATORIO PREVIO A RT-FM-13)
     ========================================================================= -->
<div id="modalAprobarOS" class="modal-premium" style="display:none; z-index:10000; align-items:center; justify-content:center;">
    <div class="modal-premium-content" style="max-width: 550px; border-radius: 12px; padding: 25px 30px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); background:white;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
            <div style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-clipboard-check" style="color: #059669;"></i> Aprobación Formal de Orden de Servicio
            </div>
            <button type="button" onclick="cerrarModalAprobarOS()" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/ordenes-servicio/aprobar" id="form-aprobar-os">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="aprobar_os_id" value="0">

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 15px; margin-bottom:15px; font-size:13px; color:#334155;">
                <div style="margin-bottom:6px;"><strong style="color:#0f172a;">Orden de Servicio:</strong> <span id="aprobar_os_codigo" style="font-family:monospace; font-weight:700; color:var(--cycsa-azul);">OS-2026-XXXX</span></div>
                <div style="margin-bottom:6px;"><strong style="color:#0f172a;">Cliente:</strong> <span id="aprobar_os_cliente">--</span></div>
                <div><strong style="color:#0f172a;">Proyecto:</strong> <span id="aprobar_os_proyecto">--</span></div>
            </div>

            <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:12px 15px; margin-bottom:14px; font-size:13px; color:#065f46; line-height:1.5;">
                <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
                Al aprobar formalmente esta Orden de Servicio, el equipo técnico y de laboratorio quedará habilitado para registrar las <strong>Hojas de Solicitud CYCSA-RT-FM-13</strong> y proceder con la recepción o muestreo de especímenes.
            </div>

            <div style="margin-bottom:16px;">
                <div style="font-size:12px; font-weight:700; color:#0f172a; margin-bottom:6px;">Ensayos habilitados para recepción</div>
                <p style="font-size:12px; color:#64748b; margin:0 0 8px 0;">Todos vienen marcados. Desmarque los que no se ejecutarán en esta O/S. La selección queda fija al aprobar y exige al menos un ensayo.</p>
                <div id="aprobar_os_ensayos" style="max-height:220px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; padding:8px 10px; background:#fff;"></div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="cerrarModalAprobarOS()" class="btn-cycsa btn-cycsa-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#059669;">
                    <i class="fa-solid fa-check-double"></i> Confirmar y Aprobar O/S
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ELECCIÓN DE MUESTREO VS INGRESO DIRECTO -->
<div id="modalDecisionMuestreoGlobal" class="modal-premium" style="display:none; z-index:10000; align-items:center; justify-content:center;">
    <div class="modal-premium-content" style="max-width: 740px; border-radius: 14px; padding: 28px 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); background:white;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
            <div style="font-family: 'Outfit', sans-serif; font-size: 19px; font-weight: 700; color: #0f172a; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-circle-question" style="color: var(--cycsa-azul);"></i> <span id="decision_titulo_modal">¿Cómo se Realizará el Muestreo de esta Hoja RT-FM-13?</span>
            </div>
            <button type="button" onclick="cerrarModalDecisionMuestreo()" style="background: none; border: none; font-size: 26px; color: #94a3b8; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:20px;">
            <span style="font-size:11.5px; text-transform:uppercase; color:#64748b; font-weight:700; display:block; letter-spacing:0.5px;">Orden de Servicio:</span>
            <div id="decision_codigo_os" style="font-size:16.5px; font-weight:700; color:var(--cycsa-azul); font-family:monospace; margin-top:2px;">OS-2026-XXXX</div>
            <div id="decision_subtitulo_entrega" style="font-size:12.5px; color:#64748b; margin-top:4px;"></div>
        </div>

        <p style="font-size: 14px; color: #475569; margin-bottom: 20px; line-height: 1.5;">
            Seleccione la modalidad de recepción o muestreo para esta Hoja de Solicitud de Servicio (CYCSA-RT-FM-13):
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 22px;">
            
            <!-- OPCIÓN 1: MUESTREO DIRECTO (LABORATORIO CENTRAL - PREFIJO MS) -->
            <div style="border: 2px solid #cbd5e1; border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; background: white; transition: all 0.2s;" onmouseover="this.style.borderColor='#10b981'; this.style.boxShadow='0 6px 18px rgba(16,185,129,0.12)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                <div>
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 14px;">
                        <i class="fa-solid fa-flask"></i>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Muestreo Directo</h4>
                        <span class="badge" style="background:#d1fae5; color:#065f46; font-family:monospace; font-weight:700; font-size:12px; padding: 4px 8px;">Prefijo MS</span>
                    </div>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0 0 20px 0;">
                        Los especímenes fueron entregados directamente por el cliente en el Laboratorio Central de CYCSA (sin requerir salida técnica a obra).
                    </p>
                </div>
                <div>
                    <button type="button" class="btn-cycsa btn-cycsa-success" style="width: 100%; min-height: 42px; font-size: 13.5px; font-weight: 700; display:flex; align-items:center; justify-content:center; gap:8px;" onclick="confirmarDecisionMuestreo('directo')">
                        <i class="fa-solid fa-file-circle-plus"></i> Abrir RT-FM-13 (Directo - MS)
                    </button>
                </div>
            </div>

            <!-- OPCIÓN 2: SE ENVÍA A CAMPO (MUESTREO EN OBRA - PREFIJO MC) -->
            <div style="border: 2px solid #cbd5e1; border-radius: 12px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; background: white; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--cycsa-azul)'; this.style.boxShadow='0 6px 18px rgba(16,52,135,0.12)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                <div>
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: #eff6ff; color: var(--cycsa-azul); display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 14px;">
                        <i class="fa-solid fa-truck-pickup"></i>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Se envía a campo</h4>
                        <span class="badge" style="background:#e0f2fe; color:#0369a1; font-family:monospace; font-weight:700; font-size:12px; padding: 4px 8px;">Prefijo MC</span>
                    </div>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0 0 20px 0;">
                        El personal técnico de CYCSA sale a obra a tomar las muestras. Abre el <strong>formulario para asignar técnico, vehículo, fechas y la Lista de Chequeo CYCSA-RT-FM-40 B</strong>.
                    </p>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <button type="button" class="btn-cycsa btn-cycsa-primary" style="width: 100%; min-height: 42px; font-size: 13.5px; font-weight: 700; display:flex; align-items:center; justify-content:center; gap:8px;" onclick="confirmarDecisionMuestreo('logistica')">
                        <i class="fa-solid fa-truck-pickup"></i> Asignar Técnicos y FM-40 B
                    </button>
                    <div id="btn_opcion_logistica_campo" style="text-align:center;">
                        <button type="button" class="btn btn-outline-primary" style="width: 100%; min-height: 36px; font-size: 12.5px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px;" onclick="confirmarDecisionMuestreo('campo')" title="Si las muestras ya fueron tomadas y solo desea registrar la RT-FM-13">
                            <i class="fa-solid fa-file-signature"></i> O abrir RT-FM-13 directo (Prefijo MC)
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalDecisionMuestreo()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 9px 22px; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer; min-height:38px;">
                Cancelar
            </button>
        </div>
    </div>
</div>

<script>
    // =========================================================================
    // 0. APROBACIÓN DE ORDEN DE SERVICIO (PASO PREVIO OBLIGATORIO)
    // =========================================================================
    function abrirModalAprobarOS(idOS, codigoOS, cliente, proyecto, btn) {
        document.getElementById('aprobar_os_id').value = idOS;
        document.getElementById('aprobar_os_codigo').innerText = codigoOS;
        document.getElementById('aprobar_os_cliente').innerText = cliente || '--';
        document.getElementById('aprobar_os_proyecto').innerText = proyecto || '--';
        const cont = document.getElementById('aprobar_os_ensayos');
        if (cont) {
            cont.innerHTML = '';
            let ensayos = [];
            try {
                ensayos = JSON.parse((btn && btn.getAttribute('data-ensayos')) || '[]');
            } catch (e) {
                ensayos = [];
            }
            if (!Array.isArray(ensayos) || ensayos.length === 0) {
                const aviso = document.createElement('div');
                aviso.style.cssText = 'color:#b45309; font-size:12.5px; padding:6px 2px;';
                aviso.textContent = 'Esta O/S no tiene ensayos en la cotización. No se puede aprobar sin al menos un ensayo.';
                cont.appendChild(aviso);
            } else {
                ensayos.forEach(function (ens) {
                    const etiqueta = document.createElement('label');
                    etiqueta.style.cssText = 'display:flex; align-items:flex-start; gap:8px; padding:6px 2px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#0f172a; cursor:pointer;';
                    const chk = document.createElement('input');
                    chk.type = 'checkbox';
                    chk.name = 'ensayos_permitidos[]';
                    chk.value = String(ens.id);
                    chk.checked = true;
                    chk.style.marginTop = '3px';
                    const texto = document.createElement('span');
                    const nombre = ens.nombre || 'Ensayo';
                    const extra = [ens.codigo, ens.norma].filter(Boolean).join(' · ');
                    texto.textContent = extra ? (nombre + ' (' + extra + ')') : nombre;
                    etiqueta.appendChild(chk);
                    etiqueta.appendChild(texto);
                    cont.appendChild(etiqueta);
                });
            }
        }
        const m = document.getElementById('modalAprobarOS');
        if (m) m.style.display = 'flex';
    }

    document.getElementById('form-aprobar-os')?.addEventListener('submit', function (ev) {
        const checks = this.querySelectorAll('input[name="ensayos_permitidos[]"]');
        const alguno = Array.prototype.some.call(checks, function (chk) { return chk.checked; });
        if (!checks.length || !alguno) {
            ev.preventDefault();
            alert('Seleccione al menos un ensayo para habilitar en la Orden de Servicio.');
        }
    });

    function cerrarModalAprobarOS() {
        const m = document.getElementById('modalAprobarOS');
        if (m) m.style.display = 'none';
    }

    // Variables de control para el modal de decisión
    let decisionOSId = null;
    let decisionOSCodigo = '';
    let esSegundaHojaDecision = false;

    function cerrarModalDecisionMuestreo() {
        const m = document.getElementById('modalDecisionMuestreoGlobal');
        if (m) m.style.display = 'none';
    }

    function abrirModalDecisionMuestreo(idOS, codigoOS, esSegundaHoja = false) {
        decisionOSId = idOS;
        decisionOSCodigo = codigoOS;
        esSegundaHojaDecision = !!esSegundaHoja;
        const lbl = document.getElementById('decision_codigo_os');
        if (lbl) lbl.innerText = codigoOS;
        const sub = document.getElementById('decision_subtitulo_entrega');
        if (sub) {
            sub.innerText = esSegundaHoja ? 'Añadiendo una nueva hoja de solicitud (entrega adicional) a esta O/S.' : 'Configuración de ingreso para la Hoja de Solicitud.';
        }
        const m = document.getElementById('modalDecisionMuestreoGlobal');
        if (m) m.style.display = 'flex';
    }

    function confirmarDecisionMuestreo(tipo) {
        if (!decisionOSId) return;
        const idOS = decisionOSId;
        const codigoOS = decisionOSCodigo;
        const esSegunda = esSegundaHojaDecision;
        cerrarModalDecisionMuestreo();

        const csrfVal = document.querySelector('input[name="csrf_token"]')?.value || '<?= $_SESSION['csrf_token'] ?? '' ?>';

        if (tipo === 'logistica') {
            fetch('/Cycsa/publico/ordenes-servicio/marcar-requiere-muestreo', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfVal
                },
                body: 'id_os=' + encodeURIComponent(idOS) + '&csrf_token=' + encodeURIComponent(csrfVal)
            }).finally(() => {
                window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + encodeURIComponent(idOS);
            });
            return;
        }

        const prefijo = (tipo === 'campo') ? 'MC' : 'MS';

        if (tipo === 'directo') {
            fetch('/Cycsa/publico/ordenes-servicio/marcar-ingreso-directo', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfVal
                },
                body: 'id_os=' + encodeURIComponent(idOS) + '&csrf_token=' + encodeURIComponent(csrfVal)
            }).catch(console.error);

            // Actualizar botones en el DOM
            const btns = document.querySelectorAll(`button[data-id-os="${idOS}"]`);
            btns.forEach(b => b.setAttribute('data-estado-muestreo', 'no_aplica'));
        } else if (tipo === 'campo') {
            fetch('/Cycsa/publico/ordenes-servicio/marcar-requiere-muestreo', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfVal
                },
                body: 'id_os=' + encodeURIComponent(idOS) + '&csrf_token=' + encodeURIComponent(csrfVal)
            }).catch(console.error);

            // Actualizar botones en el DOM
            const btns = document.querySelectorAll(`button[data-id-os="${idOS}"]`);
            btns.forEach(b => b.setAttribute('data-estado-muestreo', 'pendiente_programar'));
        }

        // Abrir siempre la Hoja RT-FM-13 con el prefijo seleccionado
        abrirModalHojaSolicitud(idOS, codigoOS, 0, true, prefijo);
    }

    // =========================================================================
    // 1. DESPLIEGUE INTELIGENTE DE FILAS (ACORDEÓN)
    // =========================================================================
    function toggleFilaDesplegable(idOS, e) {
        const filaPrincipal = document.getElementById('fila-os-' + idOS);
        const filaDesplegable = document.getElementById('desplegable-os-' + idOS);
        
        if (!filaDesplegable) return;

        const estaAbierta = filaDesplegable.classList.contains('mostrar');

        // Cerrar todas las demás filas para mantener la tabla limpia
        document.querySelectorAll('.fila-desplegable').forEach(el => el.classList.remove('mostrar'));
        document.querySelectorAll('.fila-principal').forEach(el => el.classList.remove('expandida'));

        if (!estaAbierta) {
            filaDesplegable.classList.add('mostrar');
            filaPrincipal.classList.add('expandida');
        }
    }

    // =========================================================================
    // 2. APERTURA DIRECTA DE "REGISTRAR HOJA RT-FM-13" (Sin diálogo previo)
    // =========================================================================
    function iniciarRegistroHojaRTFM13(btnOrId, codeParam, estadoParam, tecParam, idaParam, llegParam) {
        let idOS, codigoOS;

        if (typeof btnOrId === 'object' && btnOrId !== null) {
            idOS = btnOrId.getAttribute('data-id-os');
            codigoOS = btnOrId.getAttribute('data-codigo-os');
        } else {
            idOS = btnOrId;
            codigoOS = codeParam || 'O/S #' + idOS;
        }

        if (!idOS) return;
        abrirModalHojaSolicitud(idOS, codigoOS, 0, true);
    }

    // =========================================================================
    // 3. APERTURA Y CONTROL DEL MODAL HOJA DE SOLICITUD (CYCSA-RT-FM-13)
    // =========================================================================
    const modHS = document.getElementById('modalHojaSolicitud');
    const hsLoading = document.getElementById('loading-hoja-solicitud');
    const hsSplit = document.getElementById('wrapper-split-rt-fm-13');

    let idOSModalActual = 0;
    let codigoOSModalActual = '';

    function abrirModalHojaSolicitud(idOS, code, idHoja = 0, esNueva = false, prefijo = '', idDetalle = 0) {
        rehabilitarBotonesEnvioHoja();
        idOSModalActual = idOS;
        codigoOSModalActual = code;
        document.getElementById('hs_codigo_os_label').innerText = code;
        const elFootCode = document.getElementById('hs_footer_os_code');
        if (elFootCode) elFootCode.innerText = code;
        const elHiddenPlt = document.getElementById('select_plantilla_global');
        if (elHiddenPlt) elHiddenPlt.value = '';
        const triggerTexto = document.getElementById('texto_plantilla_seleccionada');
        if (triggerTexto) {
            triggerTexto.innerHTML = '<span style="color:#64748b; font-size:12px;"><i class="fa-solid fa-layer-group me-1"></i>Seleccionar plantilla para toda la hoja...</span>';
        }
        window.plantillasCache = [];
        modHS.style.display = 'block';
        hsLoading.style.display = 'block';
        hsSplit.style.display = 'none';
        const selCont = document.getElementById('hs_selector_hojas_container');
        if (selCont) selCont.style.display = 'none';

        let url = '/Cycsa/publico/hojas-servicio/datos?id_os=' + encodeURIComponent(idOS);
        if (idHoja > 0) {
            url += '&id_hoja=' + encodeURIComponent(idHoja);
        }
        if (esNueva) {
            url += '&nueva=1';
        }
        if (prefijo) {
            url += '&prefijo=' + encodeURIComponent(prefijo);
        }
        if (idDetalle > 0) {
            url += '&id_detalle_cotizacion=' + encodeURIComponent(idDetalle);
        }

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(async res => {
                if (!res.ok) {
                    let detail = '';
                    try {
                        const errJson = await res.json();
                        if (errJson && errJson.message) detail = ': ' + errJson.message;
                    } catch (e) {}
                    throw new Error('HTTP ' + res.status + ' (' + res.statusText + ')' + detail);
                }
                return res.json();
            })
            .then(data => {
                hsLoading.style.display = 'none';
                if (data.status === 'success') {
                    hsSplit.style.display = 'grid';
                    llenarModalHojaSolicitud(data);
                } else {
                    alert('Error: ' + (data.message || 'No se pudieron obtener los datos.'));
                    cerrarModalHojaSolicitud();
                }
            })
            .catch(err => {
                console.error('Error al cargar Hoja RT-FM-13:', err);
                alert('Error al comunicarse con el servidor: ' + err.message);
                cerrarModalHojaSolicitud();
            });
    }

    function actualizarInfoProductoSeleccionado() {
        const selProd = document.getElementById('id_detalle_cotizacion_modal');
        const elInfo = document.getElementById('hs_ensayo_info_text');
        if (!selProd || !elInfo) return;
        if (selProd.selectedIndex > 0) {
            elInfo.innerText = selProd.options[selProd.selectedIndex].text;
        } else {
            elInfo.innerText = 'Ítem O/S Vinculado';
        }
    }

    function alCambiarProductoModalRTFM13(idDetalle) {
        actualizarInfoProductoSeleccionado();
        if (!idDetalle || !idOSModalActual) return;
        const elIdHoja = document.getElementById('hs_id_hoja');
        const esNueva = (!elIdHoja || elIdHoja.value == '0' || elIdHoja.value == '');
        if (esNueva) {
            abrirModalHojaSolicitud(idOSModalActual, codigoOSModalActual, 0, true, prefijoMuestraActual, parseInt(idDetalle, 10));
        } else {
            const productos = window.productosPermitidosActuales || [];
            const prod = productos.find(p => String(p.id_detalle_cotizacion) === String(idDetalle));
            if (prod) {
                window.productoHojaIdActual = prod.id_producto || prod.ensayo_id;
                window.esCompactacionHojaActual = !!prod.es_compactacion;
                const elHiddenPlt = document.getElementById('select_plantilla_global');
                if (elHiddenPlt) elHiddenPlt.value = '';
                const triggerTexto = document.getElementById('texto_plantilla_seleccionada');
                if (triggerTexto) {
                    triggerTexto.innerHTML = '<span style="color:#64748b; font-size:12px;"><i class="fa-solid fa-layer-group me-1"></i>Seleccionar plantilla para toda la hoja...</span>';
                }
                poblarSelectorPlantillaGlobal(window.productoHojaIdActual, window.clienteIdActualHoja, null);
            }
        }
    }

    function cambiarPrefijoOficial(prefix) {
        if (prefix !== 'MC' && prefix !== 'MS') return;
        prefijoMuestraActual = prefix;
        const inputPref = document.getElementById('hs_prefijo_seleccionado');
        if (inputPref) inputPref.value = prefix;

        const btnMC = document.getElementById('btn_prefijo_mc');
        const btnMS = document.getElementById('btn_prefijo_ms');
        if (btnMC && btnMS) {
            btnMC.style.display = 'inline-flex';
            btnMS.style.display = 'inline-flex';
            if (prefix === 'MC') {
                btnMC.classList.add('active');
                btnMC.style.background = '#0284c7';
                btnMC.style.color = '#fff';
                btnMC.style.borderColor = '#0284c7';
                btnMS.classList.remove('active');
                btnMS.style.background = '#fff';
                btnMS.style.color = '#475569';
                btnMS.style.borderColor = '#cbd5e1';
            } else {
                btnMS.classList.add('active');
                btnMS.style.background = '#059669';
                btnMS.style.color = '#fff';
                btnMS.style.borderColor = '#059669';
                btnMC.classList.remove('active');
                btnMC.style.background = '#fff';
                btnMC.style.color = '#475569';
                btnMC.style.borderColor = '#cbd5e1';
            }
        }
        const elInd = document.getElementById('hs_prefijo_indicador');
        if (elInd) elInd.innerText = prefix;
    }

    function seleccionarPrefijoMuestraModal(prefix) {
        if (prefix !== 'MC' && prefix !== 'MS') return;
        cambiarPrefijoOficial(prefix);

        const baseInicial = prefix === 'MS' ? siguienteConsecutivoMS : siguienteConsecutivoMC;
        siguienteConsecutivoMuestraInicial = baseInicial;
        siguienteConsecutivoMuestra = baseInicial;

        const idHojaActual = parseInt(document.getElementById('hs_id_hoja')?.value || '0', 10) || 0;
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;

        // En hojas nuevas (sin emitir aún), recodificar filas no bloqueadas al prefijo elegido
        if (idHojaActual === 0) {
            let seq = baseInicial;
            const esCompactacion = !!window.esCompactacionHojaActual;
            tbody.querySelectorAll('tr:not(#fila-sin-muestras)').forEach(tr => {
                const inpBloq = tr.querySelector('input[name="m_bloqueado[]"]');
                if (inpBloq && parseInt(inpBloq.value || '0', 10) === 1) return;

                const inpNom = tr.querySelector('input[name="m_nombre[]"]');
                const badge = tr.querySelector('td:first-child .badge');
                const inpDesc = tr.querySelector('input[name="m_desc[]"]');
                const inpInfo = tr.querySelector('input[name="m_info[]"]');

                if (inpNom) {
                    const consecutiveStr = String(seq).padStart(4, '0');
                    inpNom.value = `${prefix}-${consecutiveStr}-${anioActual2Digitos}`;
                    seq++;
                }
                if (badge) {
                    badge.textContent = prefix;
                    badge.classList.remove('bg-primary', 'bg-secondary', 'bg-success');
                    badge.classList.add(prefix === 'MS' ? 'bg-success' : 'bg-primary');
                }
                if (inpDesc && (inpDesc.value === 'Muestra tomada en campo' || inpDesc.value === 'Muestra entregada en laboratorio')) {
                    inpDesc.value = (prefix === 'MS') ? 'Muestra entregada en laboratorio' : 'Muestra tomada en campo';
                }
                if (inpInfo && (inpInfo.value === 'Recepción Lab Central' || inpInfo.value === 'Ensayo in situ' || inpInfo.value === 'Muestreo en Obra')) {
                    inpInfo.value = (prefix === 'MS') ? 'Recepción Lab Central' : (esCompactacion ? 'Ensayo in situ' : 'Muestreo en Obra');
                }
            });
            siguienteConsecutivoMuestra = seq;
        }
    }

    function actualizarContadorEspecimenes() {
        const tbody = document.getElementById('hs-tbody-muestras');
        const cnt = tbody ? tbody.querySelectorAll('tr:not(#fila-sin-muestras)').length : 0;
        const elLabel = document.getElementById('hs_cnt_especimenes_label');
        if (elLabel) elLabel.innerText = `${cnt} espécimen(es)`;
        const elFoot = document.getElementById('hs_footer_cant_muestras');
        if (elFoot) elFoot.innerText = cnt;
    }

    let guardandoHojaEnProceso = false;

    function deshabilitarBotonesEnvioHoja(texto) {
        guardandoHojaEnProceso = true;
        const btnEmitir = document.getElementById('hs_btn_emitir_oficial');
        const btnDraft = document.getElementById('hs_btn_guardar_borrador');
        if (btnEmitir) {
            btnEmitir.disabled = true;
            btnEmitir.setAttribute('disabled', 'disabled');
            btnEmitir.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> ${texto || 'Emitiendo...'}`;
            btnEmitir.style.opacity = '0.6';
            btnEmitir.style.cursor = 'not-allowed';
        }
        if (btnDraft) {
            btnDraft.disabled = true;
            btnDraft.setAttribute('disabled', 'disabled');
            btnDraft.style.opacity = '0.6';
            btnDraft.style.cursor = 'not-allowed';
        }
        setTimeout(() => {
            if (guardandoHojaEnProceso) {
                rehabilitarBotonesEnvioHoja();
            }
        }, 15000);
    }

    function rehabilitarBotonesEnvioHoja() {
        guardandoHojaEnProceso = false;
        const btnEmitir = document.getElementById('hs_btn_emitir_oficial');
        const btnDraft = document.getElementById('hs_btn_guardar_borrador');
        if (btnEmitir) {
            btnEmitir.disabled = false;
            btnEmitir.removeAttribute('disabled');
            btnEmitir.innerHTML = `<i class="fa-solid fa-circle-check"></i> Emitir Hoja Oficial (RT-FM-13)`;
            btnEmitir.style.opacity = '1';
            btnEmitir.style.cursor = 'pointer';
        }
        if (btnDraft) {
            btnDraft.disabled = false;
            btnDraft.removeAttribute('disabled');
            btnDraft.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Guardar Borrador`;
            btnDraft.style.opacity = '1';
            btnDraft.style.cursor = 'pointer';
        }
    }

    function guardarBorradorHoja() {
        if (guardandoHojaEnProceso) return;
        const chkRecepcion = document.getElementById('chk_confirmar_recepcion_lims');
        if (chkRecepcion) {
            chkRecepcion.checked = false;
        }
        const form = document.getElementById('form-hoja-solicitud');
        if (form) {
            if (form.checkValidity && !form.checkValidity()) {
                form.reportValidity();
            } else {
                deshabilitarBotonesEnvioHoja('Guardando Borrador...');
                form.submit();
            }
        }
    }

    function validarYEnviarHojaOficial(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (guardandoHojaEnProceso) {
            return false;
        }
        const form = document.getElementById('form-hoja-solicitud');
        if (!form) return false;
        
        const tbody = document.getElementById('hs-tbody-muestras');
        const muestrasCount = tbody ? tbody.querySelectorAll('tr:not(#fila-sin-muestras)').length : 0;
        if (muestrasCount === 0) {
            alert('Atención: Debe agregar al menos una muestra antes de emitir la hoja oficial.');
            return false;
        }

        if (form.checkValidity && !form.checkValidity()) {
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                const labelText = firstInvalid.closest('.rtfm13-form-group')?.querySelector('label')?.innerText || firstInvalid.name || 'campo requerido';
                alert('Por favor complete el campo requerido: ' + labelText.replace(/[:*]/g, '').trim());
                firstInvalid.focus();
            } else if (form.reportValidity) {
                form.reportValidity();
            }
            return false;
        }

        const isRecepcion = document.getElementById('chk_confirmar_recepcion_lims')?.checked;
        if (isRecepcion) {
            let faltaPlantilla = false;
            let nombreFaltante = '';
            document.querySelectorAll('#hs-tbody-muestras tr').forEach(tr => {
                const selPlt = tr.querySelector('select[name="m_plantilla_id[]"]');
                const inpNom = tr.querySelector('input[name="m_nombre[]"]');
                if (selPlt && (!selPlt.value || selPlt.value === '0')) {
                    faltaPlantilla = true;
                    nombreFaltante = inpNom ? inpNom.value : 'Espécimen';
                }
            });
            if (faltaPlantilla) {
                alert(`Validación ISO 17025:\n\nLa muestra "${nombreFaltante}" no tiene asignada una plantilla de ensayo aprobada.\nNo se puede admitir la recepción física sin plantilla vinculada.`);
                return false;
            }
        }

        deshabilitarBotonesEnvioHoja('Emitiendo Hoja...');
        form.submit();
        return false;
    }

    function cargarNuevaHojaEnModal() {
        if (!idOSModalActual) return;
        abrirModalHojaSolicitud(idOSModalActual, codigoOSModalActual, 0, true);
    }

    let prefijoMuestraActual = 'MC';
    let siguienteConsecutivoMuestra = 1;
    let siguienteConsecutivoMuestraInicial = 1;
    let siguienteConsecutivoMC = 1;
    let siguienteConsecutivoMS = 1;
    let anioActual2Digitos = String(new Date().getFullYear()).slice(-2);

    function llenarModalHojaSolicitud(data) {
        const h = data.hoja;
        const os = data.os_referencia;
        siguienteConsecutivoMC = parseInt(data.siguiente_consecutivo_mc || 1, 10) || 1;
        siguienteConsecutivoMS = parseInt(data.siguiente_consecutivo_ms || 1, 10) || 1;
        prefijoMuestraActual = data.prefijo_muestra || 'MC';
        if (!data.es_nueva && data.detalles_muestras_relacionales && data.detalles_muestras_relacionales.length > 0) {
            const firstCode = data.detalles_muestras_relacionales[0].codigo_muestra || '';
            if (firstCode.startsWith('MS-')) prefijoMuestraActual = 'MS';
            else if (firstCode.startsWith('MC-')) prefijoMuestraActual = 'MC';
        }
        siguienteConsecutivoMuestra = parseInt(
            data.siguiente_consecutivo_muestra || (prefijoMuestraActual === 'MS' ? siguienteConsecutivoMS : siguienteConsecutivoMC) || 1,
            10
        ) || 1;
        siguienteConsecutivoMuestraInicial = siguienteConsecutivoMuestra;
        cambiarPrefijoOficial(prefijoMuestraActual);

        const badgeStat = document.getElementById('hs_badge_status_hoja');
        if (badgeStat) {
            if (data.es_nueva) {
                badgeStat.className = 'badge-rtfm13-status badge-rtfm13-status-borrador';
                badgeStat.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i> Borrador';
            } else if (h && (h.congelado_recepcion || h.firma_recibe_cycsa)) {
                badgeStat.className = 'badge-rtfm13-status badge-rtfm13-status-congelada';
                badgeStat.innerHTML = '<i class="fa-solid fa-lock"></i> Congelada ISO 17025';
            } else {
                badgeStat.className = 'badge-rtfm13-status badge-rtfm13-status-emitida';
                badgeStat.innerHTML = '<i class="fa-solid fa-circle-check"></i> Emitida #' + (h.numero_registro || h.id);
            }
        }
        window.ensayosDisponiblesOS = (data.ensayos_disponibles_os || []).filter(function (ens) {
            return ens.permitido !== 0 && ens.permitido !== '0' && ens.permitido !== false;
        });
        window.clienteIdActualHoja = data.cliente_id || '';
        window.productoHojaIdActual = data.producto_hoja_id || (h ? (h.id_producto || h.ensayo_id || 0) : 0);
        window.esCompactacionHojaActual = typeof data.es_compactacion_hoja !== 'undefined' ? !!data.es_compactacion_hoja : false;
        window.plantillaSugeridaHojaActual = data.plantilla_sugerida_hoja || null;
        window.productosPermitidosActuales = data.productos_permitidos_os || data.productos_permitidos || [];

        const alertaAlcEl = document.getElementById('hs_alerta_alcance_mensual');
        if (alertaAlcEl) {
            const alc = data.alerta_alcance_mensual || {};
            if (alc.tiene_alerta && Array.isArray(alc.mensajes) && alc.mensajes.length > 0) {
                const esDanger = alc.nivel === 'danger';
                alertaAlcEl.style.display = 'block';
                alertaAlcEl.style.background = esDanger ? '#fef2f2' : '#fffbeb';
                alertaAlcEl.style.border = esDanger ? '1.5px solid #fca5a5' : '1.5px solid #fcd34d';
                alertaAlcEl.style.color = esDanger ? '#991b1b' : '#92400e';
                alertaAlcEl.innerHTML = '<strong><i class="fa-solid fa-triangle-exclamation"></i> Alerta de Alcance Mensual / Vigencia:</strong><ul style="margin:6px 0 0 18px; padding:0;">' +
                    alc.mensajes.map(m => '<li>' + escapeHtml(String(m)) + '</li>').join('') +
                    '</ul>';
            } else {
                alertaAlcEl.style.display = 'none';
                alertaAlcEl.innerHTML = '';
            }
        }

        poblarSelectorPlantillaGlobal(window.productoHojaIdActual, window.clienteIdActualHoja, window.plantillaSugeridaHojaActual);
        aplicarFiltroWhitelistReq(window.ensayosDisponiblesOS, !!data.es_nueva);

        // Set id_hoja hidden field
        const elIdHoja = document.getElementById('hs_id_hoja');
        if (elIdHoja) {
            elIdHoja.value = data.es_nueva ? 0 : (h.id || 0);
        }

        // Guardar lista de productos de la O/S para asignar por muestra (en RT-FM-13 o después en Datos de Recepción)
        window.productosPermitidosActuales = data.productos_permitidos_os || data.productos_permitidos || [];
        const selProd = document.getElementById('id_detalle_cotizacion_modal');
        if (selProd) {
            const idActualProd = parseInt(h.id_detalle_cotizacion || 0, 10) || 0;
            selProd.value = (!data.es_nueva && idActualProd > 0) ? String(idActualProd) : '';
        }

        // Renderizado del Selector de Hojas / Entregas
        const selContainer = document.getElementById('hs_selector_hojas_container');
        const tabsContainer = document.getElementById('hs_lista_tabs_hojas');
        const btnNuevaEntrega = document.getElementById('hs_btn_nueva_entrega');
        if (selContainer && tabsContainer) {
            selContainer.style.display = 'flex';
            tabsContainer.innerHTML = '';

            const listaHojas = data.todas_las_hojas || [];
            const idActual = data.id_hoja_actual || (h ? h.id : 0);
            const esNuevaHoja = !!data.es_nueva;

            if (listaHojas.length > 0) {
                listaHojas.forEach((itemHoja, idx) => {
                    const isSelected = (!esNuevaHoja && itemHoja.id == idActual);
                    const btnTab = document.createElement('button');
                    btnTab.type = 'button';
                    btnTab.style.cssText = `padding: 5px 12px; font-size: 11.5px; font-weight: 600; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border: 1.5px solid ${isSelected ? '#0284c7' : '#cbd5e1'}; background: ${isSelected ? '#e0f2fe' : 'white'}; color: ${isSelected ? '#0369a1' : '#475569'}; transition: all 0.15s;`;
                    let labelProd = itemHoja.descripcion_ensayo ? ` · <span style="font-size:10px; color:#0369a1; font-weight:600;">${escapeHtml(itemHoja.descripcion_ensayo)}</span>` : '';
                    btnTab.innerHTML = `<i class="fa-solid fa-file-lines" style="color:${isSelected ? '#0284c7' : '#94a3b8'};"></i> Hoja #${itemHoja.numero_secuencial || (idx + 1)} <span style="font-size:10px; font-family:monospace; background:${isSelected ? '#bae6fd' : '#f1f5f9'}; padding:1px 5px; border-radius:4px; font-weight:700;">${itemHoja.numero_registro || 'Sin Reg'}</span>${labelProd}`;
                    btnTab.onclick = () => {
                        abrirModalHojaSolicitud(idOSModalActual, codigoOSModalActual, itemHoja.id, false);
                    };
                    tabsContainer.appendChild(btnTab);
                });
            }

            if (esNuevaHoja) {
                const badgeNueva = document.createElement('span');
                badgeNueva.style.cssText = 'padding: 5px 12px; font-size: 11.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; border: 1.5px solid #10b981; background: #d1fae5; color: #065f46;';
                badgeNueva.innerHTML = '<i class="fa-solid fa-plus-circle"></i> Nueva Hoja (Borrador Entrega)';
                tabsContainer.appendChild(badgeNueva);
            }

            if (btnNuevaEntrega) {
                btnNuevaEntrega.style.opacity = esNuevaHoja ? '0.6' : '1';
                btnNuevaEntrega.disabled = esNuevaHoja;
            }
        }

        // Referencia O/S
        if (os) {
            document.getElementById('ref_os_cliente').innerText = os.cliente_nombre || '--';
            document.getElementById('ref_os_rfc').innerText = os.cliente_rfc || '--';
            document.getElementById('ref_os_atencion').innerText = os.atencion_a || '--';
            document.getElementById('ref_os_proyecto').innerText = os.nombre_proyecto || '--';
            const elDirRef = document.getElementById('ref_os_direccion');
            if (elDirRef) {
                elDirRef.innerText = os.direccion_proyecto || os.cliente_direccion || '--';
            }
            document.getElementById('ref_os_cotizacion').innerText = os.cotizacion_codigo || '--';
            document.getElementById('ref_os_pago').innerText = os.forma_pago || '--';
            
            const boxLog = document.getElementById('ref_os_logistica_box');
            if (os.programacion_muestreo) {
                boxLog.style.display = 'block';
                document.getElementById('ref_os_tecnico').innerText = os.programacion_muestreo.tecnico_nombre || 'No asignado';
                document.getElementById('ref_os_vehiculo').innerText = (os.programacion_muestreo.marca ? os.programacion_muestreo.marca + ' ' + os.programacion_muestreo.modelo + ' (' + os.programacion_muestreo.placa + ')' : 'No asignado');
                document.getElementById('ref_os_fechas').innerText = (os.programacion_muestreo.fecha_ida || '') + ' a ' + (os.programacion_muestreo.fecha_llegada || '');
            } else {
                boxLog.style.display = 'none';
            }

            const tbEns = document.getElementById('ref_os_tbody_ensayos');
            tbEns.innerHTML = '';
            if (os.ensayos && os.ensayos.length > 0) {
                let itemNum = 1;
                os.ensayos.forEach(ens => {
                    if (ens.permitido === 0 || ens.permitido === '0' || ens.permitido === false) {
                        return;
                    }
                    const tr = document.createElement('tr');
                    const codCampo = ens.codigo_hoja_campo || ens.codigo_servicio || '';
                    const cond = ens.condiciones_muestra || 'Estándar / Muestra sin alteración';
                    const proc = ens.procedimiento || 'CYCSA-PE-01';
                    const um = ens.unidad_medida || 'Unidad';
                    const cant = parseFloat(ens.cantidad || 1).toFixed(2);

                    tr.innerHTML = `
                        <td style="padding:6px 4px; border-bottom:1px solid #e2e8f0; text-align:center; font-weight:700; color:#64748b; font-size:11px;">${itemNum}</td>
                        <td style="padding:6px; border-bottom:1px solid #e2e8f0;">
                            <div style="font-weight:700; color:#0f172a; font-size:11px; line-height:1.2;">${ens.nombre_ensayo || ens.descripcion_ensayo || ''}</div>
                            ${codCampo ? `<div style="font-size:9.5px; color:var(--cycsa-azul); font-weight:700; font-family:monospace; margin-top:2px;">${codCampo}</div>` : ''}
                        </td>
                        <td style="padding:6px; border-bottom:1px solid #e2e8f0; font-size:10px; color:#475569; line-height:1.3;">
                            ${cond}
                        </td>
                        <td style="padding:6px; border-bottom:1px solid #e2e8f0; font-size:10px; color:#0369a1; font-weight:600; font-family:monospace;">
                            ${proc}
                        </td>
                        <td style="padding:6px; border-bottom:1px solid #e2e8f0; text-align:center; font-size:10px; color:#64748b;">
                            ${um}
                        </td>
                        <td style="padding:6px; border-bottom:1px solid #e2e8f0; text-align:center; font-weight:700; font-size:11px; color:#0f172a;">
                            ${cant}
                        </td>
                    `;
                    tbEns.appendChild(tr);
                    itemNum++;
                });
            } else {
                tbEns.innerHTML = '<tr><td colspan="6" style="padding:10px; text-align:center; color:#94a3b8; font-size:11px;">Sin ensayos registrados en la O/S</td></tr>';
            }
        }

        // Formulario RT-FM-13
        const elIdOs = document.getElementById('hs_id_os');
        if (elIdOs) elIdOs.value = h.id_os || '';
        const elReg = document.getElementById('hs_numero_registro');
        if (elReg) elReg.value = h.numero_registro || '';
        const elFechaLlegada = document.getElementById('hs_fecha_llegada');
        if (elFechaLlegada) elFechaLlegada.value = h.fecha_hora_llegada_laboratorio ? h.fecha_hora_llegada_laboratorio.replace(' ', 'T').substring(0, 16) : '';
        const elCodDoc = document.getElementById('hs_codigo_documento');
        if (elCodDoc) elCodDoc.value = h.codigo_documento || 'CYCSA-RT-FM-13';
        const elEmpresa = document.getElementById('hs_nombre_empresa');
        if (elEmpresa) elEmpresa.value = h.nombre_empresa_o_cliente || '';
        const elRazon = document.getElementById('hs_razon_social');
        if (elRazon) elRazon.value = h.razon_social || '';
        const elDir = document.getElementById('hs_direccion');
        if (elDir) elDir.value = h.direccion_proyecto || '';
        const elTel = document.getElementById('hs_telefono');
        if (elTel) elTel.value = h.telefono || '';
        const elEmail = document.getElementById('hs_email');
        if (elEmail) elEmail.value = h.correo_electronico || '';
        const elEntrega = document.getElementById('hs_persona_entrega');
        if (elEntrega) elEntrega.value = h.nombre_persona_entrega_muestra || '';
        const elProcedencia = document.getElementById('hs_procedencia');
        if (elProcedencia) elProcedencia.value = h.procedencia_punto_muestreo || '';
        const elPersonaToma = document.getElementById('hs_persona_toma');
        if (elPersonaToma) elPersonaToma.value = h.nombre_persona_toma_muestra || '';
        const elFechaToma = document.getElementById('hs_fecha_toma');
        if (elFechaToma) elFechaToma.value = h.fecha_hora_toma_muestra ? h.fecha_hora_toma_muestra.replace(' ', 'T').substring(0, 16) : '';

        // Naturaleza Checkboxes
        const natureList = (h.naturaleza_muestra || '').split(',').map(s => s.trim());
        document.querySelectorAll('.hs-nat-checkbox').forEach(cb => {
            cb.checked = natureList.includes(cb.value);
        });

        // Especímenes / Muestras Dinámicas
        const tbodyM = document.getElementById('hs-tbody-muestras');
        if (tbodyM) tbodyM.innerHTML = '';
        let muestrasArr = [];
        try {
            if (h.identificacion_muestras_json) {
                muestrasArr = JSON.parse(h.identificacion_muestras_json);
            } else if (h.muestras_json) {
                muestrasArr = JSON.parse(h.muestras_json);
            }
        } catch(e) { muestrasArr = []; }

        if (data.detalles_muestras_relacionales && Array.isArray(data.detalles_muestras_relacionales) && data.detalles_muestras_relacionales.length > 0) {
            data.detalles_muestras_relacionales.forEach((det) => {
                const nom = det.codigo_muestra || '';
                const tipo = nom.startsWith('MS-') ? 'MS' : (nom.startsWith('MC-') ? 'MC' : prefijoMuestraActual);
                agregarFilaMuestraModal(
                    tipo,
                    nom,
                    det.descripcion_ubicacion || '',
                    det.observacion_edad || '',
                    det.ensayo_id || window.productoHojaIdActual || null,
                    det.plantilla_id || null,
                    parseInt(det.seleccion_manual || 0),
                    det.motivo_cambio || '',
                    parseInt(det.bloqueado_recepcion || 0),
                    det.plantilla_codigo || '',
                    det.plantilla_nombre || '',
                    parseInt(det.id_detalle_cotizacion || h.id_detalle_cotizacion || 0, 10) || 0
                );
            });
        } else if (Array.isArray(muestrasArr) && muestrasArr.length > 0) {
            muestrasArr.forEach((m) => {
                const nom = m.nombre_muestra || m.identificacion || '';
                const tipo = nom.startsWith('MS-') ? 'MS' : (nom.startsWith('MC-') ? 'MC' : prefijoMuestraActual);
                agregarFilaMuestraModal(
                    tipo,
                    nom,
                    m.descripcion || m.ubicacion || '',
                    m.info_importante || m.observaciones || '',
                    m.ensayo_id || window.productoHojaIdActual || null,
                    m.plantilla_id || null,
                    parseInt(m.seleccion_manual || 0),
                    m.motivo_cambio || '',
                    parseInt(m.bloqueado || 0),
                    m.plantilla_codigo || '',
                    m.plantilla_nombre || '',
                    parseInt(m.id_detalle_cotizacion || h.id_detalle_cotizacion || 0, 10) || 0
                );
            });
        } else {
            // Pre-poblar al menos 1 espécimen inicial con el correlativo correspondiente
            agregarFilaMuestraModal();
        }

        // Checkboxes de Análisis con comprobación segura de nulidad
        const elConc = document.getElementById('hs_req_concreto') || document.getElementById('hs_req_resistencia_concreto');
        if (elConc) elConc.checked = (parseInt(h.req_resistencia_concreto) === 1);
        const elAdo = document.getElementById('hs_req_adoquin') || document.getElementById('hs_req_resistencia_adoquin');
        if (elAdo) elAdo.checked = (parseInt(h.req_resistencia_adoquin) === 1);
        const elBlo = document.getElementById('hs_req_bloques') || document.getElementById('hs_req_resistencia_bloques');
        if (elBlo) elBlo.checked = (parseInt(h.req_resistencia_bloques) === 1);
        const elOtCon = document.getElementById('hs_req_otros_concreto');
        if (elOtCon) elOtCon.value = h.req_otros_concreto || '';

        const elGran = document.getElementById('hs_req_granulometria');
        if (elGran) elGran.checked = (parseInt(h.req_granulometria) === 1);
        const elLim = document.getElementById('hs_req_limites') || document.getElementById('hs_req_limites_atterberg');
        if (elLim) elLim.checked = (parseInt(h.req_limites_atterberg) === 1);
        const elHum = document.getElementById('hs_req_humedad');
        if (elHum) elHum.checked = (parseInt(h.req_humedad) === 1);
        const elCorte = document.getElementById('hs_req_resistencia_corte') || document.getElementById('hs_req_corte');
        if (elCorte) elCorte.checked = (parseInt(h.req_resistencia_corte) === 1);
        const elSucs = document.getElementById('hs_req_clasificacion_sucs_hr') || document.getElementById('hs_req_sucs');
        if (elSucs) elSucs.checked = (parseInt(h.req_clasificacion_sucs_hr) === 1);
        const elProc = document.getElementById('hs_req_proctor') || document.getElementById('hs_req_proctor_sm');
        if (elProc) elProc.checked = (parseInt(h.req_proctor_sm) === 1);
        const elInf = document.getElementById('hs_req_infiltracion');
        if (elInf) elInf.checked = (parseInt(h.req_infiltracion) === 1);
        const elCbr = document.getElementById('hs_req_cbr');
        if (elCbr) elCbr.checked = (parseInt(h.req_cbr) === 1);
        const elDens = document.getElementById('hs_req_densidad');
        if (elDens) elDens.checked = (parseInt(h.req_densidad) === 1);
        const elOtSuel = document.getElementById('hs_req_otros_suelo');
        if (elOtSuel) elOtSuel.value = h.req_otros_suelo || '';

        const elOtMat = document.getElementById('hs_req_otros_materiales') || document.getElementById('hs_req_otros_mat');
        if (elOtMat) elOtMat.checked = (parseInt(h.req_otros_materiales) === 1);
        const elDescOt = document.getElementById('hs_descripcion_otros');
        if (elDescOt) elDescOt.value = h.descripcion_otros_analisis || '';
        
        const elAnAd = document.getElementById('hs_analisis_adicionales');
        if (elAnAd) elAnAd.value = h.analisis_adicionales || '';
        const elObs = document.getElementById('hs_observaciones');
        if (elObs) elObs.value = h.observaciones || '';
        
        const elRecibe = document.getElementById('hs_nombre_recibe');
        if (elRecibe) elRecibe.value = h.nombre_recibe_cycsa || '';
        const elFirmaRecibe = document.getElementById('hs_firma_recibe_cycsa');
        if (elFirmaRecibe) elFirmaRecibe.checked = (parseInt(h.firma_recibe_cycsa) === 1);
        const elFirmaCli = document.getElementById('hs_firma_cliente');
        if (elFirmaCli) elFirmaCli.checked = (parseInt(h.firma_cliente) === 1);
        const elFirmaAdNom = document.getElementById('hs_firma_adicional_nombre');
        if (elFirmaAdNom) elFirmaAdNom.value = h.firma_adicional_nombre || '';
        const elFirmaAdCargo = document.getElementById('hs_firma_adicional_cargo');
        if (elFirmaAdCargo) elFirmaAdCargo.value = h.firma_adicional_cargo || '';
        if (typeof toggleFirmaAdicionalRTFM13 === 'function') {
            toggleFirmaAdicionalRTFM13(parseInt(h.incluir_firma_adicional || 0, 10) === 1);
        }

        actualizarInfoProductoSeleccionado();
        actualizarContadorEspecimenes();
    }

    function toggleFirmaAdicionalRTFM13(forzarEstado) {
        const hiddenEl = document.getElementById('hs_incluir_firma_adicional');
        const panelEl = document.getElementById('panel_firma_adicional_rtfm13');
        const btnEl = document.getElementById('btn_toggle_firma_adicional_rtfm13');
        if (!hiddenEl || !panelEl || !btnEl) return;
        const actual = hiddenEl.value === '1';
        const activar = (typeof forzarEstado === 'boolean') ? forzarEstado : !actual;
        hiddenEl.value = activar ? '1' : '0';
        panelEl.style.display = activar ? 'block' : 'none';
        if (activar) {
            btnEl.classList.remove('btn-outline-primary');
            btnEl.classList.add('btn-primary');
            btnEl.innerHTML = '<i class="fa-solid fa-check me-1"></i> Firma adicional activa (quitar)';
        } else {
            btnEl.classList.remove('btn-primary');
            btnEl.classList.add('btn-outline-primary');
            btnEl.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Agregar espacio de firma';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function recalcularConsecutivoMuestra(prefix = prefijoMuestraActual) {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return siguienteConsecutivoMuestraInicial;
        let max = 0;
        const regex = new RegExp(`^${prefix}-(\\d+)-(\\d{2}|\\d{4})$`, 'i');
        tbody.querySelectorAll('input[name="m_nombre[]"]').forEach(input => {
            const val = (input.value || '').trim();
            const m = val.match(regex);
            if (m) {
                const num = parseInt(m[1], 10);
                if (num > max) max = num;
            }
        });
        return Math.max(max + 1, siguienteConsecutivoMuestraInicial);
    }

    function mostrarEstadoVacioMuestras() {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;
        tbody.innerHTML = `
            <tr id="fila-sin-muestras">
                <td colspan="5" style="text-align:center; padding:36px 16px; color:#64748b; background:#f8fafc; border-radius:8px;">
                    <div style="font-size:32px; color:#94a3b8; margin-bottom:8px;"><i class="fa-solid fa-vial-circle-check"></i></div>
                    <div style="font-size:14px; font-weight:700; color:#1e293b;">No hay especímenes registrados en esta hoja</div>
                    <div style="font-size:12px; color:#64748b; margin-top:4px;">Haga clic en el botón azul <strong>"+ Agregar Muestra"</strong> para ingresar los especímenes recibidos o recolectados en campo.</div>
                </td>
            </tr>
        `;
        actualizarContadorEspecimenes();
    }

    function agregarFilaMuestraModal(tipo = null, nom = '', desc = '', info = '', ensayoId = null, plantillaId = null, seleccionManual = 0, motivoCambio = '', bloqueado = 0, codigoPlantillaPrevia = '', nombrePlantillaPrevia = '', idDetalleCot = 0) {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;

        // Quitar fila de estado vacío si existe
        const filaVacia = document.getElementById('fila-sin-muestras');
        if (filaVacia) filaVacia.remove();

        const esCompactacion = !!window.esCompactacionHojaActual;
        const prefijo = tipo || prefijoMuestraActual || 'MC';

        if (nom === '') {
            const nextNum = recalcularConsecutivoMuestra(prefijo);
            const consecutiveStr = String(nextNum).padStart(4, '0');
            nom = `${prefijo}-${consecutiveStr}-${anioActual2Digitos}`;
            siguienteConsecutivoMuestra = nextNum + 1;
            if (desc === '') {
                desc = (prefijo === 'MS') ? 'Muestra entregada en laboratorio' : 'Muestra tomada en campo';
            }
            if (info === '') {
                info = (prefijo === 'MS') ? 'Recepción Lab Central' : (esCompactacion ? 'Ensayo in situ' : 'Muestreo en Obra');
            }
        }

        // Si no se especificó plantillaId pero hay una seleccionada en el selector general, usarla
        if (!plantillaId) {
            const selGlobal = document.getElementById('select_plantilla_global');
            if (selGlobal && selGlobal.value) {
                plantillaId = selGlobal.value;
            }
        }

        // Obtener datos legibles de la plantilla asignada
        let etiquetaPlantilla = 'Sin plantilla asignada';
        let codigoPlantilla = codigoPlantillaPrevia || '';
        if (codigoPlantillaPrevia || nombrePlantillaPrevia) {
            etiquetaPlantilla = codigoPlantillaPrevia ? `${codigoPlantillaPrevia} · ${nombrePlantillaPrevia}` : nombrePlantillaPrevia;
        }
        if (plantillaId && Array.isArray(window.plantillasCache) && window.plantillasCache.length > 0) {
            const plt = window.plantillasCache.find(p => String(p.id) === String(plantillaId));
            if (plt) {
                codigoPlantilla = plt.codigo || '';
                etiquetaPlantilla = plt.codigo ? `${plt.codigo} · ${plt.nombre}` : plt.nombre;
            }
        }

        const ensayoFinal = ensayoId || window.productoHojaIdActual || 1;
        const productosOS = Array.isArray(window.productosPermitidosActuales) ? window.productosPermitidosActuales : [];
        const idDetSel = parseInt(idDetalleCot || 0, 10) || 0;
        let optsProdHtml = '<option value="">-- Definir después --</option>';
        productosOS.forEach(p => {
            const pId = parseInt(p.id_detalle_cotizacion || 0, 10);
            const pLbl = (p.codigo_servicio ? '[' + p.codigo_servicio + '] ' : '') + (p.descripcion_ensayo || 'Ensayo');
            const selAttr = (idDetSel > 0 && pId === idDetSel) ? ' selected' : '';
            optsProdHtml += `<option value="${pId}" data-prod-id="${parseInt(p.id_producto || 0, 10)}"${selAttr}>${escapeHtml(pLbl)}</option>`;
        });

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="vertical-align:middle;">
                <div style="display:flex; align-items:center; gap:6px;">
                    <span class="badge ${prefijo === 'MS' ? 'bg-secondary' : 'bg-primary'}" style="font-size:10.5px; font-weight:700; padding:4px 6px; border-radius:4px;">${escapeHtml(prefijo)}</span>
                    <input type="text" name="m_nombre[]" value="${nom}" readonly required class="rtfm13-input rtfm13-input-mono" style="padding:6px 10px; font-size:12px; height:36px; color:var(--rtfm13-azul); background:#f8fafc; cursor:not-allowed;" title="Código consecutivo oficial asignado por el sistema">
                </div>
                <input type="hidden" name="m_ensayo_id[]" value="${ensayoFinal}" class="input-ensayo-muestra">
                <input type="hidden" name="m_plantilla_id[]" value="${plantillaId || ''}" class="input-plantilla-muestra">
                <input type="hidden" name="m_seleccion_manual[]" value="${seleccionManual}">
                <input type="hidden" name="m_motivo_cambio[]" value="${escapeHtml(motivoCambio)}">
                <input type="hidden" name="m_bloqueado[]" value="${bloqueado}">
            </td>
            <td style="vertical-align:middle;">
                <input type="text" name="m_desc[]" value="${escapeHtml(desc)}" class="rtfm13-input" style="height:36px; font-size:12.5px;" required placeholder="Ubicación o elemento (ej: Columna Eje C-3)">
                ${motivoCambio ? `<div style="font-size:10px; color:#b45309; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${escapeHtml(motivoCambio)}"><i class="fa-solid fa-triangle-exclamation"></i> ${escapeHtml(motivoCambio)}</div>` : ''}
            </td>
            <td style="vertical-align:middle;">
                <input type="text" name="m_info[]" value="${escapeHtml(info)}" class="rtfm13-input" style="height:36px; font-size:12.5px;" placeholder="Edad / Estado (ej: 7 días, húmeda)">
            </td>
            <td style="vertical-align:middle;">
                <select name="m_detalle_cotizacion[]" class="rtfm13-input select-destino-muestra" style="height:36px; font-size:12px; padding:4px 8px;" ${bloqueado ? 'disabled' : ''} onchange="(function(sel){ const opt = sel.options[sel.selectedIndex]; const prodId = opt ? parseInt(opt.getAttribute('data-prod-id') || '0', 10) : 0; const inpEns = sel.closest('tr')?.querySelector('.input-ensayo-muestra'); if (inpEns && prodId > 0) inpEns.value = String(prodId); })(this)">
                    ${optsProdHtml}
                </select>
            </td>
            <td style="text-align:center; vertical-align:middle;">
                ${bloqueado ? 
                    '<span class="badge bg-light text-muted border" style="font-size:10px; font-weight:700; padding:4px 8px;"><i class="fa-solid fa-lock me-1"></i>Bloqueado</span>' 
                    : 
                    '<button type="button" class="btn btn-sm btn-outline-danger" style="padding:4px 8px; font-size:12px; border-radius:6px;" onclick="eliminarFilaMuestraModal(this)" title="Eliminar muestra"><i class="fa-solid fa-trash"></i></button>'
                }
            </td>
        `;
        tbody.appendChild(tr);
        actualizarContadorEspecimenes();
    }

    function eliminarFilaMuestraModal(btn) {
        const tr = btn.closest('tr');
        if (tr) tr.remove();
        siguienteConsecutivoMuestra = recalcularConsecutivoMuestra();
        actualizarContadorEspecimenes();
        const tbody = document.getElementById('hs-tbody-muestras');
        if (tbody && tbody.querySelectorAll('tr').length === 0) {
            mostrarEstadoVacioMuestras();
        }
    }

    // Funciones del Desplegable Interactivo AJAX de Plantillas
    window.plantillasCache = [];
    window.plantillaFiltroCategoria = 'todos';

    function escapeJs(str) {
        if (!str) return '';
        return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function poblarSelectorPlantillaGlobal(ensayoOProductoId, clienteId, plantillaSugeridaPrevia = null) {
        const hiddenInput = document.getElementById('select_plantilla_global');
        const listaCont = document.getElementById('lista_resultados_plantillas_ajax');
        if (!hiddenInput) return;

        if (listaCont) {
            listaCont.innerHTML = '<div style="padding:15px; text-align:center; color:#64748b; font-size:12px;"><i class="fa-solid fa-spinner fa-spin me-2 text-primary"></i>Cargando plantillas aprobadas...</div>';
        }

        const cid = clienteId || window.clienteIdActualHoja || '';
        let ensayoIdParam = 0;
        if (typeof ensayoOProductoId === 'number' || typeof ensayoOProductoId === 'string') {
            ensayoIdParam = parseInt(ensayoOProductoId, 10) || 0;
        } else if (Array.isArray(ensayoOProductoId) && ensayoOProductoId.length > 0) {
            ensayoIdParam = parseInt(ensayoOProductoId[0].id, 10) || 0;
        }
        if (ensayoIdParam <= 0) {
            ensayoIdParam = window.productoHojaIdActual || 0;
        }

        fetch(`/Cycsa/publico/plantillas/disponibles?ensayo_id=${ensayoIdParam}&cliente_id=${cid}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && Array.isArray(res.plantillas) && res.plantillas.length > 0) {
                    window.plantillasCache = res.plantillas;
                    
                    // Actualizar contadores
                    const total = res.plantillas.length;
                    const clienteCount = res.plantillas.filter(p => p.es_variante_cliente == 1).length;
                    const servicioCount = res.plantillas.filter(p => p.es_variante_cliente != 1 && p.es_del_ensayo == 1).length;
                    
                    const elTotal = document.getElementById('cnt_plt_todas');
                    if (elTotal) elTotal.innerText = total;
                    const elCli = document.getElementById('cnt_plt_cliente');
                    if (elCli) elCli.innerText = clienteCount;
                    const elSrv = document.getElementById('cnt_plt_servicio');
                    if (elSrv) elSrv.innerText = servicioCount;

                    // Renderizar lista en el panel desplegable
                    renderizarListaDropdownPlantillas(window.plantillasCache);

                    // Seleccionar plantilla sugerida si no hay una elegida
                    const sugerida = plantillaSugeridaPrevia || res.plantilla_sugerida;
                    if (!hiddenInput.value && sugerida) {
                        seleccionarPlantillaGlobal(sugerida.id, sugerida.codigo, sugerida.nombre, sugerida.norma, false);
                    }
                } else {
                    window.plantillasCache = [];
                    if (listaCont) {
                        listaCont.innerHTML = '<div style="padding:15px; text-align:center; color:#94a3b8; font-size:12px;"><i class="fa-solid fa-circle-exclamation me-2"></i>No hay plantillas aprobadas en el sistema.</div>';
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (listaCont) {
                    listaCont.innerHTML = '<div style="padding:15px; text-align:center; color:#ef4444; font-size:12px;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Error al cargar plantillas del servidor.</div>';
                }
            });
    }

    function renderizarListaDropdownPlantillas(plantillas) {
        const listaCont = document.getElementById('lista_resultados_plantillas_ajax');
        if (!listaCont) return;

        // Filtrar según pestaña de categoría
        let filtradas = plantillas;
        if (window.plantillaFiltroCategoria === 'cliente') {
            filtradas = plantillas.filter(p => p.es_variante_cliente == 1);
        } else if (window.plantillaFiltroCategoria === 'servicio') {
            filtradas = plantillas.filter(p => p.es_variante_cliente != 1 && p.es_del_ensayo == 1);
        }

        if (filtradas.length === 0) {
            listaCont.innerHTML = '<div style="padding:15px; text-align:center; color:#94a3b8; font-size:12px;"><i class="fa-solid fa-filter me-2"></i>No se encontraron plantillas para esta categoría o búsqueda.</div>';
            return;
        }

        const hiddenVal = document.getElementById('select_plantilla_global')?.value || '';

        let html = '';
        filtradas.forEach(p => {
            const isSelected = String(p.id) === String(hiddenVal);
            const esCliente = p.es_variante_cliente == 1;
            const esServicio = p.es_del_ensayo == 1;

            html += `
                <div class="item-plantilla-ajax" onclick="seleccionarPlantillaGlobal(${p.id}, '${escapeJs(p.codigo)}', '${escapeJs(p.nombre)}', '${escapeJs(p.norma || '')}', true)" 
                     style="padding:8px 10px; border-radius:6px; cursor:pointer; margin-bottom:4px; display:flex; align-items:center; justify-content:space-between; background:${isSelected ? '#f0fdf4' : '#ffffff'}; border:1px solid ${isSelected ? '#86efac' : '#f1f5f9'}; transition:all 0.15s;" 
                     onmouseover="if (!this.classList.contains('selected')) { this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'; }" 
                     onmouseout="if (!this.classList.contains('selected')) { this.style.background='${isSelected ? '#f0fdf4' : '#ffffff'}'; this.style.borderColor='${isSelected ? '#86efac' : '#f1f5f9'}'; }">
                    <div style="display:flex; align-items:center; gap:9px; flex:1; min-width:0;">
                        <div style="width:32px; height:32px; border-radius:6px; background:${esCliente ? '#fef3c7' : (esServicio ? '#dbeafe' : '#f1f5f9')}; color:${esCliente ? '#d97706' : (esServicio ? '#1d4ed8' : '#475569')}; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0;">
                            <i class="fa-solid ${esCliente ? 'fa-star' : (esServicio ? 'fa-bullseye' : 'fa-file-lines')}"></i>
                        </div>
                        <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span class="badge" style="background:#0284c7; color:white; font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px;">${escapeHtml(p.codigo)}</span>
                                <span style="font-size:12.5px; font-weight:600; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(p.nombre)}</span>
                            </div>
                            <div style="font-size:11px; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:6px;">
                                ${p.norma ? `<span><i class="fa-solid fa-bookmark me-1 text-secondary" style="font-size:10px;"></i>${escapeHtml(p.norma)}</span>` : ''}
                                ${esCliente ? '<span class="text-warning font-weight-bold" style="color:#d97706;"><i class="fa-solid fa-star me-1"></i>Variante Cliente</span>' : ''}
                                ${esServicio && !esCliente ? '<span class="text-primary" style="color:#0284c7;"><i class="fa-solid fa-check-circle me-1"></i>Recomendada</span>' : ''}
                            </div>
                        </div>
                    </div>
                    <div style="margin-left:8px; flex-shrink:0;">
                        ${isSelected ? '<span class="badge bg-success" style="background:#16a34a; color:white; font-size:11px; padding:3px 8px; border-radius:4px;"><i class="fa-solid fa-check me-1"></i>Activa</span>' : '<span style="font-size:11px; color:#94a3b8;"><i class="fa-solid fa-arrow-right"></i></span>'}
                    </div>
                </div>
            `;
        });
        listaCont.innerHTML = html;
    }

    function filtrarPlantillasAjax(query) {
        const q = (query || '').toLowerCase().trim();
        if (!q) {
            renderizarListaDropdownPlantillas(window.plantillasCache);
            return;
        }

        const filtradas = window.plantillasCache.filter(p => {
            const cod = (p.codigo || '').toLowerCase();
            const nom = (p.nombre || '').toLowerCase();
            const nrm = (p.norma || '').toLowerCase();
            return cod.includes(q) || nom.includes(q) || nrm.includes(q);
        });
        renderizarListaDropdownPlantillas(filtradas);
    }

    function filtrarCategoriaPlantilla(categoria, btn) {
        window.plantillaFiltroCategoria = categoria;
        document.querySelectorAll('.btn-filtro-plt').forEach(b => {
            b.style.background = '#f8fafc';
            b.style.color = '#475569';
            b.style.borderColor = '#cbd5e1';
        });
        if (btn) {
            btn.style.background = '#0284c7';
            btn.style.color = '#ffffff';
            btn.style.borderColor = '#0284c7';
        }
        const query = document.getElementById('input_buscar_plantilla_ajax')?.value || '';
        filtrarPlantillasAjax(query);
    }

    function seleccionarPlantillaGlobal(id, codigo, nombre, norma, sincronizar = false) {
        const hiddenInput = document.getElementById('select_plantilla_global');
        const triggerTexto = document.getElementById('texto_plantilla_seleccionada');
        if (hiddenInput) hiddenInput.value = id;

        if (triggerTexto) {
            triggerTexto.innerHTML = `
                <span class="badge" style="background:#0284c7; color:white; font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; margin-right:4px;">${escapeHtml(codigo)}</span>
                <span style="font-size:12px; font-weight:700; color:#0f172a;">${escapeHtml(nombre)}</span>
                ${norma ? `<span style="font-size:11px; color:#64748b; margin-left:4px;">(${escapeHtml(norma)})</span>` : ''}
            `;
        }

        cerrarDropdownPlantillasGlobal();

        // Si se pide sincronizar, auto-asignar a filas existentes que no tengan plantilla
        if (sincronizar) {
            const tbodyAuto = document.getElementById('hs-tbody-muestras');
            if (tbodyAuto) {
                tbodyAuto.querySelectorAll('tr:not(#fila-sin-muestras)').forEach(tr => {
                    const inpPlt = tr.querySelector('input[name="m_plantilla_id[]"]');
                    if (inpPlt && !inpPlt.value) {
                        inpPlt.value = id;
                        const badgePlt = tr.querySelector('.badge-plt-display');
                        if (badgePlt) {
                            badgePlt.innerHTML = `<i class="fa-solid fa-file-waveform me-1"></i>${escapeHtml(codigo || nombre)}`;
                            badgePlt.title = `${codigo} · ${nombre}`;
                            badgePlt.style.background = '#e0f2fe';
                            badgePlt.style.color = '#0369a1';
                            badgePlt.style.borderColor = '#bae6fd';
                        }
                    }
                });
            }
        }
    }

    function toggleDropdownPlantillasGlobal() {
        const panel = document.getElementById('dropdown_plantillas_panel');
        if (!panel) return;
        if (panel.style.display === 'none' || !panel.style.display) {
            panel.style.display = 'block';
            const input = document.getElementById('input_buscar_plantilla_ajax');
            if (input) {
                input.value = '';
                renderizarListaDropdownPlantillas(window.plantillasCache);
                setTimeout(() => input.focus(), 50);
            }
        } else {
            panel.style.display = 'none';
        }
    }

    function cerrarDropdownPlantillasGlobal() {
        const panel = document.getElementById('dropdown_plantillas_panel');
        if (panel) panel.style.display = 'none';
    }

    // Cerrar desplegable interactivo al hacer clic fuera de él
    document.addEventListener('click', function(event) {
        const panel = document.getElementById('dropdown_plantillas_panel');
        const trigger = document.getElementById('trigger_plantilla_global');
        if (panel && panel.style.display === 'block') {
            if (!panel.contains(event.target) && !trigger.contains(event.target)) {
                cerrarDropdownPlantillasGlobal();
            }
        }
    });

    function aplicarPlantillaATodasLasMuestras() {
        const selGlobal = document.getElementById('select_plantilla_global');
        if (!selGlobal || !selGlobal.value) {
            alert('Por favor seleccione primero una plantilla del selector interactivo.');
            return;
        }

        const plantillaId = selGlobal.value;
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;

        const filas = tbody.querySelectorAll('tr:not(#fila-sin-muestras)');
        if (filas.length === 0) {
            alert('No hay muestras en la tabla para asignar la plantilla. Haga clic en "+ Agregar Muestra" primero.');
            return;
        }

        let codigoPlt = '';
        let textoPlt = 'Plantilla asignada';
        if (Array.isArray(window.plantillasCache)) {
            const plt = window.plantillasCache.find(p => String(p.id) === String(plantillaId));
            if (plt) {
                codigoPlt = plt.codigo || '';
                textoPlt = plt.codigo ? `${plt.codigo} · ${plt.nombre}` : plt.nombre;
            }
        }

        let aplicadas = 0;
        let bloqueadas = 0;

        filas.forEach(tr => {
            const inpBloqueado = tr.querySelector('input[name="m_bloqueado[]"]');
            if (inpBloqueado && inpBloqueado.value === '1') {
                bloqueadas++;
                return;
            }

            const inpPlt = tr.querySelector('input[name="m_plantilla_id[]"]');
            if (inpPlt) {
                inpPlt.value = plantillaId;
                aplicadas++;
            }

            const badgePlt = tr.querySelector('.badge-plt-display');
            if (badgePlt) {
                badgePlt.innerHTML = `<i class="fa-solid fa-file-waveform me-1"></i>${escapeHtml(codigoPlt || textoPlt)}`;
                badgePlt.title = textoPlt;
                badgePlt.style.background = '#e0f2fe';
                badgePlt.style.color = '#0369a1';
                badgePlt.style.borderColor = '#bae6fd';
            }

            // Efecto visual de confirmación instantánea (destello verde)
            tr.style.transition = 'background-color 0.4s ease';
            tr.style.backgroundColor = '#dcfce7';
            setTimeout(() => {
                tr.style.backgroundColor = '';
            }, 1200);
        });

        if (bloqueadas > 0) {
            alert(`Se aplicó la plantilla general a ${aplicadas} muestra(s). Nota: ${bloqueadas} muestra(s) ya estaban congeladas por recepción de laboratorio y no se modificaron.`);
        }
    }

    function abrirPreviewPlantillaGlobal() {
        const selGlobal = document.getElementById('select_plantilla_global');
        if (!selGlobal || !selGlobal.value) {
            alert('Seleccione una plantilla del selector general para ver su vista previa.');
            return;
        }
        abrirModalPreviewPlantilla(selGlobal.value);
    }

    function replicarPlantillaFilaATodas(btn) {
        const trOrigen = btn.closest('tr');
        if (!trOrigen) return;
        const selOrigen = trOrigen.querySelector('select[name="m_plantilla_id[]"]');
        if (!selOrigen || !selOrigen.value) {
            alert('Esta fila no tiene una plantilla seleccionada para replicar.');
            return;
        }

        const plantillaId = selOrigen.value;
        const plantillaText = selOrigen.options[selOrigen.selectedIndex]?.text || '';

        // Sincronizar el selector general
        const selGlobal = document.getElementById('select_plantilla_global');
        if (selGlobal) {
            let optExists = false;
            for (let i = 0; i < selGlobal.options.length; i++) {
                if (selGlobal.options[i].value == plantillaId) {
                    selGlobal.selectedIndex = i;
                    optExists = true;
                    break;
                }
            }
            if (!optExists && plantillaText) {
                const newOpt = document.createElement('option');
                newOpt.value = plantillaId;
                newOpt.textContent = plantillaText;
                newOpt.selected = true;
                selGlobal.appendChild(newOpt);
            }
        }

        aplicarPlantillaATodasLasMuestras();
    }

    function actualizarPlantillasDeFila(selectEnsayo) {
        const tr = selectEnsayo.closest('tr');
        const selectPlantilla = tr.querySelector('select[name="m_plantilla_id[]"]');
        if (selectPlantilla) {
            cargarOpcionesPlantillaSelect(selectPlantilla, selectEnsayo.value, null);
        }
    }

    let selectCambiandoActual = null;
    let valorPrevioPlantilla = null;

    function guardarPlantillaPrevia(sel) {
        valorPrevioPlantilla = sel.value;
    }

    function verificarCambioPlantilla(sel) {
        const sugeridaId = sel.dataset.sugeridaId;
        const tr = sel.closest('tr');
        const inpManual = tr.querySelector('input[name="m_seleccion_manual[]"]');

        if (sugeridaId && sel.value !== sugeridaId && inpManual.value !== '1') {
            selectCambiandoActual = sel;
            document.getElementById('txtMotivoCambioModal').value = '';
            document.getElementById('errMotivoCambio').style.display = 'none';
            document.getElementById('modalMotivoCambioPlantilla').style.display = 'block';
        }
    }

    function cancelarCambioPlantillaModal() {
        if (selectCambiandoActual && valorPrevioPlantilla !== null) {
            selectCambiandoActual.value = valorPrevioPlantilla;
        }
        document.getElementById('modalMotivoCambioPlantilla').style.display = 'none';
        selectCambiandoActual = null;
    }

    function confirmarCambioPlantillaModal() {
        const txt = document.getElementById('txtMotivoCambioModal').value.trim();
        if (txt.length < 10) {
            document.getElementById('errMotivoCambio').style.display = 'block';
            return;
        }

        if (selectCambiandoActual) {
            const tr = selectCambiandoActual.closest('tr');
            tr.querySelector('input[name="m_seleccion_manual[]"]').value = '1';
            tr.querySelector('input[name="m_motivo_cambio[]"]').value = txt;
        }
        document.getElementById('modalMotivoCambioPlantilla').style.display = 'none';
        selectCambiandoActual = null;
    }

    function abrirPreviewPlantillaFila(btn) {
        const tr = btn.closest('tr');
        const sel = tr.querySelector('select[name="m_plantilla_id[]"]');
        const pid = sel ? sel.value : null;

        if (!pid) {
            alert('Seleccione primero una plantilla para ver su maqueta.');
            return;
        }

        const contenedor = document.getElementById('contenidoVerPlantillaReadOnly');
        contenedor.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i><div style="color:#64748b; margin-top:8px; font-size:12px;">Cargando formato oficial...</div></div>';
        document.getElementById('modalVerPlantillaReadOnly').style.display = 'block';

        fetch(`/Cycsa/publico/plantillas/preview-modal?id=${pid}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    contenedor.innerHTML = data.html;
                } else {
                    contenedor.innerHTML = `<div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px;">${data.message}</div>`;
                }
            })
            .catch(() => {
                contenedor.innerHTML = `<div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px;">Error al cargar la plantilla.</div>`;
            });
    }

    function cerrarVerPlantillaReadOnly() {
        document.getElementById('modalVerPlantillaReadOnly').style.display = 'none';
    }

    // Validación obligatoria en el envío del formulario RT-FM-13
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar dropdowns de acciones con estrategia fixed para evitar clipping por overflow
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            document.querySelectorAll('.dropdown-btn-mas').forEach(function(btn) {
                try {
                    new bootstrap.Dropdown(btn, {
                        boundary: 'viewport',
                        popperConfig: function(defaultBsPopperConfig) {
                            return Object.assign({}, defaultBsPopperConfig, { strategy: 'fixed' });
                        }
                    });
                } catch (e) {}
            });
        }
        const formHS = document.getElementById('form-hoja-solicitud');
        if (formHS) {
            formHS.addEventListener('submit', function(e) {
                const isRecepcion = document.getElementById('chk_confirmar_recepcion_lims')?.checked;
                if (isRecepcion) {
                    let faltaPlantilla = false;
                    let nombreFaltante = '';
                    document.querySelectorAll('#hs-tbody-muestras tr').forEach(tr => {
                        const selPlt = tr.querySelector('select[name="m_plantilla_id[]"]');
                        const inpNom = tr.querySelector('input[name="m_nombre[]"]');
                        if (selPlt && (!selPlt.value || selPlt.value === '0')) {
                            faltaPlantilla = true;
                            nombreFaltante = inpNom ? inpNom.value : 'Espécimen';
                        }
                    });
                    if (faltaPlantilla) {
                        e.preventDefault();
                        rehabilitarBotonesEnvioHoja();
                        alert(`Validación ISO 17025:\n\nLa muestra "${nombreFaltante}" no tiene asignada una plantilla de ensayo aprobada.\nNo se puede admitir la recepción física sin plantilla vinculada.`);
                        return false;
                    }
                }
            });
        }
    });

    
    const BLOQUEAR_REQ_POR_WHITELIST = false;

    function aplicarFiltroWhitelistReq(ensayos, esNueva) {
        const colConc = document.getElementById('hs_col_concreto');
        const colSuel = document.getElementById('hs_col_suelo');

        const mapaCheckboxes = {
            'req_resistencia_concreto': ['hs_req_concreto', 'hs_req_resistencia_concreto'],
            'req_resistencia_adoquin': ['hs_req_adoquin', 'hs_req_resistencia_adoquin'],
            'req_resistencia_bloques': ['hs_req_bloques', 'hs_req_resistencia_bloques'],
            'req_granulometria': ['hs_req_granulometria'],
            'req_limites_atterberg': ['hs_req_limites', 'hs_req_limites_atterberg'],
            'req_humedad': ['hs_req_humedad'],
            'req_resistencia_corte': ['hs_req_resistencia_corte', 'hs_req_corte'],
            'req_clasificacion_sucs_hr': ['hs_req_clasificacion_sucs_hr', 'hs_req_sucs'],
            'req_proctor_sm': ['hs_req_proctor', 'hs_req_proctor_sm'],
            'req_infiltracion': ['hs_req_infiltracion'],
            'req_cbr': ['hs_req_cbr'],
            'req_densidad': ['hs_req_densidad'],
            'req_otros_materiales': ['hs_req_otros_materiales', 'hs_req_otros_mat']
        };

        // Si no hay whitelist o es legado (ensayos vacío/null), permitir todo
        if (!Array.isArray(ensayos) || ensayos.length === 0) {
            Object.values(mapaCheckboxes).flat().forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.disabled = false;
                    if (el.parentElement) el.parentElement.style.opacity = '1';
                }
            });
            if (colConc) { colConc.style.opacity = '1'; colConc.style.border = '1px solid #e2e8f0'; colConc.style.background = '#f8fafc'; }
            if (colSuel) { colSuel.style.opacity = '1'; colSuel.style.border = '1px solid #e2e8f0'; colSuel.style.background = '#f8fafc'; }
            return;
        }

        const textoTotal = ensayos.map(e => ((e.nombre || e.nombre_ensayo || '') + ' ' + (e.norma || '') + ' ' + (e.descripcion_ensayo || '') + ' ' + (e.norma_astm || '') + ' ' + (e.procedimiento || '')).toLowerCase()).join(' ');

        const flagsPermitidos = {
            'req_resistencia_concreto': /compresi|concreto|cilindro|c39|viga|c78|c42/i.test(textoTotal),
            'req_resistencia_adoquin': /adoqu|c936/i.test(textoTotal),
            'req_resistencia_bloques': /bloque|c90|mamposter/i.test(textoTotal),
            'req_granulometria': /granulo|c136|c117/i.test(textoTotal),
            'req_limites_atterberg': /atterberg|consistencia|l[ií]mite|d4318/i.test(textoTotal),
            'req_humedad': /humedad|d2216/i.test(textoTotal),
            'req_resistencia_corte': /corte|triaxial|veleta|d3080/i.test(textoTotal),
            'req_clasificacion_sucs_hr': /sucs|clasificaci|d2487|aashto/i.test(textoTotal),
            'req_proctor_sm': /proctor|compactaci|d698|d1557/i.test(textoTotal),
            'req_infiltracion': /infiltraci|porchet|permeabil/i.test(textoTotal),
            'req_cbr': /cbr|california|d1883/i.test(textoTotal),
            'req_densidad': /densidad|cono|dens[ií]metro|d1556|d6938/i.test(textoTotal),
            'req_otros_materiales': /acero|ferroscan|metal|asfalto|otro/i.test(textoTotal)
        };

        for (const [campo, ids] of Object.entries(mapaCheckboxes)) {
            const estaPermitido = !!flagsPermitidos[campo];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    // ADDENDUM P0 (Abdia 2026-11-02): Parámetros libres en RT-FM-13.
                    // Los checkboxes de req_* NO se deshabilitan ni se desmarcan automáticamente.
                    // El técnico puede marcar o desmarcar libremente cualquier parámetro.
                    el.disabled = false;
                    if (el.parentElement) {
                        el.parentElement.style.opacity = '1';
                        el.parentElement.title = estaPermitido 
                            ? 'Ensayo sugerido según Orden de Servicio' 
                            : 'Parámetro adicional disponible para selección libre';
                    }
                }
            });
        }

        const tieneConcreto = flagsPermitidos['req_resistencia_concreto'] || flagsPermitidos['req_resistencia_adoquin'] || flagsPermitidos['req_resistencia_bloques'];
        const tieneSuelo = flagsPermitidos['req_granulometria'] || flagsPermitidos['req_limites_atterberg'] || flagsPermitidos['req_humedad'] || flagsPermitidos['req_densidad'] || flagsPermitidos['req_proctor_sm'] || flagsPermitidos['req_cbr'] || flagsPermitidos['req_resistencia_corte'] || flagsPermitidos['req_infiltracion'] || flagsPermitidos['req_clasificacion_sucs_hr'];

        if (colConc) {
            colConc.style.opacity = tieneConcreto ? '1' : '0.5';
            colConc.style.border = tieneConcreto ? '2px solid #0284c7' : '1px solid #e2e8f0';
            colConc.style.background = tieneConcreto ? '#f0f9ff' : '#f8fafc';
        }
        if (colSuel) {
            colSuel.style.opacity = tieneSuelo ? '1' : '0.5';
            colSuel.style.border = tieneSuelo ? '2px solid #0284c7' : '1px solid #e2e8f0';
            colSuel.style.background = tieneSuelo ? '#f0f9ff' : '#f8fafc';
        }
    }

    function cerrarModalHojaSolicitud() {
        rehabilitarBotonesEnvioHoja();
        modHS.style.display = 'none';
    }

    // =========================================================================
    // 3B. MODAL DATOS DE RECEPCIÓN POR RT-FM-13 (N HOJAS x N MUESTRAS)
    // =========================================================================
    const modRec = document.getElementById('modalDatosRecepcion');
    let recHojaActualId = 0;
    let recOsActualId = 0;
    let recCamposActuales = [];
    let recSoloLecturaActual = false;

    function abrirModalDatosRecepcion(idOS, codigoOS, idHoja = 0) {
        if (!modRec) return;
        recOsActualId = parseInt(idOS, 10) || 0;
        recHojaActualId = parseInt(idHoja, 10) || 0;
        document.getElementById('rec_os_codigo').textContent = codigoOS || ('O/S #' + idOS);
        document.getElementById('rec_feedback_msg').textContent = '';
        modRec.style.display = 'block';
        cargarDatosRecepcionHoja(recOsActualId, recHojaActualId);
    }

    function cerrarModalDatosRecepcion() {
        if (modRec) modRec.style.display = 'none';
    }

    function normalizarClaveJsRec(txt) {
        return String(txt || '')
            .replace(/²/g, '2')
            .replace(/³/g, '3')
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]/g, '');
    }

    function cargarDatosRecepcionHoja(idOS, idHoja) {
        const loading = document.getElementById('rec_loading');
        const contenido = document.getElementById('rec_contenido');
        loading.style.display = 'block';
        contenido.style.display = 'none';

        const qs = idHoja > 0 ? `id_hoja=${idHoja}` : `id_os=${idOS}`;
        fetch(`/Cycsa/publico/hojas-servicio/datos-recepcion?${qs}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.status !== 'success') {
                document.getElementById('rec_feedback_msg').innerHTML = `<span style="color:#dc2626;">${escapeHtml(data.message || 'No se pudieron cargar los datos.')}</span>`;
                return;
            }
            contenido.style.display = 'block';
            renderizarModalDatosRecepcion(data);
        })
        .catch(() => {
            loading.style.display = 'none';
            document.getElementById('rec_feedback_msg').innerHTML = '<span style="color:#dc2626;">Error de red al cargar los datos de recepción.</span>';
        });
    }

    function renderizarModalDatosRecepcion(data) {
        const osInfo = data.os || {};
        const hoja = data.hoja || {};
        const todas = Array.isArray(data.todas_las_hojas) ? data.todas_las_hojas : [];
        recHojaActualId = parseInt(hoja.id, 10) || 0;
        recOsActualId = parseInt(osInfo.id, 10) || recOsActualId;
        recCamposActuales = Array.isArray(data.campos_recepcion) ? data.campos_recepcion : [];
        recSoloLecturaActual = !!osInfo.solo_lectura;

        document.getElementById('rec_os_codigo').textContent = osInfo.codigo_os || '--';
        document.getElementById('rec_hoja_reg').textContent = 'Reg: ' + (hoja.numero_registro || ('#' + recHojaActualId));
        document.getElementById('rec_ensayo_nombre').textContent = hoja.descripcion_ensayo || hoja.formato_nombre || '';

        const recCompleta = parseInt(hoja.recepcion_completa || 0, 10) === 1;
        const badgeEtapa = document.getElementById('rec_badge_etapa');
        if (badgeEtapa) {
            if (recCompleta) {
                badgeEtapa.textContent = 'Recepción completa';
                badgeEtapa.style.background = '#dbeafe';
                badgeEtapa.style.color = '#1e40af';
                badgeEtapa.style.borderColor = '#93c5fd';
            } else {
                badgeEtapa.textContent = 'Recepción en borrador';
                badgeEtapa.style.background = '#f1f5f9';
                badgeEtapa.style.color = '#475569';
                badgeEtapa.style.borderColor = '#cbd5e1';
            }
        }
        const btnReabrir = document.getElementById('rec_btn_reabrir');
        const btnCompletar = document.getElementById('rec_btn_completar');
        const esSupRec = <?= in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3], true) || tienePermiso('recepcion', 'aprobar') ? 'true' : 'false' ?>;
        if (btnReabrir) {
            btnReabrir.style.display = (recCompleta && esSupRec && !recSoloLecturaActual) ? 'inline-flex' : 'none';
        }
        if (btnCompletar) {
            btnCompletar.style.display = (!recSoloLecturaActual) ? 'inline-flex' : 'none';
        }

        // Tabs para cambiar entre todas las RT-FM-13 de la O/S
        const tabsWrap = document.getElementById('rec_tabs_hojas_wrap');
        const tabsList = document.getElementById('rec_tabs_hojas_list');
        if (todas.length > 0) {
            tabsWrap.style.display = 'block';
            tabsList.innerHTML = todas.map(h => {
                const activa = parseInt(h.id, 10) === recHojaActualId;
                const tot = parseInt(h.total_muestras, 10) || 0;
                const comp = parseInt(h.muestras_con_datos, 10) || 0;
                const completo = parseInt(h.recepcion_completa || 0, 10) === 1 || (tot > 0 && comp >= tot);
                const bg = activa ? '#103487' : '#fff';
                const fg = activa ? '#fff' : '#0f172a';
                const br = activa ? '#103487' : '#cbd5e1';
                const badgeCount = h.requiere_datos_recepcion
                    ? `<span style="margin-left:6px; padding:1px 6px; border-radius:999px; font-size:11px; background:${activa ? 'rgba(255,255,255,0.22)' : (completo ? '#dcfce7' : '#f1f5f9')}; color:${activa ? '#fff' : (completo ? '#166534' : '#475569')};">${comp}/${tot}</span>`
                    : '';
                return `<button type="button" onclick="cargarDatosRecepcionHoja(${recOsActualId}, ${h.id})" style="border:1px solid ${br}; background:${bg}; color:${fg}; padding:6px 12px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;">
                    Reg: ${escapeHtml(h.numero_registro)} · ${escapeHtml((h.descripcion_ensayo || '').substring(0, 34))}${badgeCount}
                </button>`;
            }).join('');
        } else {
            tabsWrap.style.display = 'none';
        }

        const cRecDiv = document.getElementById('rec_lista_campos_recepcion');
        const cTecDiv = document.getElementById('rec_lista_campos_tecnico');
        cRecDiv.innerHTML = recCamposActuales.length > 0
            ? recCamposActuales.map(c => `<span style="display:inline-block; background:#eff6ff; border:1px solid #bfdbfe; color:#103487; padding:2px 8px; border-radius:6px; margin:2px 4px 2px 0; font-size:12px; font-weight:700;">${escapeHtml(c.nombre)}</span>`).join('')
            : '<em>Sin casillas adicionales de recepción para este formato.</em>';
        const camposTec = Array.isArray(data.campos_tecnico) ? data.campos_tecnico : [];
        cTecDiv.innerHTML = camposTec.length > 0
            ? camposTec.map(c => `<span style="display:inline-block; background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:2px 8px; border-radius:6px; margin:2px 4px 2px 0; font-size:12px; font-weight:700;">${escapeHtml(c)}</span>`).join('')
            : '<em>Ninguno adicional.</em>';

        const sinNotice = document.getElementById('rec_sin_campos_notice');
        const tablaSec = document.getElementById('rec_tabla_section');
        const btnGuardar = document.getElementById('rec_btn_guardar');
        const productosOSRec = Array.isArray(data.productos_os) ? data.productos_os : [];
        window.recProductosOsActuales = productosOSRec;
        const mapaCamposPorDet = {};
        productosOSRec.forEach(p => {
            const setC = new Set();
            (p.campos_recepcion || []).forEach(c => setC.add(normalizarClaveJsRec(c.nombre)));
            mapaCamposPorDet[parseInt(p.id_detalle_cotizacion, 10)] = setC;
        });

        if (recCamposActuales.length === 0 && productosOSRec.length === 0) {
            sinNotice.style.display = 'block';
            tablaSec.style.display = 'none';
            btnGuardar.style.display = 'none';
            return;
        }
        sinNotice.style.display = 'none';
        tablaSec.style.display = 'block';
        btnGuardar.style.display = recSoloLecturaActual ? 'none' : 'inline-flex';

        const muestras = Array.isArray(data.muestras) ? data.muestras : [];
        document.getElementById('rec_total_muestras_lbl').textContent = muestras.length;

        const thead = document.getElementById('rec_thead_muestras');
        const tbody = document.getElementById('rec_tbody_muestras');
        thead.innerHTML = `<tr>
            <th style="padding:9px 10px; border-bottom:1.5px solid #fdba74; text-align:center; width:42px;">#</th>
            <th style="padding:9px 10px; border-bottom:1.5px solid #fdba74; min-width:125px;">Código Muestra</th>
            <th style="padding:9px 10px; border-bottom:1.5px solid #fdba74; min-width:210px;">Producto / Ensayo Destino</th>
            <th style="padding:9px 10px; border-bottom:1.5px solid #fdba74; min-width:130px;">Identificación / Ubicación</th>
            ${recCamposActuales.map(c => `<th style="padding:9px 10px; border-bottom:1.5px solid #fdba74; min-width:125px;">${escapeHtml(c.nombre)}</th>`).join('')}
        </tr>`;

        tbody.innerHTML = '';
        if (muestras.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${4 + recCamposActuales.length}" style="padding:18px; text-align:center; color:#64748b;">Esta hoja RT-FM-13 no tiene muestras registradas todavía.</td></tr>`;
            return;
        }

        const actualizarVisibilidadCamposFilaRec = (trRow) => {
            const selD = trRow.querySelector('.rec-destino-select');
            const idDetElegido = selD ? (parseInt(selD.value || '0', 10) || 0) : 0;
            const setPermitidos = idDetElegido > 0 ? mapaCamposPorDet[idDetElegido] : null;
            trRow.querySelectorAll('.rec-campo-input').forEach(inp => {
                const clave = inp.dataset.clave || '';
                const aplica = !setPermitidos || setPermitidos.size === 0 || setPermitidos.has(clave);
                const esAuto = inp.dataset.autocalc === '1';
                if (!aplica) {
                    inp.disabled = true;
                    inp.style.opacity = '0.4';
                    inp.style.background = '#f1f5f9';
                    inp.placeholder = 'N/A';
                } else {
                    inp.disabled = recSoloLecturaActual;
                    inp.style.opacity = '1';
                    inp.style.background = (recSoloLecturaActual || esAuto) ? '#f1f5f9' : '#fff';
                    inp.placeholder = '';
                }
            });
        };

        muestras.forEach((m, idx) => {
            const tr = document.createElement('tr');
            tr.dataset.codigo = m.codigo_muestra || '';
            tr.style.background = (idx % 2 === 0) ? '#ffffff' : '#fffaf5';

            const idDetActualM = parseInt(m.id_detalle_cotizacion || 0, 10) || (productosOSRec.length === 1 ? parseInt(productosOSRec[0].id_detalle_cotizacion, 10) : 0);
            let optsDestRec = '<option value="0">-- Sin asignar (definir producto) --</option>';
            productosOSRec.forEach(p => {
                const pId = parseInt(p.id_detalle_cotizacion, 10);
                const pNom = (p.codigo_servicio ? '[' + p.codigo_servicio + '] ' : '') + (p.descripcion_ensayo || 'Ensayo');
                const selAttr = (pId === idDetActualM) ? ' selected' : '';
                optsDestRec += `<option value="${pId}"${selAttr}>${escapeHtml(pNom)}</option>`;
            });

            let html = `
                <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; text-align:center; font-weight:700; color:#64748b;">${idx + 1}</td>
                <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; font-family:monospace; font-weight:700; color:#103487;">${escapeHtml(m.codigo_muestra || '')}</td>
                <td style="padding:6px 8px; border-bottom:1px solid #f1f5f9;">
                    <select class="rtfm13-input rec-destino-select" style="height:34px; font-size:12px; padding:4px 8px; background:${recSoloLecturaActual ? '#f1f5f9' : '#fff'};" ${recSoloLecturaActual ? 'disabled' : ''}>
                        ${optsDestRec}
                    </select>
                </td>
                <td style="padding:8px 10px; border-bottom:1px solid #f1f5f9; color:#334155; font-size:12px;">${escapeHtml(m.descripcion_ubicacion || m.observacion_edad || '—')}</td>
            `;

            const dRec = m.datos_recepcion || {};
            const tieneRevenIn = recCamposActuales.some(cc => ['revenin', 'revenimientoin'].includes(normalizarClaveJsRec(cc.nombre)));
            recCamposActuales.forEach(c => {
                const val = dRec[c.nombre] !== undefined ? dRec[c.nombre] : '';
                const stepAttr = c.step ? `step="${escapeHtml(c.step)}"` : '';
                const clave = normalizarClaveJsRec(c.nombre);
                const esAutoCalcRec = tieneRevenIn && ['revencm', 'revenimientocm'].includes(clave);
                const roAttr = recSoloLecturaActual
                    ? 'readonly disabled'
                    : (esAutoCalcRec ? 'readonly tabindex="-1" title="Calculado automáticamente desde Reven. (in)"' : '');
                const bgInput = (recSoloLecturaActual || esAutoCalcRec) ? '#f1f5f9' : '#fff';
                const cursorCss = esAutoCalcRec ? 'cursor:not-allowed; color:#334155; font-weight:600;' : '';
                html += `<td style="padding:6px 8px; border-bottom:1px solid #f1f5f9;">
                    <input type="${escapeHtml(c.tipo || 'text')}" ${stepAttr} ${roAttr}
                           class="rtfm13-input rec-campo-input"
                           style="height:34px; font-size:12.5px; padding:4px 8px; background:${bgInput}; ${cursorCss}"
                           data-campo="${escapeHtml(c.nombre)}"
                           data-clave="${escapeHtml(clave)}"
                           data-autocalc="${esAutoCalcRec ? '1' : '0'}"
                           value="${escapeHtml(val)}">
                </td>`;
            });
            tr.innerHTML = html;
            tbody.appendChild(tr);

            const selDestEl = tr.querySelector('.rec-destino-select');
            if (selDestEl) {
                selDestEl.addEventListener('change', () => actualizarVisibilidadCamposFilaRec(tr));
            }
            actualizarVisibilidadCamposFilaRec(tr);

            // Auto-cálculo Reven. in <-> Reven. cm y Fechas <-> Edad (Días)
            tr.querySelectorAll('.rec-campo-input').forEach(inp => {
                inp.addEventListener('input', () => recalcularFilaDatosRecepcion(tr, inp));
                inp.addEventListener('change', () => recalcularFilaDatosRecepcion(tr, inp));
            });
        });
    }

    function recalcularFilaDatosRecepcion(tr, origenInp) {
        const claveOri = origenInp.dataset.clave || '';
        const buscarPorClaves = (lista) => Array.from(tr.querySelectorAll('.rec-campo-input')).find(i => lista.includes(i.dataset.clave || ''));

        const inpIn = buscarPorClaves(['revenin', 'revenimientoin']);
        const inpCm = buscarPorClaves(['revencm', 'revenimientocm']);
        if (inpIn && inpCm) {
            if (origenInp === inpIn) {
                if (inpIn.value !== '' && !isNaN(parseFloat(inpIn.value))) {
                    const cmCalc = Math.round(parseFloat(inpIn.value) * 2.54 * 2) / 2;
                    inpCm.value = Number.isInteger(cmCalc) ? String(cmCalc) : cmCalc.toFixed(1);
                } else {
                    inpCm.value = '';
                }
            } else if (origenInp === inpCm && !inpCm.readOnly && inpCm.value !== '' && !isNaN(parseFloat(inpCm.value))) {
                inpIn.value = (parseFloat(inpCm.value) / 2.54).toFixed(2);
            }
        }

        const inpFab = buscarPorClaves(['fechadefabricacion', 'fechafabricacion', 'fechademoldeo', 'fechamoldeo', 'fechadecolado', 'fechacolado']);
        const inpEns = buscarPorClaves(['fechadeensayo', 'fechaensayo', 'fechaderuptura', 'fecharuptura', 'fechadefinalizacion', 'fechafinalizacion']);
        const inpEdad = buscarPorClaves(['edaddias', 'edad']);
        if (inpFab && inpEns && inpEdad) {
            if ((claveOri === inpFab.dataset.clave || claveOri === inpEns.dataset.clave) && inpFab.value && inpEns.value) {
                const d1 = new Date(inpFab.value + 'T00:00:00');
                const d2 = new Date(inpEns.value + 'T00:00:00');
                const dias = Math.round((d2 - d1) / 86400000);
                if (!isNaN(dias) && dias >= 0) {
                    inpEdad.value = dias;
                }
            } else if (claveOri === inpEdad.dataset.clave && inpFab.value && inpEdad.value !== '' && !isNaN(parseInt(inpEdad.value, 10))) {
                const d1 = new Date(inpFab.value + 'T00:00:00');
                d1.setDate(d1.getDate() + parseInt(inpEdad.value, 10));
                if (!isNaN(d1.getTime())) {
                    inpEns.value = d1.toISOString().substring(0, 10);
                }
            }
        }
    }

    function copiarFilaUnoDatosRecepcion() {
        const filas = document.querySelectorAll('#rec_tbody_muestras tr');
        if (filas.length < 2) return;
        const valoresFila1 = {};
        filas[0].querySelectorAll('.rec-campo-input').forEach(inp => {
            valoresFila1[inp.dataset.campo] = inp.value;
        });
        for (let i = 1; i < filas.length; i++) {
            filas[i].querySelectorAll('.rec-campo-input').forEach(inp => {
                if (valoresFila1[inp.dataset.campo] !== undefined && valoresFila1[inp.dataset.campo] !== '') {
                    inp.value = valoresFila1[inp.dataset.campo];
                }
            });
        }
        document.getElementById('rec_feedback_msg').innerHTML = '<span style="color:#0369a1;"><i class="fa-solid fa-check me-1"></i>Valores copiados de la fila 1 a todas las muestras (revise edades/fechas individuales si varían y guarde).</span>';
    }

    function guardarDatosRecepcionModal(marcarCompleta = false) {
        if (recHojaActualId <= 0 || recSoloLecturaActual) return;
        const payloadMuestras = {};
        const payloadDestinos = {};
        document.querySelectorAll('#rec_tbody_muestras tr').forEach(tr => {
            const cod = tr.dataset.codigo || '';
            if (!cod) return;
            payloadMuestras[cod] = {};
            tr.querySelectorAll('.rec-campo-input').forEach(inp => {
                if (!inp.disabled) {
                    payloadMuestras[cod][inp.dataset.campo] = inp.value.trim();
                }
            });
            const selDest = tr.querySelector('.rec-destino-select');
            if (selDest) {
                payloadDestinos[cod] = parseInt(selDest.value || '0', 10) || 0;
            }
        });

        const btn = marcarCompleta ? (document.getElementById('rec_btn_completar') || document.getElementById('rec_btn_guardar')) : document.getElementById('rec_btn_guardar');
        const textoOriginal = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando...';
        }

        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const formData = new FormData();
        formData.append('csrf_token', csrfInput ? csrfInput.value : '');
        formData.append('id_hoja', String(recHojaActualId));
        formData.append('muestras_recepcion_json', JSON.stringify(payloadMuestras));
        formData.append('muestras_destino_json', JSON.stringify(payloadDestinos));
        if (marcarCompleta) {
            formData.append('marcar_recepcion_completa', '1');
        }

        const endpoint = marcarCompleta
            ? '/Cycsa/publico/hojas-servicio/marcar-recepcion-completa'
            : '/Cycsa/publico/hojas-servicio/guardar-datos-recepcion';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
            }
            if (res.status === 'success') {
                document.getElementById('rec_feedback_msg').innerHTML = `<span style="color:#15803d;"><i class="fa-solid fa-circle-check me-1"></i>${escapeHtml(res.message || 'Guardado correctamente.')}</span>`;
                cargarDatosRecepcionHoja(recOsActualId, recHojaActualId);
            } else {
                document.getElementById('rec_feedback_msg').innerHTML = `<span style="color:#dc2626;"><i class="fa-solid fa-triangle-exclamation me-1"></i>${escapeHtml(res.message || 'Error al guardar.')}</span>`;
            }
        })
        .catch(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = textoOriginal;
            }
            document.getElementById('rec_feedback_msg').innerHTML = '<span style="color:#dc2626;">Error de red al guardar los datos de recepción.</span>';
        });
    }

    function reabrirRecepcionModal() {
        if (recHojaActualId <= 0) return;
        const motivo = window.prompt('Indique el motivo obligatorio de reapertura de recepción (mínimo 5 caracteres):', '');
        if (motivo === null) return;
        if (motivo.trim().length < 5) {
            document.getElementById('rec_feedback_msg').innerHTML = '<span style="color:#dc2626;">El motivo de reapertura debe tener al menos 5 caracteres.</span>';
            return;
        }
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const formData = new FormData();
        formData.append('csrf_token', csrfInput ? csrfInput.value : '');
        formData.append('id_hoja', String(recHojaActualId));
        formData.append('motivo_reapertura', motivo.trim());

        fetch('/Cycsa/publico/hojas-servicio/reabrir-recepcion', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                document.getElementById('rec_feedback_msg').innerHTML = `<span style="color:#15803d;"><i class="fa-solid fa-circle-check me-1"></i>${escapeHtml(res.message || 'Recepción reabierta.')}</span>`;
                cargarDatosRecepcionHoja(recOsActualId, recHojaActualId);
            } else {
                document.getElementById('rec_feedback_msg').innerHTML = `<span style="color:#dc2626;">${escapeHtml(res.message || 'No se pudo reabrir la recepción.')}</span>`;
            }
        })
        .catch(() => {
            document.getElementById('rec_feedback_msg').innerHTML = '<span style="color:#dc2626;">Error de red al reabrir la recepción.</span>';
        });
    }

    // =========================================================================
    // 4. CONTROL DEL MODAL DE REVISIÓN DEL SUPERVISOR
    // =========================================================================
    const modRev = document.getElementById('modalRevision');

    function abrirModalRevision(idOS, codigoOS) {
        document.getElementById('rev_id_os').value = idOS;
        document.getElementById('rev_codigo_os').innerText = codigoOS;
        document.getElementById('wrapper-observacion').style.display = 'none';
        document.getElementById('btn-observar-os').style.display = 'inline-flex';
        document.getElementById('btn-aprobar-os').style.display = 'inline-flex';
        modRev.style.display = 'block';
    }

    function cerrarModalRevision() {
        modRev.style.display = 'none';
    }

    function mostrarCampoObservacion() {
        const wrap = document.getElementById('wrapper-observacion');
        if (wrap.style.display === 'none') {
            wrap.style.display = 'block';
            document.getElementById('btn-aprobar-os').style.display = 'none';
            document.getElementById('rev_motivo').focus();
        } else {
            const motivo = document.getElementById('rev_motivo').value.trim();
            if (motivo === '') {
                alert('Debe ingresar el motivo de la observación.');
                document.getElementById('rev_motivo').focus();
                return;
            }
            document.getElementById('rev_nuevo_estado').value = 'Estado 2: Observada';
            document.getElementById('form-revision-os').submit();
        }
    }

    function ejecutarAprobacionOS() {
        document.getElementById('rev_nuevo_estado').value = 'Estado 3: Ingreso Directo';
        document.getElementById('form-revision-os').submit();
    }

    // =========================================================================
    // 4B. CONTROLADOR MODAL EN LÍNEA CYCSA-RT-FM-40 B ASOCIADO A RT-FM-13
    // =========================================================================
    let fm40bOsActual = 0;
    let fm40bHojaActual = 0;
    let fm40bCambioGuardado = false;

    function abrirModalFm40B(idOS, idHoja = 0) {
        fm40bOsActual = parseInt(idOS, 10) || 0;
        fm40bHojaActual = parseInt(idHoja, 10) || 0;
        fm40bCambioGuardado = false;
        if (!fm40bOsActual) return;

        const mod = document.getElementById('modalFm40B');
        if (mod) mod.style.display = 'flex';
        document.getElementById('fm40b_id_os').value = fm40bOsActual;
        document.getElementById('fm40b_feedback').innerHTML = '';

        cargarDatosFm40B(fm40bOsActual, fm40bHojaActual);
    }

    function cerrarModalFm40B() {
        const mod = document.getElementById('modalFm40B');
        if (mod) mod.style.display = 'none';
        if (fm40bCambioGuardado && fm40bOsActual > 0) {
            window.location.href = '/Cycsa/publico/ordenes-servicio?id_os=' + fm40bOsActual;
        }
    }

    function cambiarHojaModalFm40B(nuevoIdHoja) {
        fm40bHojaActual = parseInt(nuevoIdHoja || '0', 10) || 0;
        document.getElementById('fm40b_feedback').innerHTML = '';
        cargarDatosFm40B(fm40bOsActual, fm40bHojaActual);
    }

    function cargarDatosFm40B(idOS, idHoja = 0) {
        const loader = document.getElementById('fm40b_loader');
        const cont = document.getElementById('fm40b_contenido');
        if (loader) loader.style.display = 'block';
        if (cont) cont.style.display = 'none';

        fetch(`/Cycsa/publico/ordenes-servicio/fm40b-datos?id_os=${encodeURIComponent(idOS)}&id_hoja=${encodeURIComponent(idHoja)}`)
            .then(r => r.json())
            .then(res => {
                if (loader) loader.style.display = 'none';
                if (cont) cont.style.display = 'block';
                if (res.status !== 'success') {
                    document.getElementById('fm40b_feedback').innerHTML = `<span style="color:#dc2626;">${escapeHtml(res.message || 'No se pudieron cargar los datos del 40 B.')}</span>`;
                    return;
                }

                const os = res.os || {};
                const fm = res.fm40b || {};
                const hojas = Array.isArray(res.hojas) ? res.hojas : [];
                const tecnicos = Array.isArray(res.tecnicos) ? res.tecnicos : [];
                const vehiculos = Array.isArray(res.vehiculos) ? res.vehiculos : [];

                document.getElementById('fm40b_info_os').innerText = os.codigo_os || ('O/S #' + idOS);
                document.getElementById('fm40b_info_cliente').innerText = os.cliente_nombre || '-';
                document.getElementById('fm40b_info_proyecto').innerText = os.nombre_proyecto || '-';

                // Selector de hoja RT-FM-13
                const selHoja = document.getElementById('fm40b_id_hoja_solicitud');
                if (selHoja) {
                    selHoja.innerHTML = '<option value="">— Sin hoja RT-FM-13 específica (General O/S) —</option>';
                    hojas.forEach(h => {
                        const regLabel = h.numero_registro ? ('Reg. ' + h.numero_registro) : ('Hoja #' + h.id);
                        const ensayoLabel = h.ensayo_nombre ? (' · ' + h.ensayo_nombre) : '';
                        const est40b = h.fm40b_estado ? (` [40 B: ${h.fm40b_estado}]`) : ' [Sin 40 B]';
                        const opt = document.createElement('option');
                        opt.value = h.id;
                        opt.textContent = `${regLabel}${ensayoLabel}${est40b}`;
                        if (parseInt(h.id, 10) === parseInt(res.id_hoja_seleccionada || idHoja || 0, 10)) {
                            opt.selected = true;
                        }
                        selHoja.appendChild(opt);
                    });
                }

                // Técnicos y vehículos
                const selTec = document.getElementById('fm40b_id_tecnico');
                if (selTec) {
                    selTec.innerHTML = '<option value="">— Seleccionar técnico —</option>';
                    tecnicos.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.nombre_completo || t.nombre || ('Técnico #' + t.id);
                        if (parseInt(t.id, 10) === parseInt(fm.id_tecnico || 0, 10)) opt.selected = true;
                        selTec.appendChild(opt);
                    });
                }

                const selVeh = document.getElementById('fm40b_id_vehiculo');
                if (selVeh) {
                    selVeh.innerHTML = '<option value="">— Seleccionar vehículo —</option>';
                    vehiculos.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v.id;
                        const descVeh = [v.placa, v.marca, v.modelo, v.nombre].filter(Boolean).join(' · ');
                        opt.textContent = descVeh || ('Vehículo #' + v.id);
                        if (parseInt(v.id, 10) === parseInt(fm.id_vehiculo || 0, 10)) opt.selected = true;
                        selVeh.appendChild(opt);
                    });
                }

                document.getElementById('fm40b_fecha_ida').value = fm.fecha_ida || '';
                document.getElementById('fm40b_fecha_llegada').value = fm.fecha_llegada || '';
                document.getElementById('fm40b_lugar_muestreo').value = fm.lugar_muestreo || os.nombre_proyecto || '';
                document.getElementById('fm40b_num_muestreadores').value = fm.num_muestreadores || 1;
                document.getElementById('fm40b_cantidad_muestras_est').value = fm.cantidad_muestras_est || '';
                document.getElementById('fm40b_observaciones_campo').value = fm.observaciones_campo || '';

                // Limpiar y llenar checklist
                const chkData = fm.checklist || {};
                const form = document.getElementById('formFm40BModal');
                if (form) {
                    form.querySelectorAll('input[name^="chk["]').forEach(inp => {
                        const m = inp.name.match(/^chk\[(.+)\]$/);
                        const key = m ? m[1] : '';
                        if (inp.type === 'checkbox') {
                            inp.checked = !!(key && chkData[key]);
                        } else {
                            inp.value = (key && typeof chkData[key] !== 'undefined' && chkData[key] !== null) ? chkData[key] : '';
                        }
                    });
                }

                actualizarEstadoVisualFm40B(fm.id > 0 ? (fm.estado_muestreo || 'Programado') : 'Pendiente', fm.id > 0, os.id, res.id_hoja_seleccionada || idHoja, !!os.solo_lectura);
            })
            .catch(err => {
                if (loader) loader.style.display = 'none';
                if (cont) cont.style.display = 'block';
                console.error(err);
                document.getElementById('fm40b_feedback').innerHTML = '<span style="color:#dc2626;">Error de comunicación cargando datos 40 B.</span>';
            });
    }

    function actualizarEstadoVisualFm40B(estado, yaExiste, idOS, idHoja, soloLectura = false) {
        const badge = document.getElementById('fm40b_badge_estado');
        if (badge) {
            badge.innerText = estado || 'Pendiente';
            if (estado === 'Finalizado') {
                badge.style.background = '#10b981';
                badge.style.color = '#fff';
            } else if (estado === 'En Campo' || estado === 'Programado') {
                badge.style.background = '#f59e0b';
                badge.style.color = '#fff';
            } else {
                badge.style.background = 'rgba(255,255,255,0.22)';
                badge.style.color = '#fff';
            }
        }

        const sub = document.getElementById('fm40b_subtitulo');
        if (sub) {
            sub.innerText = idHoja > 0
                ? `Asociado a Hoja RT-FM-13 #${idHoja} en O/S`
                : 'Documento general de la Orden de Servicio';
        }

        const btnImp = document.getElementById('fm40b_btn_imprimir');
        if (btnImp) {
            if (yaExiste) {
                btnImp.href = `/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=${encodeURIComponent(idOS)}${idHoja > 0 ? '&id_hoja=' + encodeURIComponent(idHoja) : ''}`;
                btnImp.style.display = 'inline-flex';
            } else {
                btnImp.style.display = 'none';
            }
        }

        const btnGuardar = document.getElementById('fm40b_btn_guardar');
        const btnCerrar40b = document.getElementById('fm40b_btn_cerrar_40b');
        if (btnGuardar) btnGuardar.style.display = soloLectura ? 'none' : 'inline-flex';
        if (btnCerrar40b) btnCerrar40b.style.display = soloLectura ? 'none' : 'inline-flex';
    }

    function marcarTodoFm40bModal() {
        const form = document.getElementById('formFm40BModal');
        if (!form) return;
        form.querySelectorAll('.fm40b-chk-salida').forEach(cb => {
            cb.checked = true;
            const cantA = document.getElementById(cb.id.replace('_chk_a', '_cant_a'));
            if (cantA && !cantA.value) cantA.value = '1';
        });
    }

    function guardarFm40bModal(accion = 'guardar') {
        const form = document.getElementById('formFm40BModal');
        if (!form) return;

        const idTec = document.getElementById('fm40b_id_tecnico')?.value || '';
        const idVeh = document.getElementById('fm40b_id_vehiculo')?.value || '';
        const fb = document.getElementById('fm40b_feedback');
        if (!idTec || !idVeh) {
            if (fb) fb.innerHTML = '<span style="color:#dc2626;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Seleccione el técnico responsable y el vehículo asignado.</span>';
            if (!idTec) document.getElementById('fm40b_id_tecnico')?.focus();
            else document.getElementById('fm40b_id_vehiculo')?.focus();
            return;
        }

        const btnG = document.getElementById('fm40b_btn_guardar');
        const btnC = document.getElementById('fm40b_btn_cerrar_40b');
        if (btnG) btnG.disabled = true;
        if (btnC) btnC.disabled = true;
        if (fb) fb.innerHTML = '<span style="color:#0369a1;"><i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando documento 40 B...</span>';

        const fd = new FormData(form);
        fd.append('accion_muestreo', accion);

        const csrfVal = form.querySelector('input[name="csrf_token"]')?.value || '<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? $csrfToken), ENT_QUOTES, 'UTF-8') ?>';
        if (csrfVal && !fd.get('csrf_token')) {
            fd.set('csrf_token', csrfVal);
        }
        fetch('/Cycsa/publico/ordenes-servicio/guardar-fm40b', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfVal },
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (btnG) btnG.disabled = false;
            if (btnC) btnC.disabled = false;
            if (res.status === 'success') {
                fm40bCambioGuardado = true;
                const idHojaSel = parseInt(res.id_hoja_solicitud || fm40bHojaActual || 0, 10) || 0;
                actualizarEstadoVisualFm40B(res.estado_muestreo || 'En Campo', true, fm40bOsActual, idHojaSel, false);
                if (fb) {
                    fb.innerHTML = `<span style="color:#059669;"><i class="fa-solid fa-circle-check me-1"></i> ${escapeHtml(res.message || 'Guardado correctamente.')}</span>`;
                }
                const selHoja = document.getElementById('fm40b_id_hoja_solicitud');
                if (selHoja && idHojaSel > 0) {
                    const optSel = selHoja.querySelector(`option[value="${idHojaSel}"]`);
                    if (optSel) {
                        optSel.textContent = optSel.textContent.replace(/\s*\[[^\]]+\]\s*$/, '') + ` [40 B: ${res.estado_muestreo}]`;
                    }
                }
            } else {
                if (fb) fb.innerHTML = `<span style="color:#dc2626;">${escapeHtml(res.message || 'Error guardando FM-40 B.')}</span>`;
            }
        })
        .catch(err => {
            if (btnG) btnG.disabled = false;
            if (btnC) btnC.disabled = false;
            console.error(err);
            if (fb) fb.innerHTML = '<span style="color:#dc2626;">Error de comunicación con el servidor.</span>';
        });
    }

    // =========================================================================
    // 4C. CONTROLADOR MODAL PERÍODO MENSUAL / CONTRATO LARGO
    // =========================================================================
    let pcOsActual = 0;

    function abrirModalPeriodoContrato(idOS) {
        pcOsActual = parseInt(idOS, 10) || 0;
        if (!pcOsActual) return;

        const mod = document.getElementById('modalPeriodoContrato');
        if (mod) mod.style.display = 'flex';
        document.getElementById('pc_id_os').value = pcOsActual;
        document.getElementById('pc_feedback').innerHTML = '';

        fetch(`/Cycsa/publico/ordenes-servicio/periodo-contrato-datos?id_os=${encodeURIComponent(pcOsActual)}`)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') {
                    document.getElementById('pc_feedback').innerHTML = `<span style="color:#dc2626;">${escapeHtml(res.message || 'No se pudo cargar el período.')}</span>`;
                    return;
                }
                const os = res.os || {};
                document.getElementById('pc_subtitulo').innerText = `${os.codigo_os || ('O/S #' + pcOsActual)} · ${os.cliente_nombre || ''}`;

                const alertaAp = document.getElementById('pc_alerta_aprobada');
                if (alertaAp) {
                    if (os.es_aprobada) {
                        alertaAp.style.display = 'block';
                        document.getElementById('pc_estado_actual_lbl').innerText = os.estado || 'Aprobada';
                    } else {
                        alertaAp.style.display = 'none';
                    }
                }

                const esMult = !!os.es_multimes;
                document.getElementById('pc_es_multimes').checked = esMult;
                document.getElementById('pc_duracion_meses').value = os.duracion_meses || 3;
                document.getElementById('pc_fecha_inicio').value = os.fecha_inicio || new Date().toISOString().slice(0, 10);
                document.getElementById('pc_fecha_fin').value = os.fecha_fin || '';
                document.getElementById('pc_periodicidad').value = os.periodicidad || 'Mensual';

                togglePeriodoContratoTipo(esMult);
                if (esMult && !document.getElementById('pc_fecha_fin').value) {
                    recalcularFechaFinPeriodoModal();
                } else {
                    recalcularResumenPeriodoModal();
                }

                const btnGuardar = document.getElementById('pc_btn_guardar');
                if (btnGuardar) btnGuardar.style.display = os.solo_lectura ? 'none' : 'inline-flex';
            })
            .catch(() => {
                document.getElementById('pc_feedback').innerHTML = '<span style="color:#dc2626;">Error de comunicación cargando período.</span>';
            });
    }

    function cerrarModalPeriodoContrato() {
        const mod = document.getElementById('modalPeriodoContrato');
        if (mod) mod.style.display = 'none';
    }

    function togglePeriodoContratoTipo(activo) {
        const box = document.getElementById('pc_campos_multimes');
        if (!box) return;
        box.style.opacity = activo ? '1' : '0.45';
        box.style.pointerEvents = activo ? 'auto' : 'none';
        if (activo && !document.getElementById('pc_fecha_fin').value) {
            recalcularFechaFinPeriodoModal();
        }
        recalcularResumenPeriodoModal();
    }

    function seleccionarDuracionMesesModal(meses) {
        document.getElementById('pc_es_multimes').checked = true;
        togglePeriodoContratoTipo(true);
        document.getElementById('pc_duracion_meses').value = meses;
        recalcularFechaFinPeriodoModal();
    }

    function recalcularFechaFinPeriodoModal() {
        const fi = document.getElementById('pc_fecha_inicio')?.value;
        const m = Math.max(1, parseInt(document.getElementById('pc_duracion_meses')?.value || '3', 10) || 3);
        const ffEl = document.getElementById('pc_fecha_fin');
        if (fi && ffEl) {
            const d = new Date(fi + 'T00:00:00');
            if (!isNaN(d.getTime())) {
                d.setMonth(d.getMonth() + m);
                ffEl.value = d.toISOString().slice(0, 10);
            }
        }
        recalcularResumenPeriodoModal();
    }

    function recalcularMesesDesdeFechaFinModal() {
        const fi = document.getElementById('pc_fecha_inicio')?.value;
        const ff = document.getElementById('pc_fecha_fin')?.value;
        if (fi && ff) {
            const d1 = new Date(fi + 'T00:00:00');
            const d2 = new Date(ff + 'T00:00:00');
            if (!isNaN(d1.getTime()) && !isNaN(d2.getTime()) && d2 >= d1) {
                const diffMonths = Math.max(1, (d2.getFullYear() - d1.getFullYear()) * 12 + (d2.getMonth() - d1.getMonth()));
                document.getElementById('pc_duracion_meses').value = diffMonths;
            }
        }
        recalcularResumenPeriodoModal();
    }

    function recalcularResumenPeriodoModal() {
        const resEl = document.getElementById('pc_resumen_vigencia');
        if (!resEl) return;
        const activo = document.getElementById('pc_es_multimes')?.checked;
        if (!activo) {
            resEl.innerText = 'Modalidad Puntual (sin vigencia mensual extendida).';
            return;
        }
        const m = parseInt(document.getElementById('pc_duracion_meses')?.value || '0', 10) || 0;
        const fi = document.getElementById('pc_fecha_inicio')?.value || '';
        const ff = document.getElementById('pc_fecha_fin')?.value || '';
        const per = document.getElementById('pc_periodicidad')?.value || 'Mensual';

        document.querySelectorAll('.pc-mes-pill').forEach(btn => {
            const bm = parseInt(btn.getAttribute('data-meses') || '0', 10);
            if (bm === m) {
                btn.style.background = '#3730a3';
                btn.style.color = '#fff';
                btn.style.borderColor = '#3730a3';
            } else {
                btn.style.background = '';
                btn.style.color = '';
                btn.style.borderColor = '';
            }
        });

        let diasTxt = '';
        if (ff) {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const dFin = new Date(ff + 'T00:00:00');
            if (!isNaN(dFin.getTime())) {
                const diffDays = Math.round((dFin - hoy) / (1000 * 60 * 60 * 24));
                diasTxt = diffDays >= 0 ? ` · Faltan ${diffDays} días para finalizar` : ` · Vencido hace ${Math.abs(diffDays)} días`;
            }
        }
        resEl.innerText = `Contrato Largo (${per}) · ${m} ${m === 1 ? 'mes' : 'meses'} · Del ${fi || '—'} al ${ff || '—'}${diasTxt}`;
    }

    function guardarPeriodoContratoModal() {
        const form = document.getElementById('formPeriodoContratoModal');
        if (!form) return;

        const btn = document.getElementById('pc_btn_guardar');
        const fb = document.getElementById('pc_feedback');
        if (btn) btn.disabled = true;
        if (fb) fb.innerHTML = '<span style="color:#3730a3;"><i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando período...</span>';

        const fd = new FormData(form);
        const csrfVal = form.querySelector('input[name="csrf_token"]')?.value || '<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? $csrfToken), ENT_QUOTES, 'UTF-8') ?>';
        if (csrfVal && !fd.get('csrf_token')) {
            fd.set('csrf_token', csrfVal);
        }
        fetch('/Cycsa/publico/ordenes-servicio/guardar-periodo-contrato', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfVal },
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (btn) btn.disabled = false;
            if (res.status === 'success') {
                if (fb) fb.innerHTML = `<span style="color:#059669;"><i class="fa-solid fa-circle-check me-1"></i> ${escapeHtml(res.message || 'Guardado.')}</span>`;
                setTimeout(() => {
                    window.location.href = '/Cycsa/publico/ordenes-servicio?id_os=' + pcOsActual;
                }, 550);
            } else {
                if (fb) fb.innerHTML = `<span style="color:#dc2626;">${escapeHtml(res.message || 'Error guardando período.')}</span>`;
            }
        })
        .catch(() => {
            if (btn) btn.disabled = false;
            if (fb) fb.innerHTML = '<span style="color:#dc2626;">Error de comunicación con el servidor.</span>';
        });
    }

    // =========================================================================
    // 5. AUTO-APERTURA SI SE REDIRIGE CON id_os
    // =========================================================================
    <?php if (!empty($id_os_auto)): ?>
    <?php
    $codigoOSAuto = 'O/S #' . (int)$id_os_auto;
    foreach ($ordenes as $oAuto) {
        if ((int)$oAuto['id'] === (int)$id_os_auto) {
            $codigoOSAuto = $oAuto['codigo_os'];
            break;
        }
    }
    ?>
    document.addEventListener("DOMContentLoaded", function() {
        const idAuto = <?= (int)$id_os_auto ?>;
        const filaDesplegable = document.getElementById('desplegable-os-' + idAuto);
        const filaPrincipal = document.getElementById('fila-os-' + idAuto);
        if (filaDesplegable && filaPrincipal) {
            filaDesplegable.classList.add('mostrar');
            filaPrincipal.classList.add('expandida');
            filaPrincipal.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        <?php if (!empty($_GET['abrir_hoja'])): ?>
        if (typeof abrirModalHojaSolicitud === 'function') {
            abrirModalHojaSolicitud(idAuto, '<?= htmlspecialchars($codigoOSAuto, ENT_QUOTES, 'UTF-8') ?>', 0, true, '<?= htmlspecialchars($_GET['prefijo'] ?? 'MC', ENT_QUOTES, 'UTF-8') ?>');
        }
        <?php elseif (!empty($_GET['recepcion_hoja'])): ?>
        if (typeof abrirModalDatosRecepcion === 'function') {
            abrirModalDatosRecepcion(idAuto, '<?= htmlspecialchars($codigoOSAuto, ENT_QUOTES, 'UTF-8') ?>', <?= (int)$_GET['recepcion_hoja'] ?>);
        }
        <?php endif; ?>
    });
    <?php endif; ?>

    window.addEventListener('pageshow', function() {
        if (typeof rehabilitarBotonesEnvioHoja === 'function') {
            rehabilitarBotonesEnvioHoja();
        }
    });

    function abrirModalEnviarInformeOS(idDetalle, nombreEnsayo, clienteNombre, clienteEmail, codigoOS) {
        const mod = document.getElementById('modalEnviarInformeOS');
        if (!mod) return;
        document.getElementById('env_os_id_detalle').value = idDetalle;
        document.getElementById('env_os_txt_ensayo').textContent = nombreEnsayo || 'Informe de Ensayo';
        document.getElementById('env_os_txt_os').textContent = codigoOS || '';
        document.getElementById('env_os_txt_cliente').textContent = clienteNombre || '';
        document.getElementById('env_os_destinatario').value = clienteEmail || '';
        document.getElementById('env_os_asunto').value = 'Informe Oficial de Ensayo CYCSA - ' + (codigoOS || '') + ' (' + (nombreEnsayo || '') + ')';
        const btnPrev = document.getElementById('env_os_btn_prev_pdf');
        if (btnPrev) {
            btnPrev.href = '/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=' + encodeURIComponent(idDetalle);
        }
        mod.style.display = 'flex';
    }

    function cerrarModalEnviarInformeOS() {
        const mod = document.getElementById('modalEnviarInformeOS');
        if (mod) mod.style.display = 'none';
    }
</script>

<!-- MODAL: ENVIAR INFORME APROBADO AL CLIENTE (ATENCIÓN AL CLIENTE - ÓRDENES DE SERVICIO) -->
<div id="modalEnviarInformeOS" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:10050; align-items:center; justify-content:center; padding:16px;" onclick="if (event.target === this) cerrarModalEnviarInformeOS()">
    <div style="background:#ffffff; border-radius:12px; border:1px solid #cbd5e1; width:min(520px, 95vw); padding:22px 24px; box-shadow:0 20px 40px rgba(15,23,42,0.18);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:12px; margin-bottom:14px;">
            <h3 style="margin:0; font-size:17px; font-weight:700; color:#0f172a;">
                <i class="fa-solid fa-paper-plane" style="color:var(--cycsa-azul);"></i> Enviar Informe al Cliente
            </h3>
            <button type="button" onclick="cerrarModalEnviarInformeOS()" style="background:none; border:none; font-size:22px; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/enviar-matriz-cliente">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="env_os_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/ordenes-servicio">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:14px;">
                <div id="env_os_txt_ensayo" style="font-weight:700; color:#0f172a; font-size:13.5px;"></div>
                <div style="font-size:12px; color:#475569; margin-top:2px;">
                    Orden: <strong id="env_os_txt_os"></strong> · Cliente: <strong id="env_os_txt_cliente"></strong>
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:5px;">Correo(s) del destinatario *</label>
                <input type="text" name="destinatario" id="env_os_destinatario" required placeholder="cliente@empresa.com, copia@empresa.com"
                       style="width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
                <div style="font-size:11.5px; color:#64748b; margin-top:3px;">Separe múltiples destinatarios con coma.</div>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:5px;">Asunto del correo *</label>
                <input type="text" name="asunto" id="env_os_asunto" required
                       style="width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13.5px;">
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; border-top:1px solid #e2e8f0; padding-top:14px; flex-wrap:wrap;">
                <a href="#" id="env_os_btn_prev_pdf" target="_blank" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; padding:5px 12px; font-size:12.5px;">
                    <i class="fa-solid fa-file-pdf text-danger"></i> Previsualizar PDF
                </a>
                <div style="display:flex; gap:8px;">
                    <button type="button" onclick="cerrarModalEnviarInformeOS()" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; padding:5px 12px; font-size:12.5px;">Cancelar</button>
                    <button type="submit" class="btn-cycsa btn-cycsa-primary" style="min-height:34px; padding:5px 14px; font-size:12.5px;">
                        <i class="fa-solid fa-paper-plane"></i> Enviar por Correo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
