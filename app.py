# -*- coding: utf-8 -*-
import os
from dotenv import load_dotenv
import streamlit as st
import psycopg2
import pandas as pd
from io import BytesIO
from datetime import datetime

load_dotenv()

def get_conn():
    return psycopg2.connect(
        host=os.getenv("DB_HOST"),
        port=os.getenv("DB_PORT", "5432"),
        database=os.getenv("DB_NAME"),
        user=os.getenv("DB_USER"),
        password=os.getenv("DB_PASS")
    )
# ════════════════════════════════════════
# FUNCIONES PDF
# ════════════════════════════════════════

def generar_pdf_generico(df, titulo, col_widths=None):
    from reportlab.lib.pagesizes import landscape, A4
    from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer
    from reportlab.lib.styles import getSampleStyleSheet
    from reportlab.lib import colors

    buffer = BytesIO()
    doc = SimpleDocTemplate(buffer, pagesize=landscape(A4),
                            leftMargin=20, rightMargin=20,
                            topMargin=30, bottomMargin=20)
    styles = getSampleStyleSheet()
    elements = []

    titulo_par = Paragraph(
        f"<b>{titulo} — IMJ</b><br/>"
        f"<font size=9>Generado: {datetime.now().strftime('%d/%m/%Y %H:%M')}</font>",
        styles["Title"]
    )
    elements.append(titulo_par)
    elements.append(Spacer(1, 12))

    columnas = list(df.columns)
    data = [columnas]
    for _, row in df.iterrows():
        data.append([str(row.get(c, "") or "") for c in columnas])

    if col_widths is None:
        page_width = landscape(A4)[0] - 40
        col_widths = [page_width / len(columnas)] * len(columnas)

    tabla = Table(data, colWidths=col_widths, repeatRows=1)
    tabla.setStyle(TableStyle([
        ("BACKGROUND",    (0, 0), (-1, 0),  colors.HexColor("#1a3c5e")),
        ("TEXTCOLOR",     (0, 0), (-1, 0),  colors.white),
        ("FONTNAME",      (0, 0), (-1, 0),  "Helvetica-Bold"),
        ("FONTSIZE",      (0, 0), (-1, 0),  8),
        ("ALIGN",         (0, 0), (-1, -1), "CENTER"),
        ("FONTSIZE",      (0, 1), (-1, -1), 7),
        ("ROWBACKGROUNDS",(0, 1), (-1, -1), [colors.white, colors.HexColor("#eaf1fb")]),
        ("GRID",          (0, 0), (-1, -1), 0.4, colors.grey),
        ("VALIGN",        (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING",    (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
    ]))
    elements.append(tabla)
    elements.append(Spacer(1, 10))
    elements.append(Paragraph(f"Total de registros: <b>{len(df)}</b>", styles["Normal"]))
    doc.build(elements)
    buffer.seek(0)
    return buffer


def generar_pdf_usuarios(df):
    col_widths = [30, 70, 90, 90, 80, 100, 130]
    return generar_pdf_generico(df, "Inventario de Usuarios", col_widths)


def generar_pdf_equipos_detalle(df, usuario_nombre=None):
    titulo = f"Equipos de Computo — {usuario_nombre}" if usuario_nombre else "Inventario de Equipos de Computo"
    col_widths = [80, 80, 100, 120, 160, 70]
    return generar_pdf_generico(df, titulo, col_widths)


def generar_pdf_equipos_resumen(df):
    col_widths = [140, 160, 180, 50]
    return generar_pdf_generico(df, "Resumen de Equipos de Computo", col_widths)


# ════════════════════════════════════════
# CONFIGURACIÓN STREAMLIT
# ════════════════════════════════════════

st.set_page_config(page_title="Sistema IMJ", layout="wide")

st.markdown("""
    <style>
    [data-testid="stElementToolbar"] { display: none !important; }
    </style>
""", unsafe_allow_html=True)

st.title("Sistema de Inventario IMJ")
st.markdown("---")

menu = st.sidebar.selectbox("Selecciona un modulo", [
    "🏠 Inicio",
    "👤 Usuarios",
    "💻 Equipos de Computo",
    "🌐 Direccionamiento IP",
    "📱 Telefonos",
    "🖨️ Impresoras",
    "📦 Insumos",
])

# ════════════════════════════════════════
# INICIO
# ════════════════════════════════════════
if menu == "🏠 Inicio":
    st.subheader("Bienvenido al Sistema de Inventario del IMJ")
    try:
        conn = get_conn()
        cur  = conn.cursor()

        cur.execute("SELECT COUNT(*) FROM usuarios WHERE activo = true")
        total_usuarios = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM computo")
        total_computo = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM impresoras")
        total_impresoras = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM computo WHERE estatus = 'activo'")
        equipos_activos = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM computo WHERE estatus IN ('dañado','en reparacion')")
        equipos_danados = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM inventario_ips_completo WHERE estatus ILIKE 'Libre%'")
        ips_libres = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM inventario_ips_completo WHERE estatus ILIKE 'Ocupada%'")
        ips_ocupadas = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM insumos WHERE stock_actual <= stock_minimo")
        insumos_bajos = cur.fetchone()[0]

        cur.execute("""
            SELECT d.nombre, COUNT(u.id_usuario)
            FROM departamentos d
            LEFT JOIN usuarios u ON u.id_departamento = d.id_departamento AND u.activo = true
            GROUP BY d.nombre ORDER BY COUNT(u.id_usuario) DESC
        """)
        df_deptos = pd.DataFrame(cur.fetchall(), columns=["Departamento", "Usuarios"])

        cur.close()
        conn.close()

        st.markdown("#### Resumen general")
        col1, col2, col3, col4 = st.columns(4)
        col1.metric("👤 Usuarios activos",  total_usuarios)
        col2.metric("💻 Equipos",           total_computo)
        col3.metric("🖨️ Impresoras",        total_impresoras)
        col4.metric("📦 Insumos con stock bajo", insumos_bajos,
                    delta=f"-{insumos_bajos}" if insumos_bajos > 0 else None,
                    delta_color="inverse")

        st.markdown("#### Equipos e IPs")
        col5, col6, col7, col8 = st.columns(4)
        col5.metric("✅ Equipos activos",     equipos_activos)
        col6.metric("⚠️ Dañados / Reparacion", equipos_danados,
                    delta=f"+{equipos_danados}" if equipos_danados > 0 else None,
                    delta_color="inverse")
        col7.metric("🟢 IPs libres",   ips_libres)
        col8.metric("🔴 IPs ocupadas", ips_ocupadas)

        st.markdown("#### Usuarios por departamento")
        st.bar_chart(df_deptos.set_index("Departamento"), use_container_width=True, height=280)

    except Exception as e:
        st.error(f"Error al conectar con la BD: {e}")

# ════════════════════════════════════════
# USUARIOS
# ════════════════════════════════════════
elif menu == "👤 Usuarios":
    st.subheader("👤 Gestion de Usuarios")
    accion = st.radio("Accion", ["Ver usuarios", "Editar usuario", "Alta de usuario", "Baja de usuario", "Reactivar usuario", "Traspaso de area"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver usuarios":
        cur.execute("SELECT nombre FROM departamentos ORDER BY nombre")
        deptos_lista = ["Todos"] + [r[0] for r in cur.fetchall()]
        mostrar_inactivos = st.checkbox("Mostrar usuarios inactivos (dados de baja)")
        cur.execute("""
            SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno,
                   u.puesto, d.nombre as departamento, u.correo
            FROM usuarios u
            LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
            WHERE u.activo = %s
            ORDER BY u.apellido_paterno
        """, (not mostrar_inactivos,))
        df = pd.DataFrame(cur.fetchall(), columns=["ID","Nombre(s)","Ap. Paterno","Ap. Materno","Puesto","Departamento","Correo"])

        col_f1, col_f2, col_f3 = st.columns([1.5, 1, 2])
        depto_f   = col_f1.selectbox("Departamento", deptos_lista)
        puesto_f  = col_f2.text_input("Puesto", placeholder="Ej: Jefe...")
        busqueda_u = col_f3.text_input("Buscar", placeholder="Nombre, apellido, correo...")

        if depto_f != "Todos":
            df = df[df["Departamento"] == depto_f].reset_index(drop=True)
        if puesto_f:
            df = df[df["Puesto"].str.contains(puesto_f, case=False, na=False)].reset_index(drop=True)
        if busqueda_u:
            mask = df[["Nombre(s)","Ap. Paterno","Ap. Materno","Correo"]].apply(
                lambda col: col.astype(str).str.contains(busqueda_u, case=False, na=False)
            ).any(axis=1)
            df = df[mask].reset_index(drop=True)

        st.dataframe(df, use_container_width=True, hide_index=True)
        st.caption(f"Total: {len(df)} usuarios")

        col1, col2 = st.columns(2)
        with col1:
            try:
                excel_buffer = BytesIO()
                with pd.ExcelWriter(excel_buffer, engine="openpyxl") as writer:
                    df.to_excel(writer, index=False, sheet_name="Usuarios")
                st.download_button(
                    "📊 Exportar a Excel",
                    data=excel_buffer.getvalue(),
                    file_name=f"usuarios_IMJ_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    use_container_width=True
                )
            except Exception as e:
                st.error(f"Error al generar Excel: {e}")
        with col2:
            try:
                pdf_bytes = generar_pdf_usuarios(df).read()
                st.download_button(
                    "📄 Exportar a PDF",
                    data=pdf_bytes,
                    file_name=f"usuarios_IMJ_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                    mime="application/pdf",
                    use_container_width=True
                )
            except Exception as e:
                st.error(f"Error al generar PDF: {e}")

    elif accion == "Editar usuario":
        if st.session_state.get("usuario_guardado"):
            nombre_guardado = st.session_state.pop("usuario_guardado")
            st.success(f"✅ **{nombre_guardado}** fue actualizado correctamente.")
        cur.execute("""
            SELECT id_usuario, nombre, apellido_paterno, apellido_materno,
                   puesto, correo, id_departamento
            FROM usuarios ORDER BY apellido_paterno
        """)
        usuarios = cur.fetchall()
        opciones_u = {f"{u[2]}, {u[1]}  (ID {u[0]})": u for u in usuarios}
        sel = st.selectbox("Selecciona el usuario a editar", list(opciones_u.keys()))
        u = opciones_u[sel]
        id_usuario = u[0]
        st.info(f"Editando ID: **{id_usuario}**")
        cur.execute("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")
        deptos = cur.fetchall()
        idx_dep = next((i for i, d in enumerate(deptos) if d[0] == u[6]), 0)
        with st.form("editar_usuario"):
            col1, col2, col3 = st.columns(3)
            nuevo_nombre = col1.text_input("Nombre(s)",        value=u[1] or "")
            nuevo_ap_pat = col2.text_input("Apellido Paterno", value=u[2] or "")
            nuevo_ap_mat = col3.text_input("Apellido Materno", value=u[3] or "")
            col4, col5 = st.columns(2)
            nuevo_puesto = col4.text_input("Puesto",  value=u[4] or "")
            nuevo_correo = col5.text_input("Correo",  value=u[5] or "")
            depto_sel = st.selectbox("Departamento", [d[1] for d in deptos], index=idx_dep)
            nuevo_id_dep = next(d[0] for d in deptos if d[1] == depto_sel)
            if st.form_submit_button("💾 Guardar cambios"):
                if nuevo_nombre and nuevo_ap_pat:
                    cur.execute("""
                        UPDATE usuarios SET nombre=%s, apellido_paterno=%s, apellido_materno=%s,
                        puesto=%s, correo=%s, id_departamento=%s WHERE id_usuario=%s
                    """, (nuevo_nombre, nuevo_ap_pat, nuevo_ap_mat, nuevo_puesto, nuevo_correo, nuevo_id_dep, id_usuario))
                    conn.commit()
                    st.session_state["usuario_guardado"] = f"{nuevo_nombre} {nuevo_ap_pat}"
                    st.rerun()
                else:
                    st.warning("Nombre y Apellido Paterno son obligatorios.")

    elif accion == "Alta de usuario":
        with st.form("alta_usuario"):
            col1, col2, col3 = st.columns(3)
            nombre = col1.text_input("Nombre(s)")
            ap_pat = col2.text_input("Apellido Paterno")
            ap_mat = col3.text_input("Apellido Materno")
            col4, col5 = st.columns(2)
            puesto = col4.text_input("Puesto")
            correo = col5.text_input("Correo")
            cur.execute("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")
            deptos = cur.fetchall()
            depto_sel = st.selectbox("Departamento", [d[1] for d in deptos])
            id_depto  = next(d[0] for d in deptos if d[1] == depto_sel)
            if st.form_submit_button("✅ Dar de Alta"):
                if nombre and ap_pat:
                    cur.execute("""
                        INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, puesto, correo, id_departamento)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """, (nombre, ap_pat, ap_mat, puesto, correo, id_depto))
                    conn.commit()
                    st.success(f"✅ Usuario {nombre} {ap_pat} dado de alta.")
                else:
                    st.warning("Llena al menos Nombre y Apellido Paterno.")

    elif accion == "Baja de usuario":
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios WHERE activo = true ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        if not usuarios:
            st.info("No hay usuarios activos.")
        else:
            opciones = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
            sel = st.selectbox("Selecciona el usuario a dar de baja", list(opciones.keys()))
            st.warning(f"⚠️ **{sel}** quedará inactivo. Sus datos se conservan y puede reactivarse.")
            if st.button("🔴 Dar de Baja"):
                cur.execute("UPDATE usuarios SET activo = false WHERE id_usuario = %s", (opciones[sel],))
                conn.commit()
                st.success(f"✅ {sel} dado de baja. Puedes reactivarlo desde 'Reactivar usuario'.")
                st.rerun()

    elif accion == "Reactivar usuario":
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios WHERE activo = false ORDER BY apellido_paterno")
        inactivos = cur.fetchall()
        if not inactivos:
            st.info("No hay usuarios inactivos.")
        else:
            opciones = {f"{u[1]} {u[2]}": u[0] for u in inactivos}
            sel = st.selectbox("Selecciona el usuario a reactivar", list(opciones.keys()))
            st.info(f"Se reactivara el acceso de **{sel}**.")
            if st.button("✅ Reactivar"):
                cur.execute("UPDATE usuarios SET activo = true WHERE id_usuario = %s", (opciones[sel],))
                conn.commit()
                st.success(f"✅ {sel} reactivado correctamente.")
                st.rerun()

    elif accion == "Traspaso de area":
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        sel = st.selectbox("Selecciona el usuario", list(opciones.keys()))
        cur.execute("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")
        deptos = cur.fetchall()
        depto_sel = st.selectbox("Nuevo departamento", [d[1] for d in deptos])
        id_depto  = next(d[0] for d in deptos if d[1] == depto_sel)
        if st.button("🔄 Traspasar"):
            cur.execute("UPDATE usuarios SET id_departamento = %s WHERE id_usuario = %s",
                        (id_depto, opciones[sel]))
            conn.commit()
            st.success(f"✅ {sel} trasladado a {depto_sel}.")
            st.rerun()

    cur.close()
    conn.close()

# ════════════════════════════════════════
# EQUIPOS DE COMPUTO  
# ════════════════════════════════════════
elif menu == "💻 Equipos de Computo":
    st.subheader("💻 Equipos de Computo")
    accion = st.radio("Accion", ["Ver equipos", "Agregar equipo", "Editar equipo", "Cambiar estatus", "Reasignar equipo"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver equipos":
        if "expandido" not in st.session_state:
            st.session_state.expandido = False

        col_f1, col_f2, col_f3 = st.columns([1, 2, 0.4])
        filtro   = col_f1.selectbox("Estatus", ["Todos", "activo", "dañado", "en reparacion"])
        busqueda = col_f2.text_input("Buscar usuario o equipo", placeholder="Ej: Adriana, Dell...")
        with col_f3:
            st.markdown("<br>", unsafe_allow_html=True)
            if st.button("⛶", help="Expandir tabla"):
                st.session_state.expandido = not st.session_state.expandido
                st.rerun()

        query_base = """
            SELECT
                u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                STRING_AGG(DISTINCT NULLIF(c.nombre_equipo, ''), ', ') AS "Equipos",
                STRING_AGG(DISTINCT NULLIF(c.serie, ''), ' / ') AS "Series",
                COUNT(c.id_computo) AS "Total"
            FROM public.computo c
            INNER JOIN public.usuarios u ON c.id_usuario = u.id_usuario
        """
        if filtro == "Todos":
            cur.execute(query_base + " GROUP BY u.nombre, u.apellido_paterno ORDER BY \"Total\" DESC;")
        else:
            cur.execute(query_base + " WHERE c.estatus = %s GROUP BY u.nombre, u.apellido_paterno ORDER BY \"Total\" DESC;", (filtro,))

        df = pd.DataFrame(cur.fetchall(), columns=["Usuario", "Equipos", "Series", "Total"])
        if busqueda:
            mask = df.apply(lambda col: col.astype(str).str.contains(busqueda, case=False, na=False)).any(axis=1)
            df = df[mask].reset_index(drop=True)

        altura = 700 if st.session_state.expandido else 420
        seleccion = st.dataframe(
            df,
            use_container_width=True,
            hide_index=True,
            height=altura,
            on_select="rerun",
            selection_mode="single-row"
        )

        df_detalles = None
        usuario_sel = None

        if seleccion.selection.rows:
            indice = seleccion.selection.rows[0]
            usuario_sel = df.iloc[indice]["Usuario"]

            st.markdown("---")
            st.subheader(f"📋 Equipos de: {usuario_sel}")

            cur.execute("""
                SELECT c.nombre_equipo AS "Equipo", c.marca AS "Marca", c.modelo AS "Modelo",
                       c.serie AS "Serie", c.mac_address AS "MAC", c.estatus AS "Estatus"
                FROM public.computo c
                JOIN public.usuarios u ON c.id_usuario = u.id_usuario
                WHERE (u.nombre || ' ' || u.apellido_paterno) = %s
            """, (usuario_sel,))

            df_detalles = pd.DataFrame(
                cur.fetchall(),
                columns=["Equipo", "Marca", "Modelo", "Serie", "MAC", "Estatus"]
            )
            st.table(df_detalles)

        tb1, tb2, tb3, tb4, _ = st.columns([0.8, 0.8, 0.5, 0.5, 6])

        with tb1:
            try:
                if df_detalles is not None and not df_detalles.empty:
                    pdf_bytes = generar_pdf_equipos_detalle(df_detalles, usuario_sel).read()
                    nombre_archivo = f"equipos_{usuario_sel.replace(' ', '_')}_{datetime.now().strftime('%Y%m%d')}.pdf"
                else:
                    pdf_bytes = generar_pdf_equipos_resumen(df).read()
                    nombre_archivo = f"equipos_resumen_{datetime.now().strftime('%Y%m%d')}.pdf"
                st.download_button("📄 PDF", data=pdf_bytes, file_name=nombre_archivo, mime="application/pdf", use_container_width=True)
            except Exception as e:
                st.button("📄 PDF", disabled=True)
                st.caption(f"Error PDF: {e}")

        with tb2:
            try:
                excel_buffer = BytesIO()
                if df_detalles is not None and not df_detalles.empty:
                    with pd.ExcelWriter(excel_buffer, engine="openpyxl") as writer:
                        df_detalles.to_excel(writer, index=False, sheet_name="Equipos")
                    nombre_archivo = f"equipos_{usuario_sel.replace(' ', '_')}_{datetime.now().strftime('%Y%m%d')}.xlsx"
                else:
                    with pd.ExcelWriter(excel_buffer, engine="openpyxl") as writer:
                        df.to_excel(writer, index=False, sheet_name="Equipos")
                    nombre_archivo = f"equipos_resumen_{datetime.now().strftime('%Y%m%d')}.xlsx"
                st.download_button("📊 Excel", data=excel_buffer.getvalue(), file_name=nombre_archivo,
                                   mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                   use_container_width=True)
            except Exception as e:
                st.button("📊 Excel", disabled=True)

        with tb3:
            pass

        with tb4:
            if st.button("⛶", help="Pantalla completa"):
                st.session_state.expandido = not st.session_state.expandido
                st.rerun()

        if not seleccion.selection.rows:
            st.caption(f"💡 Selecciona una fila para ver detalles. Total: {len(df)} usuarios.")

    elif accion == "Agregar equipo":
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios WHERE activo = true ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        with st.form("agregar_equipo"):
            sel_usr = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
            col1, col2 = st.columns(2)
            nombre_eq = col1.text_input("Nombre del equipo")
            marca     = col2.text_input("Marca")
            col3, col4 = st.columns(2)
            modelo = col3.text_input("Modelo")
            serie  = col4.text_input("Serie")
            col5, col6 = st.columns(2)
            mac    = col5.text_input("MAC Address")
            estatus = col6.selectbox("Estatus", ["activo", "dañado", "en reparacion"])
            if st.form_submit_button("✅ Agregar equipo"):
                if nombre_eq and marca:
                    cur.execute("""
                        INSERT INTO computo (id_usuario, nombre_equipo, marca, modelo, serie, mac_address, estatus)
                        VALUES (%s, %s, %s, %s, %s, %s, %s)
                    """, (opciones_usr[sel_usr], nombre_eq, marca, modelo, serie, mac, estatus))
                    conn.commit()
                    st.success(f"✅ Equipo **{nombre_eq}** registrado y asignado a {sel_usr}.")
                    st.rerun()
                else:
                    st.warning("Completa al menos Nombre del equipo y Marca.")

    elif accion == "Editar equipo":
        cur.execute("""
            SELECT c.id_computo, u.nombre, u.apellido_paterno,
                   c.nombre_equipo, c.marca, c.modelo, c.serie, c.mac_address, c.estatus
            FROM computo c LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        equipos = cur.fetchall()
        opciones_eq = {f"{e[1]} {e[2]} — {e[3]} (Serie: {e[6]})": e for e in equipos}
        sel = st.selectbox("Selecciona el equipo a editar", list(opciones_eq.keys()))
        equipo = opciones_eq[sel]
        id_computo = equipo[0]
        st.info(f"Editando equipo ID: **{id_computo}**")

        with st.form("editar_equipo"):
            col1, col2 = st.columns(2)
            nuevo_nombre = col1.text_input("Nombre del equipo", value=equipo[3] or "")
            nueva_marca  = col2.text_input("Marca", value=equipo[4] or "")
            nuevo_modelo = st.text_input("Modelo", value=equipo[5] or "")
            nueva_serie  = st.text_input("Serie", value=equipo[6] or "")
            nuevo_estatus = st.selectbox("Estatus", ["activo", "dañado", "en reparacion"], index=0)

            if st.form_submit_button("💾 Guardar cambios"):
                cur.execute("UPDATE computo SET nombre_equipo=%s, marca=%s, modelo=%s, serie=%s, estatus=%s WHERE id_computo=%s",
                           (nuevo_nombre, nueva_marca, nuevo_modelo, nueva_serie, nuevo_estatus, id_computo))
                conn.commit()
                st.success("Actualizado correctamente")
                st.rerun()

    elif accion == "Cambiar estatus":
        cur.execute("""
            SELECT c.id_computo, u.nombre, u.apellido_paterno, c.nombre_equipo, c.estatus
            FROM computo c JOIN usuarios u ON c.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        equipos = cur.fetchall()
        opciones = {f"{e[1]} {e[2]} — {e[3]}": (e[0], e[4]) for e in equipos}
        sel = st.selectbox("Selecciona equipo", list(opciones.keys()))
        id_eq, estatus_actual = opciones[sel]
        nuevo_estatus = st.selectbox(
            "Nuevo estatus", ["activo", "dañado", "en reparacion"],
            index=["activo", "dañado", "en reparacion"].index(estatus_actual)
                  if estatus_actual in ["activo", "dañado", "en reparacion"] else 0
        )
        if st.button("✅ Actualizar estatus"):
            cur.execute("UPDATE computo SET estatus=%s WHERE id_computo=%s", (nuevo_estatus, id_eq))
            conn.commit()
            st.success(f"✅ Estatus actualizado a '{nuevo_estatus}'.")
            st.rerun()

    elif accion == "Reasignar equipo":
        cur.execute("""
            SELECT c.id_computo, u.nombre, u.apellido_paterno, c.nombre_equipo, c.serie
            FROM computo c JOIN usuarios u ON c.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        equipos = cur.fetchall()
        opciones_eq = {f"{e[1]} {e[2]} — {e[3]} (Serie: {e[4]})": e[0] for e in equipos}
        sel_eq = st.selectbox("Selecciona el equipo", list(opciones_eq.keys()))
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        sel_usr = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
        if st.button("🔄 Reasignar"):
            cur.execute("UPDATE computo SET id_usuario=%s WHERE id_computo=%s",
                        (opciones_usr[sel_usr], opciones_eq[sel_eq]))
            conn.commit()
            st.success(f"✅ Equipo reasignado a {sel_usr}.")
            st.rerun()

    cur.close()
    conn.close()

# ════════════════════════════════════════
# TELEFONOS
# ════════════════════════════════════════
elif menu == "📱 Telefonos":
    st.subheader("📱 Telefonos")
    accion = st.radio("Accion", ["Ver telefonos", "Agregar telefono", "Reasignar telefono"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver telefonos":
        cur.execute("""
            SELECT t.id_telefono, t.numero_general, t.extension,
                   u.nombre, u.apellido_paterno
            FROM telefonos t LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        df = pd.DataFrame(cur.fetchall(), columns=["ID", "Numero General", "Extension", "Nombre", "Ap. Paterno"])

        col_f1, col_f2 = st.columns([1, 2])
        ext_f  = col_f1.text_input("Buscar extension", placeholder="Ej: 1234...")
        nombre_f = col_f2.text_input("Buscar usuario", placeholder="Ej: Juan...")

        if ext_f:
            df = df[df["Extension"].astype(str).str.contains(ext_f, case=False, na=False)].reset_index(drop=True)
        if nombre_f:
            mask = df[["Nombre","Ap. Paterno"]].apply(
                lambda col: col.astype(str).str.contains(nombre_f, case=False, na=False)
            ).any(axis=1)
            df = df[mask].reset_index(drop=True)

        st.dataframe(df, use_container_width=True, hide_index=True)
        st.caption(f"Total: {len(df)} telefonos")
        if not df.empty:
            excel_buf = BytesIO()
            df.to_excel(excel_buf, index=False)
            st.download_button("📥 Exportar a Excel", data=excel_buf.getvalue(),
                               file_name=f"telefonos_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                               mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")

    elif accion == "Agregar telefono":
        with st.form("agregar_telefono"):
            numero    = st.text_input("Numero general", value="(55)1500 1300")
            extension = st.text_input("Extension")
            cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
            usuarios = cur.fetchall()
            opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
            sel_usr = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
            if st.form_submit_button("✅ Agregar"):
                if extension:
                    cur.execute("INSERT INTO telefonos (numero_general, extension, id_usuario) VALUES (%s, %s, %s)",
                                (numero, extension, opciones_usr[sel_usr]))
                    conn.commit()
                    st.success("✅ Telefono agregado correctamente.")
                else:
                    st.warning("Por favor escribe la extension.")

    elif accion == "Reasignar telefono":
        cur.execute("""
            SELECT t.id_telefono, t.extension, u.nombre, u.apellido_paterno
            FROM telefonos t LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        telefonos = cur.fetchall()
        opciones_tel = {f"Ext. {t[1]} — {t[2]} {t[3]}": t[0] for t in telefonos}
        sel_tel = st.selectbox("Selecciona el telefono", list(opciones_tel.keys()))
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        sel_usr = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
        if st.button("🔄 Reasignar"):
            cur.execute("UPDATE telefonos SET id_usuario = %s WHERE id_telefono = %s",
                        (opciones_usr[sel_usr], opciones_tel[sel_tel]))
            conn.commit()
            st.success(f"✅ Telefono reasignado a {sel_usr}.")
            st.rerun()

    cur.close()
    conn.close()

# ════════════════════════════════════════
# IMPRESORAS
# ════════════════════════════════════════
elif menu == "🖨️ Impresoras":
    st.subheader("🖨️ Impresoras")
    accion = st.radio("Accion", ["Ver impresoras", "Agregar impresora", "Editar impresora", "Reasignar impresora"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver impresoras":
        cur.execute("""
            SELECT i.id_impresora, i.marca, i.modelo, i.serie, i.ip_address,
                   i.firmware, u.nombre, u.apellido_paterno
            FROM impresoras i LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        df = pd.DataFrame(cur.fetchall(), columns=["ID","Marca","Modelo","Serie","IP","Firmware","Nombre","Ap. Paterno"])

        cur.execute("SELECT DISTINCT marca FROM impresoras WHERE marca IS NOT NULL ORDER BY marca")
        marcas = ["Todas"] + [r[0] for r in cur.fetchall()]

        col_f1, col_f2, col_f3 = st.columns([1, 1, 2])
        marca_f  = col_f1.selectbox("Marca", marcas)
        ip_f     = col_f2.text_input("IP", placeholder="Ej: 192.168...")
        usuario_f = col_f3.text_input("Buscar usuario", placeholder="Nombre o apellido...")

        if marca_f != "Todas":
            df = df[df["Marca"] == marca_f].reset_index(drop=True)
        if ip_f:
            df = df[df["IP"].astype(str).str.contains(ip_f, case=False, na=False)].reset_index(drop=True)
        if usuario_f:
            mask = df[["Nombre","Ap. Paterno"]].apply(
                lambda col: col.astype(str).str.contains(usuario_f, case=False, na=False)
            ).any(axis=1)
            df = df[mask].reset_index(drop=True)

        st.dataframe(df, use_container_width=True, hide_index=True)
        st.caption(f"Total: {len(df)} impresoras")
        if not df.empty:
            excel_buf = BytesIO()
            df.to_excel(excel_buf, index=False)
            st.download_button("📥 Exportar a Excel", data=excel_buf.getvalue(),
                               file_name=f"impresoras_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                               mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")

    elif accion == "Agregar impresora":
        with st.form("agregar_impresora"):
            col1, col2 = st.columns(2)
            marca   = col1.text_input("Marca")
            modelo  = col2.text_input("Modelo")
            col3, col4 = st.columns(2)
            serie   = col3.text_input("Serie")
            ip      = col4.text_input("IP Address")
            firmware = st.text_input("Firmware")
            cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
            usuarios = cur.fetchall()
            opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
            sel_usr = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
            if st.form_submit_button("✅ Agregar"):
                if marca and modelo:
                    cur.execute("""
                        INSERT INTO impresoras (marca, modelo, serie, ip_address, firmware, id_usuario)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """, (marca, modelo, serie, ip, firmware, opciones_usr[sel_usr]))
                    conn.commit()
                    st.success(f"✅ Impresora {marca} {modelo} agregada.")
                else:
                    st.warning("Llena al menos Marca y Modelo.")

    elif accion == "Editar impresora":
        cur.execute("""
            SELECT i.id_impresora, i.marca, i.modelo, i.serie, i.ip_address, i.firmware,
                   u.nombre, u.apellido_paterno
            FROM impresoras i LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        impresoras = cur.fetchall()
        opciones_imp = {f"{e[1]} {e[2]} — {e[6] or ''} {e[7] or ''} (Serie: {e[3] or 'S/N'})": e for e in impresoras}
        sel = st.selectbox("Selecciona la impresora a editar", list(opciones_imp.keys()))
        imp = opciones_imp[sel]
        with st.form("editar_impresora"):
            col1, col2 = st.columns(2)
            nueva_marca  = col1.text_input("Marca",  value=imp[1] or "")
            nuevo_modelo = col2.text_input("Modelo", value=imp[2] or "")
            col3, col4 = st.columns(2)
            nueva_serie  = col3.text_input("Serie",  value=imp[3] or "")
            nueva_ip     = col4.text_input("IP Address", value=imp[4] or "")
            nuevo_fw     = st.text_input("Firmware", value=imp[5] or "")
            if st.form_submit_button("💾 Guardar cambios"):
                cur.execute("""
                    UPDATE impresoras SET marca=%s, modelo=%s, serie=%s, ip_address=%s, firmware=%s
                    WHERE id_impresora=%s
                """, (nueva_marca, nuevo_modelo, nueva_serie, nueva_ip, nuevo_fw, imp[0]))
                conn.commit()
                st.success(f"✅ Impresora actualizada correctamente.")
                st.rerun()

    elif accion == "Reasignar impresora":
        cur.execute("""
            SELECT i.id_impresora, i.marca, i.modelo, u.nombre, u.apellido_paterno
            FROM impresoras i LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        impresoras = cur.fetchall()
        opciones_imp = {f"{e[1]} {e[2]} — {e[3]} {e[4]}": e[0] for e in impresoras}
        sel_imp = st.selectbox("Selecciona la impresora", list(opciones_imp.keys()))
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        sel_usr = st.selectbox("Asignar usuario", list(opciones_usr.keys()))
        if st.button("🔄 Reasignar"):
            cur.execute("UPDATE impresoras SET id_usuario = %s WHERE id_impresora = %s",
                        (opciones_usr[sel_usr], opciones_imp[sel_imp]))
            conn.commit()
            st.success(f"✅ Impresora reasignada a {sel_usr}.")
            st.rerun()

    cur.close()
    conn.close()

# ════════════════════════════════════════
# INSUMOS
# ════════════════════════════════════════
elif menu == "📦 Insumos":
    st.subheader("📦 Insumos")
    accion = st.radio("Accion", ["Ver insumos", "Agregar insumo", "Registrar suministro"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver insumos":
        cur.execute("""
            SELECT id_insumo, nombre_insumo, numero_parte,
                   stock_minimo, stock_maximo, stock_actual
            FROM insumos ORDER BY nombre_insumo
        """)
        df = pd.DataFrame(cur.fetchall(), columns=["ID","Insumo","No. Parte","Stock Min","Stock Max","Stock Actual"])

        col_f1, col_f2 = st.columns([2, 1])
        busqueda_ins = col_f1.text_input("Buscar insumo", placeholder="Nombre o numero de parte...")
        stock_f = col_f2.selectbox("Stock", ["Todos", "Stock bajo / agotado", "Stock normal"])

        if busqueda_ins:
            mask = df[["Insumo","No. Parte"]].apply(
                lambda col: col.astype(str).str.contains(busqueda_ins, case=False, na=False)
            ).any(axis=1)
            df = df[mask].reset_index(drop=True)
        if stock_f == "Stock bajo / agotado":
            df = df[df["Stock Actual"] <= df["Stock Min"]].reset_index(drop=True)
        elif stock_f == "Stock normal":
            df = df[df["Stock Actual"] > df["Stock Min"]].reset_index(drop=True)

        def resaltar_stock(row):
            if row["Stock Actual"] <= row["Stock Min"]:
                return ["background-color: #ffcccc"] * len(row)
            return [""] * len(row)
        st.dataframe(df.style.apply(resaltar_stock, axis=1), use_container_width=True, hide_index=True)
        st.caption(f"Total: {len(df)} insumos  |  Rojo = stock bajo o agotado")
        if not df.empty:
            excel_buf = BytesIO()
            df.to_excel(excel_buf, index=False)
            st.download_button("📥 Exportar a Excel", data=excel_buf.getvalue(),
                               file_name=f"insumos_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                               mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")

    elif accion == "Agregar insumo":
        with st.form("agregar_insumo"):
            nombre    = st.text_input("Nombre del insumo")
            num_parte = st.text_input("Numero de parte")
            col1, col2, col3 = st.columns(3)
            stock_min = col1.number_input("Stock minimo", min_value=0, value=5)
            stock_max = col2.number_input("Stock maximo", min_value=0, value=50)
            stock_act = col3.number_input("Stock actual",  min_value=0, value=0)
            if st.form_submit_button("✅ Agregar"):
                if nombre:
                    cur.execute("""
                        INSERT INTO insumos (nombre_insumo, numero_parte, stock_minimo, stock_maximo, stock_actual)
                        VALUES (%s, %s, %s, %s, %s)
                    """, (nombre, num_parte, stock_min, stock_max, stock_act))
                    conn.commit()
                    st.success(f"✅ Insumo '{nombre}' agregado.")
                else:
                    st.warning("Escribe el nombre del insumo.")

    elif accion == "Registrar suministro":
        cur.execute("SELECT id_insumo, nombre_insumo, stock_actual FROM insumos ORDER BY nombre_insumo")
        insumos = cur.fetchall()
        opciones_ins = {f"{i[1]} (stock: {i[2]})": (i[0], i[2]) for i in insumos}
        sel_ins = st.selectbox("Selecciona el insumo", list(opciones_ins.keys()))
        id_insumo, stock_actual = opciones_ins[sel_ins]
        cur.execute("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")
        deptos = cur.fetchall()
        depto_nombres = [d[1] for d in deptos]
        depto_sel = st.selectbox("Departamento solicitante", depto_nombres)
        id_depto  = next(d[0] for d in deptos if d[1] == depto_sel)
        cantidad  = st.number_input("Cantidad a entregar", min_value=1, value=1)
        if st.button("📤 Registrar entrega"):
            if cantidad > stock_actual:
                st.error(f"❌ Stock insuficiente. Stock actual: {stock_actual}")
            else:
                cur.execute("""
                    INSERT INTO suministros (id_insumo, id_departamento, fecha_solicitud,
                                            cantidad_requerida, cantidad_entregada, estatus)
                    VALUES (%s, %s, CURRENT_DATE, %s, %s, %s)
                """, (id_insumo, id_depto, cantidad, cantidad, "entregado"))
                cur.execute("UPDATE insumos SET stock_actual = stock_actual - %s WHERE id_insumo = %s",
                            (cantidad, id_insumo))
                conn.commit()
                st.success(f"✅ Entrega registrada. Nuevo stock: {stock_actual - cantidad}")
                st.rerun()

    cur.close()
    conn.close()

# ════════════════════════════════════════
# DIRECCIONAMIENTO IP
# ════════════════════════════════════════
elif menu == "🌐 Direccionamiento IP":
    st.subheader("🌐 Gestión de Direccionamiento IP - IMJUVE")

    accion = st.radio("Acción", ["Ver IPs y Rangos", "Asignar IP", "Editar asignación", "Liberar IP"])

    conn = get_conn()
    cur = conn.cursor()

    try:
        if accion == "Ver IPs y Rangos":
            st.markdown("### Rangos y Disponibilidad")

            cur.execute("""
                SELECT
                    r.area_nombre,
                    r.ip_inicial || ' a ' || r.ip_final,
                    r.capacidad_total,
                    COUNT(i.ip) FILTER (WHERE i.estatus ILIKE 'Ocupada%'),
                    COUNT(i.ip) FILTER (WHERE i.estatus ILIKE 'Libre%'),
                    COUNT(i.ip) FILTER (WHERE i.estatus ILIKE 'En Conflicto%')
                FROM cat_rangos_ips r
                LEFT JOIN inventario_ips_completo i
                    ON UPPER(TRIM(r.area_nombre)) = UPPER(TRIM(i.departamento_pestana))
                GROUP BY r.area_nombre, r.ip_inicial, r.ip_final, r.capacidad_total
                ORDER BY r.area_nombre
            """)
            resumen_data = cur.fetchall()

            if resumen_data:
                df_r = pd.DataFrame(resumen_data, columns=["Área", "Rango de IPs", "Total IPs", "Ocupadas", "Libres", "En Conflicto"])
                df_r['%_num'] = (df_r['Ocupadas'] / df_r['Total IPs'] * 100).round(1)
                df_r['% Usado'] = df_r['%_num'].astype(str) + '%'

                def color_semaforo(val):
                    try:
                        num = float(val.replace('%', ''))
                        if num >= 90: color = '#ff4b4b'
                        elif num >= 70: color = '#ffa500'
                        else: color = '#09ab3b'
                        return f'background-color: {color}; color: white; font-weight: bold'
                    except:
                        return ''

                df_styled = df_r.drop(columns=['%_num']).style.map(color_semaforo, subset=['% Usado'])
                st.table(df_styled)
            else:
                st.info("💡 No hay datos en el catálogo de rangos. Verifica 'cat_rangos_ips'.")

            st.divider()
            st.markdown("### 🔍 Buscador de IPs")

            cur.execute("SELECT DISTINCT departamento_pestana FROM inventario_ips_completo WHERE departamento_pestana IS NOT NULL ORDER BY departamento_pestana")
            areas_db = [a[0] for a in cur.fetchall()]
            col_f1, col_f2, col_f3 = st.columns([1, 2, 1])
            area_sel   = col_f1.selectbox("Filtrar por Área", ["Todas"] + areas_db)
            busqueda   = col_f2.text_input("Buscar por IP, Usuario, MAC o Uso", placeholder="Ej: 172.17... o Personal...")
            estatus_sel = col_f3.selectbox("Estatus", ["Todos", "Libre", "Ocupada", "En Conflicto"])

            query_busqueda = "SELECT ip, usuario, tipo_equipo, institucional_o_personal, mac, departamento_pestana, estatus, observaciones FROM inventario_ips_completo WHERE 1=1"
            params = []
            if area_sel != "Todas":
                query_busqueda += " AND departamento_pestana = %s"
                params.append(area_sel)
            if estatus_sel != "Todos":
                query_busqueda += " AND estatus ILIKE %s"
                params.append(f"{estatus_sel}%")
            if busqueda:
                query_busqueda += " AND (ip LIKE %s OR usuario LIKE %s OR mac LIKE %s OR tipo_equipo LIKE %s OR institucional_o_personal LIKE %s)"
                term = f"%{busqueda}%"
                params.extend([term, term, term, term, term])
            query_busqueda += " ORDER BY departamento_pestana, ip"

            cur.execute(query_busqueda, params)
            df_ip = pd.DataFrame(cur.fetchall(), columns=["Dirección IP", "Usuario", "Equipo", "Uso (Inst./Pers.)", "MAC Address", "Área Origen", "Estatus", "Notas"])

            st.metric("Resultados encontrados", len(df_ip))
            st.dataframe(df_ip, use_container_width=True, hide_index=True, height=400)

            if not df_ip.empty:
                excel_data = BytesIO()
                df_ip.to_excel(excel_data, index=False)
                st.download_button(
                    label="📥 Exportar a Excel",
                    data=excel_data.getvalue(),
                    file_name=f"reporte_ips_{datetime.now().strftime('%H%M')}.xlsx",
                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                )

        elif accion == "Asignar IP":
            st.markdown("### 📌 Asignar IP a Usuario / Equipo")

            cur.execute("SELECT DISTINCT departamento_pestana FROM inventario_ips_completo WHERE estatus ILIKE 'Libre%' AND departamento_pestana IS NOT NULL ORDER BY departamento_pestana")
            areas_libres = ["Todas"] + [r[0] for r in cur.fetchall()]
            cur.execute("SELECT ip, usuario, departamento_pestana FROM inventario_ips_completo WHERE estatus ILIKE 'Libre%' ORDER BY departamento_pestana, ip")
            ips_libres = cur.fetchall()

            if not ips_libres:
                st.warning("No hay IPs libres disponibles.")
            else:
                col_f1, col_f2 = st.columns([1, 2])
                area_f = col_f1.selectbox("Filtrar por área", areas_libres, key="asignar_area")
                texto_f = col_f2.text_input("Buscar IP", placeholder="Ej: 172.17.1...", key="asignar_texto")

                ips_filtradas = [
                    i for i in ips_libres
                    if (area_f == "Todas" or i[2] == area_f)
                    and (not texto_f or texto_f.lower() in i[0].lower())
                ]

                if not ips_filtradas:
                    st.info("Sin resultados con ese filtro.")
                else:
                    st.caption(f"{len(ips_filtradas)} IPs libres encontradas")

                    cur.execute("SELECT nombre || ' ' || apellido_paterno FROM usuarios ORDER BY apellido_paterno")
                    nombres_usuarios = ["-- Escribir manualmente --"] + [r[0] for r in cur.fetchall()]
                    usr_rapido = st.selectbox("Seleccionar usuario registrado (opcional)", nombres_usuarios)

                    opciones_ip = {f"{i[0]}  —  {i[2]}": i[0] for i in ips_filtradas}

                    with st.form("asignar_ip"):
                        ip_sel = st.selectbox("IP a asignar", list(opciones_ip.keys()))
                        nombre_manual = st.text_input(
                            "Propietario / Usuario",
                            value="" if usr_rapido == "-- Escribir manualmente --" else usr_rapido,
                            help="Puedes editar este campo libremente"
                        )
                        col1, col2, col3 = st.columns(3)
                        tipos = ["PC", "Laptop", "Impresora", "Servidor", "Switch", "Camara", "Otro"]
                        tipo_equipo = col1.selectbox("Tipo de equipo", tipos)
                        inst_pers   = col2.selectbox("Uso", ["Institucional", "Personal"])
                        mac         = col3.text_input("MAC Address")
                        col4, col5, col6 = st.columns(3)
                        marca  = col4.text_input("Marca")
                        modelo = col5.text_input("Modelo")
                        serie  = col6.text_input("Serie")
                        observaciones = st.text_area("Observaciones", height=80)

                        if st.form_submit_button("✅ Asignar IP"):
                            if not nombre_manual.strip():
                                st.warning("Escribe el nombre del propietario.")
                            else:
                                ip_real = opciones_ip[ip_sel]
                                cur.execute("""
                                    UPDATE inventario_ips_completo
                                    SET usuario=%s, tipo_equipo=%s, institucional_o_personal=%s,
                                        marca=%s, modelo=%s, serie=%s, mac=%s,
                                        estatus='Ocupada', observaciones=%s
                                    WHERE ip=%s
                                """, (nombre_manual.strip(), tipo_equipo, inst_pers, marca, modelo, serie, mac, observaciones, ip_real))
                                conn.commit()
                                st.success(f"✅ IP **{ip_real}** asignada a **{nombre_manual.strip()}**.")
                                st.rerun()

        elif accion == "Editar asignación":
            st.markdown("### ✏️ Editar Asignación de IP")

            cur.execute("SELECT DISTINCT departamento_pestana FROM inventario_ips_completo WHERE estatus ILIKE 'Ocupada%' AND departamento_pestana IS NOT NULL ORDER BY departamento_pestana")
            areas_ocup = ["Todas"] + [r[0] for r in cur.fetchall()]
            cur.execute("""
                SELECT ip, usuario, tipo_equipo, institucional_o_personal,
                       marca, modelo, serie, mac, observaciones, departamento_pestana
                FROM inventario_ips_completo WHERE estatus ILIKE 'Ocupada%'
                ORDER BY departamento_pestana, ip
            """)
            ips_ocupadas = cur.fetchall()

            if not ips_ocupadas:
                st.info("No hay IPs ocupadas registradas.")
            else:
                col_f1, col_f2 = st.columns([1, 2])
                area_f  = col_f1.selectbox("Filtrar por área", areas_ocup, key="editar_area")
                texto_f = col_f2.text_input("Buscar por IP o usuario", placeholder="Ej: 172.17... o Juan...", key="editar_texto")

                ips_filtradas = [
                    i for i in ips_ocupadas
                    if (area_f == "Todas" or i[9] == area_f)
                    and (not texto_f or texto_f.lower() in i[0].lower() or texto_f.lower() in (i[1] or "").lower())
                ]

                if not ips_filtradas:
                    st.info("Sin resultados con ese filtro.")
                else:
                    st.caption(f"{len(ips_filtradas)} IPs encontradas")
                    opciones_ip = {f"{i[0]}  —  {i[1] or '(sin usuario)'}  |  {i[9]}": i for i in ips_filtradas}
                    ip_sel_key = st.selectbox("Selecciona la IP a editar", list(opciones_ip.keys()))
                    d = opciones_ip[ip_sel_key]

                    with st.form("editar_ip"):
                        nuevo_usuario = st.text_input("Propietario / Usuario", value=d[1] or "")
                        col1, col2, col3 = st.columns(3)
                        tipos = ["PC", "Laptop", "Impresora", "Servidor", "Switch", "Camara", "Otro"]
                        idx_tipo = tipos.index(d[2]) if d[2] in tipos else len(tipos) - 1
                        tipo_equipo = col1.selectbox("Tipo de equipo", tipos, index=idx_tipo)
                        usos = ["Institucional", "Personal"]
                        idx_uso = usos.index(d[3]) if d[3] in usos else 0
                        inst_pers = col2.selectbox("Uso", usos, index=idx_uso)
                        mac = col3.text_input("MAC Address", value=d[7] or "")
                        col4, col5, col6 = st.columns(3)
                        marca  = col4.text_input("Marca",  value=d[4] or "")
                        modelo = col5.text_input("Modelo", value=d[5] or "")
                        serie  = col6.text_input("Serie",  value=d[6] or "")
                        observaciones = st.text_area("Observaciones", value=d[8] or "", height=80)

                        if st.form_submit_button("💾 Guardar cambios"):
                            cur.execute("""
                                UPDATE inventario_ips_completo
                                SET usuario=%s, tipo_equipo=%s, institucional_o_personal=%s,
                                    marca=%s, modelo=%s, serie=%s, mac=%s, observaciones=%s
                                WHERE ip=%s
                            """, (nuevo_usuario, tipo_equipo, inst_pers, marca, modelo, serie, mac, observaciones, d[0]))
                            conn.commit()
                            st.success(f"✅ IP **{d[0]}** actualizada correctamente.")
                            st.rerun()

        elif accion == "Liberar IP":
            st.markdown("### 🔓 Liberar IP")

            cur.execute("SELECT DISTINCT departamento_pestana FROM inventario_ips_completo WHERE estatus ILIKE 'Ocupada%' AND departamento_pestana IS NOT NULL ORDER BY departamento_pestana")
            areas_ocup = ["Todas"] + [r[0] for r in cur.fetchall()]
            cur.execute("""
                SELECT ip, usuario, tipo_equipo, departamento_pestana
                FROM inventario_ips_completo WHERE estatus ILIKE 'Ocupada%'
                ORDER BY departamento_pestana, ip
            """)
            ips_ocupadas = cur.fetchall()

            if not ips_ocupadas:
                st.info("No hay IPs ocupadas registradas.")
            else:
                col_f1, col_f2 = st.columns([1, 2])
                area_f  = col_f1.selectbox("Filtrar por área", areas_ocup, key="liberar_area")
                texto_f = col_f2.text_input("Buscar por IP o usuario", placeholder="Ej: 172.17... o Juan...", key="liberar_texto")

                ips_filtradas = [
                    i for i in ips_ocupadas
                    if (area_f == "Todas" or i[3] == area_f)
                    and (not texto_f or texto_f.lower() in i[0].lower() or texto_f.lower() in (i[1] or "").lower())
                ]

                if not ips_filtradas:
                    st.info("Sin resultados con ese filtro.")
                else:
                    st.caption(f"{len(ips_filtradas)} IPs encontradas")
                    opciones_ip = {f"{i[0]}  —  {i[1] or '(sin usuario)'}  |  {i[2] or ''}  |  {i[3]}": i[0] for i in ips_filtradas}
                    ip_sel_key = st.selectbox("Selecciona la IP a liberar", list(opciones_ip.keys()))
                    ip_real = opciones_ip[ip_sel_key]
                    st.warning(f"⚠️ Se borrará toda la asignación de **{ip_real}** y quedará como Libre.")
                    if st.button("🔓 Liberar IP"):
                        cur.execute("""
                            UPDATE inventario_ips_completo
                            SET usuario=NULL, tipo_equipo=NULL, institucional_o_personal=NULL,
                                marca=NULL, modelo=NULL, serie=NULL, mac=NULL,
                                estatus='Libre', observaciones=NULL
                            WHERE ip=%s
                        """, (ip_real,))
                        conn.commit()
                        st.success(f"✅ IP **{ip_real}** liberada correctamente.")
                        st.rerun()

    except Exception as e:
        st.error(f"Error en el sistema: {e}")
        st.warning("Verifica la conexión y las tablas en pgAdmin.")

    cur.close()
    conn.close()