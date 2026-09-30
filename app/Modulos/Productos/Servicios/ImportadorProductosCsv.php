<?php

namespace Cycsa\Modulos\Productos\Servicios;

use Cycsa\Modulos\Productos\Modelos\ProductoModelo;

final class ImportadorProductosCsv {

    private const BOM_UTF8 = "\xEF\xBB\xBF";

    /**
     * Encabezados canónicos de la plantilla y exportación oficial.
     */
    public const ENCABEZADOS = [
        'ID',
        'No_Item',
        'Codigo_Servicio',
        'Nombre_Comercial',
        'Ensayo_Servicio',
        'Matriz_Tipo',
        'Tipo_Muestra',
        'Tipo_Muestreo',
        'Estatus',
        'Norma_ASTM',
        'Procedimiento_Muestreo',
        'Codigo_Hoja_Campo',
        'Unidad_Medida',
        'Precio',
        'Condiciones_Muestra',
        'Observaciones',
        'Formato_Codigo'
    ];

    /**
     * Genera el contenido de la plantilla oficial descargable en formato CSV UTF-8.
     * Usa punto y coma (;) por defecto para compatibilidad directa con Microsoft Excel en español.
     */
    public static function generarPlantillaCsv(string $delimitador = ';'): string {
        $flujo = fopen('php://temp', 'w+b');
        if ($flujo === false) {
            throw new \RuntimeException('No fue posible inicializar el flujo para la plantilla.');
        }

        fwrite($flujo, self::BOM_UTF8);
        fwrite($flujo, "sep={$delimitador}\r\n");
        fputcsv($flujo, self::ENCABEZADOS, $delimitador, '"', '\\');

        $limpiar = static function(?string $valor): string {
            if ($valor === null) return '';
            $v = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $valor);
            return trim((string)preg_replace('/[ ]{2,}/', ' ', $v));
        };

        // Filas de ejemplo reales basadas en el catálogo de CYCSA (con y sin ID)
        $ejemplos = [
            [
                '1',
                '1',
                'CYCSA-RT-FM-22 G',
                'Humedad en agregados, ASTM C566-25',
                'CYCSA-PE-01-Determinación de Humedad en muestras de Agregados.',
                'Agregados',
                'Agregados',
                'Aleatorio / Puntual',
                'Acreditado',
                'ASTM C566-25',
                'CYCSA-PE-01',
                'N/A',
                'Unidad',
                '900.00',
                'Bolsas o recipientes herméticos que mantengan la humedad.',
                '(2 días hábiles)',
                'CYCSA-RT-FM-22 G'
            ],
            [
                '29',
                '29',
                'CYCSA-RT-FM-22 B',
                'Densidad y Humedad In Situ (Densímetro Nuclear) – ASTM D6938-23',
                '*CYCSA-PE-25-Metodo estándar para la determinación de densidad in situ y contenido de agua',
                'Suelo',
                'Suelo',
                'Aleatorio / Puntual',
                'Acreditado',
                'ASTM D6938-23',
                'CYCSA-PE-25',
                'CYCSA-RT-FM-05',
                'Unidad',
                '1300.00',
                'Terreno compactado libre de saturación de humedad, tiempo despejado.',
                '(1 día una vez recibida hoja de campo en laboratorio)',
                'CYCSA-RT-FM-22 B'
            ],
            [
                '',
                '63',
                'CYCSA-026-1',
                'Movilización Managua Urbano',
                'Movilización Managua Urbano',
                'Movilización',
                'N/A',
                'N/A',
                'No acreditado',
                'N/A',
                'N/A',
                'N/A',
                'Viaje',
                '2300.00',
                'N/A',
                'A convenir',
                ''
            ]
        ];

        foreach ($ejemplos as $fila) {
            $filaLimpia = array_map($limpiar, $fila);
            fputcsv($flujo, $filaLimpia, $delimitador, '"', '\\');
        }

        rewind($flujo);
        $contenido = stream_get_contents($flujo);
        fclose($flujo);

        return preg_replace('/(?<!\r)\n/', "\r\n", (string)$contenido);
    }

    /**
     * Exporta el catálogo completo de productos con sus IDs e identificadores
     * en formato CSV UTF-8 con BOM y punto y coma (;) para visualización perfecta en Excel
     * (sin saltos de línea internos en celdas, 1 producto por fila exacta).
     */
    public static function generarCatalogoCompletoCsv(ProductoModelo $modelo, string $delimitador = ';'): string {
        $flujo = fopen('php://temp', 'w+b');
        if ($flujo === false) {
            throw new \RuntimeException('No fue posible inicializar el flujo para la exportación.');
        }

        $limpiar = static function(?string $valor): string {
            if ($valor === null) return '';
            $v = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $valor);
            return trim((string)preg_replace('/[ ]{2,}/', ' ', $v));
        };

        fwrite($flujo, self::BOM_UTF8);
        fwrite($flujo, "sep={$delimitador}\r\n");
        fputcsv($flujo, self::ENCABEZADOS, $delimitador, '"', '\\');

        $productos = $modelo->obtenerTodos('', '', 1);

        foreach ($productos as $p) {
            $fila = [
                (string)$p['id'],
                $limpiar($p['no_item'] ?? ''),
                $limpiar($p['codigo_servicio'] ?? ''),
                $limpiar($p['nombre_comercial'] ?? ''),
                $limpiar($p['ensayo_servicio'] ?? ''),
                $limpiar($p['matriz_tipo'] ?? ''),
                $limpiar($p['tipo_muestra'] ?? ''),
                $limpiar($p['tipo_muestreo'] ?? ''),
                $limpiar($p['estatus'] ?? 'No acreditado'),
                $limpiar($p['norma_astm'] ?? ''),
                $limpiar($p['procedimiento_muestreo'] ?? ''),
                $limpiar($p['codigo_hoja_campo'] ?? ''),
                $limpiar($p['unidad_medida'] ?? 'Unidad'),
                number_format((float)($p['precio'] ?? 0), 2, '.', ''),
                $limpiar($p['condiciones_muestra'] ?? ''),
                $limpiar($p['observaciones'] ?? ''),
                $limpiar($p['formato_reporte'] ?? '')
            ];
            fputcsv($flujo, $fila, $delimitador, '"', '\\');
        }

        rewind($flujo);
        $contenido = stream_get_contents($flujo);
        fclose($flujo);

        return preg_replace('/(?<!\r)\n/', "\r\n", (string)$contenido);
    }

    /**
     * Parsea y valida el contenido de un archivo CSV para previsualización.
     * Detecta automáticamente delimitador (, o ;) y codificación.
     */
    public static function parsearYValidar(string $contenidoCsv, ProductoModelo $modelo): array {
        // Remover BOM si viene presente
        if (str_starts_with($contenidoCsv, self::BOM_UTF8)) {
            $contenidoCsv = substr($contenidoCsv, 3);
        }

        // Si no es UTF-8 válido, intentar convertir desde ISO-8859-1 / Windows-1252
        if (!mb_check_encoding($contenidoCsv, 'UTF-8')) {
            $contenidoCsv = mb_convert_encoding($contenidoCsv, 'UTF-8', 'ISO-8859-1');
        }

        // Detectar delimitador (coma o punto y coma)
        $lineas = preg_split('/\r\n|\r|\n/', trim($contenidoCsv));
        if (empty($lineas)) {
            return [
                'exito' => false,
                'error' => 'El archivo CSV está vacío.',
                'total_filas' => 0,
                'validas' => 0,
                'errores' => 0,
                'nuevos' => 0,
                'actualizaciones' => 0,
                'filas' => []
            ];
        }

        $primeraLinea = $lineas[0];
        if (preg_match('/^sep=([;,])/i', trim($primeraLinea), $mSep)) {
            $delimitador = $mSep[1];
            array_shift($lineas);
            $contenidoCsv = implode("\r\n", $lineas);
        } else {
            $delimitador = (substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',')) ? ';' : ',';
        }

        $flujo = fopen('php://temp', 'w+b');
        fwrite($flujo, $contenidoCsv);
        rewind($flujo);

        // 1. Leer encabezados
        $rawHeaders = fgetcsv($flujo, 0, $delimitador, '"', '\\');
        if ($rawHeaders === false || empty($rawHeaders)) {
            fclose($flujo);
            return [
                'exito' => false,
                'error' => 'No se pudieron leer los encabezados del archivo CSV.',
                'total_filas' => 0,
                'validas' => 0,
                'errores' => 0,
                'nuevos' => 0,
                'actualizaciones' => 0,
                'filas' => []
            ];
        }

        $mapaColumnas = self::normalizarEncabezados($rawHeaders);

        // Cargar índices en memoria para cotejo O(1)
        $indices = $modelo->obtenerIndiceMapeo();
        $formatosMapeo = $modelo->obtenerMapeoFormatos();

        $filasProcesadas = [];
        $clavesLote = [];
        $totalFilas = 0;
        $validas = 0;
        $erroresCount = 0;
        $nuevosCount = 0;
        $actualizacionesCount = 0;
        $sinCambiosCount = 0;
        $numeroFilaFisica = 1; // Fila 1 = Encabezados

        while (($datosFila = fgetcsv($flujo, 0, $delimitador, '"', '\\')) !== false) {
            $numeroFilaFisica++;

            // Omitir filas enteramente vacías
            $filaVacia = true;
            foreach ($datosFila as $val) {
                if (trim((string)$val) !== '') {
                    $filaVacia = false;
                    break;
                }
            }
            if ($filaVacia) {
                continue;
            }

            $totalFilas++;
            $filaExtraida = self::extraerCamposFila($datosFila, $mapaColumnas);
            $evaluacion = self::evaluarFila($filaExtraida, $indices, $formatosMapeo, $numeroFilaFisica);

            if ($evaluacion['es_valida']) {
                $claveLote = $evaluacion['producto_existente_id']
                    ? 'id:' . $evaluacion['producto_existente_id']
                    : (!empty($evaluacion['datos']['no_item'])
                        ? 'no:' . mb_strtolower((string)$evaluacion['datos']['no_item'], 'UTF-8')
                        : 'nombre:' . mb_strtolower((string)$evaluacion['datos']['nombre_comercial'], 'UTF-8'));

                if (isset($clavesLote[$claveLote])) {
                    $evaluacion['es_valida'] = false;
                    $evaluacion['errores'][] = 'El producto está repetido dentro del archivo (primera aparición en la fila ' . $clavesLote[$claveLote] . ').';
                } else {
                    $clavesLote[$claveLote] = $numeroFilaFisica;
                }
            }

            if ($evaluacion['es_valida']) {
                $validas++;
                if ($evaluacion['accion'] === 'crear') {
                    $nuevosCount++;
                } elseif ($evaluacion['accion'] === 'actualizar') {
                    $actualizacionesCount++;
                } else {
                    $sinCambiosCount++;
                }
            } else {
                $erroresCount++;
            }

            $filasProcesadas[] = $evaluacion;
        }

        fclose($flujo);

        return [
            'exito'           => true,
            'total_filas'     => $totalFilas,
            'validas'         => $validas,
            'errores'         => $erroresCount,
            'nuevos'          => $nuevosCount,
            'actualizaciones' => $actualizacionesCount,
            'modificados'     => $actualizacionesCount,
            'sin_cambios'     => $sinCambiosCount,
            'filas'           => $filasProcesadas
        ];
    }

    /**
     * Ejecuta transaccionalmente la creación y actualización de productos.
     */
    public static function ejecutarImportacion(array $filasValidadas, ProductoModelo $modelo): array {
        if (empty($filasValidadas)) {
            return [
                'exito' => false,
                'error' => 'No hay filas válidas para procesar.',
                'creados' => 0,
                'actualizados' => 0,
                'modificados' => 0,
                'sin_cambios' => 0,
                'total' => 0
            ];
        }

        $modelo->iniciarTransaccion();
        $creados = 0;
        $actualizados = 0;
        $sinCambios = 0;
        $errores = [];

        try {
            foreach ($filasValidadas as $idx => $item) {
                // Solo procesar filas que hayan sido marcadas como válidas
                if (empty($item['es_valida'])) {
                    continue;
                }

                $datos = $item['datos'];
                $accion = $item['accion'];
                $idProducto = !empty($item['producto_existente_id']) ? (int)$item['producto_existente_id'] : 0;

                if ($accion === 'actualizar' && $idProducto > 0) {
                    $ok = $modelo->actualizar($idProducto, $datos);
                    if ($ok) {
                        $actualizados++;
                    } else {
                        $errores[] = "Error al actualizar producto ID {$idProducto} (Fila {$item['fila_excel']})";
                    }
                } elseif ($accion === 'sin_cambios') {
                    $sinCambios++;
                } elseif ($accion === 'crear') {
                    $nuevoId = $modelo->guardarYRetornarId($datos);
                    if ($nuevoId > 0) {
                        $creados++;
                    } else {
                        $errores[] = "Error al crear producto '{$datos['nombre_comercial']}' (Fila {$item['fila_excel']})";
                    }
                }
            }

            if (!empty($errores)) {
                $modelo->revertirTransaccion();
                return [
                    'exito' => false,
                    'error' => 'Se presentaron errores durante la transacción: ' . implode('; ', $errores),
                    'creados' => 0,
                    'actualizados' => 0,
                    'modificados' => 0,
                    'sin_cambios' => 0,
                    'total' => 0
                ];
            }

            $modelo->confirmarTransaccion();

            return [
                'exito' => true,
                'creados' => $creados,
                'actualizados' => $actualizados,
                'modificados' => $actualizados,
                'sin_cambios' => $sinCambios,
                'total' => $creados + $actualizados + $sinCambios
            ];

        } catch (\Throwable $e) {
            $modelo->revertirTransaccion();
            return [
                'exito' => false,
                'error' => 'Excepción durante la importación: ' . $e->getMessage(),
                'creados' => 0,
                'actualizados' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Mapea encabezados a nombres de campos canónicos de forma insensible a mayúsculas y acentos.
     */
    private static function normalizarEncabezados(array $headers): array {
        $mapa = [];
        foreach ($headers as $idx => $header) {
            $h = mb_strtolower(trim((string)$header), 'UTF-8');
            $h = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ', ' ', '.', '-', '/'], ['a', 'e', 'i', 'o', 'u', 'n', '_', '', '_', '_'], $h);
            $h = preg_replace('/_+/', '_', $h);
            $h = trim($h, '_');

            if (in_array($h, ['no_item', 'no', 'item', 'numero_item', 'n_item'])) {
                $mapa['no_item'] = $idx;
            } elseif (in_array($h, ['codigo_servicio', 'codigo', 'cod_servicio', 'codigo_ensayo'])) {
                $mapa['codigo_servicio'] = $idx;
            } elseif (in_array($h, ['nombre_comercial', 'nombre', 'producto', 'servicio_nombre', 'descripcion_comercial'])) {
                $mapa['nombre_comercial'] = $idx;
            } elseif (in_array($h, ['ensayo_servicio', 'ensayo', 'servicio', 'metodo', 'procedimiento_oficial'])) {
                $mapa['ensayo_servicio'] = $idx;
            } elseif (in_array($h, ['matriz_tipo', 'matriz', 'categoria', 'tipo_matriz'])) {
                $mapa['matriz_tipo'] = $idx;
            } elseif (in_array($h, ['tipo_muestra', 'muestra_tipo', 'muestra'])) {
                $mapa['tipo_muestra'] = $idx;
            } elseif (in_array($h, ['tipo_muestreo', 'muestreo_tipo', 'muestreo'])) {
                $mapa['tipo_muestreo'] = $idx;
            } elseif (in_array($h, ['estatus', 'acreditacion', 'estado_acreditacion', 'acreditado'])) {
                $mapa['estatus'] = $idx;
            } elseif (in_array($h, ['norma_astm', 'norma', 'normativa', 'astm', 'aashto'])) {
                $mapa['norma_astm'] = $idx;
            } elseif (in_array($h, ['procedimiento_muestreo', 'procedimiento', 'pe'])) {
                $mapa['procedimiento_muestreo'] = $idx;
            } elseif (in_array($h, ['codigo_hoja_campo', 'hoja_campo', 'hoja_solicitud', 'formato_campo'])) {
                $mapa['codigo_hoja_campo'] = $idx;
            } elseif (in_array($h, ['unidad_medida', 'unidad', 'medida', 'um'])) {
                $mapa['unidad_medida'] = $idx;
            } elseif (in_array($h, ['precio', 'costo', 'valor', 'precio_oficial', 'tarifa'])) {
                $mapa['precio'] = $idx;
            } elseif (in_array($h, ['condiciones_muestra', 'condiciones', 'requisitos_muestra'])) {
                $mapa['condiciones_muestra'] = $idx;
            } elseif (in_array($h, ['observaciones', 'observacion', 'tiempo_entrega', 'plazo'])) {
                $mapa['observaciones'] = $idx;
            } elseif (in_array($h, ['formato_codigo', 'formato', 'formato_id', 'codigo_formato', 'plantilla'])) {
                $mapa['formato_codigo'] = $idx;
            } elseif (in_array($h, ['id', 'id_producto'])) {
                $mapa['id'] = $idx;
            }
        }
        return $mapa;
    }

    /**
     * Extrae los valores de una fila según el mapa de columnas.
     */
    private static function extraerCamposFila(array $datosFila, array $mapa): array {
        $get = function(string $clave, string $default = '') use ($datosFila, $mapa): string {
            if (isset($mapa[$clave]) && isset($datosFila[$mapa[$clave]])) {
                return trim((string)$datosFila[$mapa[$clave]]);
            }
            return $default;
        };
        $camposPresentes = array_keys($mapa);

        return [
            'id'                     => $get('id', ''),
            'no_item'                => $get('no_item', ''),
            'codigo_servicio'        => $get('codigo_servicio', ''),
            'nombre_comercial'       => $get('nombre_comercial', ''),
            'ensayo_servicio'        => $get('ensayo_servicio', ''),
            'matriz_tipo'            => $get('matriz_tipo', 'Otros ensayos'),
            'tipo_muestra'           => $get('tipo_muestra', 'N/A'),
            'tipo_muestreo'          => $get('tipo_muestreo', 'Aleatorio / Puntual'),
            'estatus'                => $get('estatus', 'No acreditado'),
            'norma_astm'             => $get('norma_astm', ''),
            'procedimiento_muestreo' => $get('procedimiento_muestreo', ''),
            'codigo_hoja_campo'      => $get('codigo_hoja_campo', 'NA'),
            'unidad_medida'          => $get('unidad_medida', 'Unidad'),
            'precio'                 => $get('precio', '0.00'),
            'condiciones_muestra'    => $get('condiciones_muestra', ''),
            'observaciones'          => $get('observaciones', ''),
            'formato_codigo'         => $get('formato_codigo', ''),
            '_campos_presentes'      => $camposPresentes
        ];
    }

    /**
     * Evalúa una fila extraída, determina upsert, valida reglas y calcula diferencias.
     */
    private static function evaluarFila(array $fila, array $indices, array $formatosMapeo, int $filaExcel): array {
        $errores = [];

        $camposPresentes = $fila['_campos_presentes'] ?? [];
        $estaPresente = static fn(string $campo): bool => in_array($campo, $camposPresentes, true);

        // 1. Detectar Upsert (Existente vs Nuevo) antes de completar columnas omitidas.
        $nombreComercial = trim($fila['nombre_comercial']);
        $ensayoServicio = trim($fila['ensayo_servicio']);

        $productoExistente = null;
        $idDirecto = (int)$fila['id'];
        if ($idDirecto > 0 && isset($indices['por_id'][$idDirecto])) {
            $productoExistente = $indices['por_id'][$idDirecto];
        } elseif (!empty($fila['no_item']) && isset($indices['por_no_item'][trim($fila['no_item'])])) {
            $productoExistente = $indices['por_no_item'][trim($fila['no_item'])];
        } elseif ($nombreComercial !== '') {
            $claveNom = mb_strtolower($nombreComercial, 'UTF-8');
            if (isset($indices['por_nombre'][$claveNom])) {
                $productoExistente = $indices['por_nombre'][$claveNom];
            }
        }

        if ($nombreComercial === '' && $ensayoServicio === '') {
            if ($productoExistente && !$estaPresente('nombre_comercial') && !$estaPresente('ensayo_servicio')) {
                $nombreComercial = (string)($productoExistente['nombre_comercial'] ?? '');
                $ensayoServicio = (string)($productoExistente['ensayo_servicio'] ?? $nombreComercial);
            } else {
                $errores[] = 'El nombre comercial o la descripción del ensayo es obligatorio.';
            }
        } elseif ($nombreComercial === '') {
            $nombreComercial = $productoExistente && !$estaPresente('nombre_comercial')
                ? (string)($productoExistente['nombre_comercial'] ?? $ensayoServicio)
                : $ensayoServicio;
        } elseif ($ensayoServicio === '') {
            $ensayoServicio = $productoExistente && !$estaPresente('ensayo_servicio')
                ? (string)($productoExistente['ensayo_servicio'] ?? $nombreComercial)
                : $nombreComercial;
        }

        // 2. Normalizar y validar precio
        $precioRaw = trim($fila['precio']);
        if (!$estaPresente('precio') && $productoExistente) {
            $precioFinal = (float)($productoExistente['precio'] ?? 0);
        } else {
            $precioNormalizado = self::limpiarPrecio($precioRaw);
            if ($precioNormalizado === null || $precioNormalizado < 0) {
                $errores[] = "Precio no válido ('{$precioRaw}'). Debe ser un valor numérico mayor o igual a 0.";
                $precioFinal = 0.00;
            } else {
                $precioFinal = $precioNormalizado;
            }
        }

        // 3. Normalizar estatus
        $estatus = trim($fila['estatus']);
        if (!$estaPresente('estatus') && $productoExistente) {
            $estatusFinal = (string)($productoExistente['estatus'] ?? 'No acreditado');
        } elseif (mb_stripos($estatus, 'acredit') !== false && mb_stripos($estatus, 'no') === false) {
            $estatusFinal = 'Acreditado';
        } else {
            $estatusFinal = 'No acreditado';
        }

        // 4. Resolver formato_id si se especificó o preservar el existente
        $formatoId = null;
        $formatoCodigo = trim($fila['formato_codigo']);
        if ($formatoCodigo !== '') {
            $claveUpper = mb_strtoupper($formatoCodigo, 'UTF-8');
            $claveLower = mb_strtolower($formatoCodigo, 'UTF-8');
            if (isset($formatosMapeo[$claveUpper])) {
                $formatoId = $formatosMapeo[$claveUpper];
            } elseif (isset($formatosMapeo[$claveLower])) {
                $formatoId = $formatosMapeo[$claveLower];
            }
        } elseif ($productoExistente && !empty($productoExistente['formato_id'])) {
            $formatoId = (int)$productoExistente['formato_id'];
        }

        $valor = static function(string $campo, mixed $predeterminado = null) use ($fila, $productoExistente, $estaPresente): mixed {
            if (!$estaPresente($campo) && $productoExistente) {
                return $productoExistente[$campo] ?? $predeterminado;
            }
            $importado = trim((string)($fila[$campo] ?? ''));
            return $importado !== '' ? $importado : $predeterminado;
        };

        $datosLimpios = [
            'no_item'                => !empty($fila['no_item']) ? trim($fila['no_item']) : ($productoExistente['no_item'] ?? null),
            'formato_id'             => $formatoId,
            'tipo_muestra'           => $valor('tipo_muestra', 'N/A'),
            'matriz_tipo'            => $valor('matriz_tipo', 'Otros ensayos'),
            'tipo_muestreo'          => $valor('tipo_muestreo', 'Aleatorio / Puntual'),
            'ensayo_servicio'        => $ensayoServicio,
            'nombre_comercial'       => $nombreComercial,
            'condiciones_muestra'    => $valor('condiciones_muestra', null),
            'codigo_servicio'        => $valor('codigo_servicio', null),
            'estatus'                => $estatusFinal,
            'norma_astm'             => $valor('norma_astm', null),
            'procedimiento_muestreo' => $valor('procedimiento_muestreo', null),
            'codigo_hoja_campo'      => $valor('codigo_hoja_campo', 'NA'),
            'unidad_medida'          => $valor('unidad_medida', 'Unidad'),
            'precio'                 => $precioFinal,
            'observaciones'          => $valor('observaciones', null),
            'activo'                 => 1
        ];

        $diferencias = [];
        $accion = 'crear';

        if ($productoExistente) {
            // Comparar precio
            $precioAnterior = (float)$productoExistente['precio'];
            if (abs($precioAnterior - $precioFinal) > 0.001) {
                $diferencias['precio'] = [
                    'etiqueta' => 'Precio',
                    'anterior' => $precioAnterior,
                    'nuevo'    => $precioFinal
                ];
            }

            // Comparar estatus
            if ((string)$productoExistente['estatus'] !== (string)$estatusFinal) {
                $diferencias['estatus'] = [
                    'etiqueta' => 'Estatus',
                    'anterior' => $productoExistente['estatus'],
                    'nuevo'    => $estatusFinal
                ];
            }

            // Comparar campos técnicos y descriptivos
            $camposComparar = [
                'no_item'                => 'No. Ítem',
                'codigo_servicio'        => 'Código Servicio',
                'nombre_comercial'       => 'Nombre Comercial',
                'ensayo_servicio'        => 'Ensayo/Servicio',
                'matriz_tipo'            => 'Matriz',
                'norma_astm'             => 'Norma ASTM',
                'unidad_medida'          => 'Unidad de Medida',
                'procedimiento_muestreo' => 'Procedimiento',
                'condiciones_muestra'    => 'Condiciones Muestra',
                'observaciones'          => 'Observaciones'
            ];

            foreach ($camposComparar as $campoKey => $campoEtiqueta) {
                $valAnterior = trim((string)($productoExistente[$campoKey] ?? ''));
                $valNuevo = trim((string)($datosLimpios[$campoKey] ?? ''));
                if ($valAnterior !== $valNuevo) {
                    $diferencias[$campoKey] = [
                        'etiqueta' => $campoEtiqueta,
                        'anterior' => $valAnterior,
                        'nuevo'    => $valNuevo
                    ];
                }
            }

            $accion = !empty($diferencias) ? 'actualizar' : 'sin_cambios';
        }

        return [
            'fila_excel'             => $filaExcel,
            'es_valida'              => empty($errores),
            'accion'                 => $accion,
            'producto_existente_id'  => $productoExistente ? (int)$productoExistente['id'] : null,
            'producto_existente_nom' => $productoExistente ? $productoExistente['nombre_comercial'] : null,
            'diferencias'            => $diferencias,
            'errores'                => $errores,
            'datos'                  => $datosLimpios
        ];
    }

    /**
     * Limpia y normaliza cadenas de moneda o números a float ('C$ 1,250.50' => 1250.50).
     */
    private static function limpiarPrecio(string $valor): ?float {
        $v = trim($valor);
        if ($v === '') return 0.00;

        // Quitar símbolos de moneda y espacios
        $v = str_ireplace(['c$', '$', 'usd', 'nio', ' '], '', $v);

        // Si termina en coma con 1 o 2 decimales (ej: 1.500,50 o 1500,50 o 25,5), tratar coma como decimal
        if (preg_match('/,\d{1,2}$/', $v)) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            // Formato estándar estadounidense (ej: 1,500.50 o 1500.50) -> quitar comas de miles
            $v = str_replace(',', '', $v);
        }

        if (is_numeric($v)) {
            return round((float)$v, 2);
        }
        return null;
    }
}
