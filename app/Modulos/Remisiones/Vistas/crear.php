<?php
$ordenes = $ordenes ?? [];
$codigoSugerido = $codigoSugerido ?? 'RLAB-' . date('m-Y') . '-1';
$idOsPreseleccionada = (int)($idOsPreseleccionada ?? 0);
$ordenPreseleccionada = $ordenPreseleccionada ?? null;
$informesPreseleccionados = $informesPreseleccionados ?? [];
$entregadoPorDefault = $entregadoPorDefault ?? 'Recepción CYCSA';
$csrfToken = $csrfToken ?? ($_SESSION['csrf_token'] ?? '');
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/remisiones-sobrio.css">

<div class="container-fluid px-0 py-0 cy-rem-wrap">
    <a href="/Cycsa/publico/remisiones" class="cy-rem-back-link">&larr; Volver a Remisiones</a>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="alert">
            <span class="cy-pill cy-pill-danger">Aviso</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="cy-rem-header">
        <div class="cy-rem-header-left">
            <h1 class="cy-page-title">Nueva Remisión de Entrega (CYCSA-RG-FM-41)</h1>
            <div class="cy-meta">Consecutivo estimado: <strong><?= htmlspecialchars($codigoSugerido, ENT_QUOTES, 'UTF-8') ?></strong></div>
        </div>
    </div>

    <form method="POST" action="/Cycsa/publico/remisiones/guardar" id="form-crear-remision">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_cliente" id="rem_id_cliente" value="<?= (int)($ordenPreseleccionada['id_cliente'] ?? 0) ?>">

        <!-- Sección 1: Orden de Servicio y Cliente -->
        <div class="cy-rem-card">
            <div class="cy-rem-card-head">
                <span>1. Orden de Servicio y Modalidad de Entrega</span>
                <span class="cy-meta">Formato oficial CYCSA-RG-FM-41</span>
            </div>
            <div class="cy-rem-card-body">
                <div class="cy-rem-grid-2 mb-3">
                    <div>
                        <label for="rem_id_os" class="form-label">Orden de Servicio (Opcional — autopobla cliente e informes)</label>
                        <select name="id_os" id="rem_id_os" class="form-select">
                            <option value="">— Seleccionar Orden de Servicio —</option>
                            <?php foreach ($ordenes as $os): ?>
                                <option
                                    value="<?= (int)$os['id'] ?>"
                                    <?= $idOsPreseleccionada === (int)$os['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars((string)$os['codigo_os'], ENT_QUOTES, 'UTF-8') ?>
                                    — <?= htmlspecialchars((string)($os['cliente_nombre'] ?: 'Sin cliente'), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($os['nombre_proyecto'])): ?>
                                        (<?= htmlspecialchars((string)$os['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="cy-meta mt-1" id="rem-estado-carga-os">Seleccione una O/S para cargar automáticamente empresa e informes disponibles.</div>
                    </div>

                    <div>
                        <label for="rem_empresa_nombre" class="form-label">Empresa / Cliente <span style="color: var(--cy-danger);">*</span></label>
                        <input
                            type="text"
                            name="empresa_nombre"
                            id="rem_empresa_nombre"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars((string)($ordenPreseleccionada['empresa_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Razón social o nombre del cliente"
                        >
                    </div>
                </div>

                <div class="cy-rem-grid-3">
                    <div>
                        <label for="rem_modalidad" class="form-label">Modalidad de Entrega <span style="color: var(--cy-danger);">*</span></label>
                        <select name="modalidad_entrega" id="rem_modalidad" class="form-select" required>
                            <option value="laboratorio">Entrega en Laboratorio</option>
                            <option value="domicilio">Entrega a Domicilio</option>
                        </select>
                    </div>

                    <div>
                        <label for="rem_fecha_entrega" class="form-label">Fecha y Hora de Entrega</label>
                        <input
                            type="datetime-local"
                            name="fecha_entrega"
                            id="rem_fecha_entrega"
                            class="form-control"
                            value="<?= date('Y-m-d\TH:i') ?>"
                        >
                    </div>

                    <div>
                        <label for="rem_entregado_por" class="form-label">Entregado por (Personal CYCSA)</label>
                        <input
                            type="text"
                            name="entregado_por"
                            id="rem_entregado_por"
                            class="form-control"
                            value="<?= htmlspecialchars($entregadoPorDefault, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 2: Datos del Receptor y Dirección -->
        <div class="cy-rem-card">
            <div class="cy-rem-card-head">
                <span>2. Datos del Receptor y Dirección de Entrega</span>
            </div>
            <div class="cy-rem-card-body">
                <div class="cy-rem-grid-3 mb-3">
                    <div>
                        <label for="rem_persona_recibe" class="form-label">Persona que Recibe</label>
                        <input
                            type="text"
                            name="persona_recibe"
                            id="rem_persona_recibe"
                            class="form-control"
                            value="<?= htmlspecialchars((string)($ordenPreseleccionada['atencion_a'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Nombre completo del receptor"
                        >
                    </div>

                    <div>
                        <label for="rem_cedula_recibe" class="form-label">Cédula / Identificación</label>
                        <input
                            type="text"
                            name="cedula_recibe"
                            id="rem_cedula_recibe"
                            class="form-control"
                            placeholder="Ej. 281-010190-0001A"
                        >
                    </div>

                    <div>
                        <label for="rem_telefono_recibe" class="form-label">Teléfono de Contacto</label>
                        <input
                            type="text"
                            name="telefono_recibe"
                            id="rem_telefono_recibe"
                            class="form-control"
                            value="<?= htmlspecialchars((string)($ordenPreseleccionada['cliente_telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ej. 8888-0000"
                        >
                    </div>
                </div>

                <div class="cy-rem-grid-2">
                    <div>
                        <label for="rem_direccion_entrega" class="form-label">Dirección de Entrega</label>
                        <input
                            type="text"
                            name="direccion_entrega"
                            id="rem_direccion_entrega"
                            class="form-control"
                            value="<?= htmlspecialchars((string)($ordenPreseleccionada['direccion_entrega'] ?? 'Instalaciones Laboratorio CYCSA — León'), ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Dirección física de entrega o sede CYCSA"
                        >
                    </div>

                    <div>
                        <label for="rem_observaciones" class="form-label">Observaciones</label>
                        <input
                            type="text"
                            name="observaciones"
                            id="rem_observaciones"
                            class="form-control"
                            placeholder="Notas adicionales sobre la entrega, anexos o copias"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 3: Informes Incluidos en la Remisión -->
        <div class="cy-rem-card">
            <div class="cy-rem-card-head">
                <span>3. Informes de Ensayo Incluidos</span>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-agregar-informe-manual">
                    + Agregar Informe Manual
                </button>
            </div>
            <div class="table-responsive">
                <table class="table" id="tabla-items-remision">
                    <thead>
                        <tr>
                            <th style="width: 56px;">Incluir</th>
                            <th style="width: 220px;">Código de Informe</th>
                            <th>Descripción del Ensayo / Matriz</th>
                            <th style="width: 190px;">Formato de Entrega</th>
                            <th style="width: 140px;">Control PT-13</th>
                            <th style="width: 80px;" class="text-end">Quitar</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-items-remision">
                        <?php if (!empty($informesPreseleccionados)): ?>
                            <?php foreach ($informesPreseleccionados as $idx => $inf): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="item_incluir[]" value="<?= (int)$idx ?>" checked aria-label="Incluir informe">
                                    <input type="hidden" name="item_id_detalle_cotizacion[<?= (int)$idx ?>]" value="<?= htmlspecialchars((string)($inf['id_detalle_cotizacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="item_id_informe_control[<?= (int)$idx ?>]" value="<?= htmlspecialchars((string)($inf['id_informe_control'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td>
                                    <input type="text" name="item_codigo_informe[<?= (int)$idx ?>]" class="form-control" required value="<?= htmlspecialchars((string)$inf['codigo_informe'], ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td>
                                    <input type="text" name="item_descripcion_ensayo[<?= (int)$idx ?>]" class="form-control" value="<?= htmlspecialchars((string)$inf['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td>
                                    <select name="item_formato_entrega[<?= (int)$idx ?>]" class="form-select">
                                        <option value="Digital PDF" selected>Digital PDF</option>
                                        <option value="Impreso Original">Impreso Original</option>
                                        <option value="Ambos">Ambos</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="cy-pill <?= ($inf['estado_pt13'] ?? '') === 'Autorizado' || ($inf['estado_pt13'] ?? '') === 'Aprobado' ? 'cy-pill-ok' : 'cy-pill-pend' ?>">
                                        <?= htmlspecialchars((string)($inf['estado_pt13'] ?? 'Pendiente'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-quitar-fila">Quitar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="item_incluir[]" value="0" checked aria-label="Incluir informe">
                                    <input type="hidden" name="item_id_detalle_cotizacion[0]" value="">
                                    <input type="hidden" name="item_id_informe_control[0]" value="">
                                </td>
                                <td>
                                    <input type="text" name="item_codigo_informe[0]" class="form-control" required placeholder="Ej. INF-2026-0001">
                                </td>
                                <td>
                                    <input type="text" name="item_descripcion_ensayo[0]" class="form-control" placeholder="Descripción del ensayo o informe entregado">
                                </td>
                                <td>
                                    <select name="item_formato_entrega[0]" class="form-select">
                                        <option value="Digital PDF" selected>Digital PDF</option>
                                        <option value="Impreso Original">Impreso Original</option>
                                        <option value="Ambos">Ambos</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="cy-pill cy-pill-pend">Manual</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-quitar-fila">Quitar</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cy-rem-form-footer">
            <a href="/Cycsa/publico/remisiones" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Emitir Remisión (RG-FM-41)</button>
        </div>
    </form>
</div>

<script>
(function () {
    const selectOs = document.getElementById('rem_id_os');
    const tbody = document.getElementById('tbody-items-remision');
    const btnAgregar = document.getElementById('btn-agregar-informe-manual');
    const estadoCarga = document.getElementById('rem-estado-carga-os');
    const inputEmpresa = document.getElementById('rem_empresa_nombre');
    const inputClienteId = document.getElementById('rem_id_cliente');
    const inputPersona = document.getElementById('rem_persona_recibe');
    const inputTel = document.getElementById('rem_telefono_recibe');
    const inputDir = document.getElementById('rem_direccion_entrega');

    let nextIdx = tbody.querySelectorAll('tr').length + 1;

    function escapar(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function crearFila(item) {
        const idx = nextIdx++;
        const tr = document.createElement('tr');
        const estadoPt = item.estado_pt13 || 'Manual';
        const pillClass = (estadoPt === 'Autorizado' || estadoPt === 'Aprobado') ? 'cy-pill-ok' : 'cy-pill-pend';

        tr.innerHTML = ''
            + '<td>'
            + '<input type="checkbox" name="item_incluir[]" value="' + idx + '" checked aria-label="Incluir informe">'
            + '<input type="hidden" name="item_id_detalle_cotizacion[' + idx + ']" value="' + escapar(item.id_detalle_cotizacion || '') + '">'
            + '<input type="hidden" name="item_id_informe_control[' + idx + ']" value="' + escapar(item.id_informe_control || '') + '">'
            + '</td>'
            + '<td><input type="text" name="item_codigo_informe[' + idx + ']" class="form-control" required value="' + escapar(item.codigo_informe || '') + '" placeholder="Ej. INF-2026-0001"></td>'
            + '<td><input type="text" name="item_descripcion_ensayo[' + idx + ']" class="form-control" value="' + escapar(item.descripcion_ensayo || '') + '" placeholder="Descripción del ensayo"></td>'
            + '<td>'
            + '<select name="item_formato_entrega[' + idx + ']" class="form-select">'
            + '<option value="Digital PDF"' + ((item.formato_entrega || 'Digital PDF') === 'Digital PDF' ? ' selected' : '') + '>Digital PDF</option>'
            + '<option value="Impreso Original"' + (item.formato_entrega === 'Impreso Original' ? ' selected' : '') + '>Impreso Original</option>'
            + '<option value="Ambos"' + (item.formato_entrega === 'Ambos' ? ' selected' : '') + '>Ambos</option>'
            + '</select>'
            + '</td>'
            + '<td><span class="cy-pill ' + pillClass + '">' + escapar(estadoPt) + '</span></td>'
            + '<td class="text-end"><button type="button" class="btn btn-outline-secondary btn-sm btn-quitar-fila">Quitar</button></td>';
        return tr;
    }

    tbody.addEventListener('click', function (ev) {
        if (ev.target && ev.target.classList.contains('btn-quitar-fila')) {
            const filas = tbody.querySelectorAll('tr');
            if (filas.length > 1) {
                ev.target.closest('tr').remove();
            }
        }
    });

    btnAgregar.addEventListener('click', function () {
        tbody.appendChild(crearFila({}));
    });

    selectOs.addEventListener('change', function () {
        const idOs = selectOs.value;
        if (!idOs) {
            estadoCarga.textContent = 'Seleccione una O/S para cargar automáticamente empresa e informes disponibles.';
            return;
        }
        estadoCarga.textContent = 'Cargando datos e informes de la Orden de Servicio...';

        fetch('/Cycsa/publico/remisiones/api-informes-os?id_os=' + encodeURIComponent(idOs), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok || !data.orden) {
                    estadoCarga.textContent = (data && data.error) ? data.error : 'No se pudieron cargar los datos de la O/S.';
                    return;
                }
                const ord = data.orden;
                inputClienteId.value = ord.id_cliente || '';
                if (ord.empresa_nombre) inputEmpresa.value = ord.empresa_nombre;
                if (ord.atencion_a) inputPersona.value = ord.atencion_a;
                if (ord.cliente_telefono) inputTel.value = ord.cliente_telefono;
                if (ord.direccion_entrega) inputDir.value = ord.direccion_entrega;

                tbody.innerHTML = '';
                const informes = Array.isArray(data.informes) ? data.informes : [];
                if (informes.length > 0) {
                    informes.forEach(function (inf) {
                        tbody.appendChild(crearFila(inf));
                    });
                    estadoCarga.textContent = 'Se cargaron ' + informes.length + ' informe(s) disponible(s) de ' + (ord.codigo_os || 'la O/S') + '.';
                } else {
                    tbody.appendChild(crearFila({}));
                    estadoCarga.textContent = 'La O/S no tiene informes previos; puede agregar códigos manualmente.';
                }
            })
            .catch(function () {
                estadoCarga.textContent = 'Error de conexión al consultar los informes de la Orden de Servicio.';
            });
    });
})();
</script>
