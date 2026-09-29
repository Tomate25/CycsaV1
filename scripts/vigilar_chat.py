#!/usr/bin/env python3
"""
vigilar_chat.py
Monitor centinela en segundo plano que vigila el archivo de chat en vivo de Obsidian:
  C:\\Users\\abdia\\OneDrive\\Desktop\\CycsaObsi\\CYCSA\\05_CHAT_EN_VIVO_AGENTES.md

Detecta de forma instantánea cualquier nuevo mensaje publicado por ChatGPT CLI
y notifica inmediatamente al agente Antigravity terminando con éxito y mostrando el mensaje.
"""

import sys
import os
import time
import re

# Forzar codificación UTF-8 en consola de Windows
if hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass

CHAT_PATH = r"C:\Users\abdia\OneDrive\Desktop\CycsaObsi\CYCSA\05_CHAT_EN_VIVO_AGENTES.md"

def obtener_estado_chat():
    if not os.path.exists(CHAT_PATH):
        return None, None, ""

    try:
        with open(CHAT_PATH, "r", encoding="utf-8") as f:
            contenido = f.read()
    except Exception as e:
        return None, None, ""

    remitente_match = re.search(r"ultimo_remitente:\s*(.*)", contenido)
    ultimo_remitente = remitente_match.group(1).strip() if remitente_match else ""

    fecha_match = re.search(r"ultimo_mensaje:\s*(.*)", contenido)
    ultimo_mensaje = fecha_match.group(1).strip() if fecha_match else ""

    return ultimo_remitente, ultimo_mensaje, contenido

def main():
    import argparse
    parser = argparse.ArgumentParser(description="Centinela de chat en vivo inter-agentes")
    parser.add_argument("--esperar-a", "-e", type=str, default="ChatGPT", help="Nombre del agente esperado (ChatGPT o Antigravity)")
    parser.add_argument("--timeout", "-t", type=int, default=600, help="Tiempo máximo de escucha en segundos (default: 600)")
    args = parser.parse_args()

    esperado = args.esperar_a.strip()
    timeout = args.timeout
    intervalo = 2  # Verificación cada 2 segundos

    if not os.path.exists(CHAT_PATH):
        print(f"[ERROR] No se encuentra el archivo de chat en: {CHAT_PATH}")
        sys.exit(1)

    remitente_inicial, fecha_inicial, _ = obtener_estado_chat()
    mtime_inicial = os.path.getmtime(CHAT_PATH)

    print(f"🛡️ [CENTINELA ACTIVO] Vigilando el canal de chat en vivo...")
    print(f"   Archivo: {CHAT_PATH}")
    print(f"   Esperando mensajes de: {esperado}")
    print(f"   Último remitente base: {remitente_inicial} ({fecha_inicial})")
    print(f"   Timeout de sesión: {timeout}s\n")
    sys.stdout.flush()

    inicio = time.time()

    while (time.time() - inicio) < timeout:
        time.sleep(intervalo)

        try:
            mtime_actual = os.path.getmtime(CHAT_PATH)
        except OSError:
            continue

        if mtime_actual != mtime_inicial:
            mtime_inicial = mtime_actual
            remitente_act, fecha_act, contenido = obtener_estado_chat()

            # Verificar si el nuevo mensaje proviene del agente esperado
            if esperado.lower() in remitente_act.lower() and fecha_act != fecha_inicial:
                print("\n" + "="*70)
                print(f"🔔 [ALERTA CENTINELA] NUEVO MENSAJE RECIBIDO DE {remitente_act.upper()}")
                print("="*70)
                print(f"Remitente: {remitente_act}")
                print(f"Fecha/Hora: {fecha_act}")
                print("\n--- CONTENIDO DEL MENSAJE ---")

                # Extraer el último bloque del historial
                partes = contenido.split("## 🗨️ Historial de Conversación en Vivo")
                if len(partes) >= 2:
                    bloques = re.findall(
                        r"(> \[!(?:NOTE|TIP|WARNING)\].*?)(?=(?:\n> \[!(?:NOTE|TIP|WARNING)\])|\Z)",
                        partes[1],
                        re.DOTALL
                    )
                    if bloques:
                        print(bloques[-1].strip())
                    else:
                        print(contenido[-500:])
                else:
                    print(contenido[-500:])

                print("="*70 + "\n")
                sys.stdout.flush()
                # Termina con éxito para activar inmediatamente el turno de Antigravity
                sys.exit(0)
            else:
                # El archivo cambió pero fue por otro usuario o por nosotros mismos
                fecha_inicial = fecha_act
                remitente_inicial = remitente_act

    print(f"[CENTINELA] Ciclo de espera completado ({timeout}s) sin nuevos mensajes de ChatGPT CLI.")
    sys.exit(0)

if __name__ == "__main__":
    main()
