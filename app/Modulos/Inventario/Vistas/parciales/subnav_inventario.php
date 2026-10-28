<?php
/**
 * Parcial de Subnavegación: Módulo de Inventario (Bootstrap 5)
 */
$uriActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (!isset($subnavActiva)) {
    if (str_contains($uriActual, '/inventario/lotes')) {
        $subnavActiva = 'lotes';
    } elseif (str_contains($uriActual, '/inventario/kardex')) {
        $subnavActiva = 'kardex';
    } else {
        $subnavActiva = 'stock';
    }
}

$items = [
    'stock' => [
        'label' => 'Stock e Insumos',
        'url' => '/Cycsa/publico/inventario',
        'icon' => 'fa-layer-group'
    ],
    'lotes' => [
        'label' => 'Lotes y Caducidades',
        'url' => '/Cycsa/publico/inventario/lotes',
        'icon' => 'fa-tags'
    ],
    'kardex' => [
        'label' => 'Kárdex de Movimientos',
        'url' => '/Cycsa/publico/inventario/kardex',
        'icon' => 'fa-clock-rotate-left'
    ]
];
?>
<ul class="nav nav-pills p-1 bg-light rounded-3 mb-4 gap-1 border">
    <?php foreach ($items as $clave => $item): 
        $isActive = ($subnavActiva === $clave);
    ?>
        <li class="nav-item">
            <a class="nav-link py-2 px-3 fw-medium <?= $isActive ? 'active shadow-sm' : 'text-secondary' ?>" 
               href="<?= $item['url'] ?>" 
               <?= $isActive ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid <?= $item['icon'] ?> me-2"></i><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                <?php if ($clave === 'lotes' && !empty($alertas)): 
                    $badgeAlertas = ($alertas['conteo_por_vencer'] ?? 0) + ($alertas['conteo_vencidos'] ?? 0);
                    if ($badgeAlertas > 0):
                ?>
                    <span class="badge bg-danger rounded-pill ms-1"><?= $badgeAlertas ?></span>
                <?php 
                    endif;
                endif; 
                ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
