<?php
/**
 * Migración 039: Plan Maestro CYCSA (Acuerdos 16 Nov 2026 — Fases 1, 2 y 3)
 *
 * Idempotente y compatible con MySQL 5.7 / 8.0 / MariaDB:
 * 1) Crea tabla `config_calculo_lab` con parámetros configurables de laboratorio y flujos.
 * 2) Amplía `roturas` (peso_volumetrico, edad_calculada) y `plan_rotura_edades.tolerancia_horas` (DECIMAL(5,2) para ±0.5h ASTM C39).
 * 3) Amplía `informes_rotura` (revisado_por, fecha_revision, revisado_nombre, aprobado_nombre, observaciones_pendientes, es_interno).
 * 4) Crea `ordenes_servicio_versiones` y agrega `version`, `motivo_cambio_version` en `ordenes_servicio`.
 * 5) Agrega `id_responsable_interno`, `aprobacion_interna_por`, `fecha_aprobacion_interna` en `cotizaciones`.
 * 6) Agrega `cuota_mensual` en `orden_servicio_ensayos_permitidos`.
 * 7) Corrige unidades en `formatos_ensayos` (`P.V.S.Sitio (kg/m³)`, `Módulo de finura`, `50 mm`),
 *    separa Colorimetría (`PE-05`) de Suelo-Cemento (`PE-58`) y registra plantillas `RT-FM-22 H` / `F` faltantes.
 */

use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;

if (!function_exists('ejecutarMigracion039')) {
    function ejecutarMigracion039(PDO $db): void {
        $columnaExiste = static function (PDO $pdo, string $tabla, string $columna): bool {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE " . $pdo->quote($columna));
                return $stmt && (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                return false;
            }
        };

        $tablaExiste = static function (PDO $pdo, string $tabla): bool {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
                return $stmt && (bool)$stmt->fetch(PDO::FETCH_NUM);
            } catch (Throwable $e) {
                return false;
            }
        };

        // 1. Tabla config_calculo_lab (parámetros configurables de laboratorio y flujos)
        $db->exec("
            CREATE TABLE IF NOT EXISTS `config_calculo_lab` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `clave` VARCHAR(100) NOT NULL UNIQUE,
                `valor` TEXT NULL,
                `descripcion` VARCHAR(255) NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $descripciones = [
            'cilindros_divisor_kgcm2' => 'Divisor oficial psi -> kgf/cm² (14.2029 hoja RT-FM-61, configurable a 14.2 o 14.223)',
            'cilindros_usar_prensa' => 'Aplicar corrección polinomial PRENSA-01 en cilindros (0=hoja campo oficial, 1=central)',
            'cilindros_usar_factor_ld' => 'Aplicar factor L/D ASTM C42/C39 en cilindros (0=hoja oficial, 1=activar)',
            'cilindros_redondear_psi_10' => 'Redondear resistencia a compresión a los 10 psi más cercanos (acuerdo 16 nov)',
            'cilindros_redondear_pv_10' => 'Redondear peso volumétrico a los 10 kg/m³ más cercanos',
            'proctor_metodo' => 'Método de reporte Proctor RT-FM-27: maximo_medido o regresion_parabolica',
            'cbr_modo_reporte' => 'Regla CBR RT-FM-46: golpes (100/95/90 por 56/25/12 golpes) o interpolado',
            'martillo_posicion_defecto' => 'Posición por defecto del polinomio de esclerómetro (A, B o C)',
            'martillo_usar_factor_cal' => 'Aplicar factor de calibración 0.976 en martillo suizo (0=no, 1=sí)',
            'martillo_redondeo_psi' => 'Paso de redondeo en martillo suizo (1, 10 o 100 psi)',
            'informe_incluir_incertidumbre' => 'Mostrar incertidumbre U junto a la resistencia en informe final (0=no, 1=sí)',
            'informe_envio_auto_aprobar' => 'Enviar automáticamente el informe PDF al aprobarlo (0=manual, 1=automático)',
            'informe_parcial_es_interno' => 'Marcar informes parciales por muestra rota como documento interno (0=cliente, 1=interno)',
            'informe_ubicacion_aditivos' => 'Ubicación de notas de aditivos en informe (pie, cabecera o ambos)',
            'cotizacion_responsable_interno_id' => 'ID de usuario responsable interno autorizado para aprobar cotizaciones',
            'informe_observaciones_pendientes_catalogo' => 'Catálogo JSON de observaciones pendientes para sección de firmas',
        ];

        $defaults = class_exists(ConfigCalculoLabServicio::class)
            ? ConfigCalculoLabServicio::DEFAULTS
            : [
                'cilindros_divisor_kgcm2' => '14.2029',
                'cilindros_usar_prensa' => '0',
                'cilindros_usar_factor_ld' => '0',
                'cilindros_redondear_psi_10' => '1',
                'cilindros_redondear_pv_10' => '1',
                'proctor_metodo' => 'maximo_medido',
                'cbr_modo_reporte' => 'golpes',
                'martillo_posicion_defecto' => 'A',
                'martillo_usar_factor_cal' => '0',
                'martillo_redondeo_psi' => '100',
                'informe_incluir_incertidumbre' => '0',
                'informe_envio_auto_aprobar' => '0',
                'informe_parcial_es_interno' => '0',
                'informe_ubicacion_aditivos' => 'pie',
                'cotizacion_responsable_interno_id' => '',
                'informe_observaciones_pendientes_catalogo' => '["Pendiente firma de revisión técnica","Pendiente verificación de equipo calibrado","Pendiente confirmación de datos de recepción","Observación de cliente en trámite"]',
            ];

        $stmtCfg = $db->prepare("INSERT IGNORE INTO config_calculo_lab (clave, valor, descripcion) VALUES (:clave, :valor, :desc)");
        foreach ($defaults as $k => $v) {
            $stmtCfg->execute([
                'clave' => $k,
                'valor' => $v,
                'desc' => $descripciones[$k] ?? null,
            ]);
        }

        // 2. Columnas en roturas y plan_rotura_edades (Fase 1: 1.3, 1.4, 1.8)
        if ($tablaExiste($db, 'roturas')) {
            if (!$columnaExiste($db, 'roturas', 'peso_volumetrico')) {
                $db->exec("ALTER TABLE `roturas` ADD COLUMN `peso_volumetrico` DECIMAL(10,2) NULL AFTER `area`");
            }
            if (!$columnaExiste($db, 'roturas', 'edad_calculada')) {
                $db->exec("ALTER TABLE `roturas` ADD COLUMN `edad_calculada` INT NULL AFTER `fecha_real`");
            }
        }

        if ($tablaExiste($db, 'plan_rotura_edades')) {
            $colTol = $db->query("SHOW COLUMNS FROM `plan_rotura_edades` LIKE 'tolerancia_horas'")->fetch(PDO::FETCH_ASSOC);
            if ($colTol && stripos((string)($colTol['Type'] ?? ''), 'decimal') === false) {
                $db->exec("ALTER TABLE `plan_rotura_edades` MODIFY COLUMN `tolerancia_horas` DECIMAL(5,2) NOT NULL DEFAULT 20.00");
            }
        }

        // 3. Columnas en informes_rotura (Fase 2: 2.2, 2.7, 2.20)
        if ($tablaExiste($db, 'informes_rotura')) {
            if (!$columnaExiste($db, 'informes_rotura', 'revisado_por')) {
                $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `revisado_por` INT NULL AFTER `estado`");
            }
            if (!$columnaExiste($db, 'informes_rotura', 'fecha_revision')) {
                $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `fecha_revision` DATETIME NULL AFTER `revisado_por`");
            }
            if (!$columnaExiste($db, 'informes_rotura', 'revisado_nombre')) {
                $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `revisado_nombre` VARCHAR(150) NULL AFTER `fecha_revision`");
            }
            if (!$columnaExiste($db, 'informes_rotura', 'aprobado_nombre')) {
                $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `aprobado_nombre` VARCHAR(150) NULL AFTER `aprobado_por`");
            }
            if (!$columnaExiste($db, 'informes_rotura', 'observaciones_pendientes')) {
                $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `observaciones_pendientes` TEXT NULL AFTER `observaciones`");
            }
            if (!$columnaExiste($db, 'informes_rotura', 'es_interno')) {
                $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `es_interno` TINYINT(1) NOT NULL DEFAULT 0 AFTER `observaciones_pendientes`");
            }
        }

        // 4. Versionado de Órdenes de Servicio (Fase 2: 2.5)
        if ($tablaExiste($db, 'ordenes_servicio')) {
            if (!$columnaExiste($db, 'ordenes_servicio', 'version')) {
                $db->exec("ALTER TABLE `ordenes_servicio` ADD COLUMN `version` INT NOT NULL DEFAULT 1");
            }
            if (!$columnaExiste($db, 'ordenes_servicio', 'motivo_cambio_version')) {
                $db->exec("ALTER TABLE `ordenes_servicio` ADD COLUMN `motivo_cambio_version` TEXT NULL");
            }
        }

        $db->exec("
            CREATE TABLE IF NOT EXISTS `ordenes_servicio_versiones` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `id_os` INT NOT NULL,
                `version` INT NOT NULL,
                `snapshot_json` LONGTEXT NOT NULL,
                `motivo_cambio` TEXT NULL,
                `usuario_id` INT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_os_ver_id_os` (`id_os`),
                UNIQUE KEY `uq_os_ver` (`id_os`, `version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 5. Aprobación interna y responsable interno en cotizaciones (Fase 2: 2.9, 2.10, 2.11)
        if ($tablaExiste($db, 'cotizaciones')) {
            if (!$columnaExiste($db, 'cotizaciones', 'id_responsable_interno')) {
                $db->exec("ALTER TABLE `cotizaciones` ADD COLUMN `id_responsable_interno` INT NULL");
            }
            if (!$columnaExiste($db, 'cotizaciones', 'aprobacion_interna_por')) {
                $db->exec("ALTER TABLE `cotizaciones` ADD COLUMN `aprobacion_interna_por` INT NULL");
            }
            if (!$columnaExiste($db, 'cotizaciones', 'fecha_aprobacion_interna')) {
                $db->exec("ALTER TABLE `cotizaciones` ADD COLUMN `fecha_aprobacion_interna` DATETIME NULL");
            }
        }

        // 6. Alcance mensual por ensayo en orden_servicio_ensayos_permitidos (Fase 2: 2.13, 2.14)
        if ($tablaExiste($db, 'orden_servicio_ensayos_permitidos')) {
            if (!$columnaExiste($db, 'orden_servicio_ensayos_permitidos', 'cuota_mensual')) {
                $db->exec("ALTER TABLE `orden_servicio_ensayos_permitidos` ADD COLUMN `cuota_mensual` INT NULL");
            }
        }

        // 7. Correcciones de unidades y formatos H/F en formatos_ensayos (Fase 3: 3.1-3.8)
        if ($tablaExiste($db, 'formatos_ensayos')) {
            // Corregir unidades erróneas en configuracion_json de formatos existentes
            $reemplazos = [
                'P.V.S.Sitio (kg/cm²)' => 'P.V.S.Sitio (kg/m³)',
                'Módulo de finura ((%) (P/P))' => 'Módulo de finura',
                '50 nm' => '50 mm',
            ];
            $stmtFmt = $db->query("SELECT id, configuracion_json FROM formatos_ensayos WHERE configuracion_json IS NOT NULL");
            if ($stmtFmt) {
                $stmtUp = $db->prepare("UPDATE formatos_ensayos SET configuracion_json = :cfg WHERE id = :id");
                while ($row = $stmtFmt->fetch(PDO::FETCH_ASSOC)) {
                    $orig = (string)$row['configuracion_json'];
                    $nuevo = str_replace(array_keys($reemplazos), array_values($reemplazos), $orig);
                    if ($nuevo !== $orig) {
                        $stmtUp->execute(['cfg' => $nuevo, 'id' => (int)$row['id']]);
                    }
                }
            }

            // Registrar formatos faltantes de Fase 3 de manera idempotente (por archivo_markdown)
            $colsFmt = $db->query("SHOW COLUMNS FROM `formatos_ensayos`")->fetchAll(PDO::FETCH_COLUMN);
            $tieneArchivo = in_array('archivo_markdown', $colsFmt, true);
            $tieneNombre = in_array('nombre', $colsFmt, true);
            $tieneCodigo = in_array('codigo_formato', $colsFmt, true);
            $tieneConfig = in_array('configuracion_json', $colsFmt, true);

            if ($tieneArchivo && $tieneNombre && $tieneConfig) {
                $nuevosFormatos = [
                    [
                        'archivo' => 'formato_cbr_h07.md',
                        'codigo' => 'CYCSA-RT-FM-22 H-7',
                        'nombre' => 'Relación de Soporte California (CBR)',
                        'config' => [
                            'formato_codigo' => 'CYCSA-RT-FM-22 H-7',
                            'ensayo_titulo' => 'Relación de Soporte California (CBR) de Laboratorio',
                            'metodo_muestreo' => 'DE-ASTM D1883-21 / AASHTO T193',
                            'orientacion' => 'horizontal',
                            'columns' => ['Código laboratorio', 'Nombre muestra', 'Golpes/Capa', 'Densidad Seca (kg/m³)', 'Humedad Moldeo (%)', 'Compactación (%)', 'CBR 0.1" (%)', 'CBR 0.2" (%)', 'CBR Adoptado (%)', 'Fecha de Ensayo'],
                            'column_methods' => ['CBR 0.1" (%)' => 'CYCSA-PE-11', 'CBR 0.2" (%)' => 'CYCSA-PE-11', 'CBR Adoptado (%)' => 'CYCSA-PE-11'],
                            'notas' => ['Ensayo ejecutado conforme a ASTM D1883 / AASHTO T193. Reporte configurable por golpes (56/25/12) o interpolado.'],
                        ],
                    ],
                    [
                        'archivo' => 'formato_ge_suelos_h08.md',
                        'codigo' => 'CYCSA-RT-FM-22 H-8',
                        'nombre' => 'Gravedad Específica de Suelos (Picnómetro)',
                        'config' => [
                            'formato_codigo' => 'CYCSA-RT-FM-22 H-8',
                            'ensayo_titulo' => 'Gravedad Específica de Suelos por Picnómetro (ASTM D854-23)',
                            'metodo_muestreo' => 'DE-ASTM D854-23',
                            'orientacion' => 'horizontal',
                            'columns' => ['Código laboratorio', 'Nombre muestra', 'Picnómetro (N°)', 'Temperatura (°C)', 'Masa Suelo Seco (g)', 'Gravedad Específica (Gs)', 'Fecha de Ensayo'],
                            'column_methods' => ['Gravedad Específica (Gs)' => 'CYCSA-PE-14'],
                            'notas' => ['Gravedad específica de suelos determinada por picnómetro conforme a ASTM D854-23 (CYCSA-PE-14).'],
                        ],
                    ],
                    [
                        'archivo' => 'formato_doblado_acero_h20.md',
                        'codigo' => 'CYCSA-RT-FM-22 H-20',
                        'nombre' => 'Doblado de Barras de Refuerzo (Acero)',
                        'config' => [
                            'formato_codigo' => 'CYCSA-RT-FM-22 H-20',
                            'ensayo_titulo' => 'Ensayo de Doblado Simple de Barras de Acero de Refuerzo',
                            'metodo_muestreo' => 'DE-ASTM A615/A706',
                            'orientacion' => 'horizontal',
                            'columns' => ['Código laboratorio', 'Nombre muestra', 'Varilla (N°)', 'Diámetro Nominal (mm)', 'Ángulo de Doblado (°)', 'Diámetro de Mandril', 'Resultado (Cumple/Falla)', 'Fecha de Ensayo'],
                            'column_methods' => ['Resultado (Cumple/Falla)' => 'CYCSA-PE-38'],
                            'notas' => ['Ensayo de doblado simple conforme a CYCSA-PE-38 (ASTM A615/A706). Separado del ensayo de tensión (CYCSA-PE-61).'],
                        ],
                    ],
                    [
                        'archivo' => 'formato_tension_acero_h37.md',
                        'codigo' => 'CYCSA-RT-FM-22 H-37',
                        'nombre' => 'Tracción / Tensión en Varillas de Acero',
                        'config' => [
                            'formato_codigo' => 'CYCSA-RT-FM-22 H-37',
                            'ensayo_titulo' => 'Ensayo de Tracción en Varillas de Acero de Refuerzo (ASTM E8 / A370)',
                            'metodo_muestreo' => 'DE-ASTM E8/A370',
                            'orientacion' => 'horizontal',
                            'columns' => ['Código laboratorio', 'Nombre muestra', 'Varilla (N°)', 'Diámetro (mm)', 'Área (mm²)', 'Peso Unitario (kg/m)', 'Fluencia (MPa)', 'Fluencia (lbf/in²)', 'Tensión Máx. (lbf/in²)', 'Tensión Máx. (kg/cm²)', 'Elongación (%)', 'Fecha de Ensayo'],
                            'column_methods' => ['Fluencia (lbf/in²)' => 'CYCSA-PE-61', 'Tensión Máx. (lbf/in²)' => 'CYCSA-PE-61', 'Elongación (%)' => 'CYCSA-PE-61'],
                            'notas' => ['Ensayo de tracción en varillas de acero de refuerzo #3 a #11 conforme a CYCSA-PE-61 (ASTM E8/A370/A615).'],
                        ],
                    ],
                    [
                        'archivo' => 'formato_compresion_roca_h22.md',
                        'codigo' => 'CYCSA-RT-FM-22 H-22',
                        'nombre' => 'Compresión Uniaxial en Roca (ASTM D7012)',
                        'config' => [
                            'formato_codigo' => 'CYCSA-RT-FM-22 H-22',
                            'ensayo_titulo' => 'Resistencia a la Compresión Uniaxial en Núcleos de Roca (ASTM D7012-23)',
                            'metodo_muestreo' => 'DE-ASTM D7012-23',
                            'orientacion' => 'horizontal',
                            'columns' => ['Código laboratorio', 'Nombre muestra', 'Diámetro (cm)', 'Altura (cm)', 'Relación L/D', 'Área (cm²)', 'Carga Máx. (lb)', 'Resistencia (MPa)', 'Resistencia (kg/cm²)', 'Resistencia (lbf/in²)', 'Fecha de Ensayo'],
                            'column_methods' => ['Resistencia (MPa)' => 'CYCSA-PE-40', 'Resistencia (kg/cm²)' => 'CYCSA-PE-40'],
                            'notas' => ['Compresión uniaxial en núcleos de roca intactos conforme a CYCSA-PE-40 (ASTM D7012-23).'],
                        ],
                    ],
                    [
                        'archivo' => 'formato_sanidad_intemperismo_f.md',
                        'codigo' => 'CYCSA-RT-FM-22 F',
                        'nombre' => 'Sanidad / Intemperismo Acelerado de Agregados (ASTM C88)',
                        'config' => [
                            'formato_codigo' => 'CYCSA-RT-FM-22 F',
                            'ensayo_titulo' => 'Sanidad de Agregados por Intemperismo Acelerado (Sulfato de Sodio/Magnesio)',
                            'metodo_muestreo' => 'DE-ASTM C88/C88M-24',
                            'orientacion' => 'horizontal',
                            'columns' => ['Fracción', 'Retenido Original (g)', '% Gradación Original', 'Masa Inicial (g)', 'Masa Final (g)', 'Pérdida (g)', '% Pérdida Fracción', '% Pérdida Ponderada'],
                            'column_methods' => ['% Pérdida Ponderada' => 'CYCSA-PE-52'],
                            'notas' => ['Sanidad de agregados (intemperismo acelerado) conforme a CYCSA-PE-52 (ASTM C88/C88M-24).'],
                        ],
                    ],
                    [
                        'archivo' => 'formato_colorimetria_pe05.md',
                        'codigo' => 'CYCSA-RT-FM-22 G',
                        'nombre' => 'Impurezas Orgánicas en Agregado Fino (Colorimetría)',
                        'config' => [
                            'formato_codigo' => 'CYCSA-RT-FM-22 G',
                            'ensayo_titulo' => 'Determinación de Impurezas Orgánicas en Agregado Fino (ASTM C40)',
                            'metodo_muestreo' => 'DE-ASTM C40',
                            'orientacion' => 'vertical',
                            'columns' => ['Código laboratorio', 'Nombre muestra', 'Color (Placa Orgánica)', 'Resultado', 'Fecha de Ensayo'],
                            'column_methods' => ['Color (Placa Orgánica)' => 'CYCSA-PE-05'],
                            'notas' => ['Determinación colorimétrica de impurezas orgánicas conforme a CYCSA-PE-05 (ASTM C40).'],
                        ],
                    ],
                ];

                $stmtCheck = $db->prepare("SELECT id, configuracion_json FROM formatos_ensayos WHERE archivo_markdown = :arch LIMIT 1");
                $stmtIns = $tieneCodigo
                    ? $db->prepare("INSERT INTO formatos_ensayos (nombre, archivo_markdown, codigo_formato, configuracion_json) VALUES (:nombre, :arch, :cod, :cfg)")
                    : $db->prepare("INSERT INTO formatos_ensayos (nombre, archivo_markdown, configuracion_json) VALUES (:nombre, :arch, :cfg)");
                $stmtUpFirmante = $db->prepare("UPDATE formatos_ensayos SET configuracion_json = :cfg WHERE id = :id");

                foreach ($nuevosFormatos as $nf) {
                    $nf['config']['firmante_nombre'] = $nf['config']['firmante_nombre'] ?? 'Ing. Noel Quintana Lira';
                    $nf['config']['firmante_cargo'] = $nf['config']['firmante_cargo'] ?? 'Dirección Técnica';
                    $stmtCheck->execute(['arch' => $nf['archivo']]);
                    $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                    if (!$existente) {
                        $params = [
                            'nombre' => $nf['nombre'],
                            'arch' => $nf['archivo'],
                            'cfg' => json_encode($nf['config'], JSON_UNESCAPED_UNICODE),
                        ];
                        if ($tieneCodigo) {
                            $params['cod'] = $nf['codigo'];
                        }
                        $stmtIns->execute($params);
                    } else {
                        $cfgActual = json_decode((string)($existente['configuracion_json'] ?? '{}'), true);
                        if (is_array($cfgActual) && empty($cfgActual['firmante_nombre'])) {
                            $cfgActual['firmante_nombre'] = $nf['config']['firmante_nombre'];
                            $cfgActual['firmante_cargo'] = $cfgActual['firmante_cargo'] ?? $nf['config']['firmante_cargo'];
                            $stmtUpFirmante->execute([
                                'cfg' => json_encode($cfgActual, JSON_UNESCAPED_UNICODE),
                                'id' => (int)$existente['id'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}

if (isset($db) && $db instanceof PDO && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    ejecutarMigracion039($db);
    echo "[OK] Migración 039 aplicada exitosamente.\n";
}
