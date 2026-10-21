<?php
/**
 * Migración: 005_plantillas_dinamicas_relacionales.php
 * Crea las tablas relacionales para unidades, variables, plantillas dinámicas,
 * secciones, campos, versiones y el detalle formal de muestras en RT-FM-13.
 * Además, migra los 21 formatos existentes de formatos_ensayos hacia las nuevas tablas.
 *
 * Ejecución: php database/migrations/005_plantillas_dinamicas_relacionales.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "--- Iniciando Migración 005: Plantillas Dinámicas ISO 17025 ---\n";

// 1. Crear tabla unidades_medida
$db->exec("
CREATE TABLE IF NOT EXISTS `unidades_medida` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `simbolo` VARCHAR(25) NOT NULL,
  `magnitud` VARCHAR(50) DEFAULT 'General',
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 2. Crear tabla variables
$db->exec("
CREATE TABLE IF NOT EXISTS `variables` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(60) NOT NULL UNIQUE,
  `etiqueta` VARCHAR(150) NOT NULL,
  `tipo` ENUM('numero','texto','fecha','lista','calculado','tabla') NOT NULL DEFAULT 'numero',
  `unidad_id` INT NULL,
  `decimales` TINYINT DEFAULT 2,
  `minimo` DECIMAL(12,4) NULL,
  `maximo` DECIMAL(12,4) NULL,
  `opciones_lista` JSON NULL,
  `requerido` TINYINT(1) DEFAULT 1,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_var_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidades_medida` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 3. Crear tabla plantillas
$db->exec("
CREATE TABLE IF NOT EXISTS `plantillas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `ensayo_id` INT NOT NULL,
  `norma` VARCHAR(150) NOT NULL,
  `procedimiento_interno` VARCHAR(150) NULL,
  `orientacion` ENUM('vertical','horizontal') DEFAULT 'horizontal',
  `fondo_reporte_id` VARCHAR(100) DEFAULT 'hoja_membretada_horizontal.jpg',
  `incluir_logo_cycsa` TINYINT(1) DEFAULT 1,
  `incluir_sello_acreditacion` TINYINT(1) DEFAULT 1,
  `sello_acreditacion_url` VARCHAR(255) DEFAULT 'publico/img/sello_acreditacion_default.jpeg',
  `cliente_id` INT NULL,
  `plantilla_padre_id` INT NULL,
  `estado` ENUM('borrador','en_revision','aprobada','obsoleta') DEFAULT 'borrador',
  `version_actual` VARCHAR(20) DEFAULT 'Rev. 01',
  `es_por_defecto` TINYINT(1) DEFAULT 0,
  `creado_por` INT NOT NULL,
  `aprobado_por` INT NULL,
  `fecha_aprobacion` DATETIME NULL,
  `notas_version` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_plt_ensayo` FOREIGN KEY (`ensayo_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_plt_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plt_padre` FOREIGN KEY (`plantilla_padre_id`) REFERENCES `plantillas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plt_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_plt_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 4. Crear tabla plantilla_secciones
$db->exec("
CREATE TABLE IF NOT EXISTS `plantilla_secciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plantilla_id` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `orden` INT DEFAULT 1,
  `tipo_layout` ENUM('formulario','tabla_matriz') DEFAULT 'formulario',
  CONSTRAINT `fk_sec_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 5. Crear tabla plantilla_campos
$db->exec("
CREATE TABLE IF NOT EXISTS `plantilla_campos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plantilla_id` INT NOT NULL,
  `seccion_id` INT NOT NULL,
  `variable_id` INT NOT NULL,
  `orden` INT DEFAULT 1,
  `fila` INT DEFAULT 1,
  `columna` INT DEFAULT 1,
  `ancho_cols` TINYINT DEFAULT 4,
  `formula` TEXT NULL,
  `visible_en_reporte` TINYINT(1) DEFAULT 1,
  `solo_lectura` TINYINT(1) DEFAULT 0,
  `texto_ayuda` VARCHAR(255) NULL,
  CONSTRAINT `fk_cmp_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmp_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `plantilla_secciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmp_variable` FOREIGN KEY (`variable_id`) REFERENCES `variables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 6. Crear tabla plantilla_versiones
$db->exec("
CREATE TABLE IF NOT EXISTS `plantilla_versiones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plantilla_id` INT NOT NULL,
  `version` VARCHAR(20) NOT NULL,
  `esquema_json` LONGTEXT NOT NULL,
  `formato_legacy_json` LONGTEXT NOT NULL,
  `hash_integridad` VARCHAR(64) NOT NULL,
  `aprobado_por` INT NOT NULL,
  `fecha_aprobacion` DATETIME NOT NULL,
  `motivo_version` TEXT NULL,
  CONSTRAINT `fk_ver_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ver_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 7. Crear tabla hojas_solicitud_detalles
$db->exec("
CREATE TABLE IF NOT EXISTS `hojas_solicitud_detalles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_hoja_solicitud` INT NOT NULL,
  `orden` INT DEFAULT 1,
  `codigo_muestra` VARCHAR(50) NOT NULL,
  `descripcion_ubicacion` VARCHAR(255) NOT NULL,
  `observacion_edad` VARCHAR(255) NULL,
  `ensayo_id` INT NOT NULL,
  `plantilla_id` INT NOT NULL,
  `plantilla_version_id` INT NULL,
  `seleccion_manual` TINYINT(1) DEFAULT 0,
  `motivo_cambio` TEXT NULL,
  `bloqueado_recepcion` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_hsd_hoja` FOREIGN KEY (`id_hoja_solicitud`) REFERENCES `hojas_solicitud` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hsd_ensayo` FOREIGN KEY (`ensayo_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_hsd_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`),
  CONSTRAINT `fk_hsd_version` FOREIGN KEY (`plantilla_version_id`) REFERENCES `plantilla_versiones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 8. Asegurar columnas de plantilla en recepcion_muestras si no existen
$colRec = $db->query("SHOW COLUMNS FROM recepcion_muestras")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('plantilla_id', $colRec, true)) {
    $db->exec("ALTER TABLE `recepcion_muestras` ADD COLUMN `plantilla_id` INT NULL AFTER `codigo_campo`");
}
if (!in_array('plantilla_version_id', $colRec, true)) {
    $db->exec("ALTER TABLE `recepcion_muestras` ADD COLUMN `plantilla_version_id` INT NULL AFTER `plantilla_id`");
}

echo "[OK] Estructura de tablas relacionales creada exitosamente.\n";

// 9. Sembrar Unidades de Medida Iniciales
$unidadesBasicas = [
    ['Porcentaje', '%', 'Adimensional'],
    ['Kilogramo por centímetro cuadrado', 'kg/cm²', 'Presión'],
    ['Libra por pulgada cuadrada', 'lb/in²', 'Presión'],
    ['Kilogramo por metro cúbico', 'kg/m³', 'Densidad'],
    ['Libra por pie cúbico', 'lb/ft³', 'Densidad'],
    ['Milímetros', 'mm', 'Longitud'],
    ['Centímetros', 'cm', 'Longitud'],
    ['Pulgadas', 'in', 'Longitud'],
    ['Metros', 'm', 'Longitud'],
    ['Pulgadas cuadradas', 'in²', 'Área'],
    ['Centímetros cuadrados', 'cm²', 'Área'],
    ['Libras fuerza', 'lb', 'Fuerza'],
    ['Kilogramos fuerza', 'kg', 'Fuerza'],
    ['Kilonewtons', 'kN', 'Fuerza'],
    ['Grados Celsius', '°C', 'Temperatura'],
    ['Golpes', 'golpes', 'Conteo'],
    ['Días', 'días', 'Tiempo'],
    ['Horas', 'hrs', 'Tiempo'],
    ['Número', 'N°', 'Conteo'],
    ['Gramos', 'g', 'Masa'],
];

$stmtUnidad = $db->prepare("INSERT INTO `unidades_medida` (`nombre`, `simbolo`, `magnitud`) VALUES (:nom, :sim, :mag)");
$stmtCheckU = $db->prepare("SELECT id FROM `unidades_medida` WHERE `simbolo` = :sim LIMIT 1");
foreach ($unidadesBasicas as $u) {
    $stmtCheckU->execute(['sim' => $u[1]]);
    if (!$stmtCheckU->fetchColumn()) {
        $stmtUnidad->execute(['nom' => $u[0], 'sim' => $u[1], 'mag' => $u[2]]);
    }
}
echo "[OK] Unidades de medida sembradas.\n";

// 10. Sembrar Variables Base
$mapUnidades = [];
$rU = $db->query("SELECT id, simbolo FROM unidades_medida")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rU as $row) {
    $mapUnidades[$row['simbolo']] = (int)$row['id'];
}

$variablesBasicas = [
    ['codigo_lab', 'Código de Laboratorio', 'texto', null, 0, 1, 1],
    ['nombre_muestra', 'Nombre de la Muestra', 'texto', null, 0, 1, 1],
    ['fecha_fabricacion', 'Fecha de Fabricación / Moldeo', 'fecha', null, 0, 1, 1],
    ['fecha_ensayo', 'Fecha de Ensayo / Rotura', 'fecha', null, 0, 1, 1],
    ['edad_dias', 'Edad en Días', 'numero', $mapUnidades['días'] ?? null, 0, 1, 1],
    ['diametro_in', 'Diámetro de Probeta', 'numero', $mapUnidades['in'] ?? null, 2, 1, 1],
    ['altura_in', 'Altura de Probeta', 'numero', $mapUnidades['in'] ?? null, 2, 1, 1],
    ['area_in2', 'Área de Contacto', 'calculado', $mapUnidades['in²'] ?? null, 3, 0, 1],
    ['revenimiento_in', 'Revenimiento (Slump)', 'numero', $mapUnidades['in'] ?? null, 2, 0, 1],
    ['revenimiento_cm', 'Revenimiento (Slump)', 'numero', $mapUnidades['cm'] ?? null, 1, 0, 1],
    ['temperatura_c', 'Temperatura Concreto Fresco', 'numero', $mapUnidades['°C'] ?? null, 1, 0, 1],
    ['carga_lb', 'Carga Máxima de Rotura', 'numero', $mapUnidades['lb'] ?? null, 2, 1, 1],
    ['peso_volumetrico_kgm3', 'Peso Volumétrico', 'numero', $mapUnidades['kg/m³'] ?? null, 1, 0, 1],
    ['resistencia_diseno_psi', 'Resistencia de Diseño f\'c', 'numero', $mapUnidades['lb/in²'] ?? null, 0, 1, 1],
    ['resistencia_psi', 'Resistencia a Compresión', 'calculado', $mapUnidades['lb/in²'] ?? null, 1, 0, 1],
    ['resistencia_kgcm2', 'Resistencia a Compresión', 'calculado', $mapUnidades['kg/cm²'] ?? null, 1, 0, 1],
    ['porcentaje_cumplimiento', 'Porcentaje de Cumplimiento', 'calculado', $mapUnidades['%'] ?? null, 1, 0, 1],
    ['tipo_fractura', 'Tipo de Fractura (ASTM C39)', 'lista', null, 0, 0, 1],
    ['capa_num', 'Capa N°', 'numero', null, 0, 0, 1],
    ['espesor_cm', 'Espesor Capa', 'numero', $mapUnidades['cm'] ?? null, 1, 0, 1],
    ['profundidad_cm', 'Profundidad', 'numero', $mapUnidades['cm'] ?? null, 1, 0, 1],
    ['pvs_max_kgm3', 'P.V.S. Máximo (Proctor)', 'numero', $mapUnidades['kg/m³'] ?? null, 1, 1, 1],
    ['humedad_optima_pct', 'Humedad Óptima', 'numero', $mapUnidades['%'] ?? null, 2, 1, 1],
    ['pvs_sitio_kgm3', 'P.V.S. Sitio (Seco)', 'numero', $mapUnidades['kg/m³'] ?? null, 1, 1, 1],
    ['humedad_sitio_pct', 'Humedad en Sitio', 'numero', $mapUnidades['%'] ?? null, 2, 1, 1],
    ['compactacion_pct', 'Grado de Compactación', 'calculado', $mapUnidades['%'] ?? null, 1, 0, 1],
    ['limite_liquido', 'Límite Líquido (LL)', 'numero', $mapUnidades['%'] ?? null, 1, 0, 1],
    ['limite_plastico', 'Límite Plástico (LP)', 'numero', $mapUnidades['%'] ?? null, 1, 0, 1],
    ['indice_plasticidad', 'Índice de Plasticidad (IP)', 'calculado', $mapUnidades['%'] ?? null, 1, 0, 1],
    ['clasificacion_sucs', 'Clasificación SUCS', 'texto', null, 0, 0, 1],
];

$stmtVarCheck = $db->prepare("SELECT id FROM `variables` WHERE `codigo` = :cod LIMIT 1");
$stmtVarInsert = $db->prepare("INSERT INTO `variables` (`codigo`, `etiqueta`, `tipo`, `unidad_id`, `decimales`, `requerido`, `activo`) VALUES (:cod, :eti, :tip, :uni, :dec, :req, :act)");
foreach ($variablesBasicas as $v) {
    $stmtVarCheck->execute(['cod' => $v[0]]);
    if (!$stmtVarCheck->fetchColumn()) {
        $stmtVarInsert->execute([
            'cod' => $v[0],
            'eti' => $v[1],
            'tip' => $v[2],
            'uni' => $v[3],
            'dec' => $v[4],
            'req' => $v[5],
            'act' => $v[6]
        ]);
    }
}
echo "[OK] Variables base sembradas.\n";

// 11. Migrar formatos_ensayos hacia plantillas y versiones inmutables
$usuarioAdminId = (int)$db->query("SELECT id FROM usuarios WHERE id_rol = 1 LIMIT 1")->fetchColumn() ?: 1;
$formatos = $db->query("SELECT * FROM formatos_ensayos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmtFindProd = $db->prepare("SELECT id, norma_astm, procedimiento_muestreo FROM productos WHERE formato_id = :fid LIMIT 1");
$stmtPltCheck = $db->prepare("SELECT id FROM plantillas WHERE codigo = :cod LIMIT 1");
$stmtPltInsert = $db->prepare("
INSERT INTO plantillas (codigo, nombre, ensayo_id, norma, procedimiento_interno, orientacion, fondo_reporte_id, incluir_logo_cycsa, incluir_sello_acreditacion, sello_acreditacion_url, cliente_id, estado, version_actual, es_por_defecto, creado_por, aprobado_por, fecha_aprobacion, notas_version)
VALUES (:cod, :nom, :ensayo_id, :norma, :proc, :orientacion, :fondo, 1, 1, 'publico/img/sello_acreditacion_default.jpeg', NULL, 'aprobada', :version, 1, :creado_por, :aprobado_por, NOW(), 'Migración inicial de formato oficial')
");
$stmtVerInsert = $db->prepare("
INSERT INTO plantilla_versiones (plantilla_id, version, esquema_json, formato_legacy_json, hash_integridad, aprobado_por, fecha_aprobacion, motivo_version)
VALUES (:pid, :ver, :esq, :leg, :hash, :aprob, NOW(), 'Versión canónica aprobada V1R2')
");

$plantillasMigradas = 0;
foreach ($formatos as $fmt) {
    $formatoId = (int)$fmt['id'];
    $stmtFindProd->execute(['fid' => $formatoId]);
    $prod = $stmtFindProd->fetch(PDO::FETCH_ASSOC);
    if (!$prod) {
        // Asignar primer producto general si no estuviera mapeado
        $ensayoId = (int)$db->query("SELECT id FROM productos LIMIT 1")->fetchColumn();
        $norma = 'ASTM';
        $proc = $fmt['procedimientos'] ?? 'CYCSA-PE';
    } else {
        $ensayoId = (int)$prod['id'];
        $norma = !empty($prod['norma_astm']) ? $prod['norma_astm'] : 'ASTM';
        $proc = !empty($fmt['procedimientos']) ? $fmt['procedimientos'] : $prod['procedimiento_muestreo'];
    }

    $codSlug = 'PL-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $fmt['codigo_formato'] . '-' . $fmt['id']);
    $stmtPltCheck->execute(['cod' => $codSlug]);
    $existenteId = $stmtPltCheck->fetchColumn();

    if (!$existenteId) {
        $orientacion = 'horizontal';
        $fondo = 'hoja_membretada_horizontal.jpg';
        $version = !empty($fmt['version_formato']) ? $fmt['version_formato'] : 'V1R2';

        $stmtPltInsert->execute([
            'cod' => $codSlug,
            'nom' => $fmt['nombre'],
            'ensayo_id' => $ensayoId,
            'norma' => $norma,
            'proc' => $proc,
            'orientacion' => $orientacion,
            'fondo' => $fondo,
            'version' => $version,
            'creado_por' => $usuarioAdminId,
            'aprobado_por' => $usuarioAdminId
        ]);
        $plantillaId = (int)$db->lastInsertId();

        // Crear versión inmutable
        $legacyJson = $fmt['configuracion_json'] ?: '{}';
        $esquema = [
            'id' => $plantillaId,
            'codigo' => $codSlug,
            'nombre' => $fmt['nombre'],
            'ensayo_id' => $ensayoId,
            'norma' => $norma,
            'orientacion' => $orientacion,
            'fondo_reporte_id' => $fondo,
            'sello_acreditacion_url' => 'publico/img/sello_acreditacion_default.jpeg',
            'legacy_data' => json_decode($legacyJson, true)
        ];
        $esquemaStr = json_encode($esquema, JSON_UNESCAPED_UNICODE);
        $hashIntegridad = hash('sha256', $esquemaStr);

        $stmtVerInsert->execute([
            'pid' => $plantillaId,
            'ver' => $version,
            'esq' => $esquemaStr,
            'leg' => $legacyJson,
            'hash' => $hashIntegridad,
            'aprob' => $usuarioAdminId
        ]);

        $plantillasMigradas++;
    }
}
echo "[OK] {$plantillasMigradas} plantillas migradas con versiones inmutables.\n";

// 12. Migrar muestras existentes en hojas_solicitud a hojas_solicitud_detalles
$hojas = $db->query("SELECT id, id_os, muestras_json, fecha_creacion FROM hojas_solicitud ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$stmtDetCheck = $db->prepare("SELECT COUNT(*) FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid");
$stmtDetInsert = $db->prepare("
INSERT INTO hojas_solicitud_detalles (id_hoja_solicitud, orden, codigo_muestra, descripcion_ubicacion, observacion_edad, ensayo_id, plantilla_id, plantilla_version_id, seleccion_manual, motivo_cambio, bloqueado_recepcion)
VALUES (:hid, :ord, :cod, :desc, :obs, :ensayo_id, :plantilla_id, :version_id, 0, NULL, :bloqueado)
");

// Obtener plantilla por defecto para fallback
$plantillaDefault = $db->query("SELECT p.id, p.ensayo_id, pv.id as version_id FROM plantillas p JOIN plantilla_versiones pv ON p.id = pv.plantilla_id WHERE p.estado = 'aprobada' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$totalDetallesMigrados = 0;
foreach ($hojas as $h) {
    $hid = (int)$h['id'];
    $stmtDetCheck->execute(['hid' => $hid]);
    if ((int)$stmtDetCheck->fetchColumn() > 0) {
        continue;
    }

    $muestras = json_decode($h['muestras_json'] ?? '[]', true) ?: [];
    if (empty($muestras)) continue;

    // Verificar si la OS está finalizada
    $idOS = (int)$h['id_os'];
    $stOS = $db->prepare("SELECT estado FROM ordenes_servicio WHERE id = :idos");
    $stOS->execute(['idos' => $idOS]);
    $estadoOS = $stOS->fetchColumn() ?: '';
    $bloqueado = in_array($estadoOS, ['Finalizado', 'Archivado', 'Cerrado'], true) ? 1 : 0;

    $ord = 1;
    foreach ($muestras as $m) {
        $nom = trim($m['nombre_muestra'] ?? $m['identificacion'] ?? ("M-" . $ord));
        $desc = trim($m['descripcion'] ?? $m['ubicacion'] ?? 'Muestra recepcionada');
        $obs = trim($m['info_importante'] ?? $m['observacion'] ?? '');

        // Determinar ensayo y plantilla adecuada
        $ensayoId = $plantillaDefault ? (int)$plantillaDefault['ensayo_id'] : 1;
        $plantillaId = $plantillaDefault ? (int)$plantillaDefault['id'] : 1;
        $versionId = $plantillaDefault ? (int)$plantillaDefault['version_id'] : 1;

        $stmtDetInsert->execute([
            'hid' => $hid,
            'ord' => $ord,
            'cod' => $nom,
            'desc' => $desc,
            'obs' => $obs,
            'ensayo_id' => $ensayoId,
            'plantilla_id' => $plantillaId,
            'version_id' => $versionId,
            'bloqueado' => $bloqueado
        ]);
        $ord++;
        $totalDetallesMigrados++;
    }
}
echo "[OK] {$totalDetallesMigrados} registros migrados a hojas_solicitud_detalles.\n";
echo "--- Migración 005 finalizada con éxito ---\n";
