<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Enrutador;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;

class MiddlewarePrimeroPrueba {
    public static array $ejecutados = [];
    public function handle(): void {
        self::$ejecutados[] = 'primero';
    }
}

class MiddlewareSegundoPrueba {
    public function handle(): void {
        MiddlewarePrimeroPrueba::$ejecutados[] = 'segundo';
    }
}

class ControladorPrueba {
    public function index(Peticion $peticion, Respuesta $respuesta): string {
        return 'accion_index_ejecutada';
    }
}

class RouterTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        MiddlewarePrimeroPrueba::$ejecutados = [];
    }

    public function testRutaGetSimpleEjecutaCallback(): void {
        $peticion = $this->createMock(Peticion::class);
        $peticion->method('obtenerMetodo')->willReturn('GET');
        $peticion->method('obtenerRuta')->willReturn('/dashboard');

        $respuesta = $this->createMock(Respuesta::class);

        $enrutador = new Enrutador($peticion, $respuesta);
        $enrutador->get('/dashboard', function (Peticion $req, Respuesta $res) {
            return 'dashboard_contenido';
        });

        $resultado = $enrutador->resolver();
        $this->assertEquals('dashboard_contenido', $resultado);
    }

    public function testRutaPostSimpleEjecutaCallback(): void {
        $peticion = $this->createMock(Peticion::class);
        $peticion->method('obtenerMetodo')->willReturn('POST');
        $peticion->method('obtenerRuta')->willReturn('/guardar');

        $respuesta = $this->createMock(Respuesta::class);

        $enrutador = new Enrutador($peticion, $respuesta);
        $enrutador->post('/guardar', function (Peticion $req, Respuesta $res) {
            return 'guardado_exitoso';
        });

        $resultado = $enrutador->resolver();
        $this->assertEquals('guardado_exitoso', $resultado);
    }

    public function testRutaNoRegistradaDevuelve404(): void {
        $peticion = $this->createMock(Peticion::class);
        $peticion->method('obtenerMetodo')->willReturn('GET');
        $peticion->method('obtenerRuta')->willReturn('/ruta-inexistente');

        $respuesta = $this->createMock(Respuesta::class);
        $respuesta->expects($this->once())
            ->method('establecerCodigoEstado')
            ->with(404);

        $enrutador = new Enrutador($peticion, $respuesta);
        $resultado = $enrutador->resolver();

        $this->assertStringContainsString('Error 404', $resultado);
        $this->assertStringContainsString('/ruta-inexistente', $resultado);
    }

    public function testRutaEjecutaMiddlewaresEnOrden(): void {
        $peticion = $this->createMock(Peticion::class);
        $peticion->method('obtenerMetodo')->willReturn('GET');
        $peticion->method('obtenerRuta')->willReturn('/protegida');

        $respuesta = $this->createMock(Respuesta::class);

        $enrutador = new Enrutador($peticion, $respuesta);
        $enrutador->get(
            '/protegida',
            function () {
                return 'contenido_protegido';
            },
            [MiddlewarePrimeroPrueba::class, MiddlewareSegundoPrueba::class]
        );

        $resultado = $enrutador->resolver();

        $this->assertEquals('contenido_protegido', $resultado);
        $this->assertEquals(['primero', 'segundo'], MiddlewarePrimeroPrueba::$ejecutados);
    }

    public function testRutaResuelveControladorComoArreglo(): void {
        $peticion = $this->createMock(Peticion::class);
        $peticion->method('obtenerMetodo')->willReturn('GET');
        $peticion->method('obtenerRuta')->willReturn('/controlador-test');

        $respuesta = $this->createMock(Respuesta::class);

        $enrutador = new Enrutador($peticion, $respuesta);
        $enrutador->get('/controlador-test', [ControladorPrueba::class, 'index']);

        $resultado = $enrutador->resolver();
        $this->assertEquals('accion_index_ejecutada', $resultado);
    }

    public function testMiddlewareInexistenteLanzaExcepcionFailClosed(): void {
        $peticion = $this->createMock(Peticion::class);
        $peticion->method('obtenerMetodo')->willReturn('GET');
        $peticion->method('obtenerRuta')->willReturn('/ruta-segura-critica');

        $respuesta = $this->createMock(Respuesta::class);

        $enrutador = new Enrutador($peticion, $respuesta);
        $enrutador->get('/ruta-segura-critica', function () {
            return 'no_deberia_ejecutarse';
        }, ['\\Clase\\Middleware\\Inexistente\\ParaPrueba']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("no encontrado");

        $enrutador->resolver();
    }

    public function testRutaPostRaizEjecutaLoginCallback(): void {
        $peticion = $this->createMock(Peticion::class);
        $peticion->method('obtenerMetodo')->willReturn('POST');
        $peticion->method('obtenerRuta')->willReturn('/');

        $respuesta = $this->createMock(Respuesta::class);

        $enrutador = new Enrutador($peticion, $respuesta);
        $enrutador->post('/', function (Peticion $req, Respuesta $res) {
            return 'login_procesado';
        });

        $resultado = $enrutador->resolver();
        $this->assertEquals('login_procesado', $resultado);
    }

    public function testPeticionNormalizaRutaConIndexPhpYTrailingSlash(): void {
        $peticion = new Peticion();

        $_SERVER['SCRIPT_NAME'] = '/index.php';

        // 1. Caso raíz con /index.php
        $_SERVER['REQUEST_URI'] = '/index.php';
        $this->assertEquals('/', $peticion->obtenerRuta());

        // 2. Caso /index.php/login
        $_SERVER['REQUEST_URI'] = '/index.php/login';
        $this->assertEquals('/login', $peticion->obtenerRuta());

        // 3. Caso trailing slash /login/
        $_SERVER['REQUEST_URI'] = '/login/';
        $this->assertEquals('/login', $peticion->obtenerRuta());

        // 4. Caso con parámetros GET /login/?ref=1
        $_SERVER['REQUEST_URI'] = '/login/?ref=1';
        $this->assertEquals('/login', $peticion->obtenerRuta());
    }
}

