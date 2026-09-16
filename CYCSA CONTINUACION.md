# CYCSA ERP & LIMS — Documentación del Sistema y Nuevas Funcionalidades

> **CORPORACIÓN Y CONSTRUCCIONES S.A. (CYCSA)**  
> **RUC:** J0310000185934  
> **Sistema:** ERP Corporativo & LIMS Especializado de Laboratorio de Ensayos de Materiales  
> **Estándar de Calidad:** ISO/IEC 17025:2017  
> **Repositorio Oficial:** `https://github.com/Tomate25/CycsaV1.git`  
> **Contacto Técnico:** anlrocha2006@gmail.com  

---

## 📑 Tabla de Contenidos
1. [Visión General del Sistema](#-visión-general-del-sistema)
2. [Arquitectura y Stack Tecnológico](#-arquitectura-y-stack-tecnológico)
3. [Flujo de Calidad End-to-End (Ciclo de Vida del Servicio)](#-flujo-de-calidad-end-to-end)
4. [Módulos Principales del Sistema](#-módulos-principales-del-sistema)
5. [Todo lo Nuevo Implementado en Esta Versión](#-todo-lo-nuevo-implementado-en-esta-versión)
   - [5.1 Apartado de Facturación y Cobranza Flexible](#51-apartado-de-facturación-y-cobranza-flexible)
   - [5.2 Afectación Contable Automática en el Libro Diario (Partida Doble)](#52-afectación-contable-automática-en-el-libro-diario-partida-doble)
   - [5.3 Emisión e Impresión de Factura Comercial Oficial](#53-emisión-e-impresión-de-factura-comercial-oficial)
   - [5.4 Envío de Informes de Matriz Técnica en PDF por Correo](#54-envío-de-informes-de-matriz-técnica-en-pdf-por-correo)
   - [5.5 Hojas de Solicitud de Ensayos CYCSA-RT-FM-13](#55-hojas-de-solicitud-de-ensayos-cycsa-rt-fm-13)
   - [5.6 Catálogo de 112 Ensayos Técnicos con Esquemas Dinámicos](#56-catálogo-de-112-ensayos-técnicos-con-esquemas-dinámicos)
   - [5.7 Infraestructura de Pruebas Unitarias Automatizadas (PHPUnit)](#57-infraestructura-de-pruebas-unitarias-automatizadas-phpunit)
   - [5.8 Sanitización y Seguridad Defensiva](#58-sanitización-y-seguridad-defensiva)
6. [Guía de Instalación, Configuración y Pruebas](#-guía-de-instalación-configuración-y-pruebas)
7. [Mapa de Rutas Web del Sistema](#-mapa-de-rutas-web-del-sistema)

---

## 🏢 Visión General del Sistema

**CYCSA ERP & LIMS** es una plataforma integral diseñada específicamente para gestionar las operaciones técnicas y administrativas de laboratorios de control de calidad de materiales de construcción (suelos, concretos, agregados, asfaltos y mezclas).

El sistema cubre desde la captación comercial del cliente hasta la entrega formal de resultados técnicos certificados y su respectiva liquidación contable:

```
[Cotización Comercial] ➔ [Orden de Servicio (O/S)] ➔ [Muestreo en Campo / Recepción Lab]
                                                               ⬇
[Factura Comercial] ==== [Libro Diario] ==== [Matriz Técnica de Ensayos ISO/IEC 17025]
(Disponible en cualquier momento)                               ⬇
                                                      [Envío de Informe PDF al Cliente]
```

---

## 🛠 Arquitectura y Stack Tecnológico

- **Lenguaje Base:** PHP 8.2+ con tipado estricto y separación modular limpia (MVC).
- **Base de Datos:** MySQL / MariaDB con motor InnoDB, transaccionalidad ACID y bloqueos pesimistas (`FOR UPDATE`) en procesos monetarios.
- **Enrutador & Núcleo:** Motor propio con soporte de Middlewares (`AuthMiddleware`, `ContabilidadMiddleware`, `CsrfMiddleware`).
- **Seguridad:** Tokens CSRF criptográficamente seguros (`hash_equals`), protección contra inyecciones SQL vía PDO preparado, encabezados HTTP de seguridad y aislamiento estricto de roles.
- **Motor de Plantillas & UI:** HTML5 semántico, CSS responsive con diseño corporativo CYCSA (Azul `#0f3b68`, Dorado `#d4af37`), Iconos FontAwesome 6 y fuentes Outfit / Inter.
- **Librerías Clave:**
  - `phpmailer/phpmailer`: Despacho SMTP de correos electrónicos transaccionales.
  - `dompdf/dompdf`: Renderizado de documentos PDF vectoriales y de alta fidelidad.
  - `phpunit/phpunit`: Suite de pruebas unitarias y de integración.

---

## 🔄 Flujo de Calidad End-to-End

El ciclo operativo sigue estrictamente las directrices de trazabilidad de la norma **ISO/IEC 17025:2017**:

1. **Cotización (`/cotizaciones`):**
   - Creación interna o recepción mediante formulario público del cliente.
   - Selección de ensayos del catálogo de 112 productos con precios, normas ASTM/AASHTO y condiciones de muestra.
   - Flujo de revisión técnica y aprobación administrativa.

2. **Orden de Servicio (`/ordenes-servicio` y `/operaciones`):**
   - Al aprobar la cotización, se genera automáticamente una Orden de Servicio secuencial (ej. `OS-2026-0001`).
   - Se define la modalidad de origen:
     - **Muestreo en Campo:** Se asigna técnico de muestreo dedicado y vehículo corporativo de la flota.
     - **Ingreso Directo:** El cliente entrega las muestras directamente en la ventanilla del laboratorio central.
     - **Compactación In Situ:** Ensayos de terreno (ej. Densímetro nuclear ASTM D6938) que no requieren custodia de probeta física.

3. **Hoja de Solicitud de Ensayos CYCSA-RT-FM-13 (`/hojas-servicio`):**
   - Registro de datos de campo, procedencia, estratos, condiciones ambientales y firmas.
   - Aprobación y visto bueno del supervisor de calidad.

4. **Laboratorio y Matrices Técnicas (`/operaciones` y `/laboratorio`):**
   - Recepción e ingreso con código único de laboratorio (ej. `MS-0001-26`).
   - Captura de lecturas en matrices de ensayo en tiempo real con fórmulas de cálculo automático.

5. **Informe Oficial y Despacho:**
   - Emisión de informe oficial en PDF con membrete horizontal CYCSA.
   - Envío directo al correo electrónico del cliente con copia de respaldo y registro en bitácora.

6. **Facturación y Contabilidad (`/operaciones/procesar-facturacion`):**
   - Facturación flexible en cualquier etapa del proceso.
   - Liquidación en Efectivo, Transferencia Bancaria o Crédito.
   - Registro simultáneo en Caja, Bancos, Cuentas por Cobrar y Libro Diario.

---

## 📦 Módulos Principales del Sistema

| Módulo | Ruta Base | Funcionalidad Clave |
| :--- | :--- | :--- |
| **Autenticación** | `/login`, `/logout` | Control de sesiones, roles (Administrador, Gerencia, Laboratorio, Facturación, etc.) y cambio obligatorio de clave. |
| **Cotizaciones** | `/cotizaciones` | Presupuestos comerciales, solicitud pública, cálculos automáticos de IVA y descuentos. |
| **Órdenes de Servicio** | `/ordenes-servicio` | Planificación logística, asignación de técnicos de muestreo y control de campo. |
| **Hojas de Servicio** | `/hojas-servicio` | Gestión de solicitudes RT-FM-13, validación de ingresos y firmas técnicas. |
| **Operaciones LIMS** | `/operaciones` | Panel integral de ensayos, calendario de rupturas de concreto, matrices de resultados y facturación. |
| **Laboratorio** | `/laboratorio` | Tablero Kanban operativo para técnicos de laboratorio y registro de rupturas. |
| **Contabilidad** | `/contabilidad` | Catálogo contable, Libro Diario, CXC, CXP, Cuentas Bancarias, Balance y Estado de Resultados. |
| **Clientes** | `/clientes` | Directorio de clientes, RUC, condiciones de crédito, direcciones y contactos autorizados. |
| **Productos** | `/productos` | Catálogo de ensayos, métodos normativos, precios base y formatos asignados. |

---

## 🚀 Todo lo Nuevo Implementado en Esta Versión

### 5.1 Apartado de Facturación y Cobranza Flexible
Se incorporó en el módulo de Operaciones ([`OperacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Controladores/OperacionesControlador.php) y [`index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Vistas/index.php)) un sistema integral de cobro y facturación:
- **Disponibilidad Inmediata sin Bloqueos:** La facturación se puede realizar **en cualquier momento del ciclo de vida**. No se bloquea si los ensayos no tienen resultados o si no se ha llenado una hoja de campo.
- **Acceso Visual Rápido:**
  - Nueva columna **"Facturación / Cobro"** en la tabla principal de Órdenes de Servicio.
  - Indicadores dinámicos de estado: `Pagada`, `Parcial` o `Pendiente`.
  - Tarjeta detallada de **"Apartado de Facturación y Registro Contable Diario"** dentro del acordeón expandible de cada O/S.

### 5.2 Afectación Contable Automática en el Libro Diario (Partida Doble)
Cada operación de cobro procesada a través del modal genera un asiento contable estricto balanceado en la base de datos (`partidas_diario` y `partidas_diario_detalles`):

1. **Cobro en Efectivo:**
   - **Débito (Debe):** Cuenta `1010101` (ID 4) — *Caja Principal*.
   - **Crédito (Haber):** Cuenta `4010106` (ID 208) — *Consultorías-Laboratorios (G)*.
   - **Cuentas por Cobrar:** Se actualiza el saldo de la factura a C$ 0.00 y se marca como `Pagado`.

2. **Cobro por Transferencia Bancaria:**
   - **Selección de Banco:** Selección de la cuenta bancaria de destino (BAC Córdobas, BANPRO Córdobas/Dólares, LAFISE BANCENTRO).
   - **Actualización Bancaria:** Se incrementa inmediatamente el `saldo_actual` en `bancos_cuentas`.
   - **Transacción en Bancos:** Se inserta el registro en `bancos_transacciones` con estado `Cobrado` y número de referencia.
   - **Débito (Debe):** Cuenta contable del banco receptor (ej. `1010103` para BAC, `1010106` para LAFISE).
   - **Crédito (Haber):** Cuenta `4010106` (ID 208) — *Consultorías-Laboratorios (G)*.
   - **Cuentas por Cobrar:** Saldo actualizado y estado `Pagado`.

3. **Facturación a Crédito:**
   - Permite establecer plazo en días (ej. 15, 30 o 60 días).
   - Débito a Clientes Nacionales (`1010201`, ID 13) y Crédito a Ingresos por Laboratorio (`4010106`).
   - Generación de cuenta por cobrar con fecha de vencimiento calculada.

### 5.3 Emisión e Impresión de Factura Comercial Oficial
Se implementó la vista oficial e imprimible [`factura_print.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Vistas/factura_print.php), accesible mediante la ruta `/operaciones/imprimir-factura?id_os=X`:
- **Membrete Corporativo CYCSA:** Con logotipo institucional de alta resolución, RUC `J0310000185934`, datos de contacto fiscal y dirección física.
- **Metadatos Comerciales:** Número oficial de factura (`FAC-COT-2026-XXXX`), fecha de emisión, fecha de vencimiento, código de O/S y código de Cotización vinculada.
- **Datos Completos del Cliente:** Razón social, RUC/Cédula, dirección, teléfono, correo y nombre del proyecto.
- **Tabla Detallada de Ensayos:** Código de servicio, descripción del ensayo, norma técnica ASTM/AASHTO, cantidad facturada, precio unitario y subtotal.
- **Liquidación de Impuestos:** Subtotal neto, 15% de I.V.A., Total en Córdobas (C$) y desglose de saldo pendiente.
- **Conversor a Letras:** Función nativa `numeroALetras()` que transcribe el total exacto a texto oficial (ej. *MIL CUATROCIENTOS NOVENTA Y CINCO CON 00/100 CÓRDOBAS NETOS*).
- **Sello de Pago & Trazabilidad:** Sello verde oficial `FACTURA PAGADA`, referencia bancaria o de caja, número de asiento en el Libro Diario y casillas para firmas de autorización y recibido conforme.
- **Estilos de Impresión Optimizados:** Configuración `@page { size: letter portrait; margin: 10mm; }` para impresión perfecta o exportación nativa a PDF en navegador.

### 5.4 Envío de Informes de Matriz Técnica en PDF por Correo
- En el panel de Operaciones, cuando una matriz cuenta con resultados, se habilita el botón **"Enviar al Cliente"**.
- Abre una ventana modal que precarga automáticamente:
  - Destinatario: Correo electrónico del cliente.
  - Asunto: Identificador de la O/S y nombre del ensayo.
  - Enlace de previsualización del PDF antes de enviar.
- El sistema compila la matriz de resultados con membrete horizontal CYCSA oficial en PDF vectorizado, lo adjunta y lo despacha vía SMTP.
- Se registra el evento en la bitácora de operaciones y en `storage/logs/operaciones_emails.log`.

### 5.5 Hojas de Solicitud de Ensayos CYCSA-RT-FM-13
- Implementación completa del módulo de Hojas de Servicio ([`HojasServicioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/HojasServicio/Controladores/HojasServicioControlador.php)).
- Cumplimiento del formato normativo oficial CYCSA-RT-FM-13 con datos de muestreador, transportista, condiciones de entrega, estado de preservación de muestras y observaciones técnicas.
- Generación de PDF oficial y flujo de revisión y aprobación por el supervisor de calidad.

### 5.6 Catálogo de 112 Ensayos Técnicos con Esquemas Dinámicos
- Base de datos con 112 ensayos normados (ASTM C39, ASTM C143, ASTM D6938, ASTM D1557, AASHTO T180, etc.).
- Esquema JSON de metadatos matemáticos (`database/ensayos/formatos_schema.json`) con definición de columnas, fórmulas de cálculo automático de humedad, densidad seca, compactación y resistencia a la compresión.
- Normalización insensible a acentos para vincular automáticamente cada ensayo con su matriz correspondiente.

### 5.7 Infraestructura de Pruebas Unitarias Automatizadas (PHPUnit)
- Configuración de PHPUnit (`phpunit.xml`) integrado con Composer.
- **72 pruebas unitarias y de integración** con **645 aserciones** que validan:
  - Enrutamiento HTTP y protección de Middlewares.
  - Generación y verificación estricta de tokens CSRF.
  - Integridad contable de partida doble (rechazo de asientos descuadrados, débitos negativos o líneas insuficientes).
  - Conversión de números a letras para facturación.
  - Generación de PDFs y flujos de muestreo.
- Ejecución limpia y garantizada con el comando: `vendor/bin/phpunit`.

### 5.8 Sanitización y Seguridad Defensiva
- Eliminación de credenciales en texto plano en archivos públicos y documentación.
- Manejo centralizado de secretos en variables de entorno `.env` (excluido en `.gitignore`).
- Sistema fail-closed en verificación de sesiones y permisos por rol.

---

## ⚙️ Guía de Instalación, Configuración y Pruebas

### 1. Clonar el Repositorio
```bash
git clone https://github.com/Tomate25/CycsaV1.git
cd CycsaV1
```

### 2. Instalar Dependencias PHP
```bash
composer install
```

### 3. Configurar Variables de Entorno
Copiar la plantilla de configuración e ingresar los parámetros locales:
```bash
cp .env.example .env
```
Parámetros esenciales en `.env`:
```ini
APP_ENV=local
APP_URL=http://localhost/Cycsa/publico
DB_HOST=127.0.0.1
DB_NAME=cycsa_db
DB_USER=root
DB_PASS=

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=tu_correo@gmail.com
MAIL_PASS=tu_app_password_de_aplicacion
MAIL_FROM_ADDRESS=facturacion@cycsanic.com
MAIL_FROM_NAME="CYCSA S.A."
```

### 4. Ejecutar la Suite de Pruebas
```bash
vendor/bin/phpunit
```
*Resultado esperado:* `OK (72 tests, 645 assertions)`

---

## 🗺 Mapa de Rutas Web del Sistema

### Operaciones LIMS & Facturación
- `GET  /operaciones` — Tablero principal de seguimiento de ensayos y facturación.
- `POST /operaciones/procesar-facturacion` — Procesa el cobro inmediato (Efectivo/Transferencia/Crédito) y asienta en Libro Diario.
- `GET  /operaciones/imprimir-factura` — Vista imprimible oficial de la Factura Comercial CYCSA.
- `GET  /operaciones/captura-matriz` — Interfaz de captura de resultados por producto.
- `GET  /operaciones/imprimir-matriz` — Vista imprimible de la matriz técnica con membrete.
- `GET  /operaciones/descargar-matriz-pdf` — Descarga del informe oficial de resultados en PDF.
- `POST /operaciones/enviar-matriz-cliente` — Despacho automático del informe en PDF al correo del cliente.

### Cotizaciones y Órdenes de Servicio
- `GET  /cotizaciones` — Listado y gestión comercial.
- `GET  /cotizaciones/crear` — Creación de cotización con catálogo de 112 ensayos.
- `GET  /cotizaciones/solicitar-publica` — Portal público para recepción de solicitudes externas.
- `GET  /ordenes-servicio` — Gestión de órdenes de servicio y asignación de muestreo.

### Contabilidad y Finanzas
- `GET  /contabilidad/cuentas` — Catálogo general de cuentas contables.
- `GET  /contabilidad/diario` — Libro Diario (asientos automáticos y manuales).
- `GET  /contabilidad/bancos` — Cuentas bancarias y saldos en tiempo real.
- `GET  /contabilidad/cxc` — Cuentas por cobrar y control de vencimientos.
- `GET  /contabilidad/balance` — Balance de comprobación.

---

*Documentación técnica generada y actualizada para el repositorio oficial CYCSA V1.*
