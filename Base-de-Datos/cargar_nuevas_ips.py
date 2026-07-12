import pandas as pd
import psycopg2
import os
from dotenv import load_dotenv

load_dotenv()

CSV_PATH = 'NUEVAS IP.csv'
DEPTO = 'SUBDIRECCIÓN DE SISTEMAS'

COL_MAP = {
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

VACIOS = {'nan', 'none', 'null', 'n/a', '-', '/', ''}

COLS_BD = [
    'ip', 'usuario', 'tipo_equipo', 'institucional_o_personal', 'marca', 'modelo',
    'serie', 'mac', 'tipo_conexion', 'config_red', 'area_excel', 'departamento_pestana',
    'restricciones', 'youtube', 'vimeo', 'spotify', 'otros_streaming', 'facebook',
    'tiktok', 'instagram', 'whatsapp_web', 'otra_red_social', 'sitios_gub', 'noticias',
    'otro_permiso', 'estatus', 'observaciones',
]

def limpiar(v):
    if v is None:
        return None
    if isinstance(v, float) and pd.isna(v):
        return None
    s = str(v).strip()
    return None if s.lower() in VACIOS else s

def cargar():
    print(f"Leyendo: {CSV_PATH}")
    df = pd.read_csv(CSV_PATH, encoding='latin1', header=None, skiprows=1,
                     usecols=list(range(27)), low_memory=False)
    df.columns = df.iloc[0].values
    df = df.iloc[1:].reset_index(drop=True)
    df = df[df['IP'].astype(str).str.match(r'^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$', na=False)].copy()
    df = df.rename(columns=COL_MAP)
    df['departamento_pestana'] = DEPTO

    cols_presentes = [c for c in COLS_BD if c in df.columns]
    df = df[cols_presentes].copy()
    for col in df.columns:
        if col != 'ip':
            df[col] = df[col].apply(limpiar)

    print(f"IPs encontradas en CSV: {len(df)}")

    conn = psycopg2.connect(
        host=os.getenv('DB_HOST'),
        port=os.getenv('DB_PORT', '5432'),
        database=os.getenv('DB_NAME'),
        user=os.getenv('DB_USER'),
        password=os.getenv('DB_PASS')
    )
    cur = conn.cursor()

    cur.execute("DELETE FROM inventario_ips_completo WHERE departamento_pestana ILIKE %s", (DEPTO,))
    print(f"Registros anteriores eliminados: {cur.rowcount}")

    sql = (
        f"INSERT INTO inventario_ips_completo ({', '.join(cols_presentes)}) "
        f"VALUES ({', '.join(['%s'] * len(cols_presentes))})"
    )
    cur.executemany(sql, [tuple(row[c] for c in cols_presentes) for _, row in df.iterrows()])

    conn.commit()
    ocup = (df['estatus'] == 'Ocupada').sum() if 'estatus' in df.columns else 0
    libre = (df['estatus'] == 'Libre').sum() if 'estatus' in df.columns else 0
    print(f"OK: {len(df)} IPs insertadas  (Ocupadas: {ocup} | Libres: {libre})")
    cur.close()
    conn.close()

if __name__ == '__main__':
    cargar()
