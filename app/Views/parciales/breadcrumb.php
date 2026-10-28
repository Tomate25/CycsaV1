<?php
/**
 * Parcial Global: Breadcrumb / Migas de Pan (Bootstrap 5)
 * 
 * Variables esperadas:
 * @var array<array{label: string, url?: string|null}> $migas Array de niveles de navegación.
 */
if (!empty($migas) && is_array($migas)):
?>
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0 py-1">
        <li class="breadcrumb-item">
            <a href="/Cycsa/publico/panel" class="text-decoration-none text-secondary">
                <i class="fa-solid fa-house-chimney me-1"></i>Panel
            </a>
        </li>
        <?php 
        $totalMigas = count($migas);
        $idx = 0;
        foreach ($migas as $m): 
            $idx++;
            $esUltimo = ($idx === $totalMigas) || empty($m['url']);
        ?>
            <?php if ($esUltimo): ?>
                <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">
                    <?= htmlspecialchars($m['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php else: ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars($m['url'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-secondary">
                        <?= htmlspecialchars($m['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>
