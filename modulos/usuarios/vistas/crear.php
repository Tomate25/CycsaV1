<div style="max-width: 600px; margin: 0 auto 20px auto;">
    <a href="/Cycsa/publico/usuarios" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
</div>

<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 600px; margin: 0 auto;">
    
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

        <div style="margin-bottom: 30px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Rol en el Sistema</label>
            <select name="id_rol" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; background-color: white;">
                <option value="">Selecciona un nivel de acceso...</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= htmlspecialchars($rol['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- 🛡️ SECCIÓN DINÁMICA DE PERMISOS -->
        <div id="seccion-permisos" style="margin-bottom: 25px; border: 1px solid var(--border-light); padding: 20px; border-radius: 6px; background-color: #f8fafc; display: none;">
            <h4 style="margin: 0 0 12px 0; color: var(--cycsa-azul); font-size: 14.5px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved"></i> Permisos del Vendedor
            </h4>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 18px; line-height: 1.4;">Marca los módulos y las acciones específicas a las que este vendedor tendrá acceso en la aplicación.</p>
            
            <!-- Modulo: Clientes -->
            <div style="margin-bottom: 15px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px;">
                <strong style="font-size: 13px; color: #334155; display: block; margin-bottom: 6px;">Módulo Clientes:</strong>
                <div style="display: flex; gap: 20px;">
                    <label style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[clientes][ver]" value="1" checked style="accent-color: var(--cycsa-azul);"> Ver Clientes (Acceso al módulo)
                    </label>
                    <label style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[clientes][crear_editar]" value="1" checked style="accent-color: var(--cycsa-azul);"> Registrar / Editar Clientes
                    </label>
                </div>
            </div>
            
            <!-- Modulo: Productos / Ensayos -->
            <div style="margin-bottom: 15px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px;">
                <strong style="font-size: 13px; color: #334155; display: block; margin-bottom: 6px;">Módulo Productos / Ensayos (Catálogo):</strong>
                <div style="display: flex; gap: 20px;">
                    <label style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[productos][ver]" value="1" checked style="accent-color: var(--cycsa-azul);"> Ver Productos (Catálogo)
                    </label>
                    <label style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[productos][crear_editar]" value="1" style="accent-color: var(--cycsa-azul);"> Crear / Editar / Eliminar Ensayos
                    </label>
                </div>
            </div>
            
            <!-- Modulo: Cotizaciones -->
            <div style="margin-bottom: 5px;">
                <strong style="font-size: 13px; color: #334155; display: block; margin-bottom: 6px;">Módulo Cotizaciones:</strong>
                <div style="display: flex; gap: 20px;">
                    <label style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[cotizaciones][ver]" value="1" checked style="accent-color: var(--cycsa-azul);"> Ver Cotizaciones
                    </label>
                    <label style="font-size: 13px; color: #475569; display: flex; align-items: center; gap: 6px; cursor: pointer; user-select: none;">
                        <input type="checkbox" name="permisos[cotizaciones][crear_editar]" value="1" checked style="accent-color: var(--cycsa-azul);"> Crear / Editar / Aprobar
                    </label>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectRol = document.querySelector('select[name="id_rol"]');
                const seccionPermisos = document.getElementById('seccion-permisos');
                
                function togglePermisos() {
                    if (selectRol.value == '2') { // 2 = Vendedor
                        seccionPermisos.style.display = 'block';
                    } else {
                        seccionPermisos.style.display = 'none';
                    }
                }
                
                if (selectRol) {
                    selectRol.addEventListener('change', togglePermisos);
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