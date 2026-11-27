-- Migración 043: Matrices y entradas de Laboratorio independientes por cada (Hoja RT-FM-13 × Producto)
-- Permite que cada RT-FM-13 creada en una O/S genere su propia recepción de datos,
-- captura técnica, matriz/documento en Operaciones y entrada independiente en Laboratorio por producto asignado.

CREATE TABLE IF NOT EXISTS hojas_matrices_resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_os INT NOT NULL,
    id_hoja INT NOT NULL,
    id_detalle_cotizacion INT NOT NULL,
    resultados_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_hoja_detalle (id_hoja, id_detalle_cotizacion),
    INDEX idx_hmr_os (id_os),
    INDEX idx_hmr_detalle (id_detalle_cotizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE recepcion_muestras
    ADD COLUMN IF NOT EXISTS id_detalle_cotizacion INT NULL AFTER id_hoja;

CREATE INDEX IF NOT EXISTS idx_rm_hoja_detalle ON recepcion_muestras (id_hoja, id_detalle_cotizacion);
