import openpyxl

wb = openpyxl.load_workbook(r"C:\Users\abdia\Downloads\Exportar_data_clientes_29-06-2026 (1).xlsx", read_only=True)
sheet = wb.active

rows = list(sheet.iter_rows(values_only=True))
headers = rows[10]
for idx, h in enumerate(headers):
    print(f"Col {idx}: '{h}'")
