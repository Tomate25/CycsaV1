import subprocess
import os
import re
import datetime

mysqldump_bin = r"C:\xampp\mysql\bin\mysqldump.exe"
db_name = "cycsa_db"
host = "127.0.0.1"
port = "3306"
user = "root"

print(f"Exportando base de datos '{db_name}' usando mysqldump...")

cmd = [
    mysqldump_bin,
    f"--host={host}",
    f"--port={port}",
    f"--user={user}",
    "--default-character-set=utf8mb4",
    "--single-transaction",
    "--quick",
    "--routines",
    "--triggers",
    "--no-create-db",
    db_name
]

process = subprocess.run(cmd, capture_output=True, text=True, encoding="utf-8", errors="replace")

if process.returncode != 0:
    print("ERROR al ejecutar mysqldump:")
    print(process.stderr)
    exit(1)

raw_sql = process.stdout
print(f"mysqldump completado. Tamaño bruto: {len(raw_sql):,} caracteres.")

# 1. Limpieza de directivas conflictivas con Bluehost / cPanel / phpMyAdmin
cleaned_sql = raw_sql

# Eliminar CREATE DATABASE y USE
cleaned_sql = re.sub(r'(?i)CREATE DATABASE.*?;', '', cleaned_sql)
cleaned_sql = re.sub(r'(?i)USE `?[a-zA-Z0-9_]+`?;', '', cleaned_sql)

# Eliminar DEFINER de VISTAS, TRIGGERS y PROCEDURES
cleaned_sql = re.sub(r'(?i)/\*!50013\s+DEFINER=`?[^`*@]+`?@`?[^`*]+`?\s+\*/', '', cleaned_sql)
cleaned_sql = re.sub(r'(?i)DEFINER=`?[^`@]+`?@`?[^`\s]+`?', '', cleaned_sql)
cleaned_sql = re.sub(r'(?i)SQL SECURITY DEFINER', '', cleaned_sql)

# Reemplazar definiciones complejas de vistas generadas por mysqldump si tienen artefactos
# Cabecera estándar universal para importación limpia en phpMyAdmin
header = f"""-- ==============================================================================
-- CYCSA ERP & LIMS - Exportación Oficial para Producción (Bluehost cPanel)
-- Base de datos destino: cycsanic_cycsa_db
-- Generado el: {datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")}
-- Repositorio: https://github.com/Tomate25/CycsaV1.git (Rama main)
-- ==============================================================================

SET NAMES utf8mb4;
SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;

"""

footer = """
-- ==============================================================================
-- Restaurar configuraciones de MySQL / MariaDB
-- ==============================================================================
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_NOTES=@OLD_SQL_NOTES;

-- Exportación completada exitosamente.
"""

final_sql = header + cleaned_sql + footer

# Destinos de exportación
destinos = [
    r"C:\Users\abdia\Desktop\cycsa_db_para_produccion.sql",
    r"C:\Users\abdia\OneDrive\Desktop\cycsa_db_para_produccion.sql",
    r"C:\xampp\htdocs\Cycsa\cycsa_db_para_produccion.sql"
]

for dest in destinos:
    try:
        os.makedirs(os.path.dirname(dest), exist_ok=True)
        with open(dest, "w", encoding="utf-8", newline="\n") as f:
            f.write(final_sql)
        print(f"Archivo guardado exitosamente en: {dest} ({os.path.getsize(dest):,} bytes)")
    except Exception as e:
        print(f"Aviso no crítico al escribir en {dest}: {e}")

# Validaciones de integridad
tables_count = len(re.findall(r'(?i)CREATE TABLE', final_sql))
insert_count = len(re.findall(r'(?i)INSERT INTO', final_sql))
print(f"\n--- VALIDACIÓN DE INTEGRIDAD ---")
print(f"Tablas creadas (CREATE TABLE): {tables_count}")
print(f"Bloques de datos (INSERT INTO): {insert_count}")

# Verificar catálogos clave en el SQL
catalogos_clave = ['clientes', 'productos', 'plantillas', 'cuentas_contables', 'usuarios', 'roles', 'tecnicos', 'vehiculos']
for cat in catalogos_clave:
    has_insert = f"INSERT INTO `{cat}`" in final_sql or f"insert into `{cat}`" in final_sql.lower()
    print(f"Catálogo '{cat}': {'OK (Contiene datos)' if has_insert else 'ADVERTENCIA: Sin inserts'}")

print("\n¡EXPORTACIÓN COMPLETADA CON ÉXITO!")
