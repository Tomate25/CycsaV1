import openpyxl
import json
import re

wb = openpyxl.load_workbook(r"C:\Users\abdia\Downloads\Exportar_data_clientes_29-06-2026 (1).xlsx", data_only=True)
sheet = wb.active

clients = []
header_found = False
start_row = 11  # Based on index 10 being the header row

def clean_str(val):
    if val is None:
        return ""
    return str(val).strip()

def clean_int(val):
    if val is None:
        return 0
    try:
        return int(float(str(val).replace(",", "").strip()))
    except:
        return 0

def clean_float(val):
    if val is None:
        return 0.0
    try:
        return float(str(val).replace(",", "").strip())
    except:
        return 0.0

for r_idx, row in enumerate(sheet.iter_rows(values_only=True)):
    if r_idx < start_row:
        continue
    
    # Check if this row is empty (if id is empty or tipo_cliente is empty, skip)
    client_id = row[0]
    tipo_cliente = row[1]
    if client_id is None and tipo_cliente is None:
        continue
        
    client = {
        "id": clean_int(row[0]),
        "tipo_cliente": clean_str(row[1]),
        "codigo_cliente": clean_str(row[2]),
        "activo": clean_int(row[3]) if row[3] is not None else 1,
        "nombre_cliente": clean_str(row[4]),
        "primer_apellido": clean_str(row[5]),
        "segundo_apellido": clean_str(row[6]),
        "sucursal_sede": clean_str(row[7]),
        "clasificacion": clean_str(row[8]),
        "sub_clasificacion": clean_str(row[9]),
        "vendedor": clean_str(row[10]),
        "numero_cedula": clean_str(row[11]),
        "numero_ruc": clean_str(row[12]),
        "contacto": clean_str(row[13]),
        "direccion": clean_str(row[14]),
        "notas": clean_str(row[15]),
        "telefono": clean_str(row[16]),
        "fax": clean_str(row[17]),
        "email": clean_str(row[18]),
        "cuenta_cxc": clean_str(row[19]),
        "cuenta_cxp": clean_str(row[20]),
        "exonerado_impuestos": clean_int(row[21]),
        "cuenta_ingresos_exonerados": clean_str(row[22]),
        "exportacion": clean_int(row[23]),
        "tipo_moneda": clean_int(row[24]) if row[24] is not None else 1,
        "activar_prorroga_credito": clean_int(row[25]),
        "limite_credito": clean_float(row[26]),
        "dias_credito": clean_int(row[27]),
        "facturas_vencidas_permitidas": clean_int(row[28]),
        "descuento_automatico": clean_int(row[29]),
        "porcentaje_descuento": clean_float(row[30]),
        "predeterminado_pos": clean_int(row[31]),
        "facturacion_correo": clean_int(row[32]),
        "contacto_nombre": clean_str(row[33]),
        "contacto_apellido": clean_str(row[34]),
        "contacto_cargo": clean_str(row[35]),
        "contacto_correo": clean_str(row[36])
    }
    
    # Standardize nombre_razon_social field that other tables reference
    # It should be Nombre del Cliente + LastName if natural
    if client["tipo_cliente"] == "Natural":
        parts = [client["nombre_cliente"]]
        if client["primer_apellido"]:
            parts.append(client["primer_apellido"])
        if client["segundo_apellido"]:
            parts.append(client["segundo_apellido"])
        client["nombre_razon_social"] = " ".join(parts)
    else:
        client["nombre_razon_social"] = client["nombre_cliente"]
        
    # Standardize RUC or Cedula as main identificacion
    if client["numero_ruc"]:
        client["identificacion"] = client["numero_ruc"]
    elif client["numero_cedula"]:
        client["identificacion"] = client["numero_cedula"]
    else:
        client["identificacion"] = ""
        
    clients.append(client)

print(f"Parsed {len(clients)} clients.")

# Save to JSON
with open(r"C:\xampp\htdocs\Cycsa\scratch\clientes_import.json", "w", encoding="utf-8") as f:
    json.dump(clients, f, indent=4, ensure_ascii=False)

print("Exported to clientes_import.json successfully.")
