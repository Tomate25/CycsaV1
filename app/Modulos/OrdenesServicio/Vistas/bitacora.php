<?php
/**
 * Bitácora cronológica de una Orden de Servicio. Solo lectura.
 *
 * @var array $os
 * @var array $filtros
 * @var array $bitacora_logs
 */
$filtros = $filtros ?? [];
$idOS = (int)($os['id'] ?? 0);
$codigo = (string)($os['codigo_os'] ?? '');
?>
<style>
    .os-bitacora-wrap { max-width: 980px; margin: 0 auto 40px auto; }
    .os-bitacora-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .os-bitacora-head h1 { margin: 0; font-size: 22px; color: #0f172a; }
    .os-bitacora-head p { margin: 4px 0 0 0; color: #64748b; font-size: 13.5px; }
    .os-bitacora-filtros { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 8px; }
    .os-bitacora-filtros label { display: flex; flex-direction: column; gap: 4px; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .04em; }
    .os-bitacora-filtros input, .os-bitacora-filtros select { min-width: 140px; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; }
    .os-bitacora-filtros button, .os-bitacora-filtros a.btn-limpiar { height: 36px; padding: 0 14px; border-radius: 6px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; }
    .os-bitacora-filtros button { background: #103487; color: #fff; border: none; cursor: pointer; }
    .os-bitacora-filtros a.btn-limpiar { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
</style>

<div class="os-bitacora-wrap">
    <div class="os-bitacora-head">
        <div>
            <h1><i class="fa-solid fa-clock-rotate-left" style="color:#103487;"></i> Bitácora <?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Quién hizo qué, cuándo y sobre qué entidad. Los registros no se editan ni se borran desde la aplicación.</p>
        </div>
        <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $idOS ?>" style="color:#103487; font-weight:700; text-decoration:none;">Volver a la orden</a>
    </div>

    <form method="get" action="/Cycsa/publico/ordenes-servicio/bitacora" class="os-bitacora-filtros">
        <input type="hidden" name="id" value="<?= $idOS ?>">
        <label>Usuario
            <input type="text" name="usuario" value="<?= htmlspecialchars((string)($filtros['usuario'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre o id">
        </label>
        <label>Acción
            <input type="text" name="accion" value="<?= htmlspecialchars((string)($filtros['accion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="aprobar_matriz">
        </label>
        <label>Entidad
            <select name="entidad_tipo">
                <option value="">Todas</option>
                <?php foreach (['os' => 'O/S', 'matriz' => 'Matriz', 'rtfm13' => 'RT-FM-13', 'muestra' => 'Muestra', 'otro' => 'Otro'] as $clave => $etiq): ?>
                    <option value="<?= $clave ?>" <?= (($filtros['entidad_tipo'] ?? '') === $clave) ? 'selected' : '' ?>><?= $etiq ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Id entidad
            <input type="number" min="1" name="entidad_id" value="<?= !empty($filtros['entidad_id']) ? (int)$filtros['entidad_id'] : '' ?>">
        </label>
        <label>Desde
            <input type="date" name="desde" value="<?= htmlspecialchars((string)($filtros['desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>Hasta
            <input type="date" name="hasta" value="<?= htmlspecialchars((string)($filtros['hasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <button type="submit">Filtrar</button>
        <a class="btn-limpiar" href="/Cycsa/publico/ordenes-servicio/bitacora?id=<?= $idOS ?>">Limpiar</a>
    </form>

<?php
$bitacora_modulo_nombre = $bitacora_modulo_nombre ?? ('Orden de Servicio ' . $codigo);
$bitacora_limite = $bitacora_limite ?? 300;
$bitacora_abierta = true;
require dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
</div>
