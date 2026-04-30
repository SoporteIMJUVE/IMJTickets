import pandas as pd
import psycopg2
import os
from dotenv import load_dotenv

load_dotenv()

CSV = 'Lista_buena.csv'

COLUMNAS = {
    'IP':                              'ip',
    'Usuario o nombre del propietario':'usuario',
    'Tipo de equipo':                  'tipo_equipo',
    'Institucional o Personal':        'institucional_o_personal',
    'Marca':                           'marca',
    'modelo':                          'modelo',
    'Número de Serie':                 'serie',
    'MAC':                             'mac',
    'Tipo de Conexión':                'tipo_conexion',
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

def cargar():
    # Leer departamento desde la primera fila del CSV
    with open(CSV, encoding='utf-8-sig') as f:
        depto = f.readline().split(';')[0].strip().upper()
    print(f"Departamento detectado: {depto.encode('ascii', 'replace').decode()}")

    # Leer CSV (solo las primeras 27 columnas, ignorar las vacías de la derecha)
    df = pd.read_csv(CSV, sep=';', skiprows=1, header=0, encoding='utf-8-sig', low_memory=False)
    df = df.iloc[:, :27]
    df.columns = [str(c).strip().replace('\n', '') for c in df.columns]
    df = df.rename(columns=COLUMNAS)
    df = df.dropna(subset=['ip'])
    df['ip'] = df['ip'].astype(str).str.strip()
    df['departamento_pestana'] = depto
    if 'estatus' in df.columns:
        df['estatus'] = df['estatus'].astype(str).str.strip()

    print(f"Filas a cargar: {len(df)}  |  Ocupadas: {(df['estatus'] == 'Ocupada').sum()}  |  Libres: {(df['estatus'] == 'Libre').sum()}")

    conn = psycopg2.connect(
        host=os.getenv('DB_HOST'),
        port=os.getenv('DB_PORT', '5432'),
        database=os.getenv('DB_NAME'),
        user=os.getenv('DB_USER'),
        password=os.getenv('DB_PASS')
    )
    cur = conn.cursor()

    # Eliminar filas existentes para estas IPs (evita duplicados)
    ips = df['ip'].tolist()
    cur.execute(f"DELETE FROM inventario_ips_completo WHERE ip IN ({','.join(['%s']*len(ips))})", ips)
    print(f"Filas anteriores eliminadas: {cur.rowcount}")

    # Insertar todas las filas del CSV
    cols_bd = ['ip', 'usuario', 'tipo_equipo', 'institucional_o_personal', 'marca', 'modelo',
               'serie', 'mac', 'tipo_conexion', 'config_red', 'area_excel', 'departamento_pestana',
               'restricciones', 'youtube', 'vimeo', 'spotify', 'otros_streaming', 'facebook',
               'tiktok', 'instagram', 'whatsapp_web', 'otra_red_social', 'sitios_gub', 'noticias',
               'otro_permiso', 'estatus', 'observaciones']

    cols_presentes = [c for c in cols_bd if c in df.columns]
    df_final = df[cols_presentes].where(pd.notnull(df[cols_presentes]), None)

    sql = f"INSERT INTO inventario_ips_completo ({', '.join(cols_presentes)}) VALUES ({', '.join(['%s']*len(cols_presentes))})"
    cur.executemany(sql, [tuple(r) for r in df_final.itertuples(index=False)])

    conn.commit()
    print(f"OK: {len(df_final)} filas insertadas correctamente en 'inventario_ips_completo'.")
    cur.close()
    conn.close()

if __name__ == '__main__':
    cargar()
