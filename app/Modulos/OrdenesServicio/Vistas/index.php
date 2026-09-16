<?php
// Vista principal - Listado Unificado de Órdenes de Servicio & Hojas de Recepción (CYCSA ERP Premium Style)
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-dark: #0c2766;
        --cycsa-azul-light: #eff6ff;
    }

    .tabla-cycsa { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 15px; font-size: 13.5px; background: white; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 14px 16px; text-align: left; font-weight: 700; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #1e293b; }
    
    .fila-principal { cursor: pointer; transition: background-color 0.2s; }
    .fila-principal:hover { background-color: #f8fafc; }
    .fila-principal.expandida { background-color: #f0f7ff !important; }
    
    .badge-premium { padding: 5px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
    .badge-muestreo-no { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .badge-muestreo-si { background-color: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }
    .badge-muestreo-fin { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    
    .badge-hoja-sin { background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
    .badge-hoja-borrador { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-hoja-revision { background-color: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }
    .badge-hoja-observada { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .badge-hoja-aprobada { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }

    .btn-cycsa { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid transparent; padding: 7px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: var(--cycsa-azul-dark); color: white; transform: translateY(-1px); }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }
    .btn-cycsa-warning { background: #f59e0b; color: white; }
    .btn-cycsa-warning:hover { background: #d97706; color: white; }
    .btn-cycsa-success { background: #10b981; color: white; }
    .btn-cycsa-success:hover { background: #059669; color: white; }
    .btn-cycsa-danger { background: #ef4444; color: white; }
    .btn-cycsa-danger:hover { background: #dc2626; color: white; }

    .search-bar-premium { display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap; }
    .form-control-cycsa { padding: 9px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 13.5px; transition: border-color 0.2s; width: 100%; box-sizing: border-box; }
    .form-control-cycsa:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }

    /* FILA DESPLEGABLE CON ACORDEÓN */
    .fila-desplegable { display: none; background-color: #f8fafc; }
    .fila-desplegable.mostrar { display: table-row; }
    .acordeon-contenido { padding: 20px 25px; border-bottom: 2px solid #cbd5e1; }
    
    .grid-info-os { display: grid; grid-template-columns: 1.1fr 1.1fr 1.1fr; gap: 20px; margin-bottom: 20px; }
    @media (max-width: 1024px) {
        .grid-info-os { grid-template-columns: 1fr; }
    }

    .tarjeta-detalle-os { background: white; border-radius: 10px; padding: 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
    .tarjeta-detalle-titulo { font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 700; color: var(--cycsa-azul); border-bottom: 1.5px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; }

    .dato-fila { display: flex; font-size: 12.5px; margin-bottom: 7px; color: #334155; line-height: 1.4; }
    .dato-etiqueta { font-weight: 700; color: #64748b; min-width: 110px; }
    .dato-valor { flex: 1; color: #0f172a; }

    .barra-acciones-os { display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap; background: white; padding: 14px 18px; border-radius: 8px; border: 1px solid #e2e8f0; }

    .chevron-icon { transition: transform 0.25s ease; color: #94a3b8; font-size: 13px; }
    .fila-principal.expandida .chevron-icon { transform: rotate(90deg); color: var(--cycsa-azul); }

    /* ESTILOS DE MODAL Y FORMULARIO */
    .modal-premium { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
    .modal-premium-content { width: 95%; max-width: 1350px; max-height: 90vh; overflow-y: auto; padding: 25px 30px; background: white; margin: 2% auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
</style>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); margin-bottom: 30px;">
    
    <!-- CABECERA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 22px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-file-contract" style="color: var(--cycsa-azul);"></i> Órdenes de Servicio & Hojas de Recepción
            </h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 13.5px; margin-bottom: 0;">
                Gestión integral del formato <strong>CYCSA-RG-FM-39 V1</strong>, logística de muestreo y registro de especímenes <strong>CYCSA-RT-FM-13</strong>.
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
        <div style="flex: 1; min-width: 250px;">
            <input type="text" name="q" placeholder="Buscar por código O/S, cotización, cliente o proyecto..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control-cycsa">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-cycsa btn-cycsa-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            <?php if(!empty($busqueda)): ?>
                <a href="/Cycsa/publico/ordenes-servicio" class="btn-cycsa btn-cycsa-secondary"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- TABLA DE REGISTROS CON DESPLIEGUE INTELIGENTE -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"></th>
                    <th style="width: 170px;">Código O/S</th>
                    <th style="width: 130px;">Cotización</th>
                    <th>Cliente / Proyecto</th>
                    <th style="width: 110px;">Emisión</th>
                    <th style="width: 180px;">Muestreo / Visita</th>
                    <th style="width: 160px;">Hoja RT-FM-13</th>
                    <th style="text-align: right; width: 210px;">Acciones Rápidas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ordenes)): ?>
                    <?php foreach ($ordenes as $os): ?>
                        <?php
                        // Determinar estado de muestreo para Casos A, B y C
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

                        // Estado de la Hoja RT-FM-13
                        $tieneHoja = !empty($os['id_hoja']);
                        $estadoOS = $os['estado'];
                        ?>
                        <!-- FILA PRINCIPAL -->
                        <tr class="fila-principal" id="fila-os-<?= $os['id'] ?>" onclick="toggleFilaDesplegable(<?= $os['id'] ?>, event)">
                            <td style="text-align: center;">
                                <i class="fa-solid fa-chevron-right chevron-icon" id="icon-chevron-<?= $os['id'] ?>"></i>
                            </td>
                            <td>
                                <strong style="font-family: monospace; font-size: 13.5px; color: var(--cycsa-azul);">
                                    <?= htmlspecialchars($os['codigo_os']) ?>
                                </strong>
                            </td>
                            <td>
                                <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 11.5px; border: 1px solid #e2e8f0; font-family: monospace;">
                                    <?= htmlspecialchars($os['cotizacion_codigo'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($os['cliente_nombre']) ?></div>
                                <div style="color: #64748b; font-size: 12px; margin-top: 2px;"><?= htmlspecialchars($os['nombre_proyecto'] ?? 'Sin nombre de proyecto') ?></div>
                            </td>
                            <td style="color: #475569; font-weight: 500; font-size: 12.5px;">
                                <?= date('d/m/Y', strtotime($os['fecha_emision'])) ?>
                            </td>
                            <td>
                                <?php if ($estadoMuestreo === 'en_proceso'): ?>
                                    <span class="badge-premium badge-muestreo-si">
                                        <i class="fa-solid fa-truck-pickup"></i> En Campo
                                    </span>
                                    <?php if (!empty($os['tecnico_nombre'])): ?>
                                        <div style="color: #64748b; font-size: 11px; margin-top: 3px; font-weight: 500;">Téc: <?= htmlspecialchars($os['tecnico_nombre']) ?></div>
                                    <?php endif; ?>
                                <?php elseif ($estadoMuestreo === 'finalizado' && !empty($os['id_pm'])): ?>
                                    <span class="badge-premium badge-muestreo-fin">
                                        <i class="fa-solid fa-circle-check"></i> Muestreo Finalizado
                                    </span>
                                    <?php if (!empty($os['tecnico_nombre'])): ?>
                                        <div style="color: #166534; font-size: 11px; margin-top: 3px; font-weight: 600;">
                                            <i class="fa-solid fa-person-walking-arrow-right"></i> Retornó: <?= htmlspecialchars($os['tecnico_nombre']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($estadoMuestreo === 'pendiente_programar'): ?>
                                    <span class="badge-premium badge-hoja-borrador">
                                        <i class="fa-solid fa-truck-pickup"></i> Requiere Muestreo
                                    </span>
                                <?php elseif ($estadoMuestreo === 'no_aplica'): ?>
                                    <span class="badge-premium badge-muestreo-no">
                                        <i class="fa-solid fa-flask"></i> Ingreso Directo
                                    </span>
                                <?php else: ?>
                                    <span class="badge-premium" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                                        <i class="fa-solid fa-circle-question"></i> Por Definir
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$tieneHoja): ?>
                                    <span class="badge-premium badge-hoja-sin">
                                        <i class="fa-solid fa-file-circle-plus"></i> Sin Registrar
                                    </span>
                                <?php else: ?>
                                    <?php if ($estadoOS === 'Estado 1: Recepcion'): ?>
                                        <span class="badge-premium badge-hoja-borrador">
                                            <i class="fa-solid fa-pen-to-square"></i> Borrador
                                        </span>
                                    <?php elseif ($estadoOS === 'Estado 2: Revision'): ?>
                                        <span class="badge-premium badge-hoja-revision">
                                            <i class="fa-solid fa-hourglass-half"></i> En Revisión
                                        </span>
                                    <?php elseif ($estadoOS === 'Estado 2: Observada'): ?>
                                        <span class="badge-premium badge-hoja-observada">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Observada
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-premium badge-hoja-aprobada">
                                            <i class="fa-solid fa-circle-check"></i> Aprobada
                                        </span>
                                    <?php endif; ?>
                                    <div style="font-family: monospace; font-size: 10.5px; color: #64748b; margin-top: 2px;">
                                        <?= htmlspecialchars($os['hoja_codigo'] ?? '') ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" onclick="event.stopPropagation();">
                                <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                    <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-secondary" title="Ver Documento CYCSA-RG-FM-39 V1">
                                        <i class="fa-solid fa-print" style="color: var(--cycsa-azul);"></i> O/S
                                    </a>

                                    <?php if (!$tieneHoja): ?>
                                        <button type="button" class="btn-cycsa btn-cycsa-success"
                                                data-id-os="<?= $os['id'] ?>"
                                                data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                onclick="iniciarRegistroHojaRTFM13(this)">
                                            <i class="fa-solid fa-file-signature"></i> Registrar Hoja
                                        </button>
                                    <?php elseif ($estadoOS === 'Estado 1: Recepcion'): ?>
                                        <button type="button" class="btn-cycsa btn-cycsa-success"
                                                data-id-os="<?= $os['id'] ?>"
                                                data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                onclick="iniciarRegistroHojaRTFM13(this)">
                                            <i class="fa-solid fa-pen-to-square"></i> Editar RT-FM-13
                                        </button>
                                    <?php elseif ($estadoOS === 'Estado 2: Observada'): ?>
                                        <button type="button" class="btn-cycsa btn-cycsa-warning"
                                                data-id-os="<?= $os['id'] ?>"
                                                data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                onclick="iniciarRegistroHojaRTFM13(this)">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Corregir RT-FM-13
                                        </button>
                                    <?php elseif ($estadoOS === 'Estado 2: Revision'): ?>
                                        <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-primary" style="background:#059669;" onclick="abrirModalRevision(<?= $os['id'] ?>, '<?= $os['codigo_os'] ?>')">
                                                <i class="fa-solid fa-check-double"></i> Revisar
                                            </button>
                                        <?php else: ?>
                                            <span class="btn-cycsa btn-cycsa-secondary" style="opacity:0.75; cursor:not-allowed;" title="En revisión por supervisor - No editable">
                                                <i class="fa-solid fa-hourglass-half"></i> En Revisión
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-primary" title="Ver PDF Oficial RT-FM-13">
                                            <i class="fa-solid fa-file-pdf"></i> Ver PDF
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- FILA DESPLEGABLE CON DETALLES COMPLETOS (ACORDEÓN) -->
                        <tr class="fila-desplegable" id="desplegable-os-<?= $os['id'] ?>">
                            <td colspan="8" style="padding: 0;">
                                <div class="acordeon-contenido">
                                    <div class="grid-info-os">
                                        
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
                                                <span class="dato-valor"><a href="/Cycsa/publico/cotizaciones" style="color:var(--cycsa-azul); font-weight:700; text-decoration:none; font-family:monospace;"><?= htmlspecialchars($os['cotizacion_codigo'] ?? '') ?></a></span>
                                            </div>
                                            <div class="dato-fila">
                                                <span class="dato-etiqueta">Forma Pago:</span>
                                                <span class="dato-valor"><?= htmlspecialchars($os['forma_pago'] ?? 'Pago contra entrega') ?></span>
                                            </div>
                                        </div>

                                        <!-- TARJETA 2: LOGÍSTICA DE MUESTREO EN CAMPO -->
                                        <div class="tarjeta-detalle-os">
                                            <div class="tarjeta-detalle-titulo">
                                                <i class="fa-solid fa-truck-pickup"></i> 2. Logística de Muestreo / Visita
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
                                                    <div style="font-size: 11.5px; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 6px; color: #475569;">
                                                        <strong>Notas de Campo:</strong> <?= htmlspecialchars($os['observaciones_campo']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($os['estado_muestreo'] === 'Finalizado'): ?>
                                                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px 12px; border-radius: 8px; color: #166534; font-size: 12.5px; margin-top: 10px; display: flex; align-items: center; gap: 8px;">
                                                        <i class="fa-solid fa-circle-check" style="color: #10b981; font-size: 16px;"></i>
                                                        <div>
                                                            <strong>Muestreo finalizado:</strong> El técnico retornó con las muestras al laboratorio y se encuentra listo para procesar la Hoja RT-FM-13.
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="margin-top: 10px;">
                                                        <a href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-secondary" style="padding: 5px 12px; font-size: 12px;">
                                                            <i class="fa-solid fa-pen-to-square"></i> Ver / Finalizar Muestreo
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($os['requiere_muestreo'] === 1 || $os['requiere_muestreo'] === '1'): ?>
                                                <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px; border-radius: 8px; color: #1e40af; font-size: 12.5px;">
                                                    <i class="fa-solid fa-calendar-plus"></i> <strong>Requiere salida a campo:</strong> Pendiente de asignar técnico, vehículo y fechas de salida.
                                                    <div style="margin-top: 8px;">
                                                        <a href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-primary" style="padding: 5px 12px; font-size: 12px;">
                                                            <i class="fa-solid fa-calendar-days"></i> Programar Muestreo en Campo
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php elseif ($os['requiere_muestreo'] === 0 || $os['requiere_muestreo'] === '0'): ?>
                                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 8px; color: #166534; font-size: 12.5px;">
                                                    <i class="fa-solid fa-flask"></i> <strong>Ingreso Directo:</strong> Los especímenes son entregados por el cliente en ventanilla del laboratorio (no requiere salida técnica a campo).
                                                </div>
                                            <?php else: ?>
                                                <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 12px; border-radius: 8px; color: #92400e; font-size: 12.5px;">
                                                    <i class="fa-solid fa-circle-question"></i> <strong>Por Definir:</strong> Aún no se ha especificado si requiere muestreo en campo o ingreso directo en ventanilla.
                                                    <div style="margin-top: 8px;">
                                                        <button type="button" class="btn-cycsa btn-cycsa-primary" style="padding: 5px 12px; font-size: 12px;" onclick="abrirModalDecisionMuestreo(<?= $os['id'] ?>, '<?= htmlspecialchars($os['codigo_os']) ?>')">
                                                            <i class="fa-solid fa-hand-pointer"></i> Definir Tipo de Ingreso
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- TARJETA 3: HOJA TÉCNICA CYCSA-RT-FM-13 -->
                                        <div class="tarjeta-detalle-os">
                                            <div class="tarjeta-detalle-titulo">
                                                <i class="fa-solid fa-file-signature"></i> 3. Hoja de Servicio (RT-FM-13)
                                            </div>
                                            <?php if ($tieneHoja): ?>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Código Doc:</span>
                                                    <span class="dato-valor"><strong style="color:var(--cycsa-azul); font-family:monospace;"><?= htmlspecialchars($os['hoja_codigo']) ?></strong></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Estado Hoja:</span>
                                                    <span class="dato-valor"><strong><?= htmlspecialchars($os['estado']) ?></strong></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Tomada por:</span>
                                                    <span class="dato-valor"><?= htmlspecialchars($os['nombre_persona_toma_muestra'] ?? 'Cliente') ?></span>
                                                </div>
                                                <div class="dato-fila">
                                                    <span class="dato-etiqueta">Fecha Toma:</span>
                                                    <span class="dato-valor"><?= !empty($os['fecha_hora_toma_muestra']) ? date('d/m/Y H:i', strtotime($os['fecha_hora_toma_muestra'])) : 'N/A' ?></span>
                                                </div>
                                                <?php if ($os['estado'] === 'Estado 2: Observada' && !empty($os['motivo_observacion'])): ?>
                                                    <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 8px 12px; border-radius: 6px; color: #991b1b; font-size: 11.5px; margin-top: 6px;">
                                                        <strong><i class="fa-solid fa-triangle-exclamation"></i> Observación del Supervisor:</strong> <?= htmlspecialchars($os['motivo_observacion']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 15px; border-radius: 8px; text-align: center; color: #64748b; font-size: 12.5px;">
                                                    <i class="fa-solid fa-file-circle-question" style="font-size: 24px; margin-bottom: 6px; color: #94a3b8; display: block;"></i>
                                                    Aún no se ha registrado la Hoja de Servicio técnica para esta orden.
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                    </div>

                                    <!-- BARRA DE ACCIONES OPERATIVAS -->
                                    <div class="barra-acciones-os">
                                        <div style="margin-right: auto; font-size: 12.5px; color: #64748b;">
                                            <i class="fa-solid fa-circle-info" style="color:var(--cycsa-azul);"></i> Estado técnico: <strong><?= htmlspecialchars($os['estado']) ?></strong>
                                        </div>

                                        <?php if (!$tieneHoja): ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-success"
                                                    data-id-os="<?= $os['id'] ?>"
                                                    data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                    data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                    data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                    data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                    data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                    onclick="iniciarRegistroHojaRTFM13(this)">
                                                <i class="fa-solid fa-file-signature"></i> Registrar Hoja RT-FM-13
                                            </button>
                                        <?php elseif ($os['estado'] === 'Estado 1: Recepcion'): ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-success"
                                                    data-id-os="<?= $os['id'] ?>"
                                                    data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                    data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                    data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                    data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                    data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                    onclick="iniciarRegistroHojaRTFM13(this)">
                                                <i class="fa-solid fa-pen-to-square"></i> Editar Hoja RT-FM-13
                                            </button>
                                            <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary">
                                                <i class="fa-solid fa-file-pdf"></i> Ver PDF RT-FM-13
                                            </a>
                                            <form method="POST" action="/Cycsa/publico/hojas-servicio/enviar-revision" style="margin: 0;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="id_os" value="<?= $os['id'] ?>">
                                                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#4f46e5;">
                                                    <i class="fa-solid fa-paper-plane"></i> Enviar a Revisión Supervisor
                                                </button>
                                            </form>
                                        <?php elseif ($os['estado'] === 'Estado 2: Observada'): ?>
                                            <button type="button" class="btn-cycsa btn-cycsa-warning"
                                                    data-id-os="<?= $os['id'] ?>"
                                                    data-codigo-os="<?= htmlspecialchars($os['codigo_os']) ?>"
                                                    data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                    data-tecnico="<?= htmlspecialchars($os['tecnico_nombre'] ?? '') ?>"
                                                    data-fecha-ida="<?= !empty($os['fecha_ida']) ? date('d/m/Y H:i', strtotime($os['fecha_ida'])) : '' ?>"
                                                    data-fecha-llegada="<?= !empty($os['fecha_llegada']) ? date('d/m/Y H:i', strtotime($os['fecha_llegada'])) : '' ?>"
                                                    onclick="iniciarRegistroHojaRTFM13(this)">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Corregir Hoja RT-FM-13
                                            </button>
                                            <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary">
                                                <i class="fa-solid fa-file-pdf"></i> Ver PDF RT-FM-13
                                            </a>
                                            <form method="POST" action="/Cycsa/publico/hojas-servicio/enviar-revision" style="margin: 0;">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="id_os" value="<?= $os['id'] ?>">
                                                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#4f46e5;">
                                                    <i class="fa-solid fa-paper-plane"></i> Reenviar a Revisión Supervisor
                                                </button>
                                            </form>
                                        <?php elseif ($os['estado'] === 'Estado 2: Revision'): ?>
                                            <span style="font-size:12.5px; color:#0369a1; background:#e0f2fe; padding:7px 14px; border-radius:6px; border:1px solid #bae6fd; display:inline-flex; align-items:center; gap:6px;">
                                                <i class="fa-solid fa-lock"></i> En revisión por supervisión (No editable)
                                            </span>
                                            <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary">
                                                <i class="fa-solid fa-file-pdf"></i> Ver PDF RT-FM-13
                                            </a>
                                            <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                                <button type="button" class="btn-cycsa btn-cycsa-primary" style="background:#059669;" onclick="abrirModalRevision(<?= $os['id'] ?>, '<?= $os['codigo_os'] ?>')">
                                                    <i class="fa-solid fa-check-double"></i> Decisión Supervisor (Aprobar / Observar)
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="font-size:12.5px; color:#15803d; background:#dcfce7; padding:7px 14px; border-radius:6px; border:1px solid #86efac; display:inline-flex; align-items:center; gap:6px;">
                                                <i class="fa-solid fa-circle-check"></i> Hoja Aprobada
                                            </span>
                                            <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-primary">
                                                <i class="fa-solid fa-file-pdf"></i> Ver PDF RT-FM-13 Oficial
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 40px;">
                            <i class="fa-solid fa-folder-open" style="font-size: 32px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            No se encontraron Órdenes de Servicio registradas.
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
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
            <div>
                <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-file-signature" style="color:var(--cycsa-azul);"></i> Hoja de Solicitud de Servicio (Ingreso CYCSA-RT-FM-13)
                </h3>
                <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b;">
                    Orden de Servicio Vinculada: <strong id="hs_codigo_os_label" style="color:var(--cycsa-azul); font-family:monospace; font-size:14px;"></strong>
                </p>
            </div>
            <button onclick="cerrarModalHojaSolicitud()" class="btn-cerrar" style="font-size:26px; border:none; background:none; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        
        <div id="loading-hoja-solicitud" style="text-align:center; padding: 50px; display:none;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:36px; color:var(--cycsa-azul);"></i>
            <p style="color:#64748b; margin-top:12px; font-size:14px; font-weight:600;">Cargando datos de la O/S y plantilla RT-FM-13...</p>
        </div>

        <div id="wrapper-split-rt-fm-13" style="display:none; grid-template-columns: 460px 1fr; gap: 25px; align-items: flex-start;">
            
            <!-- PANEL LATERAL IZQUIERDO: REFERENCIA VISUAL DE LA ORDEN DE SERVICIO (SOLO LECTURA) -->
            <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 18px; position: sticky; top: 0; max-height: 75vh; overflow-y: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div style="display:flex; align-items:center; justify-content:space-between; border-bottom: 1.5px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 12px;">
                    <span style="font-family:'Outfit'; font-weight:700; color:var(--cycsa-azul); font-size:13px; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-file-contract"></i> Referencia O/S (CYCSA-RG-FM-39)
                    </span>
                    <span id="ref_os_badge_estado" style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; text-transform:uppercase;">Solo Lectura</span>
                </div>

                <div style="font-size: 12.5px; color: #334155; display: flex; flex-direction: column; gap: 8px;">
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
                    <strong style="font-size:11.5px; color:#0f172a; text-transform:uppercase; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                        <i class="fa-solid fa-vials" style="color:var(--cycsa-azul);"></i> Ensayos Solicitados (O/S)
                    </strong>
                    <div style="overflow-x:auto; border:1px solid #cbd5e1; border-radius:6px; background:white;">
                        <table style="width:100%; border-collapse:collapse; font-size:11px;">
                            <thead>
                                <tr style="background:#f1f5f9; font-size:10px; text-transform:uppercase; color:#475569;">
                                    <th style="padding:6px 4px; text-align:center; border-bottom:1px solid #cbd5e1; width:20px;">#</th>
                                    <th style="padding:6px; text-align:left; border-bottom:1px solid #cbd5e1; min-width:120px;">Descripción (Nombre comercial)</th>
                                    <th style="padding:6px; text-align:left; border-bottom:1px solid #cbd5e1; min-width:130px;">Condiciones de muestra</th>
                                    <th style="padding:6px; text-align:left; border-bottom:1px solid #cbd5e1; min-width:70px;">Procedimiento</th>
                                    <th style="padding:6px; text-align:center; border-bottom:1px solid #cbd5e1; width:35px;">U/M</th>
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
            <form method="POST" action="/Cycsa/publico/hojas-servicio/guardar" id="form-hoja-solicitud" style="display:block;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_os" id="hs_id_os">

                <!-- 1. METADATOS Y CONTROL INTERNO -->
                <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-clipboard-check"></i> A rellenar por el laboratorio (Control Interno)</div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">No. Registro Laboratorio</label>
                        <input type="text" name="numero_registro" id="hs_numero_registro" class="form-control-cycsa" style="font-size:13px; font-weight:700;" placeholder="Ej: 23896">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Fecha/Hora de Llegada al Lab</label>
                        <input type="datetime-local" name="fecha_hora_llegada_laboratorio" id="hs_fecha_llegada" required class="form-control-cycsa" style="font-size:13px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Código del Documento</label>
                        <input type="text" name="codigo_documento" id="hs_codigo_documento" readonly class="form-control-cycsa" style="background:#f1f5f9; font-weight:700; font-size:13px;" value="CYCSA-RT-FM-13">
                    </div>
                </div>

                <!-- 2. DATOS DEL CLIENTE -->
                <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-user-tie"></i> 1. Empresa o Cliente que Solicita el Servicio</div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom: 12px;">
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Nombre Empresa/Cliente</label>
                        <input type="text" name="nombre_empresa_o_cliente" id="hs_nombre_empresa" required class="form-control-cycsa">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Razón Social</label>
                        <input type="text" name="razon_social" id="hs_razon_social" class="form-control-cycsa" placeholder="Ej: CYCSA S.A. / Persona Natural">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Dirección Proyecto/Obra</label>
                        <input type="text" name="direccion_proyecto" id="hs_direccion" required class="form-control-cycsa">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Teléfono</label>
                        <input type="text" name="telefono" id="hs_telefono" required class="form-control-cycsa">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Correo Electrónico</label>
                        <input type="email" name="correo_electronico" id="hs_email" required class="form-control-cycsa">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Nombre de la persona quien trae la muestra</label>
                        <input type="text" name="nombre_persona_entrega_muestra" id="hs_persona_entrega" required class="form-control-cycsa">
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
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:2px;">Procedencia/ Punto de muestreo:</label>
                        <small style="display:block; color:#64748b; font-size:10.5px; margin-bottom:4px;">Describir la ubicación del punto donde se tomó la muestra así como la comarca.</small>
                        <input type="text" name="procedencia_punto_muestreo" id="hs_procedencia" required class="form-control-cycsa">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">Persona quien tomó la muestra:</label>
                        <input type="text" name="nombre_persona_toma_muestra" id="hs_persona_toma" list="lista_tecnicos_hs_os" required class="form-control-cycsa" placeholder="Nombre de quien tomó la muestra (Ej: Juan / Cliente)">
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
                    <div class="form-group">
                        <label style="font-size:12px; font-weight:700; color:#475569; display:block; margin-bottom:4px;">1.2 Fecha y hora en que se tomó la muestra:</label>
                        <input type="datetime-local" name="fecha_hora_toma_muestra" id="hs_fecha_toma" required class="form-control-cycsa">
                    </div>
                </div>

                <!-- 4. IDENTIFICACIONES PROPIAS (TABLA DINÁMICA) -->
                <div style="display:flex; justify-content:space-between; align-items:center; font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;">
                    <span><i class="fa-solid fa-list-ol"></i> 2. Identificaciones Propias de la Muestra</span>
                    <button type="button" class="btn-cycsa btn-cycsa-primary" style="padding:6px 14px; font-size:12.5px;" onclick="agregarFilaMuestraModal()">
                        <i class="fa-solid fa-plus"></i> Agregar Muestra
                    </button>
                </div>
                <table style="width:100%; border-collapse:collapse; margin-bottom:20px; border:1px solid #e2e8f0;" id="hs-tabla-muestras">
                    <thead>
                        <tr style="background:#f8fafc; font-size:11.5px; color:#475569; text-transform:uppercase;">
                            <th style="width:30%; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left;">Nombre de la muestra</th>
                            <th style="width:30%; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left;">Descripción</th>
                            <th style="width:30%; padding:8px; border-bottom:1px solid #e2e8f0; text-align:left;">Informaciones importantes</th>
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

                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:10px 12px; margin-bottom:20px; font-size:11.5px; color:#1e40af; line-height:1.4;">
                    <strong>*.- Con este documento doy fe</strong>, que todo lo escrito lo he revisado y que he quedado de mutuo acuerdo de los servicios que me ofrecerá CYCSA en la muestra. Cualquier cambio deberá ser notificado previo a CYCSA de cualquier forma escrita para su debido registro.
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; border-top:1.5px solid #e2e8f0; padding-top:18px;">
                    <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="cerrarModalHojaSolicitud()">Cancelar</button>
                    <button type="submit" class="btn-cycsa btn-cycsa-success" style="padding:10px 24px; font-size:14px;">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Hoja de Solicitud (RT-FM-13)
                    </button>
                </div>
            </form>
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

<!-- MODAL ELECCIÓN DE MUESTREO VS INGRESO DIRECTO -->
<div id="modalDecisionMuestreoGlobal" class="modal-premium" style="display:none; z-index:10000; align-items:center; justify-content:center;">
    <div class="modal-premium-content" style="max-width: 650px; border-radius: 12px; padding: 25px 30px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); background:white;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
            <div style="font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: #0f172a; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-circle-question" style="color: var(--cycsa-azul);"></i> ¿Se Requiere Muestreo en Campo?
            </div>
            <button type="button" onclick="cerrarModalDecisionMuestreo()" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 15px; margin-bottom:20px;">
            <span style="font-size:11px; text-transform:uppercase; color:#64748b; font-weight:700; display:block;">Orden de Servicio:</span>
            <div id="decision_codigo_os" style="font-size:16px; font-weight:700; color:var(--cycsa-azul); font-family:monospace; margin-top:2px;">OS-2026-XXXX</div>
        </div>

        <p style="font-size: 13.5px; color: #475569; margin-bottom: 20px; line-height: 1.5;">
            Seleccione el flujo correspondiente para la recepción de especímenes de esta Orden de Servicio:
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            
            <!-- OPCIÓN 1: SÍ REQUIERE MUESTREO (GIRA DE CAMPO) -->
            <div style="border: 2px solid #cbd5e1; border-radius: 10px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; background: white; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--cycsa-azul)'; this.style.boxShadow='0 4px 12px rgba(16,52,135,0.08)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                <div>
                    <div style="width: 44px; height: 44px; border-radius: 8px; background: #eff6ff; color: var(--cycsa-azul); display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px;">
                        <i class="fa-solid fa-truck-pickup"></i>
                    </div>
                    <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: #0f172a;">Sí, Muestreo en Campo</h4>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.4; margin: 0 0 15px 0;">
                        Asignar técnico muestreador, vehículo, fecha de salida y llenar la <strong>Lista de Chequeo Oficial CYCSA-RT-FM-40 B</strong>.
                    </p>
                </div>
                <button type="button" class="btn-cycsa btn-cycsa-primary" style="width: 100%; padding: 10px; font-size: 13px;" onclick="confirmarDecisionMuestreo(true)">
                    <i class="fa-solid fa-calendar-plus"></i> Programar Muestreo
                </button>
            </div>

            <!-- OPCIÓN 2: NO REQUIERE MUESTREO (INGRESO DIRECTO AL LAB) -->
            <div style="border: 2px solid #cbd5e1; border-radius: 10px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; background: white; transition: all 0.2s;" onmouseover="this.style.borderColor='#10b981'; this.style.boxShadow='0 4px 12px rgba(16,185,129,0.08)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                <div>
                    <div style="width: 44px; height: 44px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px;">
                        <i class="fa-solid fa-flask"></i>
                    </div>
                    <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: #0f172a;">No, Ingreso Directo</h4>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.4; margin: 0 0 15px 0;">
                        Los especímenes fueron entregados directamente por el cliente en el Laboratorio Central (sin salida a campo).
                    </p>
                </div>
                <button type="button" class="btn-cycsa btn-cycsa-success" style="width: 100%; padding: 10px; font-size: 13px;" onclick="confirmarDecisionMuestreo(false)">
                    <i class="fa-solid fa-file-signature"></i> Abrir Hoja RT-FM-13
                </button>
            </div>

        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalDecisionMuestreo()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Cancelar
            </button>
        </div>
    </div>
</div>

<script>
    // Variables de control para el modal de decisión
    let decisionOSId = null;
    let decisionOSCodigo = '';

    function cerrarModalDecisionMuestreo() {
        const m = document.getElementById('modalDecisionMuestreoGlobal');
        if (m) m.style.display = 'none';
    }

    function abrirModalDecisionMuestreo(idOS, codigoOS) {
        decisionOSId = idOS;
        decisionOSCodigo = codigoOS;
        const lbl = document.getElementById('decision_codigo_os');
        if (lbl) lbl.innerText = codigoOS;
        const m = document.getElementById('modalDecisionMuestreoGlobal');
        if (m) m.style.display = 'flex';
    }

    function confirmarDecisionMuestreo(requiereMuestreo) {
        if (!decisionOSId) return;
        const idOS = decisionOSId;
        const codigoOS = decisionOSCodigo;
        cerrarModalDecisionMuestreo();

        if (requiereMuestreo) {
            // Redirige a Programar Muestreo en Campo
            window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
        } else {
            // Ingreso directo: Marcar en BD y abrir modal RT-FM-13
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

            abrirModalHojaSolicitud(idOS, codigoOS);
        }
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

        // CASO C: MUESTREO FINALIZADO O INGRESO DIRECTO CONFIRMADO
        if (estadoMuestreo === 'finalizado' || estadoMuestreo === 'no_aplica') {
            abrirModalHojaSolicitud(idOS, codigoOS);
            return;
        }

        // CASO A: NUEVA ORDEN (Abrir Modal Interactivo Garantizado)
        abrirModalDecisionMuestreo(idOS, codigoOS);
    }

    // =========================================================================
    // 3. APERTURA Y CONTROL DEL MODAL HOJA DE SOLICITUD (CYCSA-RT-FM-13)
    // =========================================================================
    const modHS = document.getElementById('modalHojaSolicitud');
    const hsLoading = document.getElementById('loading-hoja-solicitud');
    const hsSplit = document.getElementById('wrapper-split-rt-fm-13');

    function abrirModalHojaSolicitud(idOS, code) {
        document.getElementById('hs_codigo_os_label').innerText = code;
        modHS.style.display = 'block';
        hsLoading.style.display = 'block';
        hsSplit.style.display = 'none';

        fetch('/Cycsa/publico/hojas-servicio/datos?id_os=' + idOS)
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

    let prefijoMuestraActual = 'MC';
    let anioActual2Digitos = String(new Date().getFullYear()).slice(-2);

    function llenarModalHojaSolicitud(data) {
        const h = data.hoja;
        const os = data.os_referencia;
        prefijoMuestraActual = data.prefijo_muestra || 'MC';

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
        document.getElementById('hs_id_os').value = h.id_os;
        const elReg = document.getElementById('hs_numero_registro');
        if (elReg) elReg.value = h.numero_registro || '';
        document.getElementById('hs_fecha_llegada').value = h.fecha_hora_llegada_laboratorio ? h.fecha_hora_llegada_laboratorio.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('hs_codigo_documento').value = h.codigo_documento || 'CYCSA-RT-FM-13';
        document.getElementById('hs_nombre_empresa').value = h.nombre_empresa_o_cliente || '';
        document.getElementById('hs_razon_social').value = h.razon_social || '';
        document.getElementById('hs_direccion').value = h.direccion_proyecto || '';
        document.getElementById('hs_telefono').value = h.telefono || '';
        document.getElementById('hs_email').value = h.correo_electronico || '';
        document.getElementById('hs_persona_entrega').value = h.nombre_persona_entrega_muestra || '';
        document.getElementById('hs_procedencia').value = h.procedencia_punto_muestreo || '';
        document.getElementById('hs_persona_toma').value = h.nombre_persona_toma_muestra || '';
        document.getElementById('hs_fecha_toma').value = h.fecha_hora_toma_muestra ? h.fecha_hora_toma_muestra.replace(' ', 'T').substring(0, 16) : '';

        // Naturaleza Checkboxes
        const natureList = (h.naturaleza_muestra || '').split(',').map(s => s.trim());
        document.querySelectorAll('.hs-nat-checkbox').forEach(cb => {
            cb.checked = natureList.includes(cb.value);
        });

        // Especímenes / Muestras Dinámicas
        const tbodyM = document.getElementById('hs-tbody-muestras');
        tbodyM.innerHTML = '';
        let muestrasArr = [];
        try {
            if (h.identificacion_muestras_json) {
                muestrasArr = JSON.parse(h.identificacion_muestras_json);
            } else if (h.muestras_json) {
                muestrasArr = JSON.parse(h.muestras_json);
            }
        } catch(e) { muestrasArr = []; }

        if (Array.isArray(muestrasArr) && muestrasArr.length > 0) {
            muestrasArr.forEach((m) => {
                const nom = m.nombre_muestra || m.identificacion || '';
                const tipo = nom.startsWith('MS-') ? 'MS' : (nom.startsWith('MC-') ? 'MC' : prefijoMuestraActual);
                agregarFilaMuestraModal(tipo, nom, m.descripcion || m.ubicacion || '', m.info_importante || m.observaciones || '');
            });
        } else {
            // Pre-cargar automáticamente las N muestras estimadas en campo o sede con contexto del ensayo
            const cantSugerida = parseInt(data.cantidad_muestras_sugerida || os?.programacion_muestreo?.cantidad_muestras_est || 1) || 1;
            const puntoMuestreo = h.procedencia_punto_muestreo || data.lugar_muestreo || os?.nombre_proyecto || '';
            const primerEnsayo = (os?.ensayos && os.ensayos.length > 0) ? (os.ensayos[0].nombre_ensayo || os.ensayos[0].descripcion_ensayo || '') : '';
            const desc = primerEnsayo ? (primerEnsayo.length > 50 ? primerEnsayo.substring(0, 50) + '...' : primerEnsayo) : ((prefijoMuestraActual === 'MC') ? 'Muestra tomada en campo' : 'Muestra entregada en laboratorio');
            let infoPunto = puntoMuestreo ? `Punto: ${puntoMuestreo}` : '';
            if (os?.nombre_proyecto && (!puntoMuestreo || !puntoMuestreo.includes(os.nombre_proyecto))) {
                infoPunto += (infoPunto ? ' - ' : '') + `Proy: ${os.nombre_proyecto}`;
            }
            const info = infoPunto || ((prefijoMuestraActual === 'MC') ? 'Muestreo en Obra' : 'Recepción Lab Central');
            for (let i = 1; i <= cantSugerida; i++) {
                const consecutiveStr = String(i).padStart(4, '0');
                const nom = `${prefijoMuestraActual}-${consecutiveStr}-${anioActual2Digitos}`;
                agregarFilaMuestraModal(prefijoMuestraActual, nom, desc, info);
            }
        }

        // Checkboxes de Análisis
        document.getElementById('hs_req_concreto').checked = (parseInt(h.req_resistencia_concreto) === 1);
        document.getElementById('hs_req_adoquin').checked = (parseInt(h.req_resistencia_adoquin) === 1);
        document.getElementById('hs_req_bloques').checked = (parseInt(h.req_resistencia_bloques) === 1);
        document.getElementById('hs_req_otros_concreto').value = h.req_otros_concreto || '';
        document.getElementById('hs_req_granulometria').checked = (parseInt(h.req_granulometria) === 1);
        document.getElementById('hs_req_limites').checked = (parseInt(h.req_limites_atterberg) === 1);
        document.getElementById('hs_req_humedad').checked = (parseInt(h.req_humedad) === 1);
        const elCorte = document.getElementById('hs_req_resistencia_corte');
        if (elCorte) elCorte.checked = (parseInt(h.req_resistencia_corte) === 1);
        const elSucs = document.getElementById('hs_req_clasificacion_sucs_hr');
        if (elSucs) elSucs.checked = (parseInt(h.req_clasificacion_sucs_hr) === 1);
        document.getElementById('hs_req_proctor').checked = (parseInt(h.req_proctor_sm) === 1);
        const elInf = document.getElementById('hs_req_infiltracion');
        if (elInf) elInf.checked = (parseInt(h.req_infiltracion) === 1);
        document.getElementById('hs_req_cbr').checked = (parseInt(h.req_cbr) === 1);
        document.getElementById('hs_req_densidad').checked = (parseInt(h.req_densidad) === 1);
        const elOtSuelo = document.getElementById('hs_req_otros_suelo');
        if (elOtSuelo) elOtSuelo.value = h.req_otros_suelo || '';
        
        const elOtMat = document.getElementById('hs_req_otros_materiales');
        if (elOtMat) elOtMat.checked = (parseInt(h.req_otros_materiales) === 1);
        const elDescOt = document.getElementById('hs_descripcion_otros');
        if (elDescOt) elDescOt.value = h.descripcion_otros_analisis || '';
        
        const elAnAd = document.getElementById('hs_analisis_adicionales');
        if (elAnAd) elAnAd.value = h.analisis_adicionales || '';
        document.getElementById('hs_observaciones').value = h.observaciones || '';
        
        const elRecibe = document.getElementById('hs_nombre_recibe');
        if (elRecibe) elRecibe.value = h.nombre_recibe_cycsa || '';
        const elFirmaRecibe = document.getElementById('hs_firma_recibe_cycsa');
        if (elFirmaRecibe) elFirmaRecibe.checked = (parseInt(h.firma_recibe_cycsa) === 1);
        const elFirmaCli = document.getElementById('hs_firma_cliente');
        if (elFirmaCli) elFirmaCli.checked = (parseInt(h.firma_cliente) === 1);
    }


    function recalcularConsecutivoMuestra(prefix = prefijoMuestraActual) {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return 1;
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
        return max + 1;
    }

    function resecuenciarMuestras(prefix = prefijoMuestraActual) {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;
        let idx = 1;
        tbody.querySelectorAll('input[name="m_nombre[]"]').forEach(input => {
            const consecutiveStr = String(idx).padStart(4, '0');
            input.value = `${prefix}-${consecutiveStr}-${anioActual2Digitos}`;
            idx++;
        });
    }

    function agregarFilaMuestraModal(tipo = null, nom = '', desc = '', info = '') {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;

        const prefijo = tipo || prefijoMuestraActual || 'MC';

        if (nom === '') {
            const nextNum = recalcularConsecutivoMuestra(prefijo);
            const consecutiveStr = String(nextNum).padStart(4, '0');
            nom = `${prefijo}-${consecutiveStr}-${anioActual2Digitos}`;
            if (desc === '') {
                desc = (prefijo === 'MS') ? 'Muestra entregada en laboratorio' : 'Muestra tomada en campo';
            }
            if (info === '') {
                info = (prefijo === 'MS') ? 'Recepción Lab Central' : 'Muestreo en Obra';
            }
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding:6px; border-bottom:1px solid #e2e8f0;">
                <input type="text" name="m_nombre[]" value="${nom}" readonly required class="form-control-cycsa" style="padding:6px 10px; font-size:12px; font-weight:700; font-family:monospace; color:var(--cycsa-azul); background:#f8fafc; cursor:not-allowed; border-color:#cbd5e1;" title="Código consecutivo bloqueado por el sistema">
            </td>
            <td style="padding:6px; border-bottom:1px solid #e2e8f0;">
                <input type="text" name="m_desc[]" value="${desc}" class="form-control-cycsa" style="padding:6px 10px; font-size:12px;" required placeholder="Elemento / Ubicación">
            </td>
            <td style="padding:6px; border-bottom:1px solid #e2e8f0;">
                <input type="text" name="m_info[]" value="${info}" class="form-control-cycsa" style="padding:6px 10px; font-size:12px;" placeholder="Revenimiento / Edad">
            </td>
            <td style="padding:6px; border-bottom:1px solid #e2e8f0; text-align:center;">
                <button type="button" class="btn-cycsa btn-cycsa-danger" style="padding:4px 8px; font-size:11px;" onclick="this.closest('tr').remove(); resecuenciarMuestras();" title="Eliminar muestra"><i class="fa-solid fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
    }

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
        if (typeof abrirModalHojaSolicitud === 'function') {
            abrirModalHojaSolicitud(idAuto, <?= json_encode($codigoOSAuto) ?>);
        }
    });
    <?php endif; ?>
</script>
