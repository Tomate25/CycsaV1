<div style="max-width: 800px; margin: 0 auto 20px auto;">
    <a href="/Cycsa/publico/roles" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista de roles</a>
</div>

<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 800px; margin: 0 auto;">
    
    <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <h2 style="margin: 0; color: #333; font-size: 20px;">Crear Nuevo Rol de Usuario</h2>
        <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Define el nombre, responsabilidades y los permisos asignados por defecto al nuevo rol.</p>
    </div>

    <form action="/Cycsa/publico/roles/crear" method="POST">
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a; font-size: 14px;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Nombre del Rol</label>
            <input type="text" name="nombre" required placeholder="Ej. Encargado de Bodega, Auditor de Calidad..." style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Descripción de Funciones</label>
            <textarea name="descripcion" placeholder="Explica de forma resumida qué responsabilidades o puestos abarca este rol en la empresa..." style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; height: 80px; resize: vertical;"></textarea>
        </div>

        <!-- 🛡️ SECCIÓN DE CONFIGURACIÓN DE PERMISOS PREDETERMINADOS -->
        <div style="margin-bottom: 30px; border: 1px solid #e2e8f0; padding: 20px; border-radius: 6px; background-color: #f8fafc;">
            <h4 style="margin: 0 0 12px 0; color: #103487; font-size: 14.5px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved"></i> Permisos Predeterminados del Rol
            </h4>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 18px; line-height: 1.4;">Marca los accesos y acciones que los usuarios asignados a este rol tendrán por defecto.</p>
            
            <?php 
            $permisos = [
                'clientes' => ['ver' => 1],
                'productos' => ['ver' => 1],
                'cotizaciones' => ['ver' => 1]
            ];
            include __DIR__ . '/_permisos_checklist.php'; 
            ?>
        </div>

        <div style="display: flex; gap: 15px; justify-content: flex-end;">
            <a href="/Cycsa/publico/roles" style="padding: 10px 20px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
            <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; transition: background 0.3s;">Guardar Rol</button>
        </div>
    </form>
</div>
