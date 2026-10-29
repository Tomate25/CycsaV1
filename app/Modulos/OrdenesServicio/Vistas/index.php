<?php
// Vista principal - Listado Unificado de Órdenes de Servicio & Hojas de Recepción (CYCSA ERP Premium Style)
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-dark: #0c2766;
        --cycsa-azul-light: #eff6ff;
    }

    /* MÓDULO O/S: TOKENS DE DISEÑO Y TIPOGRAFÍA */
    .os-modulo-container {
        max-width: 1440px;
        margin: 0 auto;
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

    <!-- BUSCADOR -->
    <form method="GET" action="/Cycsa/publico/ordenes-servicio" class="search-bar-premium">
        <div style="flex: 1; min-width: 280px; position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
            <input type="text" name="q" placeholder="Buscar por código O/S, cotización, cliente o proyecto..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control-cycsa" style="padding-left: 38px; min-height: 40px;">
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-cycsa btn-cycsa-primary" style="min-height: 40px; padding: 8px 18px;"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            <?php if(!empty($busqueda)): ?>
                <a href="/Cycsa/publico/ordenes-servicio" class="btn-cycsa btn-cycsa-secondary" style="min-height: 40px; padding: 8px 16px;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- TABLA DE REGISTROS CON DESPLIEGUE INTELIGENTE (5 COLUMNAS DESDENSIFICADAS) -->
    <div style="overflow-x: auto;">
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
                    <?php foreach ($ordenes as $os): ?>
                        <?php
                        // Determinar estado de muestreo para Casos A, B y C
                        $estadoMuestreo = 'sin_decidir';
                        if (!empty($os['id_pm'])) {
                            if (in_array($os['estado_muestreo'], ['Programado', 'En Proceso', 'En Campo'])) {
                                $estadoMuestreo = 'en_proceso';
                            } else {
                                $estadoMuestreo = 'finalizado';
                            }
                        } elseif (!empty($os['id_hoja'])) {
                            $estadoMuestreo = 'finalizado';
                        } elseif ($os['requiere_muestreo'] === 1 || $os['requiere_muestreo'] === '1') {
                            $estadoMuestreo = 'pendiente_programar';
                        } elseif ($os['requiere_muestreo'] === 0 || $os['requiere_muestreo'] === '0') {
                            $estadoMuestreo = 'no_aplica';
                        }

                        // Estado de la Hoja RT-FM-13 y aprobación general
                        $tieneHoja = !empty($os['id_hoja']);
                        $estadoOS = $os['estado'];
                        $cantHojas = count($os['hojas_servicio'] ?? []);

                        $esOSAprobada = !in_array($os['estado'], ['Estado 1: Recepcion', 'Pendiente', 'Borrador'])
                                        || $cantHojas > 0
                                        || !empty($os['id_hoja'])
                                        || !empty($os['id_pm'])
                                        || ($os['requiere_muestreo'] !== null && $os['requiere_muestreo'] !== '')
                                        || in_array($os['estado'], [
                                            'Aprobada', 'Estado 3: Aprobada', 'Estado 3: Ingreso Directo', 
                                            'Estado 3A: Programacion Muestreo', 'Estado 3B: Ejecucion Muestreo', 
                                            'Estado 4: Ingreso Laboratorio', 'Estado 5: Solicitud Tecnicos', 
                                            'Estado 6: Ejecucion Ensayos', 'Estado 7: Revision Resultados', 
                                            'Finalizado', 'En Proceso'
                                        ]);

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
                                        </strong>
                                        <span style="font-family: monospace; font-size: 11.5px; color: #475569; background: #f8fafc; padding: 2px 7px; border-radius: 4px; border: 1px solid #e2e8f0; display: inline-block; margin-top: 3px;">
                                            <i class="fa-solid fa-file-invoice" style="font-size: 10.5px; color:#64748b;"></i> <?= htmlspecialchars($os['cotizacion_codigo'] ?? 'N/A') ?>
                                        </span>
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
                                        <?php elseif ($estadoMuestreo === 'finalizado' && !empty($os['id_pm'])): ?>
                                            <span class="badge-premium badge-muestreo-fin" title="Muestreo finalizado y recibido en laboratorio">
                                                <i class="fa-solid fa-circle-check"></i> Muestreo Finalizado
                                            </span>
                                        <?php elseif ($estadoMuestreo === 'pendiente_programar'): ?>
                                            <span class="badge-premium badge-hoja-borrador" title="Requiere asignación de salida a campo">
                                                <i class="fa-solid fa-calendar-plus"></i> Requiere Muestreo
                                            </span>
                                        <?php elseif ($estadoMuestreo === 'no_aplica'): ?>
                                            <span class="badge-premium badge-muestreo-no" title="Entrega de muestras directamente en ventanilla">
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
                                        <?php if (!$esOSAprobada): ?>
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
                                    <?php if (!$esOSAprobada): ?>
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
                                            <?php if ($estadoMuestreo === 'pendiente_programar'): ?>
                                                <a href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-primary" style="min-height:38px; font-weight:600;">
                                                    <i class="fa-solid fa-calendar-days"></i> Programar Muestreo
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn-cycsa btn-cycsa-success" style="min-height:38px; font-weight:700;"
                                                        data-id-os="<?= $os['id'] ?>"
                                                        data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                        data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                        data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                        data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                        data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                        onclick="iniciarRegistroHojaRTFM13(this)">
                                                    <i class="fa-solid fa-file-signature"></i> Registrar Hoja
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:38px; font-weight:600; border-color:#cbd5e1;" onclick="toggleFilaDesplegable(<?= $os['id'] ?>, event)" title="Ver Hojas RT-FM-13 registradas">
                                                <i class="fa-solid fa-layer-group" style="color:var(--cycsa-azul);"></i> Ver Hojas (<?= $cantHojas ?>)
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- MENÚ DESPLEGABLE MÁS ACCIONES (BOOTSTRAP 5 DROPDOWN) -->
                                    <div class="dropdown" style="display: inline-block;">
                                        <button class="dropdown-btn-mas dropdown-toggle-no-caret" type="button" id="dropdownMenuOS<?= $os['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones de la Orden de Servicio">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownMenuOS<?= $os['id'] ?>" style="font-size: 13px; min-width: 220px; border-radius: 8px; border: 1px solid #e2e8f0; padding: 6px 0; z-index: 1050;">
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
                                            
                                            <?php if ($esOSAprobada): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item py-2"
                                                            data-id-os="<?= $os['id'] ?>"
                                                            data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                            data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                            data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                            data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                            data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                            onclick="abrirModalDecisionMuestreo(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>', true)">
                                                        <i class="fa-solid fa-plus-circle me-2 text-success"></i> + Nueva Hoja RT-FM-13
                                                    </button>
                                                </li>
                                                <?php if ($cantHojas > 0): ?>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank">
                                                            <i class="fa-solid fa-file-pdf me-2 text-danger"></i> Descargar PDF Oficial
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <a class="dropdown-item py-2" href="/Cycsa/publico/operaciones">
                                                    <i class="fa-solid fa-flask-vial me-2 text-info"></i> Ir a Operaciones / Lab
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
                                                    <div style="margin-top: 12px; display:flex; gap:8px;">
                                                        <a href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; font-size:12px; padding:6px 14px;">
                                                            <i class="fa-solid fa-pen-to-square"></i> Ver / Editar Datos de Muestreo
                                                        </a>
                                                    </div>
                                                <?php elseif ($os['requiere_muestreo'] === 1 || $os['requiere_muestreo'] === '1'): ?>
                                                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 14px; border-radius: 8px; color: #1e40af; font-size: 13px;">
                                                        <i class="fa-solid fa-calendar-plus me-1"></i> <strong>Requiere salida a campo:</strong> Pendiente de asignar técnico, vehículo y fechas.
                                                        <div style="margin-top: 10px;">
                                                            <a href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-primary" style="min-height:36px; padding: 6px 14px; font-size: 12.5px;">
                                                                <i class="fa-solid fa-calendar-days"></i> Programar Muestreo en Campo
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php elseif ($os['requiere_muestreo'] === 0 || $os['requiere_muestreo'] === '0'): ?>
                                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 14px; border-radius: 8px; color: #166534; font-size: 13px;">
                                                        <i class="fa-solid fa-flask me-1"></i> <strong>Ingreso Directo:</strong> Los especímenes son entregados por el cliente en ventanilla del laboratorio central.
                                                    </div>
                                                <?php else: ?>
                                                    <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 14px; border-radius: 8px; color: #92400e; font-size: 13px;">
                                                        <i class="fa-solid fa-circle-question me-1"></i> <strong>Por Definir:</strong> La modalidad se define al emitir cada Hoja RT-FM-13.
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
                                                        <?php if ($esOSAprobada && $cantHojas > 0): ?>
                                                            <button type="button" class="btn-cycsa btn-cycsa-success" style="min-height:30px; padding:4px 10px; font-size:11.5px; font-weight:700;"
                                                                    data-id-os="<?= $os['id'] ?>"
                                                                    data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                                    data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                                    data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                                    data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                                    data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                                    onclick="abrirModalDecisionMuestreo(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>', true)">
                                                                <i class="fa-solid fa-plus"></i> Nueva Hoja
                                                            </button>
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
                                                        'numero_registro' => '00001',
                                                        'codigo_documento' => $os['hoja_codigo'] ?? 'CYCSA-RT-FM-13',
                                                        'nombre_persona_toma_muestra' => $os['nombre_persona_toma_muestra'] ?? 'Cliente',
                                                        'fecha_hora_toma_muestra' => $os['fecha_hora_toma_muestra'] ?? '',
                                                        'total_muestras' => !empty($os['muestras_json']) ? count(json_decode($os['muestras_json'], true) ?: []) : 1
                                                    ]];
                                                }
                                                ?>
                                                <?php if (!$esOSAprobada): ?>
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
                                                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                                                <div>
                                                                    <div style="font-size:13px; font-weight:700; color:var(--cycsa-azul);">
                                                                        <i class="fa-solid fa-file-invoice"></i> <?= htmlspecialchars($hItem['codigo_documento'] ?? 'CYCSA-RT-FM-13') ?> 
                                                                        <span style="font-family:monospace; color:#0f172a; background:#e2e8f0; padding:2px 7px; border-radius:4px; font-size:12px;">Reg: <?= htmlspecialchars($hItem['numero_registro'] ?? ('#' . $hItem['id'])) ?></span>
                                                                    </div>
                                                                    <div style="font-size:11.5px; color:#64748b; margin-top:3px;">
                                                                        <i class="fa-solid fa-vials"></i> <?= (int)($hItem['total_muestras'] ?? 0) ?> espécimen(es) • 
                                                                        <i class="fa-regular fa-clock"></i> <?= !empty($hItem['fecha_hora_toma_muestra']) ? date('d/m/Y H:i', strtotime($hItem['fecha_hora_toma_muestra'])) : (!empty($hItem['fecha_creacion']) ? date('d/m/Y H:i', strtotime($hItem['fecha_creacion'])) : 'N/A') ?>
                                                                    </div>
                                                                </div>
                                                                <div style="display:flex; gap:6px;">
                                                                    <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height:34px; padding:4px 10px; font-size:12px;"
                                                                            onclick="abrirModalHojaSolicitud(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>', <?= $hItem['id'] ?>, false)"
                                                                            title="Editar Hoja RT-FM-13">
                                                                        <i class="fa-solid fa-pen"></i>
                                                                    </button>
                                                                    <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>&id_hoja=<?= $hItem['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-primary" style="min-height:34px; padding:4px 10px; font-size:12px;" title="Descargar PDF Oficial">
                                                                        <i class="fa-solid fa-file-pdf"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; padding: 20px; border-radius: 8px; text-align: center; color: #64748b; font-size: 13px;">
                                                        <i class="fa-solid fa-file-circle-question" style="font-size: 28px; margin-bottom: 8px; color: #94a3b8; display: block;"></i>
                                                        Aún no se ha registrado la primera Hoja de Servicio técnica para esta orden.
                                                        <div style="margin-top:12px;">
                                                            <button type="button" class="btn-cycsa btn-cycsa-success" style="min-height:36px; padding:6px 16px; font-size:12.5px; font-weight:700;"
                                                                    data-id-os="<?= $os['id'] ?>"
                                                                    data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                                    data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                                    data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                                    data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                                    data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                                    onclick="iniciarRegistroHojaRTFM13(this)">
                                                                <i class="fa-solid fa-file-signature"></i> Registrar Primera Hoja RT-FM-13
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- TARJETA 4: ENSAYOS, MATRIZ TÉCNICA Y LIMS -->
                                            <div class="tarjeta-detalle-os">
                                                <div class="tarjeta-detalle-titulo" style="display:flex; justify-content:space-between; align-items:center;">
                                                    <span><i class="fa-solid fa-microscope"></i> 4. Ensayos y Matriz Técnica</span>
                                                    <a href="/Cycsa/publico/operaciones" class="btn-cycsa btn-cycsa-secondary" style="min-height:30px; padding:4px 10px; font-size:11.5px;" title="Ir al módulo de Operaciones">
                                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Operaciones
                                                    </a>
                                                </div>
                                                <?php 
                                                $itemsEnsayos = $os['items'] ?? [];
                                                ?>
                                                <?php if (!empty($itemsEnsayos)): ?>
                                                    <div style="display:flex; flex-direction:column; gap:10px; max-height:240px; overflow-y:auto; padding-right:4px;">
                                                        <?php foreach ($itemsEnsayos as $it): ?>
                                                            <?php 
                                                            $tieneMatriz = !empty($it['resultados_json']);
                                                            $revInfo = ($tieneMatriz && function_exists('obtenerEstadoRevisionMatriz')) ? obtenerEstadoRevisionMatriz($it['resultados_json']) : null;
                                                            $codInf = ($tieneMatriz && function_exists('generarCodigoInformeEnsayo')) ? generarCodigoInformeEnsayo($it['resultados_json'], $os['fecha_muestreo'] ?? null, $it['descripcion_ensayo']) : null;
                                                            ?>
                                                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; display:flex; flex-direction:column; gap:6px;">
                                                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                                                                    <div style="font-size:13px; font-weight:700; color:#0f172a;">
                                                                        <i class="fa-solid fa-vial-virus" style="color:var(--cycsa-azul);"></i> <?= htmlspecialchars($it['descripcion_ensayo']) ?>
                                                                    </div>
                                                                    <?php if ($tieneMatriz && $revInfo): ?>
                                                                        <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; background:<?= $revInfo['badge_bg'] ?>; color:<?= $revInfo['badge_color'] ?>; border:1px solid <?= $revInfo['badge_border'] ?>;">
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
                                                                            Sin Matriz
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <?php if ($tieneMatriz && !empty($codInf)): ?>
                                                                    <div style="font-size:11.5px; font-family:monospace; color:#0369a1; background:#e0f2fe; padding:2px 8px; border-radius:4px; border:1px solid #bae6fd; align-self:flex-start;">
                                                                        <i class="fa-solid fa-barcode"></i> <?= htmlspecialchars($codInf) ?>
                                                                    </div>
                                                                <?php endif; ?>
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
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 50px 20px;">
                            <i class="fa-solid fa-folder-open" style="font-size: 36px; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                            <div style="font-size: 15px; font-weight: 600; color: #334155;">No se encontraron Órdenes de Servicio registradas</div>
                            <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">Las nuevas órdenes aprobadas desde cotizaciones aparecerán aquí automáticamente.</div>
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
    <div class="modal-premium-content" style="max-width: 1380px; width: 96%; max-height: 94vh; display: flex; flex-direction: column; padding: 0; border-radius: 14px; overflow: hidden; background: white; margin: 2vh auto; box-shadow: 0 20px 45px rgba(0,0,0,0.25);">
        
        <!-- ENCABEZADO STICKY FIJO SUPERIOR -->
        <div style="padding: 16px 26px; background: white; border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; flex-shrink: 0;">
            <div>
                <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-file-signature" style="color:var(--cycsa-azul);"></i> Hoja de Solicitud de Servicio (CYCSA-RT-FM-13)
                </h3>
                <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b;">
                    Orden de Servicio Vinculada: <strong id="hs_codigo_os_label" style="color:var(--cycsa-azul); font-family:monospace; font-size:14.5px;"></strong>
                </p>
            </div>
            <button type="button" onclick="cerrarModalHojaSolicitud()" class="btn-cerrar" style="font-size:28px; border:none; background:none; cursor:pointer; color:#64748b; line-height:1;" title="Cerrar modal">&times;</button>
        </div>

        <!-- CUERPO PRINCIPAL CON SCROLL INDEPENDIENTE -->
        <div style="flex: 1; overflow-y: auto; padding: 22px 26px;">
        
        <div id="loading-hoja-solicitud" style="text-align:center; padding: 50px; display:none;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:36px; color:var(--cycsa-azul);"></i>
            <p style="color:#64748b; margin-top:12px; font-size:14px; font-weight:600;">Cargando datos de la O/S y plantilla RT-FM-13...</p>
        </div>

        <!-- SELECTOR DE HOJAS RT-FM-13 (Para Contratos Activos / Múltiples Entregas) -->
        <div id="hs_selector_hojas_container" style="display:none; background:#f1f5f9; border:1.5px solid #cbd5e1; border-radius:8px; padding:12px 18px; margin-bottom:18px; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span style="font-size:12px; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:0.5px;">
                    <i class="fa-solid fa-layer-group" style="color:var(--cycsa-azul);"></i> Hojas / Entregas:
                </span>
                <div id="hs_lista_tabs_hojas" style="display:flex; gap:6px; flex-wrap:wrap;">
                    <!-- Tabs dinámicos inyectados por JS -->
                </div>
            </div>
            <div>
                <button type="button" id="hs_btn_nueva_entrega" onclick="cargarNuevaHojaEnModal()" class="btn-cycsa" style="background:#0284c7; color:white; font-size:12px; padding:6px 14px; border-radius:6px; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fa-solid fa-plus-circle"></i> + Nueva Hoja RT-FM-13
                </button>
            </div>
        </div>

        <div id="wrapper-split-rt-fm-13" style="display:none; grid-template-columns: 340px 1fr; gap: 20px; align-items: flex-start;">
            
            <!-- PANEL LATERAL IZQUIERDO: REFERENCIA VISUAL DE LA ORDEN DE SERVICIO (SOLO LECTURA) -->
            <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 16px; position: sticky; top: 0; max-height: 78vh; overflow-y: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div style="display:flex; align-items:center; justify-content:space-between; border-bottom: 1.5px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 12px;">
                    <span style="font-family:'Outfit'; font-weight:700; color:var(--cycsa-azul); font-size:12.5px; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-file-contract"></i> Referencia O/S
                    </span>
                    <span id="ref_os_badge_estado" style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; text-transform:uppercase;">Solo Lectura</span>
                </div>

                <div style="font-size: 12px; color: #334155; display: flex; flex-direction: column; gap: 7px;">
                    <div><strong style="color:#0f172a;">Cliente:</strong> <span id="ref_os_cliente">--</span></div>
                    <div><strong style="color:#0f172a;">RUC / Cédula:</strong> <span id="ref_os_rfc" style="font-family:monospace;">--</span></div>
                    <div><strong style="color:#0f172a;">Atención a:</strong> <span id="ref_os_atencion">--</span></div>
                    <div><strong style="color:#0f172a;">Proyecto:</strong> <span id="ref_os_proyecto">--</span></div>
                    <div><strong style="color:#0f172a;">Dirección:</strong> <span id="ref_os_direccion">--</span></div>
                    <div><strong style="color:#0f172a;">Cotización:</strong> <span id="ref_os_cotizacion" style="font-family:monospace;">--</span></div>
                    <div><strong style="color:#0f172a;">Forma de Pago:</strong> <span id="ref_os_pago">--</span></div>
                </div>

                <!-- Logística de Campo si aplica -->
                <div id="ref_os_logistica_box" style="display:none; background:white; border:1px solid #93c5fd; border-radius:8px; padding:10px; margin-top:12px;">
                    <strong style="font-size:11px; color:#0369a1; text-transform:uppercase; display:block; margin-bottom:4px;">
                        <i class="fa-solid fa-truck-pickup"></i> Logística de Muestreo en Campo
                    </strong>
                    <div style="font-size:11.5px; color:#1e293b;">
                        <div><strong>Técnico:</strong> <span id="ref_os_tecnico"></span></div>
                        <div><strong>Vehículo:</strong> <span id="ref_os_vehiculo"></span></div>
                        <div><strong>Fechas:</strong> <span id="ref_os_fechas"></span></div>
                    </div>
                </div>

                <!-- Tabla de Ensayos Solicitados por el Cliente -->
                <div style="margin-top: 15px;">
                    <strong style="font-size:11px; color:#0f172a; text-transform:uppercase; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                        <i class="fa-solid fa-vials" style="color:var(--cycsa-azul);"></i> Ensayos Solicitados (O/S)
                    </strong>
                    <div style="overflow-x:auto; border:1px solid #cbd5e1; border-radius:6px; background:white;">
                        <table style="width:100%; border-collapse:collapse; font-size:11px;">
                            <thead>
                                <tr style="background:#f1f5f9; font-size:10px; text-transform:uppercase; color:#475569;">
                                    <th style="padding:6px 4px; text-align:center; border-bottom:1px solid #cbd5e1; width:20px;">#</th>
                                    <th style="padding:6px; text-align:left; border-bottom:1px solid #cbd5e1;">Descripción</th>
                                    <th style="padding:6px; text-align:center; border-bottom:1px solid #cbd5e1; width:35px;">Cant</th>
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

                <!-- 1. METADATOS Y CONTROL INTERNO -->
                <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-clipboard-check"></i> A rellenar por el laboratorio (Control Interno)</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; margin-bottom: 15px; align-items:flex-end;">
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">No. Registro Laboratorio</label>
                        <input type="text" name="numero_registro" id="hs_numero_registro" class="form-control-cycsa" style="font-size:13px; font-weight:700; height:38px; box-sizing:border-box;" placeholder="Ej: 23896">
                    </div>
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Fecha/Hora de Llegada al Lab</label>
                        <input type="datetime-local" name="fecha_hora_llegada_laboratorio" id="hs_fecha_llegada" required class="form-control-cycsa" style="font-size:13px; height:38px; box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Código del Documento</label>
                        <input type="text" name="codigo_documento" id="hs_codigo_documento" readonly class="form-control-cycsa" style="background:#f1f5f9; font-weight:700; font-size:13px; height:38px; box-sizing:border-box;" value="CYCSA-RT-FM-13">
                    </div>
                </div>

                <!-- 2. DATOS DEL CLIENTE -->
                <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-user-tie"></i> 1. Empresa o Cliente que Solicita el Servicio</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; margin-bottom: 12px; align-items:flex-end;">
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Nombre Empresa/Cliente</label>
                        <input type="text" name="nombre_empresa_o_cliente" id="hs_nombre_empresa" required class="form-control-cycsa" style="height:38px; box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Razón Social</label>
                        <input type="text" name="razon_social" id="hs_razon_social" class="form-control-cycsa" placeholder="Ej: CYCSA S.A. / Persona Natural" style="height:38px; box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Dirección Proyecto/Obra</label>
                        <input type="text" name="direccion_proyecto" id="hs_direccion" required class="form-control-cycsa" style="height:38px; box-sizing:border-box;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; margin-bottom: 15px; align-items:flex-end;">
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Teléfono</label>
                        <input type="text" name="telefono" id="hs_telefono" required class="form-control-cycsa" style="height:38px; box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Correo Electrónico</label>
                        <input type="email" name="correo_electronico" id="hs_email" required class="form-control-cycsa" style="height:38px; box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Persona que entrega la muestra</label>
                        <input type="text" name="nombre_persona_entrega_muestra" id="hs_persona_entrega" required class="form-control-cycsa" style="height:38px; box-sizing:border-box;">
                    </div>
                </div>

                <!-- 3. DATOS DE LA MUESTRA -->
                <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-flask-vial"></i> 1. Datos de la Muestra (1.1 y 1.2)</div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">1.1 Denominación-Descripción e Identificación de la Muestra &mdash; Naturaleza de la muestra:</label>
                    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:5px;">
                        <?php foreach (['Concreto', 'Bloques', 'Suelo', 'Adoquines', 'Agregados', 'Otros materiales'] as $nat): ?>
                            <label style="display:flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:6px 12px; font-size:13px; cursor:pointer;">
                                <input type="checkbox" name="naturaleza_muestra[]" value="<?= $nat ?>" class="hs-nat-checkbox"> <?= $nat ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; margin-bottom: 15px; align-items:flex-end;">
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;" title="Describir la ubicación del punto donde se tomó la muestra así como la comarca.">Procedencia/ Punto de muestreo:</label>
                        <input type="text" name="procedencia_punto_muestreo" id="hs_procedencia" required class="form-control-cycsa" placeholder="Punto de toma y comarca" style="height:38px; box-sizing:border-box;">
                    </div>
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">Persona quien tomó la muestra:</label>
                        <input type="text" name="nombre_persona_toma_muestra" id="hs_persona_toma" list="lista_tecnicos_hs_os" required class="form-control-cycsa" placeholder="Nombre (Ej: Juan / Cliente)" style="height:38px; box-sizing:border-box;">
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
                    <div class="form-group" style="display:flex; flex-direction:column; width:100%;">
                        <label style="font-size:12px; font-weight:700; color:#475569; margin-bottom:4px; white-space:nowrap;">1.2 Fecha y hora en que se tomó:</label>
                        <input type="datetime-local" name="fecha_hora_toma_muestra" id="hs_fecha_toma" required class="form-control-cycsa" style="height:38px; box-sizing:border-box;">
                    </div>
                </div>

                <!-- 4. IDENTIFICACIONES PROPIAS (TABLA DINÁMICA) -->
                <div style="display:flex; justify-content:space-between; align-items:center; font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;">
                    <span><i class="fa-solid fa-list-ol"></i> 2. Identificaciones Propias de la Muestra</span>
                    <button type="button" class="btn-cycsa btn-cycsa-primary" style="padding:6px 14px; font-size:12.5px;" onclick="agregarFilaMuestraModal()">
                        <i class="fa-solid fa-plus"></i> Agregar Muestra
                    </button>
                </div>

                <!-- BARRA DE ASIGNACIÓN GENERAL DE PLANTILLA (SELECTOR INTERACTIVO AJAX AMPLIADO) -->
                <div style="background:#f0fdf4; border:1.5px solid #86efac; border-radius:10px; padding:12px 16px; margin-bottom:15px; position:relative;">
                    <input type="hidden" id="select_plantilla_global" name="plantilla_global_id" value="">
                    
                    <!-- Fila Superior: Encabezado y Acciones -->
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-layer-group text-success" style="font-size:18px;"></i>
                            <span style="font-size:13px; font-weight:700; color:#166534;">Plantilla de Ensayo Predeterminada:</span>
                            <span style="font-size:11.5px; color:#15803d; font-weight:500;">(Se asigna automáticamente a especímenes nuevos)</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button type="button" class="btn-cycsa btn-cycsa-success" onclick="aplicarPlantillaATodasLasMuestras()" style="padding:6px 14px; font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                                <i class="fa-solid fa-check-double"></i> Aplicar a todas las muestras
                            </button>
                            <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="abrirPreviewPlantillaGlobal()" style="padding:6px 12px; font-size:12px;" title="Ver maqueta de la plantilla seleccionada">
                                <i class="fa-solid fa-eye"></i> Vista Previa
                            </button>
                        </div>
                    </div>

                    <!-- Fila Principal: Desplegable AJAX Full-Width -->
                    <div style="position:relative; width:100%;">
                        <div id="trigger_plantilla_global" onclick="toggleDropdownPlantillasGlobal()" style="background:white; border:1.5px solid #86efac; border-radius:8px; padding:8px 14px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; min-height:42px; transition:all 0.2s;" onmouseover="this.style.borderColor='#16a34a'; this.style.boxShadow='0 3px 8px rgba(22,163,74,0.12)';" onmouseout="this.style.borderColor='#86efac'; this.style.boxShadow='none';">
                            <div id="texto_plantilla_seleccionada" style="display:flex; align-items:center; gap:10px; font-size:12.5px; color:#334155; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <i class="fa-solid fa-file-waveform text-success" style="font-size:15px;"></i>
                                <span style="font-weight:600;">-- Seleccionar Plantilla General --</span>
                            </div>
                            <i class="fa-solid fa-chevron-down" style="color:#64748b; font-size:12px; margin-left:8px;"></i>
                        </div>

                        <!-- Panel Flotante Desplegable AJAX Espacioso -->
                        <div id="dropdown_plantillas_panel" style="display:none; position:absolute; top:calc(100% + 6px); left:0; width:100%; background:white; border:1.5px solid #cbd5e1; border-radius:10px; box-shadow:0 16px 36px rgba(0,0,0,0.2); z-index:99999; padding:14px; box-sizing:border-box;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                                <div style="position:relative; flex:1;">
                                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                                    <input type="text" id="input_buscar_plantilla_ajax" placeholder="Buscar por código (ej: FM-22, FM-61, FM-35), nombre o norma técnica..." oninput="filtrarPlantillasAjax(this.value)" style="width:100%; padding:8px 12px 8px 34px; font-size:13px; border:1.5px solid #cbd5e1; border-radius:6px; box-sizing:border-box; outline:none;" autocomplete="off">
                                </div>
                                <button type="button" onclick="cerrarDropdownPlantillasGlobal()" class="btn btn-sm btn-outline-secondary" style="padding:6px 10px; font-size:14px; line-height:1; cursor:pointer;" title="Cerrar">&times;</button>
                            </div>

                            <div style="display:flex; gap:8px; margin-bottom:10px; flex-wrap:wrap;">
                                <button type="button" class="btn-filtro-plt active" onclick="filtrarCategoriaPlantilla('todos', this)" style="padding:4px 12px; font-size:11.5px; font-weight:600; border-radius:14px; border:1px solid #0284c7; background:#0284c7; color:white; cursor:pointer;">Todas (<span id="cnt_plt_todas">0</span>)</button>
                                <button type="button" class="btn-filtro-plt" onclick="filtrarCategoriaPlantilla('cliente', this)" style="padding:4px 12px; font-size:11.5px; font-weight:600; border-radius:14px; border:1px solid #cbd5e1; background:#f8fafc; color:#475569; cursor:pointer;">⭐ Del Cliente (<span id="cnt_plt_cliente">0</span>)</button>
                                <button type="button" class="btn-filtro-plt" onclick="filtrarCategoriaPlantilla('servicio', this)" style="padding:4px 12px; font-size:11.5px; font-weight:600; border-radius:14px; border:1px solid #cbd5e1; background:#f8fafc; color:#475569; cursor:pointer;">🎯 Recomendadas (<span id="cnt_plt_servicio">0</span>)</button>
                            </div>

                            <div id="lista_resultados_plantillas_ajax" style="max-height:280px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:6px; padding:6px;">
                                <!-- Inyectado dinámicamente por AJAX -->
                            </div>
                        </div>
                    </div>
                </div>

                <table style="width:100%; border-collapse:collapse; margin-bottom:20px; border:1px solid #e2e8f0;" id="hs-tabla-muestras">
                    <thead>
                        <tr style="background:#f8fafc; font-size:11.5px; color:#475569; text-transform:uppercase;">
                            <th style="width:20%; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left;">Identificación Espécimen</th>
                            <th style="width:45%; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left;">Descripción de la Muestra / Elemento</th>
                            <th style="width:25%; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left;">Plantilla Asignada</th>
                            <th style="width:10%; padding:8px; border-bottom:1px solid #e2e8f0; text-align:center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="hs-tbody-muestras">
                        <!-- Filas inyectadas dinámicamente -->
                    </tbody>
                </table>

                <!-- 5. ANÁLISIS REQUERIDOS (CHECKBOXES) -->
                <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-vial"></i> 3. Parámetros Solicitados</div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom: 20px;">
                    <!-- Columna Concreto -->
                    <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                        <h4 style="margin:0 0 10px 0; font-size:13px; color:var(--cycsa-azul); font-family:'Outfit';"><i class="fa-solid fa-cube"></i> 3.1 Muestra de Concreto, Adoquines, Bloques</h4>
                        <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                            <label><input type="checkbox" name="req_resistencia_concreto" value="1" id="hs_req_concreto"> Resistencia de conc</label>
                            <label><input type="checkbox" name="req_resistencia_adoquin" value="1" id="hs_req_adoquin"> Resistencia de adoquin</label>
                            <label><input type="checkbox" name="req_resistencia_bloques" value="1" id="hs_req_bloques"> Resistencia bloques</label>
                            <div style="margin-top:6px;">
                                <label style="font-size:12px; color:#64748b;">Otros:</label>
                                <input type="text" name="req_otros_concreto" id="hs_req_otros_concreto" class="form-control-cycsa" style="padding:6px 10px; font-size:12px;" placeholder="Otros análisis en concreto...">
                            </div>
                        </div>
                    </div>

                    <!-- Columna Suelos -->
                    <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                        <h4 style="margin:0 0 10px 0; font-size:13px; color:var(--cycsa-azul); font-family:'Outfit';"><i class="fa-solid fa-mountain"></i> 3.2 Muestras de Suelo</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:12.5px;">
                            <label><input type="checkbox" name="req_granulometria" value="1" id="hs_req_granulometria"> Granulometria</label>
                            <label><input type="checkbox" name="req_limites_atterberg" value="1" id="hs_req_limites"> Límites de atterberg</label>
                            <label><input type="checkbox" name="req_humedad" value="1" id="hs_req_humedad"> Humedad</label>
                            <label><input type="checkbox" name="req_resistencia_corte" value="1" id="hs_req_resistencia_corte"> Resistencia al corte</label>
                            <label><input type="checkbox" name="req_clasificacion_sucs_hr" value="1" id="hs_req_clasificacion_sucs_hr"> Clasificación SUCS/HR</label>
                            <label><input type="checkbox" name="req_proctor_sm" value="1" id="hs_req_proctor"> PROCTOR S/M</label>
                            <label><input type="checkbox" name="req_infiltracion" value="1" id="hs_req_infiltracion"> Infiltración</label>
                            <label><input type="checkbox" name="req_cbr" value="1" id="hs_req_cbr"> CBR</label>
                            <label><input type="checkbox" name="req_densidad" value="1" id="hs_req_densidad"> Densidad</label>
                        </div>
                        <div style="margin-top:8px;">
                            <label style="font-size:12px; color:#64748b;">Otros:</label>
                            <input type="text" name="req_otros_suelo" id="hs_req_otros_suelo" class="form-control-cycsa" style="padding:6px 10px; font-size:12px;" placeholder="Otros análisis en suelos...">
                        </div>
                    </div>
                </div>

                <!-- 3.3 OTROS MATERIALES -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #cbd5e1; padding-bottom:6px; margin-bottom:10px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#1e293b; cursor:pointer; margin:0;">
                            <input type="checkbox" name="req_otros_materiales" value="1" id="hs_req_otros_materiales"> 3.3 Otros Materiales &mdash; Otro
                        </label>
                    </div>
                    <div class="form-group" style="margin-bottom:10px;">
                        <label style="font-size:12px; color:#475569; font-weight:600;">* Si seleccionó la casilla otros, favor decir que análisis necesita:</label>
                        <input type="text" name="descripcion_otros_analisis" id="hs_descripcion_otros" class="form-control-cycsa" style="font-size:12.5px; padding:6px 10px;" placeholder="Detalle el análisis requerido...">
                    </div>
                </div>

                <!-- 6. ANÁLISIS ADICIONALES Y OBSERVACIONES -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Analisis adicionales</label>
                        <textarea name="analisis_adicionales" id="hs_analisis_adicionales" class="form-control-cycsa" rows="2" placeholder="Análisis adicionales requeridos..."></textarea>
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Observaciones</label>
                        <textarea name="observaciones" id="hs_observaciones" class="form-control-cycsa" rows="2" placeholder="Observaciones de recepción de muestra..."></textarea>
                    </div>
                </div>

                <!-- 7. FIRMAS Y RECEPCIÓN CYCSA -->
                <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-signature"></i> Recepción y Conformidad</div>
                <div style="display:grid; grid-template-columns:2fr 1fr 1fr; gap:15px; margin-bottom:15px; align-items:center;">
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Persona de CYCSA quien recibe la muestra</label>
                        <input type="text" name="nombre_recibe_cycsa" id="hs_nombre_recibe" required class="form-control-cycsa" placeholder="Nombre completo">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12.5px; cursor:pointer; margin-top:6px;">
                            <input type="checkbox" name="firma_recibe_cycsa" value="1" id="hs_firma_recibe_cycsa"> ¿Firma digital receptor?
                        </label>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <label style="display:flex; align-items:center; gap:6px; font-size:12.5px; cursor:pointer; margin-top:6px;">
                            <input type="checkbox" name="firma_cliente" value="1" id="hs_firma_cliente"> ¿Firma digital cliente?
                        </label>
                    </div>
                </div>

                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:10px 12px; margin-bottom:15px; font-size:11.5px; color:#1e40af; line-height:1.4;">
                    <strong>*.- Con este documento doy fe</strong>, que todo lo escrito lo he revisado y que he quedado de mutuo acuerdo de los servicios que me ofrecerá CYCSA en la muestra. Cualquier cambio deberá ser notificado previo a CYCSA de cualquier forma escrita para su debido registro.
                </div>

                <!-- CONTROL DE RECEPCIÓN FÍSICA Y CONGELAMIENTO ISO 17025 -->
                <div style="background:#fef3c7; border:1.5px solid #f59e0b; border-radius:8px; padding:12px 16px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <label style="display:flex; align-items:center; gap:8px; font-weight:700; color:#92400e; font-size:13px; cursor:pointer; margin:0;">
                            <input type="checkbox" name="confirmar_recepcion_lims" value="1" id="chk_confirmar_recepcion_lims" style="transform:scale(1.2);">
                            <span><i class="fa-solid fa-lock text-warning me-1"></i> Confirmar Recepción Física y Congelar Plantillas (LIMS / ISO 17025)</span>
                        </label>
                        <div style="font-size:11px; color:#b45309; margin-left:26px; margin-top:2px;">
                            Al marcar esta opción, las plantillas de ensayo seleccionadas quedarán congeladas permanentemente para trazabilidad de auditoría.
                        </div>
                    </div>
                    <span style="font-size:10.5px; background:#fffbeb; color:#92400e; border:1px solid #fde68a; padding:4px 8px; border-radius:6px; font-weight:700;">Trazabilidad ISO 17025</span>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; border-top:1.5px solid #e2e8f0; padding-top:18px; margin-top:20px;">
                    <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="cerrarModalHojaSolicitud()">Cancelar</button>
                    <button type="submit" class="btn-cycsa btn-cycsa-success" style="padding:10px 26px; font-size:14px; font-weight:700;">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Hoja de Solicitud (RT-FM-13)
                    </button>
                </div>
            </form>
        </div>
        </div>

        <!-- FOOTER STICKY INFERIOR FLOTANTE SIEMPRE VISIBLE -->
        <div style="padding: 14px 26px; background: #f8fafc; border-top: 1.5px solid #e2e8f0; display: flex; justify-content: flex-end; align-items: center; gap: 12px; position: sticky; bottom: 0; z-index: 1000; flex-shrink: 0; box-shadow: 0 -4px 15px rgba(0,0,0,0.04);">
            <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height: 40px; padding: 8px 22px; font-size: 13.5px;" onclick="cerrarModalHojaSolicitud()">Cancelar</button>
            <button type="submit" form="form-hoja-solicitud" class="btn-cycsa btn-cycsa-success" style="min-height: 40px; padding: 8px 28px; font-size: 14px; font-weight: 700; box-shadow: 0 2px 8px rgba(16,185,129,0.25);" onclick="var f = document.getElementById('form-hoja-solicitud'); if (f.checkValidity && !f.checkValidity()) { f.reportValidity(); } else { f.submit(); }">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Hoja de Solicitud (RT-FM-13)
            </button>
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

        if (tipo === 'logistica') {
            window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + encodeURIComponent(idOS);
            return;
        }

        const prefijo = (tipo === 'campo') ? 'MC' : 'MS';

        if (!esSegunda && tipo === 'directo') {
            const csrfVal = document.querySelector('input[name="csrf_token"]')?.value || '<?= $_SESSION['csrf_token'] ?? '' ?>';
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
    // 2. INTERCEPCIÓN INTELIGENTE DE "REGISTRAR HOJA RT-FM-13" (Casos A, B y C)
    // =========================================================================
    function iniciarRegistroHojaRTFM13(btnOrId, codeParam, estadoParam, tecParam, idaParam, llegParam) {
        let idOS, codigoOS, estadoMuestreo, tecnico, fechaIda, fechaLlegada;

        if (typeof btnOrId === 'object' && btnOrId !== null) {
            idOS = btnOrId.getAttribute('data-id-os');
            codigoOS = btnOrId.getAttribute('data-codigo-os');
            estadoMuestreo = btnOrId.getAttribute('data-estado-muestreo') || 'sin_decidir';
            tecnico = btnOrId.getAttribute('data-tecnico') || '';
            fechaIda = btnOrId.getAttribute('data-fecha-ida') || '';
            fechaLlegada = btnOrId.getAttribute('data-fecha-llegada') || '';
        } else {
            idOS = btnOrId;
            codigoOS = codeParam || 'O/S #' + idOS;
            estadoMuestreo = estadoParam || 'sin_decidir';
            tecnico = tecParam || '';
            fechaIda = idaParam || '';
            fechaLlegada = llegParam || '';
        }

        // CASO B: MUESTREO EN PROCESO (Técnico en Campo)
        if (estadoMuestreo === 'en_proceso') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Muestreo en campo en proceso',
                    html: `
                        <div style="text-align:left; font-size:13.5px; color:#475569; margin-top:8px; line-height:1.5;">
                            <div style="background:#fef3c7; border:1px solid #fde68a; border-radius:8px; padding:12px; margin-bottom:14px;">
                                <strong style="color:#92400e; display:block; margin-bottom:4px;"><i class="fa-solid fa-truck-pickup"></i> Estado: Técnico en Campo</strong>
                                <div style="font-size:12.5px; color:#78350f;">
                                    ${tecnico ? `<div><strong>Técnico:</strong> ${tecnico}</div>` : ''}
                                    ${fechaIda ? `<div><strong>Salida:</strong> ${fechaIda}</div>` : ''}
                                    ${fechaLlegada ? `<div><strong>Retorno estimado:</strong> ${fechaLlegada}</div>` : ''}
                                </div>
                            </div>
                            <p style="font-size:14px; font-weight:700; color:#0f172a; margin-bottom:6px;">¿El técnico ya regresó al laboratorio con los especímenes?</p>
                            <p style="font-size:12px; color:#64748b; margin:0;">Al confirmar, la orden se marcará como finalizada y se abrirá el formulario RT-FM-13 de inmediato.</p>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: '<i class="fa-solid fa-circle-check"></i> Sí, finalizar muestreo',
                    confirmButtonColor: '#10b981',
                    denyButtonText: '<i class="fa-solid fa-pen-to-square"></i> Ver / Editar Logística',
                    denyButtonColor: '#103487',
                    cancelButtonText: 'Aún en campo (Cerrar)',
                    cancelButtonColor: '#94a3b8',
                    reverseButtons: false,
                    focusConfirm: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Finalizando muestreo...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        const csrfVal = document.querySelector('input[name="csrf_token"]')?.value || '<?= $_SESSION['csrf_token'] ?? '' ?>';
                        const formData = new FormData();
                        formData.append('id_os', idOS);
                        formData.append('ajax', '1');
                        formData.append('csrf_token', csrfVal);

                        fetch('/Cycsa/publico/ordenes-servicio/finalizar-muestreo', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfVal
                            },
                            body: formData
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.close();
                                if (typeof btnOrId === 'object' && btnOrId !== null) {
                                    btnOrId.setAttribute('data-estado-muestreo', 'finalizado');
                                }
                                abrirModalHojaSolicitud(idOS, codigoOS);
                            } else {
                                Swal.fire('Error', res.message || 'No se pudo finalizar el muestreo.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                        });
                    } else if (result.isDenied) {
                        window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
                    }
                });
            } else {
                if (confirm("El técnico está en campo.\n\n¿El técnico ya regresó y desea FINALIZAR el muestreo para abrir la Hoja RT-FM-13?")) {
                    window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
                }
            }
            return;
        }

        // =========================================================================
        // CASO PENDIENTE DE PROGRAMAR (El usuario ya indicó que requiere muestreo)
        // =========================================================================
        if (estadoMuestreo === 'pendiente_programar') {
            window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
            return;
        }

        // Siempre preguntar si es muestreo en campo (MC) o ingreso directo (MS) al registrar una hoja RT-FM-13
        abrirModalDecisionMuestreo(idOS, codigoOS, false);
    }

    // =========================================================================
    // 3. APERTURA Y CONTROL DEL MODAL HOJA DE SOLICITUD (CYCSA-RT-FM-13)
    // =========================================================================
    const modHS = document.getElementById('modalHojaSolicitud');
    const hsLoading = document.getElementById('loading-hoja-solicitud');
    const hsSplit = document.getElementById('wrapper-split-rt-fm-13');

    let idOSModalActual = 0;
    let codigoOSModalActual = '';

    function abrirModalHojaSolicitud(idOS, code, idHoja = 0, esNueva = false, prefijo = '') {
        idOSModalActual = idOS;
        codigoOSModalActual = code;
        document.getElementById('hs_codigo_os_label').innerText = code;
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

        fetch(url)
            .then(res => {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status + ' (' + res.statusText + ')');
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

    function cargarNuevaHojaEnModal() {
        if (!idOSModalActual) return;
        cerrarModalHojaSolicitud();
        abrirModalDecisionMuestreo(idOSModalActual, codigoOSModalActual, true);
    }

    let prefijoMuestraActual = 'MC';
    let siguienteConsecutivoMuestra = 1;
    let siguienteConsecutivoMuestraInicial = 1;
    let anioActual2Digitos = String(new Date().getFullYear()).slice(-2);

    function llenarModalHojaSolicitud(data) {
        const h = data.hoja;
        const os = data.os_referencia;
        prefijoMuestraActual = data.prefijo_muestra || 'MC';
        siguienteConsecutivoMuestra = parseInt(data.siguiente_consecutivo_muestra || 1, 10) || 1;
        siguienteConsecutivoMuestraInicial = siguienteConsecutivoMuestra;
        window.ensayosDisponiblesOS = (data.ensayos_disponibles_os || []).filter(function (ens) {
            return ens.permitido !== 0 && ens.permitido !== '0' && ens.permitido !== false;
        });
        window.clienteIdActualHoja = data.cliente_id || '';

        poblarSelectorPlantillaGlobal(window.ensayosDisponiblesOS, window.clienteIdActualHoja);

        // Set id_hoja hidden field
        const elIdHoja = document.getElementById('hs_id_hoja');
        if (elIdHoja) {
            elIdHoja.value = data.es_nueva ? 0 : (h.id || 0);
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
                    btnTab.innerHTML = `<i class="fa-solid fa-file-lines" style="color:${isSelected ? '#0284c7' : '#94a3b8'};"></i> Hoja #${itemHoja.numero_secuencial || (idx + 1)} <span style="font-size:10px; font-family:monospace; background:${isSelected ? '#bae6fd' : '#f1f5f9'}; padding:1px 5px; border-radius:4px; font-weight:700;">${itemHoja.numero_registro || 'Sin Reg'}</span>`;
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
                    det.ensayo_id || null,
                    det.plantilla_id || null,
                    parseInt(det.seleccion_manual || 0),
                    det.motivo_cambio || '',
                    parseInt(det.bloqueado_recepcion || 0)
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
                    m.ensayo_id || null,
                    m.plantilla_id || null,
                    parseInt(m.seleccion_manual || 0),
                    m.motivo_cambio || '',
                    parseInt(m.bloqueado || 0)
                );
            });
        } else {
            // No pre-poblar automáticamente especímenes; el usuario los agregará manualmente
            mostrarEstadoVacioMuestras();
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
        const regex = new RegExp(`^[A-Za-z]+-(\\d+)-(\\d{2}|\\d{4})$`, 'i');
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
                <td colspan="4" style="text-align:center; padding:32px 16px; color:#64748b; background:#f8fafc; border-radius:6px;">
                    <div style="font-size:26px; color:#94a3b8; margin-bottom:6px;"><i class="fa-solid fa-vial-circle-check"></i></div>
                    <div style="font-size:13px; font-weight:700; color:#334155;">No hay muestras registradas en esta hoja</div>
                    <div style="font-size:11.5px; color:#64748b; margin-top:3px;">Haz clic en el botón verde <strong>"+ Agregar Muestra"</strong> para añadir los especímenes recibidos o muestreados.</div>
                </td>
            </tr>
        `;
    }

    function agregarFilaMuestraModal(tipo = null, nom = '', desc = '', info = '', ensayoId = null, plantillaId = null, seleccionManual = 0, motivoCambio = '', bloqueado = 0) {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;

        // Quitar fila de estado vacío si existe
        const filaVacia = document.getElementById('fila-sin-muestras');
        if (filaVacia) filaVacia.remove();

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
                info = (prefijo === 'MS') ? 'Recepción Lab Central' : 'Muestreo en Obra';
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
        let codigoPlantilla = '';
        if (plantillaId && Array.isArray(window.plantillasCache)) {
            const plt = window.plantillasCache.find(p => String(p.id) === String(plantillaId));
            if (plt) {
                codigoPlantilla = plt.codigo || '';
                etiquetaPlantilla = plt.codigo ? `${plt.codigo} · ${plt.nombre}` : plt.nombre;
            }
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding:6px 8px; vertical-align:middle; border-bottom:1px solid #e2e8f0;">
                <input type="text" name="m_nombre[]" value="${nom}" readonly required class="form-control-cycsa" style="padding:6px 10px; font-size:12px; font-weight:700; font-family:monospace; color:var(--cycsa-azul); background:#f8fafc; cursor:not-allowed; border-color:#cbd5e1;" title="Código consecutivo asignado por el sistema">
                <input type="hidden" name="m_info[]" value="${escapeHtml(info)}">
                <input type="hidden" name="m_ensayo_id[]" value="${ensayoId || 1}">
                <input type="hidden" name="m_plantilla_id[]" value="${plantillaId || ''}" class="input-plantilla-muestra">
                <input type="hidden" name="m_seleccion_manual[]" value="${seleccionManual}">
                <input type="hidden" name="m_motivo_cambio[]" value="${escapeHtml(motivoCambio)}">
                <input type="hidden" name="m_bloqueado[]" value="${bloqueado}">
            </td>
            <td style="padding:6px 8px; vertical-align:middle; border-bottom:1px solid #e2e8f0;">
                <input type="text" name="m_desc[]" value="${escapeHtml(desc)}" class="form-control-cycsa" style="padding:6px 10px; font-size:12.5px;" required placeholder="Descripción de la muestra (ej: Concreto F'c 210 kg/cm² - Columnas Eje C-3 / Sub-base)">
                ${motivoCambio ? `<div style="font-size:10px; color:#b45309; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${escapeHtml(motivoCambio)}"><i class="fa-solid fa-triangle-exclamation"></i> ${escapeHtml(motivoCambio)}</div>` : ''}
            </td>
            <td style="padding:6px 8px; vertical-align:middle; border-bottom:1px solid #e2e8f0;">
                <div class="col-plantilla-badge" style="display:flex; align-items:center; gap:6px;">
                    <span class="badge badge-plt-display" style="background:${plantillaId ? '#e0f2fe' : '#f8fafc'}; color:${plantillaId ? '#0369a1' : '#64748b'}; border:1px solid ${plantillaId ? '#bae6fd' : '#cbd5e1'}; font-size:11px; padding:5px 8px; border-radius:6px; max-width:190px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block;" title="${escapeHtml(etiquetaPlantilla)}">
                        <i class="fa-solid fa-file-waveform me-1"></i>${escapeHtml(codigoPlantilla ? codigoPlantilla : etiquetaPlantilla)}
                    </span>
                </div>
            </td>
            <td style="padding:6px 8px; border-bottom:1px solid #e2e8f0; text-align:center; vertical-align:middle;">
                ${bloqueado ? 
                    '<span style="background:#f1f5f9; color:#64748b; font-size:10px; font-weight:700; padding:3px 6px; border-radius:4px;">Bloqueado</span>' 
                    : 
                    '<button type="button" class="btn-cycsa btn-cycsa-danger" style="padding:5px 8px; font-size:11px;" onclick="eliminarFilaMuestraModal(this)" title="Eliminar muestra"><i class="fa-solid fa-trash"></i></button>'
                }
            </td>
        `;
        tbody.appendChild(tr);
    }

    function eliminarFilaMuestraModal(btn) {
        const tr = btn.closest('tr');
        if (tr) tr.remove();
        siguienteConsecutivoMuestra = recalcularConsecutivoMuestra();
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

    function poblarSelectorPlantillaGlobal(ensayos, clienteId) {
        const hiddenInput = document.getElementById('select_plantilla_global');
        const listaCont = document.getElementById('lista_resultados_plantillas_ajax');
        if (!hiddenInput) return;

        if (listaCont) {
            listaCont.innerHTML = '<div style="padding:15px; text-align:center; color:#64748b; font-size:12px;"><i class="fa-solid fa-spinner fa-spin me-2 text-primary"></i>Cargando plantillas aprobadas...</div>';
        }

        const cid = clienteId || window.clienteIdActualHoja || '';
        const primerEnsayoId = (ensayos && ensayos.length > 0) ? ensayos[0].id : 0;

        fetch(`/Cycsa/publico/plantillas/disponibles?ensayo_id=${primerEnsayoId}&cliente_id=${cid}`)
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
                    if (!hiddenInput.value && res.plantilla_sugerida) {
                        seleccionarPlantillaGlobal(res.plantilla_sugerida.id, res.plantilla_sugerida.codigo, res.plantilla_sugerida.nombre, res.plantilla_sugerida.norma, false);
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
                        alert(`Validación ISO 17025:\n\nLa muestra "${nombreFaltante}" no tiene asignada una plantilla de ensayo aprobada.\nNo se puede admitir la recepción física sin plantilla vinculada.`);
                        return false;
                    }
                }
            });
        }
    });

    function cerrarModalHojaSolicitud() {
        modHS.style.display = 'none';
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
        <?php endif; ?>
    });
    <?php endif; ?>
</script>
