# Reglas de Desarrollo del Proyecto Cycsa

## Sensibilidad de Mayúsculas/Minúsculas en Producción (Linux / Bluehost)
- El entorno de producción en Bluehost es un servidor Linux (sensible a mayúsculas/minúsculas).
- La arquitectura del proyecto sigue el estándar **PSR-4**:
  - `app/Core/` mapeado al namespace `Cycsa\Nucleo\`
  - `app/Modulos/` mapeado al namespace `Cycsa\Modulos\`
  - `app/` mapeado al namespace `Cycsa\App\`
  - `config/` mapeado al namespace `Cycsa\Config\`
- Todas las carpetas de módulos y sus subcarpetas (`Controladores/`, `Modelos/`, `Vistas/`) respetan la nomenclatura PascalCase en el sistema de archivos para garantizar compatibilidad con Linux.
- Si se agregan nuevas clases o directorios, actualiza `composer.json` y ejecuta siempre `composer dump-autoload -o`.
- No colocar contraseñas reales en archivos de repositorio ni en `.env.example`.
- En caso de consultas sobre tareas o colaboración entre agentes, consulta siempre `COORDINATION.md`.
