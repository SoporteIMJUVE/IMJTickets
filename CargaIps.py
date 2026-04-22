import pandas as pd
from sqlalchemy import create_engine

# --- CONFIGURACIÓN ---
nombre_archivo = 'Inventario IPS.xlsx'
# Ya puse tu contraseña Pistache07 y el nombre de la base de datos InventarioIps
url_db = 'postgresql://postgres:Pistache07@localhost:5432/Sistemitas'
engine = create_engine(url_db)

def cargar_datos():
    print("🚀 Iniciando carga masiva...")
    
    # Leer todas las pestañas excepto las que no son departamentos
    excel = pd.ExcelFile(nombre_archivo)
    pestanas_a_omitir = ['RANGO', 'DATOS', 'Configuraciones ', 'SS']
    departamentos = [s for s in excel.sheet_names if s not in pestanas_a_omitir]
    
    for depto in departamentos:
        print(f"📦 Procesando área: {depto}...")
        # Leer hoja, saltando la primera fila que es el título largo
        df = pd.read_excel(nombre_archivo, sheet_name=depto, skiprows=1)
        
        # Limpiar nombres de columnas para que coincidan con SQL
        # Agregué las columnas que faltaban según tu archivo real
        df = df.rename(columns={
            'IP': 'ip',
            'Usuario o nombre del propietario': 'usuario',
            'Tipo de \nequipo': 'tipo_equipo',
            'Institucional o \nPersonal': 'propiedad',
            'Marca': 'marca',
            'modelo': 'modelo',
            'Número de \nSerie': 'serie',
            'MAC': 'mac',
            'Tipo de \nConexión': 'tipo_conexion',
            'Confguración de red': 'config_red',
            'Área': 'area_excel',           
            'Restricciones': 'restricciones',
            'YOUTUBE': 'youtube',
            'VIMEO': 'vimeo',
            'SPOTIFY': 'spotify',
            'OTRO STREAMING': 'otros_streaming',
            'FACEBOOK': 'facebook',
            'TIK TOK': 'tiktok',
            'INSTAGRAM': 'instagram',
            'WHATSAPP WEB': 'whatsapp_web',
            'OTRA RED SOCIAL': 'otra_red_social', # <--- NUEVA
            'SITIOS GUBERNAMENTALES': 'sitios_gub', # <--- NUEVA
            'NOTICIAS': 'noticias',                 # <--- NUEVA
            'OTRO': 'otro_permiso',                 # <--- NUEVA
            'Estatus': 'estatus',
            'OBSERVACIONES': 'observaciones'
        })
        
        # El nombre de la pestaña lo guardamos en departamento_pestana
        df['departamento_pestana'] = depto 
        
        # Seleccionamos las columnas EXACTAS de tu nueva tabla en pgAdmin
        columnas_finales = [
            'ip', 'usuario', 'tipo_equipo', 'propiedad', 'marca', 'modelo', 
            'serie', 'mac', 'tipo_conexion', 'config_red', 'area_excel', 
            'departamento_pestana', 'restricciones', 'youtube', 'vimeo', 
            'spotify', 'otros_streaming', 'facebook', 'tiktok', 'instagram', 
            'whatsapp_web', 'otra_red_social', 'sitios_gub', 'noticias', 
            'otro_permiso', 'estatus', 'observaciones'
        ]
        
        # Solo tomamos las columnas que existen en el DataFrame para evitar errores
        columnas_existentes = [c for c in columnas_finales if c in df.columns]
        df_final = df[columnas_existentes].dropna(subset=['ip']) 
        
        # Subir a Postgres
        df_final.to_sql('inventario_ips_completo', engine, if_exists='append', index=False)
    
    print("✅ ¡Carga masiva completada con éxito!")

if __name__ == "__main__":
    cargar_datos()