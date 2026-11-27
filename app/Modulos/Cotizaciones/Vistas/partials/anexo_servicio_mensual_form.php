<?php
/**
 * Partial reutilizable: Editor del Anexo Técnico – Alcance del Servicio Mensual
 * Variables esperadas:
 *  - bool $cotEsMensualForm (true si la cotización está en modalidad mensual)
 *  - array $anexoMensualData (salida de AnexoServicioMensualServicio::decodificar())
 *  - bool $anexoMensualIncluido (true si incluir_anexo_tecnico está activo)
 */
$cotEsMensualForm = !empty($cotEsMensualForm);
$anexoMensualData = is_array($anexoMensualData ?? null)
    ? $anexoMensualData
    : \Cycsa\Modulos\Cotizaciones\Servicios\AnexoServicioMensualServicio::plantillaPorDefecto();
$anexoMensualIncluido = isset($anexoMensualIncluido) ? (bool)$anexoMensualIncluido : $cotEsMensualForm;
$radPuntosTexto = implode("\n", is_array($anexoMensualData['seguridad_radiologica_puntos'] ?? null)
    ? $anexoMensualData['seguridad_radiologica_puntos']
    : []);
$ensayosLista = is_array($anexoMensualData['ensayos'] ?? null) ? $anexoMensualData['ensayos'] : [];
$totalEnsActivos = count(array_filter($ensayosLista, static fn($e): bool => is_array($e) && (!array_key_exists('incluido', $e) || !empty($e['incluido']))));
?>
<!-- ANEXO TÉCNICO – ALCANCE DEL SERVICIO MENSUAL (visible automáticamente en modalidad Mensual / Por Período) -->
<div id="cot_anexo_mensual_bloque" style="display: <?= $cotEsMensualForm ? 'block' : 'none' ?>; margin-top: 16px; background: #ffffff; border: 1.5px solid #1F3864; border-radius: 10px; overflow: hidden;">
    <div style="background: #1F3864; color: #ffffff; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <i class="fa-solid fa-file-contract" style="font-size: 16px;"></i>
            <div>
                <div style="font-weight: 700; font-size: 13.5px; letter-spacing: 0.3px;">ANEXO TÉCNICO – ALCANCE DEL SERVICIO MENSUAL</div>
                <div style="font-size: 11.5px; color: #dbeafe;">Se adjunta automáticamente en la vista oficial y en el PDF de la cotización mensual.</div>
            </div>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <label style="display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 12px; font-weight: 600; background: rgba(255,255,255,0.14); padding: 5px 10px; border-radius: 6px; cursor: pointer;">
                <input type="checkbox" name="incluir_anexo_tecnico" id="chk_incluir_anexo_mensual" value="1" <?= $anexoMensualIncluido ? 'checked' : '' ?> style="cursor: pointer;">
                Incluir Anexo en Cotización y PDF
            </label>
            <button type="button" id="btn_toggle_editor_anexo" onclick="toggleEditorAnexoMensual()" style="background: #ffffff; color: #1F3864; border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                <i class="fa-solid fa-pen-to-square"></i> Personalizar / Ver detalle
            </button>
        </div>
    </div>

    <div id="cot_anexo_mensual_resumen" style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 12.5px; color: #334155;">
        <div>
            <strong>Contenido oficial precargado:</strong> 1. Descripción del servicio · 2. Condiciones (<span id="cnt_anx_cond"><?= count($anexoMensualData['condiciones'] ?? []) ?></span> puntos) · 3. Lista de ensayos (<span id="cnt_anx_ens"><?= $totalEnsActivos ?></span>/<?= count($ensayosLista) ?> activos) · 4. Recursos e informes · 5. Responsabilidades del cliente · 6. Seguridad radiológica.
        </div>
    </div>

    <div id="cot_anexo_mensual_editor" style="display: none; padding: 18px 18px 14px 18px;">
        <input type="hidden" name="anexo_mensual[titulo]" value="<?= htmlspecialchars((string)($anexoMensualData['titulo'] ?? 'CYCSA  |  ANEXO TÉCNICO – ALCANCE MENSUAL'), ENT_QUOTES, 'UTF-8') ?>">

        <!-- 1. Descripción del servicio -->
        <div class="form-group">
            <label class="form-label" style="color: #1F3864; font-weight: 700;">1. Descripción del servicio</label>
            <textarea name="anexo_mensual[descripcion_servicio]" rows="2" class="form-control"><?= htmlspecialchars((string)($anexoMensualData['descripcion_servicio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <!-- 2. Condiciones del servicio -->
        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <label class="form-label" style="margin: 0; color: #1F3864; font-weight: 700;">2. Condiciones del servicio</label>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="agregarCondicionAnexoMensual()">+ Agregar condición</button>
            </div>
            <div style="overflow-x: auto;">
                <table class="tabla-detalles" id="tabla-anexo-condiciones" style="margin-bottom: 6px;">
                    <thead>
                        <tr>
                            <th style="width: 28%;">Concepto</th>
                            <th>Detalle / Condición</th>
                            <th style="width: 46px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($anexoMensualData['condiciones'] ?? []) as $cond): ?>
                        <tr>
                            <td><input type="text" name="anexo_mensual[cond_label][]" class="form-control" value="<?= htmlspecialchars((string)($cond['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                            <td><textarea name="anexo_mensual[cond_valor][]" rows="2" class="form-control"><?= htmlspecialchars((string)($cond['valor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></td>
                            <td style="text-align: center; vertical-align: middle;">
                                <button type="button" class="btn-remover" onclick="this.closest('tr').remove(); actualizarContadoresAnexoMensual();" title="Eliminar"><i class="fa-solid fa-xmark"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Ensayos incluidos en el servicio -->
        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                <label class="form-label" style="margin: 0; color: #1F3864; font-weight: 700;">3. Ensayos incluidos en el servicio</label>
                <div style="display: inline-flex; gap: 6px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="marcarTodosEnsayosAnexo(true)">Marcar todos</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="agregarEnsayoAnexoMensual()">+ Agregar ensayo</button>
                </div>
            </div>
            <input type="text" name="anexo_mensual[ensayos_intro]" class="form-control mb-2" value="<?= htmlspecialchars((string)($anexoMensualData['ensayos_intro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <div style="overflow-x: auto; max-height: 360px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table class="tabla-detalles" id="tabla-anexo-ensayos" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th style="width: 52px; text-align: center;">Incl.</th>
                            <th style="width: 24%;">Categoría</th>
                            <th>Ensayo incluido</th>
                            <th style="width: 46px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ensayosLista as $idxE => $ens):
                            $incE = !array_key_exists('incluido', $ens) || !empty($ens['incluido']);
                        ?>
                        <tr>
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="hidden" name="anexo_mensual[ensayo_incluido][<?= (int)$idxE ?>]" value="0">
                                <input type="checkbox" class="chk-ensayo-anexo" name="anexo_mensual[ensayo_incluido][<?= (int)$idxE ?>]" value="1" <?= $incE ? 'checked' : '' ?> onchange="actualizarContadoresAnexoMensual()" style="cursor: pointer; width: 16px; height: 16px;">
                            </td>
                            <td><input type="text" name="anexo_mensual[ensayo_categoria][<?= (int)$idxE ?>]" class="form-control" value="<?= htmlspecialchars((string)($ens['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                            <td><input type="text" name="anexo_mensual[ensayo_nombre][<?= (int)$idxE ?>]" class="form-control" value="<?= htmlspecialchars((string)($ens['ensayo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                            <td style="text-align: center; vertical-align: middle;">
                                <button type="button" class="btn-remover" onclick="this.closest('tr').remove(); actualizarContadoresAnexoMensual();" title="Eliminar"><i class="fa-solid fa-xmark"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Recursos asignados y entrega de informes -->
        <div class="form-group">
            <label class="form-label" style="color: #1F3864; font-weight: 700;">4. Recursos asignados y entrega de informes de ensayos</label>
            <textarea name="anexo_mensual[recursos_entrega]" rows="4" class="form-control"><?= htmlspecialchars((string)($anexoMensualData['recursos_entrega'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <!-- 5. Recursos y responsabilidades del cliente -->
        <div class="form-group">
            <label class="form-label" style="color: #1F3864; font-weight: 700;">5. Recursos y responsabilidades del cliente</label>
            <textarea name="anexo_mensual[responsabilidades_cliente]" rows="3" class="form-control"><?= htmlspecialchars((string)($anexoMensualData['responsabilidades_cliente'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <!-- 6. Condiciones de seguridad radiológica -->
        <div class="form-group" style="margin-bottom: 0; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                <label class="form-label" style="margin: 0; color: #1F3864; font-weight: 700;">6. Condiciones de seguridad radiológica (Densímetro nuclear)</label>
                <label style="display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 12.5px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="hidden" name="anexo_mensual[incluir_seguridad_radiologica]" value="0">
                    <input type="checkbox" name="anexo_mensual[incluir_seguridad_radiologica]" value="1" <?= (!array_key_exists('incluir_seguridad_radiologica', $anexoMensualData) || !empty($anexoMensualData['incluir_seguridad_radiologica'])) ? 'checked' : '' ?>>
                    Incluir cláusula de seguridad radiológica
                </label>
            </div>
            <textarea name="anexo_mensual[seguridad_radiologica_intro]" rows="2" class="form-control mb-2"><?= htmlspecialchars((string)($anexoMensualData['seguridad_radiologica_intro'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <label class="form-label" style="font-size: 11.5px; color: #64748b;">Condiciones mínimas (una por línea)</label>
            <textarea name="anexo_mensual[seguridad_radiologica_puntos_texto]" rows="5" class="form-control mb-2"><?= htmlspecialchars($radPuntosTexto, ENT_QUOTES, 'UTF-8') ?></textarea>
            <input type="text" name="anexo_mensual[seguridad_radiologica_cierre]" class="form-control" value="<?= htmlspecialchars((string)($anexoMensualData['seguridad_radiologica_cierre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>
</div>

<script>
    let _anxEnsayoSeq = <?= count($ensayosLista) + 1 ?>;

    function toggleEditorAnexoMensual() {
        const ed = document.getElementById('cot_anexo_mensual_editor');
        const btn = document.getElementById('btn_toggle_editor_anexo');
        if (!ed) return;
        const abrir = (ed.style.display === 'none' || ed.style.display === '');
        ed.style.display = abrir ? 'block' : 'none';
        if (btn) {
            btn.innerHTML = abrir
                ? '<i class="fa-solid fa-chevron-up"></i> Ocultar editor'
                : '<i class="fa-solid fa-pen-to-square"></i> Personalizar / Ver detalle';
        }
    }

    function actualizarContadoresAnexoMensual() {
        const cEl = document.getElementById('cnt_anx_cond');
        const eEl = document.getElementById('cnt_anx_ens');
        if (cEl) cEl.innerText = document.querySelectorAll('#tabla-anexo-condiciones tbody tr').length;
        if (eEl) eEl.innerText = document.querySelectorAll('#tabla-anexo-ensayos tbody .chk-ensayo-anexo:checked').length;
    }

    function agregarCondicionAnexoMensual() {
        const tbody = document.querySelector('#tabla-anexo-condiciones tbody');
        if (!tbody) return;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="anexo_mensual[cond_label][]" class="form-control" placeholder="Ej: Condición adicional:"></td>
            <td><textarea name="anexo_mensual[cond_valor][]" rows="2" class="form-control" placeholder="Detalle..."></textarea></td>
            <td style="text-align: center; vertical-align: middle;">
                <button type="button" class="btn-remover" onclick="this.closest('tr').remove(); actualizarContadoresAnexoMensual();" title="Eliminar"><i class="fa-solid fa-xmark"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        actualizarContadoresAnexoMensual();
    }

    function agregarEnsayoAnexoMensual() {
        const tbody = document.querySelector('#tabla-anexo-ensayos tbody');
        if (!tbody) return;
        const idx = _anxEnsayoSeq++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align: center; vertical-align: middle;">
                <input type="hidden" name="anexo_mensual[ensayo_incluido][${idx}]" value="0">
                <input type="checkbox" class="chk-ensayo-anexo" name="anexo_mensual[ensayo_incluido][${idx}]" value="1" checked onchange="actualizarContadoresAnexoMensual()" style="cursor: pointer; width: 16px; height: 16px;">
            </td>
            <td><input type="text" name="anexo_mensual[ensayo_categoria][${idx}]" class="form-control" placeholder="Ej: Suelo / Concreto"></td>
            <td><input type="text" name="anexo_mensual[ensayo_nombre][${idx}]" class="form-control" placeholder="Nombre del ensayo y norma ASTM..."></td>
            <td style="text-align: center; vertical-align: middle;">
                <button type="button" class="btn-remover" onclick="this.closest('tr').remove(); actualizarContadoresAnexoMensual();" title="Eliminar"><i class="fa-solid fa-xmark"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        actualizarContadoresAnexoMensual();
    }

    function marcarTodosEnsayosAnexo(estado) {
        document.querySelectorAll('#tabla-anexo-ensayos tbody .chk-ensayo-anexo').forEach(chk => {
            chk.checked = !!estado;
        });
        actualizarContadoresAnexoMensual();
    }
</script>
