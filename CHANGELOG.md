# CHANGELOG

Todas las novedades, cambios notables, correcciones y actualizaciones de CYCSA ERP & LIMS se documentarán en este archivo.

## [Sprint C.5] - Bitácora quién-hizo-qué (O/S, matriz, RT-FM-13)

### Añadido
- Migración `027_bitacora_entidades`: columnas nullable `entidad_tipo`, `entidad_id`, `id_os`, `usuario_rol` y `datos_json`, más índices por O/S, entidad y fecha. Idempotente. La bitácora sigue siendo append-only: la aplicación no expone UPDATE ni DELETE.
- `registrarBitacora` acepta un 5.º argumento opcional `?array $ctx`. Sin contexto, el INSERT legacy no toca las columnas nuevas. Con contexto escribe entidad, O/S, rol (o `$_SESSION['usuario_rol']`) y `datos_json`. Atajo `registrarEventoOS`.
- `obtenerBitacoraOS`: filas con `id_os`, más legado cuyo `id_referencia` es la O/S en los módulos de orden, operaciones, hojas y laboratorio. Quedan fuera las acciones cuyo id no es la O/S (matriz, congelado RT-FM-13, cambio de plantilla, roturas por muestra). Se anexa el historial JSON de revisión de matrices con `fuente=json`, sin sustituir `resultados_json`.
- Detalle de O/S y `GET /ordenes-servicio/bitacora` (AuthMiddleware). Acceso si el rol de sesión es 1, 2 o 3, o si el rol tiene `tienePermiso('bitacora','ver')`. El permiso explícito cubre a un laboratorista sin ampliar el panel global, que sigue restringido al rol 1. Filtros de usuario, acción, entidad, fechas e `id_os` en el panel y en la vista de la O/S.
- En captura de matriz, enlace a la bitácora de la O/S filtrada al ensayo. La barra de revisiones JSON permanece.

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
