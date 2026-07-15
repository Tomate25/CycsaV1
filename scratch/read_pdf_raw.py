import re
import sys

# Set standard output encoding to utf-8 for Windows terminal
sys.stdout.reconfigure(encoding='utf-8')

def extract_strings(pdf_path):
    with open(pdf_path, 'rb') as f:
        content = f.read()
    
    strings = re.findall(rb'\((.*?)\)', content)
    decoded = []
    for s in strings:
        try:
            # Decode using cp1252 or utf-8
            dec = s.decode('latin1', errors='ignore')
            dec_clean = "".join(c for c in dec if ord(c) < 128 and c.isprintable()).strip()
            if len(dec_clean) > 3:
                decoded.append(dec_clean)
        except:
            pass
    return decoded

print("=== SUELO PDF ===")
suelo = extract_strings("C:/Users/abdia/Downloads/Formato de Granulometria de Suelo.pdf")
for s in suelo[:80]:
    print(s)

print("\n=== AGREGADOS PDF ===")
agregados = extract_strings("C:/Users/abdia/Downloads/Granulomnetria de Agregados.pdf")
for s in agregados[:80]:
    print(s)
