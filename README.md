# 🏗️ CYCSA ERP & LIMS — Sistema Integral de Gestión de Laboratorio y Operaciones

Sistema Integral de Planificación de Recursos Empresariales (**ERP**) y Sistema de Información para la Gestión de Laboratorios (**LIMS**) desarrollado a medida para **CYCSA (Control y Calidad S.A.)**, diseñado en estricto cumplimiento con la norma internacional **ISO/IEC 17025:2017** (Requisitos generales para la competencia de los laboratorios de ensayo y calibración).

---

## 📑 TABLA DE CONTENIDOS
1. [Resumen del Sistema](#-resumen-del-sistema)
2. [Arquitectura y Tecnologías](#-arquitectura-y-tecnolog%C3%ADas)
3. [Módulos del Sistema](#-m%C3%B3dulos-del-sistema)
   - [1. Módulo Comercial y Cotizaciones](#1-m%C3%B3dulo-comercial-y-cotizaciones)
   - [2. Órdenes de Servicio y Logística de Muestreo](#2-%C3%B3rdenes-de-servicio-y-log%C3%ADstica-de-muestreo)
   - [3. Laboratorio LIMS y Tablero Kanban (Modo Ciego ISO 17025)](#3-laboratorio-lims-y-tablero-kanban-modo-ciego-iso-17025)
   - [4. Operaciones y Matrices Técnicas (21 Ensayos ASTM)](#4-operaciones-y-matrices-t%C3%A9cnicas-21-ensayos-astm)
   - [5. Módulo Contable y Financiero](#5-m%C3%B3dulo-contable-y-financiero)
   - [6. Seguridad, Roles y Trazabilidad](#6-seguridad-roles-y-trazabilidad)
4. [Catálogo Oficial de los 21 Ensayos Técnicos](#-cat%C3%A1logo-oficial-de-los-21-ensayos-t%C3%A9cnicos)
5. [Estructura del Proyecto](#-estructura-del-proyecto)
6. [Instalación y Despliegue en Producción](#-instalaci%C3%B3n-y-despliegue-en-producci%C3%B3n)
7. [Mantenimiento y Soporte](#-mantenimiento-y-soporte)

---

## 🌟 RESUMEN DEL SISTEMA

El sistema **CYCSA ERP & LIMS** centraliza y automatiza el ciclo de vida completo de los servicios de control de calidad de materiales de construcción (suelos, concretos, agregados, morteros, mezclas asfálticas y mampostería), desde la cotización comercial inicial hasta la emisión de informes técnicos oficiales certificados y el registro contable de las operaciones.

### Principales Beneficios:
* **Garantía de Imparcialidad (ISO/IEC 17025):** Operación en **Modo Ciego** en el laboratorio mediante códigos unívocos de muestra (`MS-XXXX-AA` / `MC-XXXX-AA`), ocultando información comercial, precios y clientes a analistas técnicos.
* **Cálculos Analíticos en Tiempo Real:** Eliminación de errores humanos mediante fórmulas automáticas integradas en matrices de ensayo.
* **Diferenciación Inteligente de Flujos:** Ensayos de laboratorio convencionales con recepción física vs. Ensayos In Situ (Compactación con Densímetro Nuclear, Cono de Arena, Reemplazo de Agua) que avanzan directamente a captura de campo sin emitir custodias innecesarias de laboratorio.
* **Contabilidad Integrada:** Libro Diario, Libro Mayor, Balanza de Comprobación, Cuentas por Cobrar y Conciliación Bancaria vinculadas a las órdenes de servicio.

---

## 💻 ARQUITECTURA Y TECNOLOGÍAS

* **Lenguaje Backend:** PHP 8.1 / 8.2+ con Arquitectura Modelo-Vista-Controlador (MVC) pura, rápida y ligera.
* **Base de Datos:** MySQL 5.7+ / MariaDB 10.4+ con motor transaccional InnoDB, claves foráneas y codificación `utf8mb4`.
* **Frontend:** HTML5, CSS3 Moderno (Variables CSS, Flexbox, CSS Grid), JavaScript Vanilla ES6+ reactivo, SweetAlert2, FontAwesome 6 Pro.
* **Motor de Generación PDF:** Dompdf con soporte de membrete institucional, marcas de agua oficiales y códigos QR de verificación.
* **Motor de Hojas de Cálculo / Reportes:** PhpSpreadsheet para exportación/importación avanzada de matrices.
* **Seguridad:** Tokens anti-CSRF, sanitización contra inyección SQL con PDO Prepared Statements, Hashids para ofuscación de URLs y protección de endpoints contra ataques XSS.

---

## 📦 MÓDULOS DEL SISTEMA

### 1. Módulo Comercial y Cotizaciones
* **Gestión de Clientes:** Directorio centralizado de clientes corporativos, proyectos y contactos.
* **Cotizaciones Dinámicas:** Generación de cotizaciones con ítems del catálogo oficial, cálculo de subtotales, IVA y descuentos.
* **Historial de Versiones:** Control de versiones de cotización con trazabilidad de cambios.
* **Generación de PDF Oficial (`CYCSA-RG-FM-39`):** Cotizaciones formales listas para firma de aceptación del cliente.
* **Aprobación Directa a Orden de Servicio (O/S):** Conversión automática a Orden de Servicio con solo un clic.

### 2. Órdenes de Servicio y Logística de Muestreo
* **Generación de Hoja de Servicio Oficial (`CYCSA-RT-FM-13`):** Registro de datos de muestreo en campo, coordenadas, observaciones ambientales y listado de especímenes declarados.
* **Programación y Despacho:** Asignación de técnicos muestreadores, vehículos de flota (`vehiculos`) y calendario operativo de recolección.
* **Segregación Automática:**
  * *Ensayos Convencionales:* Generan solicitud técnica de muestras para custodia en laboratorio.
  * *Ensayos de Compactación / In Situ:* Permiten acceso directo al llenado de matriz técnica sin bloquear la orden.

### 3. Laboratorio LIMS y Tablero Kanban (Modo Ciego ISO 17025)
* **Tablero Kanban de Trazabilidad:**
  1. **Recién Llegadas:** Solicitudes recibidas en ventanilla técnica pendientes de revisión.
  2. **En Revisión:** Inspección previa por parte de supervisores de calidad.
  3. **Muestras Aceptadas / En Custodia:** Especímenes formalmente ingresados con código oficial `MS-XXXX-26` / `MC-XXXX-26`.
  4. **Finalizadas:** Ensayos ejecutados y validados.
* **Modo Ciego e Imparcialidad:** Las tarjetas de laboratorio ocultan totalmente números de orden de servicio (`OS-`), precios, facturas y nombres de clientes; el analista trabaja exclusivamente con el código de solicitud técnica (`CYCSA-RT-FM-60`) y el código de laboratorio de la muestra.
* **Calendario de Rupturas Programadas:** Alertas automáticas para roturas de cilindros y especímenes de concreto a las edades normativas de diseño (ej. 3, 7, 14, 28 días).

### 4. Operaciones y Matrices Técnicas (21 Ensayos ASTM)
* **Captura de Matriz Técnica Dinámica:** Cuadrícula de captura adaptada al esquema normativo exacto de cada ensayo.
* **Motor de Autocalculado en Tiempo Real (JavaScript):**
  * **Compactación / Densímetro Nuclear:** `% Compactación = (P.V.S. Sitio / P.V.S. Máx) * 100`.
  * **Compresión (Cilindros, Mortero, Núcleos, Adoquines, Ladrillos):** Cálculo automático de Resistencia a la Compresión en $\text{lb/in}^2$ y $\text{kg/cm}^2$.
  * **Flexión (Vigas):** Cálculo automático de Módulo de Ruptura ($MR$).
  * **Edades Normativas:** Cálculo automático de días de curado entre la fecha de moldeo/fabricación y la fecha de ensaye.
  * **Granulometrías:** Suma acumulada de pesos retenidos, porcentajes que pasan y verificación de pérdida por lavado ($\le 2\%$).
* **Impresión Oficial de Matrices:** Documento técnico imprimible con membrete oficial, norma ASTM y firmas de responsabilidad técnica.

### 5. Módulo Contable y Financiero
* **Catálogo de Cuentas:** Clasificación NIIF (Activos, Pasivos, Capital, Ingresos, Costos y Gastos).
* **Libro Diario (Partidas de Diario):** Asientos contables automáticos vinculados a ventas y facturación, más generación de partidas manuales.
* **Libro Mayor y Balanza de Comprobación:** Reportes financieros en tiempo real con cuadratura estricta de débito y crédito.
* **Cuentas por Cobrar (CxC):** Registro de cobros, saldos pendientes y estado de cuenta por cliente.
* **Cuentas por Pagar (CxP) y Bancos:** Control de proveedores y conciliación de movimientos bancarios.

### 6. Seguridad, Roles y Trazabilidad
* **Control de Acceso Basado en Roles (RBAC):**
  * *Administrador General*
  * *Supervisor de Calidad / Laboratorio*
  * *Técnico / Laboratorista*
  * *Atención al Cliente / Ventas*
  * *Contabilidad / Finanzas*
* **Bitácora de Auditoría:** Registro de eventos del sistema (usuario, fecha/hora, acción, IP).
* **Hashids:** Ofuscación reversible de IDs numéricos en URLs para prevenir enumeración maliciosa.

---

## 🔬 CATÁLOGO OFICIAL DE LOS 21 ENSAYOS TÉCNICOS

Todos los esquemas y columnas han sido calibrados y extraídos directamente de los documentos técnicos oficiales de CYCSA:

| # | Archivo de Esquema (`.md` / JSON) | Nombre del Ensayo / Método | Norma ASTM / Referencia | Método Interno CYCSA |
|---|-----------------------------------|----------------------------|-------------------------|----------------------|
| **1** | `compactacion_densimetro_nuclear.md` | Densidad y Humedad In Situ (Densímetro Nuclear) | ASTM D6938-23 | `CYCSA-PE-25` |
| **2** | `ensayos_varios.md` | Ensayos Varios / Muestreos Especiales | Métodos Específicos | `CYCSA-PE-19` |
| **3** | `formato_de_compactacion_por_cono_de_arena.md` | Densidad In Situ por Cono de Arena | ASTM D1556/D1556M-24 | `CYCSA-PE-24` |
| **4** | `formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md` | Densidad In Situ por Reemplazo de Agua | ASTM D5030/D5030M-21 | `CYCSA-PE-26` |
| **5** | `granulometria.md` | Granulometría de Agregados y Suelos | ASTM C136 / ASTM D422 | `CYCSA-PE-18` |
| **6** | `resistencia_a_la_compresion_de_cilindros_de_concreto.md` | Compresión de Cilindros de Concreto | ASTM C39/C39M-21 | `CYCSA-PE-01` |
| **7** | `resistencia_a_la_compresion_de_mortero.md` | Resistencia a la Compresión de Mortero | ASTM C109/C109M-20 | `CYCSA-PE-03` |
| **8** | `resistencia_a_la_flexion_del_concreto.md` | Resistencia a la Flexión de Vigas | ASTM C78/C78M-22 | `CYCSA-PE-02` |
| **9** | `resistencia_a_la_compresion_de_adoquines.md` | Compresión de Adoquines de Concreto | ASTM C140/C140M-24 | `CYCSA-PE-05` |
| **10** | `resistencia_a_la_compresion_de_bloques.md` | Compresión de Bloques y Ladrillos | ASTM C140/C140M-24 | `CYCSA-PE-04` |
| **11** | `resistencia_a_la_compresion_de_nucleos_de_concreto.md` | Compresión de Núcleos de Concreto | ASTM C42/C42M-20 | `CYCSA-PE-06` |
| **12** | `resistencia_a_la_compresion_de_lodo_concreto_relleno_fluido.md` | Compresión de Lodo Concreto / Relleno Fluido | ASTM D4832-16 | `CYCSA-PE-07` |
| **13** | `limites_de_consistencia_limite_liquido_y_plastico.md` | Límites de Atterberg (Líquido y Plástico) | ASTM D4318-17 | `CYCSA-PE-20` |
| **14** | `pesos_volumetricos.md` | Pesos Volumétricos Suelto y Varillado | ASTM C29/C29M-17a | `CYCSA-PE-15` |
| **15** | `proctor_estandar.md` | Compactación Proctor Estándar y Modificado | ASTM D698-12 / ASTM D1557 | `CYCSA-PE-21` |
| **16** | `densidad_relativa_gravedad_especifica_y_absorcion_de_agregados.md` | Gravedad Específica y Absorción de Agregados | ASTM C127 / ASTM C128 | `CYCSA-PE-16` |
| **17** | `desgaste_por_abrasion_en_la_maquina_de_los_angeles.md` | Abrasión Máquina de Los Ángeles | ASTM C131/C131M-20 | `CYCSA-PE-17` |
| **18** | `cbr_relacion_de_soporte_de_california.md` | CBR (Relación de Soporte de California) | ASTM D1883-21 | `CYCSA-PE-23` |
| **19** | `equivalente_de_arena.md` | Equivalente de Arena | ASTM D2419-22 | `CYCSA-PE-22` |
| **20** | `contenido_de_humedad_con_horno.md` | Contenido de Humedad en Suelos | ASTM D2216-19 | `CYCSA-PE-14` |
| **21** | `material_mas_fino_que_el_tamiz_no_200_por_lavado.md` | Material que Pasa el Tamiz No. 200 por Lavado | ASTM C117-17 | `CYCSA-PE-13` |

---

## 📁 ESTRUCTURA DEL PROYECTO

```text
Cycsa/
├── app/
│   ├── Core/                           # Núcleo MVC (Enrutador, Petición, Respuesta, ControladorBase, ModeloBase)
│   ├── Helpers/                        # Funciones auxiliares globales, autenticación, Hashids y Dompdf
│   └── Modulos/                        # Arquitectura modular desacoplada
│       ├── Autenticacion/              # Inicio de sesión, control de acceso y contraseñas
│       ├── Clientes/                   # Directorio de clientes y proyectos
│       ├── Contabilidad/               # Partidas, Libro Diario, Mayor, Balanza y Catálogo de Cuentas
│       ├── Cotizaciones/               # Cotizaciones, versiones y PDF
│       ├── Finanzas/                   # CxC, CxP y Tesorería
│       ├── Laboratorio/                # Portal LIMS y Gestión de Ensayos
│       ├── Operaciones/                # Logística, Hojas de Muestreo, Kanban Lab y Matrices Técnicas
│       ├── OrdenesServicio/            # Generación y control de O/S
│       └── Usuarios/                   # Administración de usuarios y roles
├── config/                             # Archivos de configuración (app, db, mail)
├── database/
│   ├── ensayos/                        # Esquemas JSON oficiales de los 21 ensayos
│   │   ├── formatos_schema.json        # Esquema integral de columnas y fórmulas
│   │   └── *.md                        # Plantillas individuales en formato Markdown
│   ├── cycsa_produccion_limpia.sql     # Script SQL limpio para producción
│   └── cycsa_respaldo_completo.sql     # Respaldo SQL completo
├── publico/                            # Punto de entrada público HTTP y assets
│   ├── css/                            # Hojas de estilo personalizadas
│   ├── js/                             # Controladores reactivos JavaScript
│   ├── img/                            # Logotipos oficiales y membretes CYCSA
│   ├── uploads/                        # Directorio de archivos cargados
│   ├── .htaccess                       # Reglas de reescritura para producción
│   └── index.php                       # Front Controller
├── storage/                            # Almacenamiento temporal
│   ├── cache/                          # Caché de Dompdf y plantillas
│   └── logs/                           # Bitácora de errores del sistema
├── vendor/                             # Dependencias externas de Composer
├── .env                                # Variables de entorno y credenciales
├── .htaccess                           # Redirección en la raíz del servidor
├── composer.json                       # Manifiesto de paquetes PHP
└── README.md                           # Documentación general del sistema
```

---

## 🚀 INSTALACIÓN Y DESPLIEGUE EN PRODUCCIÓN

### 1. Requisitos del Servidor
* Servidor Web Apache / Nginx con módulo `mod_rewrite` habilitado.
* PHP >= 8.1 con extensiones: `pdo_mysql`, `gd`, `mbstring`, `openssl`, `json`, `zip`, `fileinfo`.
* Base de Datos MySQL >= 5.7 o MariaDB >= 10.4.

### 2. Configuración del Archivo `.env`
Crear o verificar el archivo `.env` en la raíz del proyecto:
```env
APP_NAME="CYCSA ERP"
APP_ENV=produccion
APP_DEBUG=0
APP_URL="https://tu-dominio.com/sistema"

# BASE DE DATOS
DB_HOST=localhost
DB_PORT=3306
DB_NAME=tu_base_de_datos
DB_USER=tu_usuario_db
DB_PASS="tu_password_seguro_aqui"
DB_CHARSET=utf8mb4

# CORREO SALIENTE (SMTP)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=tu_correo@gmail.com
MAIL_PASS="tu_app_password_aqui"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_correo@gmail.com
MAIL_FROM_NAME="CYCSA ERP"
```

### 3. Permisos de Directorios
Otorgar permisos de escritura a las siguientes carpetas en Linux/cPanel:
```bash
chmod -R 775 storage/
chmod -R 775 publico/uploads/
```

### 4. Base de Datos
Importar el archivo `database/cycsa_produccion_limpia.sql` en la base de datos de producción mediante phpMyAdmin o consola MySQL:
```bash
mysql -u tu_usuario_db -p tu_base_de_datos < database/cycsa_produccion_limpia.sql
```

---

## 📞 MANTENIMIENTO Y SOPORTE

* **Empresa:** Control y Calidad S.A. (CYCSA)
* **Ubicación:** Km 83.5 Carretera León - Managua, León, Nicaragua.
* **Área:** Laboratorio Central de Control de Calidad y Ensayos de Materiales.
* **Cumplimiento:** ISO/IEC 17025:2017 & Normas ASTM International.
* **Año:** 2026.
