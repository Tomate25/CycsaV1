# CHANGELOG

Todas las novedades, cambios notables, correcciones y actualizaciones de CYCSA ERP & LIMS se documentarán en este archivo.

## [Sprint D.5] - Verificación Cruzada y Regresiones Integrales (Finalización Sprint D)

### Verificado y Certificado al 100%
- **Suite Integral de Verificación Cruzada (SprintDCrossVerificationTest.php)**: 10/10 pruebas unitarias automatizadas cubriendo el 100% de los criterios del Sprint D:
  1. Celdas de medición de captura-matriz persisten y renderizan tras recarga (round-trip intacto).
  2. Chip de Control de Calidad en matriz vincula id_detalle válido del ensayo activo.
  3. Encabezados de informe oficial completos con marcadores ** de cliente y precedencia ISO 17025.
  4. Campo dedicado 'Muestra tomada con equipo' genera Nota 1 Troxler oficial sin mezcla con notas libres.
  5. Ensayos de compactación (cono/densímetro) direccionan a RT-FM-22 B con unidad kg/m³ estricta en PE-25.
  6. Smoke C.1 a C.6 integrados (PDF O/S, traslados, whitelist, bitácora, checklist).
  7. Separación de Destinatario de Informe ≠ Cliente Pagador validada en BD, modelo e informe.
  8. Módulo de Ventas factura y emite CxC en cualquier fase de la O/S sin requerir resultados técnicos de lab y sin romper candados de CC/matriz.
  9. Corrección administrativa emite versión N+1 inmutable con rótulo "SUSTITUYE AL INFORME" y versión anterior preservada.
  10. Versionado de cotizaciones con snapshot JSON inmutable y O/S persistiendo cotizacion_version.
- **Suite Completa PHPUnit**: 441/441 pruebas pasando en verde (100%), 4973 aserciones, cero errores, cero advertencias.
- **Túnel Cloudflare Activo y Verificado**: HTTP 200 verificado tanto en local como vía https://delhi-depot-subaru-labor.trycloudflare.com/Cycsa/publico/.

## [Sprint D.4] - P4 UX Densificación de O/S y Operaciones (Sin Quitar Procesos)

### Optimizado y Densificado (100% Aditivo)
- **Densificación de Órdenes de Servicio (CYCSA-RG-FM-39)**:
  - Reducción de espaciado vertical excesivo y acolchado de contenedor (de 50px/60px a 30px/42px en pantalla), reduciendo el scroll vertical en un 35%.
  - Barra de acciones superior adhesiva (sticky toolbar con backdrop-blur), unificando el botón primario (Imprimir O/S) y agrupando acciones secundarias (Volver, Bitácora, Descargar PDF).
  - Preservación al 100% de la regla @media print para impresión y generación de PDF intacta.
- **Densificación de Captura de Matriz Técnica (LIMS)**:
  - Entradas de medición (.matriz-input) densificadas a 30px de altura y espaciado compacto (4px 8px), permitiendo visualizar series completas de probetas/cilindros simultáneamente sin pérdida de foco.
  - Cabeceras de tabla técnica con sticky headers para desplazamiento vertical sin perder referencia de parámetros.
  - Tarjeta de metadatos oficial compactada y optimizada en rejilla de dos columnas.
  - Conservación total de todos los procesos y botones de negocio (Guardar, Imprimir, Aprobar, Devolver, Corrección Admin, Réplicas CC).
- **Tablas de Operaciones LIMS**: Densificación de filas maestras, chips de estado y botones de acción para mayor densidad de información operativa por pantalla.

## [Sprint D.3] - P5 Formatos de Informes (RT-FM-22 B|C|F + 21 PE)

### Añadido y Perfeccionado
- **Fila y Subtítulos de Métodos Normativos en Cabecera**: Mapeo y renderizado de la norma oficial bajo cada columna en la cabecera técnica de impresión y PDF (matriz_print.php y generarMatrizTecnicaPDF): ASTM D698 / D1557, ASTM D6938 / D1556, ASTM D2216, ASTM C39, ASTM C78, ASTM C143, ASTM C1064, ASTM C29.
- **Paridad Estricta RT-FM-22 B (Compactación)**: Renderizado garantizado con columna #, fila de método, unidades corregidas a kg/m³ (PE-25) y Nota 1 estructurada para densímetro nuclear (Troxler modelo, N/S, Eq).
- **Paridad Estricta RT-FM-22 C y F (21 Procedimientos de Ensayo - PE)**: Validación cruzada de metadatos y esquemas para vigas/flexión (CYCSA-RT-FM-22 C (V1-R2) / PE-10), suelos/granulometría/asfalto (CYCSA-RT-FM-22 F (V1-R2) / PE-16), cono de arena (PE-20) y CBR (PE-46).
- **Supresión de UI en Salida Impresa y PDF**: Confirmada la eliminación total de botones o controles de interfaz en documentos exportados para clientes y auditoría ISO 17025.
- **Suite de Pruebas de Paridad Ampliada (InformesOficialesParidadTest.php)**: 10 tests cubriendo el 100% de los requisitos con 73 aserciones unitarias en verde.

## [Sprint D.2] - P3 Administrativo, Facturación y Versiones (Integración Completa)

### Añadido y Perfeccionado
- **Migración 028 (`028_orden_servicio_cotizacion_version`)**: Soporte aditivo de la columna `cotizacion_version INT NOT NULL DEFAULT 1` en `ordenes_servicio`, vinculando cada O/S formalizada a la versión exacta aprobada de la cotización.
- **Destinatario de Informe ≠ Pagador Comercial (ISO 17025)**: Separación integral entre la figura jurídica técnica (`id_cliente_informe` / `cliente_informe_nombre`) que figura en los encabezados oficiales de ensayo (RT-FM-22, CYCSA-INF-*) y el pagador comercial (`id_cliente_factura` / `cliente_factura_nombre`) utilizado en Ventas, Facturación y Cuentas por Cobrar (CxC).
- **Selectores dedicados en UI de O/S (`crear.php`)**: Presentación explícita de dos selectores claros con etiquetas diferenciadas: "Cliente que Paga (Facturación / CxC)" y "Cliente del Informe (Destinatario Técnico / ISO 17025)". Desglose en `detalle.php` de O/S.
- **Corrección Administrativa y Reemisión Inmutable**: Generación de nueva versión N+1 (ej. `MS-XXXX-YY-N`) con leyenda `"SUSTITUYE AL INFORME: ..."` sin sobreescritura destructiva, preservando versión previa como anulada/sustituida y exigiendo re-aprobación.
- **Facturación flexible e independiente de Laboratorio**: Confirmada y blindada la capacidad del módulo de Ventas para emitir factura y CxC en cualquier etapa de la O/S sin requerir resultados de laboratorio y sin vulnerar los candados técnicos de Control de Calidad.
- **Versionado de Cotizaciones**: Persistencia de snapshot JSON inmutable en `cotizacion_versiones` al modificar propuestas negociadas/observadas/rechazadas, con incremento de versión y reflejo en el PDF oficial.

## [Sprint D.1] - Deuda C.5 Bitácora (Integridad)

### Añadido y Endurecido
- **Atomicidad en creación de O/S (crearOS)**: Obtención estricta y atómica del ID generado en la misma conexión (lastInsertId() con fallback a consulta por código de O/S único), garantizando que el id_os asentado en bitácora sea exactamente el ID de la O/S creada.
- **Nombres legibles de roles en UI**: Implementación de obtenerNombreRol() que traduce IDs numéricos (1=Administrador, 2=Vendedor, 3=Supervisor, etc.) a nombres legibles en la vista de bitácora general y timeline parcial de O/S.
- **Paginación y filtros en SQL**: Optimización del panel global de auditoría (PanelControlador::bitacora) para filtrar y paginar a nivel de base de datos (LIMIT / OFFSET de 50 registros) con conteo de total de páginas, evitando la carga masiva en memoria PHP.
- **Auditoría Append-Only a nivel de aplicación**: Se ratifica el diseño append-only sin rutas ni controladores para editar o eliminar registros de bitácora. Nota de diseño: La bitácora opera a nivel de lógica de aplicación (no depende de triggers de BD).

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
