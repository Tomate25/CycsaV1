-- ============================================================================
-- Migración 041: Cumplimiento CYCSA-PT-13 Ver. 5 Rev. 2 (ISO/IEC 17025:2017)
-- 1. Módulo de Remisiones de Entrega de Informes (CYCSA-RG-FM-41 / RLAB-MM-AAAA-N)
-- 2. Revisión en 3 etapas, Suplencia/Unificación y Checklist CYCSA-RT-FM-65 (25 ítems)
-- 3. Registro de Informes Anulados (bloqueo de reutilización de código - Sec. 5.6 b)
-- 4. Campos complementarios en Cotizaciones (RG-FM-31 V2R1) y Órdenes de Servicio (RG-FM-39 V1)
-- ============================================================================

CREATE TABLE IF NOT EXISTS remisiones_informes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_remision VARCHAR(50) NOT NULL UNIQUE COMMENT 'Formato RLAB-MM-AAAA-N según CYCSA-PT-13 Sec. 5.8 j',
    id_os INT NULL,
    id_cliente INT NULL,
    empresa_nombre VARCHAR(255) NOT NULL,
    modalidad_entrega ENUM('laboratorio', 'domicilio') NOT NULL DEFAULT 'laboratorio',
    fecha_entrega DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    persona_recibe VARCHAR(180) NULL,
    cedula_recibe VARCHAR(80) NULL,
    telefono_recibe VARCHAR(80) NULL,
    direccion_entrega VARCHAR(255) NULL,
    entregado_por VARCHAR(180) NULL,
    observaciones TEXT NULL,
    estado ENUM('Emitida', 'Entregada', 'Anulada') NOT NULL DEFAULT 'Emitida',
    motivo_anulacion TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rem_os (id_os),
    INDEX idx_rem_cliente (id_cliente),
    INDEX idx_rem_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remisiones_informes_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_remision INT NOT NULL,
    id_detalle_cotizacion INT NULL,
    id_informe_control INT NULL,
    codigo_informe VARCHAR(120) NOT NULL,
    descripcion_ensayo VARCHAR(255) NULL,
    formato_entrega ENUM('Digital PDF', 'Impreso Original', 'Ambos') NOT NULL DEFAULT 'Digital PDF',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_remdet_rem (id_remision),
    INDEX idx_remdet_det (id_detalle_cotizacion),
    CONSTRAINT fk_remdet_remision FOREIGN KEY (id_remision) REFERENCES remisiones_informes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS informes_revisiones_pt13 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_detalle_cotizacion INT NOT NULL,
    id_os INT NULL,
    codigo_informe VARCHAR(120) NULL,
    -- Etapa 1: Revisión Técnica (Coordinador de Laboratorio)
    revisor_tecnico_id INT NULL,
    revisor_tecnico_nombre VARCHAR(160) NULL,
    fecha_rev_tecnica DATETIME NULL,
    obs_rev_tecnica TEXT NULL,
    -- Etapa 2: Revisión de Integridad y Forma (Asistente de Coordinación)
    asistente_coord_id INT NULL,
    asistente_coord_nombre VARCHAR(160) NULL,
    fecha_rev_forma DATETIME NULL,
    obs_rev_forma TEXT NULL,
    -- Etapa 3: Autorización y Liberación (Responsable de Calidad)
    autorizador_calidad_id INT NULL,
    autorizador_calidad_nombre VARCHAR(160) NULL,
    fecha_autorizacion DATETIME NULL,
    -- Unificación de funciones / Suplencia (PT-13 Sec. 5.1 a)
    es_unificacion_suplencia TINYINT(1) NOT NULL DEFAULT 0,
    suplencia_funciones VARCHAR(255) NULL,
    suplencia_motivo TEXT NULL,
    suplencia_referencia_autorizacion VARCHAR(180) NULL,
    -- Checklist CYCSA-RT-FM-65 (25 ítems ISO/IEC 17025:2017)
    checklist_json LONGTEXT NULL,
    -- Campos condicionales PT-13 Ver. 5 Rev. 2
    laboratorio_externo_nombre VARCHAR(200) NULL,
    es_laboratorio_externo TINYINT(1) NOT NULL DEFAULT 0,
    adiciones_desviaciones TEXT NULL,
    opiniones_interpretaciones TEXT NULL,
    incertidumbre_medicion VARCHAR(255) NULL,
    solicitud_simplificacion_ref VARCHAR(255) NULL,
    estado_pt13 ENUM('Pendiente', 'En Revision Tecnica', 'En Revision Forma', 'Listo Para Calidad', 'Autorizado', 'Observado', 'Anulado') NOT NULL DEFAULT 'Pendiente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rev_pt13_detalle (id_detalle_cotizacion),
    INDEX idx_rev_pt13_os (id_os)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS informes_anulados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_informe VARCHAR(120) NOT NULL UNIQUE COMMENT 'Bloquea reutilización según PT-13 Sec. 5.6 b',
    id_detalle_cotizacion INT NULL,
    id_os INT NULL,
    motivo TEXT NOT NULL,
    usuario_id INT NULL,
    usuario_nombre VARCHAR(160) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_anulado_os (id_os),
    INDEX idx_anulado_det (id_detalle_cotizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campos complementarios en cotizaciones (CYCSA-RG-FM-31 V2R1)
ALTER TABLE cotizaciones
    ADD COLUMN IF NOT EXISTS direccion_cliente VARCHAR(255) NULL AFTER atencion_a;

-- Campos complementarios en ordenes_servicio (CYCSA-RG-FM-39 V1)
ALTER TABLE ordenes_servicio
    ADD COLUMN IF NOT EXISTS direccion_cliente VARCHAR(255) NULL AFTER atencion_a,
    ADD COLUMN IF NOT EXISTS ubicacion_proyecto VARCHAR(255) NULL AFTER nombre_proyecto,
    ADD COLUMN IF NOT EXISTS documentos_vinculados_json LONGTEXT NULL AFTER contactos_json;
