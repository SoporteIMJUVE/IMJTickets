import pandas as pd
import psycopg2
import os
from dotenv import load_dotenv

load_dotenv()

XLSX = 'Lista_IPS.xlsx'
PESTANAS_OMITIR = {'RANGO', 'DATOS', 'Configuraciones '}

COLUMNAS = {
    'IP':                              'ip',
    'Usuario o nombre del propietario':'usuario',
    'Tipo de \nequipo':                'tipo_equipo',
    'Institucional o \nPersonal':      'institucional_o_personal',
    'Marca':                           'marca',
    'modelo':                          'modelo',
    'Número de \nSerie':               'serie',
    'MAC':                             'mac',
    'Tipo de \nConexión':              'tipo_conexion',
    'Confguración de red':             'config_red',
    'Área':                            'area_excel',
    'Restricciones':                   'restricciones',
    'YOUTUBE':                         'youtube',
    'VIMEO':                           'vimeo',
    'SPOTIFY':                         'spotify',
    'OTRO STREAMING':                  'otros_streaming',
    'FACEBOOK':                        'facebook',
    'TIK TOK':                         'tiktok',
    'INSTAGRAM':                       'instagram',
    'WHATSAPP WEB':                    'whatsapp_web',
    'OTRA RED SOCIAL':                 'otra_red_social',
    'SITIOS GUBERNAMENTALES':          'sitios_gub',
    'NOTICIAS':                        'noticias',
    'OTRO':                            'otro_permiso',
    'Estatus':                         'estatus',
    'OBSERVACIONES':                   'observaciones',
}

COLS_BD = [
    'ip', 'usuario', 'tipo_equipo', 'institucional_o_personal', 'marca', 'modelo',
    'serie', 'mac', 'tipo_conexion', 'config_red', 'area_excel', 'departamento_pestana',
    'restricciones', 'youtube', 'vimeo', 'spotify', 'otros_streaming', 'facebook',
    'tiktok', 'instagram', 'whatsapp_web', 'otra_red_social', 'sitios_gub', 'noticias',
    'otro_permiso', 'estatus', 'observaciones',
]

VACIOS = {'nan', 'none', 'null', 'n/a', '-', '/', ''}

def limpiar_valor(v):
    """Convierte NaN, None y strings vacíos/basura a None real para PostgreSQL."""
    if v is None:
        return None
    if isinstance(v, float) and pd.isna(v):
        return None
    s = str(v).strip()
    return None if s.lower() in VACIOS else s

def limpiar_df(df):
    """Aplica limpiar_valor a todas las columnas excepto 'ip'."""
    for col in df.columns:
        if col != 'ip':
            df[col] = df[col].apply(limpiar_valor)
    return df

def cargar():
    print(f"Leyendo: {XLSX}")
    xl = pd.ExcelFile(XLSX)
    pestanas = [s for s in xl.sheet_names if s not in PESTANAS_OMITIR]
    print(f"Departamentos encontrados: {pestanas}\n")

    todos = []
    for depto in pestanas:
        # Nombre del departamento = primera celda de la hoja (fila del titulo)
        df_raw = pd.read_excel(XLSX, sheet_name=depto, header=None, nrows=1)
        nombre_depto = str(df_raw.iloc[0, 0]).strip().upper()

        # Datos reales (row 0 = encabezados de columna, row 1+ = registros)
        df = pd.read_excel(XLSX, sheet_name=depto, skiprows=1)
        df = df.rename(columns=COLUMNAS)

        if 'ip' not in df.columns:
            print(f"  [{depto}] Sin columna IP, omitiendo.")
            continue

        df['ip'] = df['ip'].astype(str).str.strip()
        df = df[df['ip'].str.match(r'^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$')]
        df = df.dropna(subset=['ip'])
        df['departamento_pestana'] = nombre_depto

        cols_presentes = [c for c in COLS_BD if c in df.columns]
        todos.append(df[cols_presentes])

        ocup  = (df.get('estatus', pd.Series()).astype(str).str.strip() == 'Ocupada').sum()
        libre = (df.get('estatus', pd.Series()).astype(str).str.strip() == 'Libre').sum()
        print(f"  {nombre_depto}: {len(df)} IPs  (Ocupadas: {ocup}, Libres: {libre})")

    if not todos:
        print("Sin datos para cargar.")
        return

    df_final = pd.concat(todos, ignore_index=True)

    # Limpiar todos los valores: NaN, "nan", "None", "/" -> NULL real
    df_final = limpiar_df(df_final)

    # Detectar y eliminar IPs duplicadas entre pestañas (conservar la primera)
    dups = df_final[df_final.duplicated('ip', keep=False)][['ip','departamento_pestana']]
    if not dups.empty:
        print(f"\nADVERTENCIA: IPs duplicadas (se conserva la primera):")
        print(dups.to_string(index=False))
    df_final = df_final.drop_duplicates(subset='ip', keep='first').reset_index(drop=True)

    # Auto-corregir: usuario asignado pero Estatus Libre -> Ocupada
    if 'usuario' in df_final.columns and 'estatus' in df_final.columns:
        mask_fix = (
            df_final['usuario'].notna() &
            (df_final['estatus'].astype(str).str.strip().str.lower() == 'libre')
        )
        if mask_fix.any():
            df_final.loc[mask_fix, 'estatus'] = 'Ocupada'
            print(f"\nAuto-corregidas {mask_fix.sum()} IPs con usuario asignado pero Estatus Libre -> Ocupada")

    total_ocup  = (df_final['estatus'] == 'Ocupada').sum()
    total_libre = (df_final['estatus'] == 'Libre').sum()
    print(f"\nTOTAL: {len(df_final)} IPs en {len(pestanas)} departamentos")
    print(f"  Ocupadas: {total_ocup}  |  Libres: {total_libre}")

    conn = psycopg2.connect(
        host=os.getenv('DB_HOST'),
        port=os.getenv('DB_PORT', '5432'),
        database=os.getenv('DB_NAME'),
        user=os.getenv('DB_USER'),
        password=os.getenv('DB_PASS')
    )
    cur = conn.cursor()

    ips = df_final['ip'].tolist()
    cur.execute(
        f"DELETE FROM inventario_ips_completo WHERE ip IN ({','.join(['%s']*len(ips))})",
        ips
    )
    print(f"\nRegistros anteriores eliminados: {cur.rowcount}")

    cols_insert = [c for c in COLS_BD if c in df_final.columns]
    sql = (
        f"INSERT INTO inventario_ips_completo ({', '.join(cols_insert)}) "
        f"VALUES ({', '.join(['%s']*len(cols_insert))})"
    )
    cur.executemany(sql, [tuple(row[c] for c in cols_insert) for _, row in df_final.iterrows()])

    conn.commit()
    print(f"OK: {len(df_final)} filas insertadas en 'inventario_ips_completo'.")
    cur.close()

    # Vincular id_usuario por coincidencia de apellido_paterno
    cur_fk = conn.cursor()
    cur_fk.execute("""
        UPDATE inventario_ips_completo i
        SET id_usuario = (
            SELECT u.id_usuario
            FROM usuarios u
            WHERE i.usuario ILIKE '%%' || u.apellido_paterno || '%%'
            ORDER BY LENGTH(u.apellido_paterno) DESC
            LIMIT 1
        )
        WHERE i.usuario IS NOT NULL
          AND ip = ANY(%s)
    """, (ips,))
    linked = cur_fk.rowcount
    conn.commit()
    cur_fk.close()
    print(f"IPs vinculadas a usuario registrado: {linked} de {len(df_final)}")

    conn.close()

if __name__ == '__main__':
    cargar()
