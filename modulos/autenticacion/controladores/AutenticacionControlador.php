<?php

namespace Cycsa\Modulos\Autenticacion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Autenticacion\Modelos\UsuarioModelo;

class AutenticacionControlador extends ControladorBase {
    
    public function mostrarLogin(Peticion $peticion, Respuesta $respuesta) {
        // Si ya está logueado, no le mostramos el login, lo mandamos al panel
        if (isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        
        $error = null;
        if (isset($_SESSION['login_error'])) {
            $error = $_SESSION['login_error'];
            unset($_SESSION['login_error']);
        }
        
        $this->renderizarSinLayout('autenticacion/vistas/login', [
            'titulo' => 'Iniciar Sesión - Cycsa',
            'error' => $error
        ]);
    }

    public function procesarLogin(Peticion $peticion, Respuesta $respuesta) {
        $datos = $peticion->obtenerDatos();
        $email = $datos['email'] ?? '';
        $password = $datos['password'] ?? '';

        $modeloUsuario = new UsuarioModelo();
        $usuario = $modeloUsuario->buscarPorEmail($email);

        if ($usuario && password_verify($password, $usuario['password'])) {
            if ($usuario['activo'] == 1) {
                // Prevenir Session Fixation regenerando el ID
                session_regenerate_id(true);
                
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_rol'] = $usuario['id_rol'];
                $permisos = null;
                if (!empty($usuario['permisos'])) {
                    $permisos = json_decode($usuario['permisos'], true);
                } else {
                    try {
                        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                        $stmtRol = $db->prepare("SELECT permisos FROM roles WHERE id = :id_rol LIMIT 1");
                        $stmtRol->execute(['id_rol' => $usuario['id_rol']]);
                        $rolPermisos = $stmtRol->fetchColumn();
                        if (!empty($rolPermisos)) {
                            $permisos = json_decode($rolPermisos, true);
                        }
                    } catch (\Exception $e) {
                        error_log("Error al obtener permisos por defecto del rol: " . $e->getMessage());
                    }
                }
                $_SESSION['usuario_permisos'] = $permisos;
                
                // Guardar la nueva sesión en la base de datos para invalidar sesiones anteriores
                try {
                    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                    $stmt = $db->prepare("UPDATE usuarios SET session_id = :session_id WHERE id = :id");
                    $stmt->execute([
                        'session_id' => session_id(),
                        'id' => $usuario['id']
                    ]);
                } catch (\Exception $e) {
                    error_log("Error al guardar session_id en login: " . $e->getMessage());
                }

                registrarBitacora('autenticacion', 'login', 'Inicio de sesión exitoso de ' . $usuario['nombre']);
                
                // 🚀 REDIRIGIR AL PANEL DE CONTROL
                $respuesta->redirigir('/Cycsa/publico/panel');
            } else {
                registrarBitacora('autenticacion', 'intento_login', 'Intento de inicio de sesión inactivo: ' . $email);
                $this->renderizarSinLayout('autenticacion/vistas/login', [
                    'titulo' => 'Iniciar Sesión - Cycsa',
                    'error' => 'Tu cuenta está inactiva.'
                ]);
            }
        } else {
            registrarBitacora('autenticacion', 'intento_login', 'Intento fallido de inicio de sesión con correo: ' . $email);
            $this->renderizarSinLayout('autenticacion/vistas/login', [
                'titulo' => 'Iniciar Sesión - Cycsa',
                'error' => 'Credenciales incorrectas.'
            ]);
        }
    }

    // 🔒 NUEVA FUNCIÓN: Destruir la sesión
    public function cerrarSesion(Peticion $peticion, Respuesta $respuesta) {
        if (isset($_SESSION['usuario_nombre'])) {
            // Limpiar session_id en la base de datos al cerrar sesión
            try {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $stmt = $db->prepare("UPDATE usuarios SET session_id = NULL WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['usuario_id']]);
            } catch (\Exception $e) {
                // Silencioso
            }
            registrarBitacora('autenticacion', 'logout', 'Cierre de sesión de ' . $_SESSION['usuario_nombre']);
        }
        session_destroy();
        $_SESSION = [];
        $respuesta->redirigir('/Cycsa/publico/login');
    }

    public function verificarSesionActiva(Peticion $peticion, Respuesta $respuesta): void {
        $respuesta->enviarJson(['status' => 'ok']);
    }

    public function mostrarRecuperarPassword(Peticion $peticion, Respuesta $respuesta) {
        $this->renderizarSinLayout('autenticacion/vistas/recuperar', [
            'titulo' => 'Recuperar Contraseña - Cycsa'
        ]);
    }

    public function procesarRecuperarPassword(Peticion $peticion, Respuesta $respuesta) {
        $datos = $peticion->obtenerDatos();
        $email = trim($datos['email'] ?? '');

        if (empty($email)) {
            $this->renderizarSinLayout('autenticacion/vistas/recuperar', [
                'titulo' => 'Recuperar Contraseña - Cycsa',
                'error' => 'Por favor, ingresa tu correo electrónico.'
            ]);
            return;
        }

        try {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->prepare("SELECT id, nombre FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($usuario) {
                // Generar código de 6 dígitos
                $codigo = sprintf("%06d", random_int(100000, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                // Guardar código en la BD
                $stmtUpdate = $db->prepare("UPDATE usuarios SET reset_token = :code, reset_token_expires_at = :expires WHERE id = :id");
                $stmtUpdate->execute([
                    'code' => $codigo,
                    'expires' => $expires,
                    'id' => $usuario['id']
                ]);

                // Asunto y cuerpo del correo
                $asunto = "Código de Recuperación - CYCSA";
                $cuerpoHTML = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff; color: #1e293b;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #103487; margin: 0;'>CYCSA ERP</h2>
                        <span style='font-size: 11px; color: #64748b; font-weight: bold; text-transform: uppercase;'>Recuperación de Contraseña</span>
                    </div>
                    <p>Hola, <strong>" . htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') . "</strong>.</p>
                    <p>Has solicitado restablecer tu contraseña de acceso al sistema CYCSA. Tu código de seguridad temporal es:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='font-size: 32px; font-weight: bold; color: #103487; letter-spacing: 5px; background: #f1f5f9; padding: 12px 24px; border-radius: 6px; border: 1px dashed #cbd5e1; display: inline-block;'>" . $codigo . "</span>
                    </div>
                    <p style='font-size: 13px; color: #475569;'>Este código es de uso único y tiene una validez de <strong>15 minutos</strong>. Si el código expira, deberás solicitar uno nuevo.</p>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;' />
                    <p style='color: #94a3b8; font-size: 11px; text-align: center;'>Si no solicitaste este restablecimiento, por favor ignora este correo electrónico.</p>
                </div>
                ";

                // Enviar el correo usando la función de ayudantes
                enviarCorreo($email, $asunto, $cuerpoHTML);
            }

            // Mensaje de éxito genérico para seguridad
            $_SESSION['success_message'] = "Si el correo existe en nuestro sistema, recibirás un código de 6 dígitos.";
            $respuesta->redirigir('/Cycsa/publico/restablecer-password?email=' . urlencode($email));

        } catch (\Exception $e) {
            error_log("Error al procesar recuperación de contraseña: " . $e->getMessage());
            $this->renderizarSinLayout('autenticacion/vistas/recuperar', [
                'titulo' => 'Recuperar Contraseña - Cycsa',
                'error' => 'Ocurrió un error interno. Intenta más tarde.'
            ]);
        }
    }

    public function mostrarRestablecerPassword(Peticion $peticion, Respuesta $respuesta) {
        $email = $_GET['email'] ?? '';
        $success = null;
        if (isset($_SESSION['success_message'])) {
            $success = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $this->renderizarSinLayout('autenticacion/vistas/restablecer', [
            'titulo' => 'Ingresar Código - Cycsa',
            'email' => $email,
            'success' => $success
        ]);
    }

    public function procesarRestablecerPassword(Peticion $peticion, Respuesta $respuesta) {
        $datos = $peticion->obtenerDatos();
        $email = trim($datos['email'] ?? '');
        $codigo = trim($datos['codigo'] ?? '');
        $password = $datos['password'] ?? '';
        $confirm_password = $datos['confirm_password'] ?? '';

        // Validaciones iniciales
        if (empty($email) || empty($codigo) || empty($password)) {
            $this->renderizarSinLayout('autenticacion/vistas/restablecer', [
                'titulo' => 'Ingresar Código - Cycsa',
                'email' => $email,
                'error' => 'Todos los campos son obligatorios.'
            ]);
            return;
        }

        if ($password !== $confirm_password) {
            $this->renderizarSinLayout('autenticacion/vistas/restablecer', [
                'titulo' => 'Ingresar Código - Cycsa',
                'email' => $email,
                'error' => 'Las contraseñas no coinciden.'
            ]);
            return;
        }

        if (strlen($password) < 6) {
            $this->renderizarSinLayout('autenticacion/vistas/restablecer', [
                'titulo' => 'Ingresar Código - Cycsa',
                'email' => $email,
                'error' => 'La contraseña debe tener al menos 6 caracteres.'
            ]);
            return;
        }

        try {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            
            // Buscar usuario por correo y código
            $stmt = $db->prepare("SELECT id, reset_token_expires_at FROM usuarios WHERE email = :email AND reset_token = :code LIMIT 1");
            $stmt->execute(['email' => $email, 'code' => $codigo]);
            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->renderizarSinLayout('autenticacion/vistas/restablecer', [
                    'titulo' => 'Ingresar Código - Cycsa',
                    'email' => $email,
                    'error' => 'El código de seguridad o correo es incorrecto.'
                ]);
                return;
            }

            // Validar expiración del código
            $now = date('Y-m-d H:i:s');
            if ($usuario['reset_token_expires_at'] < $now) {
                $this->renderizarSinLayout('autenticacion/vistas/restablecer', [
                    'titulo' => 'Ingresar Código - Cycsa',
                    'email' => $email,
                    'error' => 'El código de seguridad ha expirado. Por favor, solicita uno nuevo.'
                ]);
                return;
            }

            // Cifrar la nueva contraseña e invalidar el código usado
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmtUpdate = $db->prepare("UPDATE usuarios SET password = :password, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id");
            $stmtUpdate->execute([
                'password' => $hash,
                'id' => $usuario['id']
            ]);

            // Guardar confirmación en sesión y redirigir al login
            $_SESSION['login_error'] = 'Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión.';
            $respuesta->redirigir('/Cycsa/publico/login');

        } catch (\Exception $e) {
            error_log("Error al restablecer contraseña: " . $e->getMessage());
            $this->renderizarSinLayout('autenticacion/vistas/restablecer', [
                'titulo' => 'Ingresar Código - Cycsa',
                'email' => $email,
                'error' => 'Ocurrió un error interno. Intenta más tarde.'
            ]);
        }
    }
}