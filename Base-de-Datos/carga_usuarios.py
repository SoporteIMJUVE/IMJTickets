import pandas as pd
from sqlalchemy import create_engine, text
from rapidfuzz import process, fuzz

engine = create_engine('postgresql://postgres:Dan040904@localhost:5432/sistemitas')
ruta_excel = r"C:\py\INVENTARIO FISICO HASTA enero 2026.xlsx"

def carga_desde_todas_las_hojas():
    # 1. Definimos las hojas que queremos leer
    hojas = ['laptop', 'PC ESPECIALIZADAS', 'PC AVANZADAS']
    
    with engine.connect() as conn:
        # Limpiamos para empezar de 0
        conn.execute(text("TRUNCATE TABLE usuarios RESTART IDENTITY CASCADE;"))
        
        # Traemos el catálogo de áreas que ya insertaste en SQL
        res = conn.execute(text("SELECT id_departamento, nombre FROM departamentos"))
        catalogo = {row[1]: row[0] for row in res}
        nombres_db = list(catalogo.keys())

        total_insertados = 0

        for hoja in hojas:
            print(f"Reading sheet: {hoja}...")
            # Leemos la hoja (usamos skiprows=2 porque tus archivos tienen títulos arriba)
            df = pd.read_excel(ruta_excel, sheet_name=hoja, skiprows=2)
            
            for _, fila in df.iterrows():
                # En tus nuevos archivos la columna se llama 'NOMBRE DE USUARIO'
                nombre_completo = str(fila.get('NOMBRE DE USUARIO', '')).strip()
                area_ex = str(fila.get('ÁREA', '')).strip()

                if nombre_completo == 'nan' or nombre_completo == "" or "CONTRATO" in nombre_completo:
                    continue

                # Separar nombre de apellido de forma básica para tu tabla
                partes = nombre_completo.split(' ')
                nombre = partes[0]
                apellido = " ".join(partes[1:]) if len(partes) > 1 else ""

                # Match de Área
                match = process.extractOne(area_ex, nombres_db, scorer=fuzz.token_set_ratio)
                id_dep = catalogo[match[0]] if match and match[1] > 40 else 1

                conn.execute(
                    text("INSERT INTO usuarios (nombre, apellido_paterno, id_departamento) VALUES (:n, :ap, :id_d)"),
                    {"n": nombre, "ap": apellido, "id_d": id_dep}
                )
                total_insertados += 1
        
        conn.commit()
        print(f"✅ ¡TERMINADO! Se insertaron {total_insertados} usuarios en total de las 3 hojas.")

if __name__ == "__main__":
    carga_desde_todas_las_hojas()