<?php
$rutaSchema = dirname(__DIR__, 3) . '/database/ensayos/formatos_schema.json';
$formatosSchemaJson = file_exists($rutaSchema) ? file_get_contents($rutaSchema) : '{}';
$estCotDet = (string)($cotizacion['estado'] ?? 'Borrador');
$pillCotDetClase = match ($estCotDet) {
    'Aprobada por Cliente', 'Aprobada Internamente' => 'cy-pill-ok',
    'En Revision', 'Enviada al Cliente' => 'cy-pill-warn',
    'Observada', 'Rechazada por Cliente' => 'cy-pill-danger',
    default => 'cy-pill-pend',
};
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/cotizaciones-sobrio.css?v=20261127">
<style>
    .doc-container { max-width: 1250px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .doc-header { display: flex; justify-content: space-between; border-bottom: 2px solid var(--cycsa-azul); padding-bottom: 20px; margin-bottom: 20px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-box { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
    .info-label { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 600; margin-bottom: 5px; display: block; }
    .info-valor { font-size: 14px; color: #333; font-weight: 500; }
    
    .tabla-visual { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .tabla-visual th { background: #f1f5f9; padding: 12px; text-align: left; font-size: 13px; color: #475569; }
    .tabla-visual td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
    
    .panel-aprobacion { background: #fffbeb; border: 1px solid #fcd34d; padding: 20px; border-radius: 8px; margin-top: 30px; }
    .btn-aprobar { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
    .btn-observar { background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
    
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    
        @media (max-width: 768px) {
            .doc-container { padding: 15px !important; }
            .doc-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
        
        .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
        .modal-premium-content { background-color: #fff; margin: 10% auto; padding: 30px; border: 1px solid #e2e8f0; width: 420px; border-radius: 12px; text-align: left; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .btn-cerrar { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; }
        .btn-cerrar:hover { color: #475569; }
        .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
        .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
        .form-control:focus { border-color: var(--cycsa-azul); }
    </style>

<div class="container-fluid px-0 py-0 cy-cot-wrap">
<div class="doc-container">
    <a href="/Cycsa/publico/cotizaciones" class="cy-back-link"><i class="fa-solid fa-arrow-left"></i> Volver a Cotizaciones</a>

    <?php if (isset($_SESSION['envio_exitoso'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="status">
            <span class="cy-pill cy-pill-ok">Enviado</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars($_SESSION['envio_exitoso'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['envio_exitoso']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="alert">
            <span class="cy-pill cy-pill-danger">Aviso</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['exito'])): ?>
        <div class="cy-note mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2" role="status">
            <div class="d-flex align-items-center gap-2">
                <span class="cy-pill cy-pill-ok">Listo</span>
                <span style="color: var(--cy-text);"><?= htmlspecialchars($_SESSION['exito'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if (($cotizacion['estado'] ?? '') === 'Borrador' && tienePermiso('cotizaciones', 'crear_editar')): ?>
                <a href="/Cycsa/publico/cotizaciones/editar?id=<?= codificarId($cotizacion['id']) ?>" class="btn btn-outline-secondary btn-sm">
                    Modificar Datos Ahora
                </a>
            <?php endif; ?>
        </div>
        <?php unset($_SESSION['exito']); ?>
    <?php endif; ?>

    <div class="cy-cot-header">
        <div class="cy-cot-header-left">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h1 class="cy-page-title">Cotización <?= htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') ?></h1>
                <span class="cy-pill <?= $pillCotDetClase ?>"><?= htmlspecialchars($estCotDet, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="cy-meta">Cód. Doc CYCSA-RG-FM-31 V2R1 · Versión <?= max(1, (int)($cotizacion['version'] ?? 1)) ?> · Generada el <?= date('d/m/Y', strtotime($cotizacion['fecha_creacion'])) ?></div>
        </div>
        <div class="cy-cot-header-actions">
            <?php if (tienePermiso('cotizaciones', 'crear_editar')): ?>
                <a href="/Cycsa/publico/cotizaciones/duplicar?id=<?= codificarId($cotizacion['id']) ?>"
                   class="btn btn-outline-secondary btn-sm"
                   title="Duplicar esta cotización con un nuevo número consecutivo conservando todos los datos"
                   onclick="return confirm('¿Desea duplicar la cotización <?= htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') ?>?\n\nSe generará una nueva cotización con el siguiente número consecutivo conservando toda la información, ítems cotizados, notas y opciones seleccionadas.');">
                    Duplicar Cotización
                </a>
            <?php endif; ?>
            <a href="/Cycsa/publico/cotizaciones/imprimir?id=<?= codificarId($cotizacion['id']) ?>&t=<?= time() ?>" target="_blank" class="btn btn-primary btn-sm">
                Imprimir PDF
            </a>
        </div>
    </div>

    <?php if (!empty($cotizacion['responsable_interno_nombre']) || !empty($cotizacion['aprobacion_interna_por'])): ?>
        <div class="cy-note mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="cy-meta">Responsable Interno:</span>
                <strong><?= htmlspecialchars($cotizacion['responsable_interno_nombre'] ?: 'Gerencia / Autorizado', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span class="cy-meta">Aprobación Interna:</span>
                <?php if (!empty($cotizacion['aprobacion_interna_por'])): ?>
                    <span class="cy-pill cy-pill-ok"><?= htmlspecialchars($cotizacion['aprobacion_interna_nombre'] ?: ('Usuario #' . (int)$cotizacion['aprobacion_interna_por']), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($cotizacion['fecha_aprobacion_interna'])): ?>
                        <span class="cy-meta ms-1">(<?= date('d/m/Y H:i', strtotime($cotizacion['fecha_aprobacion_interna'])) ?>)</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="cy-pill cy-pill-pend">Pendiente</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($cotizacion['estado'] === 'Aprobada por Cliente'): ?>
        <div class="info-box" style="grid-column: span 2; background-color: #f8fafc; border: 1px solid #cbd5e1; margin-bottom: 24px; padding: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px;">
                <span class="info-label" style="font-size: 12px; font-weight: 700; color: var(--cycsa-azul); margin: 0; display: flex; align-items: center; gap: 8px; text-transform: uppercase;">
                    <i class="fa-solid fa-gears"></i> Estado del Servicio
                </span>
                <span class="badge" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 12px; padding: 3px 10px;">
                    <?= htmlspecialchars($cotizacion['estado_operativo'] ?? 'En Proceso O/S', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php if (!empty($cotizacion['notas_operativas'])): ?>
                <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 12px; border-radius: 6px; color: #b45309; font-size: 13px;">
                    <strong>Notas e Instrucciones de Operación:</strong>
                    <p style="margin-top: 4px; margin-bottom: 0; color: #78350f;"><?= nl2br(htmlspecialchars($cotizacion['notas_operativas'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- DOCUMENTO OFICIAL UNIFICADO CYCSA-RG-FM-31 V2R1 (IDÉNTICO AL PDF) -->
    <style>
        <?= \Cycsa\Modulos\Cotizaciones\Servicios\DocumentoCotizacionRgFm31Servicio::renderEstilos(false) ?>
        .rgfm31-screen-card {
            background: #ffffff;
            border: 1px solid var(--cy-border, #cbd5e1);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            overflow-x: auto;
        }
    </style>
    <div class="rgfm31-screen-card">
        <?= \Cycsa\Modulos\Cotizaciones\Servicios\DocumentoCotizacionRgFm31Servicio::renderCuerpo($cotizacion, $detalles, false) ?>
    </div>

    <?php
    $anexoRenderizado = \Cycsa\Modulos\Cotizaciones\Servicios\AnexoServicioMensualServicio::renderDesdeCotizacion($cotizacion, false);
    $incluirAnexo = ($anexoRenderizado !== '');
    $esAnexoMensualDet = \Cycsa\Modulos\Cotizaciones\Servicios\AnexoServicioMensualServicio::esAnexoMensualJson($cotizacion['anexo_tecnico'] ?? null)
        || (!empty($cotizacion['es_mensual']) && (int)$cotizacion['es_mensual'] === 1 && !empty($cotizacion['incluir_anexo_tecnico']));
    ?>

    <!-- DOCUMENTO DIGITAL ADJUNTO (SI EXISTE) -->
    <?php if (!empty($cotizacion['archivo_adjunto'])): ?>
        <div class="cy-next mb-4">
            <div>
                <div class="fw-semibold">Documento digital adjunto</div>
                <div class="cy-meta"><?= htmlspecialchars(basename($cotizacion['archivo_adjunto']), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <a href="/Cycsa/publico/<?= htmlspecialchars($cotizacion['archivo_adjunto'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                Descargar Adjunto
            </a>
        </div>
    <?php endif; ?>

    <!-- ANEXO TÉCNICO OFICIAL (SI ESTÁ INCLUIDO) -->
    <?php if ($incluirAnexo): ?>
        <div class="cy-cot-card">
            <div class="cy-cot-card-head">
                <span><i class="fa-solid fa-file-shield me-1"></i> <?= $esAnexoMensualDet ? 'Anexo Técnico – Alcance del Servicio Mensual' : 'Anexo Técnico Oficial Incluido' ?></span>
                <span class="cy-pill cy-pill-ok">Incluido en PDF</span>
            </div>
            <div class="cy-cot-card-body">
                <div style="font-size: 13.5px; color: var(--cy-text); line-height: 1.55;">
                    <?= $anexoRenderizado ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (tienePermiso('cotizaciones', 'crear_editar') && $cotizacion['estado'] == 'Borrador' && (($_SESSION['usuario_id'] ?? 0) == ($cotizacion['id_usuario_creador'] ?? 0) || ($_SESSION['usuario_rol'] ?? 0) == 1)): ?>
        <div class="cy-cot-workflow-box is-brand d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="fw-semibold mb-1" style="font-size: 15px;">Cotización en Borrador</div>
                <div class="cy-meta">Esta cotización se encuentra en borrador. Envíala a revisión para que la gerencia pueda revisarla y aprobarla.</div>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <a href="/Cycsa/publico/cotizaciones/editar?id=<?= codificarId($cotizacion['id']) ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
                <form action="/Cycsa/publico/cotizaciones/enviar-revision" method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Enviar a Revisión</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $puedeAprobarInterno = isset($puede_aprobar_internamente)
        ? (bool)$puede_aprobar_internamente
        : \Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador::puedeAprobarInternamente($cotizacion);
    ?>
    <?php if ($puedeAprobarInterno && ($cotizacion['estado'] == 'Borrador' || $cotizacion['estado'] == 'En Revision')): ?>
        <div class="cy-cot-workflow-box is-brand">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-semibold" style="font-size: 15px;">Revisión y Aprobación Interna</span>
                <span class="cy-pill cy-pill-warn">Pendiente</span>
            </div>
            <p class="cy-meta mb-3">Valida los datos comerciales antes de enviarla al cliente. La <strong>Aprobación Interna</strong> autoriza el despacho sin cerrar la decisión del cliente; si hay correcciones, devuélvela con observaciones (sin incrementar la versión PDF).</p>
            
            <?php if (!empty($cotizacion['motivo_rechazo_cliente'])): ?>
                <div class="cy-note mt-0 mb-3" style="border-left: 3px solid var(--cy-danger);">
                    <strong style="color: var(--cy-danger);">Devuelta por el Cliente:</strong>
                    <div style="margin-top: 4px; color: var(--cy-text); font-style: italic;">"<?= htmlspecialchars($cotizacion['motivo_rechazo_cliente'], ENT_QUOTES, 'UTF-8') ?>"</div>
                </div>
            <?php endif; ?>
            
            <form action="/Cycsa/publico/cotizaciones/revision" method="POST" id="form-revision">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                <input type="hidden" name="accion" id="input-accion" value="">

                <textarea name="motivo_observacion" id="txt-motivo" placeholder="Escribe el motivo del rechazo u observación aquí..." class="form-control mb-3" style="display: none;" rows="3"></textarea>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary btn-sm" onclick="enviarRevision('aprobar')">Aprobación Interna</button>
                    <button type="button" class="btn-cy-danger-outline" onclick="mostrarCajaObservacion()">Devolver con Observación</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if (tienePermiso('cotizaciones', 'crear_editar') && $cotizacion['estado'] == 'Aprobada Internamente'): ?>
        <?php $tieneEmail = !empty($cotizacion['cliente_email']); ?>
        <div class="cy-cot-workflow-box <?= $tieneEmail ? 'is-ok' : 'is-warn' ?> d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-semibold" style="font-size: 15px;"><?= $tieneEmail ? 'Cotización Aprobada' : 'Cliente sin Correo Registrado' ?></span>
                    <span class="cy-pill <?= $tieneEmail ? 'cy-pill-ok' : 'cy-pill-warn' ?>"><?= $tieneEmail ? 'Lista para envío' : 'Envío manual' ?></span>
                </div>
                <div class="cy-meta">
                    <?php if ($tieneEmail): ?>
                        Lista para ser enviada de manera formal al correo del cliente (<?= htmlspecialchars($cotizacion['cliente_email'], ENT_QUOTES, 'UTF-8') ?>).
                    <?php else: ?>
                        Esta cotización puede ser entregada en físico. Al continuar, se registrará el envío manual en el sistema.
                    <?php endif; ?>
                </div>
            </div>
            <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <?= $tieneEmail ? 'Enviar al Cliente' : 'Registrar Envío Manual' ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (tienePermiso('cotizaciones', 'crear_editar') && in_array($cotizacion['estado'], ['Enviada al Cliente', 'Aprobada por Cliente'])): ?>
        <?php $tieneEmail = !empty($cotizacion['cliente_email']); ?>
        <div class="cy-cot-workflow-box is-brand">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fw-semibold" style="font-size: 15px;">Cotización Enviada</span>
                        <span class="cy-pill cy-pill-ok">Despachada</span>
                    </div>
                    <div class="cy-meta">
                        <?php if ($tieneEmail): ?>
                            Esta cotización ya fue enviada formalmente al cliente (<?= htmlspecialchars($cotizacion['cliente_email'], ENT_QUOTES, 'UTF-8') ?>).
                        <?php else: ?>
                            Esta cotización fue registrada como enviada de forma física/manual (El cliente no tiene correo registrado).
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($tieneEmail): ?>
                    <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;" onsubmit="return confirm('¿Deseas volver a enviar el correo al cliente?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Re-enviar Correo</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if ($cotizacion['estado'] == 'Enviada al Cliente'): ?>
                <div style="border-top: 1px solid var(--cy-border); padding-top: 16px; margin-top: 16px;">
                    <div class="fw-semibold mb-1" style="font-size: 14px;">Acciones Administrativas (En nombre del cliente)</div>
                    <p class="cy-meta mb-3">Si el cliente no puede aceptar/rechazar en línea por su cuenta, puedes registrar la decisión por él desde aquí.</p>
                    
                    <form action="/Cycsa/publico/cotizaciones/decision-administrativa" method="POST" id="form-admin-decision" style="display: flex; flex-direction: column; gap: 10px; max-width: 560px; margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                        <input type="hidden" name="accion" id="admin-accion" value="">
                        
                        <div id="admin-motivo-container" style="display: none; width: 100%;">
                            <label class="form-label" style="color: var(--cy-danger);">Motivo del Rechazo</label>
                            <textarea name="motivo_rechazo" id="admin-motivo" placeholder="Escriba el motivo por el cual el cliente rechaza la cotización..." class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" onclick="confirmarDecisionAdmin('aceptar')" class="btn btn-primary btn-sm">Aprobar Cotización (Cliente)</button>
                            <button type="button" id="btn-admin-rechazar-init" onclick="mostrarRechazoAdmin()" class="btn-cy-danger-outline">Rechazar Cotización</button>
                            <button type="button" id="btn-admin-rechazar-confirm" onclick="confirmarDecisionAdmin('rechazar')" class="btn-cy-danger-outline" style="display: none;">Confirmar Rechazo</button>
                            <button type="button" id="btn-admin-cancelar" onclick="cancelarRechazoAdmin()" class="btn btn-outline-secondary btn-sm" style="display: none;">Cancelar</button>
                        </div>

                        <!-- MODAL DE CONFIRMACIÓN ADMINISTRATIVO -->
                        <div id="modalAdminAprobar" class="modal-premium">
                            <div class="modal-premium-content" style="width: 450px;">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <h3 style="margin: 0; font-size: 17px; font-weight: 600;">Confirmar Aprobación</h3>
                                    <button type="button" onclick="cerrarAdminAprobarModal()" class="btn-cerrar">&times;</button>
                                </div>
                                
                                <p class="cy-meta mb-4">¿Está seguro de que desea aprobar esta cotización? Al confirmar, se creará la Orden de Servicio automáticamente y comenzará el flujo operativo en el laboratorio.</p>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" onclick="cerrarAdminAprobarModal()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                                    <button type="button" onclick="enviarAprobacionAdminDirecta()" class="btn btn-primary btn-sm">Confirmar y Aprobar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php 
    $mostrarCajaAlerta = false;
    $tituloAlerta = '';
    $motivoAlerta = '';
    $claseAlertaBox = 'is-warn';
    $clasePillAlerta = 'cy-pill-warn';
    
    if (tienePermiso('cotizaciones', 'crear_editar') && ($_SESSION['usuario_id'] == ($cotizacion['id_usuario_creador'] ?? 0) || ($_SESSION['usuario_rol'] ?? 0) == 1)) {
        if ($cotizacion['estado'] === 'Observada') {
            $mostrarCajaAlerta = true;
            $tituloAlerta = 'Cotización Observada (Interna)';
            $motivoAlerta = $cotizacion['motivo_observacion'];
            $claseAlertaBox = 'is-warn';
            $clasePillAlerta = 'cy-pill-warn';
        } elseif ($cotizacion['estado'] === 'Rechazada por Cliente') {
            $mostrarCajaAlerta = true;
            $tituloAlerta = 'Cotización Devuelta / Rechazada por el Cliente';
            $motivoAlerta = $cotizacion['motivo_rechazo_cliente'];
            $claseAlertaBox = 'is-danger';
            $clasePillAlerta = 'cy-pill-danger';
        }
    }
    
    if ($mostrarCajaAlerta): ?>
        <div class="cy-cot-workflow-box <?= $claseAlertaBox ?>">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-semibold" style="font-size: 15px;"><?= $tituloAlerta ?></span>
                <span class="cy-pill <?= $clasePillAlerta ?>">Requiere atención</span>
            </div>
            <p class="mb-3" style="font-size: 14px; color: var(--cy-text);"><strong>Motivo indicado:</strong> <?= htmlspecialchars($motivoAlerta ?? 'No especificado', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="/Cycsa/publico/cotizaciones/editar?id=<?= codificarId($cotizacion['id']) ?>" class="btn btn-primary btn-sm">
                    Corregir y Re-enviar
                </a>
                <?php if ($cotizacion['estado'] === 'Rechazada por Cliente'): ?>
                    <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;" onsubmit="return confirm('¿Deseas volver a enviar esta cotización al cliente sin realizar cambios? Se archivará la versión actual V<?= $cotizacion['version'] ?> y se generará una nueva versión V<?= ($cotizacion['version'] + 1) ?>.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            Volver a Enviar (Nueva Versión)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- CONTROL Y DESGLOSE DE VERSIONES DE LA COTIZACIÓN -->
    <?php 
    $versionActualNum = max(1, (int)($cotizacion['version'] ?? 1));
    $tieneVersionesPrevias = !empty($versiones) && count($versiones) > 0;
    $simboloMonedaCot = ((int)($cotizacion['tipo_moneda'] ?? 1) === 2) ? '$' : 'C$';
    $totalVersionesRegistradas = count($versiones) + 1;
    ?>
    <div style="margin-top: 28px; border-top: 1px solid var(--cy-border); padding-top: 24px;">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 style="font-size: 17px; font-weight: 600; margin: 0; color: var(--cy-text);">
                    Control y Desglose de Versiones de la Cotización
                </h3>
                <div class="cy-meta">
                    Registro y trazabilidad de revisiones emitidas. Cada versión puede desglosarse e imprimirse de forma independiente en PDF oficial.
                </div>
            </div>
            <span class="cy-pill cy-pill-pend">
                Total de Versiones: <?= $totalVersionesRegistradas ?>
            </span>
        </div>
        
        <div class="d-flex flex-column gap-3">
            <!-- VERSIÓN ACTUAL (VIGENTE) -->
            <div class="cy-version-card is-current">
                <div class="cy-version-head">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="cy-pill cy-pill-ok">v<?= $versionActualNum ?> · ACTUAL / VIGENTE</span>
                        <strong style="font-size: 14px; color: var(--cy-text);">Propuesta Vigente en el Sistema (<?= htmlspecialchars($cotizacion['estado'], ENT_QUOTES, 'UTF-8') ?>)</strong>
                        <span class="cy-meta">
                            · <?= date('d/m/Y h:i A', strtotime($cotizacion['fecha_actualizacion'] ?? $cotizacion['fecha_creacion'])) ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold me-1" style="color: var(--cy-brand); font-size: 14px;">
                            Total: <?= $simboloMonedaCot ?> <?= number_format($cotizacion['total'], 2, '.', ',') ?>
                        </span>
                        <a href="/Cycsa/publico/cotizaciones/imprimir?id=<?= codificarId($cotizacion['id']) ?>&version=<?= $versionActualNum ?>" target="_blank" class="btn btn-primary btn-sm" title="Ver e imprimir PDF de la Versión Actual">
                            Imprimir Versión Actual (v<?= $versionActualNum ?>)
                        </a>
                        <button type="button" onclick="toggleVersion('actual')" class="btn btn-outline-secondary btn-sm">
                            Ver Desglose <i class="fa-solid fa-chevron-down ms-1" id="icon-version-actual" style="transition: transform 0.2s;"></i>
                        </button>
                    </div>
                </div>

                <!-- Desglose de la Versión Actual -->
                <div id="body-version-actual" style="display: none; padding: 20px; border-top: 1px solid #e2e8f0; background: #ffffff;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 18px; font-size: 13px;">
                        <div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Atención a:</span>
                            <strong><?= htmlspecialchars($cotizacion['atencion_a'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Condiciones Comerciales:</span>
                            <strong>Pago:</strong> <?= htmlspecialchars($cotizacion['condicion_pago'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?><br>
                            <strong>Entrega:</strong> <?= htmlspecialchars($cotizacion['tiempo_entrega'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?><br>
                            <strong>Vigencia:</strong> <?= htmlspecialchars($cotizacion['vigencia_oferta'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <span style="color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Proyecto:</span>
                            <strong><?= htmlspecialchars($cotizacion['nombre_proyecto'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span style="font-size: 12px; color: #475569;"><?= htmlspecialchars($cotizacion['direccion_proyecto'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>

                    <div style="overflow-x: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 12px;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 600px; font-size: 13px; text-align: left;">
                            <thead>
                                <tr style="background: #f1f5f9;">
                                    <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 5%;">#</th>
                                    <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1;">Descripción del Ensayo / Servicio</th>
                                    <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 10%; text-align: center;">Cant.</th>
                                    <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 18%; text-align: right;">Precio Unit.</th>
                                    <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 18%; text-align: right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($detalles)): ?>
                                    <?php foreach ($detalles as $idxD => $det): ?>
                                        <tr>
                                            <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; text-align: center;"><?= $idxD + 1 ?></td>
                                            <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e293b; white-space: normal; word-wrap: break-word; overflow-wrap: anywhere;">
                                                <?= nl2br(htmlspecialchars($det['descripcion_ensayo'] ?? 'Servicio', ENT_QUOTES, 'UTF-8')) ?>
                                                <?php if (!empty($det['descripcion_adicional'])): ?>
                                                    <div style="margin-top: 4px; font-size: 12px; font-weight: 500; color: #64748b; white-space: pre-wrap; word-wrap: break-word; overflow-wrap: anywhere;">
                                                        <?= nl2br(htmlspecialchars($det['descripcion_adicional'], ENT_QUOTES, 'UTF-8')) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php
                                                $metaPartsAct = [];
                                                if (!empty($det['codigo_servicio'])) $metaPartsAct[] = 'Código: <strong>' . htmlspecialchars($det['codigo_servicio'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                if (!empty($det['norma_astm'])) $metaPartsAct[] = 'Norma: <strong>' . htmlspecialchars($det['norma_astm'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                if (!empty($det['formato_reporte'])) $metaPartsAct[] = 'Formato: <strong>' . htmlspecialchars($det['formato_reporte'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                if (!empty($det['observaciones'])) $metaPartsAct[] = 'Tiempo Entrega: <strong>' . htmlspecialchars($det['observaciones'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                ?>
                                                <?php if (!empty($metaPartsAct)): ?>
                                                    <div style="margin-top: 4px; font-size: 11px; color: #475569;">
                                                        <?= implode(' &bull; ', $metaPartsAct) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #334155; text-align: center; font-weight: 600;">
                                                <?= number_format($det['cantidad'], 2) ?>
                                            </td>
                                            <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #334155;">
                                                <?= $simboloMonedaCot ?> <?= number_format($det['precio_unitario'], 2, '.', ',') ?>
                                            </td>
                                            <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; color: #0f172a;">
                                                <?= $simboloMonedaCot ?> <?= number_format($det['subtotal'], 2, '.', ',') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <div style="min-width: 260px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #475569;">
                                <span>Subtotal:</span>
                                <strong><?= $simboloMonedaCot ?> <?= number_format($cotizacion['subtotal'], 2, '.', ',') ?></strong>
                            </div>
                            <?php if ((float)($cotizacion['descuento'] ?? 0) > 0): ?>
                                <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #dc2626;">
                                    <span>Descuento:</span>
                                    <strong>-<?= $simboloMonedaCot ?> <?= number_format($cotizacion['descuento'], 2, '.', ',') ?></strong>
                                </div>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #475569;">
                                <span>Impuesto (15%):</span>
                                <strong><?= $simboloMonedaCot ?> <?= number_format($cotizacion['impuesto'], 2, '.', ',') ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-top: 1.5px solid #cbd5e1; margin-top: 4px; font-size: 15px; color: #103487;">
                                <strong>Total Versión Actual:</strong>
                                <strong><?= $simboloMonedaCot ?> <?= number_format($cotizacion['total'], 2, '.', ',') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VERSIONES HISTÓRICAS / ANTERIORES -->
            <?php if ($tieneVersionesPrevias): ?>
                <?php foreach ($versiones as $v): 
                    $datos = json_decode($v['datos_json'], true);
                    $fecha = date('d/m/Y h:i A', strtotime($v['fecha_creacion']));
                    $vNum = (int)$v['version'];
                    $vMoneda = ((int)($datos['tipo_moneda'] ?? $cotizacion['tipo_moneda'] ?? 1) === 2) ? '$' : 'C$';
                ?>
                    <div class="cy-version-card">
                        <!-- Cabecera de la versión histórica -->
                        <div class="cy-version-head">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="cy-pill cy-pill-pend">v<?= $vNum ?> · HISTÓRICA</span>
                                <strong style="font-size: 14px; color: var(--cy-text);"><?= htmlspecialchars($v['motivo_cambio'] ?: 'Cambio registrado', ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="cy-meta">· <?= $fecha ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-semibold me-1" style="color: var(--cy-text); font-size: 14px;">
                                    Total: <?= $vMoneda ?> <?= number_format($datos['total'] ?? 0, 2, '.', ',') ?>
                                </span>
                                <a href="/Cycsa/publico/cotizaciones/imprimir?id=<?= codificarId($cotizacion['id']) ?>&version=<?= $vNum ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Ver e imprimir PDF oficial de esta versión histórica">
                                    Imprimir Versión <?= $vNum ?>
                                </a>
                                <button type="button" onclick="toggleVersion(<?= $v['id'] ?>)" class="btn btn-outline-secondary btn-sm">
                                    Ver Desglose <i class="fa-solid fa-chevron-down ms-1" id="icon-version-<?= $v['id'] ?>" style="transition: transform 0.2s;"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Cuerpo de la versión histórica (Desplegable) -->
                        <div id="body-version-<?= $v['id'] ?>" style="display: none; padding: 20px; border-top: 1px solid #edf2f7; background: #fff;">
                            <!-- Info general de la versión histórica -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 18px; font-size: 13px;">
                                <div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <span style="color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Atención a:</span>
                                    <strong><?= htmlspecialchars($datos['atencion_a'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <span style="color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Condiciones Comerciales:</span>
                                    <strong>Pago:</strong> <?= htmlspecialchars($datos['condicion_pago'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Entrega:</strong> <?= htmlspecialchars($datos['tiempo_entrega'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Vigencia:</strong> <?= htmlspecialchars($datos['vigencia_oferta'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <span style="color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Proyecto Registrado:</span>
                                    <strong><?= htmlspecialchars($datos['nombre_proyecto'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <span style="font-size: 12px; color: #475569;"><?= htmlspecialchars($datos['direccion_proyecto'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            
                            <!-- Tabla de items de esta versión histórica -->
                            <div style="overflow-x: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 12px;">
                                <table style="width: 100%; border-collapse: collapse; min-width: 600px; font-size: 13px; text-align: left;">
                                    <thead>
                                        <tr style="background: #f1f5f9;">
                                            <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 5%;">#</th>
                                            <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1;">Descripción del Ensayo / Servicio</th>
                                            <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 10%; text-align: center;">Cant.</th>
                                            <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 18%; text-align: right;">Precio Unit.</th>
                                            <th style="padding: 9px 12px; font-weight: 700; color: #334155; border-bottom: 1px solid #cbd5e1; width: 18%; text-align: right;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($datos['detalles'])): ?>
                                            <?php foreach ($datos['detalles'] as $idxHist => $det): ?>
                                                <tr>
                                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; text-align: center;"><?= $idxHist + 1 ?></td>
                                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #334155; white-space: normal; word-wrap: break-word; overflow-wrap: anywhere;">
                                                        <?= nl2br(htmlspecialchars($det['descripcion_ensayo'] ?? 'Servicio', ENT_QUOTES, 'UTF-8')) ?>
                                                        <?php if (!empty($det['descripcion_adicional'])): ?>
                                                            <div style="margin-top: 4px; font-size: 12px; font-weight: 500; color: #64748b; white-space: pre-wrap; word-wrap: break-word; overflow-wrap: anywhere;">
                                                                <?= nl2br(htmlspecialchars($det['descripcion_adicional'], ENT_QUOTES, 'UTF-8')) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php
                                                        $metaPartsV = [];
                                                        if (!empty($det['codigo_servicio'])) {
                                                            $metaPartsV[] = 'Código: <strong>' . htmlspecialchars($det['codigo_servicio'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        if (!empty($det['norma_astm'])) {
                                                            $metaPartsV[] = 'Norma: <strong>' . htmlspecialchars($det['norma_astm'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        if (!empty($det['formato_reporte'])) {
                                                            $metaPartsV[] = 'Formato Reporte: <strong>' . htmlspecialchars($det['formato_reporte'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        if (!empty($det['observaciones'])) {
                                                            $metaPartsV[] = 'Tiempo Entrega: <strong>' . htmlspecialchars($det['observaciones'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        ?>
                                                        <?php if (!empty($metaPartsV)): ?>
                                                            <div style="margin-top: 4px; padding-top: 2px; border-top: 1px dashed #e2e8f0; font-size: 11px; color: #475569;">
                                                                <?= implode(' &bull; ', $metaPartsV) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #334155; text-align: center; font-weight: 600;">
                                                        <?= number_format($det['cantidad'], 2) ?>
                                                    </td>
                                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #334155;">
                                                        <?= $vMoneda ?> <?= number_format($det['precio_unitario'], 2, '.', ',') ?>
                                                    </td>
                                                    <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 700; color: #334155;">
                                                        <?= $vMoneda ?> <?= number_format($det['subtotal'], 2, '.', ',') ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" style="text-align: center; padding: 15px; color: #94a3b8;">Sin detalles registrados.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div style="display: flex; justify-content: flex-end;">
                                <div style="min-width: 260px; font-size: 13px;">
                                    <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #475569;">
                                        <span>Subtotal:</span>
                                        <strong><?= $vMoneda ?> <?= number_format($datos['subtotal'] ?? 0, 2, '.', ',') ?></strong>
                                    </div>
                                    <?php if ((float)($datos['descuento'] ?? 0) > 0): ?>
                                        <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #dc2626;">
                                            <span>Descuento:</span>
                                            <strong>-<?= $vMoneda ?> <?= number_format($datos['descuento'], 2, '.', ',') ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display: flex; justify-content: space-between; padding: 3px 0; color: #475569;">
                                        <span>Impuesto:</span>
                                        <strong><?= $vMoneda ?> <?= number_format($datos['impuesto'] ?? 0, 2, '.', ',') ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-top: 1.5px solid #cbd5e1; margin-top: 4px; font-size: 15px; color: #334155;">
                                        <strong>Total Versión <?= $vNum ?>:</strong>
                                        <strong><?= $vMoneda ?> <?= number_format($datos['total'] ?? 0, 2, '.', ',') ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 12px 18px; font-size: 12.5px; color: #64748b;">
                    <i class="fa-solid fa-circle-info" style="color: #3b82f6;"></i> Esta cotización se encuentra en su versión original inicial (<strong>v1</strong>). Si en el futuro es observada o devuelta por el cliente, cada versión anterior quedará archivada y desglosada automáticamente en este panel con su opción de impresión independiente.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleVersion(id) {
            const body = document.getElementById('body-version-' + id);
            const icon = document.getElementById('icon-version-' + id);
            if (!body) return;
            if (body.style.display === 'none' || body.style.display === '') {
                body.style.display = 'block';
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                body.style.display = 'none';
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
    </div>
</div>

<script>
    function mostrarCajaObservacion() {
        const caja = document.getElementById('txt-motivo');
        if (caja.style.display === 'none') {
            caja.style.display = 'block';
            caja.required = true;
            caja.focus();
        } else {
            // Si ya está visible, significa que escribieron el motivo y quieren enviarlo
            if (caja.value.trim() === '') {
                alert('Debes escribir un motivo para devolver la cotización.');
                return;
            }
            enviarRevision('observar');
        }
    }

    function enviarRevision(accion) {
        if (accion === 'aprobar') {
            if (!confirm('¿Estás seguro de aprobar esta cotización? No podrá ser editada después.')) return;
        }
        document.getElementById('input-accion').value = accion;
        document.getElementById('form-revision').submit();
    }

    function mostrarRechazoAdmin() {
        document.getElementById('admin-motivo-container').style.display = 'block';
        document.getElementById('admin-motivo').required = true;
        document.getElementById('admin-motivo').focus();
        
        document.getElementById('btn-admin-rechazar-init').style.display = 'none';
        document.getElementById('btn-admin-rechazar-confirm').style.display = 'inline-block';
        document.getElementById('btn-admin-cancelar').style.display = 'inline-block';
    }

    function cancelarRechazoAdmin() {
        document.getElementById('admin-motivo-container').style.display = 'none';
        document.getElementById('admin-motivo').required = false;
        document.getElementById('admin-motivo').value = '';
        
        document.getElementById('btn-admin-rechazar-init').style.display = 'inline-block';
        document.getElementById('btn-admin-rechazar-confirm').style.display = 'none';
        document.getElementById('btn-admin-cancelar').style.display = 'none';
    }

    function abrirAdminAprobarModal() {
        document.getElementById('modalAdminAprobar').style.display = 'block';
    }

    function cerrarAdminAprobarModal() {
        document.getElementById('modalAdminAprobar').style.display = 'none';
    }

    function enviarAprobacionAdminDirecta() {
        cerrarAdminAprobarModal();
        document.getElementById('admin-accion').value = 'aceptar';
        document.getElementById('form-admin-decision').submit();
    }

    function confirmarDecisionAdmin(accion) {
        if (accion === 'aceptar') {
            abrirAdminAprobarModal();
        } else if (accion === 'rechazar') {
            const motivo = document.getElementById('admin-motivo').value.trim();
            if (motivo === '') {
                alert('Por favor, especifique el motivo por el cual el cliente rechaza la cotización.');
                return;
            }
            if (!confirm('¿Está seguro de RECHAZAR esta cotización en nombre del cliente? Esto registrará el motivo indicado.')) return;
            document.getElementById('admin-accion').value = accion;
            document.getElementById('form-admin-decision').submit();
        }
    }
</script>

<!-- MODAL PROGRAMAR OPERATIVO DESDE DETALLE -->
<?php if ($cotizacion['estado'] === 'Aprobada por Cliente' && tienePermiso('operaciones', 'crear_editar')): ?>
<div id="modalProgCotizacion" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Programación Operativa</h3>
            <button onclick="cerrarProgramacionCotizacion()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/guardar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_cotizacion" value="<?= $cotizacion['id'] ?>">
            <input type="hidden" name="redireccionar_a" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Entrega</label>
                <input type="date" name="fecha_entrega" value="<?= $cotizacion['fecha_entrega'] ?>" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Seguimiento</label>
                <input type="date" name="fecha_seguimiento" value="<?= $cotizacion['fecha_seguimiento'] ?>" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Estado Operativo</label>
                <select name="estado_operativo" required class="form-control" style="background-color: white;">
                    <option value="Pendiente" <?= ($cotizacion['estado_operativo'] ?? 'Pendiente') === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="En Proceso" <?= ($cotizacion['estado_operativo'] ?? '') === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                    <option value="Entregado" <?= ($cotizacion['estado_operativo'] ?? '') === 'Entregado' ? 'selected' : '' ?>>Entregado</option>
                    <option value="Cancelado" <?= ($cotizacion['estado_operativo'] ?? '') === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Notas Operativas</label>
                <textarea name="notas_operativas" rows="3" placeholder="Instrucciones para despacho o ruta..." class="form-control"><?= htmlspecialchars($cotizacion['notas_operativas'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" onclick="cerrarProgramacionCotizacion()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modalProgCot = document.getElementById('modalProgCotizacion');
    
    function abrirProgramacionCotizacion() {
        modalProgCot.style.display = 'block';
    }
    
    function cerrarProgramacionCotizacion() {
        modalProgCot.style.display = 'none';
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === modalProgCot) {
            cerrarProgramacionCotizacion();
        }
        const modalAdminAprobar = document.getElementById('modalAdminAprobar');
        if (modalAdminAprobar && e.target === modalAdminAprobar) {
            cerrarAdminAprobarModal();
        }
    });
</script>
<?php endif; ?>
</div>