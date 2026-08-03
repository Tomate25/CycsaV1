<?php
// Recepcion form view - LIMS Sample Reception
?>
<style>
    .form-box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-top: 15px; }
    .form-section-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    
    .age-row { display: flex; align-items: center; gap: 15px; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; }
    
    .btn-action-mini { border: none; background: none; cursor: pointer; padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    .btn-add { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-add:hover { background-color: #dbeafe; }
    .btn-del { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .btn-del:hover { background-color: #fee2e2; }

    /* Custom Search Select Premium */
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-trigger {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.2s;
        min-height: 42px;
        box-sizing: border-box;
    }
    .custom-select-trigger:hover {
        border-color: var(--cycsa-azul);
    }
    .custom-select-trigger:focus, .custom-select-trigger.active {
        border-color: var(--cycsa-azul);
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1);
        outline: none;
    }
    .custom-select-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        z-index: 100;
        margin-top: 5px;
        display: none;
        overflow: hidden;
        animation: slideDown 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .custom-select-search-box {
        padding: 8px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .custom-select-search {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
    }
    .custom-select-search:focus {
        border-color: var(--cycsa-azul);
    }
    .custom-select-options-list {
        max-height: 240px;
        overflow-y: auto;
        padding: 4px 0;
    }
    .custom-select-option {
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13.5px;
        color: #334155;
        transition: all 0.15s;
        border-bottom: 1px solid #f8fafc;
    }
    .custom-select-option:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .custom-select-option.selected {
        background: #eff6ff;
        color: var(--cycsa-azul);
        font-weight: 600;
    }
    .custom-select-option.disabled {
        background: #f8fafc;
        color: #94a3b8;
        cursor: not-allowed;
        font-style: italic;
    }
    .custom-select-option.disabled:hover {
        background: #f8fafc;
    }
    .custom-select-badge {
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-available { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-locked { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
</style>

<div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Recepción e Ingreso de Muestras</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Asociando muestra a la Orden de Servicio: <strong style="color: var(--cycsa-azul); font-family: monospace;"><?= htmlspecialchars($os['codigo_os'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    </div>
    <a href="/Cycsa/publico/operaciones" class="form-control" style="text-decoration: none; background: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; display: inline-flex; align-items: center; gap: 6px; height: auto;"><i class="fa-solid fa-arrow-left"></i> Volver</a>
</div>

<form method="POST" action="/Cycsa/publico/operaciones/guardar-recepcion">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="id_os" value="<?= $os['id'] ?>">

    <!-- 1. ENSAYOS SOLICITADOS Y MATRICES DE RESULTADOS (OBLIGATORIO PRIMERO) -->
    <?php if (!empty($servicios)): ?>
        <div class="form-box" style="border: 1px solid #e2e8f0; background-color: #ffffff; border-radius: 12px; margin-bottom: 25px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <h4 class="form-section-title" style="color: #103487; border-bottom: 1px solid #f1f5f9; margin-bottom: 15px; font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-table-cells" style="color: #103487;"></i> 
                Ensayos Solicitados y Matrices de Resultados
            </h4>
            <p style="font-size: 13.5px; color: #64748b; margin-bottom: 15px;">
                <strong>Paso 1 Obligatorio:</strong> Rellene primero la matriz técnica de campo/laboratorio para cada producto cotizado antes de procesar el registro de muestras.
            </p>
            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                <table class="tabla-rupturas" style="width: 100%; border-collapse: collapse; background: white;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">SERVICIO / ENSAYO</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">NORMA / ASTM</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">FORMATO DOCUMENTO</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; text-align: center;">CANTIDAD</th>
                            <th style="padding: 12px 16px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; text-align: right;">CAPTURA LIMS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicios as $s): 
                            $cantFact = max(1, (int)($s['cantidad_facturada'] ?? $s['cantidad'] ?? 1));
                            $tieneResultados = !empty($s['resultados_json']) && $s['resultados_json'] !== '[]';
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px; font-weight: 700; color: #0f172a; font-size: 14px;">
                                    <?= htmlspecialchars($s['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="padding: 14px 16px; color: #475569; font-family: monospace; font-size: 12.5px;">
                                    <?= htmlspecialchars($s['norma_astm'] ?: 'ASTM / Standard', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="padding: 14px 16px; font-weight: 500; color: #334155; font-size: 13px;">
                                    <?= htmlspecialchars($s['formato_nombre'] ?? 'Matriz Técnica', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="padding: 14px 16px; font-weight: 700; text-align: center; font-size: 14px;">
                                    <?= $cantFact ?>
                                </td>
                                <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                    <?php if ($tieneResultados): ?>
                                        <span style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; margin-right: 8px; font-size: 11px; padding: 5px 10px; border-radius: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-circle-check"></i> CON RESULTADOS
                                        </span>
                                    <?php else: ?>
                                        <span style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; margin-right: 8px; font-size: 11px; padding: 5px 10px; border-radius: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-hourglass"></i> PENDIENTE
                                        </span>
                                    <?php endif; ?>

                                    <button type="button" 
                                            onclick="cargarMatrizDirectaProducto(<?= $s['id'] ?>)" 
                                            class="form-control" 
                                            style="padding: 7px 14px; font-size: 12.5px; font-weight: 700; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px; width: auto; height: auto;">
                                        <i class="fa-solid fa-list-check"></i> Capturar Matriz
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 0. MUESTRAS DECLARADAS EN LA ORDEN DE SERVICIO -->
    <?php if (!empty($muestrasDeclaradas)): ?>
        <div class="form-box" style="border-color: #cbd5e1; background-color: #f8fafc; margin-bottom: 20px;">
            <h4 class="form-section-title" style="color: #475569; border-bottom-color: #e2e8f0; margin-bottom: 10px;">
                <i class="fa-solid fa-list-check" style="margin-right: 6px;"></i> 
                Muestras / Especímenes Declarados en la Orden de Servicio
            </h4>
            <p style="font-size: 13.5px; color: #64748b; margin-bottom: 15px;">
                Estas son las muestras ingresadas durante la planificación de la O/S. Haga clic en <strong>"Cargar Muestra"</strong> para autocompletar el formulario de recepción.
            </p>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 10px;">
                    <thead>
                        <tr style="background-color: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Código de Campo / Muestra</th>
                            <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Descripción</th>
                            <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Información Importante</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Estado LIMS</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($muestrasDeclaradas as $md): 
                            $nombre = htmlspecialchars($md['nombre_muestra'] ?? '', ENT_QUOTES, 'UTF-8');
                            $desc = htmlspecialchars($md['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
                            $info = htmlspecialchars($md['info_importante'] ?? '', ENT_QUOTES, 'UTF-8');
                            $recibida = !empty($md['recibida']);
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 12px; font-weight: 600; color: var(--cycsa-azul); font-family: monospace; font-size: 13.5px;"><?= $nombre ?></td>
                                <td style="padding: 10px 12px; font-size: 13px; color: #334155;"><?= $desc ?></td>
                                <td style="padding: 10px 12px; font-size: 13px; color: #64748b; font-style: italic;"><?= $info ?></td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <?php if ($recibida): ?>
                                        <span style="display: inline-block; background-color: #d1fae5; color: #065f46; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 20px; border: 1px solid #a7f3d0;">
                                            <i class="fa-solid fa-circle-check"></i> Recibida (<?= htmlspecialchars($md['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?>)
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 20px; border: 1px solid #fde68a;">
                                            <i class="fa-solid fa-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <?php if (!$recibida): 
                                        $btnUniqueId = 'btn_cargar_decl_' . md5($nombre);
                                    ?>
                                        <button type="button" 
                                                id="<?= $btnUniqueId ?>"
                                                onclick="cargarMuestraDeclarada('<?= addslashes($nombre) ?>', '<?= addslashes($desc) ?>', '<?= addslashes($info) ?>', '<?= $btnUniqueId ?>')" 
                                                class="form-control btn-cargar-decl" 
                                                style="padding: 4px 10px; font-size: 11px; background-color: var(--cycsa-azul); border-color: var(--cycsa-azul); color: white; cursor: pointer; display: inline-block; width: auto; height: auto;">
                                            <i class="fa-solid fa-cloud-arrow-down"></i> Cargar Muestra
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 1. DATOS DE LOGÍSTICA -->
    <div class="form-box">
        <h4 class="form-section-title"><i class="fa-solid fa-truck-ramp-box" style="margin-right: 6px;"></i> Datos de Logística y Recepción</h4>
        <div class="grid-3">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Cliente</label>
                <input type="text" readonly class="form-control" style="background-color: #f1f5f9; font-weight: 600;" value="<?= htmlspecialchars($os['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?> (RUC/Ced: <?= htmlspecialchars($os['cliente_ruc'], ENT_QUOTES, 'UTF-8') ?>)">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Atención A</label>
                <input type="text" readonly class="form-control" style="background-color: #f1f5f9; font-weight: 600;" value="<?= htmlspecialchars($os['atencion_a'] ?? '—', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Proyecto / Obra</label>
                <input type="text" readonly class="form-control" style="background-color: #f1f5f9; font-weight: 600;" value="<?= htmlspecialchars($os['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="grid-3" style="margin-top: 10px;">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Tipo / Origen de Muestra</label>
                <select name="tipo_muestra" required class="form-control" style="background-color: #f8fafc; font-weight: 600; border-color: #93c5fd;">
                    <option value="Laboratorio" selected>Laboratorio (MS-XXXX-YY) - Consecutivo Automático</option>
                    <option value="Campo">Campo (CAM-YY-XXXX) - Muestreo In-Situ Inmutable</option>
                </select>
            </div>
        </div>

        <div class="grid-3" style="margin-top: 10px;">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Entregado Por (Nombre cliente/chofer/técnico)</label>
                <?php
                $valorDefectoEntregado = '';
                if (!empty($os['tecnico_muestreo'])) {
                    $valorDefectoEntregado = $os['tecnico_muestreo'];
                } elseif (!empty($hoja_solicitud['nombre_persona_entrega_muestra'])) {
                    $valorDefectoEntregado = $hoja_solicitud['nombre_persona_entrega_muestra'];
                }
                ?>
                <input type="text" name="entregado_por" placeholder="Nombre completo" class="form-control" value="<?= htmlspecialchars($valorDefectoEntregado, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha/Hora Recepción</label>
                <input type="datetime-local" name="fecha_recepcion" required class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="form-group" style="justify-content: center;">
                <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 4px;">Auditoría / Control de Calidad</label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1e40af; background-color: #eff6ff; padding: 8px 12px; border-radius: 6px; border: 1px solid #bfdbfe; cursor: pointer;">
                    <input type="checkbox" name="is_qa_qc" value="1">
                    <i class="fa-solid fa-microscope"></i> Es Muestra para Control de Calidad (QA/QC - Réplica)
                </label>
            </div>
        </div>

        <div class="form-group">
            <label style="font-weight: 600; font-size: 13px; color: #334155;">Observaciones Iniciales de Recepción</label>
            <textarea name="observaciones" rows="2" placeholder="Escribe detalles del estado de las muestras al recibirse..." class="form-control"></textarea>
        </div>

        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: 500;">
            <i class="fa-solid fa-lock"></i> <strong>INMUTABILIDAD AUTOMÁTICA:</strong> Al guardar, el código consecutivo de la muestra será generado de forma automática y sellado. No requiere manipulación manual.
        </div>
    </div>

    <!-- TÍTULO DE SECCIÓN Y BOTÓN DE ADICIÓN DE MUESTRAS MULTIPLES -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; margin-bottom: 15px;">
        <h3 style="margin: 0; font-family:'Outfit'; font-size: 18px; font-weight:700; color: var(--cycsa-azul);"><i class="fa-solid fa-layer-group"></i> Detalle de Muestras a Registrar</h3>
        <button type="button" class="btn-action-mini btn-add" id="btn-agregar-muestra-bloque" style="padding: 10px 18px; font-size: 13px; border-radius: 6px;"><i class="fa-solid fa-plus"></i> + Agregar Otra Muestra</button>
    </div>

    <input type="hidden" name="muestras_recibidas_json" id="muestras_recibidas_json" value="">

    <!-- CONTENEDOR DE BLOQUES DE MUESTRAS -->
    <div id="contenedor-bloques-muestras">
        <div id="empty-muestras-placeholder" style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 40px; text-align: center; color: #64748b; background-color: #f8fafc; margin-bottom: 25px;">
            <i class="fa-solid fa-flask-vial" style="font-size: 32px; color: #94a3b8; margin-bottom: 12px; display: block;"></i>
            <strong style="display: block; margin-bottom: 6px; font-family: 'Outfit'; font-size:15px; color:#334155;">No hay muestras agregadas</strong>
            Haga clic en "+ Agregar Otra Muestra" o seleccione una de las muestras declaradas arriba para registrar la recepción.
        </div>
    </div>

    <!-- PLANTILLA DE MUESTRA BLOQUE PARA CLONACIÓN -->
    <template id="template-muestra-bloque">
        <div class="muestra-bloque" data-index="__INDEX__" style="border: 1px solid #cbd5e1; padding: 25px; border-radius: 8px; margin-bottom: 25px; background: white; position: relative;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px;">
                <h3 style="margin:0; font-family:'Outfit'; font-size: 15px; color: var(--cycsa-azul); font-weight:700;"><i class="fa-solid fa-cube"></i> Muestra <span class="lbl-n-muestra">#__NUM__</span></h3>
                <button type="button" class="btn-action-mini btn-del btn-eliminar-muestra-bloque" style="padding: 6px 12px;" onclick="eliminarMuestraBloque(this)"><i class="fa-solid fa-trash"></i> Eliminar Muestra</button>
            </div>

            <!-- Campos de Identificación específicos de la muestra -->
            <div class="grid-2" style="margin-bottom: 15px;">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Código de Campo / Referencia (Consecutivo)</label>
                    <input type="text" class="form-control input-codigo-campo" required placeholder="Ej: MC-001-2026" value="<?= htmlspecialchars($codigoCampoAuto ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-weight: 600; color: #103487; background-color: #f0f9ff; border-color: #7dd3fc;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">ID del Cilindro / Especímen Individual</label>
                    <input type="text" class="form-control input-id-cilindro" placeholder="Ej: MC10 - MC12 (3 Cilindros)">
                </div>
            </div>

            <!-- Datos técnicos del Lote -->
            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Identificación del Lote / Elemento Estructural</label>
                    <input type="text" class="form-control input-nombre-lote" required placeholder="Ej: Columnas Eje C - Nivel 2 (MC10-MC12)">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Moldeo / Fabricación (T0)</label>
                    <input type="date" class="form-control input-fecha-moldeo" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div style="margin-top: 12px; font-size: 12px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px; margin-bottom: 15px;">
                <i class="fa-solid fa-sliders"></i> Parámetros de Especificación vs. Medidos en Campo
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #0369a1;">Resistencia de Diseño Objetivo</label>
                    <input type="text" class="form-control input-diseno-resistencia" required placeholder="Ej: 3000 PSI / 210 kg/cm²" style="border-color: #93c5fd;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Revenimiento (Slump) Medido en Campo</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="number" step="0.01" placeholder="Pulgadas" class="form-control input-revenimiento-in" style="width: 50%;">
                        <input type="number" step="0.01" placeholder="CM" class="form-control input-revenimiento-cm" style="width: 50%;">
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Temperatura Concreto en Obra (°C)</label>
                    <input type="number" step="0.1" placeholder="Grados Celsius" class="form-control input-temperatura-c">
                </div>
            </div>

            <div class="grid-2" style="margin-top: 10px;">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Procedimiento de Muestreo</label>
                    <input type="text" class="form-control input-procedimiento-muestreo" value="ASTM C172 / CYCSA-PE-07">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Servicio Cotizado Asociado</label>
                    <select required class="form-control select-detalle-cotizacion" style="background-color: white;" onchange="toggleEdadesSection(this)">
                        <option value="">-- Seleccione el servicio facturado --</option>
                        <?php foreach ($servicios as $s): 
                            $cantidadFacturada = max(1, (int)($s['cantidad_facturada'] ?? $s['cantidad'] ?? 1));
                            $recibidos = (int)($s['total_recibidos'] ?? 0);
                            $yaRecibido = (!empty($s['ya_recibido']) && $s['id'] != ($idDetalle ?? 0));
                            $disponible = max(0, $cantidadFacturada - $recibidos);
                            
                            $lblEstado = '';
                            if ($recibidos > 0) {
                                if ($yaRecibido) {
                                    $lblEstado = ' (✔ COMPLETADO: ' . $recibidos . '/' . $cantidadFacturada . ' - Muestra: ' . $s['codigo_muestra'] . ')';
                                } else {
                                    $lblEstado = ' (PARTICULAR: Recibidas ' . $recibidos . '/' . $cantidadFacturada . ' Muestras)';
                                }
                            } else {
                                $lblEstado = ' (PENDIENTE DE INGRESAR)';
                            }
                        ?>
                            <option value="<?= $s['id'] ?>" 
                                    data-formato-id="<?= $s['formato_id'] ?>" 
                                    data-cantidad-disponible="<?= $disponible ?>"
                                    <?= ($s['id'] == ($idDetalle ?? 0)) ? 'selected' : '' ?>
                                    <?= $yaRecibido ? 'disabled style="color: #94a3b8; background-color: #f1f5f9;"' : '' ?>>
                                <?= htmlspecialchars($s['codigo_servicio'] ? $s['codigo_servicio'] . ' - ' : '', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars($s['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?> [Cant. Facturada: <?= $cantidadFacturada ?>]<?= $lblEstado ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- SECCIÓN EDADES DE ROTURA (ESPECÍMENES) -->
            <div class="seccion-edades-rotura" style="margin-top: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;">
                    <h5 style="font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--cycsa-azul); margin: 0;"><i class="fa-solid fa-calendar-days"></i> Programación de Edades de Rotura</h5>
                    <button type="button" class="btn-action-mini btn-add" onclick="agregarFilaEdad(this)"><i class="fa-solid fa-plus"></i> Agregar Edad Personalizada</button>
                </div>
                
                <div class="contenedor-edades">
                    <!-- Edades por defecto: 3, 7, 28, 56 -->
                    <div class="age-row">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px;">Edad de Rotura (Días):</div>
                        <input type="number" required class="form-control edad-dia-input" style="width: 70px;" value="3" min="1">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                        <input type="text" required placeholder="Ej: A" class="form-control edad-ident-input" style="flex: 1;" value="A">
                        <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    <div class="age-row">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px;">Edad de Rotura (Días):</div>
                        <input type="number" required class="form-control edad-dia-input" style="width: 70px;" value="7" min="1">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                        <input type="text" required placeholder="Ej: B" class="form-control edad-ident-input" style="flex: 1;" value="B">
                        <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    <div class="age-row">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px;">Edad de Rotura (Días):</div>
                        <input type="number" required class="form-control edad-dia-input" style="width: 70px;" value="28" min="1">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                        <input type="text" required placeholder="Ej: C, D" class="form-control edad-ident-input" style="flex: 1;" value="C, D">
                        <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                    <div class="age-row">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px;">Edad de Rotura (Días):</div>
                        <input type="number" required class="form-control edad-dia-input" style="width: 70px;" value="56" min="1">
                        <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                        <input type="text" required placeholder="Ej: Testigo" class="form-control edad-ident-input" style="flex: 1;" value="Testigo">
                        <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>

        </div>
    </template>

    <!-- BOTONES DE ACCIÓN -->
    <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; margin-bottom: 40px;">
        <a href="/Cycsa/publico/operaciones" class="form-control" style="text-decoration: none; cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b; text-align: center; width: 140px; padding: 12px 0;">Cancelar</a>
        <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; width: 220px; padding: 12px 0;"><i class="fa-solid fa-save"></i> Registrar Muestras</button>
    </div>
</form>

<script>
    function cargarMatrizDirectaProducto(idDetalle) {
        const contenedor = document.getElementById('contenedor-bloques-muestras');
        let bloques = contenedor.querySelectorAll('.muestra-bloque');
        let bloqueTarget = null;

        // Buscar si ya existe un bloque asignado a este idDetalle o usar uno vacío
        for (let b of bloques) {
            const sel = b.querySelector('.select-detalle-cotizacion');
            if (sel && (parseInt(sel.value) === idDetalle || !sel.value)) {
                bloqueTarget = b;
                break;
            }
        }

        // Si no hay bloque disponible, crear uno nuevo haciendo clic en el botón de agregar
        if (!bloqueTarget) {
            document.getElementById('btn-agregar-muestra-bloque').click();
            bloques = contenedor.querySelectorAll('.muestra-bloque');
            bloqueTarget = bloques[bloques.length - 1];
        }

        if (bloqueTarget) {
            const selectServicio = bloqueTarget.querySelector('.select-detalle-cotizacion');
            if (selectServicio) {
                selectServicio.value = idDetalle;
                selectServicio.dispatchEvent(new Event('change'));
            }

            bloqueTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            const inputNombreLote = bloqueTarget.querySelector('.input-nombre-lote');
            if (inputNombreLote) {
                setTimeout(() => inputNombreLote.focus(), 300);
            }
        }
    }

    let muestraIndexCount = 0;
    let siguienteConsecutivoMuestra = <?= $siguienteConsecutivo ?? 1 ?>;
    const anioActualMuestra = <?= date('Y') ?>;

    // Función para inicializar el select premium con buscador para un elemento específico
    function inicializarSelectPremium(select) {
        if (!select) return;
        if (select.dataset.premiumInitialized === 'true') return;
        select.dataset.premiumInitialized = 'true';

        // Ocultar select original
        select.style.display = 'none';

        // Crear contenedor principal
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';
        
        // Crear disparador (botón visible)
        const trigger = document.createElement('div');
        trigger.className = 'custom-select-trigger';
        trigger.tabIndex = 0;
        trigger.innerHTML = '<span class="trigger-label" style="font-weight: 500;">-- Seleccione el servicio facturado --</span> <i class="fa-solid fa-chevron-down" style="font-size: 11px; color: #64748b;"></i>';
        
        // Crear dropdown contenedor
        const dropdown = document.createElement('div');
        dropdown.className = 'custom-select-dropdown';
        
        // Crear caja de búsqueda
        const searchBox = document.createElement('div');
        searchBox.className = 'custom-select-search-box';
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Buscar servicio o norma (ej: Concreto, ASTM)...';
        searchInput.className = 'custom-select-search';
        searchBox.appendChild(searchInput);
        dropdown.appendChild(searchBox);
        
        // Lista de opciones
        const optionsList = document.createElement('div');
        optionsList.className = 'custom-select-options-list';
        dropdown.appendChild(optionsList);
        
        wrapper.appendChild(trigger);
        wrapper.appendChild(dropdown);
        select.parentNode.insertBefore(wrapper, select);

        // Rellenar opciones personalizadas
        const originalOptions = Array.from(select.options);
        
        originalOptions.forEach((opt, idx) => {
            if (idx === 0) return; // Omitir el placeholder inicial
            
            const isOptDisabled = opt.disabled;
            const formatoId = opt.getAttribute('data-formato-id');
            const labelText = opt.text;
            const isSelected = opt.selected;
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'custom-select-option';
            if (isOptDisabled) optionDiv.classList.add('disabled');
            if (isSelected) {
                optionDiv.classList.add('selected');
                trigger.querySelector('.trigger-label').innerText = labelText;
                trigger.querySelector('.trigger-label').style.fontWeight = '600';
            }
            
            optionDiv.dataset.value = opt.value;
            optionDiv.dataset.formatoId = formatoId;
            
            let badgeHtml = '';
            if (isOptDisabled) {
                badgeHtml = '<span class="custom-select-badge badge-locked"><i class="fa-solid fa-lock"></i> Recibido</span>';
            } else {
                badgeHtml = '<span class="custom-select-badge badge-available"><i class="fa-solid fa-circle-check"></i> Disponible</span>';
            }
            
            optionDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-flask" style="color: var(--cycsa-azul); font-size: 12.5px;"></i>
                    <span style="font-weight: 500;">${labelText}</span>
                </div>
                ${badgeHtml}
            `;
            
            optionDiv.addEventListener('click', (e) => {
                if (isOptDisabled) return;

                // --- VALIDACIÓN DE CANTIDAD DISPONIBLE / DUPLICADOS ---
                const selectedValue = opt.value;
                const disponible = parseInt(opt.getAttribute('data-cantidad-disponible')) || 0;
                
                // Contar cuántos bloques de muestra tienen seleccionado ESTE mismo valor actualmente
                let yaSeleccionados = 0;
                document.querySelectorAll('.select-detalle-cotizacion').forEach(s => {
                    if (s !== select && s.value === selectedValue) {
                        yaSeleccionados++;
                    }
                });

                if (yaSeleccionados >= disponible) {
                    alert(`No puedes agregar más muestras para este ensayo. Has alcanzado el límite disponible contratado (${disponible} muestras).`);
                    return;
                }
                
                // Desmarcar anteriores y marcar actual
                optionsList.querySelectorAll('.custom-select-option').forEach(o => o.classList.remove('selected'));
                optionDiv.classList.add('selected');
                
                // Sincronizar select original y disparar evento de cambio
                select.value = opt.value;
                select.dispatchEvent(new Event('change'));
                
                // Actualizar disparador visual
                trigger.querySelector('.trigger-label').innerText = labelText;
                trigger.querySelector('.trigger-label').style.fontWeight = '600';
                
                // Cerrar
                dropdown.style.display = 'none';
                trigger.classList.remove('active');
            });
            
            optionsList.appendChild(optionDiv);
        });

        // Evento toggle al presionar disparador
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdown.style.display === 'block';
            
            // Cerrar otros dropdowns si hubieran
            dropdown.style.display = isOpen ? 'none' : 'block';
            trigger.classList.toggle('active', !isOpen);
            
            if (!isOpen) {
                searchInput.value = '';
                optionsList.querySelectorAll('.custom-select-option').forEach(o => o.style.display = 'flex');
                setTimeout(() => searchInput.focus(), 50);
            }
        });

        // Soporte para navegación de teclado en el trigger
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                trigger.click();
            }
        });

        // Filtrado en tiempo real
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            optionsList.querySelectorAll('.custom-select-option').forEach(optionDiv => {
                const text = optionDiv.innerText.toLowerCase();
                if (text.includes(query)) {
                    optionDiv.style.display = 'flex';
                } else {
                    optionDiv.style.display = 'none';
                }
            });
        });

        // Cerrar al hacer clic fuera del control
        window.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
                trigger.classList.remove('active');
            }
        });
    }

    function toggleEdadesSection(select) {
        if (!select) return;
        const selectedOption = select.options[select.selectedIndex];
        const formatoId = selectedOption ? selectedOption.getAttribute('data-formato-id') : '';
        
        const bloque = select.closest('.muestra-bloque');
        const agesSection = bloque.querySelector('.seccion-edades-rotura');
        
        // Formato ID 17 = Resistencia de Concreto, 9 = Flexión, 20 = Mortero, 21 = Núcleo, 10 = Bloques, 16 = Adoquines, 18 = Ladrillo, 19 = Esclerómetro
        const esConcreto = (formatoId === '17' || formatoId === '9' || formatoId === '20' || formatoId === '21' || formatoId === '10' || formatoId === '16' || formatoId === '18' || formatoId === '19');
        
        agesSection.style.display = 'block';
        
        if (esConcreto) {
            bloque.querySelector('.input-nombre-lote').required = true;
            bloque.querySelector('.input-fecha-moldeo').required = true;
            bloque.querySelector('.input-diseno-resistencia').required = true;
            
            agesSection.querySelectorAll('input').forEach(i => {
                i.disabled = false;
                i.required = true;
            });
        } else {
            bloque.querySelector('.input-nombre-lote').required = false;
            bloque.querySelector('.input-fecha-moldeo').required = false;
            bloque.querySelector('.input-diseno-resistencia').required = false;
            
            agesSection.querySelectorAll('input').forEach(i => {
                i.disabled = false;
                i.required = false;
            });
        }
    }

    function agregarMuestraBloque() {
        // Ocultar placeholder de estado vacío si existe
        const placeholder = document.getElementById('empty-muestras-placeholder');
        if (placeholder) placeholder.style.display = 'none';

        muestraIndexCount++;
        const contenedor = document.getElementById('contenedor-bloques-muestras');
        
        // Instanciar desde el <template>
        const template = document.getElementById('template-muestra-bloque');
        const clon = template.content.cloneNode(true).querySelector('.muestra-bloque');
        
        // Configurar index y número
        clon.dataset.index = muestraIndexCount - 1;
        clon.querySelector('.lbl-n-muestra').innerText = '#' + muestraIndexCount;
        
        // Generar y asignar código correlativo dinámico para que no se repitan
        const nextCode = 'MC-' + String(siguienteConsecutivoMuestra).padStart(3, '0') + '-' + anioActualMuestra;
        clon.querySelector('.input-codigo-campo').value = nextCode;
        clon.querySelector('.input-id-cilindro').value = nextCode;
        siguienteConsecutivoMuestra++;
        
        // Inicializar select original y buscador premium
        const select = clon.querySelector('.select-detalle-cotizacion');
        contenedor.appendChild(clon);
        
        // Inicializar el buscador premium para este nuevo select
        inicializarSelectPremium(select);
        toggleEdadesSection(select);
        
        return clon;
    }

    function eliminarMuestraBloque(button) {
        const bloque = button.closest('.muestra-bloque');
        
        // Obtener el código de campo para rehabilitar el botón superior si corresponde
        const codigoCampoEliminado = bloque.querySelector('.input-codigo-campo').value.trim();
        
        bloque.remove();
        
        // Re-indexar etiquetas visuales de los bloques restantes
        const bloques = document.querySelectorAll('.muestra-bloque');
        muestraIndexCount = bloques.length;
        
        if (muestraIndexCount === 0) {
            // Mostrar placeholder de estado vacío
            const placeholder = document.getElementById('empty-muestras-placeholder');
            if (placeholder) placeholder.style.display = 'block';
        } else {
            bloques.forEach((b, idx) => {
                b.dataset.index = idx;
                b.querySelector('.lbl-n-muestra').innerText = '#' + (idx + 1);
            });
        }

        // Rehabilitar botón superior de muestras declaradas
        if (codigoCampoEliminado !== '') {
            rehabilitarBotonCargarDeclarada(codigoCampoEliminado);
        }
    }

    function agregarFilaEdad(button) {
        const bloque = button.closest('.muestra-bloque');
        const container = bloque.querySelector('.contenedor-edades');
        const newRow = document.createElement('div');
        newRow.className = 'age-row';
        newRow.innerHTML = `
            <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px;">Edad de Rotura (Días):</div>
            <input type="number" required class="form-control edad-dia-input" style="width: 70px;" min="1">
            <div style="font-weight: 600; font-size: 12.5px; color: #475569; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
            <input type="text" required placeholder="Ej: E" class="form-control edad-ident-input" style="flex: 1;">
            <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
        `;
        container.appendChild(newRow);
    }

    function eliminarFilaEdad(button) {
        const row = button.closest('.age-row');
        const container = row.closest('.contenedor-edades');
        if (container.children.length > 1) {
            row.remove();
        } else {
            alert('Debe tener al menos una edad de rotura registrada para la muestra.');
        }
    }

    function rehabilitarBotonCargarDeclarada(nombreMuestra) {
        // Buscar botones que tengan la función onclick llamando a esa muestra
        const botones = document.querySelectorAll('.btn-cargar-decl');
        botones.forEach(btn => {
            if (btn.getAttribute('onclick').includes("'" + nombreMuestra + "'")) {
                btn.disabled = false;
                btn.style.backgroundColor = 'var(--cycsa-azul)';
                btn.style.borderColor = 'var(--cycsa-azul)';
                btn.style.color = 'white';
                btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Cargar Muestra';
            }
        });
    }

    // Inicializar al cargar para el valor preseleccionado
    document.addEventListener('DOMContentLoaded', () => {
        // En esta nueva versión no se inicializa ninguna muestra por defecto.
        
        // Listener del botón agregar muestra bloque
        document.getElementById('btn-agregar-muestra-bloque').addEventListener('click', () => {
            agregarMuestraBloque();
        });
        
        // Modificar el action del formulario para procesar antes del submit
        const form = document.querySelector('form');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const bloques = document.querySelectorAll('.muestra-bloque');
            
            if (bloques.length === 0) {
                alert("Debe agregar al menos una muestra para registrar la recepción.");
                return;
            }

            const muestras = [];
            let valid = true;
            
            bloques.forEach((bloque) => {
                const selectDet = bloque.querySelector('.select-detalle-cotizacion');
                const idDetalleCotizacion = selectDet.value;
                
                if (!idDetalleCotizacion) {
                    alert("Por favor, seleccione el servicio cotizado asociado para todas las muestras.");
                    selectDet.focus();
                    valid = false;
                    return;
                }
                
                const edades_dias = [];
                const edades_identificadores = [];
                
                bloque.querySelectorAll('.edad-dia-input').forEach(input => {
                    edades_dias.push(parseInt(input.value) || 0);
                });
                bloque.querySelectorAll('.edad-ident-input').forEach(input => {
                    edades_identificadores.push(input.value.trim());
                });

                muestras.push({
                    codigo_campo: bloque.querySelector('.input-codigo-campo').value.trim(),
                    id_cilindro: bloque.querySelector('.input-id-cilindro').value.trim(),
                    nombre_lote: bloque.querySelector('.input-nombre-lote').value.trim(),
                    fecha_moldeo: bloque.querySelector('.input-fecha-moldeo').value,
                    diseno_resistencia: bloque.querySelector('.input-diseno-resistencia').value.trim(),
                    revenimiento_in: bloque.querySelector('.input-revenimiento-in').value.trim() ? parseFloat(bloque.querySelector('.input-revenimiento-in').value) : null,
                    revenimiento_cm: bloque.querySelector('.input-revenimiento-cm').value.trim() ? parseFloat(bloque.querySelector('.input-revenimiento-cm').value) : null,
                    temperatura_c: bloque.querySelector('.input-temperatura-c').value.trim() ? parseFloat(bloque.querySelector('.input-temperatura-c').value) : null,
                    procedimiento_muestreo: bloque.querySelector('.input-procedimiento-muestreo').value.trim(),
                    id_detalle_cotizacion: parseInt(idDetalleCotizacion),
                    edades_dias: edades_dias,
                    edades_identificadores: edades_identificadores
                });
            });
            
            if (!valid) return;
            
            // Asignar el JSON al campo oculto
            document.getElementById('muestras_recibidas_json').value = JSON.stringify(muestras);
            
            // Deshabilitar controles para evitar envíos duplicados de strings nativos
            bloques.forEach(b => {
                b.querySelectorAll('input, select').forEach(el => {
                    el.disabled = true; 
                });
            });
            
            // Continuar con el submit
            form.submit();
        });
    });

    // Autocompletar datos de muestra declarada en la O/S
    function cargarMuestraDeclarada(nombre, descripcion, info, btnId) {
        // Validar si esta muestra declarada ya ha sido agregada
        let yaExiste = false;
        document.querySelectorAll('.muestra-bloque').forEach(bloque => {
            if (bloque.querySelector('.input-codigo-campo').value.trim() === nombre) {
                yaExiste = true;
            }
        });

        if (yaExiste) {
            alert("Esta muestra ya ha sido cargada en el formulario.");
            return;
        }

        // Agregar un nuevo bloque de muestra
        const bloqueTarget = agregarMuestraBloque();
        
        // Rellenar datos
        bloqueTarget.querySelector('.input-codigo-campo').value = nombre;
        
        let loteText = descripcion;
        if (info && info.trim() !== '') {
            loteText += ' - ' + info;
        }
        bloqueTarget.querySelector('.input-nombre-lote').value = loteText;
        bloqueTarget.querySelector('.input-id-cilindro').value = nombre;
        
        // Deshabilitar visualmente el botón superior
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.disabled = true;
            btn.style.backgroundColor = '#dcfce7';
            btn.style.borderColor = '#bbf7d0';
            btn.style.color = '#15803d';
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Cargada en Formulario';
        }

        // Foco al campo cargado
        const targetInput = bloqueTarget.querySelector('.input-codigo-campo');
        targetInput.focus();
        targetInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
</script>
