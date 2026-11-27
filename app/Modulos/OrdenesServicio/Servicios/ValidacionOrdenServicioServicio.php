<?php

declare(strict_types=1);

namespace Cycsa\Modulos\OrdenesServicio\Servicios;

use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use PDO;

/**
 * Motor oficial de validaciones digitales, trazabilidad inmutable, cierre automático
 * a "Ejecutada", devolución automática a Administración/Contabilidad y alertas de
 * vencimiento mensual para el formato CYCSA-RG-FM-39 V1R1 (Puntos 1 al 17).
 */
class ValidacionOrdenServicioServicio
{
    public const AREA_ADMIN_CONTABILIDAD = 'Administración y Contabilidad';
    public const AREA_LABORATORIO = 'Laboratorio y Operaciones';

    /** @var array<string, bool> */
    private static array $cacheEsquema = [];

    public static function esquemaDisponible(PDO $db): bool
    {
        $key = spl_object_hash($db);
        if (array_key_exists($key, self::$cacheEsquema)) {
            return self::$cacheEsquema[$key];
        }
        try {
            $chk = $db->query("SHOW COLUMNS FROM ordenes_servicio LIKE 'autorizado_admin_fecha'");
            self::$cacheEsquema[$key] = (bool)($chk && $chk->fetch(PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            self::$cacheEsquema[$key] = false;
        }
        return self::$cacheEsquema[$key];
    }

    /**
     * Obtiene el nombre y rol/cargo real del usuario registrado en base de datos (Punto 2).
     *
     * @return array{id: int, nombre: string, rol_id: int, rol_nombre: string}
     */
    public static function resolverUsuarioRegistrado(PDO $db, int $usuarioId, int $rolFallback = 0, string $nombreFallback = ''): array
    {
        if ($usuarioId > 0) {
            try {
                $stmt = $db->prepare("
                    SELECT u.id, u.nombre, COALESCE(u.id_rol, 0) AS rol_id, COALESCE(r.nombre, '') AS rol_nombre
                    FROM usuarios u
                    LEFT JOIN roles r ON u.id_rol = r.id
                    WHERE u.id = :id
                    LIMIT 1
                ");
                $stmt->execute(['id' => $usuarioId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return [
                        'id' => (int)$row['id'],
                        'nombre' => trim((string)($row['nombre'] ?? '')) ?: ($nombreFallback ?: 'Usuario #' . $usuarioId),
                        'rol_id' => (int)($row['rol_id'] ?? $rolFallback),
                        'rol_nombre' => trim((string)($row['rol_nombre'] ?? '')),
                    ];
                }
            } catch (\Throwable $e) {
            }
        }
        return [
            'id' => $usuarioId,
            'nombre' => $nombreFallback !== '' ? $nombreFallback : 'Usuario del Sistema',
            'rol_id' => $rolFallback,
            'rol_nombre' => '',
        ];
    }

    /**
     * Punto 4: Permisos de autorización y recepción definitiva según cargo/rol (Administración y Contabilidad).
     */
    public static function puedeAutorizarAdmin(PDO $db, int $usuarioId, int $rolId = 0): bool
    {
        $u = self::resolverUsuarioRegistrado($db, $usuarioId, $rolId);
        $rId = $u['rol_id'] > 0 ? $u['rol_id'] : $rolId;
        if ($rId === 1) {
            return true;
        }
        $rNom = mb_strtolower($u['rol_nombre'], 'UTF-8');
        if ($rNom !== '' && preg_match('/admin|geren|contab|finanz|factur|direcci/i', $rNom)) {
            return true;
        }
        if (function_exists('tienePermiso') && (tienePermiso('ordenes_servicio', 'aprobar') || tienePermiso('contabilidad', 'ver'))) {
            return in_array($rId, [1, 2, 4, 7], true) || preg_match('/admin|geren|contab/i', $rNom);
        }
        return false;
    }

    /**
     * Punto 4: Permisos de recepción operativa (Coordinación de Laboratorio, Recepción de Muestras y Atención al Cliente).
     */
    public static function puedeRecibirLaboratorioORecepcion(PDO $db, int $usuarioId, int $rolId = 0): bool
    {
        $u = self::resolverUsuarioRegistrado($db, $usuarioId, $rolId);
        $rId = $u['rol_id'] > 0 ? $u['rol_id'] : $rolId;
        if (in_array($rId, [1, 3, 4, 6], true)) {
            return true;
        }
        $rNom = mb_strtolower($u['rol_nombre'], 'UTF-8');
        if ($rNom !== '' && preg_match('/lab|recepci|atenci|coordin|supervis|operac|asistent|tecnic/i', $rNom)) {
            return true;
        }
        return false;
    }

    /**
     * Punto 4 y 14: Permiso para recepción definitiva de la orden ejecutada (Administración y Contabilidad).
     */
    public static function puedeRecibirAdminFinal(PDO $db, int $usuarioId, int $rolId = 0): bool
    {
        return self::puedeAutorizarAdmin($db, $usuarioId, $rolId);
    }

    /**
     * Punto 17: Solo Administración / Gerencia puede realizar una corrección autorizada con trazabilidad.
     */
    public static function puedeCorregirValidacion(PDO $db, int $usuarioId, int $rolId = 0): bool
    {
        $u = self::resolverUsuarioRegistrado($db, $usuarioId, $rolId);
        $rId = $u['rol_id'] > 0 ? $u['rol_id'] : $rolId;
        if ($rId === 1) {
            return true;
        }
        $rNom = mb_strtolower($u['rol_nombre'], 'UTF-8');
        return $rNom !== '' && (bool)preg_match('/admin|geren/i', $rNom);
    }

    /**
     * Formatea fecha y hora al estilo oficial del recuadro CYCSA-RG-FM-39 V1R1:
     * "27/11/2026 – 08:00 a. m."
     */
    public static function formatearFechaHoraOficial(?string $fechaRaw): string
    {
        if ($fechaRaw === null || trim($fechaRaw) === '') {
            return '';
        }
        $ts = strtotime($fechaRaw);
        if ($ts === false) {
            return trim($fechaRaw);
        }
        $ampm = date('a', $ts) === 'am' ? 'a. m.' : 'p. m.';
        return date('d/m/Y', $ts) . ' – ' . date('h:i', $ts) . ' ' . $ampm;
    }

    /**
     * Casilla 1: Autorizado por Administración (inmutable una vez validado — Puntos 1, 2, 3, 4, 16, 17).
     *
     * @return array{ok: bool, mensaje: string}
     */
    public static function registrarAutorizacionAdmin(PDO $db, int $idOs, int $usuarioId, int $rolId = 0, string $nombreFallback = ''): array
    {
        if (!self::esquemaDisponible($db)) {
            return ['ok' => false, 'mensaje' => 'Esquema de validaciones RG-FM-39 V1R1 no instalado.'];
        }
        if (!self::puedeAutorizarAdmin($db, $usuarioId, $rolId)) {
            return ['ok' => false, 'mensaje' => 'No cuenta con el cargo/permiso de Administración para autorizar esta Orden de Servicio.'];
        }

        $os = self::obtenerFilaOs($db, $idOs);
        if (!$os) {
            return ['ok' => false, 'mensaje' => 'Orden de Servicio no encontrada.'];
        }
        if (!empty($os['autorizado_admin_fecha'])) {
            return ['ok' => false, 'mensaje' => 'La autorización de Administración ya está validada y bloqueada. Solo puede modificarse mediante corrección autorizada con trazabilidad.'];
        }

        $u = self::resolverUsuarioRegistrado($db, $usuarioId, $rolId, $nombreFallback);
        $ahora = date('Y-m-d H:i:s');

        $db->prepare("
            UPDATE ordenes_servicio
            SET autorizado_admin_usuario_id = :uid,
                autorizado_admin_nombre = :unom,
                autorizado_admin_fecha = :fec
            WHERE id = :id_os AND autorizado_admin_fecha IS NULL
        ")->execute([
            'uid' => $u['id'] > 0 ? $u['id'] : null,
            'unom' => $u['nombre'],
            'fec' => $ahora,
            'id_os' => $idOs,
        ]);

        self::registrarHistorial($db, [
            'id_os' => $idOs,
            'campo_validacion' => 'autorizado_admin',
            'accion' => 'validar',
            'usuario_id' => $u['id'] > 0 ? $u['id'] : null,
            'usuario_nombre' => $u['nombre'],
            'cargo_rol' => $u['rol_nombre'] ?: 'Administración',
            'estado_anterior' => (string)($os['estado'] ?? ''),
            'estado_nuevo' => (string)($os['estado'] ?? ''),
            'valor_nuevo' => $u['nombre'] . ' | ' . $ahora,
            'motivo' => 'Autorización digital de Orden de Servicio por Administración (CYCSA-RG-FM-39 V1R1).',
        ]);

        return ['ok' => true, 'mensaje' => 'Orden autorizada digitalmente por Administración (' . $u['nombre'] . ').'];
    }

    /**
     * Casilla 2: Recibido por Coordinación de Laboratorio y/o Recepción de Muestras y Atención al Cliente.
     *
     * @return array{ok: bool, mensaje: string}
     */
    public static function registrarRecepcionLab(PDO $db, int $idOs, int $usuarioId, int $rolId = 0, string $nombreFallback = ''): array
    {
        if (!self::esquemaDisponible($db)) {
            return ['ok' => false, 'mensaje' => 'Esquema de validaciones RG-FM-39 V1R1 no instalado.'];
        }
        if (!self::puedeRecibirLaboratorioORecepcion($db, $usuarioId, $rolId)) {
            return ['ok' => false, 'mensaje' => 'No cuenta con el cargo/permiso de Coordinación de Laboratorio o Recepción de Muestras para confirmar esta recepción.'];
        }

        $os = self::obtenerFilaOs($db, $idOs);
        if (!$os) {
            return ['ok' => false, 'mensaje' => 'Orden de Servicio no encontrada.'];
        }
        if (!empty($os['recibido_lab_fecha'])) {
            return ['ok' => false, 'mensaje' => 'La recepción por Laboratorio/Recepción ya está validada y bloqueada contra modificación directa.'];
        }

        $u = self::resolverUsuarioRegistrado($db, $usuarioId, $rolId, $nombreFallback);
        $ahora = date('Y-m-d H:i:s');

        $db->prepare("
            UPDATE ordenes_servicio
            SET recibido_lab_usuario_id = :uid,
                recibido_lab_nombre = :unom,
                recibido_lab_fecha = :fec
            WHERE id = :id_os AND recibido_lab_fecha IS NULL
        ")->execute([
            'uid' => $u['id'] > 0 ? $u['id'] : null,
            'unom' => $u['nombre'],
            'fec' => $ahora,
            'id_os' => $idOs,
        ]);

        self::registrarHistorial($db, [
            'id_os' => $idOs,
            'campo_validacion' => 'recibido_lab',
            'accion' => 'validar',
            'usuario_id' => $u['id'] > 0 ? $u['id'] : null,
            'usuario_nombre' => $u['nombre'],
            'cargo_rol' => $u['rol_nombre'] ?: 'Laboratorio / Recepción',
            'estado_anterior' => (string)($os['estado'] ?? ''),
            'estado_nuevo' => (string)($os['estado'] ?? ''),
            'valor_nuevo' => $u['nombre'] . ' | ' . $ahora,
            'motivo' => 'Recepción digital confirmada por Coordinación de Laboratorio / Recepción de Muestras.',
        ]);

        return ['ok' => true, 'mensaje' => 'Recepción de la orden confirmada por ' . $u['nombre'] . '.'];
    }

    /**
     * Casilla 4: Recepción definitiva por Administración de la orden ejecutada (Punto 14).
     *
     * @return array{ok: bool, mensaje: string}
     */
    public static function registrarRecepcionAdminFinal(PDO $db, int $idOs, int $usuarioId, int $rolId = 0, string $nombreFallback = ''): array
    {
        if (!self::esquemaDisponible($db)) {
            return ['ok' => false, 'mensaje' => 'Esquema de validaciones RG-FM-39 V1R1 no instalado.'];
        }
        if (!self::puedeRecibirAdminFinal($db, $usuarioId, $rolId)) {
            return ['ok' => false, 'mensaje' => 'Solo personal de Administración y Contabilidad puede confirmar la recepción definitiva de la orden ejecutada.'];
        }

        $os = self::obtenerFilaOs($db, $idOs);
        if (!$os) {
            return ['ok' => false, 'mensaje' => 'Orden de Servicio no encontrada.'];
        }
        if (empty($os['devuelto_sistema_fecha']) && !in_array((string)($os['estado'] ?? ''), ['Ejecutada', 'Finalizado', 'Cerrado', 'Archivado'], true)) {
            return ['ok' => false, 'mensaje' => 'La Orden de Servicio aún no ha sido devuelta automáticamente como Ejecutada.'];
        }
        if (!empty($os['recibido_admin_final_fecha'])) {
            return ['ok' => false, 'mensaje' => 'La recepción definitiva por Administración ya está registrada y bloqueada contra modificación directa.'];
        }

        $u = self::resolverUsuarioRegistrado($db, $usuarioId, $rolId, $nombreFallback);
        $ahora = date('Y-m-d H:i:s');

        $db->prepare("
            UPDATE ordenes_servicio
            SET recibido_admin_final_usuario_id = :uid,
                recibido_admin_final_nombre = :unom,
                recibido_admin_final_fecha = :fec
            WHERE id = :id_os AND recibido_admin_final_fecha IS NULL
        ")->execute([
            'uid' => $u['id'] > 0 ? $u['id'] : null,
            'unom' => $u['nombre'],
            'fec' => $ahora,
            'id_os' => $idOs,
        ]);

        // Marcar notificaciones pendientes de esta orden para Administración como leídas
        try {
            $db->prepare("
                UPDATE notificaciones_ordenes_servicio
                SET leida = 1, leida_por = :uid, fecha_leida = :fec
                WHERE id_os = :id_os AND destinatario_area = :area AND leida = 0
            ")->execute([
                'uid' => $u['id'] > 0 ? $u['id'] : null,
                'fec' => $ahora,
                'id_os' => $idOs,
                'area' => self::AREA_ADMIN_CONTABILIDAD,
            ]);
        } catch (\Throwable $e) {
        }

        self::registrarHistorial($db, [
            'id_os' => $idOs,
            'campo_validacion' => 'recibido_admin_final',
            'accion' => 'validar',
            'usuario_id' => $u['id'] > 0 ? $u['id'] : null,
            'usuario_nombre' => $u['nombre'],
            'cargo_rol' => $u['rol_nombre'] ?: 'Administración',
            'estado_anterior' => (string)($os['estado'] ?? ''),
            'estado_nuevo' => (string)($os['estado'] ?? ''),
            'valor_nuevo' => $u['nombre'] . ' | ' . $ahora,
            'motivo' => 'Recepción definitiva de orden ejecutada confirmada por Administración y Contabilidad.',
        ]);

        return ['ok' => true, 'mensaje' => 'Recepción definitiva confirmada por Administración (' . $u['nombre'] . ').'];
    }

    /**
     * Punto 17: Corrección excepcional autorizada (solo Admin/Gerencia, con motivo obligatorio
     * y trazabilidad completa del valor anterior y nuevo).
     *
     * @return array{ok: bool, mensaje: string}
     */
    public static function corregirValidacionAutorizada(
        PDO $db,
        int $idOs,
        string $campo,
        string $motivo,
        int $usuarioAutorizadorId,
        int $rolAutorizadorId = 0,
        ?int $nuevoUsuarioId = null
    ): array {
        if (!self::esquemaDisponible($db)) {
            return ['ok' => false, 'mensaje' => 'Esquema de validaciones no disponible.'];
        }
        if (!self::puedeCorregirValidacion($db, $usuarioAutorizadorId, $rolAutorizadorId)) {
            return ['ok' => false, 'mensaje' => 'Solo Administración / Gerencia puede registrar una corrección autorizada.'];
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['ok' => false, 'mensaje' => 'Debe especificar obligatoriamente el motivo de la corrección autorizada.'];
        }

        $camposPermitidos = [
            'autorizado_admin' => ['uid' => 'autorizado_admin_usuario_id', 'nom' => 'autorizado_admin_nombre', 'fec' => 'autorizado_admin_fecha'],
            'recibido_lab' => ['uid' => 'recibido_lab_usuario_id', 'nom' => 'recibido_lab_nombre', 'fec' => 'recibido_lab_fecha'],
            'recibido_admin_final' => ['uid' => 'recibido_admin_final_usuario_id', 'nom' => 'recibido_admin_final_nombre', 'fec' => 'recibido_admin_final_fecha'],
        ];
        if (!isset($camposPermitidos[$campo])) {
            return ['ok' => false, 'mensaje' => 'Campo de validación no editable (la devolución automática del sistema es inmutable).'];
        }

        $os = self::obtenerFilaOs($db, $idOs);
        if (!$os) {
            return ['ok' => false, 'mensaje' => 'Orden de Servicio no encontrada.'];
        }

        $cols = $camposPermitidos[$campo];
        $valAnterior = trim((string)($os[$cols['nom']] ?? 'Sin validar')) . ' | ' . trim((string)($os[$cols['fec']] ?? '—'));
        $uAut = self::resolverUsuarioRegistrado($db, $usuarioAutorizadorId, $rolAutorizadorId);
        $uDestino = ($nuevoUsuarioId !== null && $nuevoUsuarioId > 0)
            ? self::resolverUsuarioRegistrado($db, $nuevoUsuarioId)
            : $uAut;
        $ahora = date('Y-m-d H:i:s');

        $db->prepare("
            UPDATE ordenes_servicio
            SET {$cols['uid']} = :uid,
                {$cols['nom']} = :unom,
                {$cols['fec']} = :fec
            WHERE id = :id_os
        ")->execute([
            'uid' => $uDestino['id'] > 0 ? $uDestino['id'] : null,
            'unom' => $uDestino['nombre'],
            'fec' => $ahora,
            'id_os' => $idOs,
        ]);

        self::registrarHistorial($db, [
            'id_os' => $idOs,
            'campo_validacion' => $campo,
            'accion' => 'correccion_autorizada',
            'usuario_id' => $uAut['id'] > 0 ? $uAut['id'] : null,
            'usuario_nombre' => $uAut['nombre'],
            'cargo_rol' => $uAut['rol_nombre'] ?: 'Administración',
            'estado_anterior' => (string)($os['estado'] ?? ''),
            'estado_nuevo' => (string)($os['estado'] ?? ''),
            'valor_anterior' => $valAnterior,
            'valor_nuevo' => $uDestino['nombre'] . ' | ' . $ahora,
            'motivo' => $motivo,
        ]);

        return ['ok' => true, 'mensaje' => 'Corrección autorizada registrada con trazabilidad completa en el historial.'];
    }

    /**
     * Puntos 5, 6, 7, 8, 9, 10, 11, 12, 13:
     * Evalúa automáticamente si la Orden de Servicio cumple criterio de ejecución:
     *  - Servicio por ensayo (Puntual): 100% de los ensayos/informes revisados y autorizados.
     *  - Servicio mensual: vencimiento del plazo de ejecución (fecha_fin <= hoy) sin informes ni
     *    actividades pendientes; si el plazo vence con pendientes, genera alerta y notifica.
     * Cuando se cumple, pasa automáticamente a "Ejecutada", sella la Casilla 3 y notifica a
     * Administración y Contabilidad sin intervención manual de Recepción / Atención al Cliente.
     *
     * @return array<string, mixed>
     */
    public static function evaluarYActualizarEjecucionAutomatica(PDO $db, int $idOs, ?string $fechaReferencia = null): array
    {
        if ($idOs <= 0 || !self::esquemaDisponible($db)) {
            return ['evaluada' => false];
        }

        $os = self::obtenerFilaOs($db, $idOs);
        if (!$os) {
            return ['evaluada' => false];
        }

        $estadoActual = (string)($os['estado'] ?? '');
        $esMensual = !empty($os['es_multimes'])
            || in_array((string)($os['tipo_contrato'] ?? ''), ['Marco', 'Contrato Activo', 'Contrato Largo', 'Abierto'], true);

        // Consultar ítems / matrices reales de la O/S (ya aislados por Hoja RT-FM-13 × Producto)
        $opModelo = new OperacionModelo();
        $items = $opModelo->obtenerItemsOS($idOs);

        // Excluir la línea comercial genérica de mensualidad (id_producto NULL) cuando existan ensayos reales o en mensual
        $itemsTecnicos = [];
        foreach ($items as $it) {
            $descL = mb_strtolower(trim((string)($it['descripcion_ensayo'] ?? '')), 'UTF-8');
            if ($esMensual && empty($it['id_producto']) && str_contains($descL, 'servicio mensual de laboratorio')) {
                continue;
            }
            $itemsTecnicos[] = $it;
        }

        $totalInformes = count($itemsTecnicos);
        $aprobados = 0;
        $pendientes = 0;
        $pendientesDetalles = [];

        foreach ($itemsTecnicos as $it) {
            $rev = function_exists('obtenerEstadoRevisionMatriz')
                ? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null)
                : ['estado' => 'pendiente'];
            if (($rev['estado'] ?? 'pendiente') === 'aprobada') {
                $aprobados++;
            } else {
                $pendientes++;
                $etq = trim((string)($it['descripcion_ensayo'] ?? 'Ensayo'));
                if (!empty($it['hoja_numero_registro'])) {
                    $etq .= ' (RT-FM-13 ' . $it['hoja_numero_registro'] . ')';
                }
                $pendientesDetalles[] = $etq;
            }
        }

        // En servicios mensuales, también verificar si hay Hojas RT-FM-13 con muestras sin matriz aprobada
        $hoy = ($fechaReferencia !== null && trim($fechaReferencia) !== '') ? trim($fechaReferencia) : date('Y-m-d');
        $fechaInicio = trim((string)($os['fecha_inicio'] ?? ''));
        $fechaFin = trim((string)($os['fecha_fin'] ?? ''));
        $plazoVencido = ($esMensual && $fechaFin !== '' && $hoy >= $fechaFin);

        $yaEjecutada = !empty($os['devuelto_sistema_fecha'])
            || in_array($estadoActual, ['Ejecutada', 'Finalizado', 'Cerrado', 'Archivado'], true);

        $resultado = [
            'evaluada' => true,
            'id_os' => $idOs,
            'es_mensual' => $esMensual,
            'total_informes' => $totalInformes,
            'aprobados' => $aprobados,
            'pendientes' => $pendientes,
            'plazo_vencido' => $plazoVencido,
            'transicion_ejecutada' => false,
            'alerta_generada' => false,
        ];

        if ($yaEjecutada) {
            return $resultado;
        }

        if ($esMensual) {
            if ($plazoVencido) {
                if ($pendientes > 0) {
                    // Punto 10: Alerta por plazo mensual vencido con informes/actividades pendientes
                    $listaCort = implode(', ', array_slice($pendientesDetalles, 0, 5));
                    if (count($pendientesDetalles) > 5) {
                        $listaCort .= ' y ' . (count($pendientesDetalles) - 5) . ' más';
                    }
                    $detalleAlerta = "Plazo mensual finalizado ({$fechaFin}) con {$pendientes} informe(s)/actividad(es) pendiente(s) de revisión y autorización: {$listaCort}.";

                    $alertaPrevia = (int)($os['estado_ejecucion_alerta'] ?? 0) === 1
                        && trim((string)($os['alerta_vencimiento_detalle'] ?? '')) === $detalleAlerta;

                    if (!$alertaPrevia) {
                        $db->prepare("
                            UPDATE ordenes_servicio
                            SET estado_ejecucion_alerta = 1,
                                alerta_vencimiento_detalle = :det
                            WHERE id = :id_os
                        ")->execute(['det' => $detalleAlerta, 'id_os' => $idOs]);

                        self::registrarHistorial($db, [
                            'id_os' => $idOs,
                            'campo_validacion' => 'alerta_vencimiento_mensual',
                            'accion' => 'alerta_plazo',
                            'usuario_id' => null,
                            'usuario_nombre' => 'Sistema CYCSA',
                            'cargo_rol' => 'Verificación Automática',
                            'estado_anterior' => $estadoActual,
                            'estado_nuevo' => $estadoActual,
                            'motivo' => $detalleAlerta,
                        ]);

                        self::crearNotificacionSiNoExiste($db, $idOs, 'alerta_vencimiento_mensual', self::AREA_ADMIN_CONTABILIDAD, 'Alerta de plazo mensual vencido con pendientes: ' . ($os['codigo_os'] ?? ''), $detalleAlerta);
                        self::crearNotificacionSiNoExiste($db, $idOs, 'alerta_vencimiento_mensual', self::AREA_LABORATORIO, 'Alerta de plazo mensual vencido con pendientes: ' . ($os['codigo_os'] ?? ''), $detalleAlerta);
                    }
                    $resultado['alerta_generada'] = true;
                    $resultado['alerta_detalle'] = $detalleAlerta;
                } else {
                    // Punto 9, 11, 12, 13: Plazo mensual cumplido sin pendientes -> Ejecutada + devolución automática
                    $motivoEj = "Plazo mensual cumplido ({$fechaInicio} al {$fechaFin}) sin informes ni actividades pendientes.";
                    self::marcarComoEjecutadaAutomaticamente($db, $os, $motivoEj);
                    $resultado['transicion_ejecutada'] = true;
                }
            }
        } else {
            // Servicio por ensayo (Puntual) — Puntos 7, 11, 12, 13
            if ($totalInformes > 0 && $pendientes === 0) {
                $motivoEj = "Totalidad de ensayos e informes revisados y autorizados ({$aprobados}/{$totalInformes}).";
                self::marcarComoEjecutadaAutomaticamente($db, $os, $motivoEj);
                $resultado['transicion_ejecutada'] = true;
            }
        }

        return $resultado;
    }

    /**
     * Sella Casilla 3 ("Devuelto automáticamente por el sistema"), cambia estado a "Ejecutada"
     * y notifica a Administración y Contabilidad (Puntos 11, 12 y 13).
     *
     * @param array<string, mixed> $os
     */
    private static function marcarComoEjecutadaAutomaticamente(PDO $db, array $os, string $motivo): void
    {
        $idOs = (int)$os['id'];
        $estadoAnt = (string)($os['estado'] ?? '');
        $ahora = date('Y-m-d H:i:s');

        $db->prepare("
            UPDATE ordenes_servicio
            SET estado = 'Ejecutada',
                devuelto_sistema_fecha = COALESCE(devuelto_sistema_fecha, :fec),
                devuelto_sistema_motivo = :mot,
                estado_ejecucion_alerta = 0,
                alerta_vencimiento_detalle = NULL
            WHERE id = :id_os
        ")->execute([
            'fec' => $ahora,
            'mot' => mb_substr($motivo, 0, 250, 'UTF-8'),
            'id_os' => $idOs,
        ]);

        self::registrarHistorial($db, [
            'id_os' => $idOs,
            'campo_validacion' => 'devuelto_sistema',
            'accion' => 'ejecucion_automatica',
            'usuario_id' => null,
            'usuario_nombre' => 'Sistema CYCSA',
            'cargo_rol' => 'Flujo Completado (Automático)',
            'estado_anterior' => $estadoAnt,
            'estado_nuevo' => 'Ejecutada',
            'valor_nuevo' => 'Flujo completado | ' . $ahora,
            'motivo' => $motivo,
        ]);

        $codOs = (string)($os['codigo_os'] ?? ('#' . $idOs));
        self::crearNotificacionSiNoExiste(
            $db,
            $idOs,
            'orden_ejecutada_pendiente_recepcion',
            self::AREA_ADMIN_CONTABILIDAD,
            "Orden {$codOs} ejecutada y pendiente de recepción definitiva",
            "La Orden de Servicio {$codOs} pasó automáticamente a estado Ejecutada ({$motivo}) y fue devuelta a Administración y Contabilidad para confirmar su recepción definitiva."
        );
    }

    /**
     * Obtiene el historial inmutable de validaciones de una O/S (Punto 16).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerHistorial(PDO $db, int $idOs): array
    {
        if ($idOs <= 0) {
            return [];
        }
        try {
            $stmt = $db->prepare("
                SELECT *
                FROM ordenes_servicio_validaciones_historial
                WHERE id_os = :id_os
                ORDER BY fecha_registro DESC, id DESC
            ");
            $stmt->execute(['id_os' => $idOs]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene notificaciones pendientes para Administración y Contabilidad (Punto 13)
     * o alertas de vencimiento mensual (Punto 10).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerNotificacionesPendientes(PDO $db, string $area = self::AREA_ADMIN_CONTABILIDAD, int $limite = 20): array
    {
        try {
            $stmt = $db->prepare("
                SELECT n.*, os.codigo_os, os.estado AS os_estado, os.nombre_proyecto
                FROM notificaciones_ordenes_servicio n
                JOIN ordenes_servicio os ON n.id_os = os.id
                WHERE n.destinatario_area = :area AND n.leida = 0
                ORDER BY n.fecha_creacion DESC, n.id DESC
                LIMIT " . max(1, (int)$limite)
            );
            $stmt->execute(['area' => $area]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function obtenerFilaOs(PDO $db, int $idOs): ?array
    {
        $stmt = $db->prepare("SELECT * FROM ordenes_servicio WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $idOs]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private static function registrarHistorial(PDO $db, array $datos): void
    {
        try {
            $db->prepare("
                INSERT INTO ordenes_servicio_validaciones_historial
                    (id_os, campo_validacion, accion, usuario_id, usuario_nombre, cargo_rol, estado_anterior, estado_nuevo, valor_anterior, valor_nuevo, motivo, fecha_registro)
                VALUES
                    (:id_os, :campo, :accion, :uid, :unom, :rol, :est_ant, :est_nvo, :val_ant, :val_nvo, :motivo, NOW())
            ")->execute([
                'id_os' => (int)$datos['id_os'],
                'campo' => (string)$datos['campo_validacion'],
                'accion' => (string)$datos['accion'],
                'uid' => $datos['usuario_id'] ?? null,
                'unom' => (string)($datos['usuario_nombre'] ?? 'Sistema CYCSA'),
                'rol' => $datos['cargo_rol'] ?? null,
                'est_ant' => $datos['estado_anterior'] ?? null,
                'est_nvo' => $datos['estado_nuevo'] ?? null,
                'val_ant' => $datos['valor_anterior'] ?? null,
                'val_nvo' => $datos['valor_nuevo'] ?? null,
                'motivo' => $datos['motivo'] ?? null,
            ]);
        } catch (\Throwable $e) {
        }
    }

    private static function crearNotificacionSiNoExiste(PDO $db, int $idOs, string $tipo, string $area, string $titulo, string $mensaje): void
    {
        try {
            $chk = $db->prepare("
                SELECT id FROM notificaciones_ordenes_servicio
                WHERE id_os = :id_os AND tipo = :tipo AND destinatario_area = :area AND leida = 0
                LIMIT 1
            ");
            $chk->execute(['id_os' => $idOs, 'tipo' => $tipo, 'area' => $area]);
            if ($chk->fetchColumn()) {
                return;
            }
            $db->prepare("
                INSERT INTO notificaciones_ordenes_servicio (id_os, tipo, destinatario_area, titulo, mensaje, leida, fecha_creacion)
                VALUES (:id_os, :tipo, :area, :titulo, :mensaje, 0, NOW())
            ")->execute([
                'id_os' => $idOs,
                'tipo' => $tipo,
                'area' => $area,
                'titulo' => mb_substr($titulo, 0, 195, 'UTF-8'),
                'mensaje' => $mensaje,
            ]);
        } catch (\Throwable $e) {
        }
    }
}
