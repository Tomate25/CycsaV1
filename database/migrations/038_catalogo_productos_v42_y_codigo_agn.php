<?php

/**
 * Migración 038: Actualización del catálogo de productos desde Lista de Precios v4.2 (17/11/2026),
 * incorporación de columna `codigo_agn` y registro detallado en bitácora de carga masiva.
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
require_once dirname(__DIR__, 2) . '/app/Modulos/Productos/Modelos/ProductoModelo.php';

if (!function_exists('normalizarMatrizV42')) {
    function normalizarMatrizV42(?string $matriz): string {
        $m = trim((string)$matriz);
        if ($m === '') return 'Otros ensayos';
        $map = [
            'suelo' => 'Suelo',
            'concreto' => 'Concreto',
            'mortero' => 'Mortero',
        ];
        $lower = mb_strtolower($m, 'UTF-8');
        return $map[$lower] ?? $m;
    }
}

if (!function_exists('ejecutarMigracion038')) {
    function ejecutarMigracion038(PDO $db, bool $registrarHistorial = true): array {
        // 1. Asegurar columna codigo_agn en productos
        $colAgn = $db->query("SHOW COLUMNS FROM productos LIKE 'codigo_agn'")->fetch(PDO::FETCH_ASSOC);
        if (!$colAgn) {
            $db->exec("ALTER TABLE productos ADD COLUMN codigo_agn VARCHAR(100) NULL AFTER no_item");
        }
        $idxAgn = $db->query("SHOW INDEX FROM productos WHERE Key_name = 'idx_productos_codigo_agn'")->fetch(PDO::FETCH_ASSOC);
        if (!$idxAgn) {
            $db->exec("ALTER TABLE productos ADD INDEX idx_productos_codigo_agn (codigo_agn)");
        }

        // 2. Ampliar nombre_comercial (paquetes > 255 chars) y bitacora.datos_json a LONGTEXT
        $colNom = $db->query("SHOW COLUMNS FROM productos LIKE 'nombre_comercial'")->fetch(PDO::FETCH_ASSOC);
        if ($colNom && stripos((string)($colNom['Type'] ?? ''), 'varchar') !== false) {
            $db->exec("ALTER TABLE productos MODIFY COLUMN nombre_comercial TEXT NULL");
        }
        $colJson = $db->query("SHOW COLUMNS FROM bitacora LIKE 'datos_json'")->fetch(PDO::FETCH_ASSOC);
        if ($colJson && stripos((string)($colJson['Type'] ?? ''), 'longtext') === false) {
            $db->exec("ALTER TABLE bitacora MODIFY COLUMN datos_json LONGTEXT NULL");
        }

        $rutaJson = dirname(__DIR__) . '/data/catalogo_productos_v42.json';
        if (!is_file($rutaJson)) {
            throw new RuntimeException('No se encontró el catálogo JSON v4.2 en: ' . $rutaJson);
        }
        $itemsV42 = json_decode((string)file_get_contents($rutaJson), true);
        if (!is_array($itemsV42) || empty($itemsV42)) {
            throw new RuntimeException('El archivo catalogo_productos_v42.json está vacío o inválido.');
        }

        // Verificar formato 21 para nuevas varillas #9, #10 y #11
        $tieneFormato21 = (int)$db->query("SELECT COUNT(*) FROM formatos_ensayos WHERE id = 21")->fetchColumn() > 0;

        // Cargar estado actual de productos en memoria
        $existentesRaw = $db->query("SELECT * FROM productos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $existentesPorId = [];
        $existentesPorAgn = [];
        $existentesPorCodSrv = [];
        $existentesPorNombre = [];
        foreach ($existentesRaw as $fila) {
            $clean = \Cycsa\Modulos\Productos\Modelos\ProductoModelo::sanitizarFila($fila);
            $id = (int)$clean['id'];
            $existentesPorId[$id] = $clean;
            $agn = mb_strtoupper(trim((string)($clean['codigo_agn'] ?? '')), 'UTF-8');
            if ($agn !== '') $existentesPorAgn[$agn] = $id;
            $cs = mb_strtoupper(trim((string)($clean['codigo_servicio'] ?? '')), 'UTF-8');
            if ($cs !== '' && str_starts_with($cs, 'CYCSA-026')) {
                $existentesPorCodSrv[$cs] = $id;
            }
            $nom = mb_strtolower(trim((string)($clean['nombre_comercial'] ?? '')), 'UTF-8');
            if ($nom !== '') $existentesPorNombre[$nom] = $id;
        }

        $camposEtiquetas = [
            'no_item'                => 'No. Ítem',
            'codigo_agn'             => 'Código AGN',
            'codigo_servicio'        => 'Código Formato / Servicio',
            'nombre_comercial'       => 'Nombre Comercial',
            'ensayo_servicio'        => 'Ensayo / Servicio',
            'matriz_tipo'            => 'Matriz',
            'tipo_muestra'           => 'Tipo de Muestra',
            'tipo_muestreo'          => 'Tipo de Muestreo',
            'estatus'                => 'Estatus',
            'norma_astm'             => 'Norma ASTM',
            'procedimiento_muestreo' => 'Procedimiento',
            'codigo_hoja_campo'      => 'Hoja de Campo',
            'unidad_medida'          => 'Unidad',
            'precio'                 => 'Precio',
            'condiciones_muestra'    => 'Condiciones Muestra',
            'observaciones'          => 'Observaciones',
            'activo'                 => 'Estado Activo'
        ];

        $idsTocados = [];
        $agregados = [];
        $modificados = [];
        $sinCambios = 0;

        $stmtUpdate = $db->prepare("
            UPDATE productos SET
                no_item = :no_item,
                codigo_agn = :codigo_agn,
                tipo_muestra = :tipo_muestra,
                matriz_tipo = :matriz_tipo,
                tipo_muestreo = :tipo_muestreo,
                ensayo_servicio = :ensayo_servicio,
                nombre_comercial = :nombre_comercial,
                condiciones_muestra = :condiciones_muestra,
                codigo_servicio = :codigo_servicio,
                estatus = :estatus,
                norma_astm = :norma_astm,
                procedimiento_muestreo = :procedimiento_muestreo,
                codigo_hoja_campo = :codigo_hoja_campo,
                unidad_medida = :unidad_medida,
                precio = :precio,
                observaciones = :observaciones,
                activo = 1
            WHERE id = :id
        ");

        $stmtInsert = $db->prepare("
            INSERT INTO productos (
                no_item, codigo_agn, formato_id, tipo_muestra, matriz_tipo, tipo_muestreo,
                ensayo_servicio, nombre_comercial, condiciones_muestra, codigo_servicio,
                estatus, norma_astm, procedimiento_muestreo, codigo_hoja_campo,
                unidad_medida, precio, observaciones, activo
            ) VALUES (
                :no_item, :codigo_agn, :formato_id, :tipo_muestra, :matriz_tipo, :tipo_muestreo,
                :ensayo_servicio, :nombre_comercial, :condiciones_muestra, :codigo_servicio,
                :estatus, :norma_astm, :procedimiento_muestreo, :codigo_hoja_campo,
                :unidad_medida, :precio, :observaciones, 1
            )
        ");

        $enTransaccion = $db->inTransaction();
        if (!$enTransaccion) {
            $db->beginTransaction();
        }

        try {
            foreach ($itemsV42 as $item) {
                $noItem = (string)$item['no_item'];
                $noInt = (int)$noItem;
                $agn = trim((string)($item['codigo_agn'] ?? ''));
                $agnKey = mb_strtoupper($agn, 'UTF-8');
                $nombreNorm = normalizarMatrizV42($item['matriz_tipo'] ?? 'Otros ensayos');

                $datos = [
                    'no_item'                => $noItem,
                    'codigo_agn'             => $agn !== '' ? $agn : null,
                    'tipo_muestra'           => $item['tipo_muestra'] ?? 'N/A',
                    'matriz_tipo'            => $nombreNorm,
                    'tipo_muestreo'          => $item['tipo_muestreo'] ?? 'N/A',
                    'ensayo_servicio'        => $item['ensayo_servicio'] ?? '',
                    'nombre_comercial'       => $item['nombre_comercial'] ?? '',
                    'condiciones_muestra'    => $item['condiciones_muestra'] ?? null,
                    'codigo_servicio'        => $item['codigo_servicio'] ?? null,
                    'estatus'                => $item['estatus'] ?? 'No acreditado',
                    'norma_astm'             => $item['norma_astm'] ?? null,
                    'procedimiento_muestreo' => $item['procedimiento_muestreo'] ?? null,
                    'codigo_hoja_campo'      => $item['codigo_hoja_campo'] ?? 'N/A',
                    'unidad_medida'          => $item['unidad_medida'] ?? 'Unidad',
                    'precio'                 => round((float)($item['precio'] ?? 0), 2),
                    'observaciones'          => $item['observaciones'] ?? null,
                ];

                // Determinar registro existente a actualizar
                $idMatch = null;
                if ($agnKey !== '' && isset($existentesPorAgn[$agnKey])) {
                    $idMatch = $existentesPorAgn[$agnKey];
                } elseif ($agnKey !== '' && str_starts_with($agnKey, 'CYCSA-026') && isset($existentesPorCodSrv[$agnKey])) {
                    $idMatch = $existentesPorCodSrv[$agnKey];
                } elseif ($noInt >= 1 && $noInt <= 61 && isset($existentesPorId[$noInt]) && !isset($idsTocados[$noInt])) {
                    // Verificar que ID 1..61 no haya sido tomado por otro ítem y corresponda a ensayo (no movilización)
                    if (stripos((string)($existentesPorId[$noInt]['matriz_tipo'] ?? ''), 'moviliz') === false) {
                        $idMatch = $noInt;
                    }
                } elseif ($noInt === 73 && isset($existentesPorId[62]) && stripos((string)($existentesPorId[62]['nombre_comercial'] ?? ''), 'Granulom') !== false) {
                    $idMatch = 62;
                } else {
                    $nomKey = mb_strtolower(trim((string)$datos['nombre_comercial']), 'UTF-8');
                    if ($nomKey !== '' && isset($existentesPorNombre[$nomKey]) && !isset($idsTocados[$existentesPorNombre[$nomKey]])) {
                        $idMatch = $existentesPorNombre[$nomKey];
                    }
                }

                if ($idMatch !== null && isset($existentesPorId[$idMatch])) {
                    $prev = $existentesPorId[$idMatch];
                    $idsTocados[$idMatch] = true;

                    $cambios = [];
                    foreach ($camposEtiquetas as $campo => $etiqueta) {
                        if ($campo === 'activo') {
                            if ((int)($prev['activo'] ?? 1) !== 1) {
                                $cambios[] = ['campo' => 'activo', 'etiqueta' => $etiqueta, 'anterior' => 'Inactivo', 'nuevo' => 'Activo'];
                            }
                            continue;
                        }
                        if ($campo === 'precio') {
                            $pAnt = round((float)($prev['precio'] ?? 0), 2);
                            $pNue = $datos['precio'];
                            if (abs($pAnt - $pNue) > 0.001) {
                                $cambios[] = ['campo' => 'precio', 'etiqueta' => $etiqueta, 'anterior' => number_format($pAnt, 2, '.', ''), 'nuevo' => number_format($pNue, 2, '.', '')];
                            }
                            continue;
                        }
                        $vAnt = trim((string)($prev[$campo] ?? ''));
                        $vNue = trim((string)($datos[$campo] ?? ''));
                        if ($vAnt !== $vNue) {
                            $cambios[] = ['campo' => $campo, 'etiqueta' => $etiqueta, 'anterior' => $vAnt, 'nuevo' => $vNue];
                        }
                    }

                    // Siempre actualizamos filas UTF-8 limpias si hubo cambio o si el raw difiere del sanitizado
                    $rawPrev = $existentesRaw[array_search($idMatch, array_column($existentesRaw, 'id'))] ?? [];
                    $necesitaLimpiezaMojibake = false;
                    foreach ($datos as $k => $v) {
                        if ($k !== 'precio' && array_key_exists($k, $rawPrev) && trim((string)$rawPrev[$k]) !== trim((string)$v)) {
                            $necesitaLimpiezaMojibake = true;
                            break;
                        }
                    }

                    if (!empty($cambios) || $necesitaLimpiezaMojibake || (int)($prev['activo'] ?? 1) !== 1) {
                        $stmtUpdate->execute(array_merge($datos, ['id' => $idMatch]));
                    }

                    if (!empty($cambios)) {
                        $modificados[] = [
                            'id' => $idMatch,
                            'no_item' => $datos['no_item'],
                            'codigo_agn' => $datos['codigo_agn'] ?? '',
                            'nombre_comercial' => $datos['nombre_comercial'],
                            'cambios' => $cambios
                        ];
                    } else {
                        $sinCambios++;
                    }
                } else {
                    $formatoIdNuevo = ($noInt >= 62 && $noInt <= 64 && $tieneFormato21) ? 21 : null;
                    $stmtInsert->execute(array_merge($datos, ['formato_id' => $formatoIdNuevo]));
                    $nuevoId = (int)$db->lastInsertId();
                    $idsTocados[$nuevoId] = true;
                    $agregados[] = [
                        'id' => $nuevoId,
                        'no_item' => $datos['no_item'],
                        'codigo_agn' => $datos['codigo_agn'] ?? '',
                        'codigo_servicio' => $datos['codigo_servicio'] ?? '',
                        'nombre_comercial' => $datos['nombre_comercial'],
                        'matriz_tipo' => $datos['matriz_tipo'],
                        'precio' => $datos['precio'],
                        'estatus' => $datos['estatus']
                    ];
                }
            }

            // 3. Desactivar (soft-delete) ítems antiguos ausentes en v4.2 (Peñas Blancas, Corinto y categorías genéricas C$0.00)
            $desactivados = [];
            $stmtDeact = $db->prepare("UPDATE productos SET activo = 0 WHERE id = :id");
            foreach ($existentesPorId as $idExist => $prev) {
                if (isset($idsTocados[$idExist])) {
                    continue;
                }
                if ((int)($prev['activo'] ?? 1) !== 1) {
                    continue;
                }
                $codPrev = trim((string)($prev['codigo_servicio'] ?? ''));
                $precioPrev = round((float)($prev['precio'] ?? 0), 2);
                $esLegacyAusente = in_array($codPrev, ['CYCSA-026-39', 'CYCSA-026-41'], true) || ($precioPrev <= 0.001 && $codPrev === '');
                if ($esLegacyAusente) {
                    $stmtDeact->execute(['id' => $idExist]);
                    $desactivados[] = [
                        'id' => $idExist,
                        'no_item' => (string)($prev['no_item'] ?? ''),
                        'codigo_servicio' => $codPrev,
                        'nombre_comercial' => (string)($prev['nombre_comercial'] ?? ''),
                        'precio' => $precioPrev,
                        'motivo' => 'Ausente en Lista de Precios v4.2 (17/11/2026)'
                    ];
                }
            }

            // 4. Registrar historial detallado en bitácora si hubo cambios y aún no existe un registro idéntico de esta migración
            $totalEventos = count($agregados) + count($modificados) + count($desactivados);
            if ($registrarHistorial && $totalEventos > 0) {
                $yaRegistrado = (int)$db->query("
                    SELECT COUNT(*) FROM bitacora
                    WHERE modulo = 'productos'
                      AND accion = 'importacion_masiva'
                      AND descripcion LIKE '%CYCSA RG-LI-05%v4.2%'
                ")->fetchColumn() > 0;

                if (!$yaRegistrado) {
                    $archivoNombre = 'CYCSA RG-LI-05 LISTA DE CLASIFICACIÓN Y PRECIOS DE LOS ENSAYOS DE CYCSA 17112026.xlsx';
                    $desc = sprintf(
                        "Carga masiva desde '%s' (LISTA DE PRECIOS v4.2): %d agregado(s), %d modificado(s), %d sin cambios, %d desactivado(s) (%d ítems totales).",
                        $archivoNombre,
                        count($agregados),
                        count($modificados),
                        $sinCambios,
                        count($desactivados),
                        count($itemsV42)
                    );
                    $payloadJson = json_encode([
                        'archivo' => $archivoNombre,
                        'hoja' => 'LISTA DE PRECIOS 4.2 ',
                        'totales' => [
                            'total' => count($itemsV42),
                            'creados' => count($agregados),
                            'actualizados' => count($modificados),
                            'sin_cambios' => $sinCambios,
                            'desactivados' => count($desactivados)
                        ],
                        'agregados' => $agregados,
                        'modificados' => $modificados,
                        'desactivados' => $desactivados
                    ], JSON_UNESCAPED_UNICODE);

                    $stmtBit = $db->prepare("
                        INSERT INTO bitacora (
                            id_usuario, usuario_nombre, modulo, accion, descripcion, id_referencia, ip,
                            entidad_tipo, entidad_id, id_os, usuario_rol, datos_json
                        ) VALUES (
                            NULL, 'Sistema / Migración v4.2', 'productos', 'importacion_masiva', :descripcion, NULL, '127.0.0.1',
                            'carga_masiva_productos', NULL, NULL, 'admin', :datos_json
                        )
                    ");
                    $stmtBit->execute([
                        'descripcion' => $desc,
                        'datos_json' => $payloadJson
                    ]);
                }
            }

            if (!$enTransaccion) {
                $db->commit();
            }

            return [
                'total' => count($itemsV42),
                'agregados' => count($agregados),
                'modificados' => count($modificados),
                'sin_cambios' => $sinCambios,
                'desactivados' => count($desactivados)
            ];
        } catch (Throwable $e) {
            if (!$enTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

if (PHP_SAPI === 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    try {
        $pdo = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $res = ejecutarMigracion038($pdo, true);
        echo "[OK] Migración 038 ejecutada: " . json_encode($res, JSON_UNESCAPED_UNICODE) . "\n";
    } catch (Throwable $e) {
        echo "[ERROR] Migración 038 falló: " . $e->getMessage() . "\n";
        exit(1);
    }
}
