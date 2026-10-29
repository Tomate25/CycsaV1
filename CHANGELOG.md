# CHANGELOG

Todas las novedades, cambios notables, correcciones y actualizaciones de CYCSA ERP & LIMS se documentarán en este archivo.

## [Sprint C.4] - Bodegas y traslados de equipos

### Añadido
- Migración `026_equipos_bodegas_traslados`: bodegas (central, proyecto, taller, externo), ubicación vigente de cada equipo de calibración y bitácora append-only de traslados (quién, cuándo, origen, destino y motivo).
- Semilla idempotente de la bodega `LAB-CENTRAL` y alta de cada equipo activo que aún no tiene ubicación. No se crean bodegas de obra en la migración; hay una sola bodega por proyecto.
- Módulo `EquiposBodegas` (listado, ficha de bodega, ficha de equipo y traslado con CSRF) junto a `/equipos-calibracion`.
- Columna **Ubicación** en el inventario de equipos de calibración y tarjeta **Equipos en obra** en el detalle de proyecto.

## [v2.0] - ERP & LIMS Completo

### Añadido
- **Módulo de Muestras y Laboratorio (LIMS)**: Gestión de hojas de solicitud, recepción de muestras y generación de informes de control.
- **Módulo de Ventas y Cotizaciones**: Creación de cotizaciones y su conversión automática a órdenes de servicio.
- **Módulo Financiero**: Gestión de Cuentas por Cobrar (CXC) y Cuentas por Pagar (CXP).
- **Seguridad**: Autenticación, control de roles y permisos configurables (Administrador, Gerente, Laboratorista, Ventas).
- **Auditoría**: Bitácora de transacciones para rastrear las acciones de los usuarios en la base de datos.
- **Configuración Inicial (Seeders)**: Scripts de llenado (Seeders) para roles, permisos, normas de laboratorio (ASTM) y parámetros de la empresa.
- **Migraciones de Base de Datos**: Esquemas SQL bien estructurados con optimización a través de índices de rendimiento.

### Planificado (Módulos Futuros)
- **Inventario**: Control de reactivos e insumos con niveles mínimos de stock.
- **Compras**: Emisión de órdenes de compra para reabastecimiento.
- **Recursos Humanos (RRHH)**: Registro de empleados, puestos y salarios.
- **Activos Fijos y Calibración**: Control de equipos e instrumentos de laboratorio.
- **Mantenimiento**: Programación de mantenimientos preventivos y correctivos para equipos y maquinaria.
