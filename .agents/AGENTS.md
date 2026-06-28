# Reglas de Desarrollo del Proyecto Cycsa

## Sensibilidad de Mayúsculas/Minúsculas en Producción (Linux)
- El entorno de producción en Bluehost es un servidor Linux (sensible a mayúsculas/minúsculas).
- Todas las carpetas físicas del proyecto están en minúsculas (ej. `nucleo/`, `modulos/`, `ayudantes/`, etc.).
- Las declaraciones de namespaces de PHP usan mayúsculas iniciales (ej. `namespace Cycsa\Nucleo;`).
- **IMPORTANTE**: No asumas que `"Cycsa\\": ""` resolverá automáticamente bajo Linux. Cada nuevo directorio de clases debe ser mapeado de forma explícita en `composer.json` (sección `"autoload" -> "psr-4"`) apuntando a su ruta en minúsculas.
- Si se añaden nuevas clases o carpetas, actualiza `composer.json` y ejecuta siempre `composer dump-autoload -o` para regenerar la configuración antes de compilar o empaquetar para producción.
- Lee siempre [README_DEPLOY.md](file:///C:/xampp/htdocs/Cycsa/README_DEPLOY.md) para más detalles.
