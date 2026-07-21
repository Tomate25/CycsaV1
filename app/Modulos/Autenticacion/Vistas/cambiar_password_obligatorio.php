<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Actualizar Contraseña Obligatoria - Cycsa' ?></title>
    <link rel="shortcut icon" href="/Cycsa/publico/img/logo.png" type="image/png">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #103487;
            --primary-hover: #1e40af;
            --danger: #ef4444;
            --border-glow: rgba(255, 255, 255, 0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% 50%, #0f172a 0%, #020617 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #f8fafc;
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }

        .login-contenedor {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glow);
            padding: 40px 35px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            text-align: center;
        }

        .brand-logo-img {
            max-height: 60px;
            margin-bottom: 12px;
            object-fit: contain;
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: white;
            margin-bottom: 6px;
        }

        .brand-subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 25px;
            display: block;
            line-height: 1.4;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .grupo-input {
            margin-bottom: 20px;
            text-align: left;
        }

        .grupo-input label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: #64748b;
            font-size: 15px;
        }

        .btn-ingresar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-ingresar:hover {
            background: linear-gradient(90deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-contenedor">
            <img src="/Cycsa/publico/img/logo.png" alt="Logo CYCSA" class="brand-logo-img">
            
            <div class="brand-title">Actualizar Contraseña</div>
            <span class="brand-subtitle">Has ingresado con una contraseña temporal de desbloqueo. Por tu seguridad, establece una nueva contraseña para continuar.</span>
            
            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form action="/Cycsa/publico/cambiar-password-obligatorio" method="POST">
                <div class="grupo-input">
                    <label for="password">Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                        <i class="fa-solid fa-key"></i>
                    </div>
                </div>

                <div class="grupo-input">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Repite la contraseña">
                        <i class="fa-solid fa-shield"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-ingresar">
                    <span>Guardar y Entrar al ERP</span>
                    <i class="fa-solid fa-check-double"></i>
                </button>
            </form>
        </div>
    </div>

</body>
</html>
