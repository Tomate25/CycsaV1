# 📘 Guía General del Sistema: CYCSA ERP & LIMS

Este documento proporciona una visión detallada de la arquitectura, módulos funcionales, estructura de archivos y rutas principales de la plataforma **CYCSA ERP & LIMS** (Laboratory Information Management System y Enterprise Resource Planning).

---

## 🏢 1. ¿Qué es y qué hace el sistema?

La plataforma es un sistema integrado a medida para **Consultoría y Construcción S.A. (CYCSA)**. Su propósito es unificar el flujo comercial de la empresa, el control técnico de calidad del laboratorio bajo la norma internacional **ISO/IEC 17025** (imparcialidad y trazabilidad de ensayos), y la administración contable de la empresa.

El sistema se compone de cuatro grandes áreas de negocio interconectadas:

```
[ Cotizaciones ] ──> [ Órdenes de Servicio ] ──> [ LIMS (Operaciones / Lab) ] ──> [ Contabilidad (CxC / Ledger) ]
```

### A. Módulo Comercial (Cotizaciones)
* Creación y edición de cotizaciones vinculadas a clientes.
* Carga de ensayos específicos con códigos de servicio, tarifas y normas ASTM.
* Envío de cotizaciones para revisión interna y posterior envío formal al cliente vía correo electrónico.
* Enlace público interactivo para que el cliente pueda aprobar o rechazar la cotización en línea.

### B. Módulo de Operaciones & LIMS
* Conversión de cotizaciones aprobadas en **Órdenes de Servicio (O/S)**.
* **Logística de Muestreo**: Programación de rutas de recolección en campo, asignando técnicos y vehículos.
* **Hoja de Campo**: Registro digital de la toma de muestras in-situ.
* **Hoja de Solicitud de Servicio**: Documento físico/digital formal de entrega de muestras firmado por el cliente.
* **Recepción e Ingreso de Muestras**: Registro formal de entrada al laboratorio (recepción parcial o total), generación automática de códigos de laboratorio (ej: `MS-2026-0001`) y programación de edades de ruptura (días como 3d, 7d, 28d, etc.) asignando especímenes individuales (ej: Cilindros A, B, C).
* **Control de Rupturas por Prensa**: Registro de carga (lb) y área (in²), cálculo automático de la resistencia (PSI y Kg/cm²) y alertas de regresión de resistencia (cuando un cilindro a mayor edad tiene menor resistencia que uno más joven).
* **Generación de Reportes PDF**: Emisión de reportes firmados y validados:
  * *Informe Parcial*: Filtra y reporta únicamente los resultados de una edad de ruptura específica (ej: reporte de cilindros a los 7 días).
  * *Informe Consolidado*: Muestra la evolución e historial completo de todas las edades del lote.

### C. Módulo de Laboratorio Ciego (ISO/IEC 17025)
* Diseñado para el laboratorista técnico (Rol `6`).
* **Vista Ciega de Imparcialidad**: El técnico solo ve el código de laboratorio (ej: `MS-2026-0002-A`) y las especificaciones físicas de moldeo. **No tiene acceso a ver el nombre del cliente, el nombre del proyecto ni el costo del servicio**, garantizando que el ensayo se realice con absoluta transparencia e imparcialidad bajo normas de calidad.

### D. Módulo Contable (ERP)
* **Catálogo de Cuentas**: Gestión dinámica de cuentas contables de activos, pasivos, patrimonio, ingresos y costos.
* **Cuentas por Cobrar (CxC)**: Generadas automáticamente a partir de la facturación comercial de las O/S.
* **Cuentas por Pagar (CxP)**: Gestión de pasivos comerciales.
* **Módulo de Bancos**: Registro de cuentas bancarias y conciliación de movimientos de efectivo.
* **Libro Diario (Partidas de Diario)**: Registro de transacciones contables de doble entrada (Debe/Haber).
* **Estados Financieros**: Generación automatizada en tiempo real del **Balance General** y del **Estado de Resultados (Pérdidas y Ganancias)**.

---

## 📂 2. Estructura de Directorios

El proyecto sigue un patrón de diseño **MVC (Modelo-Vista-Controlador)** limpio y modular en PHP puro sin frameworks pesados, optimizando la velocidad de respuesta en el servidor local:

* 📁 **`almacenamiento/`**: Guarda físicamente los archivos dinámicos generados (PDFs de hojas de solicitud, informes técnicos).
* 📁 **`ayudantes/`**: Contiene helpers y funciones globales compartidas, como `funciones.php` (lógica de renderizado de Dompdf).
* 📁 **`configuracion/`**: Archivos de conexión y configuración global.
* 📁 **`datos_ensayos_markdown/`**: Almacena las plantillas descriptivas de ensayos en formato Markdown (`.md`) y el esquema JSON de columnas (`formatos_schema.json`).
* 📁 **`modulos/`**: Contiene la lógica del negocio separada en módulos independientes. Cada módulo tiene sus propios subdirectorios **`modelos/`**, **`vistas/`** y **`controladores/`**:
  * `autenticacion/` -> Lógica de login, logout y sesiones.
  * `clientes/` -> Catálogo y búsquedas de clientes.
  * `configuracion/` -> Parámetros comerciales (vehículos, técnicos).
  * `contabilidad/` -> Cuentas contables, diario, bancos, CxC, CxP.
  * `cotizaciones/` -> Propuestas comerciales.
  * `operaciones/` -> Flujo de O/S, muestreo, recepción de laboratorio, rupturas y reportes.
  * `productos/` -> Catálogo de ensayos y servicios.
  * `usuarios/` -> Cuentas de usuario, roles y permisos del sistema.
* 📁 **`nucleo/`**: El núcleo del framework MVC a medida (Clase de aplicación, Request, Response, Enrutador y Conexión PDO).
* 📁 **`plantillas/`**: Marcos comunes de maquetación HTML (encabezado, menú lateral, pie de página).
* 📁 **`publico/`**: Archivos accesibles públicamente (Assets de CSS, JavaScript, imágenes, y el archivo `index.php` que actúa como punto de entrada de la aplicación).
* 📁 **`rutas/`**: Archivos que definen la correspondencia de las URLs con los controladores (`web.php`).

---

## 🔗 3. Rutas y Endpoints Importantes

Las rutas se definen en el enrutador central del sistema en [web.php](file:///C:/xampp/htdocs/Cycsa/rutas/web.php). A continuación se destacan los endpoints más importantes de cada sección:

### 🔑 Autenticación y Panel
* `GET /login` -> Muestra el formulario de inicio de sesión.
* `POST /login` -> Procesa las credenciales del usuario.
* `GET /panel` -> Carga el dashboard principal según el rol del usuario logueado.
* `GET /panel/bitacora` -> Historial de auditoría interna de cambios en el sistema.

### 📄 Módulo Comercial
* `GET /cotizaciones` -> Lista general de cotizaciones.
* `GET /cotizaciones/crear` -> Formulario para armar una nueva propuesta.
* `POST /cotizaciones/decision-cliente` -> Enlace público donde el cliente aprueba/rechaza en línea.
* `POST /operaciones/crear-os` -> Convierte una cotización aprobada en una Orden de Servicio (O/S).

### 🧪 Módulo LIMS & Operaciones (Control de Calidad)
* `GET /operaciones` -> Panel del supervisor/administrador con las O/S en proceso, pendientes de recepción, asignación o ejecución.
* `GET /operaciones/hoja-solicitud` -> Generación formal de la Hoja de Solicitud de muestras.
* `GET /operaciones/recepcion` -> Formulario para recibir las muestras en laboratorio y programar sus cilindros por edades (1d, 3d, 7d, 28d, etc.).
* `GET /operaciones/detalle-lote` -> Vista detallada del lote recibido. Muestra el **Cronograma de Rupturas** y los resultados acumulados.
* `POST /operaciones/guardar-ruptura` -> Registra los datos de trituración de cilindros de un lote (Supervisor / Admin).
* `POST /operaciones/generar-informe` -> Genera y versiona los reportes parciales (por edad) o consolidados finales en formato PDF.

### 🙈 Módulo de Laboratorio (Vista Ciega)
* `GET /laboratorio` -> Panel del técnico de laboratorio. Lista muestras usando únicamente códigos de barra/laboratorio.
* `GET /laboratorio/detalle-muestra` -> Detalle para el laboratorista. Permite ver el cronograma de rupturas del día de hoy.
* `POST /laboratorio/guardar-ruptura` -> Registra la carga y área del cilindro roto (sin ver datos del cliente o cobros).

### 📊 Módulo Contable (ERP)
* `GET /contabilidad/cuentas` -> Visualiza y gestiona el plan de cuentas.
* `GET /contabilidad/cxc` -> Listado de cuentas por cobrar. Permite registrar abonos y pagos de facturas comerciales.
* `GET /contabilidad/cxp` -> Listado de cuentas por pagar a proveedores.
* `GET /contabilidad/bancos` -> Conciliación y estados de cuenta bancarios.
* `GET /contabilidad/diario` -> Muestra el libro diario. Permite registrar partidas manuales o sincronizar facturas automáticas.
* `GET /contabilidad/balance` -> Genera el Balance General (Activos vs Pasivos + Patrimonio).
* `GET /contabilidad/resultados` -> Genera el Estado de Resultados (Ventas - Costos = Utilidad).

---

## 🛠️ 4. Requerimientos de Ejecución

* **Servidor Web**: Apache (Apache HTTP Server).
* **Base de Datos**: MySQL / MariaDB (Nombre: `cycsa_db`).
* **Intérprete**: PHP 7.4 o superior (compatible con PHP 8.x).
* **Librerías Requeridas**: 
  * `PDO` y `PDO_MySQL` para conexión a base de datos.
  * Extensiones `gd` o `mbstring`.
  * `dompdf/dompdf` para generación de reportes en PDF (gestionado vía Composer).
* **Entorno de Red Local**: Configurado por defecto en puerto `80` (XAMPP). Se expone externamente en redes mediante herramientas de túnel como `cloudflared`.

---

*Documento técnico de referencia desarrollado para CYCSA S.A. Todos los derechos reservados.*
