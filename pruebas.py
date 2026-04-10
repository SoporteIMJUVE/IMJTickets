import pandas as pd
from sqlalchemy import create_engine, text

engine = create_engine('postgresql://postgres:Pistache07@127.0.0.1:5432/Sistemitas')
ruta_maestra = r"C:\py\INVENTARIO FISICO HASTA enero 2026.xlsx"

def vinculo_final_2026():
    try:
        # 1. Mapa de usuarios de la DB
        usuarios_db = pd.read_sql("SELECT empleadoid, nombre, apellidop FROM usuario", engine)
        usuarios_db['nombre_full'] = (usuarios_db['nombre'].fillna('') + " " + usuarios_db['apellidop'].fillna('')).str.upper().str.strip()
        
        # 2. Series huérfanas
        acc_db = pd.read_sql("SELECT numeroserie FROM accesorio WHERE empleadoid IS NULL", engine)
        series_faltantes = acc_db['numeroserie'].dropna().unique().tolist()

        print(f"--- Iniciando barrido de {len(series_faltantes)} series ---")
        total_vinculados = 0

        for hoja in ['laptop', 'PC ESPECIALIZADAS', 'PC AVANZADAS']:
            print(f"Escaneando hoja: {hoja}...")
            df = pd.read_excel(ruta_maestra, sheet_name=hoja)
            
            # Usamos .map para versiones nuevas de Pandas (reemplaza al viejo applymap)
            df_limpio = df.map(lambda x: str(x).replace('-', '').replace(' ', '').upper().strip() if pd.notnull(x) else "")

            with engine.begin() as conn:
                for serie_orig in series_faltantes:
                    serie_busqueda = str(serie_orig).replace('-', '').replace(' ', '').upper().strip()
                    
                    # Buscamos la serie en la fila
                    coincidencias = (df_limpio == serie_busqueda).any(axis=1)
                    
                    if coincidencias.any():
                        fila_index = coincidencias.idxmax()
                        fila_datos_original = df.iloc[fila_index]
                        
                        u_id = None
                        nombre_detectado = "Desconocido"
                        
                        # Buscamos el nombre en la fila original
                        for celda in fila_datos_original:
                            val_celda = str(celda).upper().strip()
                            if len(val_celda) < 5: continue 
                            
                            for _, u in usuarios_db.iterrows():
                                if u['nombre_full'] in val_celda or val_celda in u['nombre_full']:
                                    u_id = u['empleadoid']
                                    nombre_detectado = val_celda
                                    break
                            if u_id: break
                        
                        if u_id:
                            res = conn.execute(
                                text("UPDATE accesorio SET empleadoid = :uid WHERE numeroserie = :s AND empleadoid IS NULL"),
                                {"uid": u_id, "s": serie_orig}
                            )
                            if res.rowcount > 0:
                                total_vinculados += 1
                                print(f"✅ Vinculado: {serie_orig} -> {nombre_detectado}")

        print(f"\n🏆 ¡SISTEMA COMPLETADO! Se lograron vincular {total_vinculados} accesorios.")

    except Exception as e:
        print(f"❌ Error detallado: {e}")

vinculo_final_2026()