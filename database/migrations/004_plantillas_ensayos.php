<?php
/**
 * Ejecutar una vez (repetible): php database/migrations/004_plantillas_ensayos.php
 * Conserva las plantillas editadas; solo inicializa configuracion_json si está vacía.
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$schemaPath = dirname(__DIR__) . '/ensayos/formatos_schema.json';
$base = json_decode(file_get_contents($schemaPath), true, 512, JSON_THROW_ON_ERROR);

$columnas = $db->query("SHOW COLUMNS FROM formatos_ensayos")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('configuracion_json', $columnas, true)) {
    $db->exec('ALTER TABLE formatos_ensayos ADD COLUMN configuracion_json LONGTEXT NULL');
}
if (!in_array('version_formato', $columnas, true)) {
    $db->exec("ALTER TABLE formatos_ensayos ADD COLUMN version_formato VARCHAR(50) NOT NULL DEFAULT 'V1R2'");
}
if (!in_array('fecha_actualizacion', $columnas, true)) {
    $db->exec('ALTER TABLE formatos_ensayos ADD COLUMN fecha_actualizacion TIMESTAMP NULL DEFAULT NULL');
}

$select = $db->query('SELECT id, archivo_markdown, configuracion_json FROM formatos_ensayos');
$update = $db->prepare('UPDATE formatos_ensayos SET configuracion_json = :json, version_formato = :version, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = :id AND (configuracion_json IS NULL OR configuracion_json = \'\')');
$sembrados = 0;
$db->beginTransaction();
try {
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $formato) {
        if (!empty($formato['configuracion_json'])) continue;
        $config = $base[$formato['archivo_markdown']] ?? null;
        if (!is_array($config)) continue;
        $codigo = $config['codigo_formato'] ?? 'CYCSA-RT-FM-22';
        preg_match('/V\d+\s*-?\s*R\d+/i', $codigo, $match);
        $version = isset($match[0]) ? preg_replace('/\s|-/', '', strtoupper($match[0])) : 'V1R2';
        $config['version_documento'] = $version;
        $config['titulo_informe'] = 'INFORME DE ENSAYO';
        $config['subtitulo_laboratorio'] = 'Laboratorio de Ensayos y Control de Calidad';
        $config['firmante_nombre'] = 'Ing. Noel Quintana Lira';
        $config['firmante_cargo'] = 'Gerente General';
        $update->execute([
            'json' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'version' => $version,
            'id' => $formato['id'],
        ]);
        $sembrados += $update->rowCount();
    }
    $db->commit();
    echo "Plantillas inicializadas: {$sembrados}\n";
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
