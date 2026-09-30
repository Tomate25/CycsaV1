<?php

namespace Tests\Unit;

use Cycsa\Modulos\Productos\Modelos\ProductoModelo;
use Cycsa\Modulos\Productos\Servicios\ImportadorProductosCsv;
use PHPUnit\Framework\TestCase;

class CargaMasivaProductosTest extends TestCase {
    private ?ProductoModelo $modelo = null;

    protected function setUp(): void {
        parent::setUp();
        try {
            $this->modelo = new ProductoModelo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para pruebas de ProductoModelo.');
        }
    }

    public function testGenerarPlantillaCsvContieneEncabezadosYBOM(): void {
        $plantilla = ImportadorProductosCsv::generarPlantillaCsv();

        // Debe iniciar con BOM UTF-8
        $this->assertStringStartsWith("\xEF\xBB\xBF", $plantilla);

        // Debe contener los encabezados obligatorios
        foreach (ImportadorProductosCsv::ENCABEZADOS as $header) {
            $this->assertStringContainsString($header, $plantilla);
        }

        // Debe contener ejemplos reales
        $this->assertStringContainsString('Humedad en agregados', $plantilla);
        $this->assertStringContainsString('Densidad y Humedad In Situ', $plantilla);
    }

    public function testParsearCsvValidoDistingueCrearYActualizar(): void {
        // Obtenemos un producto existente de la base de datos para probar la actualización
        $existentes = $this->modelo->obtenerTodos('', '', 1);
        if (empty($existentes)) {
            $this->markTestSkipped('No hay productos en base de datos para probar upsert.');
        }

        $prodExistente = $existentes[0];
        $nuevoPrecio = (float)$prodExistente['precio'] + 150.00;

        // Construir un CSV en memoria con 1 actualización y 1 creación
        $csv = "\xEF\xBB\xBFNo_Item,Codigo_Servicio,Nombre_Comercial,Ensayo_Servicio,Matriz_Tipo,Precio,Estatus\r\n";
        // Fila 1: Actualización del producto existente (mismo nombre comercial y no_item)
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s",%.2f,"Acreditado"' . "\r\n",
            $prodExistente['no_item'],
            $prodExistente['codigo_servicio'] ?? '',
            $prodExistente['nombre_comercial'],
            $prodExistente['ensayo_servicio'],
            $prodExistente['matriz_tipo'] ?? 'Suelo',
            $nuevoPrecio
        );
        // Fila 2: Creación de un producto totalmente nuevo
        $csv .= '"9999","CYCSA-TEST-99","Ensayo de Prueba Automatizada Nueva","Descripción ensayo nuevo","Suelo",850.00,"No acreditado"' . "\r\n";

        $resultado = ImportadorProductosCsv::parsearYValidar($csv, $this->modelo);

        $this->assertTrue($resultado['exito']);
        $this->assertSame(2, $resultado['total_filas']);
        $this->assertSame(2, $resultado['validas']);
        $this->assertSame(0, $resultado['errores']);
        $this->assertSame(1, $resultado['actualizaciones']);
        $this->assertSame(1, $resultado['nuevos']);

        // Verificar fila 1 (actualizar)
        $fila1 = $resultado['filas'][0];
        $this->assertSame('actualizar', $fila1['accion']);
        $this->assertSame((int)$prodExistente['id'], $fila1['producto_existente_id']);
        $this->assertArrayHasKey('precio', $fila1['diferencias']);

        // Verificar fila 2 (crear)
        $fila2 = $resultado['filas'][1];
        $this->assertSame('crear', $fila2['accion']);
        $this->assertNull($fila2['producto_existente_id']);
    }

    public function testParsearCsvDetectaErroresDeValidacion(): void {
        // CSV con precio negativo y sin nombre/descripción
        $csv = "No_Item,Nombre_Comercial,Ensayo_Servicio,Precio\r\n";
        $csv .= '"1",""," ",500.00' . "\r\n"; // Error: sin nombre ni ensayo
        $csv .= '"2","Ensayo con precio inválido","Detalle",-150.00' . "\r\n"; // Error: precio negativo
        $csv .= '"3","Ensayo Válido","Detalle Válido",1200.00' . "\r\n"; // Válido

        $resultado = ImportadorProductosCsv::parsearYValidar($csv, $this->modelo);

        $this->assertTrue($resultado['exito']);
        $this->assertSame(3, $resultado['total_filas']);
        $this->assertSame(1, $resultado['validas']);
        $this->assertSame(2, $resultado['errores']);

        // Fila 1 inválida
        $this->assertFalse($resultado['filas'][0]['es_valida']);
        $this->assertNotEmpty($resultado['filas'][0]['errores']);

        // Fila 2 inválida
        $this->assertFalse($resultado['filas'][1]['es_valida']);
        $this->assertNotEmpty($resultado['filas'][1]['errores']);

        // Fila 3 válida
        $this->assertTrue($resultado['filas'][2]['es_valida']);
    }

    public function testParsearCsvSoportaDelimitadorPuntoYComa(): void {
        $csv = "No_Item;Nombre_Comercial;Precio;Estatus\r\n";
        $csv .= "1001;Ensayo Separado por Punto y Coma;1500,50;Acreditado\r\n";

        $resultado = ImportadorProductosCsv::parsearYValidar($csv, $this->modelo);

        $this->assertTrue($resultado['exito']);
        $this->assertSame(1, $resultado['total_filas']);
        $this->assertSame(1, $resultado['validas']);
        $this->assertSame(1500.50, $resultado['filas'][0]['datos']['precio']);
    }

    public function testActualizacionParcialPreservaCamposTecnicosNoIncluidos(): void {
        $existentes = $this->modelo->obtenerTodos('', '', 1);
        if (empty($existentes)) {
            $this->markTestSkipped('No hay productos en base de datos para probar actualización parcial.');
        }

        $existente = $existentes[0];
        $precioNuevo = (float)$existente['precio'] + 25;
        $csv = "No_Item,Precio\r\n"
            . sprintf('"%s",%.2f', $existente['no_item'], $precioNuevo) . "\r\n";

        $resultado = ImportadorProductosCsv::parsearYValidar($csv, $this->modelo);
        $fila = $resultado['filas'][0];

        $this->assertTrue($fila['es_valida']);
        $this->assertSame('actualizar', $fila['accion']);
        $this->assertSame($existente['nombre_comercial'], $fila['datos']['nombre_comercial']);
        $this->assertSame($existente['ensayo_servicio'], $fila['datos']['ensayo_servicio']);
        $this->assertSame($existente['matriz_tipo'], $fila['datos']['matriz_tipo']);
        $this->assertSame($existente['norma_astm'], $fila['datos']['norma_astm']);
        $this->assertSame($existente['condiciones_muestra'], $fila['datos']['condiciones_muestra']);
        $this->assertSame($precioNuevo, $fila['datos']['precio']);
    }

    public function testDetectaProductoRepetidoDentroDelMismoArchivo(): void {
        $csv = "No_Item,Nombre_Comercial,Precio\r\n"
            . '"MASIVO-999","Producto duplicado de prueba",100.00' . "\r\n"
            . '"MASIVO-999","Producto duplicado de prueba",200.00' . "\r\n";

        $resultado = ImportadorProductosCsv::parsearYValidar($csv, $this->modelo);

        $this->assertSame(2, $resultado['total_filas']);
        $this->assertSame(1, $resultado['validas']);
        $this->assertSame(1, $resultado['errores']);
        $this->assertFalse($resultado['filas'][1]['es_valida']);
        $this->assertStringContainsString('repetido dentro del archivo', $resultado['filas'][1]['errores'][0]);
    }

    public function testRutasDeCargaMasivaRegistradasEnWebPhp(): void {
        $contenidoRutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $this->assertNotFalse($contenidoRutas);

        $this->assertStringContainsString("'/productos/descargar-plantilla'", $contenidoRutas);
        $this->assertStringContainsString("'/productos/exportar-catalogo-csv'", $contenidoRutas);
        $this->assertStringContainsString("'/productos/previsualizar-carga'", $contenidoRutas);
        $this->assertStringContainsString("'/productos/confirmar-carga'", $contenidoRutas);
    }

    public function testConfirmacionUsaSoloLoteValidadoEnServidorYVistaEscapaCsv(): void {
        $controlador = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Productos/Controladores/ProductosControlador.php');
        $vista = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Productos/Vistas/modal_carga_masiva.php');

        $this->assertNotFalse($controlador);
        $this->assertNotFalse($vista);
        $this->assertStringNotContainsString("POST['filas_json']", $controlador);
        $this->assertStringContainsString('escapeHtml', $vista);
        $this->assertStringNotContainsString('JSON.stringify(filasValidadasActuales)', $vista);
        $this->assertStringContainsString('resumenSinCambios', $vista);
    }

    public function testGenerarCatalogoCompletoCsvExportaTodosLosProductosConIdsYBOM(): void {
        $csv = ImportadorProductosCsv::generarCatalogoCompletoCsv($this->modelo);

        // Debe iniciar con BOM UTF-8 y la directiva universal sep=; para compatibilidad total con Excel
        $this->assertStringStartsWith("\xEF\xBB\xBFsep=;\r\n", $csv);

        // Al parsear y validar con el servicio de importación, debe procesar todos los productos sin errores
        $resultado = ImportadorProductosCsv::parsearYValidar($csv, $this->modelo);
        $this->assertTrue($resultado['exito']);
        $this->assertSame(0, $resultado['errores']);

        $activos = $this->modelo->obtenerTodos('', '', 1);
        $this->assertSame(count($activos), $resultado['total_filas']);
        $this->assertSame(count($activos), $resultado['validas']);
        $this->assertSame(count($activos), $resultado['sin_cambios']);
        $this->assertSame(0, $resultado['actualizaciones']);
    }

    public function testParsearCsvDistingueModificadosDeSinCambios(): void {
        $existentes = $this->modelo->obtenerTodos('', '', 1);
        if (count($existentes) < 2) {
            $this->markTestSkipped('Se requieren al menos 2 productos para probar sin_cambios vs modificado.');
        }

        $prod1 = $existentes[0]; // Se mantendrá idéntico
        $prod2 = $existentes[1]; // Se modificará el precio

        $nuevoPrecio = (float)$prod2['precio'] + 50.00;

        $csv = "\xEF\xBB\xBFID,No_Item,Codigo_Servicio,Nombre_Comercial,Ensayo_Servicio,Matriz_Tipo,Precio\r\n";
        // Fila 1: Idéntica (Sin cambios)
        $csv .= sprintf('"%d","%s","%s","%s","%s","%s",%.2f' . "\r\n",
            $prod1['id'],
            $prod1['no_item'] ?? '',
            $prod1['codigo_servicio'] ?? '',
            $prod1['nombre_comercial'],
            $prod1['ensayo_servicio'],
            $prod1['matriz_tipo'] ?? 'Otros',
            (float)$prod1['precio']
        );
        // Fila 2: Modificado (Precio diferente)
        $csv .= sprintf('"%d","%s","%s","%s","%s","%s",%.2f' . "\r\n",
            $prod2['id'],
            $prod2['no_item'] ?? '',
            $prod2['codigo_servicio'] ?? '',
            $prod2['nombre_comercial'],
            $prod2['ensayo_servicio'],
            $prod2['matriz_tipo'] ?? 'Otros',
            $nuevoPrecio
        );
        // Fila 3: Nuevo
        $csv .= '"","ITEM-NUEVO","CYCSA-NEW","Ensayo Nuevo Masivo","Ensayo Nuevo Masivo","Suelo",500.00' . "\r\n";

        $resultado = ImportadorProductosCsv::parsearYValidar($csv, $this->modelo);

        $this->assertTrue($resultado['exito']);
        $this->assertSame(3, $resultado['total_filas']);
        $this->assertSame(3, $resultado['validas']);
        $this->assertSame(0, $resultado['errores']);
        $this->assertSame(1, $resultado['sin_cambios'], 'Debe detectar 1 producto sin cambios.');
        $this->assertSame(1, $resultado['modificados'], 'Debe detectar 1 producto modificado.');
        $this->assertSame(1, $resultado['actualizaciones'], 'Debe sincronizar actualizaciones con modificados.');
        $this->assertSame(1, $resultado['nuevos'], 'Debe detectar 1 producto nuevo.');

        // Verificar acciones de cada fila
        $this->assertSame('sin_cambios', $resultado['filas'][0]['accion']);
        $this->assertEmpty($resultado['filas'][0]['diferencias']);

        $this->assertSame('actualizar', $resultado['filas'][1]['accion']);
        $this->assertNotEmpty($resultado['filas'][1]['diferencias']);
        $this->assertArrayHasKey('precio', $resultado['filas'][1]['diferencias']);

        $this->assertSame('crear', $resultado['filas'][2]['accion']);
    }
}
