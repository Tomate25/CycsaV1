# Mapa de módulos — Hojas de Cálculo y Formatos CYCSA

Actualizado: 2026-11-22 (Plan de trabajo CYCSA — Fases 1–3, Catálogo `RG-LI-05 v4.2`).

| Módulo | Formatos HC | Qué hacen | Integración | Restricciones / Notas |
|---|---|---|---|---|
| **Control de Calidad · Equipos y Calibración** | `RT-FM-56` + catálogo `equipos_calibracion.json` | Picnómetros (`equipos_calibracion_picnometros`), prensa (`PRENSA-01`), factor L/D, esclerómetro (`ESCL-01` posiciones A/B/C), balanzas, moldes y parámetros de `config_calculo_lab` | Migración `008` (`equipos_calibracion`, `equipos_calibracion_constantes`, `equipos_calibracion_picnometros`, `configuracion_correcciones_hc`, `hojas_campo_registros`) + Migración `026` (bodegas/traslados con FK opcional `equipos_ubicacion.activo_fijo_id`) + Migración `039` (`config_calculo_lab`) | No alterar `activos_fijos` ni `mantenimiento_equipos` |
| **Laboratorio Roturas** | `RT-FM-61` (cilindros C39), `RT-FM-36` (bloques C140/NTON), `RT-FM-38` (flexión vigas C78), `RT-FM-57` (cubos de mortero C109 / `PE-57`), `RT-FM-63` (adoquines), `RT-FM-64` (tracción de varillas de acero `PE-61` #3 a #11) | Área calculada desde `D1`/`D2` (`π·(D̄/2)²/6.4516`), peso volumétrico redondeado a 10, resistencia `psi` redondeada a 10 y `kgf/cm²` con divisor configurable (`14.2029`), promedios y tolerancias ASTM C39 | `RoturasModelo::registrarRotura(array $datos, int $tecnicoId): array` y `registrarRoturaHc()`; tabla `roturas` (`carga`, `area`, `peso_volumetrico`, `edad_calculada`, `esfuerzo_psi`, `esfuerzo_kgcm2`, `porcentaje_fc`, `datos_crudos`); tabla `informes_rotura` (con `revisado_por`, `aprobado_por`, versiones `-1`/`-2`) | **Sin área por defecto `28.2743`**: el área siempre se calcula desde `D1`/`D2` o medición explícita; preservar firma `registrarRotura(array $datos, int $tecnicoId)` |
| **Hoja de Campo** | `RT-FM-54` (reemplazo de agua), `RT-FM-55` (cono de arena), `RT-FM-62` (infiltración / percolación de suelo — **NO es densímetro nuclear**) | `RT-FM-54` y `RT-FM-55` calculan densidad in situ y `% compactación` vs `RT-FM-27` (Proctor). `RT-FM-62` calcula tasa de infiltración/percolación (`PE-24`). *El densímetro nuclear (`PE-25`, `ASTM D6938`) se captura directamente en Recepción/Matriz `RT-FM-22 B` (sin HC Excel).* | `RT-FM-54` y `RT-FM-55` alimentan `RT-FM-22 B` leyendo `γd máx` del `RT-FM-27` (sin defaults peligrosos de `Capa`/`Espesor`/`Profundidad`). `RT-FM-62` emite reporte `H-13` / nuevo de infiltración. | Preservar compatibilidad de `RT-FM-22 B` y quitar defaults silenciosos de equipo Troxler |
| **Plantillas (LIMS)** | `RT-FM-25` (granulometría con tamiz `3"` + agregado fino/grueso), `RT-FM-27` (Proctor — máximo medido por defecto), `RT-FM-35` (GE/absorción agregados `PE-02`), `RT-FM-42` (límites Atterberg), `RT-FM-46` (CBR `PE-11`), `RT-FM-58` (sanidad/intemperismo `PE-52`), `RT-FM-59` (esclerómetro / martillo suizo) | Captura y cálculo con `EvaluadorFormula`, `MotorHojasCalculoServicio`, `AgregadorEnsayosServicio` y `MapeadorRtFm22Servicio` | `plantillas` → `plantilla_secciones` → `plantilla_campos.formula`; `formatos_ensayos` y `database/ensayos/formatos_schema.json` (incluyendo serie `RT-FM-22 H` y corrección de unidades SI) | Mantener `EvaluadorFormula` seguro (sin `eval()`) |

## Mapeo Hoja de Cálculo → Informe `RT-FM-22` (y Serie `H` de `RG-LI-05 v4.2`)

- **Acreditados / Serie A–G:**
  - `RT-FM-61` → **A** (`resistencia_de_concreto.md` / `PE-07`)
  - `RT-FM-36` → **A** (`formato_de_resistencia_de_bloques.md` / `PE-32`, resistencia sobre **área neta**)
  - `RT-FM-57` → **A** (`resistencia_de_mortero.md` / `PE-57`)
  - `RT-FM-63` → **A** (`resistencia_de_adoquines.md` / `PE-32`)
  - `RT-FM-54` → **B** (`formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md` / `PE-46`)
  - `RT-FM-55` → **B** (`formato_de_compactacion_por_cono_de_arena.md` / `PE-20`)
  - `RT-FM-27` → **C** (`proctor_estandar.md` / `PE-18`)
  - `RT-FM-59` → **D** (`resistencia_de_martillo_suizo.md` / `PE-12`)
  - `RT-FM-38` → **E** (`formato_de_resistencia_de_a_la_flexion.md` / `PE-30`)
  - `RT-FM-25` + `RT-FM-42` → **F** (`formato_de_granulometria_de_suelo.md` / `PE-16`, `PE-17` y `granulomnetria_de_agregados.md` / `PE-06`)
  - `RT-FM-58` → **F** (`formato_sanidad_intemperismo_f.md` / `PE-52`, con compatibilidad de reporte propio `CYCSA-RT-FM-22-SAN`)
  - `RT-FM-35` → **G** (`ensayos_varios.md` / `PE-02` agregado fino y grueso)
- **Serie `CYCSA-RT-FM-22 H` (identificada en `RG-LI-05 v4.2`, `H-1` a `H-45`) y certificados internos:**
  - `RT-FM-46` (CBR, `PE-11`) → **H-7** (`formato_cbr_h07.md`, con compatibilidad de reporte propio `CYCSA-RT-FM-22-CBR`)
  - `PE-14` (GE suelos picnómetro, ASTM D854) → **H-8** (`formato_ge_suelos_h08.md`, separado de `PE-02` agregados)
  - `RT-FM-62` (Infiltración/percolación, `PE-24`) → **H-13** / reporte propio `CYCSA-RT-FM-22-INF`
  - `PE-38` (Doblado de barras de acero, ASTM A615/A706) → **H-20** (`formato_doblado_acero_h20.md`, separado de tensión `PE-61`)
  - `PE-40` (Compresión uniaxial en roca, ASTM D7012-23) → **H-22** (`formato_compresion_roca_h22.md`)
  - `PE-58` (Suelo-cemento / lodo cemento, ASTM D1633-17) → **H-35** (`formato_de_lodo_concreto.md`, separado de colorimetría `PE-05`)
  - `RT-FM-64` (Tracción varillas de acero #3–#11, `PE-61`) → **H-37..H-45** (`formato_tension_acero_h37.md`, con compatibilidad de reporte propio `CYCSA-RT-FM-22-ACE`)
  - `RT-FM-56` (Calibración de picnómetros) → Certificado interno `CYCSA-RT-FM-56-CERT`
