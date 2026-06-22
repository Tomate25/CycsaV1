# Bitácora de Avances - Sistema de Gestión y Cotizaciones CYCSA

Este archivo contiene el registro detallado del estado del proyecto, arquitectura, funcionalidades implementadas, mejoras de seguridad y la configuración requerida para producción en **Bluehost**. Sirve como punto de partida y referencia para cualquier desarrollador o agente que trabaje en el sistema.

---

## 📂 Estructura General del Proyecto (Arquitectura MVC)

El sistema está construido sobre un framework MVC nativo en PHP sin dependencias pesadas:
* **`publico/`**: Punto de entrada del sistema. Contiene `index.php` (front controller), hojas de estilos (`css/`), scripts (`js/`) e imágenes (`img/`).
  * Contiene las reglas de reescritura `.htaccess` para redirigir todo el tráfico a `index.php` de forma segura.
* **`nucleo/`**: Clases base del framework:
  * [Aplicacion.php](file:///C:/xampp/htdocs/Cycsa/nucleo/Aplicacion.php): Inicializador de sesión (con HTTPS dinámico) y lector de variables de entorno.
  * [Conexion.php](file:///C:/xampp/htdocs/Cycsa/nucleo/Conexion.php): Conexión Singleton segura a la base de datos usando PDO.
  * [ControladorBase.php](file:///C:/xampp/htdocs/Cycsa/nucleo/ControladorBase.php): Gestor de renderizado de vistas con y sin plantilla general.
  * `Enrutador.php`, `Peticion.php`, `Respuesta.php`: Sistema de rutas, captura de parámetros HTTP y códigos de estado.
* **`modulos/`**: Código de la lógica de negocio estructurado en submódulos (Clientes, Cotizaciones, Usuarios, Autenticación). Cada módulo tiene sus propios controladores, modelos y vistas.
* **`ayudantes/`**: Archivos de soporte general.
  * [funciones.php](file:///C:/xampp/htdocs/Cycsa/ayudantes/funciones.php): Helper global que implementa el envío de correos (PHPMailer) y el motor de generación de PDFs (Dompdf).
* **`almacenamiento/`**: Directorio de escritura local.
  * `logs/`: Contiene los registros de auditoría y correos (`emails.log`, `auditoria_clientes.log`).

---

## 🚀 Funcionalidades Clave Implementadas

### 1. Robustez en Carga de Configuración
* **Parser de Entorno Personalizado**: Reemplazamos `parse_ini_file` en [Aplicacion.php](file:///C:/xampp/htdocs/Cycsa/nucleo/Aplicacion.php) por un parser línea por línea. Esto evita errores de sintaxis provocados por comentarios con `#` o uso de paréntesis `()` en el archivo [.env](file:///C:/xampp/htdocs/Cycsa/.env).

### 2. Flujo y Ciclo de Vida de Cotizaciones
* **Máquina de Estados de Cotización**: Las cotizaciones transicionan de manera ordenada según las etapas de la empresa:
  `Borrador` ➔ `En Revisión` ➔ `Enviada al Cliente` ➔ `Aprobada por Cliente` o `Rechazada por Cliente` (o `Observada`).
* **Visualización Organizada por Pestañas**: En [index.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/vistas/index.php) reestructuramos el listado superior en pestañas lineales:
  * **Borradores**: Exclusivo para guardar avances de ofertas.
  * **En Revisión**: Cola de trabajo de aprobación interna para la gerencia.
  * **Observadas**: Devueltas por gerencia al asesor con retroalimentación para corrección.
  * **Aprobadas / Enviadas**: Propuestas aprobadas o enviadas al cliente.
  * **Todas**: Histórico total de cotizaciones.
* **Botón "Enviar a Revisión"**: Habilitado en la vista de detalle para cotizaciones en estado `Borrador`, permitiendo al creador enviar su trabajo a la cola de aprobación de la gerencia.

### 3. Generación y Envío de PDFs Automatizado
* **Dompdf**: Instalamos la biblioteca `dompdf/dompdf` vía Composer para renderizar la propuesta económica directamente en formato A4 con tablas dinámicas de ítems, condiciones comerciales y leyendas automáticas seleccionadas (ensayos de concreto `CYCSA-PE-07`, entrega de muestras, alta demanda y recargos por muestreo extra).
* **Envío al Aprobar**: Cuando la Gerencia aprueba una cotización, el estado se actualiza a `Enviada al Cliente`, se genera el PDF de forma automática en memoria y se le envía al cliente por correo electrónico como archivo adjunto, incorporando un enlace para aceptar o rechazar en línea.

### 4. Portal de Decisión del Cliente
* **Pantalla de Decisión Exclusiva**: Creamos una vista pública hermosa y responsive en [decision_cliente.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/vistas/decision_cliente.php) para que el cliente revise los detalles y registre su decisión:
  * **Aceptar**: Solicita confirmación en un modal emergente.
  * **Rechazar**: Obliga a especificar los comentarios del motivo de rechazo en un campo colapsable.
* **Inmutabilidad**: Si la cotización ya fue aceptada o rechazada previamente, se muestra en modo de solo lectura para evitar alteraciones de estado.
* **Notificación al Creador**: Al registrarse la decisión, el asesor comercial creador de la oferta recibe automáticamente un correo detallando la resolución del cliente.

### 5. Cuadro de Mando Avanzado (Estilo PowerBI)
* Rediseñamos el panel de control principal [panel.php](file:///C:/xampp/htdocs/Cycsa/modulos/usuarios/vistas/panel.php) utilizando la librería **ApexCharts** para proveer analíticas comerciales dinámicas:
  * **Fila de KPIs**: Ventas totales aprobadas en C$, cantidad de propuestas, solicitudes en revisión y volumen de clientes activos.
  * **Tendencia Mensual (Gráfico de Área con Gradientes)**: Evolución monetaria de los últimos 6 meses contrastada con el volumen de cotizaciones procesadas.
  * **Distribución de Estados (Gráfico de Dona)**: Distribución porcentual y cantidad de cotizaciones en cada etapa.
  * **Gráfico de Barras por Prioridad**: Carga de cotizaciones clasificadas por urgencia (Alta, Media, Normal).
  * **Top Clientes**: Barras de progreso de los 5 clientes que mayor volumen monetario representan para la empresa.
  * **Lista de Recientes**: Tabla pulida con el historial inmediato de cotizaciones y sus respectivas insignias de estado.

---

## 🛡️ Medidas de Seguridad Incorporadas

1. **Control de Acceso de Roles (RBAC)**: Bajas de usuarios y visualizaciones de configuraciones restringidas únicamente a Administradores (Rol 1).
2. **Protección contra ataques CSRF**: Generación de tokens de sesión y comprobaciones estrictas en la creación de usuarios y en el portal de decisión del cliente.
3. **Mitigación de Timing Attacks (Ataques de Sincronización)**: Uso de `hash_equals()` al verificar el token de seguridad temporal de las cotizaciones enviado por correo electrónico.
4. **Prevención de XSS (Cross-Site Scripting)**: Escapado de variables en pantalla mediante `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
5. **HTTPS Dinámico**: Las cookies de sesión se marcan como secure de forma condicional para no bloquear el desarrollo en HTTP (localhost) mientras se protege la producción en HTTPS.
6. **Bitácora de Auditoría**: Registro de IP (`REMOTE_ADDR`), firma del navegador (`User-Agent`), ID de cotización y acción en [auditoria_clientes.log](file:///C:/xampp/htdocs/Cycsa/almacenamiento/logs/auditoria_clientes.log).

---

## ☁️ Guía de Despliegue en Bluehost (Hosting Compartido)

* **Versión de PHP**: Configurar cPanel a **PHP 8.0 o superior** (*GD*, *MBString* y *OpenSSL* deben estar activadas).
* **Envío de Correo Directo**: Dado que los servidores compartidos a menudo bloquean puertos SMTP salientes (465/587) por políticas anti-spam:
  * Deja las variables `MAIL_HOST`, `MAIL_USER` y `MAIL_PASS` vacías en el archivo [.env](file:///C:/xampp/htdocs/Cycsa/.env).
  * El sistema de correo PHPMailer detectará esto y usará automáticamente la función nativa `mail()` del servidor web, evitando bloqueos.
* **Permisos**: Asegurar permisos **`755`** para el directorio `almacenamiento/` para permitir la escritura de logs.
