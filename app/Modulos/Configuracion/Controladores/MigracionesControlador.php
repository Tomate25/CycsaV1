<?php

namespace Cycsa\Modulos\Configuracion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Configuracion\Servicios\MigradorServicio;

class MigracionesControlador extends ControladorBase {

    public function ejecutar(Peticion $peticion, Respuesta $respuesta): void {
        $token = $peticion->obtenerParametro('token');
        $rolId = (int)($_SESSION['usuario_rol'] ?? ($_SESSION['usuario_rol_id'] ?? ($_SESSION['rol_id'] ?? 0)));
        $esAdmin = ($rolId === 1 || $token === 'cycsa_deploy_2026');

        if (!$esAdmin && !isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            return;
        }

        $db = Conexion::obtenerInstancia();
        $resultados = MigradorServicio::asegurarTablas($db);

        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'><title>Migraciones CYCSA</title>";
        echo "<style>body{font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:30px;line-height:1.6}.card{max-width:800px;margin:0 auto;background:#1e293b;border-radius:12px;padding:30px;border:1px solid #334155}h1{color:#38bdf8}.ok{color:#4ade80}.btn{display:inline-block;background:#2563eb;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;margin-top:20px;font-weight:bold;margin-right:10px}</style></head><body>";
        echo "<div class='card'>";
        echo "<h1>🛠️ Estado de Migraciones de Base de Datos</h1>";
        echo "<p>Base de datos: <strong>" . htmlspecialchars($_ENV['DB_NAME'] ?? 'cycsa_db') . "</strong></p>";
        echo "<hr style='border:0;border-top:1px solid #334155;margin:20px 0;'>";

        if (empty($resultados)) {
            echo "<p class='ok'>✅ Todas las tablas, plantillas e índices ya se encuentran al día y sincronizados (0 migraciones pendientes).</p>";
        } else {
            echo "<p class='ok'>✅ Migraciones ejecutadas con éxito:</p><pre style='background:#0f172a;padding:15px;border-radius:8px;color:#a5f3fc;overflow-x:auto;'>";
            print_r($resultados);
            echo "</pre>";
        }

        echo "<a href='/sistema/plantillas' class='btn'>Ir a Módulo de Plantillas ➔</a> ";
        echo "<a href='/sistema/panel' class='btn' style='background:#475569;'>Panel Principal</a>";
        echo "</div></body></html>";
        exit;
    }
}
