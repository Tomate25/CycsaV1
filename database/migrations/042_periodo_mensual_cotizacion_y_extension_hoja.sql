-- Migración 042: Modalidad mensual / período de habilitación desde Cotización
-- y registro de ampliaciones de período y productos desde Hoja de Servicio (RT-FM-13)

ALTER TABLE cotizaciones
    ADD COLUMN IF NOT EXISTS es_mensual TINYINT(1) NOT NULL DEFAULT 0 AFTER vigencia_oferta,
    ADD COLUMN IF NOT EXISTS duracion_meses INT NULL AFTER es_mensual,
    ADD COLUMN IF NOT EXISTS fecha_inicio_periodo DATE NULL AFTER duracion_meses,
    ADD COLUMN IF NOT EXISTS fecha_fin_periodo DATE NULL AFTER fecha_inicio_periodo,
    ADD COLUMN IF NOT EXISTS periodicidad VARCHAR(50) NULL DEFAULT 'Mensual' AFTER fecha_fin_periodo;

CREATE TABLE IF NOT EXISTS ordenes_servicio_ampliaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_os INT NOT NULL,
    id_hoja INT NULL,
    duracion_meses_anterior INT NULL,
    duracion_meses_nueva INT NULL,
    fecha_fin_anterior DATE NULL,
    fecha_fin_nueva DATE NULL,
    periodicidad VARCHAR(50) NULL,
    productos_cambio_json LONGTEXT NULL,
    motivo TEXT NULL,
    usuario_id INT NULL,
    usuario_nombre VARCHAR(160) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_amp_os (id_os)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
