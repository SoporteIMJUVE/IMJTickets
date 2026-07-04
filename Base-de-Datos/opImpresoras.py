import pandas as pd
import os

# 1. Configuración de rutas
ruta = r'C:\optimizacion_py'
archivo_in = os.path.join(ruta, 'SOLICITUDES TONER Y STOCK.xlsx')
archivo_out = os.path.join(ruta, 'impresoras_listas.csv')

try:
    # 2. Leer el Excel directamente (Hoja2)
    # Saltamos la primera fila de "IMPRESORAS" para llegar a los encabezados
    df = pd.read_excel(archivo_in, sheet_name='Hoja2', skiprows=1)

    # 3. EXTRACCIÓN QUIRÚRGICA (Saltando las columnas vacías)
    # Columna 1: Área y Ubicación
    # Columna 6: Responsable
    # Columna 7: Marca
    # Columna 8: Modelo
    # Columna 9: Firmware
    # Columna 10: Número de Serie
    # Columna 11: IP
    
    # Seleccionamos las columnas por su posición numérica
    df_final = df.iloc[:, [1, 1, 6, 7, 8, 9, 10, 11]].copy()

    # 4. Asignar nombres oficiales para SQL
    df_final.columns = ['area', 'ubicacion', 'encargado', 'marca', 'modelo', 'firmware', 'serie', 'ip']

    # 5. Limpieza: Quitar filas donde no haya serie (como los espacios en blanco abajo)
    df_final = df_final.dropna(subset=['serie'])
    
    # Quitar espacios en blanco de los textos y asegurar que todo sea string
    df_final = df_final.apply(lambda x: x.astype(str).str.strip() if x.dtype == "object" else x)

    # 6. Guardar el CSV final
    df_final.to_csv(archivo_out, index=False, encoding='utf-8-sig')

    print("-" * 30)
    print(f"✅ ¡PROCESO EXITOSO!")
    print(f"Se extrajeron {len(df_final)} impresoras.")
    print(f"Archivo listo en: {archivo_out}")
    print("-" * 30)

except Exception as e:
    print(f"❌ Error inesperado: {e}")