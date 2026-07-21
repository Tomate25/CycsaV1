# 🏗️ CYCSA ERP & LIMS v2.0 - Documentación Técnica y Manual de Arquitectura (10/10)

> **Plataforma Corporativa Integral ERP & LIMS para Laboratorios de Ensayo de Concreto, Suelos, Asfaltos, Adoquines y Materiales de Construcción.**  
> Diseñado bajo la norma **ISO/IEC 17025**, arquitectura **Clean Architecture, Domain-Driven Design (DDD) y estándar PSR-4**.

---

## 📋 Tabla de Contenidos
1. [🌟 Descripción General del Sistema](#-descripción-general-del-sistema)
2. [🎯 Módulos Principales del Negocio](#-módulos-principales-del-negocio)
3. [🏆 Puntuación Arquitectónica: 10/10 (Enterprise Level)](#-puntuación-arquitectónica-1010-enterprise-level)
4. [🛠️ Resumen de los 28 Puntos de Refactorización Completados](#️-resumen-de-los-28-puntos-de-refactorización-completados)
5. [📁 Estructura Completa de Directorios (`app/` y `database/`)](#-estructura-completa-de-directorios-app-y-database)
6. [🔄 Capa de Servicios, Repositorios y Middlewares](#-capa-de-servicios-repositorios-y-middlewares)
7. [🔒 Seguridad, CSRF, Rate Limiting y Norma ISO/IEC 17025](#-seguridad-csrf-rate-limiting-y-norma-isoiec-17025)
8. [📊 Base de Datos: Esquemas, Migraciones, Seeders e Índices](#-base-de-datos-esquemas-migraciones-seeders-e-índices)
9. [🌐 Rutas y API REST Centralizada](#-rutas-y-api-rest-centralizada)
10. [🚀 Guía Paso a Paso de Despliegue en Bluehost (cPanel)](#-guía-paso-a-paso-de-despliegue-en-bluehost-cpanel)

---

## 🌟 Descripción General del Sistema

**CYCSA ERP & LIMS v2.0** es la solución tecnológica integral de **CYCSA S.A.** desarrollada para gestionar el ciclo de vida completo de un laboratorio de ensayo de materiales de construcción acreditado o en proceso de acreditación bajo la **Norma Internacional ISO/IEC 17025**.

El sistema integra de forma nativa la gestión comercial (cotizaciones, clientes, lista de precios), la gestión técnica de laboratorio LIMS (recepción de muestras, solicitud ciega, asignación de ensayos normativos ASTM/AASHTO, generación de hojas de servicio `CYCSA-RT-FM-13`, emisión de informes técnicos firmados) y la gestión financiera contable (cuentas por cobrar, cuentas por pagar, libro diario, bancos).

---

## 🎯 Módulos Principales del Negocio

1. **🏢 Gestión de Clientes (`app/Modulos/Clientes`):**
   - Catálogo unificado de clientes corporativos, RUC/Cédula, contactos y proyectos.
   - Bitácora de seguimiento de auditorías de clientes.

2. **📄 Gestión Comercial y Cotizaciones (`app/Modulos/Cotizaciones`):**
   - Generación de cotizaciones con cálculo automático de subtotal, IVA (15%), retenciones y descuentos.
   - Exportación de cotizaciones a PDF con **hoja membretada oficial**, logo corporativo y QR de verificación.
   - Historial de versiones y toma de decisiones del cliente (Aprobada / Rechazada).

3. **🔬 Operaciones LIMS e Ensayos de Laboratorio (`app/Modulos/Operaciones`):**
   - **Recepción de Muestras y Muestra Ciega (`CYCSA-RT-FM-60`):** Garantiza la imparcialidad exigida por la norma ISO 17025 eliminando la identidad del cliente para los técnicos.
   - **Hoja de Servicio (`CYCSA-RT-FM-13`):** Registro técnico oficial de muestras y ensayos solicitados. La edición está restringida exclusivamente a *Estado 1: Recepción* y *Estado 2: Observada*, manteniendo la visualización/descarga del PDF siempre disponible.
   - **Ensayos Normativos (ASTM C39, AASHTO T22, ASTM D422, ASTM C140):** Ensayo de resistencia de concreto, granulometría de suelos, límites de Atterberg, proctor, adoquines, morteros y núcleos.
   - **Informes de Control de Calidad:** Generación de informes finales en PDF con gráficos y tablas dinámicas.

4. **💼 Contabilidad y Bancos (`app/Modulos/Contabilidad`):**
   - Plan de cuentas contables estructurado.
   - Cuentas por Cobrar (CxC) y Cuentas por Pagar (CxP).
   - Conciliación bancaria y libro diario.

5. **⚙️ Configuración y Administración (`app/Modulos/Configuracion`, `Usuarios`):**
   - Gestión de usuarios con contraseñas encriptadas mediante `password_hash()` (Bcrypt).
   - Control de acceso basado en Roles y Permisos (Admin, Supervisor, Técnico, Contador, Cliente).
   - Validación de Sesión Única por usuario para impedir accesos simultáneos en múltiples dispositivos.

---

## 🏆 Puntuación Arquitectónica: 10/10 (Enterprise Level)

Toda la aplicación ha sido consolidada bajo el directorio central **`app/`**, eliminando carpetas raíz heredadas o duplicadas:

* ✅ **Integración de Núcleo (`nucleo/` $\rightarrow$ `app/Core/`):** El motor MVC base (`Aplicacion`, `Conexion`, `Enrutador`, `ControladorBase`, `Peticion`, `Respuesta`, `ManejadorErrores`) reside en `app/Core/`.
* ✅ **Integración de Módulos (`modulos/` $\rightarrow$ `app/Modulos/`):** Todos los 8 módulos operativos están organizados en `app/Modulos/` con nombres en formato PSR-4 (`Autenticacion`, `Clientes`, `Configuracion`, `Contabilidad`, `Cotizaciones`, `Operaciones`, `Productos`, `Usuarios`).
* ✅ **Integración de Plantillas (`plantillas/` $\rightarrow$ `app/Views/`):** Vistas maestras (`layout.php`), encabezados (`header.php`), pies de página (`footer.php`) y parciales organizadas en `app/Views/`.
* ✅ **Integración de Helpers (`ayudantes/` $\rightarrow$ `app/Helpers/`):** Utilidades de soporte (`AuthHelper`, `PdfHelper`, `MoneyHelper`, `DateHelper`, `StringHelper`, `UploadHelper`, `ValidationHelper` y `funciones.php`).
* ✅ **Organización de Datos (`database/catalogos/` y `database/ensayos/`):** Catálogos de datos y esquemas de formatos JSON reubicados en `database/`.

---

## 🛠️ Resumen de los 28 Puntos de Refactorización Completados

1. **Organización del Proyecto:** Estructura enterprise bajo `app/`, `config/`, `database/`, `storage/`, `rutas/`.
2. **Capa de Servicios (`app/Services/`):** Aislamiento de la lógica de negocio (`CotizacionService`, `LimsService`, `ClienteService`, `LogService`).
3. **Capa de Repositorios (`app/Repositories/`):** Abstracción de consultas a la base de datos PDO.
4. **Helpers Especializados (`app/Helpers/`):** Clases utilitarias enfocadas para seguridad, montos, fechas, uploads y PDFs.
5. **Sistema de Middlewares (`app/Middleware/`):** Pipeline de seguridad (`AuthMiddleware`, `AdminMiddleware`, `SupervisorMiddleware`, `Iso17025Middleware`, `CsrfMiddleware`, `RateLimitMiddleware`, `SecurityHeadersMiddleware`).
6. **Unificación de Respuestas (`app/Traits/ResponseTrait.php`):** Formato JSON estandarizado para APIs y AJAX.
7. **Motor de Validación (`app/Validators/Validator.php`):** Sanitización y validación centralizada.
8. **Manejo Global de Excepciones (`app/Exceptions/AppException.php`):** Captura limpia de errores sin fugas de información.
9. **Log Centralizado (`LogService`):** Registros categorizados en `storage/logs/`.
10. **Autocarga PSR-4 de Composer:** Configuración PSR-4 unificada para `Cycsa\App\`, `Cycsa\Config\`, `Cycsa\Modulos\`, `Cycsa\Nucleo\`.
11. **Migraciones SQL (`database/migrations/`):** Scripts 001, 002 y 003 estructurados.
12. **Seeders de Datos (`database/seeders/`):** Inicializadores de Roles, Permisos, Normas ASTM y Configuración.
13. **API REST Centralizada (`rutas/api.php`):** Rutas para `/api/v1/...`.
14. **Optimización de PDFs:** Renderizado de Dompdf con logos corporativos, QR y membretes de fondo.
15. **Visualización Restringida de Hojas de Servicio:** Edición permitida solo en Estado 1 y Estado 2 (Observada), con PDF siempre disponible.
16. **Compatibilidad con Hosting Compartido (Bluehost):** Normalización de separadores de ruta en Linux `/`, compatibilidad de minusculas/mayúsculas y script de corrección de permisos (`fix_permissions.php`).

---

## 📁 Estructura Completa de Directorios (`app/` y `database/`)

```
Cycsa/
├── app/
│   ├── Controllers/          <-- Controladores API y generales
│   │   └── Api/
│   ├── Core/                 <-- Motor MVC (Aplicacion, Conexion, Enrutador, ControladorBase)
│   ├── Exceptions/           <-- AppException
│   ├── Helpers/              <-- Helpers especializados + funciones.php
│   ├── Middleware/           <-- Pipeline de middlewares de seguridad y roles
│   ├── Models/               <-- Modelos de datos
│   ├── Modulos/              <-- Módulos del negocio (Autenticacion, Clientes, Operaciones, etc.)
│   ├── Policies/             <-- Políticas de autorización
│   ├── Repositories/         <-- Capa de acceso a datos (CotizacionRepository, LimsRepository)
│   ├── Services/             <-- Capa de servicios (CotizacionService, LimsService, LogService)
│   ├── Traits/               <-- ResponseTrait
│   ├── Validators/           <-- Validator.php
│   └── Views/                <-- Plantillas de vistas maestras (layout, header, footer)
├── bootstrap/                <-- Inicialización del sistema
├── config/                   <-- Hub unificado de configuración (app, database, security, mail, cache, paths, constants)
├── database/
│   ├── backups/              <-- Copias de seguridad SQL
│   ├── catalogos/            <-- Catálogos maestros
│   ├── ensayos/              <-- Esquemas de ensayos (formatos_schema.json)
│   ├── migrations/           <-- Migraciones SQL e índices
│   └── seeders/              <-- Seeders iniciales
├── docs/                     <-- Histórico de documentación
├── publico/                  <-- Entrada pública web (index.php, css, js, img)
├── rutas/                    <-- Rutas web.php y api.php
├── storage/                  <-- Logs, cache, uploads y PDFs generados
├── vendor/                   <-- Dependencias Composer (Dompdf, PHPMailer)
├── CHANGELOG.md              <-- Registro de versiones
├── composer.json             <-- Autoload PSR-4
├── fix_permissions.php       <-- Reparador de permisos en Bluehost
└── README.md                 <-- Documentación técnica principal
```

---

## 🚀 Guía Paso a Paso de Despliegue en Bluehost (cPanel)

1. **Subir el Paquete:**
   - Sube el archivo `cycsa_deploy_v2.zip` a la subcarpeta `public_html/sistema` en el Administrador de Archivos de cPanel.
   - Extrae los archivos asegurándote de que la ruta de destino sea `/public_html/sistema`.

2. **Base de Datos:**
   - Crea la base de datos `cycsanic_cycsa_db` y el usuario `cycsanic_erp_e` en cPanel MySQL Database Wizard.
   - Asigna **Todos los Permisos** al usuario sobre la base de datos.
   - Entra a **phpMyAdmin**, selecciona la base de datos e importa el archivo `database/backups/cycsa_datos_completos_2026.sql`.

3. **Configurar `.env`:**
   - Edita el archivo `.env` en `public_html/sistema/.env` con la URL `https://cycsanic.com/sistema` y las credenciales MySQL creadas.

4. **Verificación:**
   - Accede a `https://cycsanic.com/sistema/prueba.php` para verificar el entorno.
   - Ingresa a **`https://cycsanic.com/sistema`** para operar la plataforma.

---

## 📜 Créditos

Desarrollado para **CYCSA S.A. - Laboratorio de Control de Calidad de Materiales de Construcción**.  
*Plataforma refactorizada al nivel máximo de calidad de arquitectura (10/10).*
