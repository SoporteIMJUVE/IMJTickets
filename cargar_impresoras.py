import pandas as pd
from sqlalchemy import create_engine, text
from datetime import datetime

engine = create_engine('postgresql://postgres:Dan040904@localhost:5432/sistemitas')
ruta_excel = r"C:\py\SOLICITUDES TONER Y STOCK.xlsx"

def cargar_historial_suministros():
    # Leemos la hoja completa. Buscaremos la sección de SUMINISTROS manualmente
    df_raw = pd.read_excel(ruta_excel, sheet_name='Hoja2', header=None)
    
    with engine.connect() as conn:
        print("📊 Procesando historial de suministros...")
        
        # 1. Obtener catálogos para cruzar datos
        res_insumos = conn.execute(text("SELECT id_insumo, numero_parte FROM insumos")).fetchall()
        insumos_db = {row[1]: row[0] for row in res_insumos}
        
        res_deptos = conn.execute(text("SELECT id_departamento, nombre FROM departamentos")).fetchall()
        deptos_db = {row[1].upper(): row[0] for row in res_deptos}

        # 2. Localizar la fila donde empiezan los suministros
        # En tu archivo parece estar alrededor de la fila 20-25
        inicio_suministros = 0
        for i, row in df_raw.iterrows():
            if "SUMINISTROS" in str(row[0]).upper():
                inicio_suministros = i + 2 # Saltamos el título y el encabezado
                break

        if inicio_suministros == 0:
            print("❌ No se encontró la sección de SUMINISTROS.")
            return

        # 3. Procesar las filas de suministros
        # Usamos un rango para las 3 solicitudes que tiene tu Excel
        for i in range(inicio_suministros, len(df_raw)):
            fila = df_raw.iloc[i]
            area_raw = str(fila[1]).upper() # Columna 'ÁREA Y UBICACIÓN'
            n_parte = str(fila[6]).strip()  # Columna 'N° DE PARTE'
            
            if n_parte not in insumos_db: continue

            # Mapeo de columnas para las 3 solicitudes según tu Excel:
            # Solicitud 1: Req(7), Ent(9), Est(11), Fecha: 2025-08-10
            # Solicitud 2: Req(17), Ent(19), Est(21), Fecha: 2025-09-11
            # Solicitud 3: Req(24), Ent(26), Est(28), Fecha: 2025-10-23
            
            mapeo_solicitudes = [
                (7, 9, 11, "2025-08-10"),
                (17, 19, 21, "2025-09-11"),
                (24, 26, 28, "2025-10-23")
            ]

            id_dep = deptos_db.get(area_raw, 1) # 1 = Sistemas por defecto si no encuentra el área

            for col_req, col_ent, col_est, fecha in mapeo_solicitudes:
                req = fila[col_req]
                ent = fila[col_ent]
                est = str(fila[col_est])

                # Solo insertamos si hubo una solicitud real (cantidad > 0)
                if pd.notnull(req) and str(req).isdigit() and int(req) > 0:
                    conn.execute(
                        text("""INSERT INTO suministros (id_insumo, id_departamento, fecha_solicitud, 
                                cantidad_requerida, cantidad_entregada, estatus)
                                VALUES (:ins, :dep, :f, :req, :ent, :est)"""),
                        {
                            "ins": insumos_db[n_parte],
                            "dep": id_dep,
                            "f": fecha,
                            "req": int(req),
                            "ent": int(ent) if pd.notnull(ent) and str(ent).isdigit() else 0,
                            "est": est if est != 'nan' else '-'
                        }
                    )
        
        conn.commit()
    print("✅ Historial de suministros cargado con éxito.")

if __name__ == "__main__":
    cargar_historial_suministros()