<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Iniciar Sesión - Cycsa' ?></title>
    <link rel="shortcut icon" href="/Cycsa/publico/img/logo.png" type="image/png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #103487;
            --primary-hover: #1e40af;
            --primary-glow: rgba(16, 52, 135, 0.4);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.1);
            --border-glow: rgba(255, 255, 255, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% 50%, #0f172a 0%, #020617 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            position: relative;
            color: #f8fafc;
        }

        /* Efectos de luces difuminadas de fondo */
        .light-glow-1 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 52, 135, 0.25) 0%, rgba(0,0,0,0) 70%);
            top: -150px;
            left: -150px;
            filter: blur(60px);
            z-index: 1;
            pointer-events: none;
        }

        .light-glow-2 {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(227, 24, 55, 0.08) 0%, rgba(0,0,0,0) 70%);
            bottom: -200px;
            right: -200px;
            filter: blur(60px);
            z-index: 1;
            pointer-events: none;
        }

        /* Contenedor Login (Efecto Glassmorphism) */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-contenedor {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glow);
            padding: 45px 40px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
            opacity: 0;
            transform: translateY(25px);
            animation: fadeInSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInSlideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Branding */
        .brand-logo-img {
            max-height: 70px;
            margin-bottom: 12px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.1));
            animation: pulseLogo 3s ease-in-out infinite;
        }

        @keyframes pulseLogo {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .brand-subtitle {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 35px;
            display: block;
        }

        /* Form Controls */
        .grupo-input {
            margin-bottom: 22px;
            text-align: left;
            position: relative;
        }

        .grupo-input label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: #64748b;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .grupo-input input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .grupo-input input:focus {
            border-color: #38bdf8;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .grupo-input input:focus + i {
            color: #38bdf8;
        }

        /* Alerta de Error */
        .alert-error {
            background: var(--danger-bg);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        /* Botón de Ingreso */
        .btn-ingresar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, var(--primary) 0%, #1e40af 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15.5px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            box-shadow: 0 4px 20px rgba(16, 52, 135, 0.35);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-ingresar:hover {
            background: linear-gradient(90deg, #1e40af 0%, #3b82f6 100%);
            transform: translateY(-1.5px);
            box-shadow: 0 6px 24px rgba(59, 130, 246, 0.45);
        }

        .btn-ingresar:active {
            transform: translateY(0);
        }

        /* Footer del Login */
        .login-footer {
            margin-top: 30px;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 20px;
        }
    </style>
</head>
<body>

    <div class="light-glow-1"></div>
    <div class="light-glow-2"></div>

    <div class="login-wrapper">
        <div class="login-contenedor">
            <!-- Logo Corporativo -->
            <img src="/Cycsa/publico/img/logo.png" alt="Logo CYCSA" class="brand-logo-img">
            
            <div class="brand-title">CYCSA ERP</div>
            <span class="brand-subtitle">Laboratorio de Control de Calidad</span>
            
            <!-- Mensaje de Error -->
            <?php if (isset($error)): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulario de Acceso -->
            <form action="" method="POST">
                <div class="grupo-input">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" required autocomplete="email" placeholder="ejemplo@cycsa.com">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                </div>
                
                <div class="grupo-input">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: -12px; margin-bottom: 20px;">
                    <a href="/Cycsa/publico/recuperar-password" style="color: #38bdf8; text-decoration: none; font-size: 13px; font-weight: 500; transition: color 0.3s ease;">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn-ingresar">
                    <span>Iniciar Sesión</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <div class="login-footer">
                &copy; <?= date('Y') ?> CYCSA S.A. Todos los derechos reservados.
            </div>
        </div>
    </div>

</body>
</html>