# -*- coding: utf-8 -*-
import os
os.environ["LANG"]   = "en_US"
os.environ["LC_ALL"] = "en_US"

import psycopg2
import pandas as pd

DB_CONFIG = {
    "host":     "localhost",
    "database": "sistemitas",      # ← minúsculas
    "user":     "postgres",
    "password": "Dan040904",       # ← contraseña correcta
    "options":  "-c client_encoding=UTF8"
}

NUMERO_GENERAL = "(55)1500 1300"

def buscar_id_usuario(nombre, ap_pat, ap_mat, cursor):
    cursor.execute("""
        SELECT id_usuario FROM usuarios
        WHERE LOWER(nombre) LIKE %s
        AND LOWER(apellido_paterno) LIKE %s
    """, (f"%{nombre.lower().split()[0]}%", f"%{ap_pat.lower()}%"))
    row = cursor.fetchone()
    return row[0] if row else None

def cargar_telefonos():
    archivo = "C:/py/directorio_imjuve.xlsx"

    print("Leyendo Excel...")
    df = pd.read_excel(archivo, engine="openpyxl")
    df = df.fillna("")
    print(f"  {len(df)} registros encontrados")

    conn   = psycopg2.connect(**DB_CONFIG)
    cursor = conn.cursor()

    total_insertados  = 0
    total_sin_usuario = 0

    for _, fila in df.iterrows():
        nombre   = str(fila.iloc[2]).strip()
        ap_pat   = str(fila.iloc[3]).strip()
        ap_mat   = str(fila.iloc[4]).strip()
        extension = str(fila.iloc[1]).strip()

        if not nombre or not ap_pat:
            continue

        id_usuario = buscar_id_usuario(nombre, ap_pat, ap_mat, cursor)

        if id_usuario is None:
            total_sin_usuario += 1
            print(f"  [?] Sin usuario en BD: '{nombre} {ap_pat}'")
            continue

        cursor.execute("""
            INSERT INTO telefonos (numero_general, extension, id_usuario)
            VALUES (%s, %s, %s)
            ON CONFLICT DO NOTHING
        """, (NUMERO_GENERAL, extension, id_usuario))
        total_insertados += 1

    conn.commit()
    print(f"\n>>> PROCESO FINALIZADO <<<")
    print(f"    Insertados : {total_insertados}")
    print(f"    Sin usuario: {total_sin_usuario}")

    cursor.close()
    conn.close()

if __name__ == "__main__":
    cargar_telefonos()