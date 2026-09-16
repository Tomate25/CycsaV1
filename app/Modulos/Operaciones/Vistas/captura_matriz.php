<?php
// Vista de Captura de Matriz Técnica - Dinámica y Específica para los 21 Formatos CYCSA
$rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
$esLaboratorista = ($rolUsuario === 6);
$archivoMd = $detalle['archivo_markdown'] ?? '';
$isGranulometria = (strpos($archivoMd, 'granulometria') !== false || strpos($archivoMd, 'granulomnetria') !== false);

$formatosSchemaArray = json_decode($formatosSchemaJson ?? '{}', true);
$schemaInfo = $formatosSchemaArray[$archivoMd] ?? [];
$codigoFormatoOficial = !empty($schemaInfo['codigo_formato']) ? $schemaInfo['codigo_formato'] : (!empty($detalle['codigo_documento']) ? $detalle['codigo_documento'] : 'CYCSA-RT-FM-22');
$ensayoTituloOficial = !empty($schemaInfo['ensayo_titulo']) ? $schemaInfo['ensayo_titulo'] : $detalle['descripcion_ensayo'];
$metodoMuestreoOficial = !empty($schemaInfo['metodo_muestreo']) ? $schemaInfo['metodo_muestreo'] : (!empty($detalle['norma_astm']) ? $detalle['norma_astm'] : 'Norma Oficial');
$tipoMuestraOficial = !empty($schemaInfo['tipo_muestra']) ? $schemaInfo['tipo_muestra'] : 'Especímenes / Muestras';
$tomaMuestraOficial = !empty($detalle['requiere_muestreo']) ? 'Técnico CYCSA en campo (' . (!empty($detalle['tecnico_muestreo']) ? $detalle['tecnico_muestreo'] : 'Personal Asignado') . ')' : 'El Cliente y entrega en Laboratorio';
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-light: #eef2ff;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
        --color-success: #10b981;
        --color-slate-50: #f8fafc;
        --color-slate-100: #f1f5f9;
        --color-slate-200: #e2e8f0;
        --color-slate-300: #cbd5e1;
        --color-slate-600: #475569;
        --color-slate-700: #334155;
        --color-slate-800: #1e293b;
        --color-slate-900: #0f172a;
    }

    .matriz-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 10px 20px 60px 20px;
        box-sizing: border-box;
    }

    .matriz-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .btn-matriz-back {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 9px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
        font-size: 13.5px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-matriz-back:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .btn-matriz-primary {
        background: #103487;
        color: white;
        border: 1px solid #103487;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(16, 52, 135, 0.25);
        transition: all 0.2s;
    }
    .btn-matriz-primary:hover {
        background: #0c2766;
        transform: translateY(-1px);
    }

    .btn-matriz-secondary {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 9px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-matriz-secondary:hover {
        background: #e2e8f0;
    }

    .seccion-form {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        margin-bottom: 20px;
        border-top: 4px solid var(--cycsa-azul);
        width: 100%;
        box-sizing: border-box;
    }
    .seccion-titulo {
        margin: 0 0 18px 0;
        color: #1e293b;
        font-size: 17px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 12px;
    }

    .matriz-pills-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .pill-item {
        font-size: 11.5px;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .pill-os { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-family: monospace; }
    .pill-formato { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .pill-astm { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

    .matriz-meta-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 18px;
    }
    @media (max-width: 900px) {
        .matriz-meta-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .meta-cell {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .meta-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .meta-value {
        font-size: 13.5px;
        font-weight: 600;
        color: #1e293b;
    }

    /* Tabla de Captura */
    .tabla-matriz-wrapper {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
    }
    .tabla-matriz {
        width: 100%;
        min-width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .tabla-matriz th {
        background: #f8fafc;
        color: #334155;
        font-weight: 700;
        padding: 12px 14px;
        text-align: left;
        border-bottom: 2px solid #cbd5e1;
        font-size: 12.5px;
        white-space: nowrap;
    }
    .tabla-matriz td {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .tabla-matriz tbody tr:hover {
        background: #f8fafc;
    }

    .matriz-input {
        width: 100%;
        min-width: 105px;
        padding: 8px 12px;
        font-size: 13.5px;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        box-sizing: border-box;
        font-family: inherit;
        background: white;
        transition: all 0.15s;
    }
    .matriz-input:focus {
        border-color: #103487;
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.12);
    }
    .matriz-input-locked {
        background: #f1f5f9;
        color: #0f172a;
        font-weight: 700;
        border-color: #e2e8f0;
        cursor: not-allowed;
    }
    .matriz-input-calculated {
        background: #f8fafc;
        color: #0f172a;
        font-weight: 700;
        border-color: #cbd5e1;
    }
</style>

<!-- TOP BAR (ESTILO COTIZACIONES) -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <a href="/Cycsa/publico/operaciones" style="color: #6c757d; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-arrow-left"></i> Volver a Operaciones
        </a>
        <h2 style="margin: 8px 0 0 0; color: #1e293b; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-flask-vial" style="color: var(--cycsa-azul);"></i> <?= htmlspecialchars($ensayoTituloOficial, ENT_QUOTES, 'UTF-8') ?>
        </h2>
    </div>

    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $detalle['id'] ?>" target="_blank" class="btn-matriz-secondary" style="background:#f8fafc; color:#103487; border:1.5px solid #bfdbfe; font-weight:700; text-decoration:none; padding:10px 18px; display:inline-flex; align-items:center; gap:8px;" title="Ver e imprimir formato técnico horizontal oficial con membrete CYCSA">
            <i class="fa-solid fa-print" style="color:#103487;"></i> Imprimir Matriz (Horizontal)
        </a>
        <button type="button" onclick="guardarMatrizFullSubmit()" class="btn-matriz-primary">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Matriz del Producto
        </button>
    </div>
</div>

<!-- SECCIÓN 1: IDENTIFICACIÓN Y METADATOS OFICIALES -->
<div class="seccion-form">
    <div class="matriz-pills-row">
        <span class="pill-item pill-os"><i class="fa-solid fa-file-contract"></i> O/S: <?= htmlspecialchars($detalle['codigo_os'], ENT_QUOTES, 'UTF-8') ?></span>
        <span class="pill-item pill-formato" style="background:#0284c7; color:white; font-weight:700;"><i class="fa-solid fa-file-shield"></i> Formato: <?= htmlspecialchars($codigoFormatoOficial, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="pill-item pill-astm"><i class="fa-solid fa-microscope"></i> Muestreo: <?= htmlspecialchars($metodoMuestreoOficial, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="pill-item pill-formato" style="background:#e0f2fe; color:#0369a1;"><i class="fa-solid fa-vial"></i> <?= htmlspecialchars($tipoMuestraOficial, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="matriz-meta-grid">
        <?php if (!$esLaboratorista): ?>
            <div class="meta-cell">
                <span class="meta-label">Cliente / Razón Social</span>
                <span class="meta-value"><?= htmlspecialchars($detalle['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="meta-cell">
                <span class="meta-label">Nombre del Proyecto</span>
                <span class="meta-value"><?= htmlspecialchars($detalle['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php else: ?>
            <div class="meta-cell">
                <span class="meta-label">Imparcialidad Técnica</span>
                <span class="meta-value" style="color: #0369a1; font-weight: 700;"><i class="fa-solid fa-eye-slash"></i> Datos Comerciales Resguardados (ISO 17025)</span>
            </div>
            <div class="meta-cell">
                <span class="meta-label">Condición Operativa</span>
                <span class="meta-value" style="color: #059669; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> Modo Ensaye Ciego</span>
            </div>
        <?php endif; ?>
        <div class="meta-cell">
            <span class="meta-label">Muestra Tomada Por</span>
            <span class="meta-value" style="color: #0f172a; font-weight: 600;"><?= htmlspecialchars($tomaMuestraOficial, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Procedimiento Muestreo</span>
            <span class="meta-value" style="color: #475569; font-weight: 600;">Aleatorio</span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Orden de Servicio</span>
            <span class="meta-value" style="font-family: monospace; color: #103487; font-weight: 700;"><?= htmlspecialchars($detalle['codigo_os'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="meta-cell">
            <span class="meta-label">Archivo de Matriz</span>
            <span class="meta-value" style="color:#0284c7; font-weight:700; font-family:monospace;"><?= htmlspecialchars($detalle['archivo_markdown'] ?: 'Matriz Estándar') ?></span>
        </div>
    </div>
</div>

<!-- SECCIÓN 2: TABLA DE CAPTURA TÉCNICA (PANTALLA COMPLETA) -->
<div class="seccion-form">
    <form id="form-matriz-completa" method="POST" action="/Cycsa/publico/operaciones/guardar-matriz-producto" onsubmit="prepararEnvioMatriz(event)">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id_detalle" value="<?= $detalle['id'] ?>">
        <input type="hidden" name="resultados_json" id="input_resultados_json" value="">

        <div class="seccion-titulo" style="justify-content: space-between; flex-wrap: wrap;">
            <div>
                <i class="fa-solid fa-table-cells" style="color: var(--cycsa-azul);"></i> Matriz de Captura Técnica y Cálculos
                <span style="font-size: 12.5px; font-weight: normal; color: #64748b; margin-left: 8px;">(Actualización en tiempo real)</span>
            </div>

                <!-- SELECTOR DE LÍMITES GEOTÉCNICOS (Para Ensayos de Granulometría) -->
                <?php if ($isGranulometria): ?>
                <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 8px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin: 0; white-space: nowrap;">
                        <i class="fa-solid fa-sliders"></i> Aplicar Límites por Material:
                    </label>
                    <select id="select-limites-material" class="matriz-input" style="width: auto; padding: 5px 10px; font-size: 12.5px; background: white;" onchange="aplicarLimitesMaterial(this.value)">
                        <option value="">-- Seleccionar Especificación --</option>
                        <option value="Arena colchon">Arena colchón</option>
                        <option value="Material Cero">Material Cero</option>
                        <option value="Material 1 1/2">Material 1 1/2"</option>
                        <option value="Material 1">Material 1"</option>
                        <option value="Material 3/4">Material 3/4"</option>
                        <option value="Material 1/2">Material 1/2"</option>
                        <option value="Material 3/8">Material 3/8"</option>
                        <option value="Suelo">Suelo</option>
                        <option value="Selecto">Selecto</option>
                        <option value="Selecto relleno tipo 1">Selecto relleno tipo 1</option>
                        <option value="Selecto relleno tipo 2">Selecto relleno tipo 2</option>
                        <option value="Mezcla relleno 1-2">Mezcla relleno 1-2</option>
                        <option value="Selecto Base A">Selecto Base A</option>
                        <option value="Selecto Base B">Selecto Base B</option>
                        <option value="Selecto Base C">Selecto Base C</option>
                        <option value="Selecto Base D">Selecto Base D</option>
                        <option value="Sub base A-1">Sub base A-1</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <!-- CONTENEDOR DE LA TABLA DINÁMICA -->
            <div class="tabla-matriz-wrapper">
                <table class="tabla-matriz" id="tabla-captura-matriz">
                    <thead id="tabla-header">
                        <!-- Generado por JavaScript según el archivo markdown -->
                    </thead>
                    <tbody id="tabla-body">
                        <!-- Filas generadas por JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- ALERTA DE PÉRDIDA POR LAVADO -->
            <div id="alerta-lavado-norma" style="display:none; margin-top:15px; padding:12px 16px; border-radius:8px; font-size:13px; font-weight:600;"></div>

            <!-- BARRA DE ACCIÓN INFERIOR -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 18px;">
                <span style="font-size:12.5px; color:#0369a1; font-weight:600;">
                    <i class="fa-solid fa-lock"></i> Formato técnico oficial sincronizado con <?= htmlspecialchars($detalle['archivo_markdown'] ?: 'ISO 17025') ?>
                </span>
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

<script>
    const ARCHIVO_MARKDOWN = <?= json_encode($archivoMd, JSON_UNESCAPED_UNICODE) ?>;
    const FORMATOS_SCHEMA = <?= $formatosSchemaJson ?>;
    const DATOS_INICIALES = <?= !empty($detalle['resultados_json']) ? $detalle['resultados_json'] : '[]' ?>;
    const MUESTRAS_SETEADAS = <?= json_encode($muestrasSeteadas ?? [], JSON_UNESCAPED_UNICODE) ?>;

    const DEFAULT_ROWS_BY_FORMAT = {
        "formato_de_granulometria_de_suelo.md": [
            { "Malla": "2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Líquido", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Plástico", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "I.P", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" }
        ],
        "granulomnetria_de_agregados.md": [
            { "Malla": "2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Líquido", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Plástico", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
        ],
        "formato_de_resistencia_de_bloques.md": [
            { "Descripción": "Área bruta.", "Unidad": "in²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Área neta.", "Unidad": "in²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "50% A.B", "Límite Máx": "" },
            { "Descripción": "Carga", "Unidad": "lb", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "-", "Límite Máx": "-" },
            { "Descripción": "Largo", "Unidad": "cm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Alto", "Unidad": "cm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Espesor pared", "Unidad": "mm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "25", "Límite Máx": "" },
            { "Descripción": "Espesor tabique", "Unidad": "mm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "25", "Límite Máx": "" },
            { "Descripción": "Peso recibido", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso sumergido", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso saturado", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso seco", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Absorción", "Unidad": "%", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "12.00" },
            { "Descripción": "Humedad", "Unidad": "%", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Densidad", "Unidad": "kg/m³", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "2000", "Límite Máx": "" },
            { "Descripción": "R. Compresión área bruta", "Unidad": "kg/cm²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "70.00", "Límite Máx": "" },
            { "Descripción": "R. Compresión área neta", "Unidad": "kg/cm²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "133.00", "Límite Máx": "" }
        ]
    };

    const LIMITS_DB = {
        "Arena colchon": {
            "min": { "3/8\"": 100, "No. 4": 100, "No. 10": 85, "No. 200": 0 },
            "max": { "No. 200": 3 }
        },
        "Material Cero": {
            "min": { "1 1/2\"": 100, "1\"": 90, "3/4\"": 35, "1/2\"": 20, "3/8\"": 10, "No. 4": 0, "No. 8": 80, "No. 16": 50, "No. 30": 25, "No. 50": 5, "No. 100": 0, "No. 200": 0 },
            "max": { "1\"": 100, "3/4\"": 70, "1/2\"": 50, "3/8\"": 30, "No. 4": 100, "No. 8": 100, "No. 16": 85, "No. 30": 60, "No. 50": 30, "No. 100": 10, "No. 200": 5 }
        },
        "Material 1 1/2": {
            "min": { "2\"": 100, "1 1/2\"": 71, "1\"": 75, "3/4\"": 38, "1/2\"": 53, "3/8\"": 30, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "2\"": 100, "1\"": 100, "3/4\"": 100, "1/2\"": 77.5, "3/8\"": 55, "No. 4": 5, "No. 8": 0, "No. 200": 0 }
        },
        "Material 1": {
            "min": { "1\"": 100, "3/4\"": 90, "1/2\"": 55, "3/8\"": 40, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "1\"": 100, "3/4\"": 100, "1/2\"": 100, "3/8\"": 55, "No. 4": 10, "No. 8": 5, "No. 200": 0 }
        },
        "Material 3/4": {
            "min": { "3/4\"": 100, "1/2\"": 90, "3/8\"": 85, "No. 4": 0, "No. 8": 0, "No. 10": 0, "No. 16": 0, "No. 200": 0 },
            "max": { "3/4\"": 100, "1/2\"": 100, "3/8\"": 70, "No. 4": 15, "No. 8": 5, "No. 16": 0 }
        },
        "Material 1/2": {
            "min": { "1/2\"": 100, "3/8\"": 100, "No. 4": 10, "No. 8": 0, "No. 16": 0, "No. 200": 0 },
            "max": { "1/2\"": 100, "3/8\"": 100, "No. 4": 30, "No. 8": 10, "No. 16": 0 }
        },
        "Material 3/8": {
            "min": { "3/8\"": 100, "No. 4": 100, "No. 10": 100, "No. 16": 100, "No. 30": 100, "No. 40": 100, "No. 50": 100, "No. 60": 100, "No. 80": 100, "No. 100": 100, "No. 140": 100, "No. 200": 100 },
            "max": { "3/8\"": 100, "No. 4": 100, "No. 10": 100, "No. 16": 100, "No. 30": 100, "No. 40": 100, "No. 50": 100, "No. 60": 100, "No. 80": 100, "No. 100": 100, "No. 140": 100, "No. 200": 100 }
        },
        "Suelo": {
            "min": { "2\"": 100, "No. 4": 12, "No. 10": 7, "No. 40": 4, "No. 200": 0 },
            "max": { "2\"": 100, "No. 4": 40, "No. 10": 29, "No. 40": 21, "No. 200": 16 }
        },
        "Selecto": {
            "min": { "1\"": 75, "1/2\"": 50, "No. 4": 30, "No. 10": 20, "No. 40": 10, "No. 200": 0 },
            "max": { "1\"": 95, "1/2\"": 80, "No. 4": 65, "No. 10": 50, "No. 40": 35, "No. 200": 16 }
        },
        "Selecto relleno tipo 1": {
            "min": { "2\"": 100, "1\"": 75, "No. 4": 90, "No. 10": 75, "No. 40": 50, "No. 200": 0 },
            "max": { "2\"": 100, "1\"": 100, "No. 4": 100, "No. 10": 90, "No. 40": 65, "No. 200": 35 }
        },
        "Selecto relleno tipo 2": {
            "min": { "2\"": 100, "1\"": 65, "No. 4": 50, "No. 10": 30, "No. 40": 23, "No. 200": 0 },
            "max": { "2\"": 100, "1\"": 90, "No. 4": 80, "No. 10": 63, "No. 40": 46, "No. 200": 20 }
        },
        "Mezcla relleno 1-2": {
            "min": { "2\"": 97, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "2\"": 100, "No. 4": 55, "No. 10": 40, "No. 40": 20, "No. 200": 8 }
        },
        "Selecto Base A": {
            "min": { "No. 4": 30, "No. 10": 20, "No. 40": 15, "No. 200": 5 },
            "max": { "No. 4": 60, "No. 10": 45, "No. 40": 30, "No. 200": 15 }
        },
        "Selecto Base B": {
            "min": { "No. 4": 35, "No. 10": 25, "No. 40": 15, "No. 200": 5 },
            "max": { "No. 4": 65, "No. 10": 50, "No. 40": 30, "No. 200": 15 }
        },
        "Selecto Base C": {
            "min": { "No. 4": 50, "No. 10": 40, "No. 40": 25, "No. 200": 8 },
            "max": { "No. 4": 85, "No. 10": 70, "No. 40": 45, "No. 200": 15 }
        },
        "Selecto Base D": {
            "min": { "No. 4": 28, "No. 10": 22, "No. 200": 5 },
            "max": { "No. 4": 40, "No. 10": 52, "No. 200": 20 }
        },
        "Sub base A-1": {
            "min": { "2\"": 100, "1\"": 65, "No. 4": 28, "No. 10": 22, "No. 200": 5 },
            "max": { "2\"": 100, "1\"": 79, "No. 4": 40, "No. 10": 52, "No. 200": 20 }
        }
    };

    let columnasActuales = [];

    function inicializarMatriz() {
        const schema = FORMATOS_SCHEMA[ARCHIVO_MARKDOWN] || { columns: [] };
        columnasActuales = (schema.columns && schema.columns.length > 0) 
            ? schema.columns 
            : ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];

        // 1. Renderizar encabezado
        const thead = document.getElementById('tabla-header');
        thead.innerHTML = '';

        // Fila 1: Métodos de Ensayo (si existen definidos en el formato oficial)
        const hasMethods = schema.column_methods && Object.values(schema.column_methods).some(m => m && m.trim() !== '');
        if (hasMethods) {
            const trMethods = document.createElement('tr');
            trMethods.style.background = '#f8fafc';
            trMethods.style.borderBottom = '1px solid #cbd5e1';

            const thMethodTitle = document.createElement('th');
            thMethodTitle.colSpan = 3;
            thMethodTitle.style.textAlign = 'left';
            thMethodTitle.style.padding = '8px 12px';
            thMethodTitle.style.fontWeight = '700';
            thMethodTitle.style.fontSize = '12px';
            thMethodTitle.style.color = '#0284c7';
            thMethodTitle.innerHTML = '<i class="fa-solid fa-flask-vial"></i> Método de ensayo';
            trMethods.appendChild(thMethodTitle);

            columnasActuales.slice(2).forEach(col => {
                const thM = document.createElement('th');
                thM.style.textAlign = 'center';
                thM.style.padding = '6px 8px';
                thM.style.fontWeight = '600';
                thM.style.fontSize = '11px';
                const methodCode = schema.column_methods[col] || '';
                if (methodCode) {
                    thM.innerHTML = `<span style="background:#e0f2fe; color:#0369a1; padding: 2px 7px; border-radius: 4px; border: 1px solid #bae6fd; font-family: monospace; font-size: 11px;">${methodCode}</span>`;
                } else {
                    thM.innerText = '—';
                    thM.style.color = '#94a3b8';
                }
                trMethods.appendChild(thM);
            });
            thead.appendChild(trMethods);
        }

        // Fila 2: Nombres de Columnas
        const trH = document.createElement('tr');
        
        const thNum = document.createElement('th');
        thNum.style.width = '40px';
        thNum.style.textAlign = 'center';
        thNum.innerText = 'N°';
        trH.appendChild(thNum);

        columnasActuales.forEach(col => {
            const th = document.createElement('th');
            th.innerText = col;
            trH.appendChild(th);
        });
        thead.appendChild(trH);

        // 2. Determinar filas iniciales
        let filas = [];
        if (Array.isArray(DATOS_INICIALES) && DATOS_INICIALES.length > 0) {
            filas = DATOS_INICIALES.map((row, idx) => {
                if (row['Código laboratorio'] && row['Código laboratorio'].startsWith('OS-')) {
                    if (MUESTRAS_SETEADAS && MUESTRAS_SETEADAS[idx] && MUESTRAS_SETEADAS[idx].codigo_lab) {
                        row['Código laboratorio'] = MUESTRAS_SETEADAS[idx].codigo_lab;
                    } else {
                        row['Código laboratorio'] = 'MS-' + String(idx + 1).padStart(4, '0') + '-26';
                    }
                }
                return row;
            });
        } else if (DEFAULT_ROWS_BY_FORMAT[ARCHIVO_MARKDOWN]) {
            filas = JSON.parse(JSON.stringify(DEFAULT_ROWS_BY_FORMAT[ARCHIVO_MARKDOWN]));
        } else if (Array.isArray(MUESTRAS_SETEADAS) && MUESTRAS_SETEADAS.length > 0) {
            filas = MUESTRAS_SETEADAS.map((m, i) => {
                const row = {};
                columnasActuales.forEach(col => { row[col] = ''; });
                if (row.hasOwnProperty('Código laboratorio')) row['Código laboratorio'] = m.codigo_lab || '';
                if (row.hasOwnProperty('Nombre muestra')) row['Nombre muestra'] = m.nombre_muestra || m.codigo_campo || '';
                return row;
            });
        } else {
            // 3 filas genéricas
            for (let i = 1; i <= 3; i++) {
                const row = {};
                columnasActuales.forEach(col => { row[col] = ''; });
                if (row.hasOwnProperty('Código laboratorio')) row['Código laboratorio'] = 'MS-000' + i + '-26';
                if (row.hasOwnProperty('Nombre muestra')) row['Nombre muestra'] = 'Muestra ' + i;
                filas.push(row);
            }
        }

        // 3. Renderizar cuerpo
        renderizarFilas(filas);

        if (ARCHIVO_MARKDOWN.includes('granulometria') || ARCHIVO_MARKDOWN.includes('granulomnetria')) {
            recalculateGranulometria();
        } else {
            recalculateAll();
        }
    }

    function renderizarFilas(filas) {
        const tbody = document.getElementById('tabla-body');
        tbody.innerHTML = '';

        filas.forEach((rowData, rIdx) => {
            const tr = document.createElement('tr');
            
            // Columna de numeración
            const tdNum = document.createElement('td');
            tdNum.style.textAlign = 'center';
            tdNum.style.fontWeight = '700';
            tdNum.style.color = '#64748b';
            tdNum.innerText = rIdx + 1;
            tr.appendChild(tdNum);

            const isGranulo = (ARCHIVO_MARKDOWN.includes('granulometria') || ARCHIVO_MARKDOWN.includes('granulomnetria'));
            const mallaName = rowData['Malla'] || '';

            columnasActuales.forEach(col => {
                const td = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'matriz-input';
                input.value = rowData[col] !== undefined ? rowData[col] : '';
                input.dataset.col = col;
                input.dataset.row = rIdx;

                if (isGranulo) {
                    if (col === 'Malla') {
                        input.readOnly = true;
                        input.classList.add('matriz-input-locked');
                    }
                    if (['% Retenido parcial', '% Acumulativo'].includes(col)) {
                        input.readOnly = true;
                        input.classList.add('matriz-input-calculated');
                    }
                    if (col === '% que pasa la malla') {
                        if (['Límite Líquido', 'Límite Plástico'].includes(mallaName)) {
                            input.style.fontWeight = 'bold';
                            input.addEventListener('input', recalculateGranulometria);
                        } else if (mallaName === 'I.P') {
                            input.readOnly = true;
                            input.classList.add('matriz-input-calculated');
                        } else {
                            input.readOnly = true;
                            input.classList.add('matriz-input-calculated');
                        }
                    }
                    if (col === 'P. Retenido parcial (gr)') {
                        if (['Límite Líquido', 'Límite Plástico', 'I.P'].includes(mallaName)) {
                            input.readOnly = true;
                            input.classList.add('matriz-input-locked');
                            input.value = '—';
                        } else if (mallaName === 'Suma') {
                            input.readOnly = true;
                            input.classList.add('matriz-input-calculated');
                        } else {
                            input.addEventListener('input', recalculateGranulometria);
                        }
                    }
                } else if (ARCHIVO_MARKDOWN.includes('bloques')) {
                    if (['Descripción', 'Unidad', 'Método de ensayo'].includes(col)) {
                        input.readOnly = true;
                        input.classList.add('matriz-input-locked');
                    }
                } else {
                    // Ensayos de compresión / cilindros / probetas / compactación / otros
                    if (col === 'Código laboratorio') {
                        input.readOnly = true;
                        input.classList.add('matriz-input-locked');
                    }
                    if (['Compactación ((%) (P/P))', 'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)', 'R. compresión. (kg/cm²)', 'Resistencia a la flexión. (kg/cm²)', 'Reven. (cm)', 'Edad (Días)'].includes(col)) {
                        input.classList.add('matriz-input-calculated');
                    }
                    if (['Carga (lb)', 'Carga (kg)', 'Área (in²)', 'Área (cm²)'].includes(col)) {
                        input.addEventListener('input', recalculateCompresion);
                    }
                    if (['Reven. (in)'].includes(col)) {
                        input.addEventListener('input', recalculateRevenimiento);
                    }
                    if (['P.V.S Max (kg/m³)', 'P.V.S.Sitio (kg/cm²)', 'P.V.S.Sitio (kg/m³)'].includes(col)) {
                        input.addEventListener('input', recalculateCompactacion);
                    }
                    if (['Ancho Promedio (in)', 'Espesor Promedio (in)', 'Longitud de Apoyo (in)', 'Carga (lb)'].includes(col)) {
                        input.addEventListener('input', recalculateFlexion);
                    }
                    if (['Fecha de Fabricación', 'Fecha de Ensayo', 'Fecha de Ruptura'].includes(col)) {
                        input.addEventListener('change', recalculateEdades);
                        input.addEventListener('input', recalculateEdades);
                    }
                }

                td.appendChild(input);
                tr.appendChild(td);
            });

            tbody.appendChild(tr);
        });
    }

    function aplicarLimitesMaterial(matName) {
        if (!matName) return;
        const limits = LIMITS_DB[matName];
        if (!limits) return;

        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const descInput = tr.querySelector('input[data-col="Malla"]');
            const minInput = tr.querySelector('input[data-col="Límite Mín"]');
            const maxInput = tr.querySelector('input[data-col="Límite Máx"]');
            if (descInput && minInput && maxInput) {
                const malla = descInput.value.trim();
                minInput.value = (limits.min && limits.min[malla] !== undefined) ? limits.min[malla] : '';
                maxInput.value = (limits.max && limits.max[malla] !== undefined) ? limits.max[malla] : '';
            }
        });
    }

    function recalculateGranulometria() {
        const rows = document.querySelectorAll('#tabla-body tr');
        let totalSuma = 0;
        let rowsArray = [];

        rows.forEach(tr => {
            const rowData = {};
            tr.querySelectorAll('input').forEach(inp => {
                rowData[inp.dataset.col] = inp;
            });
            rowsArray.push(rowData);
        });

        // 1. Suma de pesos
        rowsArray.forEach(row => {
            const malla = row['Malla'] ? row['Malla'].value.trim() : '';
            if (malla && !['Suma', 'Límite Líquido', 'Límite Plástico', 'I.P'].includes(malla)) {
                const wInput = row['P. Retenido parcial (gr)'];
                if (wInput) {
                    totalSuma += (parseFloat(wInput.value) || 0);
                }
            }
        });

        // Fila Suma
        const sumaRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Suma');
        if (sumaRow && sumaRow['P. Retenido parcial (gr)']) {
            sumaRow['P. Retenido parcial (gr)'].value = totalSuma > 0 ? totalSuma.toFixed(4) : '';
            if (sumaRow['% Retenido parcial']) sumaRow['% Retenido parcial'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% Acumulativo']) sumaRow['% Acumulativo'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% que pasa la malla']) sumaRow['% que pasa la malla'].value = totalSuma > 0 ? '0.00' : '';
        }

        // Porcentajes de tamices
        let accumPercent = 0;
        rowsArray.forEach(row => {
            const malla = row['Malla'] ? row['Malla'].value.trim() : '';
            if (malla && !['Suma', 'Límite Líquido', 'Límite Plástico', 'I.P'].includes(malla)) {
                const wInput = row['P. Retenido parcial (gr)'];
                const rpInput = row['% Retenido parcial'];
                const acInput = row['% Acumulativo'];
                const qpInput = row['% que pasa la malla'];

                if (wInput && wInput.value !== '') {
                    const w = parseFloat(wInput.value) || 0;
                    const percent = totalSuma > 0 ? (w / totalSuma) * 100 : 0;
                    if (rpInput) rpInput.value = percent.toFixed(2);
                    accumPercent += percent;
                    if (acInput) acInput.value = accumPercent.toFixed(2);
                    if (qpInput) qpInput.value = Math.max(0, 100 - accumPercent).toFixed(2);
                } else {
                    if (rpInput) rpInput.value = '';
                    if (acInput) acInput.value = '';
                    if (qpInput) qpInput.value = '';
                }
            }
        });

        // Índice de Plasticidad (IP)
        const llRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Límite Líquido');
        const lpRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Límite Plástico');
        const ipRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'I.P');

        if (llRow && lpRow && ipRow) {
            const llVal = parseFloat(llRow['% que pasa la malla'] ? llRow['% que pasa la malla'].value : 0) || 0;
            const lpVal = parseFloat(lpRow['% que pasa la malla'] ? lpRow['% que pasa la malla'].value : 0) || 0;
            const ipVal = Math.max(0, llVal - lpVal);
            if (ipRow['% que pasa la malla']) {
                ipRow['% que pasa la malla'].value = (llVal > 0 || lpVal > 0) ? ipVal.toFixed(2) : '';
            }
        }

        // Validación de Pérdida por Lavado
        const alertaLavado = document.getElementById('alerta-lavado-norma');
        const perdidaRow = rowsArray.find(r => r['Malla'] && (r['Malla'].value.trim().toLowerCase().includes('pérdida') || r['Malla'].value.trim().toLowerCase().includes('perdida')));
        if (perdidaRow && alertaLavado) {
            const valPercent = parseFloat(perdidaRow['% Retenido parcial']?.value || 0);
            if (valPercent > 0.30) {
                alertaLavado.style.display = 'block';
                alertaLavado.style.backgroundColor = '#fef2f2';
                alertaLavado.style.color = '#991b1b';
                alertaLavado.style.border = '1px solid #fecaca';
                alertaLavado.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>RECHAZO DE NORMA:</strong> Pérdida por Lavado (' + valPercent.toFixed(2) + '%) supera el límite de 0.30%.';
            } else if (valPercent > 0) {
                alertaLavado.style.display = 'block';
                alertaLavado.style.backgroundColor = '#f0fdf4';
                alertaLavado.style.color = '#166534';
                alertaLavado.style.border = '1px solid #bbf7d0';
                alertaLavado.innerHTML = '<i class="fa-solid fa-circle-check"></i> <strong>CONFORME:</strong> Pérdida por Lavado (' + valPercent.toFixed(2) + '%) cumple dentro del máximo permitido de 0.30%.';
            } else {
                alertaLavado.style.display = 'none';
            }
        }
    }

    function recalculateCompresion() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const cargaInput = tr.querySelector('input[data-col="Carga (lb)"]') || tr.querySelector('input[data-col="Carga (kg)"]');
            const areaInput = tr.querySelector('input[data-col="Área (in²)"]') || tr.querySelector('input[data-col="Área (cm²)"]');
            const rPsiInput = tr.querySelector('input[data-col="R. Compresión (lb/in²)"]') || 
                              tr.querySelector('input[data-col="Estimación R. compresión (lb/in²)"]');
            const rKgInput = tr.querySelector('input[data-col="R. Compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. Compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. a la compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="Estimación R. compresión (kg/cm²)"]');

            if (cargaInput && areaInput) {
                const carga = parseFloat(cargaInput.value) || 0;
                const area = parseFloat(areaInput.value) || 0;

                if (carga > 0 && area > 0) {
                    const isLb = (cargaInput.getAttribute('data-col') || '').includes('(lb)');
                    const isIn2 = (areaInput.getAttribute('data-col') || '').includes('(in²)');
                    let psi = 0;
                    let kgcm2 = 0;

                    if (isLb && isIn2) {
                        psi = carga / area;
                        kgcm2 = psi * 0.070307;
                    } else if (!isLb && !isIn2) {
                        // Carga en kg y Área en cm² -> ya está en kg/cm²
                        kgcm2 = carga / area;
                        psi = kgcm2 * 14.223343;
                    } else if (isLb && !isIn2) {
                        // Carga en lb y Área en cm²
                        kgcm2 = (carga * 0.45359237) / area;
                        psi = kgcm2 * 14.223343;
                    } else {
                        // Carga en kg y Área en in²
                        psi = (carga * 2.2046226) / area;
                        kgcm2 = psi * 0.070307;
                    }

                    if (rPsiInput) rPsiInput.value = psi.toFixed(2);
                    if (rKgInput) rKgInput.value = kgcm2.toFixed(2);
                } else {
                    if (rPsiInput) rPsiInput.value = '';
                    if (rKgInput) rKgInput.value = '';
                }
            }
        });
    }

    function recalculateRevenimiento() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const revInInput = tr.querySelector('input[data-col="Reven. (in)"]');
            const revCmInput = tr.querySelector('input[data-col="Reven. (cm)"]');
            if (revInInput && revCmInput) {
                const valIn = parseFloat(revInInput.value) || 0;
                if (valIn > 0) {
                    revCmInput.value = (valIn * 2.54).toFixed(1);
                } else {
                    revCmInput.value = '';
                }
            }
        });
    }

    function recalculateCompactacion() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const pvsMaxInput = tr.querySelector('input[data-col="P.V.S Max (kg/m³)"]') || tr.querySelector('input[data-col="**P.V.S Max (kg/m³)"]');
            const pvsSitioInput = tr.querySelector('input[data-col="P.V.S.Sitio (kg/cm²)"]') || tr.querySelector('input[data-col="P.V.S.Sitio (kg/m³)"]');
            const compInput = tr.querySelector('input[data-col="Compactación ((%) (P/P))"]');

            if (pvsMaxInput && pvsSitioInput && compInput) {
                const max = parseFloat(pvsMaxInput.value) || 0;
                const sitio = parseFloat(pvsSitioInput.value) || 0;
                if (max > 0 && sitio > 0) {
                    compInput.value = ((sitio / max) * 100).toFixed(1);
                } else {
                    compInput.value = '';
                }
            }
        });
    }

    function recalculateFlexion() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const cargaInput = tr.querySelector('input[data-col="Carga (lb)"]');
            const bInput = tr.querySelector('input[data-col="Ancho Promedio (in)"]');
            const dInput = tr.querySelector('input[data-col="Espesor Promedio (in)"]');
            const lInput = tr.querySelector('input[data-col="Longitud de Apoyo (in)"]');
            const mrInput = tr.querySelector('input[data-col="Resistencia a la flexión. (kg/cm²)"]');

            if (cargaInput && bInput && dInput && lInput && mrInput) {
                const p = parseFloat(cargaInput.value) || 0;
                const b = parseFloat(bInput.value) || 0;
                const d = parseFloat(dInput.value) || 0;
                const l = parseFloat(lInput.value) || 0;

                if (p > 0 && b > 0 && d > 0 && l > 0) {
                    const mrPsi = (p * l) / (b * Math.pow(d, 2));
                    const mrKg = mrPsi * 0.070307;
                    mrInput.value = mrKg.toFixed(2);
                } else {
                    mrInput.value = '';
                }
            }
        });
    }

    function recalculateEdades() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const fFabInput = tr.querySelector('input[data-col="Fecha de Fabricación"]');
            const fEnsInput = tr.querySelector('input[data-col="Fecha de Ensayo"]') || tr.querySelector('input[data-col="Fecha de Ruptura"]') || tr.querySelector('input[data-col="Fecha de Finalización"]');
            const edadInput = tr.querySelector('input[data-col="Edad (Días)"]');

            if (fFabInput && fEnsInput && edadInput && fFabInput.value && fEnsInput.value) {
                const dFab = new Date(fFabInput.value);
                const dEns = new Date(fEnsInput.value);
                if (!isNaN(dFab) && !isNaN(dEns)) {
                    const diffTime = dEns - dFab;
                    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays >= 0) {
                        edadInput.value = diffDays;
                    }
                }
            }
        });
    }

    function recalculateAll() {
        recalculateCompresion();
        recalculateRevenimiento();
        recalculateCompactacion();
        recalculateFlexion();
        recalculateEdades();
    }


    function prepararEnvioMatriz(e) {
        const rows = document.querySelectorAll('#tabla-body tr');
        const resultados = [];

        rows.forEach(tr => {
            const rowObj = {};
            let hasAnyVal = false;
            tr.querySelectorAll('input').forEach(inp => {
                const colName = inp.dataset.col;
                const val = inp.value;
                rowObj[colName] = val;
                if (val && val.trim() !== '' && val !== '—') {
                    hasAnyVal = true;
                }
            });
            if (hasAnyVal) {
                resultados.push(rowObj);
            }
        });

        document.getElementById('input_resultados_json').value = JSON.stringify(resultados);
    }

    function guardarMatrizFullSubmit() {
        document.getElementById('form-matriz-completa').dispatchEvent(new Event('submit', { cancelable: true }));
        document.getElementById('form-matriz-completa').submit();
    }

    document.addEventListener('DOMContentLoaded', inicializarMatriz);
</script>