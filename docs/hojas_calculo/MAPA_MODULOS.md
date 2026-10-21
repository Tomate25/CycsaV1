# Mapa de módulos

| Módulo | Formatos | Qué hacen | Integración | NO cambiar |
|---|---|---|---|---|
| **Equipos y Calibración** (nuevo) | RT-FM-56 + catálogo `equipos_calibracion.json` | Picnómetros, prensa (polinomio), factor L/D, esclerómetro, balanzas, moldes | Migración nueva `equipos_calibracion(+_constantes)`, FK opcional a `activos_fijos`; las fórmulas consumen `ref_equipo` | `activos_fijos`, `mantenimiento_equipos` |
| **Laboratorio Roturas** | RT-FM-61 (cilindros C39), 36 (morteros), 38 (flexión vigas), 57 (adoquines), 63 (bloques), 64 (acero) | Carga corregida, área, esfuerzo, factor L/D, promedios | `RoturasModelo::registrarRotura` (carga, area, tipo_falla, `datos_crudos` JSON con lecturas e intermedios); columnas `esfuerzo_psi`, `esfuerzo_kgcm2`, `porcentaje_fc`; `informes_rotura` | Firma de `registrarRotura`, tablas de migración 006, área por defecto 28.2743 |
| **Hoja de Campo** | RT-FM-54 (reemplazo agua), 55 (cono de arena), 62 (densímetro nuclear) | Densidad en sitio, % compactación vs Proctor | Alimentan RT-FM-22 B; leen γd máx del RT-FM-27 de la misma solicitud | Formato B legacy y su vista |
| **Plantillas (LIMS)** | RT-FM-25 (granulometría), 27 (Proctor), 35 (GE/absorción), 42 (límites), 46 (CBR), 58 (sanidad), 59 (esclerómetro) | Captura y cálculo con `EvaluadorFormula` | `plantillas`→`plantilla_secciones`→`plantilla_campos.formula`; versión en `plantilla_versiones.esquema_json` + `formato_legacy_json`; captura en vista `captura_matriz` | `EvaluadorFormula` (solo ampliar con tests), `formatos_ensayos`, `formatos_schema.json` |

## Formato → reporte RT-FM-22
25→F · 27→C · 35→G · 36→A · 38→E · 42→F · 46→nuevo · 54→B · 55→B · 56→nuevo · 57→A · 58→nuevo · 59→D · 61→A · 62→nuevo (B opcional) · 63→A · 64→nuevo.
"nuevo" = reporte adicional; no sustituye ninguno existente. El formato H legacy no se identificó; no tocarlo.
