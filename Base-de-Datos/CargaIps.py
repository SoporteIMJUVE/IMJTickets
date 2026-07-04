import pandas as pd
from sqlalchemy import create_engine

# --- CONFIGURACIÓN ---
nombre_archivo = 'Inventario IPS.xlsx'
# Asegúrate de que la URL de tu base de datos sea correcta
url_db = 'postgresql://postgres:Pistache07@localhost:5432/Sistemitas'
engine = create_engine(url_db)

def cargar_datos():
    print("🚀 Iniciando carga masiva...")
    
    try:
        # Leer todas las pestañas excepto las que no son departamentos
        excel = pd.ExcelFile(nombre_archivo)
        pestanas_a_omitir = ['RANGO', 'DATOS', 'Configuraciones ']
        departamentos = [s for s in excel.sheet_names if s not in pestanas_a_omitir]
        
        for depto in departamentos:
            print(f"📦 Procesando área: {depto}...")
            # Leer hoja, saltando la primera fila (título largo)
            df = pd.read_excel(nombre_archivo, sheet_name=depto, skiprows=1)
            
            # --- RENOMBRADO DE COLUMNAS (Mapeo Excel -> SQL) ---
            df = df.rename(columns={
                'IP': 'ip',
                'Usuario o nombre del propietario': 'usuario',
                'Tipo de \nequipo': 'tipo_equipo',
                'Institucional o \nPersonal': 'institucional_o_personal', # <-- CAMBIO CLAVE
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
                'OTRA RED SOCIAL': 'otra_red_social',
                'SITIOS GUBERNAMENTALES': 'sitios_gub',
                'NOTICIAS': 'noticias',
                'OTRO': 'otro_permiso',
                'Estatus': 'estatus',
                'OBSERVACIONES': 'observaciones'
            })
            
            # Guardamos el nombre de la pestaña para el resumen
            df['departamento_pestana'] = depto 
            
            # Definimos todas las columnas que deben existir en tu tabla de pgAdmin
            columnas_finales = [
                'ip', 'usuario', 'tipo_equipo', 'institucional_o_personal', 'marca', 'modelo', 
                'serie', 'mac', 'tipo_conexion', 'config_red', 'area_excel', 
                'departamento_pestana', 'restricciones', 'youtube', 'vimeo', 
                'spotify', 'otros_streaming', 'facebook', 'tiktok', 'instagram', 
                'whatsapp_web', 'otra_red_social', 'sitios_gub', 'noticias', 
                'otro_permiso', 'estatus', 'observaciones'
            ]
            
            # Filtramos para usar solo las columnas que el script encontró en el Excel
            columnas_existentes = [c for c in columnas_finales if c in df.columns]
            df_final = df[columnas_existentes].dropna(subset=['ip']).copy()

            # --- LIMPIEZA DE DATOS ---
            # 1. Deptos a mayúsculas para que el JOIN con el catálogo no falle
            df_final['departamento_pestana'] = df_final['departamento_pestana'].str.upper().str.strip()
            
            # 2. Limpiar espacios en estatus y propiedad
            if 'estatus' in df_final.columns:
                df_final['estatus'] = df_final['estatus'].astype(str).str.strip()
            
            if 'institucional_o_personal' in df_final.columns:
                df_final['institucional_o_personal'] = df_final['institucional_o_personal'].astype(str).str.strip()

            # --- CARGA A POSTGRES ---
            df_final.to_sql('inventario_ips_completo', engine, if_exists='append', index=False)
        
        print("\n✅ ¡Carga masiva completada con éxito!")
        print("Recuerda correr los UPDATE en pgAdmin si los nombres de las áreas son siglas (DG, SS, etc.)")

    except Exception as e:
        print(f"❌ Error durante la carga: {e}")

if __name__ == "__main__":
    cargar_datos()