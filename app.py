# -*- coding: utf-8 -*-
import os
from dotenv import load_dotenv  # <-- Nueva librería
import streamlit as st
import psycopg2
import pandas as pd
from io import BytesIO
from datetime import datetime

# Esto carga los datos de tu archivo .env
load_dotenv()
def get_conn():
    return psycopg2.connect(
        host=os.getenv("DB_HOST"),
        database=os.getenv("DB_NAME"),
        user=os.getenv("DB_USER"),
        password=os.getenv("DB_PASS")
    )
# ── Generar PDF de equipos ───
def generar_pdf_equipos(df_mostrar):
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

    # Titulo
    titulo = Paragraph(
        f"<b>Inventario de Equipos de Computo — IMJ</b><br/>"
        f"<font size=9>Generado: {datetime.now().strftime('%d/%m/%Y %H:%M')}</font>",
        styles["Title"]
    )
    elements.append(titulo)
    elements.append(Spacer(1, 12))

    # Encabezados y datos
    columnas = ["ID", "Nombre", "Ap. Paterno", "Equipo", "Marca", "Modelo", "Serie", "MAC", "Estatus"]
    data = [columnas]
    for _, row in df.iterrows():
        data.append([str(row.get(c, "") or "") for c in columnas])

    col_widths = [30, 70, 80, 60, 55, 60, 75, 110, 65]

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

    # Pie de pagina con total
    elements.append(Spacer(1, 10))
    elements.append(Paragraph(f"Total de equipos: <b>{len(df)}</b>", styles["Normal"]))

    doc.build(elements)
    buffer.seek(0)
    return buffer


# ── Configuracion de la pagina ──────
st.set_page_config(page_title="Sistema IMJ", layout="wide")
st.title("Sistema de Inventario IMJ")
st.markdown("---")

# ── Menu lateral ──────────────────────────────────────────────
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

    # -- Ver usuarios --
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
        st.dataframe(df, use_container_width=True)
        st.caption(f"Total: {len(df)} usuarios")

    # -- Editar usuario --
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
        opciones_u = {
            f"{u[2]}, {u[1]}  (ID {u[0]})": u for u in usuarios
        }
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
                        UPDATE usuarios
                        SET nombre            = %s,
                            apellido_paterno  = %s,
                            apellido_materno  = %s,
                            puesto            = %s,
                            correo            = %s,
                            id_departamento   = %s
                        WHERE id_usuario = %s
                    """, (nuevo_nombre, nuevo_ap_pat, nuevo_ap_mat,
                          nuevo_puesto, nuevo_correo, nuevo_id_dep, id_usuario))
                    conn.commit()
                    st.session_state["usuario_guardado"] = f"{nuevo_nombre} {nuevo_ap_pat}"
                    st.rerun()
                else:
                    st.warning("Nombre y Apellido Paterno son obligatorios.")

    # -- Alta --
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

    # -- Baja --
    elif accion == "Baja de usuario":
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        sel = st.selectbox("Selecciona el usuario a dar de baja", list(opciones.keys()))
        if st.button("🗑️ Dar de Baja"):
            cur.execute("DELETE FROM usuarios WHERE id_usuario = %s", (opciones[sel],))
            conn.commit()
            st.success(f"✅ Usuario {sel} dado de baja.")
            st.rerun()

    # -- Traspaso --
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

    cur.close()
    conn.close()

# ════════════════════════════════════════
# EQUIPOS DE COMPUTO
# ════════════════════════════════════════
elif menu == "💻 Equipos de Computo":
    st.subheader("💻 Equipos de Computo")

    accion = st.radio("Accion", [
        "Ver equipos",
        "Editar equipo",
        "Cambiar estatus",
        "Reasignar equipo"
    ])

    conn = get_conn()
    cur  = conn.cursor()

    # ── Ver equipos ──
    if accion == "Ver equipos":
        filtro = st.selectbox("Filtrar por estatus", ["Todos", "activo", "dañado", "en reparacion"])

        if filtro == "Todos":
            cur.execute("""
                SELECT c.id_computo, u.nombre, u.apellido_paterno, c.nombre_equipo,
                       c.marca, c.modelo, c.serie, c.mac_address, c.estatus
                FROM computo c
                LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                ORDER BY u.apellido_paterno
            """)
        else:
            cur.execute("""
                SELECT c.id_computo, u.nombre, u.apellido_paterno, c.nombre_equipo,
                       c.marca, c.modelo, c.serie, c.mac_address, c.estatus
                FROM computo c
                LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.estatus = %s
                ORDER BY u.apellido_paterno
            """, (filtro,))

        datos = cur.fetchall()
        columnas = ["ID", "Nombre", "Ap. Paterno", "Equipo", "Marca", "Modelo", "Serie", "MAC", "Estatus"]
        df = pd.DataFrame(datos, columns=columnas)

        busqueda = st.text_input("Buscar en cualquier campo", placeholder="Ej: Adriana, DELL, HLLGW93...")
        if busqueda:
            mask = df.apply(lambda col: col.astype(str).str.contains(busqueda, case=False, na=False)).any(axis=1)
            df_mostrar = df[mask].reset_index(drop=True)
        else:
            df_mostrar = df.reset_index(drop=True)

        st.dataframe(df_mostrar, use_container_width=True)
        if busqueda:
            st.caption(f"Mostrando: {len(df_mostrar)} de {len(df)} equipos (filtrado por: '{busqueda}')")
        else:
            st.caption(f"Total: {len(df_mostrar)} equipos")

        # Descargas - siempre exportan lo que se ve en pantalla
        st.markdown("#### Descargar inventario")
        if busqueda:
            st.info(f"Se exportaran solo los {len(df_mostrar)} registros filtrados por '{busqueda}'.")

        col_pdf, col_csv, _ = st.columns([1, 1, 2])

        csv_bytes = df_mostrar.to_csv(index=False, encoding="utf-8-sig").encode("utf-8-sig")

        try:
            pdf_bytes = generar_pdf_equipos(df_mostrar).read()
        except ImportError:
            pdf_bytes = None

        with col_pdf:
            if pdf_bytes:
                st.download_button(
                    label="Descargar PDF",
                    data=pdf_bytes,
                    file_name=f"equipos_IMJ_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                    mime="application/pdf",
                    use_container_width=True
                )
            else:
                st.error("Instala reportlab: pip install reportlab")

        with col_csv:
            st.download_button(
                label="Descargar CSV/Excel",
                data=csv_bytes,
                file_name=f"equipos_IMJ_{datetime.now().strftime('%Y%m%d_%H%M')}.csv",
                mime="text/csv",
                use_container_width=True
            )

    # ── Editar equipo ──
    elif accion == "Editar equipo":

        # Mostrar mensaje de éxito si viene de un guardado anterior
        if st.session_state.get("equipo_guardado"):
            nombre_guardado = st.session_state.pop("equipo_guardado")
            st.success(f"✅ **{nombre_guardado}** fue actualizado correctamente.")

        cur.execute("""
            SELECT c.id_computo, u.nombre, u.apellido_paterno,
                   c.nombre_equipo, c.marca, c.modelo, c.serie, c.mac_address, c.estatus
            FROM computo c
            LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        equipos = cur.fetchall()

        opciones_eq = {
            f"{e[1]} {e[2]} — {e[3]} (Serie: {e[6]})": e
            for e in equipos
        }
        sel = st.selectbox("Selecciona el equipo a editar", list(opciones_eq.keys()))
        equipo = opciones_eq[sel]

        id_computo = equipo[0]
        st.info(f"Editando equipo ID: **{id_computo}** | Usuario: **{equipo[1]} {equipo[2]}**")

        with st.form("editar_equipo"):
            col1, col2 = st.columns(2)
            nuevo_nombre  = col1.text_input("Nombre del equipo", value=equipo[3] or "")
            nueva_marca   = col2.text_input("Marca",             value=equipo[4] or "")

            col3, col4 = st.columns(2)
            nuevo_modelo  = col3.text_input("Modelo",            value=equipo[5] or "")
            nueva_serie   = col4.text_input("Serie",             value=equipo[6] or "")

            col5, col6 = st.columns(2)
            nueva_mac     = col5.text_input("MAC Address",       value=equipo[7] or "")
            estatus_opts  = ["activo", "dañado", "en reparacion"]
            idx_estatus   = estatus_opts.index(equipo[8]) if equipo[8] in estatus_opts else 0
            nuevo_estatus = col6.selectbox("Estatus", estatus_opts, index=idx_estatus)

            if st.form_submit_button("💾 Guardar cambios"):
                cur.execute("""
                    UPDATE computo
                    SET nombre_equipo = %s,
                        marca         = %s,
                        modelo        = %s,
                        serie         = %s,
                        mac_address   = %s,
                        estatus       = %s
                    WHERE id_computo = %s
                """, (nuevo_nombre, nueva_marca, nuevo_modelo,
                      nueva_serie, nueva_mac, nuevo_estatus, id_computo))
                conn.commit()
                st.session_state["equipo_guardado"] = f"{equipo[1]} {equipo[2]} — {nuevo_nombre}"
                st.rerun()

    # ── Cambiar estatus ──
    elif accion == "Cambiar estatus":
        cur.execute("""
            SELECT c.id_computo, u.nombre, u.apellido_paterno, c.nombre_equipo, c.estatus
            FROM computo c
            LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        equipos = cur.fetchall()
        opciones = {f"{e[1]} {e[2]} — {e[3]}": (e[0], e[4]) for e in equipos}
        sel = st.selectbox("Selecciona el equipo", list(opciones.keys()))
        id_computo, estatus_actual = opciones[sel]
        st.info(f"Estatus actual: **{estatus_actual}**")
        nuevo_estatus = st.selectbox("Nuevo estatus", ["activo", "dañado", "en reparacion"])
        if st.button("✅ Actualizar estatus"):
            cur.execute("UPDATE computo SET estatus = %s WHERE id_computo = %s",
                        (nuevo_estatus, id_computo))
            conn.commit()
            st.success(f"✅ Estatus actualizado a '{nuevo_estatus}'.")
            st.rerun()

    # ── Reasignar equipo ──
    elif accion == "Reasignar equipo":
        cur.execute("""
            SELECT c.id_computo, u.nombre, u.apellido_paterno, c.nombre_equipo
            FROM computo c
            LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        equipos = cur.fetchall()
        opciones_eq = {f"{e[1]} {e[2]} — {e[3]}": e[0] for e in equipos}
        sel_eq = st.selectbox("Selecciona el equipo a reasignar", list(opciones_eq.keys()))
        cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
        usuarios = cur.fetchall()
        opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
        sel_usr = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
        if st.button("🔄 Reasignar"):
            cur.execute("UPDATE computo SET id_usuario = %s WHERE id_computo = %s",
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
            FROM telefonos t
            LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        datos = cur.fetchall()
        df = pd.DataFrame(datos, columns=["ID", "Numero General", "Extension", "Nombre", "Ap. Paterno"])
        st.dataframe(df, use_container_width=True)
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
                    cur.execute("""
                        INSERT INTO telefonos (numero_general, extension, id_usuario)
                        VALUES (%s, %s, %s)
                    """, (numero, extension, opciones_usr[sel_usr]))
                    conn.commit()
                    st.success("✅ Telefono agregado correctamente.")
                else:
                    st.warning("Por favor escribe la extension.")

    elif accion == "Reasignar telefono":
        cur.execute("""
            SELECT t.id_telefono, t.extension, u.nombre, u.apellido_paterno
            FROM telefonos t
            LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
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
            FROM impresoras i
            LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
            ORDER BY u.apellido_paterno
        """)
        datos = cur.fetchall()
        df = pd.DataFrame(datos, columns=["ID","Marca","Modelo","Serie","IP","Firmware","Nombre","Ap. Paterno"])
        st.dataframe(df, use_container_width=True)
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
            FROM impresoras i
            LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
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

        st.dataframe(df.style.apply(resaltar_stock, axis=1), use_container_width=True)
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