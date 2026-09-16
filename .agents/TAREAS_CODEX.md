# 📋 ASIGNACIÓN DE TAREAS PARA CODEX (FASE 13 - PROYECTO CYCSA ERP & LIMS)

Hola Codex, te habla **Antigravity (Gemini)**. Hemos completado con éxito las fases de búsqueda inteligente en cotizaciones e inventario, flujo de muestreo unificado (CYCSA-RT-FM-40 B), y la resolución de precarga integral y detección dinámica de parámetros para la Hoja de Solicitud de Laboratorio (CYCSA-RT-FM-13).

La suite de PHPUnit cuenta actualmente con **33 pruebas pasando al 100%**.

Tu misión asignada para este ciclo consiste en fortalecer la calidad, integridad de datos y cobertura de pruebas automatizadas en los siguientes frentes críticos:

---

## 🎯 TAREA 1: Suite de Pruebas Unitarias para Fórmulas y Cálculos de Laboratorio
**Archivo objetivo:** Crear `tests/Unit/CalculosLaboratorioTest.php`  
**Referencia:** `app/Modulos/Operaciones/Vistas/captura_matriz.php` y `app/Helpers/funciones.php`.  
**Objetivos:**
1. Validar las fórmulas de conversión y cálculo de **Resistencia a la Compresión de Concreto** (`ASTM C39`):
   - Conversión de `kg/cm²` a `psi` (factor `14.2233433`).
   - Cálculo de esfuerzo: `esfuerzo = carga / area`.
   - Protección contra división por cero cuando el área o diámetro sea `0` o nulo.
2. Validar fórmulas de **Contenido de Humedad en Suelos** (`ASTM D2216`):
   - `w (%) = ((peso_humedo - peso_seco) / (peso_seco - peso_tara)) * 100`.
   - Verificación de redondeo a 1 o 2 decimales.
3. Validar cálculo de **Porcentaje de Compactación** respecto al Proctor (`ASTM D1556 / D6938`):
   - `% Compactación = (densidad_seca_campo / densidad_maxima_seca_proctor) * 100`.
4. Validar cálculo de **Edad de Rotura** entre fecha de moldeo y fecha de ensayo (días transcurridos, considerando saltos de mes y años bisiestos).

---

## 🎯 TAREA 2: Suite de Pruebas Unitarias para Integridad Contable (Partida Doble)
**Archivo objetivo:** Crear `tests/Unit/ContabilidadTest.php`  
**Referencia:** `app/Modulos/Contabilidad/Modelos/ContabilidadModelo.php`.  
**Objetivos:**
1. Validar que la regla de partida doble rechace cualquier asiento donde `abs(total_debe - total_haber) > 0.01`.
2. Validar que un asiento con menos de 2 líneas de detalle sea rechazado.
3. Validar que los montos negativos en Debe o Haber sean rechazados.
4. Validar que un asiento cuadrado y con cuentas válidas se estructure y procese correctamente.

---

## 🎯 TAREA 3: Verificación de Integridad de Formatos y Plantillas Técnicas Markdown
**Ubicación:** `database/ensayos/` y `database/ensayos/formatos_schema.json`.  
**Objetivos:**
1. Inspeccionar que los 21 archivos de ensayo (`.md`) existan y que sus formatos tengan sintaxis Markdown válida.
2. Verificar que no existan enlaces rotos o referencias a imágenes faltantes en las plantillas técnicas.
3. Crear una prueba unitaria en `tests/Unit/FormatosEnsayosTest.php` que verifique que el catálogo de plantillas en `database/ensayos/` carga sin errores de sintaxis y que el schema JSON es válido.

---

## 🎯 TAREA 4: Registro de Resultados en COORDINATION.md
**Archivo objetivo:** `COORDINATION.md`  
**Objetivo:**
- Agregar la sección **Fase 13: Cobertura y Blindaje de Cálculos de Laboratorio, Integridad Contable y Validación de Formatos ASTM**.
- Documentar las pruebas creadas, número total de aserciones y certificar que la suite completa de PHPUnit permanezca 100% en verde.

---
*Codex, puedes comenzar inmediatamente y reportar tus avances paso a paso.*
