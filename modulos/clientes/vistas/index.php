<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; }
    .tabla-cycsa td { padding: 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-activo { background-color: #d4edda; color: #155724; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-inactivo { background-color: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 5px 10px; font-size: 16px; transition: color 0.2s; text-decoration: none; display: inline-block;}
    .btn-editar { color: #103487; }
    .btn-editar:hover { color: #0a225c; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #333; font-size: 20px;">Módulo de Clientes</h2>
            <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Cartera de clientes y empresas registradas.</p>
        </div>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="GET" action="/Cycsa/publico/clientes" style="display: flex;">
                <input type="text" name="q" placeholder="Buscar por nombre, RUC o email..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 8px 15px; border: 1px solid #ced4da; border-radius: 4px 0 0 4px; font-family: 'Inter', sans-serif; width: 250px; outline: none;">
                <button type="submit" style="background: #e9ecef; border: 1px solid #ced4da; border-left: none; padding: 8px 15px; border-radius: 0 4px 4px 0; cursor: pointer; color: #495057;"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if(!empty($busqueda)): ?>
                    <a href="/Cycsa/publico/clientes" style="margin-left: 10px; color: #e31837; text-decoration: none; padding-top: 8px;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
                <?php endif; ?>
            </form>

            <a href="/Cycsa/publico/clientes/crear" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-block; transition: background 0.3s; margin-left: 10px;">
                <i class="fa-solid fa-plus"></i> Registrar Cliente
            </a>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre / Razón Social</th>
                    <th>Identificación</th>
                    <th>Correo Electrónico</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td style="color: #6c757d; font-weight: 500;">#<?= htmlspecialchars($cliente['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($cliente['nombre_razon_social'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($cliente['identificacion'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($cliente['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($cliente['telefono'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($cliente['activo'] == 1): ?>
                            <span class="badge-activo">Activo</span>
                        <?php else: ?>
                            <span class="badge-inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="/Cycsa/publico/clientes/editar?id=<?= $cliente['id'] ?>" class="btn-accion btn-editar" title="Editar">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($clientes)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">No se encontraron clientes registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>