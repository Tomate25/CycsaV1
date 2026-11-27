-- Migración 044: Bandera operativa en cotizacion_detalles para ensayos incluidos
-- automáticamente desde el Anexo Técnico de Alcance del Servicio Mensual.
-- Mantiene los totales comerciales intactos (precio_unitario = 0.00, subtotal = 0.00)
-- y permite ocultarlos de la cotización comercial RG-FM-31 mientras quedan habilitados
-- en Orden de Servicio, Hojas RT-FM-13, Captura Técnica, Matrices y Laboratorio.

ALTER TABLE cotizacion_detalles
    ADD COLUMN IF NOT EXISTS es_incluido_anexo_mensual TINYINT(1) NOT NULL DEFAULT 0 AFTER observaciones;
