"""
Carga el archivo "NUEVO INVENTARIO IMJUVE..." en la tabla inventario_equipos.
Borra y reinserta los registros de cada tipo en cada ejecución.
Vincula id_usuario por coincidencia de apellido_paterno al finalizar.
"""

import pandas as pd
import psycopg2
import os
from dotenv import load_dotenv

load_dotenv()

XLSX = 'NUEVO INVENTARIO IMJUVE  ABRIL 2026.1xlsx.xlsx'

VACIOS = {'nan', 'none', 'null', 'n/a', '-', '/', ''}

def L(v):
    """Limpia un valor: NaN / strings vacíos → None."""
    if v is None:
        return None
    if isinstance(v, float) and pd.isna(v):
        return None
    s = str(v).strip()
    return None if s.lower() in VACIOS else s

def LI(v):
    """Limpia y convierte a entero (para columnas INTEGER)."""
    raw = L(v)
    if raw is None:
        return None
    try:
        return int(float(raw))
    except (ValueError, TypeError):
        return None

def conectar():
    return psycopg2.connect(
        host=os.getenv('DB_HOST'), port=os.getenv('DB_PORT', '5432'),
        database=os.getenv('DB_NAME'), user=os.getenv('DB_USER'), password=os.getenv('DB_PASS')
    )

# ── Laptop ──────────────────────────────────────────────────────────────────
def leer_laptops():
    df = pd.read_excel(XLSX, sheet_name='laptop', skiprows=2, header=0)
    # La hoja tiene columnas en posiciones fijas; las 'docking' están Unnamed
    # Col 0=No. 1=INVENTARIO 2=NOMBRE DEL EQUIPO 3=NOMBRE DE USUARIO 4=PERFIL 5=ÁREA
    # 6=MARCA 7=MODELO 8=SERIAL 9=CARGADOR 10=docking_marca 11=docking_modelo 12=docking_serie
    # 13=IPv4 14=MAC 15=RESPONSIVA 16=CANDADO 17=OBSERVACIONES
    df = df[df.iloc[:, 3].notna() | df.iloc[:, 2].notna()].reset_index(drop=True)
    registros = []
    for _, row in df.iterrows():
        r = list(row)
        registros.append({
            'tipo':           'Laptop',
            'consecutivo':    LI(r[0]),
            'num_inventario': LI(r[1]),
            'nombre_equipo':  L(r[2]),
            'nombre_usuario': L(r[3]),
            'perfil':         L(r[4]),
            'area':           L(r[5]),
            'cpu_marca':      L(r[6]),
            'cpu_modelo':     L(r[7]),
            'cpu_serie':      L(r[8]),
            'cargador_serie': L(r[9]),
            'docking_marca':  L(r[10]),
            'docking_modelo': L(r[11]),
            'docking_serie':  L(r[12]),
            'ipv4':           L(r[13]),
            'mac':            L(r[14]),
            'responsiva':     L(r[15]),
            'candado':        L(r[16]),
            'observaciones':  L(r[17]) if len(r) > 17 else None,
        })
    return registros

# ── PC Avanzadas ─────────────────────────────────────────────────────────────
def leer_pc_avanzadas():
    df = pd.read_excel(XLSX, sheet_name='PC Avanzadas', skiprows=2, header=0)
    # 0=CONSECUTIVO 1=INVENTARIO 2=NOMBRE DEL EQUIPO 3=NOMBRE DE USUARIO 4=PERFIL 5=ÁREA
    # 6=cpu_marca 7=cpu_modelo 8=cpu_serie 9=teclado_serie 10=mouse_serie
    # 11=monitor_marca 12=monitor_modelo 13=monitor_serie
    # 14=nobreak_marca 15=nobreak_modelo 16=nobreak_serie (vacía en este Excel)
    # 17=ipv4 18=mac 19=responsiva 20=observaciones
    df = df[df.iloc[:, 3].notna() | df.iloc[:, 2].notna()].reset_index(drop=True)
    registros = []
    for _, row in df.iterrows():
        r = list(row)
        def g(i): return L(r[i]) if i < len(r) else None
        registros.append({
            'tipo':           'PC Avanzada',
            'consecutivo':    LI(r[0]),
            'num_inventario': LI(r[1]),
            'nombre_equipo':  g(2),
            'nombre_usuario': g(3),
            'perfil':         g(4),
            'area':           g(5),
            'cpu_marca':      g(6),
            'cpu_modelo':     g(7),
            'cpu_serie':      g(8),
            'teclado_serie':  g(9),
            'mouse_serie':    g(10),
            'monitor_marca':  g(11),
            'monitor_modelo': g(12),
            'monitor_serie':  g(13),
            'nobreak_marca':  g(14),
            'nobreak_modelo': g(15),
            'ipv4':           g(17),
            'mac':            g(18),
            'responsiva':     g(19),
            'observaciones':  g(20),
        })
    return registros

# ── PC Especializadas ────────────────────────────────────────────────────────
def leer_pc_especializadas():
    df = pd.read_excel(XLSX, sheet_name='PC  Especializadas', skiprows=1, header=[0, 1])
    # Aplanar multi-header usando posición
    # 0=CONSECUTIVO 1=INVENTARIO 2=NOMBRE DEL EQUIPO 3=NOMBRE DE USUARIO 4=PERFIL 5=ÁREA
    # 6=cpu_marca 7=cpu_modelo 8=cpu_serie 9=teclado_serie 10=mouse_serie
    # 11=monitor_marca 12=monitor_modelo 13=monitor_serie
    # 14=nobreak_marca 15=nobreak_modelo 16=nobreak_serie
    # 17=ipv4 18=ipv4_actual 19=mac 20=responsiva 21=check_entrega 22=observaciones
    df = df[df.iloc[:, 3].notna() | df.iloc[:, 2].notna()].reset_index(drop=True)
    registros = []
    for _, row in df.iterrows():
        r = list(row)
        def g(i): return L(r[i]) if i < len(r) else None
        registros.append({
            'tipo':           'PC Especializada',
            'consecutivo':    LI(r[0]),
            'num_inventario': LI(r[1]),
            'nombre_equipo':  g(2),
            'nombre_usuario': g(3),
            'perfil':         g(4),
            'area':           g(5),
            'cpu_marca':      g(6),
            'cpu_modelo':     g(7),
            'cpu_serie':      g(8),
            'teclado_serie':  g(9),
            'mouse_serie':    g(10),
            'monitor_marca':  g(11),
            'monitor_modelo': g(12),
            'monitor_serie':  g(13),
            'nobreak_marca':  g(14),
            'nobreak_modelo': g(15),
            'nobreak_serie':  g(16),
            'ipv4':           g(17),
            'ipv4_actual':    g(18),
            'mac':            g(19),
            'responsiva':     g(20),
            'check_entrega':  g(21),
            'observaciones':  g(22),
        })
    return registros

# ── Insertar ─────────────────────────────────────────────────────────────────
COLS_INSERT = [
    'tipo', 'consecutivo', 'num_inventario', 'nombre_equipo', 'nombre_usuario',
    'perfil', 'area', 'cpu_marca', 'cpu_modelo', 'cpu_serie',
    'teclado_serie', 'mouse_serie',
    'monitor_marca', 'monitor_modelo', 'monitor_serie',
    'nobreak_marca', 'nobreak_modelo', 'nobreak_serie',
    'cargador_serie', 'docking_marca', 'docking_modelo', 'docking_serie', 'candado',
    'ipv4', 'ipv4_actual', 'mac',
    'responsiva', 'check_entrega', 'observaciones',
]

SQL_INSERT = (
    f"INSERT INTO inventario_equipos ({', '.join(COLS_INSERT)}) "
    f"VALUES ({', '.join(['%s'] * len(COLS_INSERT))})"
)

def insertar(cur, registros, tipo):
    cur.execute("DELETE FROM inventario_equipos WHERE tipo = %s", (tipo,))
    eliminados = cur.rowcount
    filas = [tuple(r.get(c) for c in COLS_INSERT) for r in registros]
    cur.executemany(SQL_INSERT, filas)
    print(f"  {tipo}: {eliminados} anteriores eliminados → {len(filas)} insertados")

# ── Main ─────────────────────────────────────────────────────────────────────
def cargar():
    print(f"Leyendo: {XLSX}")
    laptops    = leer_laptops()
    avanzadas  = leer_pc_avanzadas()
    especiales = leer_pc_especializadas()
    print(f"  Laptops: {len(laptops)}  PC Avanzadas: {len(avanzadas)}  PC Especializadas: {len(especiales)}")

    conn = conectar()
    cur = conn.cursor()

    insertar(cur, laptops,    'Laptop')
    insertar(cur, avanzadas,  'PC Avanzada')
    insertar(cur, especiales, 'PC Especializada')

    conn.commit()
    print(f"\nTotal cargado: {len(laptops)+len(avanzadas)+len(especiales)} equipos")

    # Vincular id_usuario por coincidencia de apellido_paterno
    cur.execute("""
        UPDATE inventario_equipos e
        SET id_usuario = (
            SELECT u.id_usuario FROM usuarios u
            WHERE e.nombre_usuario ILIKE '%%' || u.apellido_paterno || '%%'
            ORDER BY LENGTH(u.apellido_paterno) DESC
            LIMIT 1
        )
        WHERE e.nombre_usuario IS NOT NULL AND e.id_usuario IS NULL
    """)
    linked = cur.rowcount
    conn.commit()
    print(f"Equipos vinculados a usuario registrado: {linked}")

    cur.close()
    conn.close()

if __name__ == '__main__':
    cargar()
