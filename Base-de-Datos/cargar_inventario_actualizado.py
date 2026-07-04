# -*- coding: utf-8 -*-
"""
Carga el inventario de equipos desde 'inventario actualizado.xlsx'.
Limpia la tabla inventario_equipos y la repopula desde cero.
Ejecutar: python cargar_inventario_actualizado.py
"""
import sys
import os
import psycopg2
import openpyxl

if sys.stdout.encoding != "utf-8":
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

DB_CONFIG = {
    "host":     "localhost",
    "database": "sistemitas",
    "user":     "postgres",
    "password": "Dievigom1311",
}

ARCHIVO = os.path.join(os.path.dirname(__file__), "inventario_modificado.xlsx")


def limpiar(v):
    if v is None:
        return None
    s = str(v).strip()
    return None if s.lower() in ("none", "nan", "") else s


def int_o_none(v):
    try:
        return int(v)
    except Exception:
        return None


def leer_laptops(wb):
    ws = wb["laptop"]
    equipos = []
    for row in ws.iter_rows(min_row=4, values_only=True):
        if int_o_none(row[0]) is None:
            continue
        equipos.append({
            "tipo":          "Laptop",
            "consecutivo":   int_o_none(row[0]),
            "num_inventario": limpiar(row[1]),
            "nombre_equipo": None,
            "nombre_usuario": limpiar(row[2]),
            "perfil":        limpiar(row[3]),
            "area":          limpiar(row[4]),
            "cpu_marca":     limpiar(row[5]),
            "cpu_modelo":    limpiar(row[6]),
            "cpu_serie":     limpiar(row[7]),
            "cargador_serie": limpiar(row[8]),
            "docking_marca": limpiar(row[9]),
            "docking_modelo": limpiar(row[10]),
            "docking_serie": limpiar(row[11]),
            "teclado_serie": None,
            "mouse_serie":   None,
            "monitor_marca": None,
            "monitor_modelo": None,
            "monitor_serie": None,
            "nobreak_marca": None,
            "nobreak_modelo": None,
            "nobreak_serie": None,
        })
    return equipos


def leer_pcs(wb, nombre_hoja, tipo_bd):
    ws = wb[nombre_hoja]
    equipos = []
    for row in ws.iter_rows(min_row=4, values_only=True):
        if int_o_none(row[0]) is None:
            continue
        equipos.append({
            "tipo":          tipo_bd,
            "consecutivo":   int_o_none(row[0]),
            "num_inventario": limpiar(row[1]),
            "nombre_equipo": limpiar(row[2]),
            "nombre_usuario": limpiar(row[3]),
            "perfil":        limpiar(row[4]),
            "area":          limpiar(row[5]),
            "cpu_marca":     limpiar(row[6]),
            "cpu_modelo":    limpiar(row[7]),
            "cpu_serie":     limpiar(row[8]),
            "teclado_serie": limpiar(row[9]),
            "mouse_serie":   limpiar(row[10]),
            "monitor_marca": limpiar(row[11]),
            "monitor_modelo": limpiar(row[12]),
            "monitor_serie": limpiar(row[13]),
            "nobreak_marca": limpiar(row[14]),
            "nobreak_modelo": limpiar(row[15]),
            "nobreak_serie": limpiar(row[16]),
            "cargador_serie": None,
            "docking_marca": None,
            "docking_modelo": None,
            "docking_serie": None,
        })
    return equipos


INSERT_SQL = """
INSERT INTO inventario_equipos (
    tipo, consecutivo, num_inventario, nombre_equipo,
    nombre_usuario, perfil, area,
    cpu_marca, cpu_modelo, cpu_serie,
    teclado_serie, mouse_serie,
    monitor_marca, monitor_modelo, monitor_serie,
    nobreak_marca, nobreak_modelo, nobreak_serie,
    cargador_serie, docking_marca, docking_modelo, docking_serie,
    fecha_carga
) VALUES (
    %(tipo)s, %(consecutivo)s, %(num_inventario)s, %(nombre_equipo)s,
    %(nombre_usuario)s, %(perfil)s, %(area)s,
    %(cpu_marca)s, %(cpu_modelo)s, %(cpu_serie)s,
    %(teclado_serie)s, %(mouse_serie)s,
    %(monitor_marca)s, %(monitor_modelo)s, %(monitor_serie)s,
    %(nobreak_marca)s, %(nobreak_modelo)s, %(nobreak_serie)s,
    %(cargador_serie)s, %(docking_marca)s, %(docking_modelo)s, %(docking_serie)s,
    NOW()
)
"""


def main():
    if not os.path.exists(ARCHIVO):
        print(f"ERROR: No se encontró el archivo '{ARCHIVO}'")
        return

    print("Paso 1: Leyendo Excel...")
    wb = openpyxl.load_workbook(ARCHIVO, read_only=True, data_only=True)
    equipos = []
    equipos += leer_laptops(wb)
    equipos += leer_pcs(wb, "PC Especializadas", "PC Especializada")
    equipos += leer_pcs(wb, "PC Avanzadas",      "PC Avanzada")
    wb.close()

    conteo = {
        "Laptop":           sum(1 for e in equipos if e["tipo"] == "Laptop"),
        "PC Especializada": sum(1 for e in equipos if e["tipo"] == "PC Especializada"),
        "PC Avanzada":      sum(1 for e in equipos if e["tipo"] == "PC Avanzada"),
    }
    print(f"  Laptops:           {conteo['Laptop']}")
    print(f"  PC Especializadas: {conteo['PC Especializada']}")
    print(f"  PC Avanzadas:      {conteo['PC Avanzada']}")
    print(f"  TOTAL:             {len(equipos)}")

    print("\nPaso 2: Conectando a PostgreSQL...")
    conn = psycopg2.connect(**DB_CONFIG)
    cur = conn.cursor()
    print("  OK - Conexión exitosa")

    print("\nPaso 3: Limpiando tabla inventario_equipos (CASCADE)...")
    cur.execute("TRUNCATE TABLE inventario_equipos RESTART IDENTITY CASCADE")
    print("  OK - Tabla vaciada")

    print("\nPaso 4: Insertando registros...")
    errores = 0
    for eq in equipos:
        try:
            cur.execute(INSERT_SQL, eq)
        except Exception as e:
            errores += 1
            print(f"  [!] Error en {eq.get('tipo')} #{eq.get('consecutivo')}: {e}")
            conn.rollback()

    conn.commit()
    cur.close()
    conn.close()

    print(f"\n>>> PROCESO FINALIZADO <<<")
    print(f"    Insertados : {len(equipos) - errores}")
    print(f"    Errores    : {errores}")


if __name__ == "__main__":
    main()
