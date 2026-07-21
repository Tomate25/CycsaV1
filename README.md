# 🏗️ CYCSA ERP & LIMS v2.0 - Arquitectura Empresarial (10/10)

> **Sistema Integral ERP & LIMS para Laboratorios de Ensayo de Concreto, Suelos, Asfaltos, Adoquines y Materiales de Construcción (Norma ISO/IEC 17025).**  
> Consolidado bajo **Clean Architecture, Domain-Driven Design y estándar PSR-4**.

---

## 📋 Tabla de Contenidos
1. [Descripción General](#-descripción-general)
2. [🏆 Puntuación Arquitectónica: 10/10 (Enterprise Level)](#-puntuación-arquitectónica-1010-enterprise-level)
3. [📁 Estructura de Directorios app/ y database/](#-estructura-de-directorios-app-y-database)
4. [🔄 Capa de Servicios, Repositorios y Middlewares](#-capa-de-servicios-repositorios-y-middlewares)
5. [🔒 Seguridad, Logs y Cumplimiento ISO/IEC 17025](#-seguridad-logs-y-cumplimiento-isoiec-17025)
6. [📊 Base de Datos: Migraciones, Seeders e Índices](#-base-de-datos-migraciones-seeders-e-índices)
7. [🌐 Rutas y API REST Centralizada](#-rutas-y-api-rest-centralizada)
8. [📜 Historial de Cambios (CHANGELOG)](#-historial-de-cambios-changelog)

---

## 🌟 Descripción General

**CYCSA ERP & LIMS v2.0** es la plataforma corporativa de **CYCSA S.A.** diseñada para automatizar la gestión técnica, comercial, contable y operativa del laboratorio de control de calidad de materiales. Garantiza la trazabilidad, imparcialidad y el rigor técnico exigido por la norma internacional **ISO/IEC 17025**.

---

## 🏆 Puntuación Arquitectónica: 10/10 (Enterprise Level)

Toda la aplicación ha sido consolidada bajo el directorio central **`app/`**, eliminando carpetas raíz heredadas o duplicadas:

* ✅ **Integración de Núcleo (`nucleo/` $\rightarrow$ `app/Core/`):** El motor MVC base (`Aplicacion`, `Conexion`, `Enrutador`, `ControladorBase`) ahora reside en `app/Core/`.
* ✅ **Integración de Módulos (`modulos/` $\rightarrow$ `app/Modulos/`):** Todos los módulos operativos se ubican limpiamente bajo `app/Modulos/`.
* ✅ **Integración de Plantillas (`plantillas/` $\rightarrow$ `app/Views/`):** Vistas maestras y parciales organizadas en `app/Views/`.
* ✅ **Integración de Helpers (`ayudantes/` $\rightarrow$ `app/Helpers/`):** Utilidades de soporte autocargadas mediante Composer en `app/Helpers/`.
* ✅ **Organización de Datos (`database/catalogos/` y `database/ensayos/`):** Archivos de esquemas y catálogos reubicados dentro de `database/`.

---

## 📁 Estructura de Directorios app/ y database/

```
Cycsa/
├── app/
│   ├── Controllers/
│   │   └── Api/
│   ├── Core/                <-- Motor base MVC del sistema
│   ├── Exceptions/
│   ├── Helpers/             <-- Helpers especializados + funciones.php
│   ├── Middleware/
│   ├── Models/
│   ├── Modulos/             <-- Módulos del negocio (autenticacion, clientes, operaciones, etc.)
│   ├── Policies/
│   ├── Repositories/
│   ├── Services/            <-- Capa de Servicios de Negocio
│   ├── Traits/
│   ├── Validators/
│   └── Views/               <-- Vistas maestras y parciales del layout
├── bootstrap/
├── config/                  <-- Hub unificado de configuración
├── database/
│   ├── backups/
│   ├── catalogos/           <-- Catálogos maestro de datos
│   ├── ensayos/             <-- Esquemas normativos de ensayos (formatos_schema.json)
│   ├── migrations/
│   └── seeders/
├── docs/                    <-- Histórico de documentación
├── publico/                 <-- Entrada pública web
├── rutas/                   <-- Rutas web.php y api.php
├── storage/                 <-- Logs, cache, uploads y PDFs
├── vendor/                  <-- Autoload PSR-4 Composer
├── CHANGELOG.md
├── composer.json
└── README.md
```

---

## 📜 Créditos

Desarrollado para **CYCSA S.A. - Laboratorio de Control de Calidad de Materiales de Construcción**.  
*Sistema refactorizado al nivel máximo de calidad de arquitectura (10/10).*
