<div style="max-width: 600px; margin: 0 auto 20px auto;">
    <a href="/Cycsa/publico/usuarios" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
</div>

<div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 600px; margin: 0 auto;">
    
    <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <h2 style="margin: 0; color: #333; font-size: 20px;">Editar Usuario</h2>
        <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Modifica la información o permisos del usuario seleccionado.</p>
    </div>

    <form action="/Cycsa/publico/usuarios/editar?id=<?= htmlspecialchars($usuario['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" method="POST">
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Nombre Completo</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Correo Electrónico</label>
            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Nueva Contraseña</label>
            <input type="password" name="password" style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px;">
            <small style="display: block; margin-top: 5px; color: #6c757d; font-size: 12px;">Dejar en blanco para mantener la contraseña actual (mínimo 6 caracteres si se cambia).</small>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Rol en el Sistema</label>
            <select name="id_rol" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; background-color: white;">
                <option value="">Selecciona un nivel de acceso...</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= htmlspecialchars($rol['id'], ENT_QUOTES, 'UTF-8') ?>" <?= (isset($usuario['id_rol']) && $usuario['id_rol'] == $rol['id']) || (isset($usuario['id_rol']) && $usuario['id_rol'] == $rol['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 30px;">
            <label style="display: block; margin-bottom: 8px; color: #495057; font-weight: 500; font-size: 14px;">Estado de la Cuenta</label>
            <select name="activo" required style="width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; background-color: white;">
                <option value="1" <?= isset($usuario['activo']) && $usuario['activo'] == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= isset($usuario['activo']) && $usuario['activo'] == 0 ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div style="display: flex; gap: 15px; justify-content: flex-end;">
            <a href="/Cycsa/publico/usuarios" style="padding: 10px 20px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
            <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; transition: background 0.3s;">Guardar Cambios</button>
        </div>
    </form>

</div>
