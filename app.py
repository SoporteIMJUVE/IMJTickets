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
    "📱 Telefonos",
    "🖨️ Impresoras",
    "📦 Insumos",
])

# ════════════════════════════════════════
# INICIO
# ════════════════════════════════════════
if menu == "🏠 Inicio":
    st.subheader("Bienvenido al Sistema de Inventario del IMJ")
    st.info("Usa el menu de la izquierda para navegar entre los modulos.")
    try:
        conn = get_conn()
        cur  = conn.cursor()
        cur.execute("SELECT COUNT(*) FROM usuarios")
        total_usuarios = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM computo")
        total_computo = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM impresoras")
        total_impresoras = cur.fetchone()[0]
        cur.close()
        conn.close()
        col1, col2, col3 = st.columns(3)
        col1.metric("👤 Usuarios",   total_usuarios)
        col2.metric("💻 Equipos",    total_computo)
        col3.metric("🖨️ Impresoras", total_impresoras)
    except Exception as e:
        st.error(f"Error al conectar con la BD: {e}")

# ════════════════════════════════════════
# USUARIOS
# ════════════════════════════════════════
elif menu == "👤 Usuarios":
    st.subheader("👤 Gestion de Usuarios")
    accion = st.radio("Accion", ["Ver usuarios", "Editar usuario", "Alta de usuario", "Baja de usuario", "Traspaso de area"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver usuarios":
        cur.execute("""
            SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno,
                   u.puesto, d.nombre as departamento, u.correo
            FROM usuarios u
            LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
            ORDER BY u.apellido_paterno
        """)
        datos = cur.fetchall()
        df = pd.DataFrame(datos, columns=["ID","Nombre(s)","Ap. Paterno","Ap. Materno","Puesto","Departamento","Correo"])

        busqueda_u = st.text_input("Buscar usuario", placeholder="Nombre, apellido, departamento...")
        if busqueda_u:
            mask = df.apply(lambda col: col.astype(str).str.contains(busqueda_u, case=False, na=False)).any(axis=1)
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
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        sel = st.selectbox("Selecciona el usuario a dar de baja", list(opciones.keys()))
        st.warning(f"⚠️ Esta acción eliminará permanentemente a **{sel}**.")
        if st.button("🗑️ Dar de Baja"):
            cur.execute("DELETE FROM usuarios WHERE id_usuario = %s", (opciones[sel],))
            conn.commit()
            st.success(f"✅ Usuario {sel} dado de baja.")
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
    accion = st.radio("Accion", ["Ver equipos", "Editar equipo", "Cambiar estatus", "Reasignar equipo"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver equipos":
        filtro = st.selectbox("Filtrar por estatus", ["Todos", "activo", "dañado", "en reparacion"])

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

        datos = cur.fetchall()
        df = pd.DataFrame(datos, columns=["Usuario", "Equipos", "Series", "Total"])

        if "buscar_activo" not in st.session_state:
            st.session_state.buscar_activo = False
        if "expandido" not in st.session_state:
            st.session_state.expandido = False

        if st.session_state.buscar_activo:
            busqueda = st.text_input("🔍 Buscar usuario o equipo", placeholder="Ej: Adriana...")
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
            if st.button("🔍", help="Buscar"):
                st.session_state.buscar_activo = not st.session_state.buscar_activo
                st.rerun()

        with tb4:
            if st.button("⛶", help="Pantalla completa"):
                st.session_state.expandido = not st.session_state.expandido
                st.rerun()

        if not seleccion.selection.rows:
            st.caption(f"💡 Selecciona una fila para ver detalles. Total: {len(df)} usuarios.")

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
        datos = cur.fetchall()
        df = pd.DataFrame(datos, columns=["ID", "Numero General", "Extension", "Nombre", "Ap. Paterno"])
        st.dataframe(df, use_container_width=True, hide_index=True)
        st.caption(f"Total: {len(df)} telefonos")

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
    accion = st.radio("Accion", ["Ver impresoras", "Agregar impresora", "Reasignar impresora"])
    conn = get_conn()
    cur  = conn.cursor()

    if accion == "Ver impresoras":
        cur.execute("""
            SELECT i.id_impresora, i.marca, i.modelo, i.serie, i.ip_address,
                   i.firmware, u.nombre, u.apellido_paterno
            FROM impresoras i LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        datos = cur.fetchall()
        df = pd.DataFrame(datos, columns=["ID","Marca","Modelo","Serie","IP","Firmware","Nombre","Ap. Paterno"])
        st.dataframe(df, use_container_width=True, hide_index=True)
        st.caption(f"Total: {len(df)} impresoras")

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
        datos = cur.fetchall()
        df = pd.DataFrame(datos, columns=["ID","Insumo","No. Parte","Stock Min","Stock Max","Stock Actual"])
        def resaltar_stock(row):
            if row["Stock Actual"] <= row["Stock Min"]:
                return ["background-color: #ffcccc"] * len(row)
            return [""] * len(row)
        st.dataframe(df.style.apply(resaltar_stock, axis=1), use_container_width=True, hide_index=True)
        st.caption("🔴 Rojo = stock bajo o agotado")

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
    # Borrar despues de probar