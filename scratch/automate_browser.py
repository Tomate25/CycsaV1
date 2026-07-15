import time
import sys
from playwright.sync_api import sync_playwright

# Configure stdout to use UTF-8
sys.stdout.reconfigure(encoding='utf-8')

def main():
    print("=== INICIANDO EXPLICACION Y AUTOMATIZACION VISUAL EN TU NAVEGADOR ===")
    print("Este script abrirá Chrome de forma visible y te guiará por el flujo del sistema LIMS.")
    
    with sync_playwright() as p:
        # Launch headful browser so the user can see it
        browser = p.chromium.launch(headless=False, slow_mo=1000) # 1s delay per action so it is easy to watch
        context = browser.new_context(viewport={"width": 1280, "height": 800})
        page = context.new_page()
        
        # 1. Open login page
        url = "http://localhost/Cycsa/publico/"
        print(f"\n1. Abriendo pantalla de login: {url}")
        page.goto(url)
        page.wait_for_timeout(2000)
        
        # 2. Fill credentials
        email = "gerencia@cycsanic.com"
        password = "NoelQuin2026!"
        print(f"2. Ingresando credenciales de Gerente (Administrador): {email}")
        page.fill("input[id=email]", email)
        page.fill("input[id=password]", password)
        page.wait_for_timeout(1000)
        
        # Click login
        print("3. Haciendo clic en 'Iniciar Sesión'...")
        page.click("button[type=submit]")
        page.wait_for_load_state("networkidle")
        print("   ✓ Sesión iniciada con éxito.")
        page.wait_for_timeout(2000)
        
        # 3. Go to Cotizaciones
        print("\n4. Navegando al módulo de Cotizaciones Comerciales...")
        page.goto("http://localhost/Cycsa/publico/cotizaciones")
        page.wait_for_timeout(3000)
        
        # 4. Go to Órdenes de Servicio
        print("\n5. Navegando al módulo de Órdenes de Servicio (LIMS)...")
        page.goto("http://localhost/Cycsa/publico/operaciones")
        page.wait_for_timeout(3000)
        
        # 5. Go to Recepción de Muestras
        print("\n6. Navegando a la Recepción Técnica de Muestras (Ingreso de Muestras)...")
        page.goto("http://localhost/Cycsa/publico/recepcion-muestras")
        page.wait_for_timeout(3000)
        
        # 6. Go to Laboratorio / Ensayos
        print("\n7. Navegando al Calendario y Carga de Ensayos del Laboratorio...")
        page.goto("http://localhost/Cycsa/publico/laboratorio")
        page.wait_for_timeout(3000)
        
        # 7. Go to Detail of Concrete Lot 2 (seeded with breakages!)
        print("\n8. Abriendo el Lote de Concreto B-1 (ID 2) para ver las roturas de cilindros y autocálculos...")
        page.goto("http://localhost/Cycsa/publico/operaciones/detalle-lote?id_lote=2")
        page.wait_for_timeout(5000)
        
        # 8. Finish
        print("\n=== RECORRIDO COMPLETADO ===")
        print("Hemos navegado por todo el flujo de LIMS. Cerrando el navegador...")
        browser.close()

if __name__ == "__main__":
    main()
