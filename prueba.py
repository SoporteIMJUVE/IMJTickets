import pandas as pd
from sqlalchemy import create_engine

# Conexión
engine = create_engine('postgresql://postgres:Pistache07@127.0.0.1:5432/Sistemitas')

def cargar_inventario_limpio():
    try:
        # 1. Cargar Equipos (Computo)
        # Asegúrate de que la ruta sea la de tu Excel de inventario
        df_equipos = pd.read_excel(r"C:\py\inventario_equipos.xlsx") 
        df_equipos.to_sql('computo', engine, if_exists='append', index=False)
        print("✅ Equipos cargados en tabla 'computo'")

        # 2. Cargar Accesorios
        df_accesorios = pd.read_excel(r"C:\py\inventario_accesorios.xlsx")
        df_accesorios.to_sql('accesorio', engine, if_exists='append', index=False)
        print("✅ Accesorios cargados en tabla 'accesorio'")

    except Exception as e:
        print(f"❌ Error: {e}")

# cargar_inventario_limpio() # Descomenta para correr