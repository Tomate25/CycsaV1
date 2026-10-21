# Especificaciones de hojas de cálculo CYCSA (RT-FM-xx) para el ERP/LIMS

## Propósito
Este paquete describe en formato legible por IA y por humanos las **17 hojas de cálculo de laboratorio** (Excel RT-FM-25…64) para que se implementen en el sistema PHP CYCSA (`plantillas`, `variables`, `EvaluadorFormula`, módulo de Roturas y Hoja de Campo) **sin romper nada existente**.

## Reglas obligatorias
1. **Aditivo únicamente.** No modificar ni reemplazar los 21 formatos de `database/ensayos/formatos_schema.json` (RT-FM-22 A–H), ni tablas, columnas o rutas existentes.
2. Las hojas de cálculo **alimentan** los reportes existentes: cada formato declara `alimenta_rt_fm_22` con el formato legacy y el mapeo campo→campo.
3. Tablas nuevas solo vía **migraciones nuevas** (007+), nunca alterando 001–006.
4. Los 21 formatos existentes deben seguir funcionando (pruebas de regresión).
5. Las fórmulas usan solo la sintaxis de `EvaluadorFormula.php` (ver Glosario). Lo que no cabe (promedios, interpolación, SUCS) va en `agregaciones`, implementadas por un servicio PHP.
6. Las correcciones con `"estado":"dudosa"` requieren confirmación del laboratorio antes de pasar plantilla a `aprobada`.

## Cómo leerlo
- `_esquema.json` – JSON Schema (draft 2020-12) que cumplen todos los `formatos/*.json`.
- `formatos/RT-FM-xx.json` – un archivo por formato: `encabezado`, `secciones` (→ `plantilla_secciones`/`plantilla_campos`), `constantes`, `formulas` (→ `plantilla_campos.formula`), `agregaciones`, `variantes`, `correcciones`, `alimenta_rt_fm_22`, `caso_prueba` (entradas y salidas esperadas del Excel real).
- `catalogos/variables_comunes.json` – variables compartidas (insertar en `variables` solo si no existen).
- `catalogos/equipos_calibracion.json` – prensa, factor L/D, esclerómetro, moldes, picnómetros, balanzas.
- `catalogos/listas.json` – tamices, Proctor, SUCS, posiciones, especímenes, fallas C39, etc. (sin nombres de analistas).
- `MAPA_MODULOS.md` – qué módulo consume cada formato y qué no tocar.
- `PROMPT_ANTIGRAVITY.md` – instrucciones para el agente implementador.
- `tools/` – `verificar_casos.py` (reproduce `caso_prueba` con un port de EvaluadorFormula), `validar_esquema.py`, generadores.

```
cycsa_hojas_calculo/
├── README.md  _esquema.json  MAPA_MODULOS.md  PROMPT_ANTIGRAVITY.md
├── formatos/RT-FM-{25,27,35,36,38,42,46,54,55,56,57,58,59,61,62,63,64}.json
├── catalogos/{variables_comunes,equipos_calibracion,listas}.json
└── tools/{verificar_casos,validar_esquema,generar_formatos,generar_catalogos}.py
```

## Glosario
- **{codigo}**: referencia a variable en una fórmula. `_1/_2/_3`: lecturas replicadas.
- **por_especimen**: la sección se repite por espécimen/fila (`tabla_matriz`).
- **Funciones soportadas**: ROUND, SQRT, POW, ABS, MIN, MAX (≤10 args), CEIL, FLOOR, PI(), LOG, EXP; operadores `+ - * / % ^`. **No** hay IF/SUM/AVERAGE; **no** notación científica; negativos siempre entre paréntesis `(0-x)`.
- **ref_equipo**: la constante se lee del catálogo de equipos vigente (con `valor_referencia` para pruebas).
- **agregación**: cálculo entre especímenes (promedio, suma, max_curva, interpolar, clasificar_sucs…).
- **dudosa / confirmada**: estado de una corrección respecto al Excel original.
- **RT-FM-22**: familia de formatos de informe legacy A–H ya existentes.

## Validación
`python tools/validar_esquema.py` y `python tools/verificar_casos.py` → los 17 formatos pasan (tolerancia 0,1 %).
