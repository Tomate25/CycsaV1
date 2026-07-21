<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-activo { background-color: #d4edda; color: #155724; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-inactivo { background-color: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 5px 10px; font-size: 16px; transition: color 0.2s; text-decoration: none; display: inline-block; }
    .btn-editar { color: #103487; }
    .btn-editar:hover { color: #0a225c; }
    .btn-eliminar { color: #e31837; }
    .btn-eliminar:hover { color: #a71128; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <!-- 🗂️ PESTAÑAS DE NAVEGACIÓN DE GESTIÓN -->
    <div style="display: flex; gap: 20px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px;">
        <a href="/Cycsa/publico/usuarios" style="text-decoration: none; color: #103487; font-weight: 600; font-size: 14.5px; border-bottom: 2px solid #103487; padding-bottom: 10px; margin-bottom: -11px; display: flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-users"></i> Usuarios
        </a>
        <a href="/Cycsa/publico/roles" style="text-decoration: none; color: #6c757d; font-weight: 500; font-size: 14.5px; padding-bottom: 10px; margin-bottom: -11px; display: flex; align-items: center; gap: 6px; transition: color 0.2s;">
            <i class="fa-solid fa-shield-halved"></i> Roles y Permisos
        </a>
    </div>

    <?php if (!empty($_SESSION['temp_password_info'])): 
        $info = $_SESSION['temp_password_info'];
        unset($_SESSION['temp_password_info']);
    ?>
    <!-- Modal Premium Responsivo de Clave Temporal (Solo Administradores/Supervisores) -->
    <div id="modal-clave-temporal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 15px; box-sizing: border-box; animation: fadeInModal 0.3s ease;">
        <div style="background: white; border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); overflow: hidden; border: 1px solid #e2e8f0; animation: slideUpModal 0.35s cubic-bezier(0.16, 1, 0.3, 1);">
            <!-- Modal Header -->
            <div style="background: linear-gradient(135deg, #103487 0%, #1e40af 100%); padding: 20px 24px; color: white; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="background: rgba(255,255,255,0.2); width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 17px; font-weight: 700; font-family: 'Outfit', sans-serif;">Usuario Desbloqueado</h3>
                        <span style="font-size: 11.5px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Acción Exclusiva de Administrador</span>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-clave-temporal').remove();" style="background: rgba(255,255,255,0.15); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: background 0.2s;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div style="padding: 24px;">
                <p style="margin: 0 0 16px 0; color: #334155; font-size: 14px; line-height: 1.5;">
                    Se ha restablecido el acceso para <strong><?= htmlspecialchars($info['nombre'], ENT_QUOTES, 'UTF-8') ?></strong> (<code><?= htmlspecialchars($info['email'], ENT_QUOTES, 'UTF-8') ?></code>). Entrégale la siguiente contraseña temporal:
                </p>

                <!-- Caja de la Contraseña Temporal -->
                <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 18px; text-align: center; margin-bottom: 20px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 6px;">Contraseña Temporal Generada</span>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap;">
                        <span style="font-family: 'Courier New', monospace; font-size: 26px; font-weight: 800; color: #103487; letter-spacing: 2px;" id="lblModalTempPass"><?= htmlspecialchars($info['temp_pass'], ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($info['temp_pass'], ENT_QUOTES, 'UTF-8') ?>'); this.innerText='¡Copiado!'; this.style.background='#16a34a'; setTimeout(() => { this.innerText='Copiar'; this.style.background='#103487'; }, 2000);" style="background: #103487; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; font-family: 'Outfit', sans-serif; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>

                <div style="background: #f0fdf4; border-left: 4px solid #16a34a; padding: 12px 16px; border-radius: 6px; color: #166534; font-size: 12.5px; line-height: 1.4; display: flex; align-items: flex-start; gap: 10px;">
                    <i class="fa-solid fa-circle-info" style="margin-top: 2px; font-size: 14px;"></i>
                    <span>Cuando el usuario inicie sesión con esta clave temporal, el sistema le solicitará obligatoriamente ingresar una nueva contraseña personal antes de ingresar.</span>
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="background: #f8fafc; padding: 14px 24px; border-top: 1px solid #e2e8f0; text-align: right;">
                <button type="button" onclick="document.getElementById('modal-clave-temporal').remove();" style="background: #e2e8f0; color: #334155; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13.5px; transition: background 0.2s;">
                    Entendido / Cerrar
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUpModal {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
    <?php endif; ?>

    <?php if (!empty($exito)): ?>
        <div style="background-color: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i>
            <span><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 18px;"></i>
            <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

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
                    <td style="font-weight: 600;"><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ((int)($usuario['bloqueado'] ?? 0) === 1): ?>
                            <span class="badge-bloqueado" style="background-color: #fee2e2; color: #dc2626; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-lock"></i> Bloqueado</span>
                        <?php elseif ($usuario['activo'] == 1): ?>
                            <span class="badge-activo">Activo</span>
                        <?php else: ?>
                            <span class="badge-inactivo">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <?php if ((int)($usuario['bloqueado'] ?? 0) === 1): ?>
                            <a href="/Cycsa/publico/usuarios/desbloquear?id=<?= $usuario['id'] ?>" class="btn-accion" title="Desbloquear" style="color: #ea580c; margin-right: 5px;" onclick="return confirm('¿Estás seguro de que deseas desbloquear a este usuario y restablecer sus intentos de acceso?');"><i class="fa-solid fa-lock-open"></i></a>
                        <?php endif; ?>
                        <a href="/Cycsa/publico/usuarios/editar?id=<?= $usuario['id'] ?>" class="btn-accion btn-editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></a>
                        <a href="/Cycsa/publico/usuarios/eliminar?id=<?= $usuario['id'] ?>" class="btn-accion btn-eliminar" title="Desactivar" onclick="return confirm('¿Estás seguro de que deseas desactivar a este usuario? Ya no podrá iniciar sesión.');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($usuarios)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #6c757d;">No hay usuarios registrados en el sistema.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$bitacora_modulo_nombre = 'Usuarios';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
