#!/usr/bin/env python3
"""
chat_agentes.py
Canal CLI de mensajería y sincronización en vivo entre ChatGPT CLI, Antigravity CLI y Abdia.
Interactúa directamente con el archivo Markdown de Obsidian:
  C:\\Users\\abdia\\OneDrive\\Desktop\\CycsaObsi\\CYCSA\\05_CHAT_EN_VIVO_AGENTES.md
"""

import sys
import os
import argparse
from datetime import datetime
import re

# Forzar codificación UTF-8 en consola de Windows
if hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass

CHAT_PATH = r"C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\05_CHAT_EN_VIVO_AGENTES.md"

def leer_chat(ultimos_n=5):
    if not os.path.exists(CHAT_PATH):
        print(f"[ERROR] No se encuentra el archivo de chat en: {CHAT_PATH}")
        sys.exit(1)

    with open(CHAT_PATH, "r", encoding="utf-8") as f:
        contenido = f.read()

    print("\n" + "="*70)
    print(" 💬 CANAL DE CHAT EN VIVO AGENTES CYCSA (Obsidian)")
    print("="*70)

    # Extraer únicamente los mensajes dentro del Historial
    partes = contenido.split("## 🗨️ Historial de Conversación en Vivo")
    if len(partes) < 2:
        print("No se encontró la sección '## 🗨️ Historial de Conversación en Vivo'.")
        return

    historial = partes[1]
    # Extraer mensajes en bloques de callout
    bloques = re.findall(r"(> \[!(?:NOTE|TIP|WARNING)\].*?)(?=(?:\n> \[!(?:NOTE|TIP|WARNING)\])|\Z)", historial, re.DOTALL)

    if not bloques:
        print("No se encontraron mensajes formateados en el historial.")
        return

    mostrar = bloques[-ultimos_n:]
    print(f"Mostrando los últimos {len(mostrar)} de {len(bloques)} mensajes:\n")
    for b in mostrar:
        print(b.strip())
        print("\n" + "-"*70 + "\n")

def enviar_mensaje(remitente, destinatario, mensaje, asunto=None):
    if not os.path.exists(CHAT_PATH):
        print(f"[ERROR] No se encuentra el archivo de chat en: {CHAT_PATH}")
        sys.exit(1)

    ahora = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # Identificación y callouts según remitente
    remitente_lower = remitente.lower()
    if "chatgpt" in remitente_lower or "gpt" in remitente_lower:
        callout = "NOTE"
        nombre_display = "🤖 ChatGPT CLI"
        turno_sig = "Antigravity"
    elif "antigravity" in remitente_lower or "anti" in remitente_lower:
        callout = "TIP"
        nombre_display = "🛡️ Antigravity CLI"
        turno_sig = "ChatGPT-CLI"
    else:
        callout = "WARNING"
        nombre_display = f"👤 {remitente} (Usuario)"
        turno_sig = destinatario if destinatario else "Todos"

    if destinatario:
        turno_sig = destinatario

    # Bloque de mensaje a insertar
    lineas_msg = mensaje.strip().split("\n")
    cuerpo_identado = "\n".join(f"> {linea}" for linea in lineas_msg)

    asunto_txt = f"> **Asunto: {asunto}**\n>\n" if asunto else ""
    nuevo_bloque = (
        f"\n> [!{callout}] {nombre_display} — {ahora}\n"
        f"{asunto_txt}"
        f"{cuerpo_identado}\n"
    )

    with open(CHAT_PATH, "r", encoding="utf-8") as f:
        contenido = f.read()

    # Actualizar Frontmatter YAML
    contenido = re.sub(
        r"ultimo_mensaje:.*",
        f"ultimo_mensaje: {ahora}",
        contenido
    )
    contenido = re.sub(
        r"ultimo_remitente:.*",
        f"ultimo_remitente: {nombre_display}",
        contenido
    )
    contenido = re.sub(
        r"turno_activo:.*",
        f"turno_activo: {turno_sig}",
        contenido
    )

    # Añadir al final del archivo
    contenido = contenido.rstrip() + "\n" + nuevo_bloque

    with open(CHAT_PATH, "w", encoding="utf-8") as f:
        f.write(contenido)

    print(f"\n[OK] Mensaje registrado en Obsidian ({ahora})")
    print(f"     Remitente: {nombre_display}")
    print(f"     Destinatario / Turno: {turno_sig}")
    print("\n" + nuevo_bloque)

def main():
    parser = argparse.ArgumentParser(
        description="Canal de chat en vivo inter-agentes (ChatGPT CLI ⇄ Antigravity CLI) vía Obsidian"
    )
    parser.add_argument("--leer", "-l", action="store_true", help="Leer los últimos mensajes del chat")
    parser.add_argument("--n", type=int, default=5, help="Número de mensajes a leer (default: 5)")
    parser.add_argument("--de", "-d", type=str, help="Nombre del remitente (ej. 'ChatGPT', 'Antigravity', 'Abdia')")
    parser.add_argument("--para", "-p", type=str, default=None, help="Destinatario o próximo turno (ej. 'Antigravity', 'ChatGPT')")
    parser.add_argument("--asunto", "-a", type=str, default=None, help="Asunto breve del mensaje")
    parser.add_argument("--msg", "-m", type=str, help="Contenido del mensaje a enviar")

    args = parser.parse_args()

    if args.leer:
        leer_chat(args.n)
    elif args.msg and args.de:
        enviar_mensaje(args.de, args.para, args.msg, args.asunto)
    else:
        # Si no se pasan suficientes argumentos, mostrar últimos mensajes y ayuda
        leer_chat(3)
        print("\nPara enviar un mensaje usa:")
        print("  python scripts/chat_agentes.py --de ChatGPT --msg \"Mensaje aquí\"")
        print("  python scripts/chat_agentes.py --leer\n")

if __name__ == "__main__":
    main()
