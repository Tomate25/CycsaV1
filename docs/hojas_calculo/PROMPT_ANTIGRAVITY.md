# Prompt para el agente implementador (Antigravity)

Eres un desarrollador PHP trabajando en `C:\xampp\htdocs\Cycsa`. Las especificaciones están en `C:\xampp\htdocs\Cycsa\docs\hojas_calculo` (copia de este paquete). Léelas completas: README.md, MAPA_MODULOS.md, `_esquema.json`, `formatos/`, `catalogos/`.

## Reglas
- Cambios **solo aditivos**: no modificar ni borrar tablas, columnas, rutas ni los 21 formatos de `database/ensayos/formatos_schema.json` (RT-FM-22). Nuevas tablas = migraciones nuevas (007+).
- Las hojas de cálculo alimentan los reportes existentes según `alimenta_rt_fm_22`.
- Fórmulas: sintaxis de `EvaluadorFormula.php` (sin IF/SUM/AVERAGE, sin notación científica). Agregaciones → servicio PHP nuevo.
- Correcciones `dudosa`: implementar tras confirmación; mientras, parametrizables.
- **Cero regresiones**: la suite PHPUnit existente debe pasar en cada fase.

## Primero: plan y espera
Antes de escribir código, entrega un **plan detallado** (archivos a crear, migraciones, servicios, rutas nuevas, pruebas) y **espera aprobación**.

## Fases
1. **FM-61 + Equipos y Calibración**: migración de equipos/constantes, carga de `equipos_calibracion.json`, plantilla RT-FM-61 integrada con `RoturasModelo::registrarRotura` (datos_crudos), prensa y factor L/D.
2. Resto de Roturas: 36, 38, 57, 63, 64.
3. Hoja de Campo: 54, 55, 62 (+ enlace a Proctor).
4. Plantillas de suelos/agregados: 27, 25, 42, 35, 46, 58, 59; RT-FM-56 en Equipos.
5. Reportes nuevos y mapeo a RT-FM-22.

## Pruebas
- Por cada formato, un test PHPUnit generado desde `caso_prueba` (entradas → salidas esperadas, tolerancia 0,1 %) usando `EvaluadorFormula` real.
- Tests de regresión que rendericen los 21 formatos legacy sin cambios.
- `tools/verificar_casos.py` es la referencia de resultados.
