<?php
// subir.php - Script temporal para subir y descomprimir el sistema en Bluehost
ini_set('display_errors', 1);
error_reporting(E_ALL);

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_zip'])) {
    $zipFile = $_FILES['archivo_zip'];
    
    if ($zipFile['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = basename($zipFile['name']);
        
        if (pathinfo($nombreArchivo, PATHINFO_EXTENSION) === 'zip') {
            $destino = __DIR__ . '/' . $nombreArchivo;
            
            if (move_uploaded_file($zipFile['tmp_name'], $destino)) {
                $mensaje .= "✔️ Archivo ZIP subido con éxito a: $destino<br>";
                
                // Descomprimir
                $zip = new ZipArchive;
                if ($zip->open($destino) === TRUE) {
                    $zip->extractTo(__DIR__);
                    $zip->close();
                    $mensaje .= "✔️ Archivo ZIP descomprimido correctamente en la carpeta actual.<br>";
                    
                    // Borrar el ZIP para limpiar
                    unlink($destino);
                    $mensaje .= "✔️ Archivo ZIP temporal eliminado.<br>";
                    
                    // Ejecutar automáticamente el corrector de permisos si existe
                    $fixScript = __DIR__ . '/fix_permissions.php';
                    if (file_exists($fixScript)) {
                        $mensaje .= "⚙️ Ejecutando corrector de permisos recursivo...<br>";
                        ob_start();
                        include $fixScript;
                        $res = ob_get_clean();
                        $mensaje .= "<pre>" . htmlspecialchars($res) . "</pre>";
                    }
                } else {
                    $mensaje .= "❌ Error al abrir el archivo ZIP.<br>";
                }
            } else {
                $mensaje .= "❌ Error al mover el archivo subido al directorio de destino.<br>";
            }
        } else {
            $mensaje .= "❌ Error: El archivo debe tener extensión .zip.<br>";
        }
    } else {
        $mensaje .= "❌ Error de subida. Código: " . $zipFile['error'] . "<br>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subidor de Emergencia - CYCSA</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; padding: 40px; color: #334155; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        h2 { color: #103487; margin-top: 0; }
        .btn { background: #103487; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 15px; }
        .info { font-size: 13px; color: #64748b; margin-top: 10px; }
        .log { background: #f1f5f9; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; border-left: 4px solid #103487; margin-top: 20px; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Subidor y Extractor de Emergencia</h2>
        <p>Utiliza este formulario para subir el archivo <strong>sistema.zip</strong> o <strong>cycsa_subir.zip</strong> directamente desde tu PC.</p>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="archivo_zip" accept=".zip" required style="width: 100%;">
            <button type="submit" class="btn">Subir y Descomprimir</button>
        </form>
        
        <p class="info">Límite de subida de PHP actual: <strong><?php echo ini_get('upload_max_filesize'); ?></strong></p>
        
        <?php if (!empty($mensaje)): ?>
            <div class="log">
                <strong>Resultados:</strong><br>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
