<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-activo { background-color: #d4edda; color: #155724; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-inactivo { background-color: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 5px 10px; font-size: 16px; transition: color 0.2s; }
    .btn-editar { color: #103487; }
    .btn-editar:hover { color: #0a225c; }
    .btn-eliminar { color: #e31837; }
    .btn-eliminar:hover { color: #a71128; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #333; font-size: 20px;">Gestión de Usuarios</h2>
            <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Administra los accesos al sistema CYCSA.</p>
        </div>
        
        <a href="/Cycsa/publico/usuarios/crear" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-block; transition: background 0.3s;">
            <i class="fa-solid fa-plus"></i> Nuevo Usuario
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo Electrónico</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td style="color: #6c757d; font-weight: 500;">#<?= $usuario['id'] ?></td>
                    <td style="font-weight: 600;"><?= $usuario['nombre'] ?></td>
                    <td><?= $usuario['email'] ?></td>
                    <td><?= $usuario['rol'] ?></td>
                    <td>
                        <?php if ($usuario['activo'] == 1): ?>
                            <span class="badge-activo">Activo</span>
                        <?php else: ?>
                            <span class="badge-inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <button class="btn-accion btn-editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>
                        <button class="btn-accion btn-eliminar" title="Desactivar"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($usuarios)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #6c757d;">No hay usuarios registrados en el sistema.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>