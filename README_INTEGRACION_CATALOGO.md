# Integración de Catálogo de Productos y Formatos de Ensayos en Cotizaciones

Este documento detalla los cambios arquitectónicos, modificaciones a nivel de base de datos y actualizaciones en las vistas y controladores del módulo de **Cotizaciones** realizados para integrar de forma dinámica el catálogo unificado de productos (221 ensayos) y sus respectivos formatos de control de calidad (derivados de los 21 documentos de plantillas cargados).

---

## 1. Cambios en la Base de Datos (Esquema)

Se optó por una **vinculación lógica e íntegra (Opción 2)** mediante llaves foráneas en lugar de una simple concatenación de texto. Esto preserva la integridad referencial y permite cambiar la estructura o datos del catálogo en el futuro sin corromper el histórico de cotizaciones.

* **Tabla:** `cotizacion_detalles`
* **Alteración:** Se agregó la columna `id_producto` de tipo `INT NULL` con una restricción de llave foránea (`FK`) apuntando a la tabla `productos(id)`. En caso de eliminarse un producto del catálogo, la cotización histórica permanece intacta gracias al comportamiento `ON DELETE SET NULL`.
* **Script SQL ejecutado:**
  ```sql
  ALTER TABLE cotizacion_detalles 
  ADD COLUMN id_producto INT NULL AFTER id_cotizacion,
  ADD CONSTRAINT fk_detalles_productos 
  FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE SET NULL;
  ```

---

## 2. Flujo de Datos y Modificaciones de Código

### A. Modelo de Datos
* **Archivo:** [CotizacionModelo.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/modelos/CotizacionModelo.php)
  * **`obtenerDetalles($id_cotizacion)`:** Se modificó la consulta principal agregando dos `LEFT JOIN` hacia la tabla `productos` y `formatos_ensayos`. Ahora recupera dinámicamente:
    * `p.codigo_servicio` (Código único del ensayo en el catálogo).
    * `p.norma_astm` (Norma técnica reguladora).
    * `f.codigo_formato` como `formato_reporte` (Formato de calidad asignado de las 21 plantillas).
  * **`guardarCotizacionCompleta(...)` & `actualizarCotizacionCompleta(...)`:** Modificados para mapear e insertar el parámetro `:id_producto` en cada fila del detalle.

### B. Controlador del Sistema
* **Archivo:** [CotizacionesControlador.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/controladores/CotizacionesControlador.php)
  * **`procesarDetalles($datos)`:** Extrae el array `ensayo_id_producto[]` proveniente del formulario POST y vincula cada entrada numérica (o `null` si no está en catálogo) al arreglo estructurado de detalles que se envía al modelo.
  * **`crear(...)` & `editar(...)`:** Se corrigió un bug donde el selector de Matrices en el modal de búsqueda de ensayos aparecía vacío. Ahora se instancía `ProductoModelo` para consultar el listado de categorías (`obtenerCategorias()`) y pasarlo dinámicamente a las vistas correspondientes, permitiendo filtrar los ensayos por sus categorías reales (Suelo, Concreto, Acero, Barro, etc.).

### C. Vistas de Formulario (Creación y Edición)
* **Archivos:** [crear.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/vistas/crear.php) y [editar.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/vistas/editar.php)
  * **Inputs Ocultos:** Se agregó `<input type="hidden" name="ensayo_id_producto[]" class="prod-id-input">` para cada fila de la tabla de cotización.
  * **Datalist de Autocompletado:** El lazo que renderiza la lista de opciones del buscador ahora añade un atributo `data-id="<?= $prod['id'] ?>"` a cada opción.
  * **Lógica Javascript (`completarPrecio`):** Al seleccionar un producto del datalist, el script lee el `data-id` correspondiente y lo escribe de forma asíncrona en el input oculto `.prod-id-input`.
  * **Buscador/Modal de Ensayos:** Se modificó la lógica para que al dar clic en cualquier parte de la fila de un ensayo en el buscador modal (y no solo sobre el pequeño botón de checkbox), la fila se marque, se capture el atributo `data-id` del producto seleccionado y este sea inyectado en la cotización de forma instantánea y fluida.

### D. Vista de Detalle y PDF
* **Archivo de Vista:** [detalle.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/vistas/detalle.php)
  * Muestra el detalle de los ensayos agregados en la cotización. Si un ensayo está vinculado a un producto, renderiza una pequeña sección justo debajo de la descripción:
    > **Código:** CYCSA-XX-XX • **Norma:** ASTM XXXX • **Formato Reporte:** CYCSA-RT-FM-XX
* **Ayudante PDF:** [funciones.php](file:///C:/xampp/htdocs/Cycsa/ayudantes/funciones.php) (`generarCotizacionPDF`)
  * Agrega el mismo bloque de metadatos de calidad del producto de manera compacta y elegante (fuente pequeña, con borde superior punteado) en la columna de descripción de cada ítem de la cotización en formato PDF, garantizando que el diseño portrait no se desborde horizontalmente.

### E. Personalización Visual (Favicon de CYCSA)
* **Archivos:** [layout.php](file:///C:/xampp/htdocs/Cycsa/plantillas/layout.php), [login.php](file:///C:/xampp/htdocs/Cycsa/modulos/autenticacion/vistas/login.php) y [decision_cliente.php](file:///C:/xampp/htdocs/Cycsa/modulos/cotizaciones/vistas/decision_cliente.php)
  * Se agregó la etiqueta `<link rel="shortcut icon" href="/Cycsa/publico/img/logo.png" type="image/png">` dentro de la sección `<head>` de los tres archivos. Esto sustituye el ícono por defecto del servidor XAMPP por el logotipo corporativo de CYCSA en todas las pestañas de navegación del navegador web.

### F. Rediseño Estético Premium
* **Archivos:** [login.php](file:///C:/xampp/htdocs/Cycsa/modulos/autenticacion/vistas/login.php) y [layout.php](file:///C:/xampp/htdocs/Cycsa/plantillas/layout.php)
  * **Pantalla de Login:** Se rediseñó por completo con un fondo radial oscuro y futurista, dos luces difuminadas interactivas de fondo (azul y roja), una tarjeta de acceso semi-translúcida (efecto glassmorphism/esmerilado), íconos interactivos en los inputs de correo/contraseña, micro-animación de entrada en la tarjeta y un botón con gradiente corporativo interactivo.
  * **Panel de Control Interno (Layout):** Se estilizó el panel administrativo implementando una barra de navegación lateral en color pizarra oscuro elegante, un indicador de selección activo de color celeste brillante (`#38bdf8`), bordes extra delgados semi-transparentes y un badge en la barra superior con un avatar circular personalizado (utilizando la inicial del usuario) y una mejor jerarquía visual para el rol del usuario.

---

## 3. Pruebas y Validación Realizadas

Para asegurar que los cambios no introdujeran regresiones, se desarrollaron y ejecutaron pruebas automatizadas a nivel de script PHP y base de datos:

1. **Chequeo de Sintaxis:** Se ejecutó `php -l` en todos los archivos modificados. Todos reportaron código PHP sintácticamente válido.
2. **Prueba de Flujo Completo (`test_quotation_flow.php`):**
   * Se inicializó el núcleo de la aplicación de manera simulada en línea de comandos (CLI) cargando el entorno `.env`.
   * Se insertó una cotización de prueba vinculando su detalle al Producto ID 18 (`CYCSA-RT-FM-22-F` / `ASTM C 2216-19` / formato de reporte `CYCSA-RT-FM-22`).
   * Se consultaron los detalles guardados verificando que los `LEFT JOIN` recuperaran exactamente la información técnica guardada en el catálogo.
   * Se compiló el PDF de cotización mediante Dompdf para validar que no existiera ninguna advertencia y que el bloque de metadatos se renderizara perfectamente.
   * Finalmente, se limpiaron los registros creados para mantener la base de datos limpia de registros de prueba.

---

## 4. Estructura de Calidad Asociada

Los productos vinculados corresponden directamente a los catálogos proporcionados y a los 21 formatos de reportes indexados en [formatos_index.md](file:///C:/Users/abdia/.gemini/antigravity-cli/brain/cc36b5f3-fcd7-4732-a408-0de06a3c7b23/formatos_index.md). 

Esto consolida un sistema unificado donde cada cotización generada no es solo un documento comercial con texto plano, sino que está enlazada digitalmente al estándar técnico y de calidad del laboratorio CYCSA.
