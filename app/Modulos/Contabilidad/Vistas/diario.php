<?php
// Diario Contable View
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    
    .badge-origen { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .origen-manual { background-color: #f3f4f6; color: #1f2937; }
    .origen-cxc { background-color: #e0f2fe; color: #0369a1; }
    .origen-cxc_pago { background-color: #dcfce7; color: #14532d; }
    .origen-cxp { background-color: #fef3c7; color: #713f12; }
    .origen-cxp_pago { background-color: #fee2e2; color: #7f1d1d; }
    .origen-banco_tx { background-color: #e0e7ff; color: #312e81; }
    .origen-banco_apertura { background-color: #fae8ff; color: #701a75; }

    /* Expandable rows */
    .row-detalles { display: none; }
    .row-detalles.active { display: table-row; }
    .btn-toggle-det { background: none; border: none; color: var(--cycsa-azul); cursor: pointer; font-size: 14px; padding: 4px; }
    
    /* Modal styles */
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 30px; border: 1px solid #e2e8f0; width: 60%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    
    .form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    
    .tabla-partida-form { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .tabla-partida-form th { background: #f8fafc; color: #475569; padding: 10px; font-weight: 600; text-align: left; font-size: 13px; border-bottom: 2px solid #e2e8f0; }
    .tabla-partida-form td { padding: 8px 5px; border-bottom: 1px solid #f1f5f9; }
    
    .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-exito { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <!-- Alertas -->
    <?php if (!empty($exito)): ?>
        <div class="alert alert-exito">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="header-flex" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Registro Diario Contable</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Libro diario donde se registran cronológicamente todas las operaciones financieras.</p>
        </div>
        
        <div class="actions-flex" style="display: flex; gap: 10px; align-items: center;">
            <!-- Buscador -->
            <form method="GET" action="/Cycsa/publico/contabilidad/diario" style="display: flex; margin: 0;">
                <input type="text" name="q" placeholder="Buscar concepto o partida..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; font-family: 'Inter', sans-serif; width: 220px; outline: none; font-size: 14px;">
                <button type="submit" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-left: none; padding: 10px 18px; border-radius: 0 6px 6px 0; cursor: pointer; color: #475569; font-size: 14px;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>

            <form action="/Cycsa/publico/contabilidad/sincronizar-diario" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que deseas sincronizar el libro diario? Esto regenerará todos los asientos a partir de las facturas de cobro, de pago y transacciones de bancos históricas.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" style="background: #059669; color: white; border: none; padding: 11px 18px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 6px; font-size: 14px;" title="Regenera el diario contable sincronizando con bancos, cxc y cxp">
                    <i class="fa-solid fa-arrows-rotate"></i> Sincronizar Diario
                </button>
            </form>

            <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
            <button id="btnAbrirModal" style="background: var(--cycsa-azul); color: white; border: none; padding: 11px 18px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px; font-size: 14px;">
                <i class="fa-solid fa-plus"></i> Asiento Manual
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Menú de pestañas secundarias -->
    <div class="tabs-container" style="display: flex; gap: 8px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="/Cycsa/publico/contabilidad/cuentas" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-list-ol"></i> Catálogo</a>
        <a href="/Cycsa/publico/contabilidad/diario" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13.5px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-book"></i> Registro Diario</a>
        <a href="/Cycsa/publico/contabilidad/cxc" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-file-invoice-dollar"></i> Cobros (CXC)</a>
        <a href="/Cycsa/publico/contabilidad/cxp" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-credit-card"></i> Pagos (CXP)</a>
        <a href="/Cycsa/publico/contabilidad/bancos" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-building-columns"></i> Bancos</a>
        <a href="/Cycsa/publico/contabilidad/balance" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-scale-balanced"></i> Balance General</a>
        <a href="/Cycsa/publico/contabilidad/resultados" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-chart-line"></i> Estado de Resultados</a>
    </div>

    <!-- Lista de Asientos -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 50px;"></th>
                    <th style="width: 100px;">Partida</th>
                    <th style="width: 110px;">Fecha</th>
                    <th>Concepto / Descripción</th>
                    <th style="width: 130px;">Origen</th>
                    <th style="width: 120px; text-align: right;">Total Debe</th>
                    <th style="width: 120px; text-align: right;">Total Haber</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($asientos)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">
                            <i class="fa-solid fa-folder-open" style="font-size: 28px;"></i><br><br>
                            No hay asientos registrados en el libro diario. Haz clic en "Sincronizar Diario" para importar movimientos.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($asientos as $as): ?>
                        <tr onclick="toggleDetalles(<?= $as['id'] ?>, this)" style="cursor: pointer; transition: background-color 0.2s;">
                            <td style="text-align: center;">
                                <button type="button" class="btn-toggle-det" style="pointer-events: none;">
                                    <i class="fa-solid fa-circle-chevron-down"></i>
                                </button>
                            </td>
                            <td style="font-family: monospace; font-weight: 700; color: var(--cycsa-azul);"><?= htmlspecialchars($as['num_partida'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= date('d/m/Y', strtotime($as['fecha'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($as['concepto'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                            <td>
                                <?php
                                $origenMap = [
                                    'MANUAL' => 'Asiento Manual',
                                    'CXC' => 'Factura Cliente',
                                    'CXC_PAGO' => 'Cobro / Abono',
                                    'CXP' => 'Factura Proveedor',
                                    'CXP_PAGO' => 'Pago Realizado',
                                    'BANCO_TX' => 'Transacción Banco',
                                    'BANCO_APERTURA' => 'Saldo Inicial'
                                ];
                                $lblOrigen = $origenMap[strtoupper($as['origen'])] ?? $as['origen'];
                                ?>
                                <span class="badge-origen origen-<?= strtolower($as['origen']) ?>">
                                    <?= htmlspecialchars($lblOrigen, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 600;">C$ <?= number_format($as['total_debe'], 2, '.', ',') ?></td>
                            <td style="text-align: right; font-weight: 600;">C$ <?= number_format($as['total_haber'], 2, '.', ',') ?></td>
                        </tr>
                        <!-- Fila de Detalles Expandible -->
                        <tr class="row-detalles" id="det-<?= $as['id'] ?>">
                            <td></td>
                            <td colspan="6" style="padding: 15px; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">
                                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);">
                                    <?php if (!empty($as['referencia_origen']) || !empty($as['banco_afectado'])): ?>
                                        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 16px; margin-bottom: 15px; font-size: 13px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-family: 'Inter', sans-serif;">
                                            <?php if (!empty($as['referencia_origen'])): $ref = $as['referencia_origen']; ?>
                                                <div>
                                                    <span style="color: #0369a1; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Tipo de Operación:</span>
                                                    <strong style="color: #0c4a6e;"><?= htmlspecialchars($ref['tipo'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                </div>
                                                <?php if (!empty($ref['tercero'])): ?>
                                                    <div>
                                                        <span style="color: #0369a1; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Tercero (Cliente/Prov/Beneficiario):</span>
                                                        <strong style="color: #0c4a6e;"><?= htmlspecialchars($ref['tercero'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($ref['documento'])): ?>
                                                    <div>
                                                        <span style="color: #0369a1; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Documento / Referencia:</span>
                                                        <strong style="color: #0c4a6e;"><?= htmlspecialchars($ref['documento'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($as['banco_afectado'])): $bco = $as['banco_afectado']; ?>
                                                <div>
                                                    <span style="color: #0369a1; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Banco / Caja de Destino:</span>
                                                    <strong style="color: #0369a1; background: #e0f2fe; padding: 3px 8px; border-radius: 4px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #bae6fd;"><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($bco['banco_nombre'] . ' - ' . $bco['numero_cuenta'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($as['referencia_origen']['detalle'])): ?>
                                                <div style="grid-column: 1 / -1; border-top: 1px dashed #bae6fd; padding-top: 8px; margin-top: 4px;">
                                                    <span style="color: #0369a1; font-size: 11px; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Detalle Operativo:</span>
                                                    <span style="color: #334155; font-weight: 500;"><?= htmlspecialchars($as['referencia_origen']['detalle'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <h5 style="margin: 0 0 10px 0; color: #475569; font-size: 12.5px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Cuentas Contables y Movimientos</h5>
                                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                        <thead>
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <th style="padding: 6px 10px; color: #64748b; font-weight: 600; text-align: left;">Código Cuenta</th>
                                                <th style="padding: 6px 10px; color: #64748b; font-weight: 600; text-align: left;">Nombre de Cuenta</th>
                                                <th style="padding: 6px 10px; color: #64748b; font-weight: 600; text-align: left;">Categoría</th>
                                                <th style="padding: 6px 10px; color: #64748b; font-weight: 600; text-align: right; width: 140px;">Debe</th>
                                                <th style="padding: 6px 10px; color: #64748b; font-weight: 600; text-align: right; width: 140px;">Haber</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($as['detalles'] as $line): ?>
                                                <tr style="border-bottom: 1px dashed #f1f5f9;">
                                                    <td style="padding: 8px 10px; font-family: monospace; font-weight: 600;"><?= htmlspecialchars($line['cuenta_codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td style="padding: 8px 10px;"><?= htmlspecialchars($line['cuenta_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td style="padding: 8px 10px; color: #64748b; font-size: 11px; font-weight: 600;"><?= htmlspecialchars($line['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td style="padding: 8px 10px; text-align: right; font-weight: 600; color: <?= $line['debe'] > 0 ? '#1e293b' : '#94a3b8' ?>;"><?= $line['debe'] > 0 ? 'C$ ' . number_format($line['debe'], 2, '.', ',') : '—' ?></td>
                                                    <td style="padding: 8px 10px; text-align: right; font-weight: 600; color: <?= $line['haber'] > 0 ? '#1e293b' : '#94a3b8' ?>;"><?= $line['haber'] > 0 ? 'C$ ' . number_format($line['haber'], 2, '.', ',') : '—' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL REGISTRAR ASIENTO MANUAL -->
<div id="modalAsiento" class="modal-premium">
    <div class="modal-premium-content" style="max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Registrar Asiento Contable Manual</h3>
            <button id="btnCerrarModal" class="btn-cerrar" style="background:none; border:none; font-size: 24px; cursor:pointer;">&times;</button>
        </div>

        <form action="/Cycsa/publico/contabilidad/guardar-partida" method="POST" id="form-asiento">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 600; color: #475569;">Fecha:</label>
                    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 600; color: #475569;">Concepto / Descripción General:</label>
                    <input type="text" name="concepto" placeholder="Ej: Registro de aportación de socios..." required class="form-control">
                </div>
            </div>

            <h4 style="margin: 20px 0 10px 0; font-size: 14px; font-family: 'Outfit'; color: #0f172a;">Líneas del Asiento Diario</h4>
            
            <table class="tabla-partida-form" id="tabla-lineas">
                <thead>
                    <tr>
                        <th>Cuenta Contable (Detalle)</th>
                        <th style="width: 150px;">Debe</th>
                        <th style="width: 150px;">Haber</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Primera línea -->
                    <tr>
                        <td>
                            <select name="cuentas_linea[]" required class="form-control select-cuenta" style="width: 100%;">
                                <option value="">Seleccione una cuenta...</option>
                                <?php foreach ($cuentasDetalle as $cta): ?>
                                    <option value="<?= $cta['id'] ?>"><?= htmlspecialchars($cta['codigo'] . ' - ' . $cta['nombre'], ENT_QUOTES, 'UTF-8') ?> (<?= $cta['categoria'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="debe_linea[]" placeholder="0.00" class="form-control input-debe" oninput="calcularCuadre()" style="text-align: right; width: 100%;">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="haber_linea[]" placeholder="0.00" class="form-control input-haber" oninput="calcularCuadre()" style="text-align: right; width: 100%;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="eliminarFila(this)" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <!-- Segunda línea -->
                    <tr>
                        <td>
                            <select name="cuentas_linea[]" required class="form-control select-cuenta" style="width: 100%;">
                                <option value="">Seleccione una cuenta...</option>
                                <?php foreach ($cuentasDetalle as $cta): ?>
                                    <option value="<?= $cta['id'] ?>"><?= htmlspecialchars($cta['codigo'] . ' - ' . $cta['nombre'], ENT_QUOTES, 'UTF-8') ?> (<?= $cta['categoria'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="debe_linea[]" placeholder="0.00" class="form-control input-debe" oninput="calcularCuadre()" style="text-align: right; width: 100%;">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="haber_linea[]" placeholder="0.00" class="form-control input-haber" oninput="calcularCuadre()" style="text-align: right; width: 100%;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="eliminarFila(this)" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="button" onclick="agregarFila()" style="background: none; border: 1px dashed var(--cycsa-azul); color: var(--cycsa-azul); padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter'; display: inline-flex; align-items: center; gap: 6px; margin-top: 15px; font-size: 13px;">
                <i class="fa-solid fa-plus"></i> Agregar Cuenta / Línea
            </button>

            <!-- Resumen de Cuadre -->
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-top: 25px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 30px;">
                    <div>
                        <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Debe</span>
                        <div id="lbl-total-debe" style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px;">C$ 0.00</div>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Haber</span>
                        <div id="lbl-total-haber" style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px;">C$ 0.00</div>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Diferencia</span>
                        <div id="lbl-diferencia" style="font-size: 16px; font-weight: 700; color: #10b981; margin-top: 4px;">C$ 0.00</div>
                    </div>
                </div>
                
                <div id="banner-descuadre" style="background-color: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: none;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Descuadrado
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                <button type="button" id="btnCerrarModalCancel" class="form-control" style="cursor: pointer; background: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; width: 120px;">Cancelar</button>
                <button type="submit" id="btnGuardarAsiento" disabled style="background: #64748b; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: not-allowed; font-weight: 600; font-family: 'Inter', sans-serif;">Guardar Asiento</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Toggle expandable lines
    function toggleDetalles(id, btn) {
        const row = document.getElementById('det-' + id);
        row.classList.toggle('active');
        const icon = btn.querySelector('i');
        if (row.classList.contains('active')) {
            icon.className = 'fa-solid fa-circle-chevron-up';
        } else {
            icon.className = 'fa-solid fa-circle-chevron-down';
        }
    }

    // Modal Control
    const modal = document.getElementById('modalAsiento');
    const btnAbrir = document.getElementById('btnAbrirModal');
    const btnCerrar = document.getElementById('btnCerrarModal');
    const btnCerrarCancel = document.getElementById('btnCerrarModalCancel');

    if (btnAbrir) {
        btnAbrir.addEventListener('click', () => {
            modal.style.display = 'block';
            calcularCuadre();
        });
    }

    [btnCerrar, btnCerrarCancel].forEach(b => {
        if (b) {
            b.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Row Operations
    function agregarFila() {
        const tbody = document.querySelector('#tabla-lineas tbody');
        const templateRow = tbody.querySelector('tr');
        if (!templateRow) return;
        
        const newRow = templateRow.cloneNode(true);
        newRow.querySelector('select').value = '';
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        tbody.appendChild(newRow);
        calcularCuadre();
    }

    function eliminarFila(btn) {
        const tbody = document.querySelector('#tabla-lineas tbody');
        if (tbody.querySelectorAll('tr').length <= 2) {
            alert('Un asiento contable requiere al menos dos líneas de cuentas contables.');
            return;
        }
        btn.closest('tr').remove();
        calcularCuadre();
    }

    // Cuadre Check Logic
    function calcularCuadre() {
        const debes = document.querySelectorAll('.input-debe');
        const habers = document.querySelectorAll('.input-haber');
        
        let totalDebe = 0.0;
        let totalHaber = 0.0;
        
        debes.forEach(d => {
            if (d.value) totalDebe += parseFloat(d.value);
        });
        
        habers.forEach(h => {
            if (h.value) totalHaber += parseFloat(h.value);
        });
        
        const diff = Math.abs(totalDebe - totalHaber);
        const isQuad = diff <= 0.01 && (totalDebe > 0 || totalHaber > 0);

        document.getElementById('lbl-total-debe').textContent = 'C$ ' + totalDebe.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('lbl-total-haber').textContent = 'C$ ' + totalHaber.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('lbl-diferencia').textContent = 'C$ ' + diff.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        
        const banner = document.getElementById('banner-descuadre');
        const btnSave = document.getElementById('btnGuardarAsiento');

        if (isQuad) {
            banner.style.display = 'none';
            btnSave.disabled = false;
            btnSave.style.background = 'var(--cycsa-azul)';
            btnSave.style.cursor = 'pointer';
            document.getElementById('lbl-diferencia').style.color = '#10b981';
        } else {
            banner.style.display = 'block';
            btnSave.disabled = true;
            btnSave.style.background = '#64748b';
            btnSave.style.cursor = 'not-allowed';
            document.getElementById('lbl-diferencia').style.color = '#ef4444';
        }
    }
</script>
