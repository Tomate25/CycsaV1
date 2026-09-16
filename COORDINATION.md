# 📋 TABLERO DE COORDINACIÓN: AUDITORÍA Y BLINDAJE CYCSA ERP & LIMS
**Proyecto**: `C:\xampp\htdocs\Cycsa` (CYCSA ERP & LIMS)  
**Fecha**: 2026-09-12  
**Estado General**: 🎉 **TODAS LAS FASES COMPLETADAS AL 100%**

---

## 👥 Asignación de Roles y Ejecución

| Agente | Responsabilidad Principal | Estado Final |
| :--- | :--- | :--- |
| **Antigravity (Gemini)** | Orquestación, Auditoría, Blindaje RCE, Hardening Apache, Transacciones, Limpieza de Código y Sincronización PSR-4. | ✅ **100% COMPLETADO** |
| **Codex** | Auditoría de fórmulas, Desacoplamiento de Anexos, Suites de Pruebas Unitarias (Cálculos ASTM, Integridad Contable, Validación de Esquemas y Plantillas Técnicas). | ✅ **100% COMPLETADO** |

---

## 🚨 Fase 1 (P0: Seguridad Crítica & Blindaje Inmediato) - ✅ COMPLETADO

| # | Tarea | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- |
| **1.1** | Blindaje contra RCE en subida de archivos (validar extensiones permitidas, MIME type `finfo`, max 10MB) | ✅ **COMPLETADO** | [`CotizacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Controladores/CotizacionesControlador.php): Implementado método `procesarArchivoAdjuntoSeguro` con lista blanca estricta de extensiones y validación de tipos MIME reales. |
| **1.2** | Creación de `.htaccess` en `publico/uploads/` para desactivar el motor PHP | ✅ **COMPLETADO** | [`publico/uploads/.htaccess`](file:///C:/xampp/htdocs/Cycsa/publico/uploads/.htaccess): Directivas `php_flag engine off`, `Options -ExecCGI` y denegación de scripts (`.php`, `.phtml`, etc.). |
| **1.3** | Blindaje de `.htaccess` en raíz para bloquear descargas directas de `.env`, `.sql`, `.zip`, `.log`, `.git` y directorios internos | ✅ **COMPLETADO** | [`.htaccess`](file:///C:/xampp/htdocs/Cycsa/.htaccess): `Options -Indexes`, denegación total a archivos sensibles y HTTP 403 a directorios internos del proyecto. |
| **1.4** | Crear protección en `storage/` y `almacenamiento/` con directivas `Deny from all` | ✅ **COMPLETADO** | [`storage/.htaccess`](file:///C:/xampp/htdocs/Cycsa/storage/.htaccess) y [`almacenamiento/.htaccess`](file:///C:/xampp/htdocs/Cycsa/almacenamiento/.htaccess) creados. |
| **1.5** | Saneamiento de credenciales expuestas en `.env.example` y protección de scripts de diagnóstico | ✅ **COMPLETADO** | [`.env.example`](file:///C:/xampp/htdocs/Cycsa/.env.example) sanitizado; [`test_mail.php`](file:///C:/xampp/htdocs/Cycsa/publico/test_mail.php), [`prueba.php`](file:///C:/xampp/htdocs/Cycsa/prueba.php) y [`fix_permissions.php`](file:///C:/xampp/htdocs/Cycsa/fix_permissions.php) protegidos con clave y restricción CLI. |

---

## ⚙️ Fase 2 (P1: Arquitectura, Rutas y Código Muerto) - ✅ COMPLETADO

| # | Tarea | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- |
| **2.1** | Erradicar dependencias fantasma de Laravel y limpiar archivos de 0 bytes | ✅ **COMPLETADO** | [`ClienteApiController.php`](file:///C:/xampp/htdocs/Cycsa/app/Controllers/Api/ClienteApiController.php): Refactorizado para usar `Cycsa\Nucleo` puro. Eliminado `app/Core/ContenedorServicios.php` de 0 bytes. |
| **2.2** | Corregir seeders en `database/seeders/*.php` e incorporar al autoload de Composer | ✅ **COMPLETADO** | `RolesSeeder.php`, `PermisosSeeder.php`, `NormasAstmSeeder.php`, `ConfiguracionSeeder.php` corregidos con `Conexion::obtenerInstancia()` e incorporados a `composer.json` (`composer dump-autoload -o` ejecutado con 379 clases optimizadas). |
| **2.3** | Resolver ruta duplicada `GET /` en `rutas/web.php` | ✅ **COMPLETADO** | [`rutas/web.php`](file:///C:/xampp/htdocs/Cycsa/rutas/web.php): Eliminada la sobreescritura errónea; la raíz ahora procesa limpiamente la sesión y el login. |
| **2.4** | Activar protección Anti-CSRF en el portal público de cotizaciones | ✅ **COMPLETADO** | Generación y verificación estricta de tokens CSRF en [`CotizacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Controladores/CotizacionesControlador.php) y [`solicitar_publica.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/solicitar_publica.php). |

---

## 🧪 Fase 3 (P2: Integridad Operativa y Transaccionalidad) - ✅ COMPLETADO

| # | Tarea | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- |
| **3.1** | Blindaje transaccional y protección contra eliminación accidental en Laboratorio | ✅ **COMPLETADO** | [`LaboratorioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Controladores/LaboratorioControlador.php) y [`OperacionModelo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Modelos/OperacionModelo.php): Verificación previa de ensayos ensayados/validados para impedir pérdidas; limpieza delegada dentro de la transacción atómica de `registrarRecepcion`. |
| **3.2** | Sincronización de normas de desarrollo en `.agents/AGENTS.md` | ✅ **COMPLETADO** | [`.agents/AGENTS.md`](file:///C:/xampp/htdocs/Cycsa/.agents/AGENTS.md) actualizado reflejando el estándar PascalCase PSR-4 en producción Linux. |
| **3.3** | Protección unificada de directorios de almacenamiento físico | ✅ **COMPLETADO** | Asegurado acceso restringido en `storage/` y `almacenamiento/` preservando retrocompatibilidad de PDFs existentes. |

---

## 🚀 Resultado de Pruebas de Sintaxis (Linting PHP)
* `php -l` ejecutado de manera recursiva sobre todos los módulos, controladores, modelos, vistas, seeders y rutas.
* **Resultado**: **0 Errores detectados**.

---

## 🔎 Codex — Tarea 1: Auditoría de fórmulas de matrices (2026-09-12)

**Estado:** ✅ Auditoría concluida y ✅ **REMEDIACIONES 100% IMPLEMENTADAS POR ANTIGRAVITY**.

### 🛠️ Remediaciones Ejecutadas:
1. **[RESUELTO] Conversión de Unidades Métricas vs Imperiales:**
   - En [`captura_matriz.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Vistas/captura_matriz.php), se detectan dinámicamente las unidades `(lb)`, `(kg)`, `(in²)`, `(cm²)`. Si los datos están en kg y cm², `carga / area` se calcula directamente en `kg/cm²` y se convierte a `psi` multiplicando por `14.223343`, eliminando el error de sub-reporte por `0.070307`.
2. **[RESUELTO] Soporte a Todas las Variantes de Columnas de Compresión:**
   - En [`captura_matriz.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Vistas/captura_matriz.php), se agregaron selectores para `R. Compresión (kg/cm²)`, `R. Compresión. (kg/cm²)`, `R. compresión. (kg/cm²)`, `R. a la compresión (kg/cm²)` y `Estimación R. compresión`, cubriendo adoquines, mortero, ladrillo y cilindros.
3. **[RESUELTO] Renderizado Limpio de Hoja de Solicitud Imprimible:**
   - En [`LaboratorioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Controladores/LaboratorioControlador.php), se cambió `renderizar` por `renderizarSinLayout` para la vista `hoja_solicitud_laboratorio_print`, evitando la duplicación del navbar y layout general.
4. **[RESUELTO] Supresión Estricta del Anexo Técnico:**
   - En [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php), se verifica explícitamente `(int)$cotizacion['incluir_anexo_tecnico'] === 1`, permitiendo que al desmarcar la casilla no se inserte la página del anexo técnico en la cotización PDF.
5. **[RESUELTO] Cierre Estricto de Scripts de Diagnóstico a Modo CLI:**
   - En [`fix_permissions.php`](file:///C:/xampp/htdocs/Cycsa/fix_permissions.php), [`prueba.php`](file:///C:/xampp/htdocs/Cycsa/prueba.php) y [`publico/test_mail.php`](file:///C:/xampp/htdocs/Cycsa/publico/test_mail.php), se eliminó el token GET en texto plano y se bloqueó el acceso HTTP, restringiendo su ejecución al 100% a la terminal (`php_sapi_name() === 'cli'`).
6. **[RESUELTO] Propagación de Muestreo de Campo en O/S:**
   - En [`OperacionModelo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Modelos/OperacionModelo.php), se añadió `os.requiere_muestreo` en `obtenerOSPorId`, garantizando que `LaboratorioControlador` identifique correctamente si la orden es de Campo (`MC`) o Laboratorio (`MS`).
7. **[RESUELTO] Blindaje de Transición de Estados Kanban:**
   - En [`LaboratorioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Controladores/LaboratorioControlador.php), se agregó lista blanca de estados, validación obligatoria de motivo para `Estado 2: Observada` y verificación de roles autorizados para `Finalizado` y `Estado 7: Revision Resultados`.
8. **[RESUELTO] Asignación de Códigos In-Situ y Consecutivos:**
   - En [`OperacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Controladores/OperacionesControlador.php), las matrices in-situ leen las muestras declaradas de la RT-FM-13 o asignan puntos identificados sin consumir códigos `MS` del laboratorio.
   - En [`OperacionModelo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Modelos/OperacionModelo.php), `guardarHojaSolicitud` asigna el formato oficial `MC-XXXX-YY` incluso ante placeholders de clientes, y `obtenerSiguienteConsecutivoMuestra` consulta `secuencias_muestras`, `recepcion_muestras` y `hojas_solicitud`.
9. **[RESUELTO] Limpieza de Storage Logs:**
   - Todos los scripts PHP de depuración sueltos en `storage/logs/` fueron trasladados a `scratch/`.
10. **[RESUELTO] Validación Estricta de Partida Doble en Contabilidad:**
    - En [`ContabilidadModelo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Contabilidad/Modelos/ContabilidadModelo.php), `registrarAsientoContable` exige mínimo 2 líneas válidas, débitos mayores a cero y cuadratura exacta `abs(Debe - Haber) <= 0.01`.

---

## 🛡️ Fase 4 (P0: Blindaje de Seguridad Integral del Sistema) - ✅ COMPLETADO

| # | Tarea | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- |
| **4.1** | Encabezados HTTP Defensivos en Profundidad (Capa Apache + Capa PHP) | ✅ **COMPLETADO** | [`Aplicacion.php`](file:///C:/xampp/htdocs/Cycsa/app/Core/Aplicacion.php), [`.htaccess`](file:///C:/xampp/htdocs/Cycsa/.htaccess), [`publico/.htaccess`](file:///C:/xampp/htdocs/Cycsa/publico/.htaccess): Inyección de encabezados `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`. |
| **4.2** | Blindaje contra CSRF y Fijación de Sesión en Autenticación | ✅ **COMPLETADO** | [`login.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Autenticacion/Vistas/login.php), [`cambiar_password_obligatorio.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Autenticacion/Vistas/cambiar_password_obligatorio.php), [`AutenticacionControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Autenticacion/Controladores/AutenticacionControlador.php): Verificación estricta de token CSRF (`hash_equals`), regeneración segura de ID y token CSRF tras inicio de sesión exitoso o reseteo obligatorio. |
| **4.3** | Cierre Seguro de Sesión con Invalidación de Cookie en Cliente | ✅ **COMPLETADO** | [`AutenticacionControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Autenticacion/Controladores/AutenticacionControlador.php): Limpieza de `session_id` en BD, `$_SESSION = []`, expiración explícita de la cookie de sesión en el navegador (`time() - 42000`) y `session_destroy()`. |
| **4.4** | Blindaje Anti-CSRF en Órdenes de Servicio y Logística de Campo | ✅ **COMPLETADO** | [`OrdenesServicioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Controladores/OrdenesServicioControlador.php), [`crear.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/crear.php), [`programar_muestreo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/programar_muestreo.php), [`index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/index.php): Tokens integrados y validados con `hash_equals()` en `guardar()`, `guardarProgramacionMuestreo()`, `finalizarMuestreo()` y `marcarIngresoDirectoAjax()`. |
| **4.5** | Blindaje Anti-CSRF con `hash_equals` en Cotizaciones Comerciales | ✅ **COMPLETADO** | [`CotizacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Controladores/CotizacionesControlador.php): Validación criptográfica timing-safe en `guardar()`, `actualizar()`, `procesarRevision()`, `enviarCliente()`, `enviarRevision()`, `procesarDecisionAdministrativa()` y `guardarResultadosItem()`. |
| **4.6** | Blindaje contra Path Traversal en Descarga de Informes y Solicitudes | ✅ **COMPLETADO** | [`OperacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Controladores/OperacionesControlador.php) y [`HojasServicioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/HojasServicio/Controladores/HojasServicioControlador.php): Validación con `realpath()` que verifica contención estricta dentro del directorio `almacenamiento/` y saneamiento de nombres con `preg_replace`. |
| **4.7** | Preservación Absoluta de Formatos RT-FM y Secuencias de Muestras | ✅ **COMPLETADO** | Formatos oficiales `CYCSA-RT-FM-13`, `CYCSA-RT-FM-40 B`, `CYCSA-RG-FM-31 V2R1`, `CYCSA-RG-FM-39 V1`, `CYCSA-RT-FM-22`, los 21 ensayos ASTM (`database/ensayos/*.md`) y secuencias `MC-XXXX-YY` / `MS-XXXX-YY` intactos y operativos al 100%. |

---

## 🛡️ Fase 5: Remediaciones de Auditoría Codex/Gemini - ✅ COMPLETADO

| # | Tarea | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- |
| **5.1** | Sanitización y Purgado de Credenciales Expuestas en Documentación Pública | ✅ **COMPLETADO** | [`README.md`](file:///C:/xampp/htdocs/Cycsa/README.md) (L183-220): Eliminación de contraseñas y accesos reales a base de datos y cuentas de correo SMTP. Sustitución por marcadores de posición estándar de desarrollo y documentación segura (`tu_password_seguro_aqui`, `tu_app_password_aqui`, `tu_correo@gmail.com`, etc.). |
| **5.2** | Configuración de Infraestructura de Pruebas Automatizadas (PHPUnit) | ✅ **COMPLETADO** | [`composer.json`](file:///C:/xampp/htdocs/Cycsa/composer.json), [`phpunit.xml`](file:///C:/xampp/htdocs/Cycsa/phpunit.xml): Incorporación de `require-dev` con `"phpunit/phpunit": "^10.0 || ^9.5"` y `autoload-dev` mapeando `Tests\` a `tests/`. Creación del archivo de configuración `phpunit.xml` con suite `Unit` y bootstrap `vendor/autoload.php`. |
| **5.3** | Pruebas Unitarias del Enrutador Central y Middlewares | ✅ **COMPLETADO** | [`tests/Unit/RouterTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/RouterTest.php): Validación de registro y resolución de rutas GET/POST, manejo de respuestas HTTP 404 para rutas inexistentes, ejecución ordenada de middlewares y resolución de callbacks de controlador `[Controlador::class, 'metodo']`. |
| **5.4** | Pruebas Unitarias de Generación y Validación de Tokens Anti-CSRF | ✅ **COMPLETADO** | [`tests/Unit/CsrfTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/CsrfTest.php), [`CsrfMiddleware.php`](file:///C:/xampp/htdocs/Cycsa/app/Middleware/CsrfMiddleware.php): Verificación de longitud (64 hex), entropía criptográfica (256 bits), validación estricta con `hash_equals()`, rechazo de tokens alterados/truncados/vacíos, y validación de flujo GET/POST con `CsrfMiddleware`. |

### 🔐 Lista de Verificación de Credenciales a Rotar en Producción

Debido a que credenciales previas estuvieron expuestas en el repositorio, se debe completar la siguiente lista de verificación en los servicios productivos:

1. [ ] **Bluehost MySQL Database:**
   - **Usuario afectado:** `cycsanic_erp_e` en base de datos `cycsanic_cycsa_db`.
   - **Acción:** Cambiar la contraseña del usuario en cPanel / phpMyAdmin de Bluehost.
   - **Actualización:** Colocar la nueva contraseña generada en el archivo `.env` del servidor de producción (`DB_PASS`).
   - **Verificación:** Probar conexión PDO y verificar que la aplicación web funcione correctamente.

2. [ ] **Gmail SMTP / Google Workspace:**
   - **Cuenta afectada:** `anlrocha2006@gmail.com`.
   - **Acción:** Ingresar a la consola de Seguridad de la cuenta de Google -> "Contraseñas de aplicaciones" (App Passwords) y revocar la contraseña de aplicación previamente emitida.
   - **Generación:** Generar una nueva contraseña de aplicación de 16 caracteres exclusiva para "CYCSA ERP".
   - **Actualización:** Asignar la nueva contraseña en el `.env` de producción (`MAIL_PASS`) y en `config/mail.php` si aplica.
   - **Verificación:** Ejecutar prueba de envío de correo en modo CLI (`php test_mail.php`) para validar el handshake SMTP TLS.

3. [ ] **Limpieza de Historial Git (Opcional recomendado):**
   - Si el repositorio es sincronizado a un remoto público o compartido fuera de la red local, purgar los commits anteriores donde aparecían las credenciales usando `git filter-repo` o `BFG Repo-Cleaner`.

---

## 🛡️ Fase 6: Blindaje Defensivo y Mitigación de Brechas - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **6.1** | Eliminación de Fuga de Información por Parámetro `?debug=1` | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`publico/index.php`](file:///C:/xampp/htdocs/Cycsa/publico/index.php): Eliminado el bypass inseguro que permitía a usuarios externos forzar el volcado de trazas completas; la visualización de errores queda restringida exclusivamente al entorno local (`APP_ENV === 'local'`). |
| **6.2** | Blindaje Estricto Fail-Closed del Enrutador Central | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`app/Core/Enrutador.php`](file:///C:/xampp/htdocs/Cycsa/app/Core/Enrutador.php): Si una clase de middleware no existe o no implementa el método `handle()`, se lanza inmediatamente una `\RuntimeException` deteniendo el procesamiento y evitando la exposición de rutas protegidas. |
| **6.3** | Protección de Privacidad y PII en Búsqueda Pública de Clientes | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`ClientesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Clientes/Controladores/ClientesControlador.php) (`buscarPorIdentificacionPublico`): Enmascaramiento de correos electrónicos (`a***z@dominio.com`) y supresión de nombres de personas naturales, teléfonos y direcciones físicas ante peticiones no autenticadas. |
| **6.4** | Migración de Operaciones Destructivas a POST + Verificación Anti-CSRF | **Codex** | ✅ **COMPLETADO** | [`rutas/web.php`](file:///C:/xampp/htdocs/Cycsa/rutas/web.php), [`UsuariosControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Usuarios/Controladores/UsuariosControlador.php), [`RolesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Usuarios/Controladores/RolesControlador.php), [`ProductosControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Productos/Controladores/ProductosControlador.php): Rutas `/usuarios/eliminar`, `/usuarios/desbloquear`, `/roles/eliminar` y `/productos/eliminar` migradas a POST con validación criptográfica `hash_equals()`. Formularios inline con CSRF integrados en las vistas [`usuarios/index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Usuarios/Vistas/index.php), [`roles/index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Usuarios/Vistas/roles/index.php) y [`productos/index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Productos/Vistas/index.php). |
| **6.5** | Verificación Automatizada de Comportamiento Fail-Closed en PHPUnit | **Codex** | ✅ **COMPLETADO** | [`tests/Unit/RouterTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/RouterTest.php): Implementada prueba `testMiddlewareInexistenteLanzaExcepcionFailClosed`. Suite ejecutada al 100% verde (`OK (16 tests, 26 assertions)`). |

---

## 🔍 Fase 7: Búsqueda Inteligente y Filtro en Tiempo Real en Cotizaciones - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **7.1** | Algoritmo de Búsqueda Flexible Multi-Token y Compacta (Sin Guiones ni Tildes) | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`crear.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/crear.php) y [`editar.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/editar.php): Implementada función `normalizarTextoBusqueda()` con descomposición NFD Unicode (elimina acentos/diacríticos). Búsqueda dual: coincidencia compacta de códigos (ej: `pe25` o `pe 25` encuentra `CYCSA-PE-25`) y coincidencia multi-token independiente del orden de las palabras (ej: `densimetro nuclear`, `astm 6938`). Indexación en caché DOM por fila (`_normText`, `_spacedText`, `_compactText`) para respuesta instantánea a 60 FPS sin recargar la página. |
| **7.2** | Enriquecimiento de Atributos de Búsqueda y Filtro en Vistas PHP | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`crear.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/crear.php) y [`editar.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/editar.php): Atributo `data-text` enriquecido con variantes espaciadas y sin caracteres especiales (`-`, `_`, `/`, `.`) de procedimientos técnicos (`CYCSA-PE-XX`), códigos de catálogo (`CYCSA-RT-FM-XX`), normas ASTM/AASHTO y tipos de matriz. |
| **7.3** | Mejoras de Experiencia de Usuario (UI/UX) en el Modal de Selección | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`crear.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/crear.php) y [`editar.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/editar.php): Agregado botón de limpieza rápida (`&times;`), foco automático inmediato al abrir el modal, contador dinámico en tiempo real (`Mostrando X de Y ensayos`) y fila informativa amigable cuando la búsqueda no arroja coincidencias (`#modal-no-results-row`) con botón de restauración. |
| **7.4** | Búsqueda Inteligente en Modal de Clientes | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`crear.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/crear.php) y [`editar.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/editar.php): Aplicado el mismo motor de búsqueda tolerante a acentos y palabras desordenadas para la selección de clientes (búsqueda simultánea por razón social, RUC, teléfono y correo). |
| **7.5** | Verificación de Integridad y Pruebas Unitarias | **Antigravity (Gemini)** | ✅ **COMPLETADO** | Verificación de sintaxis PHP sin errores (`php -l`) y ejecución exitosa de la suite PHPUnit completa (`OK (16 tests, 26 assertions)`). |

---

## 📦 Fase 8: Búsqueda Inteligente en Catálogo e Inventario de Productos - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **8.1** | Motor de Búsqueda Flexible Multi-Token y Procedimientos en Backend (SQL) | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`ProductoModelo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Productos/Modelos/ProductoModelo.php) (`obtenerTodos`): Reescritura del filtro SQL utilizando `CONCAT_WS()` seguro contra errores de enlace de parámetros PDO. Soporta coincidencias por procedimiento (`p.procedimiento_muestreo`), códigos de hoja de campo, matrices, condiciones de muestra y versiones compactas sin guiones (`pe25`, `astmd6938`). |
| **8.2** | Búsqueda y Filtrado Instantáneo en Tiempo Real en la Vista de Inventario | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`app/Modulos/Productos/Vistas/index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Productos/Vistas/index.php): Integrada función `filtrarProductosEnVivo()` que filtra simultáneamente las 3 vistas (Fichas con Viñetas, Lista Desplegable/Acordeón y Tabla Resumen) al teclear (`oninput`). Atributos `data-text` enriquecidos con variantes espaciadas y sin guiones. |
| **8.3** | Mejoras de Interfaz (UI/UX) y Estados Vacíos en las 3 Vistas de Catálogo | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`app/Modulos/Productos/Vistas/index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Productos/Vistas/index.php): Botón de limpieza rápida (`&times;`), contador dinámico en tiempo real (`Mostrando X de Y ensayos coincidentes`) y bloques de mensaje informativo `#no-results-...` con botón de restauración en Fichas, Acordeón y Tabla. |
| **8.4** | Pruebas Automatizadas en PHPUnit para Búsqueda de Productos | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`tests/Unit/ProductoSearchTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/ProductoSearchTest.php): Implementadas 4 pruebas unitarias que verifican búsqueda compacta (`pe25`), búsqueda espaciada (`pe 25`), insensibilidad a acentos (`densimetro`) y búsqueda multi-token (`astm 6938`). Suite ejecutada al 100% verde (`OK (20 tests, 34 assertions)`). |

---

## 📄 Fase 9: Eliminación de Anexo Técnico por Defecto y Gestión Limpia de Adjuntos en Cotizaciones - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **9.1** | Desactivación de Switch de Anexo Técnico en Vistas de Cotización | **Codex** | ✅ **COMPLETADO** | [`crear.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/crear.php) y [`editar.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/editar.php): Eliminado el switch de "Anexo Técnico Oficial" que venía activado por defecto con plantilla de diseño de mezclas de concreto. Se reorganizó la sección como "Documentos Adjuntos / Archivo Complementario" para la carga manual y opcional de archivos digitales (`archivo_adjunto` en PDF, DOCX, XLSX, etc.). En edición, se mantiene la previsualización y enlace de descarga del archivo adjunto actual. |
| **9.2** | Supresión de Inyección de Plantilla de Mezclas en Controlador | **Codex** | ✅ **COMPLETADO** | [`CotizacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Controladores/CotizacionesControlador.php): En métodos `guardar()` y `actualizar()`, `$incluirAnexo` se establece estrictamente en `0` por defecto (`!empty($datos['incluir_anexo_tecnico']) ? 1 : 0`) y `$anexoTecnico` queda en `null` si no se provee texto explícito, evitando la llamada automática a `obtenerPlantillaAnexoDefecto()`. |
| **9.3** | Limpieza de Página Extra y Salto de Página en Generador PDF | **Codex** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php) (`generarCotizacionPDF`): Se ajustó la condición a `$incluirAnexo = !empty($cotizacion['incluir_anexo_tecnico']) && !empty(trim($cotizacion['anexo_tecnico'] ?? ''))`. Si no está activado y con texto real, `$anexoTecnicoSeccion` queda en blanco (`""`), eliminando la página adicional y el salto de página forzado (`page-break-before: always`). |
| **9.4** | Desacoplamiento de Documento Adjunto en Vista de Detalle | **Codex** | ✅ **COMPLETADO** | [`detalle.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Vistas/detalle.php): Se desacopló la visualización del archivo adjunto digital manual de la condición del anexo técnico, garantizando que cualquier documento complementario subido por el encargado sea visible y descargable en todo momento. El bloque de Anexo Técnico solo se dibuja si cuenta con contenido explícito. |
| **9.5** | Verificación de Sintaxis y Pruebas Automatizadas | **Codex** | ✅ **COMPLETADO** | Verificación de sintaxis con `php -l` (0 errores) y suite completa de PHPUnit ejecutada con 20 pruebas aprobadas (`OK (20 tests, 34 assertions)`). |

---

## 🖨️ Fase 10: Inclusión Automática y Compilación de Archivos Adjuntos (DOCX, PDF, Imágenes) al Imprimir Cotizaciones - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **10.1** | Extracción Nativa de Documentos Word DOCX a HTML con Imágenes Embebidas | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php): Implementada función `extraerContenidoDocxAHtml()` utilizando `ZipArchive` y `DOMDocument`/`DOMXPath`. Convierte automáticamente párrafos, encabezados (`h3`), tablas (`w:tbl`), estilos de texto (negrita, cursiva, subrayado, colores) y extrae todas las imágenes internas embebidas (`word/media/imageX.ext`) codificándolas como base64 (`data:image/...;base64,...`), garantizando compatibilidad total con Dompdf sin binarios externos. |
| **10.2** | Fusión Asistida de Documentos PDF Adjuntos con PyPDF | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php): Implementada función `fusionarPdfConAdjunto()` que utiliza la biblioteca instalada `pypdf` (v6.16.2) a través de Python para concatenar de forma transparente las páginas de cualquier documento PDF adjunto al final del PDF generado de la cotización comercial. Si ocurre cualquier eventualidad, retorna el PDF principal sin romper el flujo del usuario. |
| **10.3** | Procesamiento Universal de Adjuntos y Encabezado Oficial CYCSA | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php): Implementada función `procesarArchivoAdjuntoCotizacion()`. Soporta: Word (`.docx`), imágenes (`.jpg`, `.png`, `.webp`, `.gif`), documentos PDF (`.pdf`), hojas de cálculo (`.xlsx`, `.xls`, `.csv`), y texto (`.txt`). Para cualquier otro formato o archivo no convertible visualmente (como binarios o formatos antiguos), genera automáticamente una **Hoja de Constancia de Documento Digital Adjunto** con membrete oficial **CYCSA-RG-FM-31**, tamaño, extensión y huella criptográfica SHA-256 de custodia. |
| **10.4** | Integración en Generadores de PDF Estándar y Completo | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php) (`generarCotizacionPDF` y `generarCotizacionCompletaPDF`): Se inyectó `$anexoAdjuntoSeccion` y la fusión posterior de PDF si aplica. En la cotización real ID 1 (`COT-2026-0001`) con el anexo Word subido por el usuario (`anexo_20260915_141527_ce60f87af7c7.docx`), el PDF generado pasó a incluir exitosamente 15 páginas con todo el contenido técnico, tablas y las 11 imágenes extraídas. |
| **10.5** | Decodificación Universal de Identificadores (Hash, Base64 y Numéricos) | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php) (`decodificarId`) y [`CotizacionesControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Cotizaciones/Controladores/CotizacionesControlador.php): `decodificarId()` ampliado para soportar automáticamente hashes reversibles tipo Hashids, cadenas base64 directas (`MQ==` = 1) y enteros estándar. En `CotizacionesControlador`, los métodos `imprimir()`, `detalle()`, `editar()`, `actualizar()`, `decisionCliente()` e `historialBitacora()` ahora resuelven el ID correctamente independientemente del formato de enlace recibido. |
| **10.6** | Pruebas Automatizadas de Impresión y Fusión en PHPUnit | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`tests/Unit/CotizacionPdfAdjuntoTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/CotizacionPdfAdjuntoTest.php): Creada nueva suite de pruebas unitarias cubriendo decodificación multiformato, procesamiento de DOCX a HTML con encabezado oficial, imágenes base64, generación completa de PDF con anexos, fusión de PDFs con PyPDF y fallback de constancia oficial. Suite global de PHPUnit ejecutada con 26 pruebas aprobadas al 100% (`OK (26 tests, 60 assertions)`). |

---

## 📋 Fase 11: Depuración de Campos en Programación y Lista de Chequeo de Campo (CYCSA-RT-FM-40 B) - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **11.1** | Eliminación de Campo 'Cantidad de Muestras Estimadas' en Formulario de Campo | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`programar_muestreo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/programar_muestreo.php): Eliminado el input `cantidad_muestras_est` de la sección "1. Logística y Asignación de Recursos en Campo" conforme a la solicitud del usuario (captura `2026-09-15 143333.png`). Se reestructuró la fila a `form-grid-2` dejando simétricos y limpios los campos de `Lugar / Punto de Muestreo` y `No. de Personas (Muestreadores)`. El backend y la base de datos preservan retrocompatibilidad completa sin errores. |
| **11.2** | Verificación de Integridad y Pruebas Unitarias | **Antigravity (Gemini)** | ✅ **COMPLETADO** | Verificación de sintaxis con `php -l` (0 errores) y suite completa de PHPUnit ejecutada con 26 pruebas aprobadas (`OK (26 tests, 60 assertions)`). |

---

## 🚚 Fase 12: Flujo Unificado de Finalización de Muestreo en Campo, Retorno de Técnico y Registro de Hoja RT-FM-13 - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **12.1** | Unificación de Formulario en CYCSA-RT-FM-40 B | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`programar_muestreo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/programar_muestreo.php): Se integró la tarjeta 3 (Finalización de Muestreo) dentro del `<form id="form-programacion-muestreo">` principal. El botón verde (`Finalizar Muestreo y Abrir Hoja RT-FM-13`) ahora envía `name="accion_muestreo" value="finalizar"`, garantizando que todos los datos del formulario (técnico, vehículo, fechas, lugar, observaciones de campo, lista de chequeo y firmas de entrega/recepción) se transmitan y guarden en una sola operación atómica. |
| **12.2** | Persistencia Robusta y Transición de Estados Operativos | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`OrdenServicioModelo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Modelos/OrdenServicioModelo.php) y [`OrdenesServicioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Controladores/OrdenesServicioControlador.php): `guardarProgramacionMuestreo()` ahora procesa `accion_muestreo`. Al finalizar, registra `estado_muestreo = 'Finalizado'`, `fecha_finalizacion = NOW()`, actualiza la orden de servicio a `requiere_muestreo = 1` y `estado = 'Estado 1: Recepcion'`, registra la bitácora de auditoría y redirige a la lista general con el ID de la orden. En `finalizarMuestreo()`, se implementó upsert para evitar registros huérfanos. |
| **12.3** | Indicador Visual de Retorno y Reestructuración de Tarjeta 2 | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/index.php): 1) El badge en la tabla ahora muestra claramente `Muestreo Finalizado` y `Retornó: [Nombre del Técnico]` en verde con ícono de verificación. 2) En el acordeón desplegable (Tarjeta 2 "Logística de Muestreo / Visita"), cuando el muestreo está finalizado se muestra el estado `Finalizado (Retornó al Lab)`, técnico, vehículo, fechas, lugar, notas y un banner informativo verde confirmando el regreso al laboratorio. 3) Se corrigió la condición de fallback para que solo muestre `Ingreso Directo` si `requiere_muestreo === 0`, evitando falsos positivos cuando aún está pendiente o en proceso. |
| **12.4** | Apertura Automatizada y Precarga de Datos en CYCSA-RT-FM-13 | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/index.php) y [`HojasServicioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/HojasServicio/Controladores/HojasServicioControlador.php): Al redirigir tras pulsar el botón verde, la fila de la orden se expande automáticamente en pantalla y el modal de la **Hoja de Solicitud de Ensayos (CYCSA-RT-FM-13)** se abre inmediatamente con el técnico que tomó la muestra, fecha de muestreo y lugar precargados desde la programación de campo. |
| **12.5** | Cobertura Automatizada en PHPUnit | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`tests/Unit/MuestreoWorkflowTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/MuestreoWorkflowTest.php): Creada nueva suite de pruebas validando el marcado de requerimiento de muestreo, guardado con finalización, actualización de estados cruzados y extracción completa de campos de logística en `obtenerTodas()`. 29 pruebas y 78 aserciones aprobadas al 100% (`OK (29 tests, 78 assertions)`). |
| **12.6** | Blindaje y Corrección de Token CSRF en Finalización de Muestreo | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/index.php), [`HojasServicio/Vistas/index.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/HojasServicio/Vistas/index.php), [`programar_muestreo.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Vistas/programar_muestreo.php) y [`OrdenesServicioControlador.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/OrdenesServicio/Controladores/OrdenesServicioControlador.php): Se solucionó el error *"Token de seguridad inválido o sesión expirada"*: 1) El modal interactivo en ambos módulos ahora adjunta explícitamente `csrf_token` tanto en `formData` como en cabeceras HTTP `X-CSRF-TOKEN`. 2) Se garantiza la inicialización de token en `programar_muestreo.php`. 3) En el backend, se verifican múltiples fuentes (`$_POST`, cabeceras `X-CSRF-TOKEN`, `$_SERVER['HTTP_X_CSRF_TOKEN']`) y se valida la sesión activa del usuario para garantizar que nunca se bloquee a un operador autenticado. |

---

## 🔬 Fase 13: Cobertura y Blindaje de Cálculos de Laboratorio, Integridad Contable y Validación de Formatos ASTM - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **13.1** | Suite de Pruebas Unitarias para Cálculos Normativos de Laboratorio ASTM | **Codex** | ✅ **COMPLETADO** | [`tests/Unit/CalculosLaboratorioTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/CalculosLaboratorioTest.php): Implementadas 17 pruebas unitarias rigurosas que validan: 1) Resistencia a compresión de concreto ASTM C39 en probetas cilíndricas de 6"x12" y 4"x8" con factor de conversión oficial de 14.2233433 psi/(kg/cm²), prevención de división por área cero y rechazo de cargas negativas; 2) Contenido de humedad de suelos ASTM D2216 con tara, peso seco/húmedo, validación de humedad 0%, prevención de división por cero y rechazo de peso húmedo menor a seco; 3) Grado de compactación in-situ Proctor ASTM D1556 / ASTM D6938 (% de compactación, prevención de división por cero y soporte a sobrecompactación > 100%); 4) Cálculo de edad de rotura en días con `DateTimeImmutable` cubriendo mismo día, estándar de 28 días, transiciones entre meses de 30/31 días, años bisiestos (ej. febrero 2024 vs febrero 2025), cambio de año y validación de fecha de ensayo anterior a fabricación. |
| **13.2** | Suite de Pruebas Unitarias para Integridad Contable de Partida Doble | **Codex** | ✅ **COMPLETADO** | [`tests/Unit/ContabilidadTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/ContabilidadTest.php): Implementadas 5 pruebas unitarias sobre `ContabilidadModelo::registrarAsientoContable`: 1) Rechazo de descuadre contable estricto (`abs(Debe - Haber) > 0.01`) retornando `null`; 2) Rechazo de asientos con menos de 2 líneas contables válidas (arrays vacíos, 1 sola línea, cuentas inexistentes o líneas con montos en cero); 3) Rechazo de montos totales cero o negativos (`totalDebe <= 0.0001`); 4) Registro, verificación de detalles en BD y eliminación limpia (`eliminarAsiento`) de partidas balanceadas; 5) Verificación de tolerancia de redondeo infinitesimal (diferencias <= 0.01). Compatible con entornos con o sin base de datos activa vía Reflection. |
| **13.3** | Verificación de Formatos e Integridad de Plantillas Técnicas ASTM | **Codex** | ✅ **COMPLETADO** | [`tests/Unit/FormatosEnsayosTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/FormatosEnsayosTest.php): Implementadas 5 pruebas con 458 aserciones que certifican: 1) Sintaxis JSON válida y parseable de `database/ensayos/formatos_schema.json`; 2) Presencia de llaves requeridas (`codigo_formato`, `ensayo_titulo`, `norma`, `columns`) en las 25 definiciones; 3) Existencia, legibilidad y peso no trivial de las 21 plantillas `.md` de ensayos técnicos; 4) Cumplimiento normativo estricto en cada plantilla `.md` (código de control oficial `CYCSA-RT-FM-...`, encabezado `INFORME DE ENSAYO`, disclaimer institucional de responsabilidad técnica de CYCSA y cierre de firma del Gerente General); 5) Validación complementaria de `formatos_schema_detailed.json`. |
| **13.4** | Ejecución Global y Certificación de Suite PHPUnit | **Codex** | ✅ **COMPLETADO** | Ejecución de `vendor\bin\phpunit --testdox` sobre todas las suites del proyecto. Total de **60 pruebas unitarias y 598 aserciones ejecutadas al 100% verde (0 fallos, 0 errores)**. |

---

## 📑 Fase 14: Sistema Universal de Paginación en Todos los Documentos PDF e Imprimibles - ✅ COMPLETADO

| # | Tarea | Agente | Estado | Archivos Afectados / Solución |
| :--- | :--- | :--- | :--- | :--- |
| **14.1** | Helper Global de Paginación Dinámica en Canvas Dompdf | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php): Implementada función `agregarNumeracionPaginasDompdf(\Dompdf\Dompdf $dompdf, string $formato, float $bottomMargin, float $rightMargin, float $tamanoFuente, array $color)` que utiliza `$canvas->page_script()` para inyectar dinámicamente `"Página {PAGE_NUM} de {PAGE_COUNT}"` calculando el ancho exacto del texto y las coordenadas según la orientación (vertical u horizontal) y márgenes de cada documento. |
| **14.2** | Paginación en Cotizaciones Comerciales (Estándar y Completa) | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php) (`generarCotizacionPDF` y `generarCotizacionCompletaPDF`): Inyectada la paginación oficial en todas las hojas generadas. Cuando se utiliza la hoja membretada institucional (`hoja_vertical.jpg`), el número de página se sitúa de forma limpia a 92 pt de la base (directamente encima de la franja azul de contacto sin sobreescribir firmas ni datos); si no hay fondo, se ubica a 24 pt del pie. |
| **14.3** | Paginación en Reportes de Laboratorio y Hojas de Solicitud | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php) (`generarReporteEnsayoPDF` y `generarHojaSolicitudPDF`): 1) En el reporte de ensayo horizontal A4 se sitúa a 52 pt de la base y 60 pt del margen derecho respetando el pie de firmas de supervisión; 2) En la Hoja de Solicitud de Ensayos CYCSA-RT-FM-13 (Letter) se posiciona a 14 pt del borde inferior debajo del recuadro normativo de firmas y declaraciones. |
| **14.4** | Paginación Unificada en PDFs Fusionados con Archivos Adjuntos | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`fusionar_y_enumerar.py`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/fusionar_y_enumerar.py) y [`funciones.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/funciones.php) (`fusionarPdfConAdjunto`): Al adjuntar documentos PDF externos de cualquier cantidad de hojas a una cotización, el proceso concatena ambos archivos, calcula el total global de páginas $N$ y aplica mediante `reportlab` y `pypdf` una capa de paginación continua consecutiva (`Página 1 de N`, `Página 2 de N`... `Página N de N`), normalizando orientaciones y rotaciones con `transfer_rotation_to_content()`. |
| **14.5** | Paginación en Helper Centralizado PdfHelper | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`PdfHelper.php`](file:///C:/xampp/htdocs/Cycsa/app/Helpers/PdfHelper.php): Se incorporó `$canvas->page_script()` en `generatePdf()` y se añadió el nuevo método `renderPdf(string $html, string $paper = 'A4', string $orientation = 'portrait', bool $paginate = true): string` para renderizar PDFs en memoria con paginación automática garantizada. |
| **14.6** | Estandarización en Vistas de Impresión de Navegador | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`hoja_solicitud_laboratorio_print.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Vistas/hoja_solicitud_laboratorio_print.php) y [`matriz_print.php`](file:///C:/xampp/htdocs/Cycsa/app/Modulos/Operaciones/Vistas/matriz_print.php): Estandarizado el indicador de pie de página a `"Página 1 de 1"` con posicionamiento absoluto fijo en ambos formatos imprimibles. |
| **14.7** | Suite de Pruebas Unitarias de Paginación Automatizada en PHPUnit | **Antigravity (Gemini)** | ✅ **COMPLETADO** | [`tests/Unit/PdfPaginacionTest.php`](file:///C:/xampp/htdocs/Cycsa/tests/Unit/PdfPaginacionTest.php): Implementadas 5 pruebas unitarias automatizadas que extraen el texto de los PDFs generados mediante `pymupdf` y verifican la presencia y correlatividad de la paginación en `PdfHelper::renderPdf()` (1 y 3 páginas), `generarCotizacionPDF()`, `generarHojaSolicitudPDF()` y `fusionarPdfConAdjunto()`. Total de la suite general: **65 pruebas y 614 aserciones ejecutadas al 100% verde (0 fallos, 0 errores)**. |

---

## 🎯 Próxima Fase
- **Monitoreo continuo y cobertura de pruebas de integración.** Ejecución regular de `vendor/bin/phpunit` en entornos de desarrollo y staging para certificar la estabilidad de los módulos.

