<?php
/**
 * Subnavegación: Módulo de Control de Calidad (ISO/IEC 17025)
 * Pestaña 1: Réplicas de Ensayos de Control de Calidad (-CR)
 * Pestaña 2: Calibración de Equipos y Metrología
 */
$subnavQcActiva = $subnavQcActiva ?? 'replicas';
?>
<ul class="nav nav-pills p-1 bg-light rounded-3 mb-4 gap-1 border">
    <li class="nav-item">
        <a class="nav-link py-2 px-3 fw-medium <?= $subnavQcActiva === 'replicas' ? 'active shadow-sm' : 'text-secondary' ?>"
           href="/Cycsa/publico/control-calidad">
            <i class="fa-solid fa-code-compare me-2"></i>Réplicas de Ensayos (-CR)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-2 px-3 fw-medium <?= $subnavQcActiva === 'calibracion' ? 'active shadow-sm' : 'text-secondary' ?>"
           href="/Cycsa/publico/control-calidad/calibracion">
            <i class="fa-solid fa-balance-scale me-2"></i>Calibración de Equipos (ISO 17025)
        </a>
    </li>
</ul>
