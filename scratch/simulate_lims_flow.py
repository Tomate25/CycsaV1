import os
import sys
import re
import json
import subprocess
import mysql.connector

# Reconfigure stdout to use UTF-8 to prevent charmap encoding errors on Windows terminal
sys.stdout.reconfigure(encoding='utf-8')

def load_env(env_path):
    env_vars = {}
    if os.path.exists(env_path):
        with open(env_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith('#') or line.startswith(';'):
                    continue
                parts = line.split('=', 1)
                if len(parts) == 2:
                    k, v = parts[0].strip(), parts[1].strip()
                    if len(v) >= 2 and ((v[0] == '"' and v[-1] == '"') or (v[0] == "'" and v[-1] == "'")):
                        v = v[1:-1]
                    env_vars[k] = v
    return env_vars

def main():
    print("=== INICIANDO SIMULACION DEL FLUJO LIMS DESDE PYTHON ===")
    
    # 1. Load DB Credentials
    env = load_env("C:/xampp/htdocs/Cycsa/.env.local")
    if not env:
        env = load_env("C:/xampp/htdocs/Cycsa/.env")
        
    db_host = env.get("DB_HOST", "localhost")
    db_name = env.get("DB_NAME", "cycsa_db")
    db_user = env.get("DB_USER", "root")
    db_pass = env.get("DB_PASS", "")
    
    print(f"Conectando a base de datos: {db_name} en {db_host}... ")
    
    conn = mysql.connector.connect(
        host=db_host,
        database=db_name,
        user=db_user,
        password=db_pass
    )
    cursor = conn.cursor(dictionary=True)
    
    try:
        # 2. Clear previous data
        print("Limpiando tablas de cotizaciones, órdenes y laboratorios para empezar limpio...")
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
        cursor.execute("TRUNCATE TABLE ensayo_edades")
        cursor.execute("TRUNCATE TABLE lotes_muestras")
        cursor.execute("TRUNCATE TABLE recepcion_muestras")
        cursor.execute("TRUNCATE TABLE ordenes_servicio")
        cursor.execute("TRUNCATE TABLE cotizacion_detalles")
        cursor.execute("TRUNCATE TABLE cotizaciones")
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
        conn.commit()
        print("✓ Tablas vaciadas correctamente.\n")
        
        # 3. Get first client
        cursor.execute("SELECT * FROM clientes ORDER BY id ASC LIMIT 1")
        client = cursor.fetchone()
        if not client:
            raise Exception("No hay clientes en la base de datos para simular.")
        
        print(f"Cliente elegido para simulación: {client['nombre_razon_social']} (ID: {client['id']})\n")
        
        # Get products info
        cursor.execute("SELECT p.*, fe.nombre AS formato_nombre, fe.archivo_markdown FROM productos p LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id WHERE p.id = 16")
        prodSuelo = cursor.fetchone()
        cursor.execute("SELECT p.*, fe.nombre AS formato_nombre, fe.archivo_markdown FROM productos p LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id WHERE p.id = 8")
        prodConcreto = cursor.fetchone()
        cursor.execute("SELECT p.*, fe.nombre AS formato_nombre, fe.archivo_markdown FROM productos p LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id WHERE p.id = 18")
        prodProctor = cursor.fetchone()
        
        # Define Sieve Soil results JSON
        resultadosSuelo = [
            {"Malla":"2\"","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"0.00","% que pasa la malla":"100.00","Límite Mín":"100","Límite Máx":"100"},
            {"Malla":"1 1/2\"","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"0.00","% que pasa la malla":"100.00","Límite Mín":"","Límite Máx":""},
            {"Malla":"1\"","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"0.00","% que pasa la malla":"100.00","Límite Mín":"75","Límite Máx":"95"},
            {"Malla":"3/4\"","P. Retenido parcial (gr)":"46.3200","% Retenido parcial":"6.30","% Acumulativo":"6.30","% que pasa la malla":"93.70","Límite Mín":"","Límite Máx":""},
            {"Malla":"1/2\"","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"6.30","% que pasa la malla":"93.70","Límite Mín":"50","Límite Máx":"80"},
            {"Malla":"3/8\"","P. Retenido parcial (gr)":"78.6900","% Retenido parcial":"10.70","% Acumulativo":"17.01","% que pasa la malla":"82.99","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 4","P. Retenido parcial (gr)":"67.1000","% Retenido parcial":"9.13","% Acumulativo":"26.13","% que pasa la malla":"73.87","Límite Mín":"30","Límite Máx":"65"},
            {"Malla":"No. 8","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"26.13","% que pasa la malla":"73.87","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 10","P. Retenido parcial (gr)":"80.3000","% Retenido parcial":"10.92","% Acumulativo":"37.06","% que pasa la malla":"62.94","Límite Mín":"20","Límite Máx":"50"},
            {"Malla":"No. 16","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"37.06","% que pasa la malla":"62.94","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 20","P. Retenido parcial (gr)":"84.5800","% Retenido parcial":"11.51","% Acumulativo":"48.56","% que pasa la malla":"51.44","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 30","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"48.56","% que pasa la malla":"51.44","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 40","P. Retenido parcial (gr)":"80.2100","% Retenido parcial":"10.91","% Acumulativo":"59.47","% que pasa la malla":"40.53","Límite Mín":"10","Límite Máx":"35"},
            {"Malla":"No. 50","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"59.47","% que pasa la malla":"40.53","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 60","P. Retenido parcial (gr)":"61.4600","% Retenido parcial":"8.36","% Acumulativo":"67.83","% que pasa la malla":"32.17","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 80","P. Retenido parcial (gr)":"0.0000","% Retenido parcial":"0.00","% Acumulativo":"67.83","% que pasa la malla":"32.17","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 100","P. Retenido parcial (gr)":"50.4700","% Retenido parcial":"6.87","% Acumulativo":"74.70","% que pasa la malla":"25.30","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 140","P. Retenido parcial (gr)":"29.6900","% Retenido parcial":"4.04","% Acumulativo":"78.74","% que pasa la malla":"21.26","Límite Mín":"","Límite Máx":""},
            {"Malla":"No. 200","P. Retenido parcial (gr)":"25.3600","% Retenido parcial":"3.45","% Acumulativo":"82.19","% que pasa la malla":"17.81","Límite Mín":"0","Límite Máx":"16"},
            {"Malla":"Fondo","P. Retenido parcial (gr)":"1.8000","% Retenido parcial":"0.24","% Acumulativo":"82.43","% que pasa la malla":"17.57","Límite Mín":"","Límite Máx":""},
            {"Malla":"Pérdida lavado","P. Retenido parcial (gr)":"128.7600","% Retenido parcial":"17.52","% Acumulativo":"99.95","% que pasa la malla":"0.05","Límite Mín":"","Límite Máx":""},
            {"Malla":"Suma","P. Retenido parcial (gr)":"735.1300","% Retenido parcial":"100.00","% Acumulativo":"100.00","% que pasa la malla":"0.00","Límite Mín":"","Límite Máx":""},
            {"Malla":"Límite Líquido","P. Retenido parcial (gr)":"—","% Retenido parcial":"—","% Acumulativo":"—","% que pasa la malla":"37.00","Límite Mín":"","Límite Máx":""},
            {"Malla":"Límite Plástico","P. Retenido parcial (gr)":"—","% Retenido parcial":"—","% Acumulativo":"—","% que pasa la malla":"22.00","Límite Mín":"","Límite Máx":""},
            {"Malla":"I.P","P. Retenido parcial (gr)":"—","% Retenido parcial":"—","% Acumulativo":"—","% que pasa la malla":"15.00","Límite Mín":"","Límite Máx":""}
        ]
        
        resultadosProctor = [
            {"Punto":"1","Humedad (%)":"12.0","Densidad Seca (g/cm³)":"1.85"},
            {"Punto":"2","Humedad (%)":"14.0","Densidad Seca (g/cm³)":"1.92"},
            {"Punto":"3","Humedad (%)":"16.0","Densidad Seca (g/cm³)":"1.89"}
        ]

        # 4. Loop 3 times (Generate 3 separate operational flows)
        for i in range(1, 4):
            print(f"\n>>> PROCESANDO TRANSACCION / FLUJO OPERATIVO {i} DE 3 <<<")
            
            # Create Quote (Cotización)
            code_cot = f"COT-2026-000{i}"
            sql_cot = """
                INSERT INTO cotizaciones 
                (id_cliente, codigo, tipo_moneda, id_usuario_creador, id_usuario_revisor, version, nombre_proyecto, direccion_proyecto, prioridad, fecha_limite, condicion_pago, tiempo_entrega, vigencia_oferta, estado, subtotal, descuento, total, exonerado, impuesto) 
                VALUES 
                (%s, %s, 1, 1, 1, 1, %s, 'KM 84 Carretera León-Managua', 'Normal', '2026-08-14', 'Crédito', '7 días hábiles', '15 días', 'Aprobada por Cliente', 4000.00, 0.00, 4000.00, 0, 0.00)
            """
            proj_name = f"Proyecto Simulación Venta {i}"
            cursor.execute(sql_cot, (client['id'], code_cot, proj_name))
            id_cot = cursor.lastrowid
            print(f"  ✓ Cotización {code_cot} creada (ID: {id_cot})")
            
            # Create Details
            sql_det = """
                INSERT INTO cotizacion_detalles 
                (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, formato_reporte, cantidad, precio_unitario, subtotal, resultados_json) 
                VALUES 
                (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            
            # Detail 1: Soil Granulometry
            cursor.execute(sql_det, (id_cot, 16, prodSuelo['nombre_comercial'], prodSuelo['codigo_servicio'], prodSuelo['norma_astm'], prodSuelo['archivo_markdown'], 1.00, prodSuelo['precio'], prodSuelo['precio'], json.dumps(resultadosSuelo)))
            id_det_suelo = cursor.lastrowid
            
            # Detail 2: Concrete breaking
            cursor.execute(sql_det, (id_cot, 8, prodConcreto['nombre_comercial'], prodConcreto['codigo_servicio'], prodConcreto['norma_astm'], prodConcreto['archivo_markdown'], 3.00, prodConcreto['precio'], prodConcreto['precio'] * 3, json.dumps([])))
            id_det_concreto = cursor.lastrowid
            
            # Detail 3: Proctor
            cursor.execute(sql_det, (id_cot, 18, prodProctor['nombre_comercial'], prodProctor['codigo_servicio'], prodProctor['norma_astm'], prodProctor['archivo_markdown'], 1.00, prodProctor['precio'], prodProctor['precio'], json.dumps(resultadosProctor)))
            id_det_proctor = cursor.lastrowid
            print(f"  ✓ 3 Detalles de Ensayo agregados.")
            
            # Create OS
            code_os = f"OS-2026-000{i}"
            sql_os = """
                INSERT INTO ordenes_servicio 
                (id_cotizacion, codigo_os, tipo_contrato, fecha_emision, estado, requiere_muestreo) 
                VALUES 
                (%s, %s, 'Puntual', '2026-07-14', 'Emitida', 0)
            """
            cursor.execute(sql_os, (id_cot, code_os))
            id_os = cursor.lastrowid
            print(f"  ✓ Orden de Servicio {code_os} creada (ID: {id_os})")
            
            # Create Reception
            code_muestra = f"MS-000{i}-26"
            sql_rec = """
                INSERT INTO recepcion_muestras 
                (id_os, correlativo_anual, anio, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, entregado_por, observaciones, estado) 
                VALUES 
                (%(id_os)s, %(corr)s, 2026, %(cod_m)s, 'CAMP-001', '2026-07-14 09:00:00', 1, 'Ing. Carlos Pérez', 'Muestras de simulación automatizada', 'En Laboratorio')
            """
            cursor.execute(sql_rec, {'id_os': id_os, 'corr': i, 'cod_m': code_muestra})
            id_recepcion = cursor.lastrowid
            print(f"  ✓ Recepción de Muestra {code_muestra} registrada (ID: {id_recepcion})")
            
            # Create Lotes
            sql_lote = """
                INSERT INTO lotes_muestras 
                (id_recepcion, nombre_lote, diseno_resistencia, fecha_moldeo, revenimiento_in, revenimiento_cm, temperatura_c, procedimiento_muestreo) 
                VALUES 
                (%s, %s, %s, %s, %s, %s, %s, %s)
            """
            # Lote Suelo
            cursor.execute(sql_lote, (id_recepcion, f"Suelo Lote A-{i}", "", "2026-07-14", None, None, None, "ASTM D6913"))
            id_lote_suelo = cursor.lastrowid
            
            # Lote Concreto
            cursor.execute(sql_lote, (id_recepcion, f"Concreto Lote B-{i}", "3000", "2026-07-13", 3.50, 9.00, 26.5, "ASTM C172"))
            id_lote_concreto = cursor.lastrowid
            
            # Lote Proctor
            cursor.execute(sql_lote, (id_recepcion, f"Proctor Lote C-{i}", "", "2026-07-14", None, None, None, "ASTM D698"))
            id_lote_proctor = cursor.lastrowid
            print(f"  ✓ Lotes de Muestras creados (Suelo, Concreto, Proctor)")
            
            # Create specimens for Concreto (1 day broke today, 2 day programmed tomorrow/wait 24h, 7 day future)
            sql_esp = """
                INSERT INTO ensayo_edades 
                (id_lote, id_detalle_cotizacion, identificador_especimen, edad_dias, fecha_programada, fecha_ensaye_real, carga_lbs, area_in2, resistencia_psi, resistencia_kgcm2, porcentaje_diseno, cumple_norma, estado, usuario_ensayador) 
                VALUES 
                (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 1)
            """
            # Specimen A (1d - broke today)
            cursor.execute(sql_esp, (id_lote_concreto, id_det_concreto, 'A', 1, '2026-07-14', '2026-07-14', 85000.00, 28.274, 3006.00, 211.30, 100.20, 1, 'Completado'))
            # Specimen B (2d - programmed tomorrow, waits 24h)
            cursor.execute(sql_esp, (id_lote_concreto, id_det_concreto, 'B', 2, '2026-07-15', None, None, None, None, None, None, 0, 'Programado'))
            # Specimen C (7d - programmed future)
            cursor.execute(sql_esp, (id_lote_concreto, id_det_concreto, 'C', 7, '2026-07-20', None, None, None, None, None, None, 0, 'Programado'))
            
            # Dummy specimens for soil and proctor
            sql_dummy = """
                INSERT INTO ensayo_edades 
                (id_lote, id_detalle_cotizacion, identificador_especimen, edad_dias, fecha_programada, estado, usuario_ensayador) 
                VALUES 
                (%s, %s, 'Muestra', 0, '2026-07-14', 'Completado', 1)
            """
            cursor.execute(sql_dummy, (id_lote_suelo, id_det_suelo))
            cursor.execute(sql_dummy, (id_lote_proctor, id_det_proctor))
            print(f"  ✓ Especímenes creados (Concreto: A=Completado, B=Programado/24h, C=Programado)")
            
            # Commit this transaction
            conn.commit()
            
            # 5. Generate PDF Reports for this transaction programmatically!
            print("  Generating PDF report for Soil Granulometry...")
            pdf_suelo_code = f"INF-SUELO-000{i}-v0"
            sub_res_suelo = subprocess.run(
                ["php", "C:/xampp/htdocs/Cycsa/scratch/compile_pdf_cli.php", str(id_det_suelo), str(id_lote_suelo), pdf_suelo_code, "0"],
                capture_output=True, text=True
            )
            print("   " + sub_res_suelo.stdout.strip())
            
            print("  Generating PDF report for Concrete breakages...")
            pdf_concreto_code = f"INF-CONCRETO-000{i}-v0"
            sub_res_conc = subprocess.run(
                ["php", "C:/xampp/htdocs/Cycsa/scratch/compile_pdf_cli.php", str(id_det_concreto), str(id_lote_concreto), pdf_concreto_code, "0"],
                capture_output=True, text=True
            )
            print("   " + sub_res_conc.stdout.strip())
            
        print("\n=== SIMULACION DE LAS 3 TRANSACCIONES COMPLETADA CON EXITO ===")
        print("Toda la base de datos operativa fue reiniciada y cargada con las 3 ventas para el cliente.")
        print("Los 6 informes en PDF (3 de suelo con curvas, 3 de concreto) fueron generados y guardados en tu carpeta de Descargas.")
        
    except Exception as e:
        conn.rollback()
        print(f"Error durante la simulación: {e}")
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    main()
