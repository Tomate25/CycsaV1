#!/usr/bin/env python3
"""
Script utilitario para fusionar PDFs y garantizar la paginación unificada ("Página X de Y")
en todas las hojas del documento resultante.
"""

import sys
import os
import io

def fusionar_y_enumerar(ruta_principal: str, ruta_adjunto: str, ruta_salida: str, enumerar: bool = True) -> bool:
    try:
        from pypdf import PdfReader, PdfWriter
    except ImportError:
        return False

    if not os.path.isfile(ruta_principal) or not os.path.isfile(ruta_adjunto):
        return False

    try:
        reader_principal = PdfReader(ruta_principal)
        reader_adjunto = PdfReader(ruta_adjunto)

        writer_combinado = PdfWriter()
        for p in reader_principal.pages:
            writer_combinado.add_page(p)
        for p in reader_adjunto.pages:
            writer_combinado.add_page(p)

        total_paginas = len(writer_combinado.pages)
        if total_paginas == 0:
            return False

        if not enumerar:
            with open(ruta_salida, 'wb') as f_out:
                writer_combinado.write(f_out)
            return True

        # Paginación unificada con reportlab y pypdf
        try:
            from reportlab.pdfgen import canvas
            from reportlab.lib.colors import Color

            writer_final = PdfWriter()
            for idx, page in enumerate(writer_combinado.pages):
                # Si la página tiene rotación, normalizar para que las coordenadas coincidan
                if getattr(page, 'rotation', 0) != 0:
                    try:
                        page.transfer_rotation_to_content()
                    except Exception:
                        pass

                pw = float(page.mediabox.width)
                ph = float(page.mediabox.height)

                packet = io.BytesIO()
                can = canvas.Canvas(packet, pagesize=(pw, ph))
                can.setFont('Helvetica-Bold', 8.5)
                
                texto = f"Página {idx + 1} de {total_paginas}"
                tw = can.stringWidth(texto, 'Helvetica-Bold', 8.5)
                
                x = pw - tw - 36
                y = 20
                pad_x = 8
                pad_y = 4
                
                # Pastilla protectora en fondo blanco con borde sutil
                can.setFillColor(Color(1, 1, 1, alpha=0.95))
                can.setStrokeColor(Color(0.75, 0.80, 0.88))
                can.setLineWidth(0.75)
                can.roundRect(x - pad_x, y - pad_y, tw + (pad_x * 2), 8.5 + (pad_y * 2), 3, fill=1, stroke=1)
                
                # Texto en azul corporativo CYCSA
                can.setFillColor(Color(0.06, 0.20, 0.53))
                can.drawString(x, y, texto)
                can.save()

                packet.seek(0)
                overlay = PdfReader(packet).pages[0]
                page.merge_page(overlay)
                writer_final.add_page(page)

            with open(ruta_salida, 'wb') as f_out:
                writer_final.write(f_out)
            return True

        except Exception as e:
            # En caso de que reportlab falle, guardar el combinado sin overlay
            with open(ruta_salida, 'wb') as f_out:
                writer_combinado.write(f_out)
            return True

    except Exception as ex:
        sys.stderr.write(f"Error procesando PDF: {ex}\n")
        return False

def enumerar_pdf_existente(ruta_entrada: str, ruta_salida: str) -> bool:
    try:
        from pypdf import PdfReader, PdfWriter
        from reportlab.pdfgen import canvas
        from reportlab.lib.colors import Color
    except ImportError:
        return False

    if not os.path.isfile(ruta_entrada):
        return False

    try:
        reader = PdfReader(ruta_entrada)
        total_paginas = len(reader.pages)
        if total_paginas == 0:
            return False

        writer = PdfWriter()
        for idx, page in enumerate(reader.pages):
            if getattr(page, 'rotation', 0) != 0:
                try:
                    page.transfer_rotation_to_content()
                except Exception:
                    pass

            pw = float(page.mediabox.width)
            ph = float(page.mediabox.height)

            packet = io.BytesIO()
            can = canvas.Canvas(packet, pagesize=(pw, ph))
            can.setFont('Helvetica-Bold', 8.5)
            
            texto = f"Página {idx + 1} de {total_paginas}"
            tw = can.stringWidth(texto, 'Helvetica-Bold', 8.5)
            
            x = pw - tw - 36
            y = 20
            pad_x = 8
            pad_y = 4
            
            can.setFillColor(Color(1, 1, 1, alpha=0.95))
            can.setStrokeColor(Color(0.75, 0.80, 0.88))
            can.setLineWidth(0.75)
            can.roundRect(x - pad_x, y - pad_y, tw + (pad_x * 2), 8.5 + (pad_y * 2), 3, fill=1, stroke=1)
            
            can.setFillColor(Color(0.06, 0.20, 0.53))
            can.drawString(x, y, texto)
            can.save()

            packet.seek(0)
            overlay = PdfReader(packet).pages[0]
            page.merge_page(overlay)
            writer.add_page(page)

        with open(ruta_salida, 'wb') as f_out:
            writer.write(f_out)
        return True
    except Exception as ex:
        sys.stderr.write(f"Error enumerando PDF: {ex}\n")
        return False

if __name__ == '__main__':
    if len(sys.argv) < 3:
        sys.exit(1)

    accion = sys.argv[1]
    if accion == 'merge' and len(sys.argv) >= 5:
        principal = sys.argv[2]
        adjunto = sys.argv[3]
        salida = sys.argv[4]
        ok = fusionar_y_enumerar(principal, adjunto, salida, enumerar=True)
        sys.exit(0 if ok else 1)
    elif accion == 'number' and len(sys.argv) >= 4:
        entrada = sys.argv[2]
        salida = sys.argv[3]
        ok = enumerar_pdf_existente(entrada, salida)
        sys.exit(0 if ok else 1)
    elif len(sys.argv) == 4: # Compatibilidad directa: argv[1]=principal, argv[2]=adjunto, argv[3]=salida
        principal = sys.argv[1]
        adjunto = sys.argv[2]
        salida = sys.argv[3]
        ok = fusionar_y_enumerar(principal, adjunto, salida, enumerar=True)
        sys.exit(0 if ok else 1)
    else:
        sys.exit(1)
