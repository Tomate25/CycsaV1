<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Cycsa\Nucleo\Aplicacion();
try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    
    // Clear data from database first to avoid duplicate primary key errors on retry
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE ensayo_edades");
    $db->exec("TRUNCATE TABLE lotes_muestras");
    $db->exec("TRUNCATE TABLE recepcion_muestras");
    $db->exec("TRUNCATE TABLE ordenes_servicio");
    $db->exec("TRUNCATE TABLE cotizacion_detalles");
    $db->exec("TRUNCATE TABLE cotizaciones");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    $db->beginTransaction();

    // 1. Get first client
    $client = $db->query("SELECT * FROM clientes ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$client) {
        throw new Exception("No hay clientes en la base de datos.");
    }
    printf("Cliente seleccionado: %s (ID: %d)\n", $client['nombre_razon_social'], $client['id']);

    // 2. Fetch products details
    $prodSuelo = $db->query("SELECT p.*, fe.nombre AS formato_nombre, fe.archivo_markdown FROM productos p LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id WHERE p.id = 16")->fetch(PDO::FETCH_ASSOC);
    $prodConcreto = $db->query("SELECT p.*, fe.nombre AS formato_nombre, fe.archivo_markdown FROM productos p LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id WHERE p.id = 8")->fetch(PDO::FETCH_ASSOC);
    $prodProctor = $db->query("SELECT p.*, fe.nombre AS formato_nombre, fe.archivo_markdown FROM productos p LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id WHERE p.id = 18")->fetch(PDO::FETCH_ASSOC);

    // 3. Create Cotización (using real prices: Suelo=900, Concreto=600*3=1800, Proctor=1300. Total=4000)
    $stmtCot = $db->prepare("INSERT INTO cotizaciones 
        (id_cliente, codigo, tipo_moneda, id_usuario_creador, id_usuario_revisor, version, nombre_proyecto, direccion_proyecto, prioridad, fecha_limite, condicion_pago, tiempo_entrega, vigencia_oferta, estado, subtotal, descuento, total, exonerado, impuesto) 
        VALUES 
        (:id_cliente, 'COT-2026-0001', 1, 1, 1, 1, 'Proyecto Simulación LIMS Completa', 'KM 84 Carretera León-Managua', 'Normal', '2026-08-14', 'Crédito', '7 días hábiles', '15 días', 'Aprobada por Cliente', 4000.00, 0.00, 4000.00, 0, 0.00)");
    
    $stmtCot->execute(['id_cliente' => $client['id']]);
    $idCotizacion = $db->lastInsertId();
    echo "✓ Cotización COT-2026-0001 creada (ID: $idCotizacion)\n";

    // 4. Create Cotización Detalles (3 models)
    
    // Model 1: Granulometría de Suelos (Product ID: 16)
    $resultadosSuelo = [
        ["Malla"=>"2\"","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"0.00","% que pasa la malla"=>"100.00","Límite Mín"=>"100","Límite Máx"=>"100"],
        ["Malla"=>"1 1/2\"","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"0.00","% que pasa la malla"=>"100.00","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"1\"","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"0.00","% que pasa la malla"=>"100.00","Límite Mín"=>"75","Límite Máx"=>"95"],
        ["Malla"=>"3/4\"","P. Retenido parcial (gr)"=>"46.3200","% Retenido parcial"=>"6.30","% Acumulativo"=>"6.30","% que pasa la malla"=>"93.70","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"1/2\"","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"6.30","% que pasa la malla"=>"93.70","Límite Mín"=>"50","Límite Máx"=>"80"],
        ["Malla"=>"3/8\"","P. Retenido parcial (gr)"=>"78.6900","% Retenido parcial"=>"10.70","% Acumulativo"=>"17.01","% que pasa la malla"=>"82.99","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 4","P. Retenido parcial (gr)"=>"67.1000","% Retenido parcial"=>"9.13","% Acumulativo"=>"26.13","% que pasa la malla"=>"73.87","Límite Mín"=>"30","Límite Máx"=>"65"],
        ["Malla"=>"No. 8","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"26.13","% que pasa la malla"=>"73.87","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 10","P. Retenido parcial (gr)"=>"80.3000","% Retenido parcial"=>"10.92","% Acumulativo"=>"37.06","% que pasa la malla"=>"62.94","Límite Mín"=>"20","Límite Máx"=>"50"],
        ["Malla"=>"No. 16","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"37.06","% que pasa la malla"=>"62.94","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 20","P. Retenido parcial (gr)"=>"84.5800","% Retenido parcial"=>"11.51","% Acumulativo"=>"48.56","% que pasa la malla"=>"51.44","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 30","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"48.56","% que pasa la malla"=>"51.44","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 40","P. Retenido parcial (gr)"=>"80.2100","% Retenido parcial"=>"10.91","% Acumulativo"=>"59.47","% que pasa la malla"=>"40.53","Límite Mín"=>"10","Límite Máx"=>"35"],
        ["Malla"=>"No. 50","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"59.47","% que pasa la malla"=>"40.53","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 60","P. Retenido parcial (gr)"=>"61.4600","% Retenido parcial"=>"8.36","% Acumulativo"=>"67.83","% que pasa la malla"=>"32.17","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 80","P. Retenido parcial (gr)"=>"0.0000","% Retenido parcial"=>"0.00","% Acumulativo"=>"67.83","% que pasa la malla"=>"32.17","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 100","P. Retenido parcial (gr)"=>"50.4700","% Retenido parcial"=>"6.87","% Acumulativo"=>"74.70","% que pasa la malla"=>"25.30","Límite Mín"=>"", "Límite Máx"=>""],
        ["Malla"=>"No. 140","P. Retenido parcial (gr)"=>"29.6900","% Retenido parcial"=>"4.04","% Acumulativo"=>"78.74","% que pasa la malla"=>"21.26","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"No. 200","P. Retenido parcial (gr)"=>"25.3600","% Retenido parcial"=>"3.45","% Acumulativo"=>"82.19","% que pasa la malla"=>"17.81","Límite Mín"=>"0","Límite Máx"=>"16"],
        ["Malla"=>"Fondo","P. Retenido parcial (gr)"=>"1.8000","% Retenido parcial"=>"0.24","% Acumulativo"=>"82.43","% que pasa la malla"=>"17.57","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"Pérdida lavado","P. Retenido parcial (gr)"=>"128.7600","% Retenido parcial"=>"17.52","% Acumulativo"=>"99.95","% que pasa la malla"=>"0.05","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"Suma","P. Retenido parcial (gr)"=>"735.1300","% Retenido parcial"=>"100.00","% Acumulativo"=>"100.00","% que pasa la malla"=>"0.00","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"Límite Líquido","P. Retenido parcial (gr)"=>"—","% Retenido parcial"=>"—","% Acumulativo"=>"—","% que pasa la malla"=>"37.00","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"Límite Plástico","P. Retenido parcial (gr)"=>"—","% Retenido parcial"=>"—","% Acumulativo"=>"—","% que pasa la malla"=>"22.00","Límite Mín"=>"","Límite Máx"=>""],
        ["Malla"=>"I.P","P. Retenido parcial (gr)"=>"—","% Retenido parcial"=>"—","% Acumulativo"=>"—","% que pasa la malla"=>"15.00","Límite Mín"=>"","Límite Máx"=>""]
    ];

    $stmtCd = $db->prepare("INSERT INTO cotizacion_detalles 
        (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, formato_reporte, cantidad, precio_unitario, subtotal, resultados_json) 
        VALUES 
        (:id_cotizacion, :id_producto, :descripcion, :codigo, :norma, :formato, :cantidad, :precio, :subtotal, :resultados)");
    
    // Suelo
    $stmtCd->execute([
        'id_cotizacion' => $idCotizacion,
        'id_producto' => 16,
        'descripcion' => $prodSuelo['nombre_comercial'],
        'codigo' => $prodSuelo['codigo_servicio'],
        'norma' => $prodSuelo['norma_astm'],
        'formato' => $prodSuelo['archivo_markdown'],
        'cantidad' => 1.00,
        'precio' => $prodSuelo['precio'],
        'subtotal' => $prodSuelo['precio'],
        'resultados' => json_encode($resultadosSuelo)
    ]);
    $idDetSuelo = $db->lastInsertId();
    echo "✓ Item 1: Granulometría de Suelos agregado (ID: $idDetSuelo)\n";

    // Concreto
    $stmtCd->execute([
        'id_cotizacion' => $idCotizacion,
        'id_producto' => 8,
        'descripcion' => $prodConcreto['nombre_comercial'],
        'codigo' => $prodConcreto['codigo_servicio'],
        'norma' => $prodConcreto['norma_astm'],
        'formato' => $prodConcreto['archivo_markdown'],
        'cantidad' => 3.00,
        'precio' => $prodConcreto['precio'],
        'subtotal' => $prodConcreto['precio'] * 3,
        'resultados' => json_encode([])
    ]);
    $idDetConcreto = $db->lastInsertId();
    echo "✓ Item 2: Resistencia de Concreto agregado (ID: $idDetConcreto)\n";

    // Proctor
    $resultadosProctor = [
        ["Punto"=>"1","Humedad (%)"=>"12.0","Densidad Seca (g/cm³)"=>"1.85"],
        ["Punto"=>"2","Humedad (%)"=>"14.0","Densidad Seca (g/cm³)"=>"1.92"],
        ["Punto"=>"3","Humedad (%)"=>"16.0","Densidad Seca (g/cm³)"=>"1.89"]
    ];
    $stmtCd->execute([
        'id_cotizacion' => $idCotizacion,
        'id_producto' => 18,
        'descripcion' => $prodProctor['nombre_comercial'],
        'codigo' => $prodProctor['codigo_servicio'],
        'norma' => $prodProctor['norma_astm'],
        'formato' => $prodProctor['archivo_markdown'],
        'cantidad' => 1.00,
        'precio' => $prodProctor['precio'],
        'subtotal' => $prodProctor['precio'],
        'resultados' => json_encode($resultadosProctor)
    ]);
    $idDetProctor = $db->lastInsertId();
    echo "✓ Item 3: Proctor Estándar agregado (ID: $idDetProctor)\n";

    // 5. Create Orden de Servicio
    $stmtOs = $db->prepare("INSERT INTO ordenes_servicio 
        (id_cotizacion, codigo_os, tipo_contrato, fecha_emision, estado, requiere_muestreo) 
        VALUES 
        (:id_cotizacion, 'OS-2026-0001', 'Puntual', '2026-07-14', 'Emitida', 0)");
    $stmtOs->execute(['id_cotizacion' => $idCotizacion]);
    $idOs = $db->lastInsertId();
    echo "✓ Orden de Servicio OS-2026-0001 creada (ID: $idOs)\n";

    // 6. Create Recepción de Muestras
    $stmtRec = $db->prepare("INSERT INTO recepcion_muestras 
        (id_os, correlativo_anual, anio, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, entregado_por, observaciones, estado) 
        VALUES 
        (:id_os, 1, 2026, 'MS-0001-26', 'CAMP-001', '2026-07-14 09:00:00', 1, 'Ing. Carlos Pérez', 'Muestras de simulación completa LIMS', 'En Laboratorio')");
    $stmtRec->execute(['id_os' => $idOs]);
    $idRecepcion = $db->lastInsertId();
    echo "✓ Recepción de Muestras MS-0001-26 creada (ID: $idRecepcion)\n";

    // 7. Create Lotes de Muestras
    $stmtLote = $db->prepare("INSERT INTO lotes_muestras 
        (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, revenimiento_cm, temperatura_c, procedimiento_muestreo) 
        VALUES 
        (:id_recepcion, :nombre_lote, :diseno, :fecha_moldeo, :rev_in, :rev_cm, :temp, :proc)");
    
    // Suelo
    $stmtLote->execute([
        'id_recepcion' => $idRecepcion,
        'nombre_lote' => 'Muestra Suelo Lote A',
        'diseno' => '',
        'fecha_moldeo' => '2026-07-14',
        'rev_in' => null,
        'rev_cm' => null,
        'temp' => null,
        'proc' => 'ASTM D6913'
    ]);
    $idLoteSuelo = $db->lastInsertId();

    // Concreto
    $stmtLote->execute([
        'id_recepcion' => $idRecepcion,
        'nombre_lote' => 'Muestra Concreto Lote B (Cilindros)',
        'diseno' => '3000',
        'fecha_moldeo' => '2026-07-13',
        'rev_in' => '3.50',
        'rev_cm' => '9.00',
        'temp' => '26.5',
        'proc' => 'ASTM C172 / CYCSA-PE-07'
    ]);
    $idLoteConcreto = $db->lastInsertId();

    // Proctor
    $stmtLote->execute([
        'id_recepcion' => $idRecepcion,
        'nombre_lote' => 'Muestra Proctor Lote C',
        'diseno' => '',
        'fecha_moldeo' => '2026-07-14',
        'rev_in' => null,
        'rev_cm' => null,
        'temp' => null,
        'proc' => 'ASTM D698'
    ]);
    $idLoteProctor = $db->lastInsertId();
    echo "✓ Lotes de Muestras creados (Suelo ID: $idLoteSuelo, Concreto ID: $idLoteConcreto, Proctor ID: $idLoteProctor)\n";

    // 8. Create Especímenes para Concreto (3 specimens with different waiting times)
    
    // Cylinder A: Broke today (1 day curado) -> completed!
    $stmtEsp = $db->prepare("INSERT INTO ensayo_edades 
        (id_lote, id_detalle_cotizacion, identificador_especimen, edad_dias, fecha_programada, fecha_ensaye_real, carga_lbs, area_in2, resistencia_psi, resistencia_kgcm2, porcentaje_diseno, cumple_norma, estado, usuario_ensayador) 
        VALUES 
        (:id_lote, :id_detalle, :identificador, :edad, :fecha_prog, :fecha_real, :carga, :area, :psi, :kg, :porc, :cumple, :estado, 1)");
    
    $stmtEsp->execute([
        'id_lote' => $idLoteConcreto,
        'id_detalle' => $idDetConcreto,
        'identificador' => 'A',
        'edad' => 1,
        'fecha_prog' => '2026-07-14',
        'fecha_real' => '2026-07-14',
        'carga' => 85000,
        'area' => 28.274,
        'psi' => 3006,
        'kg' => 211.3,
        'porc' => 100.2,
        'cumple' => 1,
        'estado' => 'Completado'
    ]);
    echo "✓ Cilindro A (1 día - Roto hoy, completado) creado.\n";

    // Cylinder B: Programmed for tomorrow (must wait 24 hours!)
    $stmtEsp->execute([
        'id_lote' => $idLoteConcreto,
        'id_detalle' => $idDetConcreto,
        'identificador' => 'B',
        'edad' => 2,
        'fecha_prog' => '2026-07-15',
        'fecha_real' => null,
        'carga' => null,
        'area' => null,
        'psi' => null,
        'kg' => null,
        'porc' => null,
        'cumple' => 0,
        'estado' => 'Programado'
    ]);
    echo "✓ Cilindro B (2 días - Programado para mañana, espera 24h) creado.\n";

    // Cylinder C: Programmed for 7 days
    $stmtEsp->execute([
        'id_lote' => $idLoteConcreto,
        'id_detalle' => $idDetConcreto,
        'identificador' => 'C',
        'edad' => 7,
        'fecha_prog' => '2026-07-20',
        'fecha_real' => null,
        'carga' => null,
        'area' => null,
        'psi' => null,
        'kg' => null,
        'porc' => null,
        'cumple' => 0,
        'estado' => 'Programado'
    ]);
    echo "✓ Cilindro C (7 días - Programado para futuro) creado.\n";

    // Also add dummy specimens for Soil and Proctor so they match our system logic
    $stmtDummy = $db->prepare("INSERT INTO ensayo_edades 
        (id_lote, id_detalle_cotizacion, identificador_especimen, edad_dias, fecha_programada, estado, usuario_ensayador) 
        VALUES 
        (:id_lote, :id_detalle, 'Muestra', 0, '2026-07-14', 'Completado', 1)");
    $stmtDummy->execute(['id_lote' => $idLoteSuelo, 'id_detalle' => $idDetSuelo]);
    $stmtDummy->execute(['id_lote' => $idLoteProctor, 'id_detalle' => $idDetProctor]);

    $db->commit();
    echo "\n=== SIMULACION DE OPERACIONES COMPLETADA CON EXITO ===\n";
    printf("LIMS ID de Lote de Concreto: %d, Suelo: %d, Proctor: %d\n", $idLoteConcreto, $idLoteSuelo, $idLoteProctor);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
