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
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/ordenes-servicio-sobrio.css">
<style>
    .os-bitacora-wrap { width: 100%; max-width: 100%; margin: 0 0 40px 0; }
    .os-bitacora-filtros { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
    .os-bitacora-filtros label { display: flex; flex-direction: column; gap: 4px; }
    .os-bitacora-filtros input, .os-bitacora-filtros select { min-width: 140px; }
</style>

<div class="container-fluid px-0 py-0 cy-os-wrap os-bitacora-wrap">
    <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $idOS ?>" class="cy-back-link"><i class="fa-solid fa-arrow-left"></i> Volver a la orden</a>
    <div class="cy-os-header">
        <div class="cy-os-header-left">
            <h1 class="cy-page-title">Bitácora <?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="cy-meta">Quién hizo qué, cuándo y sobre qué entidad. Los registros no se editan ni se borran desde la aplicación.</div>
        </div>
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
        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        <a class="btn btn-outline-secondary btn-sm btn-limpiar" href="/Cycsa/publico/ordenes-servicio/bitacora?id=<?= $idOS ?>">Limpiar</a>
    </form>

<?php
$bitacora_modulo_nombre = $bitacora_modulo_nombre ?? ('Orden de Servicio ' . $codigo);
$bitacora_limite = $bitacora_limite ?? 300;
$bitacora_abierta = true;
require dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
</div>
