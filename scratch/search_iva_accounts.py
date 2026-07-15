import mysql.connector

conn = mysql.connector.connect(
    host="localhost",
    database="cycsa_db",
    user="root",
    password=""
)
cursor = conn.cursor(dictionary=True)

cursor.execute("SELECT * FROM cuentas_contables WHERE nombre LIKE '%IVA%' OR codigo LIKE '103%' OR codigo LIKE '202%'")
rows = cursor.fetchall()
print("=== Accounts matching IVA or codes ---")
for r in rows:
    print(r)

cursor.close()
conn.close()
