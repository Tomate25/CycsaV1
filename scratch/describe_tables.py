import mysql.connector

conn = mysql.connector.connect(
    host="localhost",
    database="cycsa_db",
    user="root",
    password=""
)
cursor = conn.cursor()

def describe_table(table_name):
    print(f"\n--- Structure of {table_name} ---")
    cursor.execute(f"DESCRIBE {table_name}")
    for row in cursor.fetchall():
        print(row)

describe_table("cuentas_por_cobrar")
describe_table("cuentas_por_pagar")
describe_table("bancos_transacciones")

cursor.close()
conn.close()
