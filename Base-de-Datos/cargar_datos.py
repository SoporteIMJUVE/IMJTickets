# -*- coding: utf-8 -*-
import os
os.environ["LANG"]   = "en_US"
os.environ["LC_ALL"] = "en_US"

import psycopg2
import pandas as pd
import sys
import locale

locale.setlocale(locale.LC_ALL, 'C')

if sys.stdout.encoding != 'utf-8':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

DB_CONFIG = {
    "host":     "localhost",
    "database": "Sistemitas",
    "user":     "postgres",
    "password": "Pistache07",
    "options":  "-c client_encoding=UTF8"
}

HOJAS = [
    {"nombre": "laptop",            "header": 3},
    {"nombre": "PC ESPECIALIZADAS", "header": 2},
    {"nombre": "PC AVANZADAS",      "header": 3},
]

def limpiar(valor):
    s = str(valor).strip()
    return "" if s.lower() == "nan" else s

def cargar_datos():
    conn   = None
    cursor = None
    archivo = "C:/py/datos.xlsx"

    if not os.path.exists(archivo):
        print("ERROR: No se encontro el archivo en C:\\py\\datos.xlsx")
        return

    try:
        print("Paso 1: Leyendo Excel...")
        dfs = {}
        for hoja in HOJAS:
            dfs[hoja["nombre"]] = pd.read_excel(
                archivo,
                sheet_name = hoja["nombre"],
                header     = hoja["header"],
                engine     = "openpyxl"
            )
            print(f"  OK - {hoja['nombre']}: {len(dfs[hoja['nombre']])} filas")
    except Exception as e:
        print(f"FALLO en lectura Excel: {e}")
        return

    try:
        print("\nPaso 2: Conectando a PostgreSQL...")
        conn   = psycopg2.connect(**DB_CONFIG)
        cursor = conn.cursor()
        print("  OK - Conexion exitosa")
    except Exception as e:
        print(f"FALLO en conexion BD: {e}")
        return

    query = """
    INSERT INTO computo (id_usuario, nombre_equipo, serie, monitor_serie, mac_address)
    VALUES (%s, %s, %s, %s, %s)
    ON CONFLICT (id_usuario)
    DO UPDATE SET
        serie         = EXCLUDED.serie,
        nombre_equipo = EXCLUDED.nombre_equipo,
        monitor_serie = EXCLUDED.monitor_serie,
        mac_address   = EXCLUDED.mac_address;
    """

    total_insertados = 0
    total_errores    = 0

    try:
        for hoja in HOJAS:
            print(f"\nInsertando hoja: {hoja['nombre']}...")
            df = dfs[hoja["nombre"]].fillna("")

            for idx, fila in df.iterrows():
                try:
                    id_usuario    = limpiar(fila.iloc[3])
                    nombre_equipo = limpiar(fila.iloc[2])
                    serie         = limpiar(fila.iloc[8])
                    monitor_serie = limpiar(fila.iloc[12])
                    mac_address   = limpiar(fila.iloc[15])

                    if not id_usuario or id_usuario.lower() in ("nan", "nombre de usuario", "perfil"):
                        continue

                    cursor.execute(query, (id_usuario, nombre_equipo, serie, monitor_serie, mac_address))
                    total_insertados += 1

                except Exception as e:
                    conn.rollback()
                    total_errores += 1
                    print(f"  [!] Fila {idx} omitida: {e}")
                    continue

        conn.commit()
        print(f"\n>>> PROCESO FINALIZADO <<<")
        print(f"    Insertados/actualizados : {total_insertados}")
        print(f"    Filas omitidas          : {total_errores}")

    except Exception as e:
        conn.rollback()
        mensaje = str(e).encode('utf-8', 'replace').decode('utf-8')
        print(f"\nERROR GENERAL: {mensaje}")

    finally:
        if cursor: cursor.close()
        if conn:   conn.close()

if __name__ == "__main__":
    cargar_datos()