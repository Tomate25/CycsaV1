<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\App\Middleware\CsrfMiddleware;

class CsrfTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void {
        $_SESSION = [];
        $_POST = [];
        parent::tearDown();
    }

    public function testGeneracionTokenLongitudYFormatoHexadecimal(): void {
        $token = bin2hex(random_bytes(32));

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token), 'El token CSRF de 32 bytes debe tener 64 caracteres hexadecimales.');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token, 'El token debe estar compuesto estrictamente por caracteres hexadecimales en minúscula.');
    }

    public function testGeneracionTokensMultiplesSonUnicos(): void {
        $tokens = [];
        for ($i = 0; $i < 50; $i++) {
            $tokens[] = bin2hex(random_bytes(32));
        }

        $tokensUnicos = array_unique($tokens);
        $this->assertCount(50, $tokensUnicos, 'Cada llamada a bin2hex(random_bytes(32)) debe producir un token con entropía criptográfica única.');
    }

    public function testHashEqualsValidaTokenIdentico(): void {
        $tokenSesion = bin2hex(random_bytes(32));
        $tokenRecibido = (string)$tokenSesion;

        $this->assertTrue(
            hash_equals($tokenSesion, $tokenRecibido),
            'hash_equals debe retornar true cuando los tokens coinciden exactamente.'
        );
    }

    public function testHashEqualsRechazaTokenAlterado(): void {
        $tokenSesion = bin2hex(random_bytes(32));
        // Alterar el último carácter
        $ultimoChar = substr($tokenSesion, -1);
        $charReemplazo = ($ultimoChar === 'a') ? 'b' : 'a';
        $tokenAlterado = substr($tokenSesion, 0, -1) . $charReemplazo;

        $this->assertFalse(
            hash_equals($tokenSesion, $tokenAlterado),
            'hash_equals debe retornar false ante cualquier alteración de un carácter en el token.'
        );
    }

    public function testHashEqualsRechazaTokenTruncadoODeDistintaLongitud(): void {
        $tokenSesion = bin2hex(random_bytes(32));
        $tokenTruncado = substr($tokenSesion, 0, 32);

        $this->assertFalse(
            hash_equals($tokenSesion, $tokenTruncado),
            'hash_equals debe retornar false si la longitud no coincide.'
        );
    }

    public function testHashEqualsRechazaTokenVacio(): void {
        $tokenSesion = bin2hex(random_bytes(32));

        $this->assertFalse(
            hash_equals($tokenSesion, ''),
            'hash_equals debe retornar false si el token recibido es una cadena vacía.'
        );
    }

    public function testHashEqualsEsSensibleAMayusculasYMinusculas(): void {
        $tokenSesion = bin2hex(random_bytes(32));
        $tokenMayusculas = strtoupper($tokenSesion);

        $this->assertFalse(
            hash_equals($tokenSesion, $tokenMayusculas),
            'hash_equals debe distinguir entre mayúsculas y minúsculas.'
        );
    }

    public function testCsrfMiddlewareGeneraTokenEnGetSiNoExiste(): void {
        $middleware = new CsrfMiddleware();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEmpty($_SESSION['csrf_token'] ?? null);

        $resultado = $middleware->handle();

        $this->assertTrue($resultado);
        $this->assertNotEmpty($_SESSION['csrf_token']);
        $this->assertSame(64, strlen($_SESSION['csrf_token']));
    }

    public function testCsrfMiddlewarePreservaTokenExistenteEnGet(): void {
        $tokenPrevio = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $tokenPrevio;
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $middleware = new CsrfMiddleware();
        $resultado = $middleware->handle();

        $this->assertTrue($resultado);
        $this->assertSame($tokenPrevio, $_SESSION['csrf_token']);
    }

    public function testCsrfMiddlewarePermitePostConTokenValido(): void {
        $tokenValido = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $tokenValido;
        $_POST['csrf_token'] = $tokenValido;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $middleware = new CsrfMiddleware();
        $resultado = $middleware->handle();

        $this->assertTrue($resultado, 'CsrfMiddleware debe retornar true cuando el token CSRF en POST es válido.');
    }
}
