import os
import sys
import mysql.connector

# Reconfigure stdout to use UTF-8
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
    print("=== LIMPIANDO TODA LA BASE DE DATOS OPERATIVA ===")
    
    # Load DB Credentials
    env = load_env("C:/xampp/htdocs/Cycsa/.env.local")
    if not env:
        env = load_env("C:/xampp/htdocs/Cycsa/.env")
        
    db_host = env.get("DB_HOST", "localhost")
    db_name = env.get("DB_NAME", "cycsa_db")
    db_user = env.get("DB_USER", "root")
    db_pass = env.get("DB_PASS", "")
    
    print(f"Conectando a base de datos: {db_name} en {db_host}...")
    
    conn = mysql.connector.connect(
        host=db_host,
        database=db_name,
        user=db_user,
        password=db_pass
    )
    cursor = conn.cursor()
    
    try:
        # Clear tables
        print("Vaciando tablas transaccionales de LIMS...")
        cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
        cursor.execute("TRUNCATE TABLE ensayo_edades")
        cursor.execute("TRUNCATE TABLE lotes_muestras")
        cursor.execute("TRUNCATE TABLE recepcion_muestras")
        cursor.execute("TRUNCATE TABLE ordenes_servicio")
        cursor.execute("TRUNCATE TABLE cotizacion_detalles")
        cursor.execute("TRUNCATE TABLE cotizaciones")
        cursor.execute("TRUNCATE TABLE informes_control") # Also clear generated report entries!
        cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
        conn.commit()
        print("✓ Toda la base de datos operativa ha sido vaciada e inicializada correctamente para tus pruebas.")
        
    except Exception as e:
        conn.rollback()
        print(f"Error al limpiar base de datos: {e}")
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    main()
