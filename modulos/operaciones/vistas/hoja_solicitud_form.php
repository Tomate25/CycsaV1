<?php
// Vista para llenar la Hoja de Solicitud de Servicio CYCSA-RT-FM-13 (Premium Design)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Hoja de Solicitud CYCSA-RT-FM-13', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="/Cycsa/publico/img/logo.png" type="image/png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #103487;
            --primary-hover: #0c2766;
            --primary-light: #e6eefc;
            --secondary: #475569;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-md: 10px;
            --radius-sm: 6px;
            --cycsa-azul: #103487;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn-back {
            text-decoration: none;
            background: #fff;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            color: #475569;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #f1f5f9;
        }

        .form-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 10px 15px -3px rgba(0,0,0,0.03);
            padding: 35px;
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 8px;
            margin-top: 25px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title:first-of-type {
            margin-top: 0;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .form-group {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group > label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            min-height: 32px;
            display: flex;
            align-items: flex-end;
            margin-bottom: 2px;
        }

        .form-control {
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            color: var(--text-main);
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1);
        }

        /* Checkbox grid style */
        .check-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
        }

        .check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: #334155;
            background: #f8fafc;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s;
        }
        .check-item:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .check-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        /* Specimen Table */
        .spec-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .spec-table th {
            background-color: #f1f5f9;
            color: #475569;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
            border: 1px solid var(--border);
        }
        .spec-table td {
            padding: 8px;
            border: 1px solid var(--border);
        }

        .btn-action {
            border: none;
            background: none;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        .btn-secondary {
            background-color: #cbd5e1;
            color: #334155;
        }
        .btn-secondary:hover {
            background-color: #94a3b8;
        }

        .btn-mini {
            padding: 5px 10px;
            font-size: 11px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700;">Hoja de Solicitud de Servicio (Ingreso)</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Formulario CYCSA-RT-FM-13 vinculado a la O/S: <strong style="color: var(--primary); font-family: monospace;"><?= htmlspecialchars($os['codigo_os'], ENT_QUOTES, 'UTF-8') ?></strong></p>
        </div>
        <a href="/Cycsa/publico/operaciones" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Volver</a>
    </div>

    <form method="POST" action="/Cycsa/publico/operaciones/guardar-hoja-solicitud">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id_os" value="<?= $os['id'] ?>">

        <div class="form-card">
            <!-- 1. METADATOS Y CONTROL INTERNO -->
            <div class="section-title">
                <i class="fa-solid fa-clipboard-check"></i> 1. Metadatos y Control Interno
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Fecha y Hora de Llegada al Laboratorio</label>
                    <input type="datetime-local" name="fecha_hora_llegada_laboratorio" required class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($hoja['fecha_hora_llegada_laboratorio'])) ?>">
                </div>
                <div class="form-group">
                    <label>Código del Documento de Solicitud</label>
                    <input type="text" name="codigo_documento" readonly class="form-control" style="background-color:#f1f5f9; font-weight:700;" value="CYCSA-RT-FM-13">
                </div>
            </div>

            <!-- 2. DATOS DEL CLIENTE -->
            <div class="section-title">
                <i class="fa-solid fa-user-tie"></i> 1. Empresa o Cliente que Solicita el Servicio
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Nombre de la Empresa o Cliente</label>
                    <input type="text" name="nombre_empresa_o_cliente" required class="form-control" value="<?= htmlspecialchars($hoja['nombre_empresa_o_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>Dirección del Proyecto / Obra</label>
                    <input type="text" name="direccion_proyecto" required class="form-control" value="<?= htmlspecialchars($hoja['direccion_proyecto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="grid-3">
                <div class="form-group">
                    <label>Teléfono de Contacto</label>
                    <input type="text" name="telefono" required class="form-control" value="<?= htmlspecialchars($hoja['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>Correo Electrónico para Informes</label>
                    <input type="email" name="correo_electronico" required class="form-control" placeholder="cliente@correo.com" value="<?= htmlspecialchars($hoja['correo_electronico'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>Nombre de la Persona quien trae la muestra</label>
                    <input type="text" name="nombre_persona_entrega_muestra" required class="form-control" placeholder="Nombre completo" value="<?= htmlspecialchars($hoja['nombre_persona_entrega_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <!-- 3. DATOS DE LA MUESTRA -->
            <div class="section-title">
                <i class="fa-solid fa-flask-vial"></i> 1. Datos de la Muestra (Sección 1.1)
            </div>
            <div class="form-group">
                <label style="margin-bottom: 5px;">Naturaleza de la Muestra</label>
                <?php $naturalezas = explode(',', $hoja['naturaleza_muestra'] ?? ''); ?>
                <div class="check-grid">
                    <?php foreach (['Concreto', 'Bloques', 'Suelo', 'Adoquines', 'Agregados', 'Otros materiales'] as $nat): ?>
                        <label class="check-item">
                            <input type="checkbox" name="naturaleza_muestra[]" value="<?= $nat ?>" <?= in_array($nat, $naturalezas) ? 'checked' : '' ?>>
                            <?= $nat ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label>Procedencia/ Punto de muestreo</label>
                    <input type="text" name="procedencia_punto_muestreo" required class="form-control" placeholder="Ej: Eje A-1" value="<?= htmlspecialchars($hoja['procedencia_punto_muestreo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>Persona quien tomó la muestra</label>
                    <input type="text" name="nombre_persona_toma_muestra" required class="form-control" value="<?= htmlspecialchars($hoja['nombre_persona_toma_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>1.2 Fecha y hora en que se tomó la muestra</label>
                    <input type="datetime-local" name="fecha_hora_toma_muestra" required class="form-control" value="<?= !empty($hoja['fecha_hora_toma_muestra']) ? date('Y-m-d\TH:i', strtotime($hoja['fecha_hora_toma_muestra'])) : '' ?>">
                </div>
            </div>

            <!-- 4. IDENTIFICACIÓN PROPIA DE LA MUESTRA (TABLA DINÁMICA) -->
            <div class="section-title" style="display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fa-solid fa-list-ol"></i> 2. Identificaciones Propias de la Muestra</span>
                <button type="button" class="btn-action btn-secondary btn-mini" onclick="agregarFilaMuestra()"><i class="fa-solid fa-plus"></i> Agregar Muestra</button>
            </div>
            
            <table class="spec-table" id="tabla-muestras-dinamica">
                <thead>
                    <tr>
                        <th style="width: 30%;">Nombre de la muestra</th>
                        <th style="width: 40%;">Descripción</th>
                        <th style="width: 25%;">Informaciones importantes</th>
                        <th style="width: 5%; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody id="tbody-muestras-dinamica">
                    <!-- Filas dinámicas -->
                    <?php
                    $identMuestras = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
                    if (empty($identMuestras)) {
                        $nombreDefecto = 'MC-' . sprintf("%03d", $siguienteConsecutivo) . '-' . $anioActual;
                        $identMuestras[] = ['nombre_muestra' => $nombreDefecto, 'descripcion' => 'Cilindros de concreto', 'info_importante' => 'Estándar'];
                        $siguienteConsecutivo++;
                    }
                    foreach ($identMuestras as $idx => $m):
                    ?>
                    <tr>
                        <td><input type="text" name="m_nombre[]" readonly class="form-control" style="width:100%; box-sizing:border-box; background:#f1f5f9; cursor:not-allowed;" value="<?= htmlspecialchars($m['nombre_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td>
                        <td><input type="text" name="m_desc[]" required class="form-control" style="width:100%; box-sizing:border-box;" value="<?= htmlspecialchars($m['descripcion'] ?: 'Cilindros de concreto', ENT_QUOTES, 'UTF-8') ?>"></td>
                        <td><input type="text" name="m_info[]" class="form-control" style="width:100%; box-sizing:border-box;" value="<?= htmlspecialchars($m['info_importante'] ?: 'Estándar', ENT_QUOTES, 'UTF-8') ?>"></td>
                        <td style="text-align: center;"><button type="button" class="btn-action btn-secondary btn-mini" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;" onclick="eliminarFilaMuestra(this)"><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 5. PARÁMETROS SOLICITADOS -->
            <div class="section-title">
                <i class="fa-solid fa-vials"></i> 3. Parámetros Solicitados
            </div>
            
            <h5 style="margin: 0 0 10px 0; font-family:'Outfit'; font-size:14px; color:var(--cycsa-azul);">3.1 Muestra de Concreto, Adoquines, Bloques</h5>
            <div class="check-grid">
                <label class="check-item">
                    <input type="checkbox" name="req_resistencia_concreto" value="1" <?= $hoja['req_resistencia_concreto'] ? 'checked' : '' ?>>
                    Resistencia de conc
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_resistencia_adoquin" value="1" <?= $hoja['req_resistencia_adoquin'] ? 'checked' : '' ?>>
                    Resistencia de adoquin
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_resistencia_bloques" value="1" <?= $hoja['req_resistencia_bloques'] ? 'checked' : '' ?>>
                    Resistencia bloques
                </label>
            </div>
            <div class="form-group" style="margin-top: 10px;">
                <label>Otros (Especificar)</label>
                <input type="text" name="req_otros_concreto" class="form-control" placeholder="Especifique otros ensayos de concreto..." value="<?= htmlspecialchars($hoja['req_otros_concreto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <h5 style="margin: 20px 0 10px 0; font-family:'Outfit'; font-size:14px; color:var(--cycsa-azul);">3.2 Muestras de Suelo</h5>
            <div class="check-grid">
                <label class="check-item">
                    <input type="checkbox" name="req_granulometria" value="1" <?= $hoja['req_granulometria'] ? 'checked' : '' ?>>
                    Granulometría
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_limites_atterberg" value="1" <?= $hoja['req_limites_atterberg'] ? 'checked' : '' ?>>
                    Límites de atterberg
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_humedad" value="1" <?= $hoja['req_humedad'] ? 'checked' : '' ?>>
                    Humedad
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_resistencia_corte" value="1" <?= $hoja['req_resistencia_corte'] ? 'checked' : '' ?>>
                    Resistencia al corte
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_clasificacion_sucs_hr" value="1" <?= $hoja['req_clasificacion_sucs_hr'] ? 'checked' : '' ?>>
                    Clasificación SUCS/HR
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_proctor_sm" value="1" <?= $hoja['req_proctor_sm'] ? 'checked' : '' ?>>
                    PROCTOR S/M
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_infiltracion" value="1" <?= $hoja['req_infiltracion'] ? 'checked' : '' ?>>
                    Infiltración
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_cbr" value="1" <?= $hoja['req_cbr'] ? 'checked' : '' ?>>
                    CBR
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_densidad" value="1" <?= $hoja['req_densidad'] ? 'checked' : '' ?>>
                    Densidad
                </label>
            </div>
            <div class="form-group" style="margin-top: 10px;">
                <label>Otros (Especificar)</label>
                <input type="text" name="req_otros_suelo" class="form-control" placeholder="Especifique otros ensayos de suelos..." value="<?= htmlspecialchars($hoja['req_otros_suelo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <h5 style="margin: 20px 0 10px 0; font-family:'Outfit'; font-size:14px; color:var(--cycsa-azul);">3.3 Otros Materiales</h5>
            <div class="check-grid">
                <label class="check-item">
                    <input type="checkbox" name="req_otros_materiales" value="1" <?= $hoja['req_otros_materiales'] ? 'checked' : '' ?>>
                    Otro
                </label>
            </div>
            <div class="form-group" style="margin-top: 10px;">
                <label>Si seleccionó la casilla otros, favor decir qué análisis necesita</label>
                <textarea name="descripcion_otros_analisis" class="form-control" rows="2" placeholder="Detalle los análisis solicitados..."><?= htmlspecialchars($hoja['descripcion_otros_analisis'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- 6. CIERRE, OBSERVACIONES Y FIRMAS -->
            <div class="section-title">
                <i class="fa-solid fa-signature"></i> Campos Finales / Pie de página
            </div>

            <div class="form-group">
                <label>Análisis adicionales</label>
                <textarea name="analisis_adicionales" class="form-control" rows="2" placeholder="Ej: Ensayos especiales solicitados..."><?= htmlspecialchars($hoja['analisis_adicionales'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones generales..."><?= htmlspecialchars($hoja['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            
            <div class="grid-3" style="margin-top: 15px;">
                <div class="form-group">
                    <label>PERSONA DE CYCSA QUIEN RECIBE LA MUESTRA</label>
                    <input type="text" name="nombre_recibe_cycsa" required class="form-control" placeholder="Nombre del receptor" value="<?= htmlspecialchars($hoja['nombre_recibe_cycsa'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display:flex; align-items:center; height:42px; box-sizing:border-box; width:100%;">
                        <label class="check-item" style="margin:0; width:100%; box-sizing:border-box;">
                            <input type="checkbox" name="firma_recibe_cycsa" value="1" <?= $hoja['firma_recibe_cycsa'] ? 'checked' : '' ?>>
                            ¿Firma Digitalizada Receptor?
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display:flex; align-items:center; height:42px; box-sizing:border-box; width:100%;">
                        <label class="check-item" style="margin:0; width:100%; box-sizing:border-box;">
                            <input type="checkbox" name="firma_cliente" value="1" <?= $hoja['firma_cliente'] ? 'checked' : '' ?>>
                            ¿Firma Digitalizada Cliente?
                        </label>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px;">
                <a href="/Cycsa/publico/operaciones" class="btn-back" style="padding: 12px 24px;">Cancelar</a>
                <button type="submit" class="btn-action btn-primary" style="padding: 12px 28px;"><i class="fa-solid fa-save"></i> Registrar y Generar CYCSA-RT-FM-13</button>
            </div>
        </div>
    </form>
</div>

<script>
    let siguienteConsecutivoMuestra = <?= $siguienteConsecutivo ?>;
    const anioActualMuestra = <?= $anioActual ?>;

    function agregarFilaMuestra() {
        const tbody = document.getElementById('tbody-muestras-dinamica');
        const tr = document.createElement('tr');
        const nextCode = 'MC-' + String(siguienteConsecutivoMuestra).padStart(3, '0') + '-' + anioActualMuestra;
        siguienteConsecutivoMuestra++;

        tr.innerHTML = `
            <td><input type="text" name="m_nombre[]" readonly class="form-control" style="width:100%; box-sizing:border-box; background:#f1f5f9; cursor:not-allowed;" value="${nextCode}"></td>
            <td><input type="text" name="m_desc[]" required class="form-control" style="width:100%; box-sizing:border-box;" value="Cilindros de concreto"></td>
            <td><input type="text" name="m_info[]" class="form-control" style="width:100%; box-sizing:border-box;" value="Estándar"></td>
            <td style="text-align: center;"><button type="button" class="btn-action btn-secondary btn-mini" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;" onclick="eliminarFilaMuestra(this)"><i class="fa-solid fa-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    }

    function eliminarFilaMuestra(btn) {
        const tbody = document.getElementById('tbody-muestras-dinamica');
        if (tbody.children.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('Debe registrar al menos un espécimen en la tabla de identificación.');
        }
    }
</script>

</body>
</html>
