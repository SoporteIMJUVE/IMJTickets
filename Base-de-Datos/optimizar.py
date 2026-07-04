import pandas as pd
import os
import glob

# 1. LOCALIZAR EL ARCHIVO
carpeta = r"C:\optimizacion_py"
archivos = glob.glob(os.path.join(carpeta, "*.xlsx"))

if not archivos:
    print("❌ No se encontró el archivo .xlsx")
else:
    ruta = archivos[0]
    print(f"📂 Analizando: {os.path.basename(ruta)}")

    try:
        # 2. ESCANEO PARA ENCONTRAR LA FILA CORRECTA
        # Cargamos el archivo completo sin saltar filas para buscar la cabecera
        df_raw = pd.read_excel(ruta, header=None)
        
        fila_cabecera = None
        for i, row in df_raw.iterrows():
            # Buscamos la fila que contenga la palabra 'USUARIOS'
            if row.astype(str).str.contains('USUARIOS', case=False).any():
                fila_cabecera = i
                break
        
        if fila_cabecera is None:
            print("❌ No logré encontrar la fila que dice 'USUARIOS'.")
        else:
            print(f"✅ ¡Encabezados encontrados en la fila {fila_cabecera + 1}!")
            
            # 3. CARGAR DATOS REALES
            df = pd.read_excel(ruta, skiprows=fila_cabecera)
            
            # Limpiar nombres de columnas (quitar espacios y saltos de línea)
            df.columns = df.columns.astype(str).str.strip().str.replace('\n', ' ')
            
            # 4. IDENTIFICAR COLUMNAS POR PALABRA CLAVE
            col_usuarios = [c for c in df.columns if 'USUARIO' in c.upper()][0]
            col_depto = [c for c in df.columns if 'DEP' in c.upper()][0]
            col_correo = [c for c in df.columns if 'CORREO' in c.upper()][0]

            # 5. LIMPIEZA Y SEPARACIÓN DE NOMBRES
            df_limpio = df[[col_usuarios, col_depto, col_correo]].copy()
            df_limpio = df_limpio.dropna(subset=[col_usuarios])
            
            # Quitar filas que repiten el título
            df_limpio = df_limpio[df_limpio[col_usuarios].astype(str).str.upper() != 'USUARIOS']

            def separar_nombres(completo):
                partes = str(completo).split()
                if len(partes) >= 3:
                    # Formato: ApellidoP ApellidoM Nombre(s)
                    return pd.Series([" ".join(partes[2:]), f"{partes[0]} {partes[1]}"])
                return pd.Series([completo, ""])

            df_limpio[['nombre', 'apellido']] = df_limpio[col_usuarios].apply(separar_nombres)

            # 6. GUARDAR PARA PGADMIN
            ruta_salida = os.path.join(carpeta, "usuarios_final_sql.csv")
            final = df_limpio[['nombre', 'apellido', col_depto, col_correo]]
            final.columns = ['nombre', 'apellido', 'departamento', 'correo']
            
            final.to_csv(ruta_salida, index=False, encoding='utf-8-sig')

            print(f"🚀 ¡ÉXITO! Se generó el archivo con {len(final)} registros.")
            print(f"📍 Ubicación: {ruta_salida}")

    except Exception as e:
        print(f"❌ Error detallado: {e}")