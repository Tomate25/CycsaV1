# Guía de Despliegue y Reglas de Sensibilidad de Mayúsculas/Minúsculas (Case Sensitivity)

Este documento contiene las reglas de configuración esenciales para evitar errores HTTP 500 al desplegar este sistema en servidores Linux (ej. Bluehost) debido a la sensibilidad a mayúsculas y minúsculas del sistema de archivos.

---

## 🚨 REGLAS CRÍTICAS PARA EL AUTOLOAD (PSR-4)

En Windows (entorno local de desarrollo), el sistema de archivos es insensible a mayúsculas/minúsculas. En Linux (producción), es estrictamente sensible.

### 1. Estructura de Carpetas Físicas
Las carpetas del proyecto están organizadas en **minúsculas** (ej. `nucleo/`, `modulos/`, `modulos/autenticacion/controladores/`).

### 2. Espacios de Nombres (Namespaces)
Las clases PHP declaran namespaces usando **mayúsculas iniciales** (ej. `namespace Cycsa\Nucleo;` o `namespace Cycsa\Modulos\Autenticacion\Controladores;`).

### 3. Mapeo en `composer.json`
Para que Composer resuelva correctamente las clases sin romper las rutas de inclusión de vistas y archivos que ya están en minúsculas, **cada namespace con mayúsculas debe estar explícitamente mapeado a su directorio físico en minúsculas** en el archivo `composer.json` bajo la clave `"autoload" -> "psr-4"`.

Ejemplo:
```json
"Cycsa\\Modulos\\Autenticacion\\Controladores\\": "modulos/autenticacion/controladores/"
```

> [!IMPORTANT]
> **Al crear un nuevo módulo o directorio con clases PHP:**
> 1. Asegúrate de añadir la correspondiente regla en la sección `"psr-4"` de `composer.json` mapeando el namespace del nuevo directorio a su ruta física en minúsculas.
> 2. Regenera el autoloader de Composer optimizado ejecutando el comando:
>    ```bash
>    composer dump-autoload -o
>    ```

---

## 📦 PROCESO DE EMPAQUETADO PARA PRODUCCIÓN

Para generar una versión limpia lista para subir a producción (con el directorio `vendor` optimizado y sin historial de desarrollo), utiliza el script PHP automatizado ejecutándolo en tu consola local:

```bash
php C:\Users\abdia\.gemini\antigravity-cli\scratch\zip_project.php
```

### ¿Qué hace este script?
1. Copia todo el proyecto `C:\xampp\htdocs\Cycsa` a un directorio temporal.
2. Elimina archivos innecesarios o de desarrollo como `.git` y el entorno local `.env.local` (dejando el archivo de variables de entorno de producción `.env`).
3. Empaqueta el resultado en un archivo ZIP llamado **`Cycsa_deploy.zip`** y lo deposita en:
   * **Descargas:** `C:\Users\abdia\Downloads\Cycsa_deploy.zip`
   * **Escritorio:** `C:\Users\abdia\OneDrive\Desktop\Cycsa_deploy.zip`
