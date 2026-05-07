from reportlab.lib.pagesizes import A4
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors
from reportlab.lib.units import cm
from datetime import datetime

SALIDA = "Resumen_Cambios_IMJUVE.pdf"

CAMBIOS = [
    {
        "seccion": "1. Configuracion inicial del proyecto",
        "items": [
            ("Archivo .env creado",
             "Se creo el archivo Base-de-Datos/.env con las variables de conexion a PostgreSQL: "
             "DB_HOST, DB_PORT, DB_NAME (sistemitas), DB_USER (postgres) y DB_PASS."),
            ("Archivo .streamlit/config.toml",
             "Se configuro Streamlit para correr en el puerto 80, permitiendo acceder "
             "desde http://localhost/ sin numero de puerto."),
            ("URL personalizada (sistemitas.local)",
             "Se agrego la entrada '127.0.0.1 sistemitas.local' al archivo hosts de Windows "
             "para acceder desde http://sistemitas.local/ en lugar de localhost."),
        ]
    },
    {
        "seccion": "2. Correcciones de compatibilidad",
        "items": [
            ("applymap -> map (pandas 2.x)",
             "Se corrigio el error 'Styler object has no attribute applymap' en app.py linea 734. "
             "En pandas 2.0+ el metodo se llama .map() en lugar de .applymap()."),
        ]
    },
    {
        "seccion": "3. Modulo Direccionamiento IP — nuevas funciones",
        "items": [
            ("Asignar IP",
             "Nueva accion que muestra solo las IPs libres. Permite seleccionar un usuario "
             "registrado o escribir un nombre manualmente, y registrar tipo de equipo, uso "
             "(institucional/personal), MAC, marca, modelo, serie y observaciones. "
             "Cambia el estatus a 'Ocupada' al guardar."),
            ("Editar asignacion",
             "Permite modificar todos los campos de una IP ya ocupada: propietario, "
             "tipo de equipo, uso, MAC, marca, modelo, serie y observaciones."),
            ("Liberar IP",
             "Limpia todos los datos de asignacion de una IP ocupada y la regresa "
             "al estatus 'Libre'."),
            ("Filtros en Asignar / Editar / Liberar",
             "Cada accion tiene dos filtros antes del dropdown: filtro por area/departamento "
             "y busqueda por texto (IP o nombre de usuario), reduciendo la lista para "
             "facilitar la seleccion."),
            ("Filtro de estatus en Ver IPs y Rangos",
             "Se agrego un tercer filtro en el buscador principal con opciones: "
             "Todos / Libre / Ocupada / En Conflicto."),
        ]
    },
    {
        "seccion": "4. Limpieza de datos — duplicados en inventario_ips_completo",
        "items": [
            ("Deteccion de IPs duplicadas",
             "Se ejecuto en pgAdmin la consulta: SELECT ip, COUNT(*) FROM inventario_ips_completo "
             "GROUP BY ip HAVING COUNT(*) > 1. Se detecto la IP 172.17.1.78 duplicada (2 filas)."),
            ("Eliminacion del duplicado",
             "Se elimino el registro duplicado usando el identificador interno ctid de PostgreSQL, "
             "conservando solo una fila por IP."),
            ("Restriccion UNIQUE agregada",
             "Se agrego ALTER TABLE inventario_ips_completo ADD CONSTRAINT uq_ip UNIQUE (ip) "
             "para evitar duplicados futuros y habilitar las operaciones de UPSERT."),
        ]
    },
    {
        "seccion": "5. Script cargar_lista_buena.py",
        "items": [
            ("Carga desde CSV actualizado",
             "Se creo el script Base-de-Datos/cargar_lista_buena.py para sincronizar la base "
             "de datos con el archivo Lista_buena.csv (fuente de verdad mas actualizada). "
             "El archivo contiene 90 registros del departamento SUBDIRECCION DE SISTEMAS, "
             "63 ocupadas y 27 libres, con IPs en el rango 172.17.1.161 - 172.17.1.250."),
            ("Logica de carga",
             "El script detecta automaticamente el nombre del departamento de la primera fila del CSV, "
             "elimina las filas existentes para esas IPs y las reinserta con los datos actualizados. "
             "Maneja el encoding utf-8-sig y omite filas sin IP."),
            ("Uso",
             "Cada vez que se actualice Lista_buena.csv, ejecutar: python cargar_lista_buena.py "
             "desde la carpeta Base-de-Datos."),
        ]
    },
    {
        "seccion": "6. Mejora de filtros en todos los modulos",
        "items": [
            ("Usuarios — Ver usuarios",
             "Se agregaron filtros: Departamento (selectbox), Puesto (texto libre) y "
             "Busqueda general por nombre, apellido o correo. Los tres filtros se aplican "
             "en combinacion en tiempo real."),
            ("Equipos de Computo — Ver equipos",
             "Se elimino el boton toggle de busqueda (que requeria un clic extra). "
             "Ahora el campo de busqueda es siempre visible junto al filtro de estatus "
             "(Todos / activo / danado / en reparacion). El boton de expandir tabla se conservo."),
            ("Telefonos — Ver telefonos",
             "Se agregaron dos campos de busqueda: por extension y por nombre/apellido del usuario."),
            ("Impresoras — Ver impresoras",
             "Se agrego filtro por Marca (selectbox con marcas reales de la BD), "
             "busqueda por IP y busqueda por nombre de usuario."),
            ("Insumos — Ver insumos",
             "Se agrego busqueda por nombre o numero de parte, y filtro de stock: "
             "Todos / Stock bajo o agotado / Stock normal."),
        ]
    },
    {
        "seccion": "7. Dashboard mejorado (pagina de Inicio)",
        "items": [
            ("Fila 1 — metricas principales",
             "Usuarios activos, total de equipos, total de impresoras, "
             "insumos con stock bajo (con indicador rojo cuando hay alguno)."),
            ("Fila 2 — equipos e IPs",
             "Equipos activos, equipos danados/en reparacion (con indicador de alerta), "
             "IPs libres e IPs ocupadas."),
            ("Grafica de barras",
             "Se agrego una grafica interactiva de barras que muestra la cantidad de "
             "usuarios activos por departamento."),
        ]
    },
    {
        "seccion": "8. Exportacion a Excel en modulos faltantes",
        "items": [
            ("Telefonos",
             "Se agrego boton 'Exportar a Excel' en la vista de telefonos con nombre "
             "de archivo con fecha y hora."),
            ("Impresoras",
             "Se agrego boton 'Exportar a Excel' en la vista de impresoras."),
            ("Insumos",
             "Se agrego boton 'Exportar a Excel' en la vista de insumos."),
        ]
    },
    {
        "seccion": "9. Agregar equipo nuevo",
        "items": [
            ("Nueva accion en Equipos de Computo",
             "Se agrego la opcion 'Agregar equipo' al modulo de equipos. El formulario permite "
             "seleccionar el usuario asignado, nombre del equipo, marca, modelo, serie, "
             "MAC address y estatus inicial (activo / danado / en reparacion)."),
        ]
    },
    {
        "seccion": "10. Editar impresora",
        "items": [
            ("Nueva accion en Impresoras",
             "Se agrego la opcion 'Editar impresora' al modulo de impresoras. Permite "
             "seleccionar una impresora existente y modificar: marca, modelo, serie, "
             "IP address y firmware."),
        ]
    },
    {
        "seccion": "11. Baja logica de usuarios (soft delete)",
        "items": [
            ("Columna activo en tabla usuarios",
             "Se agrego la columna 'activo BOOLEAN DEFAULT true' a la tabla usuarios en PostgreSQL "
             "mediante: ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS activo BOOLEAN DEFAULT true."),
            ("Baja de usuario — sin borrado permanente",
             "La accion 'Baja de usuario' ya no elimina el registro de la base de datos. "
             "Ahora establece activo=false, conservando todo el historial del usuario "
             "(equipos, telefonos, etc.)."),
            ("Reactivar usuario",
             "Se agrego la nueva accion 'Reactivar usuario' que lista los usuarios inactivos "
             "y permite restablecer su estado a activo=true."),
            ("Ver usuarios — toggle de inactivos",
             "En 'Ver usuarios' se agrego un checkbox 'Mostrar usuarios inactivos' para "
             "consultar los registros dados de baja sin mezclarlos con los activos."),
        ]
    },
    {
        "seccion": "12. Optimizaciones de rendimiento y UX",
        "items": [
            ("Conexion a BD con cache (@st.cache_resource)",
             "La funcion get_conn() ahora usa @st.cache_resource de Streamlit. La conexion "
             "a PostgreSQL se crea una sola vez y se reutiliza en toda la sesion, eliminando "
             "la latencia de abrir y cerrar conexion en cada render."),
            ("Manejo de errores consistente",
             "Cada modulo y cada pestana esta envuelto en try/except con safe_rollback(). "
             "Si ocurre un error en una operacion, se hace rollback automatico y se muestra "
             "un mensaje claro al usuario sin romper el resto de la aplicacion."),
            ("Busqueda global en pagina de Inicio",
             "Se agrego un campo de busqueda en la pagina de Inicio que consulta "
             "simultaneamente usuarios, IPs, equipos, telefonos, impresoras e insumos. "
             "Muestra los resultados con tipo y modulo de origen para navegacion rapida."),
            ("Validaciones de datos en formularios",
             "Se agregaron tres funciones de validacion: validar_mac() para formato "
             "XX:XX:XX:XX:XX:XX, validar_email() para formato correo@dominio.ext, y "
             "validar_ip_format() para formato X.X.X.X. Se aplican en Alta de usuario, "
             "Agregar/Editar equipo, Agregar/Editar impresora y Asignar/Editar IP."),
            ("Tabs en lugar de radio buttons",
             "Todos los modulos (Usuarios, Equipos, Telefonos, Impresoras, Insumos, "
             "Direccionamiento IP) reemplazaron st.radio() con st.tabs(). Las acciones "
             "ahora son pestanas horizontales, mejorando la navegacion y eliminando "
             "el desplazamiento para encontrar la accion deseada."),
            ("Confirmacion de acciones destructivas",
             "Las acciones 'Baja de usuario' y 'Liberar IP' ahora requieren que el usuario "
             "escriba la palabra CONFIRMAR en un campo de texto antes de que el boton se "
             "habilite. Esto previene borrados accidentales de datos importantes."),
        ]
    },
    {
        "seccion": "13. Nuevo fuente de datos para IPs — Lista_IPS.xlsx",
        "items": [
            ("Cambio de CSV a Excel como fuente de verdad",
             "El script cargar_lista_buena.py fue actualizado para usar Lista_IPS.xlsx "
             "en lugar de Lista_buena.csv. El archivo Excel contiene todos los departamentos "
             "del IMJUVE en pestanas separadas (DG, DBEJ, DIEJ, DCSR, DAJ, DEC, DF, "
             "DRHM, DCS, OIC, SS)."),
            ("Carga completa de todos los departamentos",
             "El script ahora procesa las 11 pestanas de departamentos en una sola ejecucion, "
             "cargando 499 IPs distribuidas en todos los departamentos. Ignora automaticamente "
             "las pestanas RANGO, DATOS y Configuraciones."),
            ("Deteccion automatica de duplicados entre pestanas",
             "El script detecta IPs que aparecen en mas de una pestana del Excel, informa "
             "cuales son y conserva solo la primera aparicion para respetar la restriccion "
             "UNIQUE de la base de datos. Se detecto y manejo la IP 172.17.1.78 duplicada "
             "dentro de la pestana DRHM."),
            ("Uso actualizado",
             "Cada vez que se actualice Lista_IPS.xlsx (cualquier departamento), ejecutar: "
             "python cargar_lista_buena.py desde la carpeta Base-de-Datos. "
             "El script elimina los registros anteriores de esas IPs y los reinserta actualizados."),
        ]
    },
]

def build_pdf():
    doc = SimpleDocTemplate(
        SALIDA, pagesize=A4,
        leftMargin=2*cm, rightMargin=2*cm,
        topMargin=2*cm, bottomMargin=2*cm
    )
    styles = getSampleStyleSheet()

    titulo_style = ParagraphStyle("titulo", parent=styles["Title"],
                                  fontSize=18, textColor=colors.HexColor("#1a3c5e"),
                                  spaceAfter=6)
    subtitulo_style = ParagraphStyle("sub", parent=styles["Normal"],
                                     fontSize=10, textColor=colors.grey, spaceAfter=16)
    seccion_style = ParagraphStyle("seccion", parent=styles["Heading2"],
                                   fontSize=12, textColor=colors.HexColor("#1a3c5e"),
                                   spaceBefore=14, spaceAfter=6,
                                   borderPad=4)
    item_titulo_style = ParagraphStyle("item_tit", parent=styles["Normal"],
                                       fontSize=10, fontName="Helvetica-Bold",
                                       textColor=colors.HexColor("#333333"), spaceAfter=2)
    item_desc_style = ParagraphStyle("item_desc", parent=styles["Normal"],
                                     fontSize=9, textColor=colors.HexColor("#555555"),
                                     spaceAfter=8, leftIndent=10)

    elements = []

    elements.append(Paragraph("Sistema de Inventario IMJUVE", titulo_style))
    elements.append(Paragraph(
        f"Resumen de cambios y mejoras realizadas &nbsp;&nbsp;|&nbsp;&nbsp; "
        f"Generado: {datetime.now().strftime('%d/%m/%Y %H:%M')}",
        subtitulo_style
    ))
    elements.append(HRFlowable(width="100%", thickness=2,
                                color=colors.HexColor("#1a3c5e"), spaceAfter=16))

    total_items = sum(len(c["items"]) for c in CAMBIOS)
    resumen_data = [
        ["Secciones modificadas", str(len(CAMBIOS))],
        ["Total de mejoras aplicadas", str(total_items)],
        ["Fecha de aplicacion", datetime.now().strftime("%d/%m/%Y")],
        ["Archivo principal", "Base-de-Datos/app.py"],
        ["Scripts nuevos", "cargar_lista_buena.py  |  generar_resumen.py"],
        ["Base de datos", "PostgreSQL 18  —  sistemitas"],
    ]
    tabla_res = Table(resumen_data, colWidths=[6*cm, 10*cm])
    tabla_res.setStyle(TableStyle([
        ("BACKGROUND",   (0, 0), (0, -1), colors.HexColor("#eaf1fb")),
        ("FONTNAME",     (0, 0), (0, -1), "Helvetica-Bold"),
        ("FONTSIZE",     (0, 0), (-1, -1), 9),
        ("GRID",         (0, 0), (-1, -1), 0.4, colors.grey),
        ("VALIGN",       (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING",   (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING",(0, 0), (-1, -1), 5),
    ]))
    elements.append(tabla_res)
    elements.append(Spacer(1, 20))

    for cambio in CAMBIOS:
        elements.append(Paragraph(cambio["seccion"], seccion_style))
        elements.append(HRFlowable(width="100%", thickness=0.5,
                                    color=colors.HexColor("#1a3c5e"), spaceAfter=6))
        for titulo, desc in cambio["items"]:
            elements.append(Paragraph(f"• {titulo}", item_titulo_style))
            elements.append(Paragraph(desc, item_desc_style))

    elements.append(Spacer(1, 20))
    elements.append(HRFlowable(width="100%", thickness=1, color=colors.grey))
    elements.append(Spacer(1, 6))
    elements.append(Paragraph(
        f"Documento generado automaticamente — Sistema IMJUVE — {datetime.now().strftime('%d/%m/%Y')}",
        ParagraphStyle("pie", parent=styles["Normal"], fontSize=8,
                       textColor=colors.grey, alignment=1)
    ))

    doc.build(elements)
    print(f"PDF generado: {SALIDA}")

if __name__ == "__main__":
    build_pdf()
