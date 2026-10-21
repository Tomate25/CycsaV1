<?php

namespace Cycsa\Nucleo;

use InvalidArgumentException;
use RuntimeException;

/**
 * Evaluador seguro de fórmulas matemáticas para ensayos de laboratorio ISO/IEC 17025.
 * 
 * Implementa el algoritmo Shunting-Yard de Dijkstra para evaluación mediante
 * Notación Polaca Inversa (RPN). Prescinde absolutamente de eval() y create_function(),
 * garantizando inmunidad contra inyección de código y RCE.
 */
class EvaluadorFormula {

    private const OPERADORES = [
        '+' => ['prec' => 1, 'assoc' => 'L', 'args' => 2],
        '-' => ['prec' => 1, 'assoc' => 'L', 'args' => 2],
        '*' => ['prec' => 2, 'assoc' => 'L', 'args' => 2],
        '/' => ['prec' => 2, 'assoc' => 'L', 'args' => 2],
        '%' => ['prec' => 2, 'assoc' => 'L', 'args' => 2],
        '^' => ['prec' => 3, 'assoc' => 'R', 'args' => 2],
    ];

    private const FUNCIONES = [
        'ROUND' => ['min' => 1, 'max' => 2],
        'SQRT'  => ['min' => 1, 'max' => 1],
        'POW'   => ['min' => 2, 'max' => 2],
        'ABS'   => ['min' => 1, 'max' => 1],
        'MIN'   => ['min' => 1, 'max' => 10],
        'MAX'   => ['min' => 1, 'max' => 10],
        'CEIL'  => ['min' => 1, 'max' => 1],
        'FLOOR' => ['min' => 1, 'max' => 1],
        'PI'    => ['min' => 0, 'max' => 0],
        'LOG'   => ['min' => 1, 'max' => 2],
        'EXP'   => ['min' => 1, 'max' => 1],
    ];

    /**
     * Extrae todas las variables contenidas en una fórmula en formato {codigo}.
     *
     * @param string $formula
     * @return array<string> Lista única de códigos de variables
     */
    public static function extraerVariables(string $formula): array {
        preg_match_all('/\{([a-zA-Z0-9_\-]+)\}/', $formula, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Valida la sintaxis de una fórmula matemática y comprueba la disponibilidad de variables.
     *
     * @param string $formula
     * @param array<string> $variablesDisponibles
     * @return array{valido: bool, mensaje: string, variables_detectadas: array<string>}
     */
    public static function validarSintaxis(string $formula, array $variablesDisponibles = []): array {
        $formulaTrim = trim($formula);
        if ($formulaTrim === '') {
            return [
                'valido' => false,
                'mensaje' => 'La fórmula no puede estar vacía.',
                'variables_detectadas' => []
            ];
        }

        $detectadas = self::extraerVariables($formulaTrim);

        // Si se proveen variables disponibles, validar que todas existan
        if (!empty($variablesDisponibles)) {
            $faltantes = array_diff($detectadas, $variablesDisponibles);
            if (!empty($faltantes)) {
                return [
                    'valido' => false,
                    'mensaje' => 'Variables no reconocidas: ' . implode(', ', $faltantes),
                    'variables_detectadas' => $detectadas
                ];
            }
        }

        // Crear valores simulados (1.0) para evaluar la sintaxis
        $valoresDummy = [];
        foreach ($detectadas as $var) {
            $valoresDummy[$var] = 1.0;
        }

        try {
            $resultado = self::evaluar($formulaTrim, $valoresDummy);
            if ($resultado === null) {
                return [
                    'valido' => false,
                    'mensaje' => 'La fórmula produjo un resultado indefinido o indeterminación.',
                    'variables_detectadas' => $detectadas
                ];
            }
            return [
                'valido' => true,
                'mensaje' => 'Sintaxis correcta. Fórmula verificada con éxito.',
                'variables_detectadas' => $detectadas
            ];
        } catch (\Throwable $e) {
            return [
                'valido' => false,
                'mensaje' => 'Error de sintaxis: ' . $e->getMessage(),
                'variables_detectadas' => $detectadas
            ];
        }
    }

    /**
     * Evalúa una fórmula sustituyendo sus variables por valores numéricos.
     *
     * @param string $formula Expresión ej: "{carga_lb} / {area_in2}" o "ROUND({area_in2} * 2.5, 2)"
     * @param array<string, mixed> $valores Mapeo [codigo => valor]
     * @return float|null
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function evaluar(string $formula, array $valores = []): ?float {
        $formulaLimpia = trim($formula);
        if ($formulaLimpia === '') {
            return null;
        }

        // Sustituir constantes especiales
        $expr = preg_replace('/\bPI\(\)/i', (string)M_PI, $formulaLimpia);

        // Sustituir variables {codigo} por valores numéricos
        $variables = self::extraerVariables($expr);
        foreach ($variables as $var) {
            if (!array_key_exists($var, $valores) || $valores[$var] === null || $valores[$var] === '') {
                // Si falta un valor necesario, no es error fatal de sintaxis, pero no se puede calcular
                return null;
            }
            $valNum = (float)$valores[$var];
            // Si el número es negativo, envolver entre paréntesis para el parser
            $valStr = ($valNum < 0) ? '(' . $valNum . ')' : (string)$valNum;
            $expr = str_replace('{' . $var . '}', $valStr, $expr);
        }

        // Tokenizar
        $tokens = self::tokenizar($expr);

        // Convertir a RPN (Shunting-Yard)
        $rpn = self::shuntingYard($tokens);

        // Evaluar RPN
        return self::evaluarRpn($rpn);
    }

    /**
     * Convierte la cadena en un flujo ordenado de tokens.
     */
    private static function tokenizar(string $expr): array {
        $tokens = [];
        $len = strlen($expr);
        $i = 0;

        $ultimoTipo = 'INICIO'; // INICIO, NUMERO, OPERADOR, ABRE_PARENTESIS, CIERRA_PARENTESIS, COMA

        while ($i < $len) {
            $char = $expr[$i];

            // Ignorar espacios en blanco
            if (ctype_space($char)) {
                $i++;
                continue;
            }

            // Números (enteros y decimales)
            if (ctype_digit($char) || ($char === '.' && isset($expr[$i + 1]) && ctype_digit($expr[$i + 1]))) {
                $num = '';
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i];
                    $i++;
                }
                $tokens[] = ['tipo' => 'NUMERO', 'valor' => (float)$num];
                $ultimoTipo = 'NUMERO';
                continue;
            }

            // Identificadores de funciones (letras)
            if (ctype_alpha($char)) {
                $id = '';
                while ($i < $len && ctype_alpha($expr[$i])) {
                    $id .= strtoupper($expr[$i]);
                    $i++;
                }
                if (isset(self::FUNCIONES[$id])) {
                    $tokens[] = ['tipo' => 'FUNCION', 'valor' => $id];
                    $ultimoTipo = 'FUNCION';
                    continue;
                }
                throw new InvalidArgumentException("Función desconocida o no autorizada: {$id}");
            }

            // Manejo de signo negativo unario: ej. -5 o (-5)
            if ($char === '-' && ($ultimoTipo === 'INICIO' || $ultimoTipo === 'OPERADOR' || $ultimoTipo === 'ABRE_PARENTESIS' || $ultimoTipo === 'COMA')) {
                // Insertar un 0 antes para simular 0 - X
                $tokens[] = ['tipo' => 'NUMERO', 'valor' => 0.0];
                $tokens[] = ['tipo' => 'OPERADOR', 'valor' => '-'];
                $ultimoTipo = 'OPERADOR';
                $i++;
                continue;
            }

            // Operadores básicos
            if (isset(self::OPERADORES[$char])) {
                $tokens[] = ['tipo' => 'OPERADOR', 'valor' => $char];
                $ultimoTipo = 'OPERADOR';
                $i++;
                continue;
            }

            // Paréntesis
            if ($char === '(') {
                $tokens[] = ['tipo' => 'ABRE_PARENTESIS', 'valor' => '('];
                $ultimoTipo = 'ABRE_PARENTESIS';
                $i++;
                continue;
            }
            if ($char === ')') {
                $tokens[] = ['tipo' => 'CIERRA_PARENTESIS', 'valor' => ')'];
                $ultimoTipo = 'CIERRA_PARENTESIS';
                $i++;
                continue;
            }

            // Separador de argumentos (coma)
            if ($char === ',') {
                $tokens[] = ['tipo' => 'COMA', 'valor' => ','];
                $ultimoTipo = 'COMA';
                $i++;
                continue;
            }

            throw new InvalidArgumentException("Carácter no permitido en la expresión: '{$char}'");
        }

        return $tokens;
    }

    /**
     * Algoritmo Shunting-Yard para convertir tokens infijos a RPN.
     */
    private static function shuntingYard(array $tokens): array {
        $salida = [];
        $pilaOperadores = [];
        $contadorArgs = [];

        foreach ($tokens as $token) {
            switch ($token['tipo']) {
                case 'NUMERO':
                    $salida[] = $token;
                    break;

                case 'FUNCION':
                    $pilaOperadores[] = $token;
                    $contadorArgs[] = 1;
                    break;

                case 'COMA':
                    while (!empty($pilaOperadores) && end($pilaOperadores)['tipo'] !== 'ABRE_PARENTESIS') {
                        $salida[] = array_pop($pilaOperadores);
                    }
                    if (empty($pilaOperadores)) {
                        throw new InvalidArgumentException("Coma fuera de lugar o paréntesis faltante.");
                    }
                    if (!empty($contadorArgs)) {
                        $contadorArgs[count($contadorArgs) - 1]++;
                    }
                    break;

                case 'OPERADOR':
                    $op1 = $token['valor'];
                    $prec1 = self::OPERADORES[$op1]['prec'];
                    $assoc1 = self::OPERADORES[$op1]['assoc'];

                    while (!empty($pilaOperadores)) {
                        $top = end($pilaOperadores);
                        if ($top['tipo'] === 'OPERADOR') {
                            $op2 = $top['valor'];
                            $prec2 = self::OPERADORES[$op2]['prec'];

                            if (($assoc1 === 'L' && $prec1 <= $prec2) || ($assoc1 === 'R' && $prec1 < $prec2)) {
                                $salida[] = array_pop($pilaOperadores);
                                continue;
                            }
                        } elseif ($top['tipo'] === 'FUNCION') {
                            $salida[] = array_pop($pilaOperadores);
                            continue;
                        }
                        break;
                    }
                    $pilaOperadores[] = $token;
                    break;

                case 'ABRE_PARENTESIS':
                    $pilaOperadores[] = $token;
                    break;

                case 'CIERRA_PARENTESIS':
                    while (!empty($pilaOperadores) && end($pilaOperadores)['tipo'] !== 'ABRE_PARENTESIS') {
                        $salida[] = array_pop($pilaOperadores);
                    }
                    if (empty($pilaOperadores)) {
                        throw new InvalidArgumentException("Paréntesis desbalanceados: falta apertura '('");
                    }
                    array_pop($pilaOperadores); // Descartar '('

                    // Si en la cima hay una función, pasarla a la salida junto con su cantidad de argumentos
                    if (!empty($pilaOperadores) && end($pilaOperadores)['tipo'] === 'FUNCION') {
                        $fn = array_pop($pilaOperadores);
                        $fn['num_args'] = !empty($contadorArgs) ? array_pop($contadorArgs) : 0;
                        $salida[] = $fn;
                    }
                    break;
            }
        }

        while (!empty($pilaOperadores)) {
            $top = array_pop($pilaOperadores);
            if ($top['tipo'] === 'ABRE_PARENTESIS' || $top['tipo'] === 'CIERRA_PARENTESIS') {
                throw new InvalidArgumentException("Paréntesis desbalanceados en la expresión.");
            }
            $salida[] = $top;
        }

        return $salida;
    }

    /**
     * Evalúa una lista de tokens en formato RPN.
     */
    private static function evaluarRpn(array $rpn): ?float {
        $pila = [];

        foreach ($rpn as $token) {
            if ($token['tipo'] === 'NUMERO') {
                $pila[] = (float)$token['valor'];
            } elseif ($token['tipo'] === 'OPERADOR') {
                if (count($pila) < 2) {
                    throw new InvalidArgumentException("Faltan operandos para el operador '{$token['valor']}'");
                }
                $b = array_pop($pila);
                $a = array_pop($pila);

                switch ($token['valor']) {
                    case '+':
                        $pila[] = $a + $b;
                        break;
                    case '-':
                        $pila[] = $a - $b;
                        break;
                    case '*':
                        $pila[] = $a * $b;
                        break;
                    case '/':
                        if (abs($b) < 1e-12) {
                            // División por cero: retornar null para evitar colapso
                            return null;
                        }
                        $pila[] = $a / $b;
                        break;
                    case '%':
                        if (abs($b) < 1e-12) return null;
                        $pila[] = fmod($a, $b);
                        break;
                    case '^':
                        $pila[] = pow($a, $b);
                        break;
                }
            } elseif ($token['tipo'] === 'FUNCION') {
                $nombreFn = $token['valor'];
                $numArgs = $token['num_args'] ?? 1;

                if (count($pila) < $numArgs) {
                    throw new InvalidArgumentException("Faltan argumentos para la función {$nombreFn}");
                }

                $args = [];
                for ($k = 0; $k < $numArgs; $k++) {
                    array_unshift($args, array_pop($pila));
                }

                $defFn = self::FUNCIONES[$nombreFn];
                if ($numArgs < $defFn['min'] || $numArgs > $defFn['max']) {
                    throw new InvalidArgumentException("La función {$nombreFn} esperaba entre {$defFn['min']} y {$defFn['max']} argumentos, se recibieron {$numArgs}.");
                }

                switch ($nombreFn) {
                    case 'ROUND':
                        $pila[] = round($args[0], (int)($args[1] ?? 0));
                        break;
                    case 'SQRT':
                        if ($args[0] < 0) return null; // Raíz negativa
                        $pila[] = sqrt($args[0]);
                        break;
                    case 'POW':
                        $pila[] = pow($args[0], $args[1]);
                        break;
                    case 'ABS':
                        $pila[] = abs($args[0]);
                        break;
                    case 'MIN':
                        $pila[] = min($args);
                        break;
                    case 'MAX':
                        $pila[] = max($args);
                        break;
                    case 'CEIL':
                        $pila[] = ceil($args[0]);
                        break;
                    case 'FLOOR':
                        $pila[] = floor($args[0]);
                        break;
                    case 'PI':
                        $pila[] = M_PI;
                        break;
                    case 'LOG':
                        if ($args[0] <= 0) return null;
                        $pila[] = isset($args[1]) ? log($args[0], $args[1]) : log($args[0]);
                        break;
                    case 'EXP':
                        $pila[] = exp($args[0]);
                        break;
                }
            }
        }

        if (count($pila) !== 1) {
            throw new InvalidArgumentException("Expresión inválida: quedaron operandos sin procesar.");
        }

        return (float)array_pop($pila);
    }
}
