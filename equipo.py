import pandas as pd
from sqlalchemy import create_engine, text

engine = create_engine('postgresql://postgres:Dan040904@localhost:5432/sistemitas')
ruta_excel = r"C:\py\INVENTARIO FISICO HASTA enero 2026.xlsx"
hojas = ["laptop", "PC ESPECIALIZADAS", "PC AVANZADAS"]

def revincular():
    # 1. Obtener todos los usuarios de la DB para comparar
    with engine.connect() as conn:
        query = text("SELECT empleadoid, UPPER(CONCAT_WS(' ', nombre, apellidop, apellidom)) as completo FROM usuario")
        df_users = pd.read_sql(query, conn)
        # Limpiar espacios dobles que a veces se cuelan
        df_users['completo'] = df_users['completo'].str.replace('  ', ' ').str.strip()
        dict_usuarios = dict(zip(df_users['completo'], df_users['empleadoid']))

    print("--- INICIANDO RE-VINCULACIÓN DE EQUIPOS ---")
    
    for hoja in hojas:
        skip = 3 if hoja != "PC ESPECIALIZADAS" else 2
        df = pd.read_excel(ruta_excel, sheet_name=hoja, skiprows=skip)
        
        # En todas tus hojas, el nombre de usuario está en la columna D (índice 3)
        # y la serie del equipo en la columna I (índice 8)
        actualizados = 0
        for _, fila in df.iterrows():
            nombre_excel = str(fila.iloc[3]).upper().replace('  ', ' ').strip()
            serie_equipo = str(fila.iloc[8]).strip()
            
            if nombre_excel in dict_usuarios:
                user_id = dict_usuarios[nombre_excel]
                with engine.connect() as conn:
                    conn.execute(
                        text("UPDATE computo SET empleadoid = :uid WHERE numeroserie = :serie"),
                        {"uid": user_id, "serie": serie_equipo}
                    )
                    conn.commit()
                actualizados += 1
        
        print(f"✅ Hoja {hoja}: Se vincularon {actualizados} equipos a sus dueños.")

revincular()