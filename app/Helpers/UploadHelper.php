<?php

namespace Cycsa\App\Helpers;

/**
 * Helper para subida segura de archivos, fotos, firmas y documentos.
 */
class UploadHelper {

    private static array $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'docx'];
    private static int $tamanoMaximo = 10485760; // 10 MB

    public static function guardar(array $archivo, string $directorioDestino): array {
        if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Error en la subida del archivo.'];
        }

        if ($archivo['size'] > self::$tamanoMaximo) {
            return ['ok' => false, 'error' => 'El archivo supera el tamaño máximo permitido (10 MB).'];
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$extensionesPermitidas)) {
            return ['ok' => false, 'error' => 'Tipo de archivo no permitido.'];
        }

        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        $nombreNuevo = uniqid('CYC_') . '.' . $extension;
        $rutaCompleta = rtrim($directorioDestino, '/') . '/' . $nombreNuevo;

        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            return ['ok' => true, 'archivo' => $nombreNuevo, 'ruta' => $rutaCompleta];
        }

        return ['ok' => false, 'error' => 'No se pudo mover el archivo subido.'];
    }
}
