import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

def scan_pdf_for_text(pdf_path):
    print(f"\n--- Scanning {pdf_path} ---")
    with open(pdf_path, 'rb') as f:
        content = f.read()
    
    # PDF text is usually inside stream blocks between BT (Begin Text) and ET (End Text)
    # or inside Tj / TJ operators
    bt_et_blocks = re.findall(rb'BT(.*?)ET', content, re.DOTALL)
    print(f"Found {len(bt_et_blocks)} text blocks.")
    
    lines = []
    for block in bt_et_blocks:
        # Find Tj or TJ strings
        # e.g., (text) Tj or [(text) 120 (more text)] TJ
        tj_strings = re.findall(rb'\((.*?)\)', block)
        for s in tj_strings:
            try:
                dec = s.decode('latin1', errors='ignore')
                clean = "".join(c for c in dec if ord(c) < 128 and c.isprintable()).strip()
                if len(clean) > 2:
                    lines.append(clean)
            except:
                pass
    
    for l in lines[:100]:
        print(l)

scan_pdf_for_text("C:/Users/abdia/Downloads/Formato de Granulometria de Suelo.pdf")
scan_pdf_for_text("C:/Users/abdia/Downloads/Granulomnetria de Agregados.pdf")
