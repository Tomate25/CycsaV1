<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Cycsa - Login' ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-contenedor {
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-contenedor h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .grupo-input {
            margin-bottom: 20px;
        }
        .grupo-input label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-weight: 500;
        }
        .grupo-input input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Para que el padding no desborde el input */
            font-size: 16px;
        }
        .btn-ingresar {
            width: 100%;
            padding: 12px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-ingresar:hover {
            background-color: #004494;
        }
    </style>
</head>
<body>

    <div class="login-contenedor">
        <h2>Ingresar al Sistema</h2>
        
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid #ef9a9a;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="grupo-input">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            
            <div class="grupo-input">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-ingresar">Iniciar Sesión</button>
        </form>
    </div>

</body>
</html>