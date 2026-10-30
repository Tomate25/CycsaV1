<?php
/**
 * Checklist interactivo de permisos por módulo y acción.
 * Espera variable $permisos (array asociativo).
 */
$permisos = $permisos ?? [];

// Helper local para consultar si tiene la marca activa
$pActivo = function(string $mod, string $acc, bool $default = false) use ($permisos): bool {
    if (isset($permisos[$mod][$acc])) {
        return (bool)$permisos[$mod][$acc];
    }
    return $default;
};

$gruposModulos = [
    'Comercial & Clientes' => [
        'icono' => 'fa-briefcase',
        'color' => '#103487',
        'modulos' => [
            'clientes' => [
                'nombre' => 'Clientes',
                'icono' => 'fa-users',
                'desc' => 'Gestión de cartera de clientes comerciales y fiscales.',
                'acciones' => [
                    'ver' => ['Visualizar Clientes', 'Buscar, filtrar y consultar la lista general de clientes.'],
                    'crear_editar' => ['Registrar / Editar Clientes', 'Dar de alta nuevos clientes y modificar sus fichas.']
                ]
            ],
            'productos' => [
                'nombre' => 'Productos / Catálogo de Ensayos',
                'icono' => 'fa-flask-vial',
                'desc' => 'Catálogo técnico de ensayos, normas ASTM y tarifas comerciales.',
                'acciones' => [
                    'ver' => ['Ver Catálogo General', 'Consultar ensayos y servicios disponibles con sus códigos y precios.'],
                    'crear_editar' => ['Crear / Editar Ensayos', 'Agregar nuevos ensayos, modificar normas y tarifas.']
                ]
            ],
            'cotizaciones' => [
                'nombre' => 'Cotizaciones',
                'icono' => 'fa-file-invoice-dollar',
                'desc' => 'Elaboración, presupuestos, descuentos y ciclo comercial.',
                'acciones' => [
                    'ver' => ['Ver Cotizaciones', 'Lectura al listado histórico de cotizaciones.'],
                    'crear_editar' => ['Crear / Editar Cotizaciones', 'Elaborar propuestas, configurar ensayos y emitir cotizaciones.'],
                    'aprobar' => ['Supervisar / Aprobar', 'Revisión formal de cotizaciones para aprobarlas o rechazarlas.']
                ]
            ],
            'ordenes_servicio' => [
                'nombre' => 'Órdenes de Servicio',
                'icono' => 'fa-file-contract',
                'desc' => 'Gestión operativa CYCSA-RG-FM-39 V1 y hojas de servicio de campo.',
                'acciones' => [
                    'ver' => ['Ver Órdenes de Servicio', 'Consultar las órdenes de servicio generadas y sus estados.'],
                    'crear_editar' => ['Crear / Editar Órdenes', 'Generar nuevas órdenes desde cotizaciones aprobadas y asignar técnicos.'],
                    'aprobar' => ['Aprobar / Cerrar Órdenes', 'Validación y cierre formal de órdenes de servicio.']
                ]
            ],
            'ventas' => [
                'nombre' => 'Ventas y Facturación',
                'icono' => 'fa-cash-register',
                'desc' => 'Facturación comercial, cobros y saldos por cobrar.',
                'acciones' => [
                    'ver' => ['Ver Facturas y Cobros', 'Consultar facturación y cuentas por cobrar asociadas a órdenes.'],
                    'crear_editar' => ['Emitir Facturas y Cobros', 'Registrar pagos, notas de crédito y abonos de clientes.']
                ]
            ]
        ]
    ],
    'Laboratorio & Operaciones (LIMS)' => [
        'icono' => 'fa-microscope',
        'color' => '#0284c7',
        'modulos' => [
            'operaciones' => [
                'nombre' => 'Operaciones LIMS',
                'icono' => 'fa-calendar-days',
                'desc' => 'Programación de campo, cronograma de ensayos y captura matricial.',
                'acciones' => [
                    'ver' => ['Ver Operaciones y Calendario', 'Consultar programación de ensayos y fechas programadas.'],
                    'crear_editar' => ['Gestionar Cronograma / Captura', 'Re-agendar fechas, gestionar hojas de servicio y captura matricial.']
                ]
            ],
            'control_calidad' => [
                'nombre' => 'Control de Calidad (CC)',
                'icono' => 'fa-code-compare',
                'desc' => 'Comparación de muestras originales contra réplicas -CR y dictamen.',
                'acciones' => [
                    'ver' => ['Ver Módulos de Calidad', 'Consultar tableros de control de calidad y réplicas.'],
                    'crear_editar' => ['Dictaminar Réplicas CC', 'Ingresar y validar dictámenes de control de calidad.']
                ]
            ],
            'laboratorio' => [
                'nombre' => 'Laboratorio de Ensayos',
                'icono' => 'fa-flask',
                'desc' => 'Rupturas, mediciones técnicas e informes oficiales de resultados.',
                'acciones' => [
                    'ver' => ['Ver Muestras y Rupturas', 'Seguimiento al estado de especímenes y rupturas.'],
                    'crear_editar' => ['Cargar Resultados e Informes', 'Ingresar mediciones analíticas y emitir informes oficiales.']
                ]
            ],
            'equipos_bodegas' => [
                'nombre' => 'Bodegas de Equipos & Traslados',
                'icono' => 'fa-warehouse',
                'desc' => 'Ubicación, custodia, traslados y calibración de instrumentos.',
                'acciones' => [
                    'ver' => ['Ver Equipos y Bodegas', 'Consultar inventario de equipos y su estado de calibración.'],
                    'crear_editar' => ['Registrar Traslados / Calibración', 'Asentar traslados a proyectos y registros metrológicos.']
                ]
            ],
            'plantillas' => [
                'nombre' => 'Plantillas Técnicas ISO 17025',
                'icono' => 'fa-file-shield',
                'desc' => 'Formatos oficiales de informe y cálculo técnico por ensayo.',
                'acciones' => [
                    'ver' => ['Ver Plantillas Oficiales', 'Consultar configuraciones de formatos y plantillas de informe.'],
                    'crear_editar' => ['Editar Parámetros de Plantillas', 'Modificar fórmulas y parámetros de cálculo ISO 17025.']
                ]
            ]
        ]
    ],
    'Administración & Finanzas ERP' => [
        'icono' => 'fa-building-columns',
        'color' => '#15803d',
        'modulos' => [
            'inventario' => [
                'nombre' => 'Inventario y Kárdex',
                'icono' => 'fa-boxes-stacked',
                'desc' => 'Materiales, reactivos, stock y consumos PEPS/FIFO por ensayo.',
                'acciones' => [
                    'ver' => ['Ver Existencias y Kárdex', 'Consultar existencias de materiales y reactivos en bodegas.'],
                    'crear_editar' => ['Registrar Entradas / Salidas', 'Asentar movimientos, transferencias y ajustes de inventario.']
                ]
            ],
            'compras' => [
                'nombre' => 'Compras y Proveedores',
                'icono' => 'fa-cart-shopping',
                'desc' => 'Proveedores calificados, solicitudes y órdenes de compra.',
                'acciones' => [
                    'ver' => ['Consultar Compras y Proveedores', 'Ver historial de compras y directorio de proveedores calificados.'],
                    'crear_editar' => ['Gestionar Órdenes de Compra', 'Generar requerimientos de compra y tramitar autorizaciones.']
                ]
            ],
            'contabilidad' => [
                'nombre' => 'Contabilidad General ERP',
                'icono' => 'fa-calculator',
                'desc' => 'Catálogo de cuentas, libro diario, balances y estados financieros.',
                'acciones' => [
                    'ver' => ['Ver Informes Contables', 'Consultar catálogo de cuentas, balances y estados de resultados.'],
                    'crear_editar' => ['Gestionar Asientos y Cuentas', 'Registrar pólizas/asientos manuales y conciliar bancos.']
                ]
            ],
            'nomina' => [
                'nombre' => 'Nómina & RRHH',
                'icono' => 'fa-id-card-clip',
                'desc' => 'Colaboradores, liquidaciones, provisiones y planillas de pago.',
                'acciones' => [
                    'ver' => ['Ver Planillas y Empleados', 'Consultar expediente de empleados y reportes de nómina.'],
                    'crear_editar' => ['Procesar Pagos y Planilla', 'Calcular devengados, deducciones de ley y emitir pagos.']
                ]
            ],
            'prestamos' => [
                'nombre' => 'Préstamos Bancarios',
                'icono' => 'fa-landmark',
                'desc' => 'Financiamientos, tablas de amortización francesa y pagos.',
                'acciones' => [
                    'ver' => ['Ver Financiamientos', 'Consultar líneas de crédito y tablas de amortización.'],
                    'crear_editar' => ['Gestionar Créditos y Cuotas', 'Registrar contratos de deuda y asentar pagos de cuotas.']
                ]
            ],
            'activos_fijos' => [
                'nombre' => 'Activos Fijos',
                'icono' => 'fa-truck-ramp-box',
                'desc' => 'Depreciación en línea recta y vida útil de maquinaria/vehículos.',
                'acciones' => [
                    'ver' => ['Ver Activos y Depreciación', 'Consultar cédulas de activos y depreciación acumulada.'],
                    'crear_editar' => ['Registrar / Depreciar Activos', 'Alta de activos fijos y corrida mensual de depreciación.']
                ]
            ],
            'proyectos' => [
                'nombre' => 'Proyectos y Obras',
                'icono' => 'fa-helmet-safety',
                'desc' => 'Control presupuestario de obras y agregación transversal de costos directos.',
                'acciones' => [
                    'ver' => ['Ver Proyectos y Presupuestos', 'Consultar rentabilidad y costos asignados por obra.'],
                    'crear_editar' => ['Gestionar Proyectos y Costos', 'Crear proyectos, fijar presupuestos y vincular consumos.']
                ]
            ]
        ]
    ]
];
?>
<div class="permisos-container" style="display: flex; flex-direction: column; gap: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; flex-wrap: wrap; gap: 10px;">
        <span style="font-size: 13px; color: #64748b; font-weight: 500;">
            <i class="fa-solid fa-list-check"></i> Selecciona los módulos y privilegios aplicables:
        </span>
        <div style="display: flex; gap: 10px;">
            <button type="button" id="btn-marcar-todos-permisos" style="font-size: 12px; padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; background: white; cursor: pointer; color: #1e293b; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                <i class="fa-solid fa-check-double" style="color: #103487;"></i> Marcar Todo
            </button>
            <button type="button" id="btn-desmarcar-todos-permisos" style="font-size: 12px; padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; background: white; cursor: pointer; color: #64748b; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                <i class="fa-solid fa-xmark"></i> Desmarcar Todo
            </button>
        </div>
    </div>

    <?php foreach ($gruposModulos as $nombreGrupo => $grupo): ?>
        <div style="border: 1px solid #e2e8f0; border-radius: 8px; background: white; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <div style="background: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-weight: 700; font-size: 13.5px; color: <?= $grupo['color'] ?>; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid <?= $grupo['icono'] ?>"></i> <?= htmlspecialchars($nombreGrupo, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span style="font-size: 11px; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 12px; font-weight: 600;">
                    <?= count($grupo['modulos']) ?> módulos
                </span>
            </div>

            <div style="padding: 15px; display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($grupo['modulos'] as $claveMod => $infoMod): ?>
                    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                        <strong style="font-size: 13px; color: #1e293b; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fa-solid <?= $infoMod['icono'] ?>" style="color: <?= $grupo['color'] ?>; font-size: 12px;"></i>
                            <?= htmlspecialchars($infoMod['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </strong>
                        <p style="font-size: 11px; color: #64748b; margin: 0 0 8px 18px; line-height: 1.3;">
                            <?= htmlspecialchars($infoMod['desc'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; padding-left: 18px;">
                            <?php foreach ($infoMod['acciones'] as $claveAcc => $infoAcc): ?>
                                <?php $activo = $pActivo($claveMod, $claveAcc); ?>
                                <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; user-select: none; max-width: 320px;">
                                    <input type="checkbox" 
                                           class="permiso-checkbox" 
                                           name="permisos[<?= $claveMod ?>][<?= $claveAcc ?>]" 
                                           value="1" 
                                           <?= $activo ? 'checked' : '' ?> 
                                           style="accent-color: <?= $grupo['color'] ?>; margin-top: 3px;">
                                    <div>
                                        <span style="font-size: 12.5px; font-weight: 600; color: #334155; display: block;">
                                            <?= htmlspecialchars($infoAcc[0], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span style="font-size: 11px; color: #64748b; display: block; line-height: 1.25;">
                                            <?= htmlspecialchars($infoAcc[1], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
(function() {
    function initPermisosButtons() {
        const btnMarcar = document.getElementById('btn-marcar-todos-permisos');
        const btnDesmarcar = document.getElementById('btn-desmarcar-todos-permisos');
        if (btnMarcar && !btnMarcar.dataset.bound) {
            btnMarcar.dataset.bound = 'true';
            btnMarcar.addEventListener('click', function() {
                document.querySelectorAll('.permiso-checkbox').forEach(cb => { cb.checked = true; });
            });
        }
        if (btnDesmarcar && !btnDesmarcar.dataset.bound) {
            btnDesmarcar.dataset.bound = 'true';
            btnDesmarcar.addEventListener('click', function() {
                document.querySelectorAll('.permiso-checkbox').forEach(cb => { cb.checked = false; });
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPermisosButtons);
    } else {
        initPermisosButtons();
    }
})();
</script>
