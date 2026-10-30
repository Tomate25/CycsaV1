<div style="max-width: 800px; margin: 0 auto 20px auto;">
    <a href="/Cycsa/publico/usuarios" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
</div>

<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 800px; margin: 0 auto;">
    
    <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <h2 style="margin: 0; color: #333; font-size: 20px;">Registrar Nuevo Usuario</h2>
        <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Ingresa los datos para dar acceso a un nuevo miembro del equipo.</p>
    </div>

    <form action="/Cycsa/publico/usuarios/crear" method="POST">
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Nombre Completo</label>
            <input type="text" name="nombre" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Correo Electrónico</label>
            <input type="email" name="email" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Contraseña de Acceso</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Rol en el Sistema</label>
            <select name="id_rol" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; background-color: white;">
                <option value="">Selecciona un nivel de acceso...</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= htmlspecialchars($rol['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- 🛡️ SECCIÓN DINÁMICA DE PERMISOS -->
        <div id="seccion-permisos" style="margin-bottom: 25px; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; background-color: #f8fafc; display: none;">
            <h4 style="margin: 0 0 12px 0; color: #103487; font-size: 14.5px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved"></i> Configuración de Permisos
            </h4>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 18px; line-height: 1.4;">Marca los módulos y las acciones específicas a las que este usuario tendrá acceso en la aplicación. Los permisos se pre-cargan automáticamente según el rol seleccionado.</p>
            
            <?php 
            $permisos = [];
            include __DIR__ . '/roles/_permisos_checklist.php'; 
            ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectRol = document.querySelector('select[name="id_rol"]');
                const seccionPermisos = document.getElementById('seccion-permisos');
                
                // Mapear los permisos por defecto de cada rol
                const rolesPermisos = <?= json_encode(array_column($roles, 'permisos', 'id')) ?>;
                
                function togglePermisos() {
                    if (selectRol.value !== '' && selectRol.value != '1') {
                        seccionPermisos.style.display = 'block';
                    } else {
                        seccionPermisos.style.display = 'none';
                    }
                }
                
                function cargarPermisosRol() {
                    const idRol = selectRol.value;
                    if (!idRol || idRol == '1') return;
                    
                    let permisos = {};
                    const rawPermisos = rolesPermisos[idRol];
                    if (rawPermisos) {
                        try {
                            permisos = typeof rawPermisos === 'string' ? JSON.parse(rawPermisos) : rawPermisos;
                        } catch (e) {
                            console.error("Error al parsear permisos del rol:", e);
                            return;
                        }
                    }
                    
                    const checkboxes = seccionPermisos.querySelectorAll('.permiso-checkbox');
                    checkboxes.forEach(cb => {
                        const name = cb.name; // Ej: "permisos[clientes][ver]"
                        const match = name.match(/permisos\[([^\]]+)\]\[([^\]]+)\]/);
                        if (match) {
                            const modulo = match[1];
                            const accion = match[2];
                            if (permisos && permisos[modulo] && permisos[modulo][accion] !== undefined) {
                                cb.checked = (permisos[modulo][accion] == 1 || permisos[modulo][accion] === true);
                            } else {
                                cb.checked = false;
                            }
                        }
                    });
                }
                
                if (selectRol) {
                    selectRol.addEventListener('change', function() {
                        togglePermisos();
                        cargarPermisosRol();
                    });
                    togglePermisos();
                }
            });
        </script>

        <div style="display: flex; gap: 15px; justify-content: flex-end;">
            <a href="/Cycsa/publico/usuarios" style="padding: 10px 20px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
            <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; transition: background 0.3s;">Guardar Usuario</button>
        </div>
    </form>
</div>
