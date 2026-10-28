<?php
/**
 * Parcial de Subnavegación: Módulo Contable (Bootstrap 5)
 */
$uriActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (!isset($subnavActiva)) {
    if (str_contains($uriActual, '/contabilidad/diario')) {
        $subnavActiva = 'diario';
    } elseif (str_contains($uriActual, '/contabilidad/cxc')) {
        $subnavActiva = 'cxc';
    } elseif (str_contains($uriActual, '/contabilidad/cxp')) {
        $subnavActiva = 'cxp';
    } elseif (str_contains($uriActual, '/contabilidad/bancos')) {
        $subnavActiva = 'bancos';
    } elseif (str_contains($uriActual, '/contabilidad/balance')) {
        $subnavActiva = 'balance';
    } elseif (str_contains($uriActual, '/contabilidad/resultados')) {
        $subnavActiva = 'resultados';
    } else {
        $subnavActiva = 'cuentas';
    }
}

$items = [
    'cuentas' => [
        'label' => 'Catálogo',
        'url' => '/Cycsa/publico/contabilidad/cuentas',
        'icon' => 'fa-list-ol'
    ],
    'diario' => [
        'label' => 'Libro Diario',
        'url' => '/Cycsa/publico/contabilidad/diario',
        'icon' => 'fa-book'
    ],
    'cxc' => [
        'label' => 'Cobros (CXC)',
        'url' => '/Cycsa/publico/contabilidad/cxc',
        'icon' => 'fa-file-invoice-dollar'
    ],
    'cxp' => [
        'label' => 'Pagos (CXP)',
        'url' => '/Cycsa/publico/contabilidad/cxp',
        'icon' => 'fa-credit-card'
    ],
    'bancos' => [
        'label' => 'Bancos',
        'url' => '/Cycsa/publico/contabilidad/bancos',
        'icon' => 'fa-building-columns'
    ],
    'balance' => [
        'label' => 'Balance General',
        'url' => '/Cycsa/publico/contabilidad/balance',
        'icon' => 'fa-scale-balanced'
    ],
    'resultados' => [
        'label' => 'Estado de Resultados',
        'url' => '/Cycsa/publico/contabilidad/resultados',
        'icon' => 'fa-chart-line'
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
            </a>
        </li>
    <?php endforeach; ?>
</ul>
