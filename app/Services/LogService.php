<?php

namespace Cycsa\App\Services;

/**
 * Servicio para registro de logs.
 */
class LogService
{
    private string $logDir;

    public function __construct()
    {
        $this->logDir = dirname(__DIR__, 2) . '/storage/logs/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    /**
     * Escribe un mensaje en el archivo de log correspondiente.
     *
     * @param string $categoria Ej: 'app', 'sql', 'login'
     * @param string $mensaje
     * @param string $nivel Ej: 'INFO', 'ERROR', 'WARNING'
     * @return void
     */
    public function registrar(string $categoria, string $mensaje, string $nivel = 'INFO'): void
    {
        $archivo = $this->logDir . $categoria . '.log';
        $fecha = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        $linea = sprintf("[%s] [%s] [%s] %s" . PHP_EOL, $fecha, $nivel, $ip, $mensaje);
        
        file_put_contents($archivo, $linea, FILE_APPEND);
    }
    
    public function error(string $categoria, string $mensaje): void
    {
        $this->registrar($categoria, $mensaje, 'ERROR');
    }
    
    public function info(string $categoria, string $mensaje): void
    {
        $this->registrar($categoria, $mensaje, 'INFO');
    }
}
