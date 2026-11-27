-- Migración 045: Validaciones automatizadas CYCSA-RG-FM-39 V1R1 (4 firmas digitales
-- con trazabilidad inmutable, ejecución automática, alertas de vencimiento mensual y
-- notificaciones a Administración y Contabilidad).

ALTER TABLE ordenes_servicio
    ADD COLUMN IF NOT EXISTS autorizado_admin_usuario_id INT NULL AFTER estado,
    ADD COLUMN IF NOT EXISTS autorizado_admin_nombre VARCHAR(180) NULL AFTER autorizado_admin_usuario_id,
    ADD COLUMN IF NOT EXISTS autorizado_admin_fecha DATETIME NULL AFTER autorizado_admin_nombre,
    ADD COLUMN IF NOT EXISTS recibido_lab_usuario_id INT NULL AFTER autorizado_admin_fecha,
    ADD COLUMN IF NOT EXISTS recibido_lab_nombre VARCHAR(180) NULL AFTER recibido_lab_usuario_id,
    ADD COLUMN IF NOT EXISTS recibido_lab_fecha DATETIME NULL AFTER recibido_lab_nombre,
    ADD COLUMN IF NOT EXISTS devuelto_sistema_fecha DATETIME NULL AFTER recibido_lab_fecha,
    ADD COLUMN IF NOT EXISTS devuelto_sistema_motivo VARCHAR(255) NULL AFTER devuelto_sistema_fecha,
    ADD COLUMN IF NOT EXISTS estado_ejecucion_alerta TINYINT(1) NOT NULL DEFAULT 0 AFTER devuelto_sistema_motivo,
    ADD COLUMN IF NOT EXISTS alerta_vencimiento_detalle TEXT NULL AFTER estado_ejecucion_alerta,
    ADD COLUMN IF NOT EXISTS recibido_admin_final_usuario_id INT NULL AFTER alerta_vencimiento_detalle,
    ADD COLUMN IF NOT EXISTS recibido_admin_final_nombre VARCHAR(180) NULL AFTER recibido_admin_final_usuario_id,
    ADD COLUMN IF NOT EXISTS recibido_admin_final_fecha DATETIME NULL AFTER recibido_admin_final_nombre;

CREATE TABLE IF NOT EXISTS ordenes_servicio_validaciones_historial (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_os INT NOT NULL,
    campo_validacion VARCHAR(60) NOT NULL,
    accion VARCHAR(60) NOT NULL,
    usuario_id INT NULL,
    usuario_nombre VARCHAR(180) NOT NULL,
    cargo_rol VARCHAR(120) NULL,
    estado_anterior VARCHAR(100) NULL,
    estado_nuevo VARCHAR(100) NULL,
    valor_anterior TEXT NULL,
    valor_nuevo TEXT NULL,
    motivo TEXT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_os_val_hist_os (id_os, fecha_registro),
    KEY idx_os_val_hist_campo (id_os, campo_validacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificaciones_ordenes_servicio (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_os INT NOT NULL,
    tipo VARCHAR(60) NOT NULL,
    destinatario_area VARCHAR(80) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    leida_por INT NULL,
    fecha_leida DATETIME NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_os_area_leida (destinatario_area, leida, fecha_creacion),
    KEY idx_notif_os_id (id_os, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
