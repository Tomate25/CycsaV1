<?php
// captura_matriz.php - Pantalla completa de captura de matriz técnica LIMS con Muestras Auto-Seteadas y Bloqueadas
?>
<style>
    :root {
        --matriz-primary: #103487;
        --matriz-primary-hover: #0b2563;
        --matriz-accent: #0284c7;
        --matriz-bg: #f8fafc;
        --matriz-card-bg: #ffffff;
        --matriz-text-main: #0f172a;
        --matriz-text-muted: #64748b;
        --matriz-border: #e2e8f0;
    }

    .matriz-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    /* Header Principal Top Bar */
    .matriz-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .btn-matriz-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        transition: all 0.2s ease;
    }
    .btn-matriz-back:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }

    .btn-matriz-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #103487 0%, #0284c7 100%);
        color: #ffffff;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(16, 52, 135, 0.25);
        transition: all 0.2s ease;
    }
    .btn-matriz-primary:hover {
        background: linear-gradient(135deg, #0b2563 0%, #0369a1 100%);
        box-shadow: 0 6px 18px rgba(16, 52, 135, 0.35);
        transform: translateY(-1px);
    }

    .btn-matriz-secondary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f0f9ff;
        color: #0369a1;
        border: 1px solid #bae6fd;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-matriz-secondary:hover {
        background: #e0f2fe;
        color: #0284c7;
        border-color: #7dd3fc;
        transform: translateY(-1px);
    }

    /* Banner Informativo */
    .matriz-hero-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
    }
    .matriz-hero-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        background: linear-gradient(180deg, #103487 0%, #0284c7 100%);
    }

    .matriz-hero-title {
        font-family: 'Outfit', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .matriz-pills-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .pill-item {
        font-family: monospace;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .pill-os { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .pill-formato { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .pill-astm { background: #fffbeb; color: #b45309; border: 1px solid #fef3c7; }

    .matriz-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 8px;
        padding: 14px 18px;
        margin-top: 15px;
    }
    .meta-cell { display: flex; flex-direction: column; gap: 2px; }
    .meta-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.4px; }
    .meta-value { font-size: 13.5px; font-weight: 600; color: #1e293b; }

    /* Data Table Card */
    .matriz-data-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        margin-bottom: 25px;
    }

    .matriz-table-container {
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
    }

    .tabla-enterprise {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .tabla-enterprise th {
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 12px 14px;
        border-bottom: 2px solid #cbd5e1;
        text-align: left;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    .tabla-enterprise td {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .tabla-enterprise tr:nth-child(even) { background-color: #fcfcfd; }
    .tabla-enterprise tr:hover { background-color: #f0f9ff; }

    .input-cell {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 13.5px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
        transition: all 0.15s ease-in-out;
        box-sizing: border-box;
    }
    .input-cell:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        background: #ffffff;
    }

    .input-cell-locked {
        background: #f1f5f9 !important;
        color: #334155 !important;
        font-weight: 700 !important;
        border-color: #cbd5e1 !important;
        cursor: not-allowed;
    }

    .btn-row-delete {
        background: #ffffff;
        color: #ef4444;
        border: 1px solid #fca5a5;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-row-delete:hover {
        background: #fef2f2;
        color: #b91c1c;
        border-color: #f87171;
    }
</style>

<div class="matriz-wrapper">
    <!-- BARRA SUPERIOR DE NAVEGACIÓN Y ACCIONES -->
    <div class="matriz-top-bar">
        <a href="/Cycsa/publico/operaciones" class="btn-matriz-back">
            <i class="fa-solid fa-arrow-left"></i> Volver a Operaciones
        </a>

        <div style="display: flex; gap: 12px;">
            <button type="button" onclick="agregarFilaMatriz()" class="btn-matriz-secondary">
                <i class="fa-solid fa-plus-circle"></i> Agregar Fila
            </button>
            <button type="button" onclick="guardarMatrizFullSubmit()" class="btn-matriz-primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Matriz del Producto
            </button>
        </div>
    </div>

    <!-- TARJETA HERÓICA DE IDENTIFICACIÓN -->
    <div class="matriz-hero-card">
        <div class="matriz-pills-row">
            <span class="pill-item pill-os"><i class="fa-solid fa-file-contract"></i> O/S: <?= htmlspecialchars($detalle['codigo_os'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="pill-item pill-formato"><i class="fa-solid fa-file-code"></i> <?= htmlspecialchars($detalle['formato_nombre'] ?? 'Matriz Técnica', ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($detalle['codigo_documento'])): ?>
                <span class="pill-item pill-formato" style="background:#e2e8f0; color:#334155;"><?= htmlspecialchars($detalle['codigo_documento'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <?php if (!empty($detalle['norma_astm'])): ?>
                <span class="pill-item pill-astm"><i class="fa-solid fa-microscope"></i> Norma: <?= htmlspecialchars($detalle['norma_astm'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <h2 class="matriz-hero-title">
            <i class="fa-solid fa-vial-circle-check" style="color: #0284c7;"></i>
            <?= htmlspecialchars($detalle['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>
        </h2>

        <div class="matriz-meta-grid">
            <div class="meta-cell">
                <span class="meta-label">Cliente / Razón Social</span>
                <span class="meta-value"><?= htmlspecialchars($detalle['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="meta-cell">
                <span class="meta-label">Nombre del Proyecto</span>
                <span class="meta-value"><?= htmlspecialchars($detalle['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="meta-cell">
                <span class="meta-label">Técnico Muestreador</span>
                <span class="meta-value"><?= !empty($detalle['tecnico_muestreo']) ? htmlspecialchars($detalle['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') : 'Asignado en campo' ?></span>
            </div>
            <div class="meta-cell">
                <span class="meta-label">Muestras de la Hoja de Servicio</span>
                <span class="meta-value" style="color:#0284c7; font-weight:700;"><?= count($muestrasSeteadas ?? []) ?> Muestras Declaradas</span>
            </div>
        </div>
    </div>

    <!-- DATA ENTRY CARD -->
    <div class="matriz-data-card">
        <form id="form-matriz-completa" method="POST" action="/Cycsa/publico/operaciones/guardar-matriz-producto" onsubmit="prepararEnvioMatriz(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" value="<?= $detalle['id'] ?>">
            <input type="hidden" name="resultados_json" id="input_resultados_json" value="">

            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #0369a1; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-lock fa-lg" style="color:#0284c7;"></i>
                <span><strong>MUESTRAS VINCULADAS Y PROTEGIDAS (CYCSA-RT-FM-13):</strong> Los campos <strong>Código laboratorio</strong> y <strong>Nombre muestra</strong> están pre-seteados y bloqueados automáticamente desde la Hoja de Servicio. Ingrese únicamente los datos y lecturas técnicas restantes.</span>
            </div>

            <div class="matriz-table-container">
                <table class="tabla-enterprise">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">N°</th>
                            <?php foreach ($columnas as $col): ?>
                                <th>
                                    <?= htmlspecialchars($col, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (in_array(mb_strtolower(trim($col)), ['código laboratorio', 'código de la muestra', 'muestra', 'nombre muestra', 'descripción'])): ?>
                                        <i class="fa-solid fa-lock" style="color:#0284c7; margin-left:4px; font-size:11px;" title="Bloqueado por Hoja de Servicio"></i>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                            <th style="width: 50px; text-align: center;">Borrar</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-matriz-full">
                        <!-- Filas de inputs -->
                    </tbody>
                </table>
            </div>

            <!-- BARRA DE ACCIÓN INFERIOR -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 18px;">
                <button type="button" onclick="agregarFilaMatriz()" class="btn-matriz-secondary">
                    <i class="fa-solid fa-plus-circle"></i> Agregar Fila de Muestra
                </button>
                <div style="display: flex; gap: 12px;">
                    <a href="/Cycsa/publico/operaciones" class="btn-matriz-back">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-matriz-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Matriz del Producto
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const COLUMNAS_MATRIZ = <?= json_encode($columnas, JSON_UNESCAPED_UNICODE) ?>;
    const DATOS_INICIALES = <?= !empty($detalle['resultados_json']) ? $detalle['resultados_json'] : '[]' ?>;
    const MUESTRAS_SETEADAS = <?= json_encode($muestrasSeteadas ?? [], JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('tbody-matriz-full');
        tbody.innerHTML = '';

        if (Array.isArray(DATOS_INICIALES) && DATOS_INICIALES.length > 0) {
            DATOS_INICIALES.forEach(rowObj => {
                agregarFilaMatriz(rowObj);
            });
        } else if (Array.isArray(MUESTRAS_SETEADAS) && MUESTRAS_SETEADAS.length > 0) {
            MUESTRAS_SETEADAS.forEach(m => {
                let initObj = {};
                COLUMNAS_MATRIZ.forEach(col => {
                    const cLower = col.toLowerCase().trim();
                    if (cLower === 'código laboratorio' || cLower === 'código de la muestra' || cLower === 'muestra') {
                        initObj[col] = m.codigo_lab;
                    } else if (cLower === 'nombre muestra' || cLower === 'descripción') {
                        initObj[col] = m.nombre_muestra;
                    } else {
                        initObj[col] = '';
                    }
                });
                agregarFilaMatriz(initObj);
            });
        } else {
            agregarFilaMatriz();
        }
    });

    function reindexarFilas() {
        const tbody = document.getElementById('tbody-matriz-full');
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((tr, index) => {
            const numCell = tr.querySelector('.row-index-cell');
            if (numCell) numCell.innerText = index + 1;
        });
    }

    function agregarFilaMatriz(data = {}) {
        const tbody = document.getElementById('tbody-matriz-full');
        const tr = document.createElement('tr');

        // Numbering cell
        const tdNum = document.createElement('td');
        tdNum.className = 'row-index-cell';
        tdNum.style.textAlign = 'center';
        tdNum.style.fontWeight = '700';
        tdNum.style.color = '#64748b';
        tdNum.style.fontSize = '12px';
        tdNum.innerText = tbody.querySelectorAll('tr').length + 1;
        tr.appendChild(tdNum);

        COLUMNAS_MATRIZ.forEach(col => {
            const td = document.createElement('td');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'input-cell';
            input.value = data[col] !== undefined ? data[col] : '';
            input.dataset.col = col;
            input.placeholder = col;

            const cLower = col.toLowerCase().trim();
            const esColumnaCodigo = (cLower === 'código laboratorio' || cLower === 'código de la muestra' || cLower === 'muestra');
            const esColumnaNombre = (cLower === 'nombre muestra' || cLower === 'descripción');

            // BLOQUEAR CÓDIGO Y NOMBRE SI ESTÁN SETEADOS POR LA HOJA DE SERVICIO
            if (esColumnaCodigo || esColumnaNombre) {
                input.readOnly = true;
                input.classList.add('input-cell-locked');
                input.title = "Campo pre-seteado desde la Hoja de Servicio CYCSA-RT-FM-13 (Protegido)";
            }

            td.appendChild(input);
            tr.appendChild(td);
        });

        const tdAccion = document.createElement('td');
        tdAccion.style.textAlign = 'center';
        tdAccion.innerHTML = `
            <button type="button" onclick="eliminarFilaMatriz(this)" class="btn-row-delete" title="Eliminar fila">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        `;
        tr.appendChild(tdAccion);

        tbody.appendChild(tr);
        reindexarFilas();
    }

    function eliminarFilaMatriz(btn) {
        const tbody = document.getElementById('tbody-matriz-full');
        if (tbody.querySelectorAll('tr').length <= 1) {
            alert('Debe mantener al menos una fila en la matriz del producto.');
            return;
        }
        const tr = btn.closest('tr');
        if (tr) {
            tr.remove();
            reindexarFilas();
        }
    }

    function guardarMatrizFullSubmit() {
        document.getElementById('form-matriz-completa').submit();
    }

    function prepararEnvioMatriz(e) {
        const tbody = document.getElementById('tbody-matriz-full');
        const rows = tbody.querySelectorAll('tr');
        const resultData = [];

        rows.forEach(tr => {
            const inputs = tr.querySelectorAll('input.input-cell');
            let rowObj = {};
            let hasValue = false;

            inputs.forEach(input => {
                const col = input.dataset.col;
                const val = input.value.trim();
                rowObj[col] = val;
                if (val !== '') hasValue = true;
            });

            if (hasValue) {
                resultData.push(rowObj);
            }
        });

        document.getElementById('input_resultados_json').value = JSON.stringify(resultData);
    }
</script>
