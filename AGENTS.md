# 🤖 Guía de Instrucciones y Protocolo de Agentes (Codex / ChatGPT CLI / Antigravity)

## 📌 Contexto del Proyecto
Eres el agente de desarrollo Full-Stack para el sistema **CYCSA ERP / LIMS (Laboratorio de Control de Calidad y Suelos)**.

- **Código fuente del sistema:** `C:\xampp\htdocs\Cycsa`
- **Centro de comando y tareas (Obsidian Vault):** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA`
- **URL local:** `http://localhost/Cycsa/publico`
- **Stack:** PHP 8.2 MVC nativo, MySQL / MariaDB, Bootstrap 5, FontAwesome, PHPUnit.

---

## 🧭 Centro de Operaciones en Obsidian
La gestión de tareas, requerimientos y documentación se coordina directamente a través de los archivos Markdown de la bóveda de Obsidian:

1. **Dashboard maestro:** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\00_TORRE_DE_CONTROL.md`
2. **Bandeja de entrada (Inbox):** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\01_BANDEJA_DE_TAREAS.md`
3. **Tareas en desarrollo:** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\02_TAREAS_EN_PROGRESO.md`
4. **Historial de tareas completadas:** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\03_HISTORIAL_TAREAS_COMPLETADAS.md`
5. **Documentación del sistema:** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\04_DOCUMENTACION_CYCSA\`
6. **Protocolo detallado:** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\Instrucciones_Para_Agentes_CLI.md`
7. **Chat en vivo inter-agentes:** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\05_CHAT_EN_VIVO_AGENTES.md`

---

## 💬 Comunicación en Vivo entre Agentes (ChatGPT CLI ⇄ Antigravity)

Los agentes deben coordinar tareas cruzadas, revisiones de código y traspaso de información a través del chat en vivo:
- **Archivo:** `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\05_CHAT_EN_VIVO_AGENTES.md`
- **Lectura por comando:**
  ```powershell
  python scripts/chat_agentes.py --leer
  ```
- **Envío de mensaje por comando:**
  ```powershell
  python scripts/chat_agentes.py --de ChatGPT --para Antigravity --msg "Tu mensaje aquí..."
  ```
- **Edición directa en Markdown:** También puedes agregar un bloque de callout al final del archivo (`> [!NOTE] 🤖 ChatGPT CLI` o `> [!TIP] 🛡️ Antigravity CLI`) y actualizar el frontmatter YAML.

---

## 🔄 Protocolo de Ejecución de Tareas

Cuando el usuario te pida revisar tareas, atender un requerimiento o trabajar en el sistema:

1. **Lectura de Bandeja:** Revisa `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\01_BANDEJA_DE_TAREAS.md`.
2. **Mover a En Progreso:**
   - Toma la tarea asignada.
   - Pásala a `C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\02_TAREAS_EN_PROGRESO.md`.
   - Márcala como iniciada con la fecha y hora.
3. **Desarrollo en el Código:**
   - Realiza los cambios necesarios dentro de `C:\xampp\htdocs\Cycsa`.
   - Respeta la arquitectura MVC existente y las convenciones de código.
4. **Verificación y Pruebas Unitarias:**
   - Ejecuta las pruebas del sistema:
     ```powershell
     cd C:\xampp\htdocs\Cycsa
     vendor\bin\phpunit
     ```
   - Asegúrate de que el 100% de las aserciones pasen sin errores.
5. **Cierre de Tarea en Obsidian:**
   - Mueve la tarea de `02_TAREAS_EN_PROGRESO.md` a `03_HISTORIAL_TAREAS_COMPLETADAS.md`.
   - Documenta un resumen conciso de los archivos modificados, funcionalidad implementada y resultado de las pruebas.
   - Si hubo cambios arquitectónicos o de BD, actualiza el documento correspondiente en `04_DOCUMENTACION_CYCSA\`.
