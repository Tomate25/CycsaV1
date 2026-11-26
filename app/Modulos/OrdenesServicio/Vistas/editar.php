<?php
// Vista para editar Orden de Servicio CYCSA-RG-FM-39 V1 (CYCSA ERP Premium Style)
$esAprobada = esOrdenServicioAprobada($os['estado'] ?? null);
$puedeAprobarOS = in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 3], true) || tienePermiso('ordenes_servicio', 'aprobar');
$mostrarAprobacionEnEdicion = (!$esAprobada && $puedeAprobarOS);
$ensayosPermitidosSet = array_flip(array_map('intval', $ensayosPermitidosActuales ?? []));
$esMultimes = !empty($os['es_multimes']);
$idClienteFacturaActual = (int)($os['id_cliente_factura'] ?? $os['id_cliente'] ?? 0);
$idClienteInformeActual = (int)($os['id_cliente_informe'] ?? $os['id_cliente'] ?? 0);
$listaClientes = $clientes ?? [];
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/ordenes-servicio-sobrio.css">
<style>
    .cycsa-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; }
    .cycsa-card-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .form-group-cycsa { margin-bottom: 15px; }
    .form-group-cycsa label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px; }
    .form-control-cycsa { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 13.5px; box-sizing: border-box; transition: all 0.2s; }
    .form-control-cycsa:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }
    .form-control-readonly { background-color: #f8fafc; font-weight: 600; color: #1e293b; }

    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13.5px; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 12px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; }
    .tabla-cycsa td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }

    .btn-cycsa { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid transparent; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: #0c2766; color: white; }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }

    .aviso-reaprobacion {
        background: #fffbeb;
        border: 1.5px solid #fde68a;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 14px;
        color: #92400e;
        font-size: 13.5px;
    }
</style>

<div class="container-fluid px-0 py-0 cy-os-wrap" style="padding-bottom: 32px;">
    <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $os['id'] ?>" class="cy-back-link"><i class="fa-solid fa-arrow-left"></i> Volver a O/S</a>
    <div class="cy-os-header">
        <div class="cy-os-header-left">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h1 class="cy-page-title">Editar Orden de Servicio <?= htmlspecialchars($os['codigo_os']) ?></h1>
                <span class="cy-pill <?= $esAprobada ? 'cy-pill-ok' : 'cy-pill-warn' ?>"><?= htmlspecialchars($os['estado']) ?></span>
            </div>
            <div class="cy-meta">
                Documento <strong><?= htmlspecialchars($os['codigo_os']) ?></strong> · v<?= (int)($os['version'] ?? 1) ?> · Estado actual: <?= htmlspecialchars($os['estado']) ?>
            </div>
        </div>
    </div>

    <?php if ($esAprobada): ?>
        <div class="aviso-reaprobacion">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 24px; color: #d97706; flex-shrink: 0;"></i>
            <div>
                <strong>Re-aprobación requerida tras edición:</strong> Esta Orden de Servicio se encuentra actualmente en estado <em>Aprobada</em>. Al guardar cambios técnicos o comerciales, el sistema transicionará automáticamente la O/S a <strong>Estado 2: Revisión</strong> para requerir la nueva validación y re-aprobación de la supervisión.
            </div>
        </div>
    <?php endif; ?>

    <form action="/Cycsa/publico/ordenes-servicio/actualizar" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_os" value="<?= $os['id'] ?>">

        <!-- DATOS PRINCIPALES DE LA O/S -->
        <div class="cycsa-card">
            <div class="cycsa-card-title">
                <i class="fa-solid fa-circle-info"></i> 1. Encabezado de la Orden de Servicio
            </div>
            
            <div class="form-grid-3">
                <div class="form-group-cycsa">
                    <label>Doc. Número (Código O/S):</label>
                    <input type="text" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($os['codigo_os']) ?>" readonly style="color: var(--cycsa-azul);">
                    <small style="font-size: 11px; color: #94a3b8;"><i class="fa-solid fa-lock"></i> Código inmutable</small>
                </div>
                <div class="form-group-cycsa">
                    <label>Fecha de Emisión:</label>
                    <input type="text" class="form-control-cycsa form-control-readonly" value="<?= !empty($os['fecha_emision']) ? date('d/m/Y', strtotime($os['fecha_emision'])) : 'N/A' ?>" readonly>
                </div>
                <div class="form-group-cycsa">
                    <label>Cotización Origen:</label>
                    <input type="text" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($cotizacion['codigo'] ?? 'N/A') ?> (v<?= $cotizacion['version'] ?? 1 ?>)" readonly>
                    <small style="font-size: 11px; color: #94a3b8;"><i class="fa-solid fa-lock"></i> Cotización inmutable</small>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label style="font-weight: 700; color: #103487;">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Cliente que Paga (Facturación / CxC):
                    </label>
                    <?php if (!empty($listaClientes)): ?>
                        <select name="id_cliente_factura" class="form-control-cycsa" style="font-weight: 600;">
                            <?php foreach ($listaClientes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ((int)$c['id'] === $idClienteFacturaActual) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre_razon_social']) ?> (<?= htmlspecialchars($c['numero_ruc'] ?? 'S/R') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="id_cliente_factura" value="<?= $idClienteFacturaActual ?>">
                        <input type="text" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($os['cliente_factura_nombre'] ?? $os['cliente_nombre']) ?>" readonly>
                    <?php endif; ?>
                </div>
                <div class="form-group-cycsa">
                    <label style="font-weight: 700; color: #047857;">
                        <i class="fa-solid fa-flask-vial"></i> Cliente del Informe (Destinatario Técnico / ISO 17025):
                    </label>
                    <?php if (!empty($listaClientes)): ?>
                        <select name="id_cliente_informe" class="form-control-cycsa" style="font-weight: 600;">
                            <?php foreach ($listaClientes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ((int)$c['id'] === $idClienteInformeActual) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre_razon_social']) ?> (<?= htmlspecialchars($c['numero_ruc'] ?? 'S/R') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="id_cliente_informe" value="<?= $idClienteInformeActual ?>">
                        <input type="text" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($os['cliente_informe_nombre'] ?? $os['cliente_nombre']) ?>" readonly>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-grid-3">
                <div class="form-group-cycsa">
                    <label>Atención a (Contacto Principal):</label>
                    <input type="text" name="atencion_a" class="form-control-cycsa" value="<?= htmlspecialchars($os['atencion_a'] ?? '') ?>" required>
                </div>
                <div class="form-group-cycsa">
                    <label>Forma de Pago:</label>
                    <input type="text" name="forma_pago" class="form-control-cycsa" value="<?= htmlspecialchars($os['forma_pago'] ?? 'Pago contra entrega') ?>">
                </div>
                <div class="form-group-cycsa">
                    <label>Condición Comercial:</label>
                    <select name="condicion_comercial" class="form-control-cycsa" style="font-weight: 600;">
                        <option value="contado" <?= (($os['condicion_comercial'] ?? '') === 'contado') ? 'selected' : '' ?>>Contado / Contra Entrega</option>
                        <option value="credito" <?= (($os['condicion_comercial'] ?? '') === 'credito') ? 'selected' : '' ?>>Crédito</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Dirección del Cliente:</label>
                    <input type="text" name="direccion_cliente" class="form-control-cycsa" value="<?= htmlspecialchars((string)($os['direccion_cliente'] ?? ($os['cliente_direccion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" placeholder="Dirección del cliente">
                </div>
                <div class="form-group-cycsa">
                    <label>Ubicación del Proyecto:</label>
                    <input type="text" name="ubicacion_proyecto" class="form-control-cycsa" value="<?= htmlspecialchars((string)($os['ubicacion_proyecto'] ?? ($os['direccion_proyecto'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. Corinto, Managua, León...">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Modalidad de Contrato / Servicio:</label>
                    <select name="tipo_contrato" class="form-control-cycsa" style="font-weight: 600;">
                        <option value="Puntual" <?= (($os['tipo_contrato'] ?? '') === 'Puntual') ? 'selected' : '' ?>>Puntual (Un solo evento/entrega)</option>
                        <option value="Marco" <?= (($os['tipo_contrato'] ?? '') === 'Marco' || str_contains($os['tipo_contrato'] ?? '', 'Marco')) ? 'selected' : '' ?>>Marco / Contrato Activo (Meses, entregas continuas)</option>
                    </select>
                </div>
                <div class="form-group-cycsa">
                    <label>Proyecto:</label>
                    <textarea name="nombre_proyecto" class="form-control-cycsa" rows="2" required><?= htmlspecialchars($os['nombre_proyecto'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SECCIÓN MULTI-MES -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px 18px; margin-bottom: 18px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #1e293b; font-size: 14px; margin: 0;">
                        <input type="checkbox" name="es_multimes" id="chk_es_multimes" value="1" <?= $esMultimes ? 'checked' : '' ?> onchange="toggleMultiMes(this.checked)" style="width: 18px; height: 18px; accent-color: var(--cycsa-azul); cursor: pointer;">
                        <span><i class="fa-solid fa-repeat" style="color: var(--cycsa-azul);"></i> Contrato Multi-mes (Proyectos continuos con entregas periódicas)</span>
                    </label>
                    <span id="multimes_badge" style="display: <?= $esMultimes ? 'inline-block' : 'none' ?>; background: #e0e7ff; color: #3730a3; font-size: 11.5px; font-weight: 700; padding: 3px 9px; border-radius: 6px; border: 1px solid #c7d2fe;">
                        <i class="fa-solid fa-check"></i> Activo
                    </span>
                </div>
                <div id="seccion_multimes_campos" style="display: <?= $esMultimes ? 'grid' : 'none' ?>; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed #cbd5e1;">
                    <div class="form-group-cycsa" style="margin-bottom: 0;">
                        <label>Duración (Meses):</label>
                        <input type="number" name="duracion_meses" id="duracion_meses" min="1" max="120" value="<?= (int)($os['vigencia_contrato']['duracion_meses'] ?? $os['duracion_meses'] ?? 3) ?>" onchange="recalcularFechaFinDesdeMeses()" class="form-control-cycsa">
                    </div>
                    <div class="form-group-cycsa" style="margin-bottom: 0;">
                        <label>Fecha de Inicio del Contrato:</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control-cycsa" onchange="recalcularFechaFinDesdeMeses()" value="<?= !empty($os['fecha_inicio']) ? htmlspecialchars($os['fecha_inicio']) : date('Y-m-d') ?>">
                    </div>
                    <div class="form-group-cycsa" style="margin-bottom: 0;">
                        <label>Fecha de Finalización:</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control-cycsa" value="<?= !empty($os['fecha_fin']) ? htmlspecialchars($os['fecha_fin']) : date('Y-m-d', strtotime('+3 months')) ?>">
                    </div>
                    <div class="form-group-cycsa" style="margin-bottom: 0;">
                        <label>Periodicidad de Entregas:</label>
                        <select name="periodicidad" id="periodicidad" class="form-control-cycsa" style="font-weight: 600;">
                            <option value="Mensual" <?= (($os['periodicidad'] ?? 'Mensual') === 'Mensual') ? 'selected' : '' ?>>Mensual</option>
                            <option value="Quincenal" <?= (($os['periodicidad'] ?? '') === 'Quincenal') ? 'selected' : '' ?>>Quincenal</option>
                            <option value="Bimestral" <?= (($os['periodicidad'] ?? '') === 'Bimestral') ? 'selected' : '' ?>>Bimestral</option>
                            <option value="Trimestral" <?= (($os['periodicidad'] ?? '') === 'Trimestral') ? 'selected' : '' ?>>Trimestral</option>
                            <option value="Semestral" <?= (($os['periodicidad'] ?? '') === 'Semestral') ? 'selected' : '' ?>>Semestral</option>
                        </select>
                    </div>
                </div>
            </div>

            <?php
            $docsVincEdit = !empty($os['documentos_vinculados_json'])
                ? (is_array($os['documentos_vinculados_json']) ? $os['documentos_vinculados_json'] : (json_decode((string)$os['documentos_vinculados_json'], true) ?: []))
                : [];
            ?>
            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Solicitud / Autorización del Cliente (Ref.):</label>
                    <input type="text" name="doc_solicitud_cliente" class="form-control-cycsa" value="<?= htmlspecialchars((string)($docsVincEdit['solicitud_cliente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. Correo de autorización / OC del cliente">
                </div>
                <div class="form-group-cycsa">
                    <label>Otros Anexos / Registros Técnicos:</label>
                    <input type="text" name="doc_otros_anexos" class="form-control-cycsa" value="<?= htmlspecialchars((string)($docsVincEdit['otros_anexos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. Planos, especificaciones, anexo técnico">
                </div>
            </div>

            <div class="form-group-cycsa" style="margin-bottom: <?= $esAprobada ? '16px' : '0' ?>;">
                <label>Notas y Condiciones Especiales:</label>
                <textarea name="notas_condiciones" class="form-control-cycsa" rows="2" placeholder="Observaciones o términos adicionales..."><?= htmlspecialchars($os['notas_condiciones'] ?? '') ?></textarea>
            </div>

            <?php if ($esAprobada): ?>
            <div class="form-group-cycsa" style="margin-bottom: 0; background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 8px; padding: 14px 16px;">
                <label for="motivo_cambio_version" style="color: #92400e;">
                    <i class="fa-solid fa-code-branch"></i> Nota / Motivo de modificación (generará nueva revisión v<?= (int)($os['version'] ?? 1) + 1 ?>) *
                </label>
                <textarea id="motivo_cambio_version" name="motivo_cambio_version" class="form-control-cycsa" rows="2" required placeholder="Explique brevemente el motivo del cambio en esta O/S aprobada..."><?= htmlspecialchars($os['motivo_cambio_version'] ?? '') ?></textarea>
                <small style="display:block; margin-top:4px; color:#92400e; font-size:11.5px;">Se archivará un snapshot de la versión actual v<?= (int)($os['version'] ?? 1) ?> y la O/S pasará a Estado 2: Revisión para re-aprobación.</small>
            </div>
            <?php endif; ?>
        </div>

        <?php
        $contactosEditOs = [];
        $fuenteContactosEdit = !empty($os['contactos_json']) ? $os['contactos_json'] : ($os['cotizacion_contactos'] ?? '');
        if (!empty($fuenteContactosEdit)) {
            $decC = is_array($fuenteContactosEdit) ? $fuenteContactosEdit : json_decode((string)$fuenteContactosEdit, true);
            if (is_array($decC)) {
                foreach ($decC as $cc) {
                    if (is_array($cc)) {
                        $contactosEditOs[] = [
                            'nombre' => (string)($cc['nombre'] ?? ''),
                            'telefono' => (string)($cc['telefono'] ?? ''),
                            'correo' => (string)($cc['correo'] ?? ($cc['email'] ?? '')),
                        ];
                    } elseif (is_string($cc) && trim($cc) !== '') {
                        $contactosEditOs[] = ['nombre' => trim($cc), 'telefono' => '', 'correo' => ''];
                    }
                }
            }
        }
        if (empty($contactosEditOs)) {
            $contactosEditOs[] = [
                'nombre' => (string)($os['atencion_a'] ?? ''),
                'telefono' => (string)($os['cliente_telefono'] ?? ''),
                'correo' => (string)($os['cliente_email'] ?? ''),
            ];
        }
        ?>
        <!-- CONTACTOS DEL CLIENTE -->
        <div class="cycsa-card">
            <div class="cycsa-card-title" style="justify-content: space-between;">
                <span><i class="fa-solid fa-address-book"></i> Contactos del Cliente</span>
                <button type="button" class="btn-cycsa btn-cycsa-secondary" style="min-height: 32px; padding: 4px 12px; font-size: 12.5px;" onclick="agregarContactoOsEdit()">
                    + Agregar contacto
                </button>
            </div>
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="width: 24%;">Teléfono</th>
                        <th style="width: 34%;">Correo electrónico</th>
                        <th style="width: 60px; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody id="tbody-contactos-os-edit">
                    <?php foreach ($contactosEditOs as $cRow): ?>
                    <tr>
                        <td><input type="text" name="contacto_nombre[]" class="form-control-cycsa" value="<?= htmlspecialchars($cRow['nombre'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre completo"></td>
                        <td><input type="text" name="contacto_telefono[]" class="form-control-cycsa" value="<?= htmlspecialchars($cRow['telefono'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. 8888-8888"></td>
                        <td><input type="email" name="contacto_correo[]" class="form-control-cycsa" value="<?= htmlspecialchars($cRow['correo'], ENT_QUOTES, 'UTF-8') ?>" placeholder="correo@empresa.com"></td>
                        <td style="text-align: center;"><button type="button" class="btn-cycsa btn-cycsa-danger" style="min-height: 32px; padding: 4px 10px;" onclick="this.closest('tr').remove()" title="Quitar">&times;</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TABLA DE ENSAYOS INCLUIDOS (REFERENCIA Y HABILITACIÓN PARA APROBAR) -->
        <div class="cycsa-card">
            <div class="cycsa-card-title" style="justify-content: space-between;">
                <span><i class="fa-solid fa-vial"></i> 2. Ensayos y Servicios Contratados (Heredados de Cotización)</span>
                <?php if ($mostrarAprobacionEnEdicion): ?>
                    <span style="font-size: 12px; font-weight: 600; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px;">
                        <i class="fa-solid fa-check-double"></i> Marque los ensayos a habilitar al aprobar
                    </span>
                <?php endif; ?>
            </div>
            
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <?php if ($mostrarAprobacionEnEdicion): ?>
                            <th style="width: 75px; text-align: center;">Habilitar</th>
                        <?php endif; ?>
                        <th style="width: 70px; text-align: center;">Línea</th>
                        <th>Código y Descripción del Ensayo / Servicio</th>
                        <th style="width: 120px; text-align: center;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($detalles)): ?>
                        <?php foreach ($detalles as $index => $det):
                            $idDetRow = (int)($det['id'] ?? 0);
                            $marcadoDet = empty($ensayosPermitidosSet) || isset($ensayosPermitidosSet[$idDetRow]);
                        ?>
                            <tr>
                                <?php if ($mostrarAprobacionEnEdicion): ?>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="ensayos_permitidos[]" value="<?= $idDetRow ?>" <?= $marcadoDet ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #059669; cursor: pointer;">
                                    </td>
                                <?php endif; ?>
                                <td style="text-align: center; font-weight: 700; color: #64748b;"><?= $index + 1 ?></td>
                                <td>
                                    <?= htmlspecialchars($det['descripcion_ensayo'] ?? $det['nombre_ensayo'] ?? '') ?>
                                    <?php if (!empty($det['norma_astm'])): ?>
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; margin-left: 8px; border: 1px solid #7dd3fc;"><?= htmlspecialchars($det['norma_astm']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #0f172a;">
                                    <?= number_format($det['cantidad'], 1) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $mostrarAprobacionEnEdicion ? '4' : '3' ?>" style="text-align: center; color: #64748b; padding: 20px;">No hay ensayos registrados en la cotización.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="cy-os-form-footer">
            <a href="/Cycsa/publico/ordenes-servicio" class="btn-cycsa btn-cycsa-secondary">
                Cancelar
            </a>
            <button type="submit" class="btn-cycsa <?= $mostrarAprobacionEnEdicion ? 'btn-cycsa-secondary' : 'btn-cycsa-primary' ?>">
                Guardar Cambios
            </button>
            <?php if ($mostrarAprobacionEnEdicion): ?>
                <button type="submit" name="aprobar_al_guardar" value="1" class="btn-cycsa btn-cycsa-primary">
                    Guardar y Aprobar O/S
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    function recalcularFechaFinDesdeMeses() {
        const fi = document.getElementById('fecha_inicio')?.value;
        const m = parseInt(document.getElementById('duracion_meses')?.value || '3', 10) || 3;
        const ffEl = document.getElementById('fecha_fin');
        if (fi && ffEl) {
            const d = new Date(fi + 'T00:00:00');
            if (!isNaN(d.getTime())) {
                d.setMonth(d.getMonth() + m);
                ffEl.value = d.toISOString().slice(0, 10);
            }
        }
    }

    function agregarContactoOsEdit() {
        const tbody = document.getElementById('tbody-contactos-os-edit');
        if (!tbody) return;
        const tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="text" name="contacto_nombre[]" class="form-control-cycsa" placeholder="Nombre completo"></td>'
            + '<td><input type="text" name="contacto_telefono[]" class="form-control-cycsa" placeholder="Ej. 8888-8888"></td>'
            + '<td><input type="email" name="contacto_correo[]" class="form-control-cycsa" placeholder="correo@empresa.com"></td>'
            + '<td style="text-align:center;"><button type="button" class="btn-cycsa btn-cycsa-danger" style="min-height:32px; padding:4px 10px;" onclick="this.closest(\'tr\').remove()" title="Quitar">&times;</button></td>';
        tbody.appendChild(tr);
    }

    function toggleMultiMes(activo) {
        const contenedor = document.getElementById('seccion_multimes_campos');
        const badge = document.getElementById('multimes_badge');
        if (activo) {
            contenedor.style.display = 'grid';
            if (badge) badge.style.display = 'inline-block';
        } else {
            contenedor.style.display = 'none';
            if (badge) badge.style.display = 'none';
        }
    }
</script>
