-- 002_performance_indexes.sql
-- Índices para optimizar consultas

-- Índices para la tabla usuarios
CREATE INDEX idx_usuarios_id_rol ON usuarios(id_rol);

-- Índices para la tabla cotizaciones
CREATE INDEX idx_cotizaciones_cliente_id ON cotizaciones(cliente_id);
CREATE INDEX idx_cotizaciones_fecha ON cotizaciones(fecha);
CREATE INDEX idx_cotizaciones_estado ON cotizaciones(estado);

-- Índices para la tabla ordenes_servicio
CREATE INDEX idx_ordenes_cliente_id ON ordenes_servicio(cliente_id);
CREATE INDEX idx_ordenes_codigo ON ordenes_servicio(codigo);
CREATE INDEX idx_ordenes_fecha ON ordenes_servicio(fecha);
CREATE INDEX idx_ordenes_estado ON ordenes_servicio(estado);

-- Índices para la tabla hojas_solicitud
CREATE INDEX idx_hojas_orden_id ON hojas_solicitud(orden_id);

-- Índices para la tabla recepcion_muestras
CREATE INDEX idx_muestras_orden_id ON recepcion_muestras(orden_id);
CREATE INDEX idx_muestras_codigo ON recepcion_muestras(codigo_muestra);
CREATE INDEX idx_muestras_estado ON recepcion_muestras(estado);

-- Índices para la tabla informes_control
CREATE INDEX idx_informes_muestra_id ON informes_control(muestra_id);
CREATE INDEX idx_informes_estado ON informes_control(estado);

-- Índices para cuentas por cobrar
CREATE INDEX idx_cxc_cliente_id ON cxc(cliente_id);
CREATE INDEX idx_cxc_estado ON cxc(estado);
