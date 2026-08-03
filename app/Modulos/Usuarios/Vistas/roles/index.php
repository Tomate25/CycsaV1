<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-modulo { background-color: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 500; display: inline-block; margin: 2px; border: 1px solid #bae6fd; }
    .badge-admin { background-color: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600; border: 1px solid #fde68a; }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 5px 10px; font-size: 16px; transition: color 0.2s; text-decoration: none; display: inline-block; }
    .btn-editar { color: #103487; }
    .btn-editar:hover { color: #0a225c; }
    .btn-eliminar { color: #e31837; }
    .btn-eliminar:hover { color: #a71128; }
    .btn-deshabilitado { color: #cbd5e1; cursor: not-allowed; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <!-- 🗂️ PESTAÑAS DE NAVEGACIÓN DE GESTIÓN -->
    <div style="display: flex; gap: 20px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px;">
        <a href="/Cycsa/publico/usuarios" style="text-decoration: none; color: #6c757d; font-weight: 500; font-size: 14.5px; padding-bottom: 10px; margin-bottom: -11px; display: flex; align-items: center; gap: 6px; transition: color 0.2s;">
            <i class="fa-solid fa-users"></i> Usuarios
        </a>
        <a href="/Cycsa/publico/roles" style="text-decoration: none; color: #103487; font-weight: 600; font-size: 14.5px; border-bottom: 2px solid #103487; padding-bottom: 10px; margin-bottom: -11px; display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-shield-halved"></i> Roles y Permisos
        </a>
    </div>

    <!-- Mensaje de Error en caso de eliminación bloqueada -->
    <?php if (isset($_SESSION['roles_error'])): ?>
        <div style="background: #ffebee; color: #c62828; padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a; font-size: 14px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['roles_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['roles_error']); ?>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #333; font-size: 20px;">Configuración de Roles</h2>
            <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Define los roles del personal y sus permisos predeterminados de acceso.</p>
        </div>
        
        <a href="/Cycsa/publico/roles/crear" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-block; transition: background 0.3s;">
            <i class="fa-solid fa-plus"></i> Nuevo Rol
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 200px;">Rol</th>
                    <th>Descripción de Funciones</th>
                    <th>Permisos de Módulos</th>
                    <th style="text-align: right; width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $rol): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="color: #64748b; font-size: 13.5px;"><?= htmlspecialchars($rol['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($rol['id'] == 1): ?>
                            <span class="badge-admin"><i class="fa-solid fa-star"></i> Acceso Total</span>
                        <?php else: 
                            $permisos = json_decode($rol['permisos'] ?? '', true) ?: [];
                            $modulosPermitidos = [];
                            foreach ($permisos as $mod => $actions) {
                                if (isset($actions['ver']) && $actions['ver'] == 1) {
                                    $modName = ucfirst($mod);
                                    if ($mod === 'productos') $modName = 'Catálogo';
                                    if ($mod === 'laboratorio') $modName = 'Ensayos';
                                    $modulosPermitidos[] = $modName;
                                }
                            }
                            if (!empty($modulosPermitidos)):
                                foreach ($modulosPermitidos as $m): ?>
                                    <span class="badge-modulo"><?= $m ?></span>
                                <?php endforeach;
                            else: ?>
                                <span style="color: #94a3b8; font-style: italic; font-size: 12.5px;">Ninguno</span>
                            <?php endif; 
                        endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="/Cycsa/publico/roles/editar?id=<?= codificarId($rol['id']) ?>" class="btn-accion btn-editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></a>
                        <?php if ($rol['id'] != 1 && $rol['id'] != 2): ?>
                            <a href="/Cycsa/publico/roles/eliminar?id=<?= codificarId($rol['id']) ?>" class="btn-accion btn-eliminar" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este rol? Solo se puede eliminar si ningún usuario lo tiene asignado.');"><i class="fa-solid fa-trash"></i></a>
                        <?php else: ?>
                            <span class="btn-accion btn-deshabilitado" title="Rol crítico del sistema (protegido)"><i class="fa-solid fa-trash"></i></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
