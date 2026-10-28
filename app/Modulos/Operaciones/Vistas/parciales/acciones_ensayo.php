<?php
/**
 * Parcial unificado para renderizar las acciones y estados de un ensayo en Operaciones LIMS.
 * Reutilizado tanto en el render inicial de PHP (Drawer / Acordeón) como en respuestas AJAX.
 *
 * Variables esperadas:
 * @var array $it Datos del ítem/ensayo (id, descripcion_ensayo, norma_astm, formato_nombre, resultados_json, etc.)
 * @var array $o Datos de la orden de servicio (codigo_os, cliente_nombre, cliente_email, etc.)
 * @var bool $tieneHojaServicio Si la O/S tiene registrada la Hoja CYCSA-RT-FM-13
 * @var bool $esSupervisor Si el usuario tiene rol de supervisión (1, 2, 3)
 * @var bool $esCompactacion Si el ensayo es in situ / compactación
 * @var bool $tieneMuestrasAceptadas Si las muestras físicas fueron aceptadas en laboratorio
 */

$rev = obtenerEstadoRevisionMatriz($it['resultados_json'] ?? '');
$tieneRes = !empty($rev['tiene_resultados']);
$clienteNombreEsc = htmlspecialchars(addslashes($o['cliente_nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$clienteEmailEsc = htmlspecialchars(addslashes($o['cliente_email'] ?? ''), ENT_QUOTES, 'UTF-8');
$codigoOsEsc = htmlspecialchars(addslashes($o['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8');
$descEnsayoEsc = htmlspecialchars(addslashes($it['descripcion_ensayo'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="lims-ensayo-actions-cluster d-flex align-items-center gap-2 flex-wrap justify-content-end">
    <!-- CHIP DE ESTADO DE REVISIÓN / CICLO DE VIDA (NO CLICABLE) -->
    <span class="lims-status-chip" style="background-color: <?= $rev['badge_bg'] ?>; color: <?= $rev['badge_color'] ?>; border: 1px solid <?= $rev['badge_border'] ?>; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
        <i class="<?= $rev['badge_icono'] ?>"></i> <?= htmlspecialchars($rev['estado_label']) ?>
    </span>

    <?php if ($rev['estado'] === 'devuelta'): ?>
        <button type="button" 
                onclick="verObservacionDevolucion(<?= (int)$it['id'] ?>, '<?= $descEnsayoEsc ?>', '<?= htmlspecialchars(addslashes($rev['motivo_devolucion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($rev['usuario_revisor'] ?? 'Supervisor'), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($rev['fecha_revision'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')" 
                class="btn btn-outline-danger btn-sm" 
                style="height: 36px; padding: 0 12px; font-size: 12.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px;"
                title="Ver observaciones del supervisor técnico">
            <i class="fa-solid fa-comment-dots"></i> Ver Observación
        </button>
    <?php endif; ?>

    <!-- ACCIONES DE SUPERVISIÓN RÁPIDA (ROLES 1, 2, 3) -->
    <?php if ($esSupervisor && $tieneRes): ?>
        <?php if ($rev['estado'] !== 'aprobada'): ?>
            <button type="button" 
                    onclick="abrirModalAprobarEnIndex(<?= (int)$it['id'] ?>, '<?= $descEnsayoEsc ?>')" 
                    class="btn btn-sm btn-success" 
                    style="height: 36px; padding: 0 14px; font-size: 12.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px; background-color: #16a34a; border-color: #15803d; color: white;" 
                    title="Aprobar técnicamente los resultados del ensayo">
                <i class="fa-solid fa-circle-check"></i> Aprobar
            </button>
        <?php endif; ?>
        <?php if ($rev['estado'] !== 'devuelta'): ?>
            <button type="button" 
                    onclick="abrirModalDevolverEnIndex(<?= (int)$it['id'] ?>, '<?= $descEnsayoEsc ?>')" 
                    class="btn btn-sm btn-outline-danger" 
                    style="height: 36px; padding: 0 14px; font-size: 12.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px;" 
                    title="Devolver matriz con observaciones para corrección técnica">
                <i class="fa-solid fa-rotate-left"></i> Devolver
            </button>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ACCIONES DE CAPTURA / EDICIÓN SEGÚN CANDADO RT-FM-13 Y TRAZABILIDAD -->
    <?php if (!$tieneHojaServicio && !$tieneRes): ?>
        <!-- CANDADO ACTIVO: SIN HOJA RT-FM-13 -->
        <span class="lims-bloqueo-chip" style="height: 36px; padding: 0 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; display: inline-flex; align-items: center; gap: 6px;" title="Bloqueado: Debe registrar primero la Hoja de Solicitud de Servicio CYCSA-RT-FM-13 oficial">
            <i class="fa-solid fa-lock" style="color: #d97706;"></i> RT-FM-13 Pendiente
        </span>
    <?php elseif ($esCompactacion): ?>
        <!-- COMPACTACIÓN / DENSIDAD IN SITU -->
        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px;">
            <i class="fa-solid fa-gauge-high"></i> In Situ
        </span>
        <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $it['id'] ?>" 
           class="btn btn-primary btn-sm" 
           style="height: 36px; padding: 0 16px; font-size: 13px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; background-color: var(--cycsa-azul, #103487); border-color: var(--cycsa-azul, #103487); color: white;">
            <i class="fa-solid fa-pen-to-square"></i> <?= $tieneRes ? 'Editar Matriz' : 'Rellenar Matriz' ?>
        </a>
        <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $it['id'] ?>" 
           target="_blank" 
           class="btn btn-outline-secondary btn-sm" 
           style="height: 36px; padding: 0 12px; font-size: 12.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px;" 
           title="Imprimir Matriz Técnica Oficial con Membrete CYCSA (Horizontal)">
            <i class="fa-solid fa-print"></i> Imprimir
        </a>
        <?php if ($rev['estado'] === 'aprobada'): ?>
            <button type="button" 
                    onclick="abrirModalEnviarMatriz(<?= (int)$it['id'] ?>, '<?= $descEnsayoEsc ?>', '<?= $clienteNombreEsc ?>', '<?= $clienteEmailEsc ?>', '<?= $codigoOsEsc ?>')" 
                    class="btn btn-sm btn-success" 
                    style="height: 36px; padding: 0 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; background-color: #059669; color: white; border-color: #047857;" 
                    title="Enviar Matriz Oficial en PDF al Correo del Cliente">
                <i class="fa-solid fa-paper-plane"></i> Enviar al Cliente
            </button>
        <?php endif; ?>
    <?php elseif (!$tieneMuestrasAceptadas && !$tieneRes): ?>
        <!-- ESPERANDO ACEPTACIÓN FÍSICA EN LAB -->
        <span class="lims-bloqueo-chip" style="height: 36px; padding: 0 14px; font-size: 12.5px; font-weight: 600; border-radius: 6px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; display: inline-flex; align-items: center; gap: 6px;" title="Las muestras físicas deben ser recibidas y aceptadas en el módulo de Laboratorio">
            <i class="fa-solid fa-hourglass-half"></i> Custodia Lab Pendiente
        </span>
    <?php else: ?>
        <!-- ENSAYO CON MUESTRAS EN CUSTODIA / LISTO PARA MATRIZ -->
        <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $it['id'] ?>" 
           class="btn btn-primary btn-sm" 
           style="height: 36px; padding: 0 16px; font-size: 13px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; background-color: var(--cycsa-azul, #103487); border-color: var(--cycsa-azul, #103487); color: white;">
            <i class="fa-solid fa-pen-to-square"></i> <?= $tieneRes ? 'Editar Matriz' : 'Rellenar Matriz' ?>
        </a>
        <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $it['id'] ?>" 
           target="_blank" 
           class="btn btn-outline-secondary btn-sm" 
           style="height: 36px; padding: 0 12px; font-size: 12.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px;" 
           title="Imprimir Matriz Técnica Oficial con Membrete CYCSA (Horizontal)">
            <i class="fa-solid fa-print"></i> Imprimir
        </a>
        <?php if ($rev['estado'] === 'aprobada'): ?>
            <button type="button" 
                    onclick="abrirModalEnviarMatriz(<?= (int)$it['id'] ?>, '<?= $descEnsayoEsc ?>', '<?= $clienteNombreEsc ?>', '<?= $clienteEmailEsc ?>', '<?= $codigoOsEsc ?>')" 
                    class="btn btn-sm btn-success" 
                    style="height: 36px; padding: 0 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; background-color: #059669; color: white; border-color: #047857;" 
                    title="Enviar Matriz Oficial en PDF al Correo del Cliente">
                <i class="fa-solid fa-paper-plane"></i> Enviar al Cliente
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>
