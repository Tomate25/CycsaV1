<?php
$esc = static fn($valor) => htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
?>
<style>
    .plantillas-wrap { max-width: 1320px; margin: 0 auto; padding: 24px; color: #1e293b; }
    .plantillas-head { display: flex; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 24px; }
    .plantillas-head h1 { margin: 0; font-size: 25px; }
    .plantillas-head a { color: #103487; text-decoration: none; font-weight: 700; }
    .plantillas-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: auto; }
    .plantillas-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .plantillas-table th, .plantillas-table td { padding: 13px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .plantillas-table th { background: #f8fafc; color: #475569; font-size: 11px; text-transform: uppercase; }
    .plantillas-table tr:hover td { background: #f8fafc; }
    .plantillas-table button, .plantilla-actions button { border: 0; border-radius: 7px; padding: 9px 12px; cursor: pointer; font-weight: 700; }
    .plantillas-table button, .guardar-plantilla { color: #fff; background: #103487; }
    .plantilla-modal { display: none; position: fixed; inset: 0; z-index: 1200; background: rgba(15,23,42,.7); padding: 18px; overflow: auto; }
    .plantilla-modal.open { display: block; }
    .plantilla-dialog { max-width: 980px; margin: 22px auto; background: #fff; border-radius: 12px; box-shadow: 0 24px 65px rgba(0,0,0,.25); }
    .plantilla-dialog-head { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid #e2e8f0; }
    .plantilla-dialog-head h2 { margin: 0; font-size: 19px; }
    .plantilla-dialog-head button { background: transparent; border: 0; font-size: 25px; cursor: pointer; }
    .plantilla-tabs { display: flex; gap: 6px; padding: 14px 22px 0; overflow: auto; border-bottom: 1px solid #e2e8f0; }
    .plantilla-tabs button { border: 0; background: transparent; padding: 11px 12px; white-space: nowrap; cursor: pointer; color: #475569; }
    .plantilla-tabs button.active { color: #103487; border-bottom: 3px solid #103487; font-weight: 700; }
    .plantilla-body { padding: 20px 22px; max-height: 58vh; overflow: auto; }
    .plantilla-pane { display: none; }
    .plantilla-pane.active { display: block; }
    .plantilla-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .plantilla-field { display: flex; flex-direction: column; gap: 5px; font-size: 12px; font-weight: 700; }
    .plantilla-field.wide { grid-column: 1 / -1; }
    .plantilla-field input, .plantilla-field textarea, .plantilla-list input { width: 100%; box-sizing: border-box; padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font: inherit; color: #1e293b; }
    .plantilla-field textarea { min-height: 80px; resize: vertical; }
    .plantilla-list { display: grid; gap: 8px; }
    .plantilla-row { display: grid; grid-template-columns: 115px minmax(0, 1.2fr) minmax(0, .7fr) auto; gap: 8px; align-items: center; }
    .plantilla-row.nota { grid-template-columns: 1fr auto; }
    .plantilla-row button, .plantilla-add { border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; padding: 8px 10px; cursor: pointer; }
    .plantilla-add { margin-top: 12px; color: #103487; font-weight: 700; }
    .plantilla-actions { display: flex; justify-content: space-between; gap: 10px; padding: 17px 22px; border-top: 1px solid #e2e8f0; }
    .restablecer-plantilla { background: #fef2f2; color: #b91c1c; }
    .plantilla-status { min-height: 20px; padding: 0 22px 8px; color: #b91c1c; }
    
    /* Estilos del Visualizador de Fórmulas y Cálculos */
    .col-pill { display: inline-flex; align-items: center; justify-content: center; padding: 6px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; user-select: none; }
    .col-pill.calc { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .col-pill.input { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .col-pill.generic { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

    .formulas-wrap { display: flex; flex-direction: column; gap: 16px; }
    .formulas-banner {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 1px solid #bae6fd;
        border-radius: 10px;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }
    .formulas-banner h3 { margin: 0 0 5px 0; font-size: 15px; color: #0369a1; }
    .formulas-banner p { margin: 0; font-size: 12px; color: #334155; line-height: 1.45; }
    .formulas-summary-bar { display: flex; gap: 8px; flex-shrink: 0; }
    .formula-stat-pill {
        background: #fff;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }
    .formula-stat-pill.activa { background: #ecfdf5; border-color: #86efac; color: #065f46; }

    .formulas-cards-grid { display: grid; gap: 14px; }
    .formula-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px 18px;
        transition: all .2s ease;
    }
    .formula-card.activa {
        border-color: #10b981;
        box-shadow: 0 3px 12px rgba(16, 185, 129, 0.08);
        background: #fbfdfc;
    }
    .formula-card.inactiva {
        background: #f8fafc;
        border-color: #e2e8f0;
        opacity: 0.72;
    }
    .formula-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .formula-card-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .formula-badge-norma {
        background: #e2e8f0;
        color: #334155;
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 4px;
        font-family: monospace;
    }
    .formula-badge-status {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }
    .formula-badge-status.activa {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }
    .formula-badge-status.inactiva {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .formula-desc { font-size: 12px; color: #475569; margin: 0 0 10px 0; }
    .math-equation-box {
        background: #0f172a;
        color: #38bdf8;
        padding: 10px 14px;
        border-radius: 6px;
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 12.5px;
        margin: 8px 0;
        line-height: 1.55;
    }
    .math-equation-box code { display: block; }
    .formula-flow {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        font-size: 11.5px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #e2e8f0;
    }
    .formula-flow-label { font-weight: 700; color: #475569; }
    .formula-pill-var {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 2px 7px;
        border-radius: 4px;
        font-weight: 600;
    }
    .formula-pill-res {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        padding: 2px 7px;
        border-radius: 4px;
        font-weight: 700;
    }
    .formula-arrow { color: #94a3b8; font-weight: 700; }

    @media (max-width: 680px) { 
        .plantilla-grid { grid-template-columns: 1fr; } 
        .plantilla-row { grid-template-columns: 1fr; } 
        .formulas-banner { flex-direction: column; align-items: flex-start; }
    }
</style>

<div class="plantillas-wrap">
    <div class="plantillas-head">
        <div>
            <a href="/Cycsa/publico/panel">← Volver al panel</a>
            <h1>Plantillas oficiales de ensayos</h1>
            <p>Los cambios guardados se aplican a la captura, impresión y PDF de las matrices.</p>
        </div>
    </div>
    <div class="plantillas-panel">
        <table class="plantillas-table">
            <thead><tr><th>Ensayo</th><th>Código vigente</th><th>Norma</th><th>Última modificación</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($plantillas as $plantilla): ?>
                <tr>
                    <td><strong><?= $esc($plantilla['nombre']) ?></strong></td>
                    <td><?= $esc($plantilla['codigo_vigente']) ?></td>
                    <td><?= $esc($plantilla['norma']) ?></td>
                    <td><?= $esc($plantilla['fecha_actualizacion'] ?: 'Sin cambios') ?></td>
                    <td><button type="button" onclick="abrirPlantilla(<?= (int)$plantilla['id'] ?>)">Configurar formato</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="plantilla-modal" class="plantilla-modal" role="dialog" aria-modal="true" aria-labelledby="plantilla-titulo">
    <div class="plantilla-dialog">
        <div class="plantilla-dialog-head">
            <h2 id="plantilla-titulo">Configurar plantilla</h2>
            <button type="button" onclick="cerrarPlantilla()" aria-label="Cerrar">×</button>
        </div>
        <div class="plantilla-tabs">
            <button type="button" class="active" data-tab="documento">Control documental</button>
            <button type="button" data-tab="columnas">Columnas de matriz</button>
            <button type="button" data-tab="formulas">Fórmulas y cálculos</button>
            <button type="button" data-tab="notas">Leyendas y notas</button>
            <button type="button" data-tab="firmas">Firmas</button>
        </div>
        <div class="plantilla-body">
            <div class="plantilla-pane active" data-pane="documento">
                <div class="plantilla-grid">
                    <label class="plantilla-field">Código oficial<input data-key="codigo_formato" maxlength="100"></label>
                    <label class="plantilla-field">Versión documental<input data-key="version_documento" maxlength="50"></label>
                    <label class="plantilla-field">Título del informe<input data-key="titulo_informe" maxlength="120"></label>
                    <label class="plantilla-field">Subtítulo del laboratorio<input data-key="subtitulo_laboratorio" maxlength="180"></label>
                    <label class="plantilla-field wide">Título del ensayo y procedimientos<textarea data-key="ensayo_titulo"></textarea></label>
                    <label class="plantilla-field">Norma ASTM / AASHTO<input data-key="norma" maxlength="255"></label>
                    <label class="plantilla-field">Método de muestreo<input data-key="metodo_muestreo" maxlength="255"></label>
                    <label class="plantilla-field wide">Tipo de muestra por defecto<input data-key="tipo_muestra" maxlength="255"></label>
                </div>
            </div>
            <div class="plantilla-pane" data-pane="columnas">
                <p>Renombra o reordena las columnas. El método se mostrará bajo el encabezado en el informe. Las columnas con etiqueta <span class="col-pill calc">Calculada</span> ejecutan fórmulas automáticas protegidas.</p>
                <div id="plantilla-columnas" class="plantilla-list"></div>
                <button type="button" class="plantilla-add" onclick="agregarColumna()">+ Agregar columna</button>
            </div>
            <div class="plantilla-pane" data-pane="formulas">
                <div class="formulas-wrap">
                    <div class="formulas-banner">
                        <div>
                            <h3>Motor de cálculos y fórmulas normativas</h3>
                            <p>Estas fórmulas de ingeniería operan automáticamente en tiempo real durante la captura técnica según las normas ASTM / AASHTO / CYCSA. Los resultados calculados se protegen contra errores manuales y se transfieren a la impresión y PDF oficial.</p>
                        </div>
                        <div id="formulas-conteo-pills" class="formulas-summary-bar"></div>
                    </div>
                    <div id="plantilla-formulas-lista" class="formulas-cards-grid"></div>
                </div>
            </div>
            <div class="plantilla-pane" data-pane="notas">
                <label class="plantilla-field">Descargo de responsabilidad<textarea data-key="disclaimer"></textarea></label>
                <p>Notas técnicas y equipos</p>
                <div id="plantilla-notas" class="plantilla-list"></div>
                <button type="button" class="plantilla-add" onclick="agregarNota()">+ Agregar nota</button>
            </div>
            <div class="plantilla-pane" data-pane="firmas">
                <div class="plantilla-grid">
                    <label class="plantilla-field">Firmante oficial<input data-key="firmante_nombre" maxlength="150"></label>
                    <label class="plantilla-field">Cargo<input data-key="firmante_cargo" maxlength="150"></label>
                </div>
            </div>
        </div>
        <div id="plantilla-status" class="plantilla-status" role="status"></div>
        <div class="plantilla-actions">
            <button type="button" class="restablecer-plantilla" onclick="restablecerPlantilla()">Restablecer original</button>
            <button type="button" class="guardar-plantilla" onclick="guardarPlantilla()">Guardar cambios en plantilla</button>
        </div>
    </div>
</div>

<script>
const plantillaBaseUrl = '/Cycsa/publico/configuracion/plantillas-ensayos';
const plantillaCsrf = <?= json_encode($_SESSION['csrf_token'], JSON_UNESCAPED_UNICODE) ?>;
let plantillaId = null;
let plantillaConfig = null;
const modalPlantilla = document.getElementById('plantilla-modal');
const estadoPlantilla = document.getElementById('plantilla-status');
const campoPlantilla = key => modalPlantilla.querySelector('[data-key="' + key + '"]');

// Catálogo Normativo de Fórmulas y Reglas de Cálculo del Laboratorio
const CATALOGO_FORMULAS = [
    {
        id: 'compresion',
        nombre: 'Resistencia a la Compresión (Esfuerzo Unitario)',
        norma: 'ASTM C39 / ASTM C109 / ASTM C42 (CYCSA-PE-07)',
        inputs: ['Carga (lb)', 'Área (in²)'],
        inputsAlt: [['Carga (lb)', 'Carga (kg)'], ['Área (in²)', 'Área (cm²)']],
        outputs: ['R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)'],
        outputsAlt: ['R. Compresión (kg/cm²)', 'R. Compresión. (kg/cm²)', 'R. compresión. (kg/cm²)', 'R. a la compresión (kg/cm²)', 'Estimación R. compresión (kg/cm²)', 'R. Compresión (lb/in²)'],
        ecuaciones: [
            'σ [psi] = Carga (lb) / Área (in²)',
            'R. Compresión (kg/cm²) = σ [psi] × 0.070307  (o directo kg / cm²)'
        ],
        descripcion: 'Calcula el esfuerzo de rotura axial en probetas y especímenes cilíndricos, cúbicos o prismáticos, convirtiendo a kg/cm² automáticamente.'
    },
    {
        id: 'flexion',
        nombre: 'Resistencia a la Flexión (Módulo de Ruptura MR)',
        norma: 'ASTM C78 / AASHTO T97 (CYCSA-PE-30)',
        inputs: ['Carga (lb)', 'Longitud de Apoyo (in)', 'Ancho Promedio (in)', 'Espesor Promedio (in)'],
        outputs: ['Resistencia a la flexión. (kg/cm²)'],
        ecuaciones: [
            'MR [psi] = ( Carga × Longitud Apoyo ) / ( Ancho × Espesor² )',
            'MR (kg/cm²) = MR [psi] × 0.070307'
        ],
        descripcion: 'Calcula el módulo de ruptura en viguetas de concreto con carga en el tercio medio de la luz.'
    },
    {
        id: 'compactacion',
        nombre: 'Grado de Compactación en Campo (%)',
        norma: 'ASTM D1556 / ASTM D6938 (CYCSA-PE-20 / PE-25)',
        inputs: ['P.V.S Max (kg/m³)', 'P.V.S.Sitio (kg/m³)'],
        inputsAlt: [['P.V.S Max (kg/m³)', '**P.V.S Max (kg/m³)'], ['P.V.S.Sitio (kg/m³)', 'P.V.S.Sitio (kg/cm²)']],
        outputs: ['Compactación ((%) (P/P))'],
        ecuaciones: [
            '% Compactación = ( P.V.S. Sitio / P.V.S. Máximo Proctor ) × 100'
        ],
        descripcion: 'Relación porcentual de compactación entre la densidad seca en sitio y la densidad seca máxima del ensayo Proctor.'
    },
    {
        id: 'granulometria',
        nombre: 'Curva Granulométrica y Tamizado en Cascada',
        norma: 'ASTM C136 / ASTM D422 / ASTM C117 (CYCSA-PE-16)',
        inputs: ['P. Retenido parcial (gr)'],
        outputs: ['% Retenido parcial', '% Acumulativo', '% que pasa la malla'],
        ecuaciones: [
            'Masa Total Σ = ∑ (P. Retenido parcial por malla)',
            '% Retenido Parcial = ( Wi / Masa Total ) × 100',
            '% Acumulativo = ∑ % Retenido Parcial acumulado',
            '% Que Pasa = 100 - % Acumulativo',
            'Control de Calidad: Pérdida por lavado > 0.30% activa RECHAZO AUTOMÁTICO'
        ],
        descripcion: 'Distribución porcentual de tamaños de partículas por tamices con cálculo acumulativo continuo y verificación normativa de lavado.'
    },
    {
        id: 'atterberg',
        nombre: 'Índice de Plasticidad (Límites de Consistencia)',
        norma: 'ASTM D4318 / AASHTO T89 / T90 (CYCSA-PE-17)',
        inputs: ['Límite Líquido', 'Límite Plástico'],
        outputs: ['I.P'],
        ecuaciones: [
            'IP = Límite Líquido (LL) - Límite Plástico (LP)'
        ],
        descripcion: 'Diferencia numérica entre el límite líquido y plástico para clasificación de suelos (SUCS / AASHTO).'
    },
    {
        id: 'revenimiento',
        nombre: 'Conversión Dimensional de Revenimiento (Slump)',
        norma: 'ASTM C143 / AASHTO T119 (CYCSA-PE-09)',
        inputs: ['Reven. (in)'],
        outputs: ['Reven. (cm)'],
        ecuaciones: [
            'Revenimiento (cm) = Revenimiento (in) × 2.54'
        ],
        descripcion: 'Convierte en tiempo real la medición de revenimiento o asentamiento de pulgadas a centímetros.'
    },
    {
        id: 'edades',
        nombre: 'Cálculo Cronológico de Edades de Especímenes',
        norma: 'ASTM C31 / ASTM C192',
        inputs: ['Fecha de Fabricación', 'Fecha de Ensayo'],
        inputsAlt: [['Fecha de Fabricación'], ['Fecha de Ensayo', 'Fecha de Ruptura', 'Fecha de Finalización']],
        outputs: ['Edad (Días)'],
        ecuaciones: [
            'Edad (Días) = Diferencia en días enteros entre (Fecha Ensayo / Ruptura - Fecha Fabricación)'
        ],
        descripcion: 'Cálculo automático de la edad de curado del espécimen a partir de las fechas registradas en campo y laboratorio.'
    }
];

function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
}

function clasificarColumna(nombre) {
    const n = (nombre || '').trim().toLowerCase();
    if (!n) return { tipo: 'generic', label: 'Columna', title: 'Columna estándar' };

    const esCalculada = [
        'r. compresión', 'r. compresion', 'r. a la compresión', 'estimación r. compresión',
        'compactación', 'compactacion',
        'resistencia a la flexión', 'resistencia a la flexion',
        'reven. (cm)',
        'edad (días)', 'edad (dias)',
        '% retenido parcial', '% acumulativo', '% que pasa la malla', 'i.p'
    ].some(k => n.includes(k));

    if (esCalculada) {
        return { tipo: 'calc', label: 'Calculada', title: 'Resultado calculado automáticamente por fórmula' };
    }

    const esInput = [
        'carga (lb)', 'carga (kg)', 'área (in²)', 'area (in²)', 'área (cm²)', 'area (cm²)',
        'p.v.s max', 'p.v.s. max', 'p.v.s.sitio', 'p.v.s. sitio',
        'longitud de apoyo', 'ancho promedio', 'espesor promedio',
        'p. retenido parcial (gr)', 'reven. (in)',
        'fecha de fabricación', 'fecha de fabricacion', 'fecha de ensayo', 'fecha de ruptura',
        'límite líquido', 'limite liquido', 'límite plástico', 'limite plastico'
    ].some(k => n.includes(k));

    if (esInput) {
        return { tipo: 'input', label: 'Entrada', title: 'Variable técnica requerida para cálculos automáticos' };
    }

    return { tipo: 'generic', label: 'Dato', title: 'Campo de información o registro manual' };
}

function actualizarPillColumna(pill, nombre) {
    if (!pill) return;
    const info = clasificarColumna(nombre);
    pill.className = 'col-pill ' + info.tipo;
    pill.textContent = info.label;
    pill.title = info.title;
}

function mostrarTabPlantilla(tab) {
    modalPlantilla.querySelectorAll('[data-tab]').forEach(el => el.classList.toggle('active', el.dataset.tab === tab));
    modalPlantilla.querySelectorAll('[data-pane]').forEach(el => el.classList.toggle('active', el.dataset.pane === tab));
    if (tab === 'formulas') {
        actualizarVisualizacionFormulas();
    }
}
modalPlantilla.querySelectorAll('[data-tab]').forEach(el => el.addEventListener('click', () => mostrarTabPlantilla(el.dataset.tab)));

function crearFilaPlantilla(container, valores, clase, placeholders) {
    const fila = document.createElement('div');
    fila.className = 'plantilla-row ' + clase;

    let pill = null;
    if (clase !== 'nota') {
        pill = document.createElement('span');
        actualizarPillColumna(pill, valores[0] || '');
        fila.appendChild(pill);
    }

    valores.forEach((valor, i) => {
        const input = document.createElement('input');
        input.value = valor || '';
        input.placeholder = placeholders[i];
        input.maxLength = clase === 'nota' ? 1000 : 120;
        if (i === 0 && pill) {
            input.addEventListener('input', () => {
                actualizarPillColumna(pill, input.value.trim());
                actualizarVisualizacionFormulas();
            });
        }
        fila.appendChild(input);
    });

    const quitar = document.createElement('button');
    quitar.type = 'button';
    quitar.textContent = 'Eliminar';
    quitar.onclick = () => {
        fila.remove();
        actualizarVisualizacionFormulas();
    };
    fila.appendChild(quitar);
    container.appendChild(fila);
}

function agregarColumna(nombre = '', metodo = '') {
    crearFilaPlantilla(document.getElementById('plantilla-columnas'), [nombre, metodo], '', ['Nombre de columna', 'Método PE-XX']);
    document.getElementById('plantilla-columnas').lastElementChild.dataset.originalName = nombre;
    actualizarVisualizacionFormulas();
}

function agregarNota(nota = '') {
    crearFilaPlantilla(document.getElementById('plantilla-notas'), [nota], 'nota', ['Nota técnica o equipo']);
}

function actualizarVisualizacionFormulas() {
    const listaContainer = document.getElementById('plantilla-formulas-lista');
    const conteoContainer = document.getElementById('formulas-conteo-pills');
    if (!listaContainer) return;

    const colInputs = document.querySelectorAll('#plantilla-columnas .plantilla-row input:first-of-type');
    const colsActuales = Array.from(colInputs).map(i => i.value.trim());
    const colsActualesLower = colsActuales.map(c => c.toLowerCase());
    const isGranulo = (plantillaConfig?.archivo_markdown || '').includes('granulo');

    let activasCount = 0;
    let inputsCount = 0;
    let outputsCount = 0;

    const cardsHtml = [];

    CATALOGO_FORMULAS.forEach(f => {
        let inputsPresentes = false;
        if (f.id === 'granulometria') {
            inputsPresentes = isGranulo || colsActualesLower.some(c => c.includes('retenido'));
        } else if (f.inputsAlt) {
            inputsPresentes = f.inputsAlt.every(grupo => grupo.some(col => colsActualesLower.includes(col.toLowerCase())));
        } else {
            inputsPresentes = f.inputs.every(col => colsActualesLower.includes(col.toLowerCase()));
        }

        let outputsPresentes = false;
        if (f.id === 'granulometria') {
            outputsPresentes = isGranulo || colsActualesLower.some(c => c.includes('%'));
        } else if (f.outputsAlt) {
            outputsPresentes = f.outputsAlt.some(col => colsActualesLower.includes(col.toLowerCase()));
        } else {
            outputsPresentes = f.outputs.some(col => colsActualesLower.includes(col.toLowerCase()));
        }

        let estado = 'inactiva';
        let badgeText = 'No aplica a este formato';
        if (inputsPresentes && outputsPresentes) {
            estado = 'activa';
            badgeText = 'Activa en este formato';
            activasCount++;
        } else if (inputsPresentes || outputsPresentes || (f.id === 'granulometria' && isGranulo)) {
            estado = 'activa';
            badgeText = 'Activa en este formato';
            activasCount++;
        }

        const equationsHtml = f.ecuaciones.map(eq => `<code>${escapeHtml(eq)}</code>`).join('');
        const inputsPills = f.inputs.map(inp => `<span class="formula-pill-var">${escapeHtml(inp)}</span>`).join(' + ');
        const outputsPills = f.outputs.map(out => `<span class="formula-pill-res">${escapeHtml(out)}</span>`).join(', ');

        cardsHtml.push(`
            <div class="formula-card ${estado}">
                <div class="formula-card-head">
                    <h4 class="formula-card-title">
                        ${escapeHtml(f.nombre)}
                        <span class="formula-badge-norma">${escapeHtml(f.norma)}</span>
                    </h4>
                    <span class="formula-badge-status ${estado}">${badgeText}</span>
                </div>
                <p class="formula-desc">${escapeHtml(f.descripcion)}</p>
                <div class="math-equation-box">
                    ${equationsHtml}
                </div>
                <div class="formula-flow">
                    <span class="formula-flow-label">Variables de Entrada:</span>
                    ${inputsPills}
                    <span class="formula-arrow">&rarr;</span>
                    <span class="formula-flow-label">Resultado Automático:</span>
                    ${outputsPills}
                </div>
            </div>
        `);
    });

    colsActuales.forEach(c => {
        const cls = clasificarColumna(c).tipo;
        if (cls === 'calc') outputsCount++;
        if (cls === 'input') inputsCount++;
    });

    if (conteoContainer) {
        conteoContainer.innerHTML = `
            <div class="formula-stat-pill activa">${activasCount} fórmulas activas</div>
            <div class="formula-stat-pill">${inputsCount} variables de entrada</div>
            <div class="formula-stat-pill">${outputsCount} columnas calculadas</div>
        `;
    }

    listaContainer.innerHTML = cardsHtml.join('');
}

async function abrirPlantilla(id) {
    estadoPlantilla.textContent = '';
    const response = await fetch(plantillaBaseUrl + '/obtener-ajax?id=' + encodeURIComponent(id), {credentials: 'same-origin'});
    const data = await response.json();
    if (!response.ok || !data.success) { alert(data.error || 'No se pudo cargar la plantilla.'); return; }
    plantillaId = id;
    plantillaConfig = data.plantilla.configuracion;
    plantillaConfig.archivo_markdown = data.plantilla.archivo_markdown;
    document.getElementById('plantilla-titulo').textContent = data.plantilla.nombre;
    modalPlantilla.querySelectorAll('[data-key]').forEach(el => { el.value = plantillaConfig[el.dataset.key] || ''; });
    const columnas = document.getElementById('plantilla-columnas');
    columnas.replaceChildren();
    (plantillaConfig.columns || []).forEach(nombre => agregarColumna(nombre, (plantillaConfig.column_methods || {})[nombre] || ''));
    const notas = document.getElementById('plantilla-notas');
    notas.replaceChildren();
    (plantillaConfig.notas || []).forEach(nota => agregarNota(nota));
    actualizarVisualizacionFormulas();
    mostrarTabPlantilla('documento');
    modalPlantilla.classList.add('open');
}

function cerrarPlantilla() { modalPlantilla.classList.remove('open'); }
modalPlantilla.addEventListener('click', e => { if (e.target === modalPlantilla) cerrarPlantilla(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarPlantilla(); });

function recogerPlantilla() {
    const config = {...plantillaConfig};
    modalPlantilla.querySelectorAll('[data-key]').forEach(el => { config[el.dataset.key] = el.value.trim(); });
    config.columns = [];
    config.column_methods = {};
    config.column_aliases = {};
    document.querySelectorAll('#plantilla-columnas .plantilla-row').forEach(fila => {
        const [nombre, metodo] = fila.querySelectorAll('input');
        const clave = nombre.value.trim();
        config.columns.push(clave);
        config.column_methods[clave] = metodo.value.trim();
        const original = fila.dataset.originalName;
        if (original && original !== clave) config.column_aliases[clave] = (plantillaConfig.column_aliases || {})[original] || original;
        else if (original && plantillaConfig.column_aliases?.[original]) config.column_aliases[clave] = plantillaConfig.column_aliases[original];
    });
    config.notas = [...document.querySelectorAll('#plantilla-notas .plantilla-row input')].map(el => el.value.trim()).filter(Boolean);
    return config;
}

async function enviarPlantilla(ruta, parametros) {
    estadoPlantilla.textContent = 'Procesando...';
    try {
        const response = await fetch(plantillaBaseUrl + ruta, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({id: plantillaId, csrf_token: plantillaCsrf, ...parametros})
        });
        const data = await response.json();
        if (!response.ok || !data.success) { estadoPlantilla.textContent = data.error || 'No se pudo guardar.'; return; }
        location.reload();
    } catch (e) {
        estadoPlantilla.textContent = 'Error de comunicación con el servidor.';
    }
}

function guardarPlantilla() { enviarPlantilla('/guardar', {configuracion_json: JSON.stringify(recogerPlantilla())}); }
function restablecerPlantilla() {
    if (confirm('¿Restablecer esta plantilla al esquema original? Se perderán las modificaciones administrativas.')) {
        enviarPlantilla('/restablecer', {});
    }
}
</script>
