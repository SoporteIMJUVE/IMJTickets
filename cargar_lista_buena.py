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

        if 'estatus' in df.columns:
            df['estatus'] = df['estatus'].astype(str).str.strip()
        if 'institucional_o_personal' in df.columns:
            df['institucional_o_personal'] = df['institucional_o_personal'].astype(str).str.strip()

        cols_presentes = [c for c in COLS_BD if c in df.columns]
        todos.append(df[cols_presentes])

        ocup  = (df.get('estatus', pd.Series()) == 'Ocupada').sum()
        libre = (df.get('estatus', pd.Series()) == 'Libre').sum()
        print(f"  {nombre_depto}: {len(df)} IPs  (Ocupadas: {ocup}, Libres: {libre})")

    if not todos:
        print("Sin datos para cargar.")
        return

    df_final = pd.concat(todos, ignore_index=True)

    # Detectar y eliminar IPs duplicadas entre pestañas (conservar la primera aparición)
    dups = df_final[df_final.duplicated('ip', keep=False)][['ip','departamento_pestana']]
    if not dups.empty:
        print(f"\nADVERTENCIA: IPs duplicadas entre pestañas (se conserva la primera):")
        print(dups.to_string(index=False))
    df_final = df_final.drop_duplicates(subset='ip', keep='first').reset_index(drop=True)

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
    df_insert = df_final[cols_insert].where(pd.notnull(df_final[cols_insert]), None)
    sql = (
        f"INSERT INTO inventario_ips_completo ({', '.join(cols_insert)}) "
        f"VALUES ({', '.join(['%s']*len(cols_insert))})"
    )
    cur.executemany(sql, [tuple(r) for r in df_insert.itertuples(index=False)])

    conn.commit()
    print(f"OK: {len(df_final)} filas insertadas en 'inventario_ips_completo'.")
    cur.close()
    conn.close()

if __name__ == '__main__':
    cargar()
