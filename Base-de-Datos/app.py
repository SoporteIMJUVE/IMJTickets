# -*- coding: utf-8 -*-
import os
import re
from dotenv import load_dotenv
import streamlit as st
import psycopg2
import pandas as pd
from io import BytesIO
from datetime import datetime

load_dotenv()

def _crear_conexion():
    return psycopg2.connect(
        host=os.getenv("DB_HOST"),
        port=os.getenv("DB_PORT", "5432"),
        database=os.getenv("DB_NAME"),
        user=os.getenv("DB_USER"),
        password=os.getenv("DB_PASS")
    )

@st.cache_resource
def _conn_cache():
    """Contenedor mutable para la conexion cached."""
    return {"conn": _crear_conexion()}

def get_conn():
    """Devuelve la conexion activa; la renueva si esta caida."""
    cache = _conn_cache()
    conn  = cache["conn"]
    try:
        # ping liviano para verificar que sigue viva
        conn.cursor().execute("SELECT 1")
    except Exception:
        try:
            conn.close()
        except Exception:
            pass
        cache["conn"] = _crear_conexion()
    return cache["conn"]

def validar_mac(mac):
    if not mac:
        return True
    return bool(re.match(r'^([0-9A-Fa-f]{2}[:\-]){5}[0-9A-Fa-f]{2}$', mac.strip()))

def validar_email(email):
    if not email:
        return True
    return bool(re.match(r'^[^@\s]+@[^@\s]+\.[^@\s]+$', email.strip()))

def validar_ip_format(ip):
    if not ip:
        return True
    parts = ip.strip().split('.')
    return len(parts) == 4 and all(p.isdigit() and 0 <= int(p) <= 255 for p in parts)

def safe_rollback():
    try:
        get_conn().rollback()
    except Exception:
        pass

def generar_excel_formateado(df, nombre_hoja="Datos"):
    from openpyxl import Workbook
    from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
    from openpyxl.utils import get_column_letter

    wb = Workbook()
    ws = wb.active
    ws.title = nombre_hoja

    AZUL    = "1A3C5E"
    CELESTE = "EAF1FB"
    BORDE   = "BFBFBF"

    borde = Border(
        left=Side(style="thin", color=BORDE),
        right=Side(style="thin", color=BORDE),
        top=Side(style="thin", color=BORDE),
        bottom=Side(style="thin", color=BORDE),
    )

    headers = list(df.columns)
    for ci, h in enumerate(headers, 1):
        c = ws.cell(row=1, column=ci, value=h)
        c.font      = Font(bold=True, color="FFFFFF", size=10)
        c.fill      = PatternFill("solid", fgColor=AZUL)
        c.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
        c.border    = borde
    ws.row_dimensions[1].height = 26

    fill_alt = PatternFill("solid", fgColor=CELESTE)
    for ri, (_, row) in enumerate(df.iterrows(), 2):
        for ci, h in enumerate(headers, 1):
            val = row[h]
            if val is None or (isinstance(val, float) and pd.isna(val)):
                val = ""
            c = ws.cell(row=ri, column=ci, value=val)
            c.font      = Font(size=9)
            c.alignment = Alignment(vertical="center")
            c.border    = borde
            if ri % 2 == 0:
                c.fill = fill_alt

    for ci, h in enumerate(headers, 1):
        vals = [str(h)] + [
            str(row[h]) if row[h] is not None and not (isinstance(row[h], float) and pd.isna(row[h])) else ""
            for _, row in df.iterrows()
        ]
        ws.column_dimensions[get_column_letter(ci)].width = min(max(len(v) for v in vals) + 4, 45)

    ws.freeze_panes = "A2"

    buf = BytesIO()
    wb.save(buf)
    buf.seek(0)
    return buf

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


def leer_inventario_datos_xlsx():
    ruta = os.path.join(os.path.dirname(os.path.abspath(__file__)), "datos.xlsx")
    frames = []

    try:
        df = pd.read_excel(ruta, sheet_name="laptop", header=None, skiprows=4)
        df = df.dropna(how="all").reset_index(drop=True)
        df = df[df.iloc[:, 3].notna() & (df.iloc[:, 3].astype(str).str.strip() != "")]
        frames.append(pd.DataFrame({
            "Tipo":        "Laptop",
            "Inventario":  df.iloc[:, 2],
            "Usuario":     df.iloc[:, 3].astype(str).str.strip(),
            "Area":        df.iloc[:, 5],
            "Marca":       df.iloc[:, 6],
            "Modelo":      df.iloc[:, 7],
            "Serie":       df.iloc[:, 8],
            "IPv4 Actual": df.iloc[:, 14],
            "MAC":         df.iloc[:, 15],
            "Responsiva":  df.iloc[:, 16],
        }))
    except Exception:
        pass

    for hoja, skip in [("PC ESPECIALIZADAS", 3), ("PC AVANZADAS", 4)]:
        try:
            df = pd.read_excel(ruta, sheet_name=hoja, header=None, skiprows=skip)
            df = df.dropna(how="all").reset_index(drop=True)
            df = df[df.iloc[:, 3].notna() & (df.iloc[:, 3].astype(str).str.strip() != "")]
            frames.append(pd.DataFrame({
                "Tipo":        "PC " + hoja.split()[1].capitalize(),
                "Inventario":  df.iloc[:, 2],
                "Usuario":     df.iloc[:, 3].astype(str).str.strip(),
                "Area":        df.iloc[:, 5],
                "Marca":       df.iloc[:, 6],
                "Modelo":      df.iloc[:, 7],
                "Serie":       df.iloc[:, 8],
                "IPv4 Actual": df.iloc[:, 18],
                "MAC":         df.iloc[:, 19],
                "Responsiva":  df.iloc[:, 20],
            }))
        except Exception:
            pass

    if not frames:
        return pd.DataFrame(columns=["Tipo","Inventario","Usuario","Area","Marca","Modelo","Serie","IPv4 Actual","MAC","Responsiva"])

    resultado = pd.concat(frames, ignore_index=True)
    for col in resultado.columns:
        resultado[col] = resultado[col].apply(
            lambda v: "" if (v is None or (isinstance(v, float) and pd.isna(v))) else str(v).strip()
        )
    return resultado


def generar_pdf_informe_completo(secciones):
    """secciones: lista de (titulo_seccion, df)"""
    from reportlab.lib.pagesizes import landscape, A4
    from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer, HRFlowable, PageBreak
    from reportlab.lib.styles import ParagraphStyle
    from reportlab.lib import colors
    from reportlab.lib.units import cm

    AZUL       = colors.HexColor("#1A3C5E")
    AZUL_CLARO = colors.HexColor("#EAF1FB")
    NARANJA    = colors.HexColor("#E8A838")
    GRIS_BORDE = colors.HexColor("#BFBFBF")
    PAGE       = landscape(A4)

    buffer = BytesIO()
    doc = SimpleDocTemplate(
        buffer, pagesize=PAGE,
        leftMargin=1.5*cm, rightMargin=1.5*cm,
        topMargin=2*cm, bottomMargin=1.5*cm
    )

    s_main_titulo = ParagraphStyle("mt", fontSize=16, fontName="Helvetica-Bold",
                                    textColor=AZUL, alignment=1, spaceAfter=3)
    s_main_sub    = ParagraphStyle("ms", fontSize=9, fontName="Helvetica",
                                    textColor=colors.HexColor("#555555"), alignment=1, spaceAfter=8)
    s_sec_titulo  = ParagraphStyle("st", fontSize=11, fontName="Helvetica-Bold",
                                    textColor=colors.white, spaceBefore=14, spaceAfter=4,
                                    backColor=AZUL, leftIndent=-6, rightIndent=-6,
                                    borderPadding=(4, 6, 4, 6))
    s_head        = ParagraphStyle("th", fontSize=8, fontName="Helvetica-Bold",
                                    textColor=colors.white, alignment=1)
    s_cell        = ParagraphStyle("td", fontSize=7.5, fontName="Helvetica", leading=10)
    s_total       = ParagraphStyle("tot", fontSize=8, fontName="Helvetica",
                                    textColor=colors.HexColor("#555555"), spaceBefore=3, spaceAfter=8)

    ANCHOS = {
        "Tipo": 52, "Inventario": 48, "Usuario": 108, "Area": 118,
        "Marca": 40, "Modelo": 68, "Serie": 68, "IPv4 Actual": 74,
        "MAC": 82, "Responsiva": 54,
        "Número General": 110, "Extension": 90,
        "IP": 90, "Firmware": 70,
    }

    def tabla_seccion(df):
        COLS = list(df.columns)
        page_w = PAGE[0] - 3*cm
        widths = [ANCHOS.get(c, 0) for c in COLS]
        fijos = sum(w for w in widths if w > 0)
        libres = sum(1 for w in widths if w == 0)
        resto = max((page_w - fijos) / libres, 60) if libres else 0
        col_widths = [w if w > 0 else resto for w in widths]
        header = [Paragraph(c, s_head) for c in COLS]
        data = [header]
        for _, row in df.iterrows():
            data.append([Paragraph(str(row.get(c, "") or ""), s_cell) for c in COLS])
        t = Table(data, colWidths=col_widths, repeatRows=1)
        t.setStyle(TableStyle([
            ("BACKGROUND",    (0, 0), (-1, 0),  AZUL),
            ("TOPPADDING",    (0, 0), (-1, 0),  6),
            ("BOTTOMPADDING", (0, 0), (-1, 0),  6),
            ("ALIGN",         (0, 0), (-1, 0),  "CENTER"),
            ("VALIGN",        (0, 0), (-1, -1), "MIDDLE"),
            ("TOPPADDING",    (0, 1), (-1, -1), 4),
            ("BOTTOMPADDING", (0, 1), (-1, -1), 4),
            ("LEFTPADDING",   (0, 0), (-1, -1), 5),
            ("RIGHTPADDING",  (0, 0), (-1, -1), 5),
            ("ROWBACKGROUNDS",(0, 1), (-1, -1), [colors.white, AZUL_CLARO]),
            ("LINEBELOW",     (0, 0), (-1, 0),  1, AZUL),
            ("GRID",          (0, 0), (-1, -1), 0.3, GRIS_BORDE),
        ]))
        return t

    def pie(canvas, doc):
        canvas.saveState()
        canvas.setFont("Helvetica", 8)
        canvas.setFillColor(colors.HexColor("#888888"))
        canvas.drawRightString(PAGE[0] - 1.5*cm, 0.7*cm, f"Pág. {canvas.getPageNumber()}")
        canvas.restoreState()

    elements = [
        Paragraph("Instituto de la Juventud del Estado de Guerrero — IMJUVE", s_main_titulo),
        Paragraph(f"Informe Completo de Usuarios &nbsp;&nbsp;|&nbsp;&nbsp; {datetime.now().strftime('%d/%m/%Y  %H:%M')}", s_main_sub),
        HRFlowable(width="100%", thickness=2, color=AZUL, spaceAfter=10),
    ]

    for i, (titulo_sec, df) in enumerate(secciones):
        if df.empty:
            continue
        if i > 0:
            elements.append(Spacer(1, 6))
        elements.append(Paragraph(f"  {titulo_sec}", s_sec_titulo))
        elements.append(tabla_seccion(df))
        elements.append(Paragraph(f"Total: <b>{len(df)}</b> registros", s_total))

    doc.build(elements, onFirstPage=pie, onLaterPages=pie)
    buffer.seek(0)
    return buffer


def generar_pdf_usuarios(df):
    from reportlab.lib.pagesizes import landscape, A4
    from reportlab.platypus import SimpleDocTemplate, Table, TableStyle, Paragraph, Spacer, HRFlowable
    from reportlab.lib.styles import ParagraphStyle
    from reportlab.lib import colors
    from reportlab.lib.units import cm

    AZUL       = colors.HexColor("#1A3C5E")
    AZUL_CLARO = colors.HexColor("#EAF1FB")
    GRIS_BORDE = colors.HexColor("#BFBFBF")

    buffer = BytesIO()
    PAGE = landscape(A4)
    doc = SimpleDocTemplate(
        buffer, pagesize=PAGE,
        leftMargin=1.5*cm, rightMargin=1.5*cm,
        topMargin=2*cm, bottomMargin=1.5*cm
    )

    s_titulo = ParagraphStyle("titulo", fontSize=15, fontName="Helvetica-Bold",
                               textColor=AZUL, spaceAfter=3, alignment=1)
    s_sub    = ParagraphStyle("sub", fontSize=9, fontName="Helvetica",
                               textColor=colors.HexColor("#555555"), spaceAfter=6, alignment=1)
    s_total  = ParagraphStyle("total", fontSize=9, fontName="Helvetica",
                               textColor=colors.HexColor("#333333"), spaceBefore=8)
    s_head   = ParagraphStyle("th", fontSize=9, fontName="Helvetica-Bold",
                               textColor=colors.white, alignment=1)
    s_cell   = ParagraphStyle("td", fontSize=8, fontName="Helvetica", leading=11)

    COLS = list(df.columns)
    col_w_map = {
        "Nombre(s)":    95,
        "Ap. Paterno":  95,
        "Ap. Materno":  90,
        "Puesto":       115,
        "Correo":       170,
        "Departamento": 135,
    }
    page_w = PAGE[0] - 3*cm
    col_widths = [col_w_map.get(c, page_w / len(COLS)) for c in COLS]

    header_row = [Paragraph(c, s_head) for c in COLS]
    data = [header_row]
    for _, row in df.iterrows():
        data.append([Paragraph(str(row.get(c, "") or ""), s_cell) for c in COLS])

    tabla = Table(data, colWidths=col_widths, repeatRows=1)
    tabla.setStyle(TableStyle([
        ("BACKGROUND",    (0, 0), (-1, 0),  AZUL),
        ("TOPPADDING",    (0, 0), (-1, 0),  8),
        ("BOTTOMPADDING", (0, 0), (-1, 0),  8),
        ("ALIGN",         (0, 0), (-1, 0),  "CENTER"),
        ("VALIGN",        (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING",    (0, 1), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 1), (-1, -1), 5),
        ("LEFTPADDING",   (0, 0), (-1, -1), 6),
        ("RIGHTPADDING",  (0, 0), (-1, -1), 6),
        ("ROWBACKGROUNDS",(0, 1), (-1, -1), [colors.white, AZUL_CLARO]),
        ("LINEBELOW",     (0, 0), (-1, 0),  1.2, AZUL),
        ("GRID",          (0, 0), (-1, -1), 0.3, GRIS_BORDE),
        ("LINEBELOW",     (0, -1), (-1, -1), 1, AZUL),
    ]))

    def pie_pagina(canvas, doc):
        canvas.saveState()
        canvas.setFont("Helvetica", 8)
        canvas.setFillColor(colors.HexColor("#888888"))
        canvas.drawRightString(PAGE[0] - 1.5*cm, 0.7*cm, f"Pág. {canvas.getPageNumber()}")
        canvas.restoreState()

    elements = [
        Paragraph("Instituto de la Juventud del Estado de Guerrero — IMJUVE", s_titulo),
        Paragraph(f"Inventario de Usuarios &nbsp;&nbsp;|&nbsp;&nbsp; Generado: {datetime.now().strftime('%d/%m/%Y  %H:%M')}", s_sub),
        HRFlowable(width="100%", thickness=2, color=AZUL, spaceAfter=10),
        tabla,
        Spacer(1, 8),
        Paragraph(f"Total de usuarios: <b>{len(df)}</b>", s_total),
    ]

    doc.build(elements, onFirstPage=pie_pagina, onLaterPages=pie_pagina)
    buffer.seek(0)
    return buffer


def generar_pdf_equipos_detalle(df, usuario_nombre=None):
    titulo = f"Equipos de Computo — {usuario_nombre}" if usuario_nombre else "Inventario de Equipos de Computo"
    col_widths = [80, 80, 100, 120, 160, 70]
    return generar_pdf_generico(df, titulo, col_widths)


def generar_pdf_equipos_resumen(df):
    col_widths = [140, 160, 180, 50]
    return generar_pdf_generico(df, "Resumen de Equipos de Computo", col_widths)


def generar_pdf_resguardo(e_o_lista):
    """PDF de resguardo oficial (una página completa por equipo), ordenado por área."""
    from reportlab.lib.pagesizes import A4
    from reportlab.lib import colors
    from reportlab.lib.units import cm
    from reportlab.platypus import (SimpleDocTemplate, Table, TableStyle, Paragraph,
                                    Spacer, PageBreak, KeepInFrame)
    from reportlab.lib.styles import ParagraphStyle
    from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY
    from reportlab.pdfbase import pdfmetrics
    from reportlab.pdfbase.ttfonts import TTFont

    _base_dir  = os.path.dirname(os.path.abspath(__file__))
    _logo_path = os.path.join(_base_dir, "LOGO.png")

    # Tipografía oficial: Noto Sans (con respaldo a Helvetica si faltan los TTF)
    try:
        if "NotoSans" not in pdfmetrics.getRegisteredFontNames():
            _fonts = os.path.join(_base_dir, "fonts")
            pdfmetrics.registerFont(TTFont("NotoSans",      os.path.join(_fonts, "NotoSans-Regular.ttf")))
            pdfmetrics.registerFont(TTFont("NotoSans-Bold", os.path.join(_fonts, "NotoSans-Bold.ttf")))
        F_REG, F_BOLD = "NotoSans", "NotoSans-Bold"
    except Exception:
        F_REG, F_BOLD = "Helvetica", "Helvetica-Bold"

    equipos = [e_o_lista] if isinstance(e_o_lista, dict) else sorted(
        list(e_o_lista),
        key=lambda x: (x.get("area") or "", x.get("tipo") or "", x.get("nombre_usuario") or "")
    )

    buf = BytesIO()
    # topMargin reserva espacio para el membrete (logo + filete) y bottomMargin para el pie institucional
    doc = SimpleDocTemplate(buf, pagesize=A4,
                            leftMargin=1.5*cm, rightMargin=1.5*cm,
                            topMargin=2.9*cm, bottomMargin=2.9*cm)

    PAGE_W, PAGE_H = A4
    guinda = colors.HexColor("#691C32")      # color institucional gob.mx
    oro    = colors.HexColor("#BC955C")
    gris   = colors.HexColor("#555555")

    def _membrete(canvas, _doc):
        """Membrete institucional: logo y filete arriba; pie con datos de contacto abajo."""
        canvas.saveState()
        mx = 1.5 * cm                                    # margen lateral

        # ── Encabezado ──────────────────────────────────────────────────────
        if os.path.exists(_logo_path):
            lw = 4.6 * cm
            lh = lw * 250.0 / 996.0                      # proporción original de LOGO.png
            canvas.drawImage(_logo_path, mx, PAGE_H - 0.8*cm - lh,
                             width=lw, height=lh, preserveAspectRatio=True, mask='auto')
        canvas.setFillColor(guinda)
        canvas.setFont(F_BOLD, 10)
        canvas.drawRightString(PAGE_W - mx, PAGE_H - 1.25*cm, "INSTITUTO MEXICANO DE LA JUVENTUD")
        canvas.setFillColor(gris)
        canvas.setFont(F_REG, 8.5)
        canvas.drawRightString(PAGE_W - mx, PAGE_H - 1.65*cm, "Subdirección de Sistemas")

        y_sup = PAGE_H - 2.35*cm                          # filete doble bajo el encabezado
        canvas.setStrokeColor(guinda); canvas.setLineWidth(1.8)
        canvas.line(mx, y_sup, PAGE_W - mx, y_sup)
        canvas.setStrokeColor(oro); canvas.setLineWidth(0.7)
        canvas.line(mx, y_sup - 0.12*cm, PAGE_W - mx, y_sup - 0.12*cm)

        # ── Pie de página ────────────────────────────────────────────────────
        y_pie = 2.35 * cm                                 # filete doble sobre el pie
        canvas.setStrokeColor(oro); canvas.setLineWidth(0.7)
        canvas.line(mx, y_pie + 0.12*cm, PAGE_W - mx, y_pie + 0.12*cm)
        canvas.setStrokeColor(guinda); canvas.setLineWidth(1.8)
        canvas.line(mx, y_pie, PAGE_W - mx, y_pie)

        cx = PAGE_W / 2
        canvas.setFillColor(gris)
        canvas.setFont(F_REG, 8)
        canvas.drawCentredString(cx, y_pie - 0.50*cm,
            "Serapio Rendón 76, Col. San Rafael, C.P. 06470, Alcaldía Cuauhtémoc, CDMX.")
        canvas.drawCentredString(cx, y_pie - 0.88*cm,
            "Tel: (55) 1500 1300   ·   www.gob.mx/imjuve")
        canvas.setFillColor(guinda)
        canvas.setFont(F_BOLD, 8)
        canvas.drawCentredString(cx, y_pie - 1.28*cm, "Subdirección de Sistemas")
        canvas.restoreState()

    azul   = guinda                                      # color de encabezados de sección
    blanco = colors.white
    W      = 18.0 * cm
    CW     = [4.0*cm, 5.0*cm, 4.0*cm, 5.0*cm]

    s_titulo = ParagraphStyle("t",  fontSize=14, fontName=F_BOLD,
                               alignment=TA_CENTER, textColor=azul, spaceAfter=2)
    s_sub    = ParagraphStyle("s",  fontSize=9,  fontName=F_REG,
                               alignment=TA_CENTER, textColor=colors.HexColor("#555555"), spaceAfter=2)
    s_sec    = ParagraphStyle("sc", fontSize=10, fontName=F_BOLD,
                               textColor=blanco, alignment=TA_CENTER)
    s_lbl    = ParagraphStyle("lb", fontSize=10, fontName=F_BOLD,  leading=13)
    s_val    = ParagraphStyle("vl", fontSize=10, fontName=F_REG,   leading=13)
    s_fw     = ParagraphStyle("fw", fontSize=10, fontName=F_BOLD,
                               textColor=blanco, alignment=TA_CENTER)
    s_fc     = ParagraphStyle("fc", fontSize=9.5, fontName=F_REG,  leading=13)
    s_leg    = ParagraphStyle("lg", fontSize=9.5, fontName=F_REG,
                               alignment=TA_JUSTIFY, leading=14)

    def V(t):
        v = str(t).strip() if t is not None else ""
        return Paragraph(v or "—", s_val)

    def L(t): return Paragraph(str(t or ""), s_lbl)
    fecha_str = datetime.now().strftime("%d-%m-%Y")

    def sec_hdr(txt):
        t = Table([[Paragraph(txt, s_sec)]], colWidths=[W])
        t.setStyle(TableStyle([
            ("BACKGROUND",    (0, 0), (-1, -1), azul),
            ("TOPPADDING",    (0, 0), (-1, -1), 5),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
        ]))
        return t

    def tabla2(filas):
        """Tabla con 2 pares (lbl, val) por fila; fondo blanco con rejilla gris."""
        data = [[L(l1), V(v1), L(l2), V(v2)] for l1, v1, l2, v2 in filas]
        t = Table(data, colWidths=CW)
        t.setStyle(TableStyle([
            ("BACKGROUND",    (0, 0), (-1, -1), colors.white),
            ("GRID",          (0, 0), (-1, -1), 0.3, colors.HexColor("#C0C0C0")),
            ("VALIGN",        (0, 0), (-1, -1), "MIDDLE"),
            ("TOPPADDING",    (0, 0), (-1, -1), 5),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
            ("LEFTPADDING",   (0, 0), (-1, -1), 6),
            ("RIGHTPADDING",  (0, 0), (-1, -1), 4),
        ]))
        return t

    def bloque(e):
        tipo = e.get("tipo", "Laptop")
        out  = []

        out.append(Paragraph("RESGUARDO DE EQUIPO DE CÓMPUTO", s_titulo))
        out.append(Paragraph(
            "Instituto Mexicano de la Juventud &nbsp;|&nbsp; "
            f"Contrato No. IMJ-ITP-018-2021-CM-006 &nbsp;|&nbsp; Fecha: {fecha_str}", s_sub
        ))
        out.append(Spacer(1, 10))

        # ── Responsable ────────────────────────────────────────────────────
        out.append(sec_hdr("DATOS DEL RESPONSABLE"))
        out.append(tabla2([
            ("Nombre:",            e.get("nombre_usuario"),  "Área / Dirección:",  e.get("area")),
            ("Perfil de usuario:", e.get("perfil"),          "",                   None),
        ]))
        out.append(Spacer(1, 10))

        # ── Equipo principal ───────────────────────────────────────────────
        out.append(sec_hdr("EQUIPO PRINCIPAL"))
        out.append(tabla2([
            ("Tipo de equipo:",  e.get("tipo"),      "Nombre del equipo:",  e.get("nombre_equipo")),
            ("Marca:",           e.get("cpu_marca"), "Modelo:",             e.get("cpu_modelo")),
            ("N° de serie:",     e.get("cpu_serie"), "MAC Address:",        e.get("mac")),
        ]))
        out.append(Spacer(1, 10))

        # ── Accesorios ─────────────────────────────────────────────────────
        out.append(sec_hdr("ACCESORIOS Y PERIFÉRICOS"))
        if tipo == "Laptop":
            acc = [
                ("Serie cargador:",   e.get("cargador_serie"), "Docking — Marca:",   e.get("docking_marca")),
                ("Docking — Modelo:", e.get("docking_modelo"), "Docking — Serie:",   e.get("docking_serie")),
                ("Candado:",          e.get("candado"),         "",                   None),
            ]
        else:
            acc = [
                ("Teclado — Serie:",   e.get("teclado_serie"),  "Mouse — Serie:",       e.get("mouse_serie")),
                ("Monitor — Marca:",   e.get("monitor_marca"),  "Monitor — Modelo:",    e.get("monitor_modelo")),
                ("Monitor — Serie:",   e.get("monitor_serie"),  "No-Break — Marca:",    e.get("nobreak_marca")),
                ("No-Break — Modelo:", e.get("nobreak_modelo"), "No-Break — Serie:",    e.get("nobreak_serie")),
            ]
            if tipo == "PC Especializada":
                acc.append(("IPv4 Actual:", e.get("ipv4_actual"), "", None))
        out.append(tabla2(acc))

        obs = e.get("observaciones")
        if obs:
            out.append(Spacer(1, 10))
            out.append(sec_hdr("OBSERVACIONES"))
            out.append(tabla2([("Observaciones:", obs, "", None)]))

        out.append(Spacer(1, 14))

        # ── Firmas ─────────────────────────────────────────────────────────
        firma_data = [
            [Paragraph("CONFORMIDAD DEL RESPONSABLE", s_fw),
             Paragraph("SISTEMAS", s_fw)],
            [Paragraph(f"Nombre: {e.get('nombre_usuario') or '___________________________'}", s_fc),
             Paragraph("Nombre: Erick de Ángel Lara Hernández", s_fc)],
            [Paragraph("", s_fc),
             Paragraph("Cargo: Subdirector de Sistemas", s_fc)],
            [Spacer(1, 55), Spacer(1, 55)],
            [Paragraph("Firma: _______________________", s_fc),
             Paragraph("Firma: _______________________", s_fc)],
        ]
        t_f = Table(firma_data, colWidths=[W / 2, W / 2])
        t_f.setStyle(TableStyle([
            ("BACKGROUND",    (0, 0), (-1,  0), azul),
            ("BACKGROUND",    (0, 1), (-1, -1), colors.white),
            ("GRID",          (0, 0), (-1, -1), 0.4, colors.HexColor("#BBBBBB")),
            ("ALIGN",         (0, 0), (-1,  0), "CENTER"),
            ("ALIGN",         (0, 1), (-1, -1), "LEFT"),
            ("VALIGN",        (0, 0), (-1, -1), "MIDDLE"),
            ("TOPPADDING",    (0, 0), (-1, -1), 6),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
            ("LEFTPADDING",   (0, 1), (-1, -1), 8),
        ]))
        out.append(t_f)

        # ── Respaldo de información ────────────────────────────────────────
        out.append(Spacer(1, 14))
        out.append(sec_hdr("RESPALDO DE INFORMACIÓN"))
        leyenda = ("Se hace constar que al responsable del equipo se le explicó el procedimiento "
                   "para realizar el respaldo y resguardo de su información, quedando bajo su "
                   "responsabilidad la ejecución periódica del mismo, lo cual manifiesta de "
                   "conformidad mediante su firma.")
        t_leg = Table([
            [Paragraph(leyenda, s_leg)],
            [Spacer(1, 40)],
            [Paragraph(f"Nombre y firma de conformidad: "
                       f"{e.get('nombre_usuario') or '___________________________'}"
                       " — Firma: _______________________", s_fc)],
        ], colWidths=[W])
        t_leg.setStyle(TableStyle([
            ("BACKGROUND",    (0, 0), (-1, -1), colors.white),
            ("BOX",           (0, 0), (-1, -1), 0.4, colors.HexColor("#BBBBBB")),
            ("TOPPADDING",    (0, 0), (-1, -1), 7),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
            ("LEFTPADDING",   (0, 0), (-1, -1), 8),
            ("RIGHTPADDING",  (0, 0), (-1, -1), 8),
        ]))
        out.append(t_leg)
        return out

    elements = []
    for i, eq in enumerate(equipos):
        if i > 0:
            elements.append(PageBreak())
        # KeepInFrame en modo 'shrink': si el contenido excede la página, se reduce
        # para que cada equipo ocupe siempre una sola hoja.
        elements.append(KeepInFrame(W, doc.height, bloque(eq), mode="shrink"))

    doc.build(elements, onFirstPage=_membrete, onLaterPages=_membrete)
    buf.seek(0)
    return buf


def generar_zip_resguardos_por_area(equipos):
    """ZIP con un PDF por área; cada archivo contiene todos los resguardos de esa área."""
    import zipfile
    from collections import defaultdict

    by_area = defaultdict(list)
    for e in sorted(equipos,
                    key=lambda x: (x.get("area") or "Sin área",
                                   x.get("tipo") or "",
                                   x.get("nombre_usuario") or "")):
        by_area[e.get("area") or "Sin área"].append(e)

    zip_buf = BytesIO()
    with zipfile.ZipFile(zip_buf, "w", zipfile.ZIP_DEFLATED) as zf:
        for area in sorted(by_area):
            pdf_buf = generar_pdf_resguardo(by_area[area])
            nombre_safe = "".join(
                c for c in area if c.isalnum() or c in " _-"
            )[:50].strip().replace(" ", "_")
            zf.writestr(f"Resguardos_{nombre_safe}.pdf", pdf_buf.read())

    zip_buf.seek(0)
    return zip_buf


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

    busqueda_global = st.text_input("🔍 Busqueda global", placeholder="Buscar usuario, IP, serie, extension, insumo...")

    if busqueda_global:
        try:
            conn = get_conn()
            cur = conn.cursor()
            term = f"%{busqueda_global}%"
            resultados = []

            cur.execute("""
                SELECT 'Usuario' as tipo, nombre || ' ' || apellido_paterno as descripcion,
                       COALESCE(puesto,'') as detalle, 'Usuarios' as modulo
                FROM usuarios WHERE activo = true
                AND (nombre ILIKE %s OR apellido_paterno ILIKE %s OR apellido_materno ILIKE %s OR correo ILIKE %s)
            """, (term, term, term, term))
            for r in cur.fetchall():
                resultados.append({"Tipo": r[0], "Descripcion": r[1], "Detalle": r[2], "Modulo": r[3]})

            cur.execute("""
                SELECT 'IP' as tipo, ip as descripcion,
                       COALESCE(usuario,'') || ' | ' || COALESCE(estatus,'') as detalle,
                       'Direccionamiento IP' as modulo
                FROM inventario_ips_completo
                WHERE ip ILIKE %s OR usuario ILIKE %s OR mac ILIKE %s
            """, (term, term, term))
            for r in cur.fetchall():
                resultados.append({"Tipo": r[0], "Descripcion": r[1], "Detalle": r[2], "Modulo": r[3]})

            cur.execute("""
                SELECT 'Equipo' as tipo, COALESCE(nombre_equipo, cpu_modelo) as descripcion,
                       tipo || ' | ' || COALESCE(cpu_marca,'') || ' ' || COALESCE(cpu_modelo,'') || ' | Serie: ' || COALESCE(cpu_serie,'') as detalle,
                       'Equipos de Computo' as modulo
                FROM inventario_equipos
                WHERE nombre_equipo ILIKE %s OR cpu_serie ILIKE %s OR mac ILIKE %s
                  OR nombre_usuario ILIKE %s OR num_inventario ILIKE %s
            """, (term, term, term, term, term))
            for r in cur.fetchall():
                resultados.append({"Tipo": r[0], "Descripcion": r[1], "Detalle": r[2], "Modulo": r[3]})

            cur.execute("""
                SELECT 'Telefono' as tipo, extension::text as descripcion,
                    COALESCE(numero_general,'') as detalle, 'Telefonos' as modulo
                FROM telefonos WHERE extension::text ILIKE %s OR numero_general ILIKE %s
            """, (term, term))
            for r in cur.fetchall():
                resultados.append({"Tipo": r[0], "Descripcion": r[1], "Detalle": r[2], "Modulo": r[3]})

            cur.execute("""
                SELECT 'Impresora' as tipo, COALESCE(marca,'') || ' ' || COALESCE(modelo,'') as descripcion,
                    'Serie: ' || COALESCE(serie,'') || ' | IP: ' || COALESCE(ip_address::text,'') as detalle,
                    'Impresoras' as modulo
                FROM impresoras WHERE marca ILIKE %s OR modelo ILIKE %s OR serie ILIKE %s
            """, (term, term, term))
            for r in cur.fetchall():
                resultados.append({"Tipo": r[0], "Descripcion": r[1], "Detalle": r[2], "Modulo": r[3]})

            cur.execute("""
                SELECT 'Insumo' as tipo, nombre_insumo as descripcion,
                    'Stock: ' || stock_actual::text as detalle, 'Insumos' as modulo
                FROM insumos WHERE nombre_insumo ILIKE %s OR numero_parte ILIKE %s
            """, (term, term))
            for r in cur.fetchall():
                resultados.append({"Tipo": r[0], "Descripcion": r[1], "Detalle": r[2], "Modulo": r[3]})

            cur.close()

            if resultados:
                st.success(f"{len(resultados)} resultados para '{busqueda_global}'")
                st.dataframe(pd.DataFrame(resultados), use_container_width=True, hide_index=True)
            else:
                st.info(f"Sin resultados para '{busqueda_global}'.")
        except Exception as e:
            safe_rollback()
            st.error(f"Error en busqueda: {e}")
    else:
        try:
            conn = get_conn()
            cur  = conn.cursor()

            cur.execute("SELECT COUNT(*) FROM usuarios WHERE activo = true")
            total_usuarios = cur.fetchone()[0]
            cur.execute("SELECT COUNT(*) FROM inventario_equipos")
            total_computo = cur.fetchone()[0]
            cur.execute("SELECT COUNT(*) FROM impresoras")
            total_impresoras = cur.fetchone()[0]
            cur.execute("SELECT COUNT(*) FILTER (WHERE tipo='Laptop') FROM inventario_equipos")
            equipos_activos = cur.fetchone()[0]
            cur.execute("SELECT COUNT(*) FILTER (WHERE tipo IN ('PC Avanzada','PC Especializada')) FROM inventario_equipos")
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

            st.markdown("#### Resumen general")
            col1, col2, col3, col4 = st.columns(4)
            col1.metric("👤 Usuarios activos",  total_usuarios)
            col2.metric("💻 Equipos",           total_computo)
            col3.metric("🖨️ Impresoras",        total_impresoras)
            col4.metric("📦 Insumos stock bajo", insumos_bajos,
                        delta=f"-{insumos_bajos}" if insumos_bajos > 0 else None,
                        delta_color="inverse")

            st.markdown("#### Equipos e IPs")
            col5, col6, col7, col8 = st.columns(4)
            col5.metric("💻 Laptops",        equipos_activos)
            col6.metric("🖥️ PCs (Av + Esp)", equipos_danados)
            col7.metric("🟢 IPs libres",   ips_libres)
            col8.metric("🔴 IPs ocupadas", ips_ocupadas)

            st.markdown("#### Usuarios por departamento")
            st.bar_chart(df_deptos.set_index("Departamento"), use_container_width=True, height=280)

        except Exception as e:
            safe_rollback()
            st.error(f"Error al conectar con la BD: {e}")

# ════════════════════════════════════════
# USUARIOS
# ════════════════════════════════════════
elif menu == "👤 Usuarios":
    st.subheader("👤 Gestion de Usuarios")
    tab1, tab2, tab3, tab4 = st.tabs(["Gestionar", "Alta", "Baja", "Reactivar"])

    with tab1:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")
            deptos       = cur.fetchall()
            deptos_map   = {d[1]: d[0] for d in deptos}
            deptos_names = [d[1] for d in deptos]

            mostrar_inactivos = st.checkbox("Mostrar usuarios inactivos (dados de baja)")
            col_f1, col_f2, col_f3 = st.columns([1.5, 1, 2])
            depto_f    = col_f1.selectbox("Departamento", ["Todos"] + deptos_names, key="ver_u_depto")
            puesto_f   = col_f2.text_input("Puesto", placeholder="Ej: Jefe...", key="ver_u_puesto")
            busqueda_u = col_f3.text_input("Buscar", placeholder="Nombre, apellido, correo...", key="ver_u_busq")

            cur.execute("""
                SELECT u.id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno,
                    u.puesto, u.correo, d.nombre as departamento
                FROM usuarios u
                LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
                WHERE u.activo = %s
                ORDER BY u.apellido_paterno
            """, (not mostrar_inactivos,))
            rows = cur.fetchall()
            cur.close()

            df = pd.DataFrame(rows, columns=[
                "id_usuario", "nombre", "ap_paterno", "ap_materno",
                "puesto", "correo", "departamento"
            ])

            if depto_f != "Todos":
                df = df[df["departamento"] == depto_f].reset_index(drop=True)
            if puesto_f:
                df = df[df["puesto"].str.contains(puesto_f, case=False, na=False)].reset_index(drop=True)
            if busqueda_u:
                mask = df[["nombre", "ap_paterno", "ap_materno", "correo"]].apply(
                    lambda col: col.astype(str).str.contains(busqueda_u, case=False, na=False)
                ).any(axis=1)
                df = df[mask].reset_index(drop=True)

            st.caption(f"{len(df)} usuarios — marca ✓ para incluir solo esos en el informe, o deja sin marcar para incluir todos los visibles.")

            df.insert(0, "sel", False)

            df_edited = st.data_editor(
                df,
                use_container_width=True,
                hide_index=True,
                num_rows="fixed",
                key="usuarios_data_editor",
                column_config={
                    "sel":          st.column_config.CheckboxColumn("✓", default=False, width="small"),
                    "id_usuario":   None,
                    "nombre":       st.column_config.TextColumn("Nombre(s)"),
                    "ap_paterno":   st.column_config.TextColumn("Ap. Paterno"),
                    "ap_materno":   st.column_config.TextColumn("Ap. Materno"),
                    "puesto":       st.column_config.TextColumn("Puesto"),
                    "correo":       st.column_config.TextColumn("Correo"),
                    "departamento": st.column_config.SelectboxColumn("Departamento", options=deptos_names),
                },
            )

            n_sel = int(df_edited["sel"].sum()) if "sel" in df_edited.columns else 0
            if n_sel:
                st.info(f"✓ {n_sel} usuario(s) seleccionado(s) — el informe incluirá solo esos.")

            # DataFrame para informes: seleccionados o todos los visibles
            _df_inf_base = df_edited[df_edited["sel"] == True] if n_sel else df_edited

            # Columnas limpias (sin sel ni id_usuario) con nombres en español
            _RENAME_USR = {
                "nombre": "Nombre(s)", "ap_paterno": "Ap. Paterno", "ap_materno": "Ap. Materno",
                "puesto": "Puesto", "correo": "Correo", "departamento": "Departamento",
            }

            col_btn, col_inf, col_inf_pdf, col_xls, col_pdf = st.columns([1, 1.2, 1.2, 1, 1])
            with col_btn:
                guardar = st.button("💾 Guardar cambios", type="primary", use_container_width=True, key="usr_guardar")

            with col_inf:
                if not _df_inf_base.empty:
                    try:
                        from openpyxl import Workbook
                        from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
                        from openpyxl.utils import get_column_letter as gcl

                        id_usuarios = [int(r) for r in _df_inf_base["id_usuario"].tolist() if pd.notna(r)]
                        cur_inf = get_conn().cursor()

                        df_h1 = _df_inf_base.drop(columns=["sel","id_usuario"], errors="ignore").rename(columns=_RENAME_USR)

                        cur_inf.execute("""
                            SELECT u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                                   e.tipo AS "Tipo", e.nombre_equipo AS "Equipo",
                                   e.cpu_marca AS "Marca", e.cpu_modelo AS "Modelo",
                                   e.cpu_serie AS "Serie", e.ipv4 AS "IPv4", e.mac AS "MAC"
                            FROM inventario_equipos e
                            JOIN usuarios u ON (
                                e.id_usuario = u.id_usuario
                                OR (e.id_usuario IS NULL AND e.nombre_usuario ILIKE '%%' || u.apellido_paterno || '%%')
                            )
                            WHERE u.id_usuario = ANY(%s) ORDER BY u.apellido_paterno, e.tipo
                        """, (id_usuarios,))
                        df_h2 = pd.DataFrame(cur_inf.fetchall(),
                                             columns=["Usuario","Tipo","Equipo","Marca","Modelo","Serie","IPv4","MAC"])

                        cur_inf.execute("""
                            SELECT u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                                t.numero_general AS "Numero General", t.extension AS "Extension"
                            FROM telefonos t JOIN usuarios u ON t.id_usuario = u.id_usuario
                            WHERE u.id_usuario = ANY(%s) ORDER BY u.apellido_paterno
                        """, (id_usuarios,))
                        df_h3 = pd.DataFrame(cur_inf.fetchall(), columns=["Usuario","Numero General","Extension"])

                        cur_inf.execute("""
                            SELECT u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                                i.marca AS "Marca", i.modelo AS "Modelo", i.serie AS "Serie",
                                COALESCE(i.ip_address::text,'') AS "IP", i.firmware AS "Firmware"
                            FROM impresoras i LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
                            WHERE u.id_usuario = ANY(%s) ORDER BY u.apellido_paterno
                        """, (id_usuarios,))
                        df_h4 = pd.DataFrame(cur_inf.fetchall(),
                                            columns=["Usuario","Marca","Modelo","Serie","IP","Firmware"])

                        cur_inf.execute("""
                            SELECT DISTINCT ON (i.ip)
                                u.nombre || ' ' || u.apellido_paterno AS "Usuario Registrado",
                                i.usuario AS "Nombre en IP",
                                i.ip AS "IP", i.tipo_equipo AS "Tipo Equipo",
                                i.mac AS "MAC", i.marca AS "Marca",
                                i.modelo AS "Modelo", i.serie AS "Serie",
                                i.estatus AS "Estatus", i.departamento_pestana AS "Area"
                            FROM inventario_ips_completo i
                            JOIN usuarios u ON (
                                i.usuario ILIKE '%%' || u.apellido_paterno || '%%'
                            )
                            WHERE u.id_usuario = ANY(%s) AND i.estatus != 'Libre'
                            ORDER BY i.ip
                        """, (id_usuarios,))
                        df_h5 = pd.DataFrame(cur_inf.fetchall(), columns=[
                            "Usuario Registrado","Nombre en IP","IP","Tipo Equipo",
                            "MAC","Marca","Modelo","Serie","Estatus","Area"
                        ])
                        cur_inf.close()

                        AZUL="1A3C5E"; CELESTE="EAF1FB"; BORDE="BFBFBF"
                        _b = Border(left=Side(style="thin",color=BORDE), right=Side(style="thin",color=BORDE),
                                    top=Side(style="thin",color=BORDE),  bottom=Side(style="thin",color=BORDE))
                        def _hoja(wb, df, nombre):
                            ws = wb.create_sheet(title=nombre)
                            headers = list(df.columns)
                            for ci, h in enumerate(headers, 1):
                                c = ws.cell(row=1, column=ci, value=h)
                                c.font = Font(bold=True, color="FFFFFF", size=10)
                                c.fill = PatternFill("solid", fgColor=AZUL)
                                c.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
                                c.border = _b
                            ws.row_dimensions[1].height = 26
                            ws.freeze_panes = "A2"
                            fill_alt = PatternFill("solid", fgColor=CELESTE)
                            for ri, (_, row) in enumerate(df.iterrows(), 2):
                                for ci, h in enumerate(headers, 1):
                                    val = row[h]
                                    if val is None or (isinstance(val, float) and pd.isna(val)):
                                        val = ""
                                    c = ws.cell(row=ri, column=ci, value=val)
                                    c.font = Font(size=9)
                                    c.alignment = Alignment(vertical="center")
                                    c.border = _b
                                    if ri % 2 == 0:
                                        c.fill = fill_alt
                            for ci, h in enumerate(headers, 1):
                                vals = [str(h)] + [str(r[h]) if r[h] is not None and not (isinstance(r[h], float) and pd.isna(r[h])) else "" for _, r in df.iterrows()]
                                ws.column_dimensions[gcl(ci)].width = min(max(len(v) for v in vals) + 4, 45)

                        df_inventario = leer_inventario_datos_xlsx()

                        wb = Workbook(); wb.remove(wb.active)
                        if not df_inventario.empty: _hoja(wb, df_inventario, "Equipos")
                        if not df_h3.empty: _hoja(wb, df_h3, "Telefonos")
                        if not df_h4.empty: _hoja(wb, df_h4, "Impresoras")
                        buf_inf = BytesIO(); wb.save(buf_inf); buf_inf.seek(0)

                        hojas = (not df_inventario.empty) + (not df_h3.empty) + (not df_h4.empty)
                        lbl = f"📊 Informe ({n_sel} sel.)" if n_sel else f"📊 Informe ({hojas} hojas)"
                        st.download_button(lbl, data=buf_inf.getvalue(),
                                        file_name=f"informe_usuarios_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                                        mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                        use_container_width=True, key="usr_export_excel")
                    except Exception as e:
                        st.error(f"Error al generar informe: {e}")

            with col_inf_pdf:
                if not _df_inf_base.empty:
                    try:
                        id_usuarios_pdf = [int(r) for r in _df_inf_base["id_usuario"].tolist() if pd.notna(r)]
                        cur_pdf = get_conn().cursor()
                        df_p1 = _df_inf_base.drop(columns=["sel","id_usuario"], errors="ignore").rename(columns=_RENAME_USR)
                        cur_pdf.execute("""
                            SELECT u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                                   e.tipo AS "Tipo", e.nombre_equipo AS "Equipo",
                                   e.cpu_marca AS "Marca", e.cpu_modelo AS "Modelo",
                                   e.cpu_serie AS "Serie", e.ipv4 AS "IPv4", e.mac AS "MAC"
                            FROM inventario_equipos e
                            JOIN usuarios u ON (
                                e.id_usuario = u.id_usuario
                                OR (e.id_usuario IS NULL AND e.nombre_usuario ILIKE '%%' || u.apellido_paterno || '%%')
                            )
                            WHERE u.id_usuario = ANY(%s) ORDER BY u.apellido_paterno, e.tipo
                        """, (id_usuarios_pdf,))
                        df_p2 = pd.DataFrame(cur_pdf.fetchall(), columns=["Usuario","Tipo","Equipo","Marca","Modelo","Serie","IPv4","MAC"])
                        cur_pdf.execute("""
                            SELECT u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                                   t.numero_general AS "Numero General", t.extension AS "Extension"
                            FROM telefonos t JOIN usuarios u ON t.id_usuario = u.id_usuario
                            WHERE u.id_usuario = ANY(%s) ORDER BY u.apellido_paterno
                        """, (id_usuarios_pdf,))
                        df_p3 = pd.DataFrame(cur_pdf.fetchall(), columns=["Usuario","Numero General","Extension"])
                        cur_pdf.execute("""
                            SELECT u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                                   i.marca AS "Marca", i.modelo AS "Modelo", i.serie AS "Serie",
                                   COALESCE(i.ip_address::text,'') AS "IP", i.firmware AS "Firmware"
                            FROM impresoras i JOIN usuarios u ON i.id_usuario = u.id_usuario
                            WHERE u.id_usuario = ANY(%s) ORDER BY u.apellido_paterno
                        """, (id_usuarios_pdf,))
                        df_p4 = pd.DataFrame(cur_pdf.fetchall(), columns=["Usuario","Marca","Modelo","Serie","IP","Firmware"])
                        cur_pdf.execute("""
                            SELECT DISTINCT ON (i.ip)
                                   u.nombre || ' ' || u.apellido_paterno AS "Usuario",
                                   i.ip AS "IP", i.tipo_equipo AS "Tipo",
                                   i.mac AS "MAC", i.marca AS "Marca",
                                   i.estatus AS "Estatus", i.departamento_pestana AS "Area"
                            FROM inventario_ips_completo i
                            JOIN usuarios u ON (
                                i.id_usuario = u.id_usuario
                                OR (i.id_usuario IS NULL AND i.usuario ILIKE '%%' || u.apellido_paterno || '%%')
                            )
                            WHERE u.id_usuario = ANY(%s) AND i.estatus != 'Libre'
                            ORDER BY i.ip
                        """, (id_usuarios_pdf,))
                        df_p5 = pd.DataFrame(cur_pdf.fetchall(), columns=["Usuario","IP","Tipo","MAC","Marca","Estatus","Area"])
                        cur_pdf.close()

                        df_inv_pdf = leer_inventario_datos_xlsx()
                        secciones = [
                            ("Equipos de Computo",   df_inv_pdf),
                            ("Telefonos",            df_p3),
                            ("Impresoras",           df_p4),
                        ]
                        pdf_inf_bytes = generar_pdf_informe_completo(secciones).read()
                        lbl_inf_pdf = f"📄 Informe PDF ({n_sel} sel.)" if n_sel else "📄 Informe PDF"
                        st.download_button(lbl_inf_pdf, data=pdf_inf_bytes,
                                           file_name=f"informe_usuarios_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                                           mime="application/pdf",
                                           use_container_width=True, key="usr_informe_pdf")
                    except Exception as e:
                        st.error(f"Error PDF informe: {e}")

            with col_xls:
                if not df_edited.empty:
                    _df_xls = df_edited.drop(columns=["sel","id_usuario"], errors="ignore").rename(columns=_RENAME_USR)
                    buf_xls = generar_excel_formateado(_df_xls, "Usuarios")
                    st.download_button("📥 Solo filtro", data=buf_xls.getvalue(),
                                    file_name=f"usuarios_filtro_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                    use_container_width=True, key="usr_export_xls")

            with col_pdf:
                if not df_edited.empty:
                    try:
                        df_pdf = (_df_inf_base if n_sel else df_edited).drop(columns=["sel","id_usuario"], errors="ignore").rename(columns=_RENAME_USR)
                        pdf_bytes = generar_pdf_usuarios(df_pdf).read()
                        lbl_pdf = f"📄 PDF ({n_sel} sel.)" if n_sel else "📄 PDF"
                        st.download_button(lbl_pdf, data=pdf_bytes,
                                        file_name=f"usuarios_IMJ_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                                        mime="application/pdf", use_container_width=True, key="usr_export_pdf")
                    except Exception as e:
                        st.error(f"Error PDF: {e}")

            if guardar:
                errores = []
                for _, row in df_edited.iterrows():
                    correo_val = str(row.get("correo", "") or "").strip()
                    if correo_val and not validar_email(correo_val):
                        errores.append(f"Correo invalido: {correo_val}")
                if errores:
                    for err in errores:
                        st.warning(err)
                else:
                    try:
                        cur2 = get_conn().cursor()
                        count = 0
                        for _, row in df_edited.iterrows():
                            id_dep = deptos_map.get(row.get("departamento"))
                            cur2.execute("""
                                UPDATE usuarios
                                SET nombre=%s, apellido_paterno=%s, apellido_materno=%s,
                                    puesto=%s, correo=%s, id_departamento=%s
                                WHERE id_usuario=%s
                            """, (
                                row.get("nombre") or None,
                                row.get("ap_paterno") or None,
                                row.get("ap_materno") or None,
                                row.get("puesto") or None,
                                row.get("correo") or None,
                                id_dep,
                                int(row["id_usuario"]),
                            ))
                            count += 1
                        get_conn().commit()
                        cur2.close()
                        st.success(f"✅ {count} usuario(s) guardados correctamente.")
                        st.rerun()
                    except Exception as e:
                        safe_rollback()
                        st.error(f"Error al guardar: {e}")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab2:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")
            deptos = cur.fetchall()
            cur.close()
            with st.form("alta_usuario"):
                col1, col2, col3 = st.columns(3)
                nombre = col1.text_input("Nombre(s)")
                ap_pat = col2.text_input("Apellido Paterno")
                ap_mat = col3.text_input("Apellido Materno")
                col4, col5 = st.columns(2)
                puesto = col4.text_input("Puesto")
                correo = col5.text_input("Correo")
                depto_sel = st.selectbox("Departamento", [d[1] for d in deptos])
                id_depto  = next(d[0] for d in deptos if d[1] == depto_sel)
                if st.form_submit_button("✅ Dar de Alta"):
                    errores = []
                    if not nombre or not ap_pat:
                        errores.append("Llena al menos Nombre y Apellido Paterno.")
                    if correo and not validar_email(correo):
                        errores.append("El formato del correo no es valido.")
                    if errores:
                        for err in errores:
                            st.warning(err)
                    else:
                        cur2 = get_conn().cursor()
                        cur2.execute("""
                            INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, puesto, correo, id_departamento)
                            VALUES (%s, %s, %s, %s, %s, %s)
                        """, (nombre, ap_pat, ap_mat, puesto, correo, id_depto))
                        get_conn().commit()
                        cur2.close()
                        st.success(f"✅ Usuario {nombre} {ap_pat} dado de alta.")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab3:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios WHERE activo = true ORDER BY apellido_paterno")
            usuarios = cur.fetchall()
            cur.close()
            if not usuarios:
                st.info("No hay usuarios activos.")
            else:
                opciones = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
                sel = st.selectbox("Selecciona el usuario a dar de baja", list(opciones.keys()), key="baja_u_sel")
                st.warning(f"⚠️ **{sel}** quedara inactivo. Sus datos se conservan y puede reactivarse.")
                confirmar = st.text_input("Escribe CONFIRMAR para continuar", key="baja_u_conf")
                if st.button("🔴 Dar de Baja", disabled=(confirmar.strip() != "CONFIRMAR")):
                    cur2 = get_conn().cursor()
                    cur2.execute("UPDATE usuarios SET activo = false WHERE id_usuario = %s", (opciones[sel],))
                    get_conn().commit()
                    cur2.close()
                    st.success(f"✅ {sel} dado de baja. Puedes reactivarlo desde la pestaña 'Reactivar'.")
                    st.rerun()
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab4:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios WHERE activo = false ORDER BY apellido_paterno")
            inactivos = cur.fetchall()
            cur.close()
            if not inactivos:
                st.info("No hay usuarios inactivos.")
            else:
                opciones = {f"{u[1]} {u[2]}": u[0] for u in inactivos}
                sel = st.selectbox("Selecciona el usuario a reactivar", list(opciones.keys()), key="react_u_sel")
                st.info(f"Se reactivara el acceso de **{sel}**.")
                if st.button("✅ Reactivar"):
                    cur2 = get_conn().cursor()
                    cur2.execute("UPDATE usuarios SET activo = true WHERE id_usuario = %s", (opciones[sel],))
                    get_conn().commit()
                    cur2.close()
                    st.success(f"✅ {sel} reactivado correctamente.")
                    st.rerun()
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

# ════════════════════════════════════════
# EQUIPOS DE COMPUTO
# ════════════════════════════════════════
elif menu == "💻 Equipos de Computo":
    st.subheader("💻 Equipos de Cómputo")
    tab1, tab2, tab3, tab4, tab5, tab6 = st.tabs([
        "📊 Resumen", "💻 Laptops", "🖥️ PC Avanzadas", "🖥️ PC Especializadas", "📄 Resguardo", "➕ Agregar"
    ])

    # ── helper compartido para los tabs de edición ───────────────────────────
    def _eq_tab(tipo, key_prefix, col_cfg_extra, rename_map, pdf_titulo):
        try:
            cur = get_conn().cursor()
            cur.execute(
                "SELECT id_usuario, nombre || ' ' || apellido_paterno FROM usuarios ORDER BY apellido_paterno"
            )
            _rows_u = cur.fetchall()
            _umap   = {r[1]: r[0] for r in _rows_u}
            _unames = [""] + list(_umap.keys())

            busqueda_eq = st.text_input(
                "Buscar equipo, usuario, serie, MAC, área...",
                placeholder="Ej: IMJ-01, Juan, OPTIPLEX...",
                key=f"{key_prefix}_busq"
            )

            cur.execute(
                "SELECT * FROM inventario_equipos WHERE tipo=%s ORDER BY consecutivo",
                (tipo,)
            )
            colnames = [d[0] for d in cur.description]
            df = pd.DataFrame(cur.fetchall(), columns=colnames)
            cur.close()

            if busqueda_eq:
                mask = df.apply(
                    lambda col: col.astype(str).str.contains(busqueda_eq, case=False, na=False)
                ).any(axis=1)
                df = df[mask].reset_index(drop=True)

            # La columna usuario mostrada es el nombre_usuario del Excel, editable via selectbox
            col_cfg = {
                "id":             None,
                "tipo":           None,
                "id_usuario":     None,
                "fecha_carga":    None,
                "consecutivo":    st.column_config.NumberColumn("No.", disabled=True, width="small"),
                "num_inventario": st.column_config.TextColumn("Inventario"),
                "nombre_equipo":  st.column_config.TextColumn("Equipo"),
                "nombre_usuario": st.column_config.SelectboxColumn("Usuario", options=_unames),
                "perfil":         st.column_config.TextColumn("Perfil"),
                "area":           st.column_config.TextColumn("Área"),
                "cpu_marca":      st.column_config.TextColumn("Marca CPU"),
                "cpu_modelo":     st.column_config.TextColumn("Modelo CPU"),
                "cpu_serie":      st.column_config.TextColumn("Serie CPU"),
                "ipv4":           st.column_config.TextColumn("IPv4"),
                "mac":            st.column_config.TextColumn("MAC"),
                "responsiva":     st.column_config.TextColumn("Responsiva"),
                "observaciones":  st.column_config.TextColumn("Observaciones"),
            }
            col_cfg.update(col_cfg_extra)

            st.caption(
                f"{len(df)} equipos — edita en la tabla y presiona **Guardar cambios**."
            )
            df_edited = st.data_editor(
                df,
                use_container_width=True,
                hide_index=True,
                num_rows="fixed",
                key=f"{key_prefix}_editor",
                column_config=col_cfg,
            )

            _HIDDEN = {"id", "tipo", "id_usuario", "fecha_carga"}
            _df_exp = df_edited.drop(columns=list(_HIDDEN), errors="ignore").rename(columns=rename_map)

            col_btn, col_xls, col_pdf = st.columns([1, 2, 1])
            with col_btn:
                guardar = st.button(
                    "💾 Guardar cambios", type="primary",
                    use_container_width=True, key=f"{key_prefix}_guardar"
                )
            with col_xls:
                if not _df_exp.empty:
                    xls_buf = generar_excel_formateado(_df_exp, tipo[:31])
                    st.download_button(
                        "📥 Exportar Excel", data=xls_buf.getvalue(),
                        file_name=f"{key_prefix}_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                        mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                        use_container_width=True, key=f"{key_prefix}_xls"
                    )
            with col_pdf:
                if not _df_exp.empty:
                    try:
                        st.download_button(
                            "📄 PDF", use_container_width=True,
                            data=generar_pdf_generico(_df_exp, pdf_titulo).read(),
                            file_name=f"{key_prefix}_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                            mime="application/pdf", key=f"{key_prefix}_pdf"
                        )
                    except Exception as e:
                        st.error(f"Error PDF: {e}")

            if guardar:
                try:
                    cur3 = get_conn().cursor()
                    count = 0
                    for _, row in df_edited.iterrows():
                        _nombre_u = (row.get("nombre_usuario") or "").strip() or None
                        _id_u     = _umap.get(_nombre_u) if _nombre_u else None
                        upd_cols  = [c for c in df.columns if c not in ("id", "tipo", "fecha_carga")]
                        set_parts = ", ".join(f"{c}=%s" for c in upd_cols)
                        vals = []
                        for c in upd_cols:
                            v = row.get(c)
                            if c == "nombre_usuario":
                                vals.append(_nombre_u)
                            elif c == "id_usuario":
                                vals.append(_id_u)
                            else:
                                vals.append(None if (v is None or (isinstance(v, float) and pd.isna(v)) or str(v).strip() in ('', 'nan', 'None')) else v)
                        vals.append(int(row["id"]))
                        cur3.execute(f"UPDATE inventario_equipos SET {set_parts} WHERE id=%s", vals)
                        count += 1
                    get_conn().commit()
                    cur3.close()
                    st.success(f"✅ {count} equipo(s) guardados.")
                    st.rerun()
                except Exception as e:
                    safe_rollback()
                    st.error(f"Error al guardar: {e}")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    # ── Tab 1: Resumen ────────────────────────────────────────────────────────
    with tab1:
        try:
            cur = get_conn().cursor()
            cur.execute("""
                SELECT tipo,
                       COUNT(*) AS total,
                       COUNT(*) FILTER (WHERE id_usuario IS NOT NULL)  AS vinculados,
                       COUNT(*) FILTER (WHERE id_usuario IS NULL)      AS sin_usuario
                FROM inventario_equipos
                GROUP BY tipo ORDER BY tipo
            """)
            df_res = pd.DataFrame(cur.fetchall(), columns=["Tipo", "Total", "Vinculados", "Sin usuario"])

            cur.execute("""
                SELECT area, tipo, COUNT(*) AS total
                FROM inventario_equipos
                WHERE area IS NOT NULL
                GROUP BY area, tipo
                ORDER BY area, tipo
            """)
            df_area = pd.DataFrame(cur.fetchall(), columns=["Área", "Tipo", "Total"])
            cur.close()

            total_all = int(df_res["Total"].sum()) if not df_res.empty else 0
            col_a, col_b, col_c, col_d = st.columns(4)
            col_a.metric("Total equipos", total_all)
            for _, rr in df_res.iterrows():
                if rr["Tipo"] == "Laptop":
                    col_b.metric("Laptops", int(rr["Total"]))
                elif rr["Tipo"] == "PC Avanzada":
                    col_c.metric("PC Avanzadas", int(rr["Total"]))
                elif rr["Tipo"] == "PC Especializada":
                    col_d.metric("PC Especializadas", int(rr["Total"]))

            st.markdown("#### Distribución por tipo")
            st.dataframe(df_res, use_container_width=True, hide_index=True)

            st.markdown("#### Equipos por área y tipo")
            if not df_area.empty:
                _areas_opts  = sorted(df_area["Área"].dropna().unique().tolist())
                _tipos_opts  = sorted(df_area["Tipo"].dropna().unique().tolist())

                col_fa, col_ft = st.columns([2, 1])
                _area_sel = col_fa.multiselect("Filtrar por área", _areas_opts,
                                               placeholder="Todas las áreas", key="res_area_f")
                _tipo_sel = col_ft.multiselect("Filtrar por tipo", _tipos_opts,
                                               placeholder="Todos los tipos", key="res_tipo_f")

                df_area_f = df_area.copy()
                if _area_sel:
                    df_area_f = df_area_f[df_area_f["Área"].isin(_area_sel)]
                if _tipo_sel:
                    df_area_f = df_area_f[df_area_f["Tipo"].isin(_tipo_sel)]

                df_pivot = df_area_f.pivot_table(index="Área", columns="Tipo", values="Total", fill_value=0)
                df_pivot["Total"] = df_pivot.sum(axis=1)
                df_pivot = df_pivot.sort_values("Total", ascending=False).reset_index()
                st.dataframe(df_pivot, use_container_width=True)
                st.caption(f"Mostrando {int(df_area_f['Total'].sum())} equipos en {len(df_pivot)} área(s).")

                # Obtener los registros detallados del filtro para descarga
                cur_f = get_conn().cursor()
                q_f = "SELECT * FROM inventario_equipos WHERE area IS NOT NULL"
                p_f = []
                if _area_sel:
                    q_f += f" AND area = ANY(%s)"
                    p_f.append(_area_sel)
                if _tipo_sel:
                    q_f += f" AND tipo = ANY(%s)"
                    p_f.append(_tipo_sel)
                q_f += " ORDER BY tipo, area, consecutivo"
                cur_f.execute(q_f, p_f)
                _cols_f = [d[0] for d in cur_f.description]
                df_filtro = pd.DataFrame(cur_f.fetchall(), columns=_cols_f)
                cur_f.close()
                df_filtro = df_filtro.drop(columns=["id","id_usuario","fecha_carga"], errors="ignore")

                col_xls_f, col_pdf_f, _ = st.columns([1, 1, 4])
                with col_xls_f:
                    if not df_filtro.empty:
                        xls_f = generar_excel_formateado(df_filtro, "Equipos filtro")
                        st.download_button(
                            "📥 Excel del filtro", data=xls_f.getvalue(),
                            file_name=f"equipos_filtro_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                            mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                            use_container_width=True, key="eq_res_xls_f"
                        )
                with col_pdf_f:
                    if not df_filtro.empty:
                        try:
                            st.download_button(
                                "📄 PDF del filtro", use_container_width=True,
                                data=generar_pdf_generico(df_filtro, "Inventario Equipos — Filtro").read(),
                                file_name=f"equipos_filtro_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                                mime="application/pdf", key="eq_res_pdf_f"
                            )
                        except Exception as e:
                            st.error(f"Error PDF: {e}")

            col_xls_r, col_pdf_r, _ = st.columns([1, 1, 4])
            with col_xls_r:
                if not df_res.empty:
                    try:
                        from openpyxl import Workbook
                        from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
                        from openpyxl.utils import get_column_letter

                        AZUL = "1A3C5E"; CELESTE = "EAF1FB"; BORDE_C = "BFBFBF"
                        _borde = Border(
                            left=Side(style="thin", color=BORDE_C), right=Side(style="thin", color=BORDE_C),
                            top=Side(style="thin", color=BORDE_C),  bottom=Side(style="thin", color=BORDE_C),
                        )
                        wb_all = Workbook()
                        wb_all.remove(wb_all.active)

                        _COLS_OCULTAS = {"id", "tipo", "id_usuario", "fecha_carga"}
                        for _tipo in ["Laptop", "PC Avanzada", "PC Especializada"]:
                            cur_t = get_conn().cursor()
                            cur_t.execute(
                                "SELECT * FROM inventario_equipos WHERE tipo=%s ORDER BY consecutivo",
                                (_tipo,)
                            )
                            _cols_t = [d[0] for d in cur_t.description]
                            _rows_t = cur_t.fetchall()
                            cur_t.close()
                            _df_t = pd.DataFrame(_rows_t, columns=_cols_t)
                            _df_t = _df_t.drop(columns=list(_COLS_OCULTAS), errors="ignore")

                            _ws = wb_all.create_sheet(title=_tipo[:31])
                            _hdrs = list(_df_t.columns)
                            for ci, h in enumerate(_hdrs, 1):
                                c = _ws.cell(row=1, column=ci, value=h)
                                c.font = Font(bold=True, color="FFFFFF", size=10)
                                c.fill = PatternFill("solid", fgColor=AZUL)
                                c.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
                                c.border = _borde
                            _ws.row_dimensions[1].height = 26
                            _fill_alt = PatternFill("solid", fgColor=CELESTE)
                            for ri, (_, row) in enumerate(_df_t.iterrows(), 2):
                                for ci, h in enumerate(_hdrs, 1):
                                    val = row[h]
                                    if val is None or (isinstance(val, float) and pd.isna(val)): val = ""
                                    c = _ws.cell(row=ri, column=ci, value=val)
                                    c.font = Font(size=9); c.alignment = Alignment(vertical="center"); c.border = _borde
                                    if ri % 2 == 0: c.fill = _fill_alt
                            for ci, h in enumerate(_hdrs, 1):
                                vals = [str(h)] + [
                                    str(row[h]) if row[h] is not None and not (isinstance(row[h], float) and pd.isna(row[h])) else ""
                                    for _, row in _df_t.iterrows()
                                ]
                                _ws.column_dimensions[get_column_letter(ci)].width = min(max(len(v) for v in vals) + 4, 40)
                            _ws.freeze_panes = "A2"

                        _buf_all = BytesIO(); wb_all.save(_buf_all); _buf_all.seek(0)
                        st.download_button(
                            "📥 Excel completo (3 hojas)", data=_buf_all.getvalue(),
                            file_name=f"inventario_equipos_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                            mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                            use_container_width=True
                        )
                    except Exception as e:
                        st.error(f"Error generando Excel: {e}")
            with col_pdf_r:
                if not df_res.empty:
                    try:
                        st.download_button(
                            "📄 PDF resumen", use_container_width=True,
                            data=generar_pdf_generico(df_res, "Resumen Equipos de Cómputo").read(),
                            file_name=f"equipos_resumen_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                            mime="application/pdf", key="eq_res_pdf"
                        )
                    except Exception as e:
                        st.error(f"Error PDF: {e}")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    # ── Tab 2: Laptops ────────────────────────────────────────────────────────
    with tab2:
        _eq_tab(
            tipo="Laptop",
            key_prefix="lap",
            col_cfg_extra={
                "teclado_serie":  None,
                "mouse_serie":    None,
                "monitor_marca":  None,
                "monitor_modelo": None,
                "monitor_serie":  None,
                "nobreak_marca":  None,
                "nobreak_modelo": None,
                "nobreak_serie":  None,
                "ipv4_actual":    None,
                "check_entrega":  None,
                "cargador_serie": st.column_config.TextColumn("Serie Cargador"),
                "docking_marca":  st.column_config.TextColumn("Marca Docking"),
                "docking_modelo": st.column_config.TextColumn("Modelo Docking"),
                "docking_serie":  st.column_config.TextColumn("Serie Docking"),
                "candado":        st.column_config.TextColumn("Candado"),
            },
            rename_map={
                "num_inventario": "Inventario", "nombre_equipo": "Equipo",
                "nombre_usuario": "Usuario", "perfil": "Perfil", "area": "Área",
                "cpu_marca": "Marca", "cpu_modelo": "Modelo", "cpu_serie": "Serie CPU",
                "cargador_serie": "Serie Cargador",
                "docking_marca": "Marca Docking", "docking_modelo": "Modelo Docking",
                "docking_serie": "Serie Docking", "candado": "Candado",
                "ipv4": "IPv4", "mac": "MAC", "responsiva": "Responsiva",
                "observaciones": "Observaciones",
            },
            pdf_titulo="Inventario Laptops",
        )

    # ── Tab 3: PC Avanzadas ───────────────────────────────────────────────────
    with tab3:
        _eq_tab(
            tipo="PC Avanzada",
            key_prefix="pca",
            col_cfg_extra={
                "cargador_serie": None,
                "docking_marca":  None,
                "docking_modelo": None,
                "docking_serie":  None,
                "candado":        None,
                "ipv4_actual":    None,
                "check_entrega":  None,
                "teclado_serie":  st.column_config.TextColumn("Serie Teclado"),
                "mouse_serie":    st.column_config.TextColumn("Serie Mouse"),
                "monitor_marca":  st.column_config.TextColumn("Marca Monitor"),
                "monitor_modelo": st.column_config.TextColumn("Modelo Monitor"),
                "monitor_serie":  st.column_config.TextColumn("Serie Monitor"),
                "nobreak_marca":  st.column_config.TextColumn("Marca No-Break"),
                "nobreak_modelo": st.column_config.TextColumn("Modelo No-Break"),
                "nobreak_serie":  st.column_config.TextColumn("Serie No-Break"),
            },
            rename_map={
                "num_inventario": "Inventario", "nombre_equipo": "Equipo",
                "nombre_usuario": "Usuario", "perfil": "Perfil", "area": "Área",
                "cpu_marca": "Marca CPU", "cpu_modelo": "Modelo CPU", "cpu_serie": "Serie CPU",
                "teclado_serie": "Serie Teclado", "mouse_serie": "Serie Mouse",
                "monitor_marca": "Marca Monitor", "monitor_modelo": "Modelo Monitor",
                "monitor_serie": "Serie Monitor",
                "nobreak_marca": "Marca No-Break", "nobreak_modelo": "Modelo No-Break",
                "nobreak_serie": "Serie No-Break",
                "ipv4": "IPv4", "mac": "MAC", "responsiva": "Responsiva",
                "observaciones": "Observaciones",
            },
            pdf_titulo="Inventario PC Avanzadas",
        )

    # ── Tab 4: PC Especializadas ──────────────────────────────────────────────
    with tab4:
        _eq_tab(
            tipo="PC Especializada",
            key_prefix="pce",
            col_cfg_extra={
                "cargador_serie": None,
                "docking_marca":  None,
                "docking_modelo": None,
                "docking_serie":  None,
                "candado":        None,
                "teclado_serie":  st.column_config.TextColumn("Serie Teclado"),
                "mouse_serie":    st.column_config.TextColumn("Serie Mouse"),
                "monitor_marca":  st.column_config.TextColumn("Marca Monitor"),
                "monitor_modelo": st.column_config.TextColumn("Modelo Monitor"),
                "monitor_serie":  st.column_config.TextColumn("Serie Monitor"),
                "nobreak_marca":  st.column_config.TextColumn("Marca No-Break"),
                "nobreak_modelo": st.column_config.TextColumn("Modelo No-Break"),
                "nobreak_serie":  st.column_config.TextColumn("Serie No-Break"),
                "ipv4_actual":    st.column_config.TextColumn("IPv4 Actual"),
                "check_entrega":  st.column_config.TextColumn("Check"),
            },
            rename_map={
                "num_inventario": "Inventario", "nombre_equipo": "Equipo",
                "nombre_usuario": "Usuario", "perfil": "Perfil", "area": "Área",
                "cpu_marca": "Marca CPU", "cpu_modelo": "Modelo CPU", "cpu_serie": "Serie CPU",
                "teclado_serie": "Serie Teclado", "mouse_serie": "Serie Mouse",
                "monitor_marca": "Marca Monitor", "monitor_modelo": "Modelo Monitor",
                "monitor_serie": "Serie Monitor",
                "nobreak_marca": "Marca No-Break", "nobreak_modelo": "Modelo No-Break",
                "nobreak_serie": "Serie No-Break",
                "ipv4": "IPv4", "ipv4_actual": "IPv4 Actual", "mac": "MAC",
                "responsiva": "Responsiva", "check_entrega": "Check",
                "observaciones": "Observaciones",
            },
            pdf_titulo="Inventario PC Especializadas",
        )

    # ── Tab 5: Resguardo ──────────────────────────────────────────────────────
    with tab5:
        try:
            cur = get_conn().cursor()
            cur.execute("""
                SELECT id, tipo, nombre_equipo, cpu_marca, cpu_modelo, cpu_serie,
                       num_inventario, nombre_usuario, area, ipv4
                FROM inventario_equipos
                ORDER BY area, tipo, nombre_usuario
            """)
            rows_rsg = cur.fetchall()
            cur.close()

            # ── Resguardo individual ───────────────────────────────────────────
            st.markdown("#### Resguardo individual")
            _opciones_ind = {
                f"{r[7] or '—'}  —  {r[2] or r[4] or '?'}  ({r[8] or 'Sin área'})": r[0]
                for r in rows_rsg
            }
            _sel_ind = st.selectbox(
                "Seleccionar equipo",
                [""] + list(_opciones_ind.keys()),
                key="rsg_ind_sel",
            )

            if _sel_ind:
                _id_ind  = _opciones_ind[_sel_ind]
                _cur_ind = get_conn().cursor()
                _cur_ind.execute("SELECT * FROM inventario_equipos WHERE id = %s", (_id_ind,))
                _col_ind = [d[0] for d in _cur_ind.description]
                _eq_ind  = dict(zip(_col_ind, _cur_ind.fetchone()))
                _cur_ind.close()

                _tipo_ind = _eq_ind.get("tipo", "Laptop")
                _K = f"_{_id_ind}"  # clave única por equipo para no mezclar ediciones

                st.markdown("**Editar datos antes de generar el PDF** *(los cambios no se guardan en la base de datos)*")

                _e = {}
                col_a, col_b = st.columns(2)
                _e["nombre_usuario"] = col_a.text_input("Responsable",    value=_eq_ind.get("nombre_usuario") or "", key=f"rsg_e_usr{_K}")
                _e["area"]           = col_b.text_input("Área / Dirección", value=_eq_ind.get("area") or "",          key=f"rsg_e_area{_K}")
                _e["perfil"]         = col_a.text_input("Perfil de usuario", value=_eq_ind.get("perfil") or "",       key=f"rsg_e_perf{_K}")
                _e["nombre_equipo"]  = col_b.text_input("Nombre del equipo", value=_eq_ind.get("nombre_equipo") or "", key=f"rsg_e_eq{_K}")

                col_c, col_d, col_e2 = st.columns(3)
                _e["cpu_marca"]  = col_c.text_input("Marca CPU",  value=_eq_ind.get("cpu_marca") or "",  key=f"rsg_e_marca{_K}")
                _e["cpu_modelo"] = col_d.text_input("Modelo CPU", value=_eq_ind.get("cpu_modelo") or "", key=f"rsg_e_modelo{_K}")
                _e["cpu_serie"]  = col_e2.text_input("Serie CPU", value=_eq_ind.get("cpu_serie") or "",  key=f"rsg_e_serie{_K}")

                col_f, col_g = st.columns(2)
                _e["mac"] = col_f.text_input("MAC Address", value=_eq_ind.get("mac") or "", key=f"rsg_e_mac{_K}")

                if _tipo_ind == "Laptop":
                    _e["cargador_serie"] = col_g.text_input("Serie cargador", value=_eq_ind.get("cargador_serie") or "", key=f"rsg_e_carg{_K}")
                    col_h, col_i, col_j = st.columns(3)
                    _e["docking_marca"]  = col_h.text_input("Docking — Marca",  value=_eq_ind.get("docking_marca") or "",  key=f"rsg_e_dm{_K}")
                    _e["docking_modelo"] = col_i.text_input("Docking — Modelo", value=_eq_ind.get("docking_modelo") or "", key=f"rsg_e_dmod{_K}")
                    _e["docking_serie"]  = col_j.text_input("Docking — Serie",  value=_eq_ind.get("docking_serie") or "",  key=f"rsg_e_ds{_K}")
                    _e["candado"]        = st.text_input("Candado", value=_eq_ind.get("candado") or "", key=f"rsg_e_cand{_K}")
                else:
                    col_h, col_i = st.columns(2)
                    _e["teclado_serie"] = col_h.text_input("Serie teclado", value=_eq_ind.get("teclado_serie") or "", key=f"rsg_e_tec{_K}")
                    _e["mouse_serie"]   = col_i.text_input("Serie mouse",   value=_eq_ind.get("mouse_serie") or "",   key=f"rsg_e_mouse{_K}")
                    col_j, col_k, col_l = st.columns(3)
                    _e["monitor_marca"]  = col_j.text_input("Monitor — Marca",  value=_eq_ind.get("monitor_marca") or "",  key=f"rsg_e_monm{_K}")
                    _e["monitor_modelo"] = col_k.text_input("Monitor — Modelo", value=_eq_ind.get("monitor_modelo") or "", key=f"rsg_e_monmod{_K}")
                    _e["monitor_serie"]  = col_l.text_input("Monitor — Serie",  value=_eq_ind.get("monitor_serie") or "",  key=f"rsg_e_mons{_K}")
                    col_m, col_n, col_o = st.columns(3)
                    _e["nobreak_marca"]  = col_m.text_input("No-Break — Marca",  value=_eq_ind.get("nobreak_marca") or "",  key=f"rsg_e_nbm{_K}")
                    _e["nobreak_modelo"] = col_n.text_input("No-Break — Modelo", value=_eq_ind.get("nobreak_modelo") or "", key=f"rsg_e_nbmod{_K}")
                    _e["nobreak_serie"]  = col_o.text_input("No-Break — Serie",  value=_eq_ind.get("nobreak_serie") or "",  key=f"rsg_e_nbs{_K}")
                    if _tipo_ind == "PC Especializada":
                        _e["ipv4_actual"] = st.text_input("IPv4 Actual", value=_eq_ind.get("ipv4_actual") or "", key=f"rsg_e_ipv4{_K}")

                _e["observaciones"] = st.text_area("Observaciones", value=_eq_ind.get("observaciones") or "", height=68, key=f"rsg_e_obs{_K}")

                _eq_pdf = {**_eq_ind, **_e}
                try:
                    _pdf_ind = generar_pdf_resguardo(_eq_pdf).read()
                    st.download_button(
                        "📄 Descargar resguardo",
                        data=_pdf_ind,
                        file_name=f"resguardo_{(_eq_pdf.get('nombre_equipo') or 'equipo').replace(' ', '_')}_{datetime.now().strftime('%Y%m%d')}.pdf",
                        mime="application/pdf",
                        type="primary",
                    )
                except Exception as _ex_ind:
                    st.error(f"Error al generar PDF: {_ex_ind}")

            st.markdown("---")
            st.markdown("#### Resguardos masivos")
            col_r1, col_r2, col_r3 = st.columns([1, 1, 2])
            tipo_r = col_r1.selectbox("Tipo", ["Todos","Laptop","PC Avanzada","PC Especializada"], key="rsg_tipo")
            area_r = col_r2.selectbox("Área", ["Todas"] + sorted({r[8] for r in rows_rsg if r[8]}), key="rsg_area")
            busq_r = col_r3.text_input("Buscar por usuario, equipo o serie", key="rsg_busq")

            df_rsg = pd.DataFrame(rows_rsg, columns=[
                "id","Tipo","Equipo","Marca","Modelo","Serie","Inventario","Usuario","Área","IPv4"
            ])
            if tipo_r != "Todos":
                df_rsg = df_rsg[df_rsg["Tipo"] == tipo_r]
            if area_r != "Todas":
                df_rsg = df_rsg[df_rsg["Área"] == area_r]
            if busq_r:
                mask = df_rsg.apply(lambda col: col.astype(str).str.contains(busq_r, case=False, na=False)).any(axis=1)
                df_rsg = df_rsg[mask]
            df_rsg = df_rsg.reset_index(drop=True)

            # Botón "Seleccionar todos" vía session_state
            col_cap, col_all = st.columns([4, 1])
            col_cap.caption(f"{len(df_rsg)} equipos — selecciona uno o varios (Ctrl+clic) para generar resguardos.")
            if col_all.button("☑ Seleccionar todos", use_container_width=True, key="rsg_sel_all"):
                st.session_state["rsg_todos"] = True
            if st.session_state.get("rsg_todos") and not busq_r and tipo_r == "Todos" and area_r == "Todas":
                pass  # se usa abajo para seleccionar todos

            # Color de fondo distinto por área
            _PALETA_RSG = [
                "#EAF4FB", "#EBF7EB", "#FEF6E7", "#F3EAFC", "#FFF9E6",
                "#E8FAFA", "#FFE9F3", "#EEF2FE", "#F5FCE8", "#FFE9E9",
                "#E8FBF3", "#FFF2E8",
            ]
            _areas_rsg = sorted({r[8] for r in rows_rsg if r[8]})
            _aclr_rsg  = {a: _PALETA_RSG[i % len(_PALETA_RSG)] for i, a in enumerate(_areas_rsg)}

            def _color_area_row(row):
                c = _aclr_rsg.get(row.get("Área", ""), "")
                return [f"background-color: {c}; color: #111111" if c else ""] * len(row)

            _df_display = df_rsg.drop(columns=["id"])
            _styled_rsg = _df_display.style.apply(_color_area_row, axis=1)

            sel_rsg = st.dataframe(
                _styled_rsg,
                use_container_width=True, hide_index=True,
                on_select="rerun", selection_mode="multi-row", key="rsg_sel"
            )

            # Determinar IDs seleccionados
            if st.session_state.get("rsg_todos"):
                indices_sel = list(range(len(df_rsg)))
                st.session_state["rsg_todos"] = False   # resetear tras aplicar
            else:
                indices_sel = [i for i in sel_rsg.selection.rows if i < len(df_rsg)]

            if indices_sel:
                ids_sel = [int(df_rsg.iloc[i]["id"]) for i in indices_sel]
                st.markdown("---")

                # Cargar datos completos de todos los seleccionados
                cur2 = get_conn().cursor()
                cur2.execute(
                    "SELECT * FROM inventario_equipos WHERE id = ANY(%s) ORDER BY area, tipo, nombre_usuario",
                    (ids_sel,)
                )
                col_names = [d[0] for d in cur2.description]
                equipos_sel = [dict(zip(col_names, row)) for row in cur2.fetchall()]
                cur2.close()

                n = len(equipos_sel)
                fecha_hoy = datetime.now().strftime('%Y%m%d')
                c1, c2, c3 = st.columns([1, 1, 2])

                # ── PDF único (todos en orden por área) ─────────────────────
                with c1:
                    try:
                        pdf_rsg = generar_pdf_resguardo(equipos_sel if n > 1 else equipos_sel[0]).read()
                        nombre_arch = (
                            f"resguardos_{fecha_hoy}.pdf" if n > 1
                            else f"resguardo_{(equipos_sel[0].get('nombre_equipo') or 'equipo').replace(' ','_')}_{fecha_hoy}.pdf"
                        )
                        st.download_button(
                            f"📄 PDF completo ({n} equipo{'s' if n > 1 else ''})",
                            data=pdf_rsg,
                            file_name=nombre_arch,
                            mime="application/pdf",
                            use_container_width=True, type="primary"
                        )
                    except Exception as ex:
                        st.error(f"Error al generar PDF: {ex}")

                # ── ZIP por área (solo cuando hay más de uno) ───────────────
                with c2:
                    if n > 1:
                        try:
                            zip_rsg = generar_zip_resguardos_por_area(equipos_sel).read()
                            areas_n = len({e.get("area") for e in equipos_sel if e.get("area")})
                            st.download_button(
                                f"📦 ZIP por área ({areas_n} área{'s' if areas_n != 1 else ''})",
                                data=zip_rsg,
                                file_name=f"resguardos_por_area_{fecha_hoy}.zip",
                                mime="application/zip",
                                use_container_width=True,
                            )
                        except Exception as ex:
                            st.error(f"Error al generar ZIP: {ex}")

                # ── Resumen ─────────────────────────────────────────────────
                with c3:
                    if n == 1:
                        e0 = equipos_sel[0]
                        st.info(
                            f"**{e0.get('tipo','')}** — {e0.get('nombre_equipo','')} &nbsp;|&nbsp; "
                            f"**Usuario:** {e0.get('nombre_usuario','—')} &nbsp;|&nbsp; "
                            f"**Área:** {e0.get('area','')} &nbsp;|&nbsp; "
                            f"**Modelo:** {e0.get('cpu_modelo','')} &nbsp;|&nbsp; "
                            f"**Serie:** {e0.get('cpu_serie','')}"
                        )
                    else:
                        resumen = ", ".join(
                            f"{e.get('nombre_equipo') or e.get('cpu_modelo','?')} ({e.get('nombre_usuario','—')})"
                            for e in equipos_sel[:6]
                        )
                        if n > 6:
                            resumen += f"... y {n-6} más"
                        st.info(f"**{n} equipos seleccionados:** {resumen}")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    # ── Tab 6: Agregar ────────────────────────────────────────────────────────
    with tab6:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_usuario, nombre || ' ' || apellido_paterno FROM usuarios WHERE activo=true ORDER BY apellido_paterno")
            _rows_agr = cur.fetchall()
            cur.close()
            _umap_agr = {r[1]: r[0] for r in _rows_agr}

            with st.form("agregar_equipo_nuevo"):
                col_t1, col_t2 = st.columns(2)
                tipo_nuevo = col_t1.selectbox("Tipo de equipo", ["Laptop", "PC Avanzada", "PC Especializada"])
                sel_usr    = col_t2.selectbox("Asignar a usuario", [""] + list(_umap_agr.keys()))
                col1, col2, col3 = st.columns(3)
                num_inv   = col1.text_input("N° Inventario")
                nombre_eq = col2.text_input("Nombre equipo (ej: IMJ-01)")
                area_eq   = col3.text_input("Área / Departamento")
                col4, col5, col6 = st.columns(3)
                cpu_marca  = col4.text_input("Marca CPU")
                cpu_modelo = col5.text_input("Modelo CPU")
                cpu_serie  = col6.text_input("Serie CPU")
                col7, col8 = st.columns(2)
                ipv4_nuevo = col7.text_input("IPv4")
                mac_nuevo  = col8.text_input("MAC Address")
                obs_nuevo  = st.text_area("Observaciones", height=68)

                if st.form_submit_button("✅ Agregar equipo"):
                    if not cpu_marca:
                        st.warning("Ingresa al menos la Marca del CPU.")
                    else:
                        _id_u_agr = _umap_agr.get(sel_usr) if sel_usr else None
                        _nom_u    = sel_usr if sel_usr else None
                        cur2 = get_conn().cursor()
                        cur2.execute("""
                            INSERT INTO inventario_equipos
                                (tipo, num_inventario, nombre_equipo, nombre_usuario, area,
                                 cpu_marca, cpu_modelo, cpu_serie, ipv4, mac, observaciones, id_usuario)
                            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                        """, (tipo_nuevo, num_inv or None, nombre_eq or None, _nom_u, area_eq or None,
                              cpu_marca, cpu_modelo or None, cpu_serie or None,
                              ipv4_nuevo or None, mac_nuevo or None, obs_nuevo or None, _id_u_agr))
                        get_conn().commit()
                        cur2.close()
                        st.success(f"✅ Equipo **{nombre_eq or cpu_modelo}** registrado correctamente.")
                        st.rerun()
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

# ════════════════════════════════════════
# TELEFONOS
# ════════════════════════════════════════
elif menu == "📱 Telefonos":
    st.subheader("📱 Telefonos")
    tab1, tab2 = st.tabs(["Gestionar", "Agregar"])

    with tab1:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_usuario, nombre || ' ' || apellido_paterno FROM usuarios ORDER BY apellido_paterno")
            rows_u = cur.fetchall()
            usuarios_map   = {r[1]: r[0] for r in rows_u}
            usuarios_names = list(usuarios_map.keys())

            col_f1, col_f2 = st.columns([1, 2])
            ext_f    = col_f1.text_input("Buscar extension", placeholder="Ej: 1234...", key="tel_ext_f")
            nombre_f = col_f2.text_input("Buscar usuario",   placeholder="Ej: Juan...",  key="tel_nom_f")

            cur.execute("""
                SELECT t.id_telefono, t.numero_general, t.extension::text,
                    COALESCE(u.nombre || ' ' || u.apellido_paterno, '') as usuario
                FROM telefonos t LEFT JOIN usuarios u ON t.id_usuario = u.id_usuario
                ORDER BY u.apellido_paterno
            """)
            rows = cur.fetchall()
            cur.close()

            df = pd.DataFrame(rows, columns=["id_telefono", "numero_general", "extension", "usuario"])

            if ext_f:
                df = df[df["extension"].astype(str).str.contains(ext_f, case=False, na=False)].reset_index(drop=True)
            if nombre_f:
                df = df[df["usuario"].str.contains(nombre_f, case=False, na=False)].reset_index(drop=True)

            st.caption(f"{len(df)} telefonos — edita en la tabla y presiona **Guardar cambios**. Cambia Usuario para reasignar.")

            df_edited = st.data_editor(
                df,
                use_container_width=True,
                hide_index=True,
                num_rows="fixed",
                key="tel_data_editor",
                column_config={
                    "id_telefono":    None,
                    "numero_general": st.column_config.TextColumn("Numero General"),
                    "extension":      st.column_config.TextColumn("Extension"),
                    "usuario":        st.column_config.SelectboxColumn("Usuario", options=usuarios_names),
                },
            )

            col_btn, col_exp, col_pdf = st.columns([1, 2, 1])
            with col_btn:
                guardar_tel = st.button("💾 Guardar cambios", type="primary", use_container_width=True, key="tel_guardar")
            with col_exp:
                if not df_edited.empty:
                    _df_exp = df_edited.drop(columns=["id_telefono"], errors="ignore").rename(columns={
                        "numero_general": "Numero General", "extension": "Extension", "usuario": "Usuario",
                    })
                    excel_buf = generar_excel_formateado(_df_exp, "Telefonos")
                    st.download_button("📥 Exportar Excel", data=excel_buf.getvalue(),
                                    file_name=f"telefonos_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                    use_container_width=True, key="tel_export")
            with col_pdf:
                if not df_edited.empty:
                    try:
                        _df_p = df_edited.drop(columns=["id_telefono"], errors="ignore").rename(columns={
                            "numero_general": "Numero General", "extension": "Extension", "usuario": "Usuario",
                        })
                        st.download_button("📄 PDF", data=generar_pdf_generico(_df_p, "Telefonos").read(),
                                        file_name=f"telefonos_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                                        mime="application/pdf", use_container_width=True, key="tel_pdf")
                    except Exception as e:
                        st.error(f"Error PDF: {e}")

            if guardar_tel:
                try:
                    cur2 = get_conn().cursor()
                    count = 0
                    for _, row in df_edited.iterrows():
                        id_usuario = usuarios_map.get(row.get("usuario"))
                        cur2.execute("""
                            UPDATE telefonos
                            SET numero_general=%s, extension=%s, id_usuario=%s
                            WHERE id_telefono=%s
                        """, (
                            row.get("numero_general") or None,
                            row.get("extension") or None,
                            id_usuario,
                            int(row["id_telefono"]),
                        ))
                        count += 1
                    get_conn().commit()
                    cur2.close()
                    st.success(f"✅ {count} telefono(s) guardados correctamente.")
                    st.rerun()
                except Exception as e:
                    safe_rollback()
                    st.error(f"Error al guardar: {e}")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab2:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
            usuarios = cur.fetchall()
            cur.close()
            opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
            with st.form("agregar_telefono"):
                numero    = st.text_input("Numero general", value="(55)1500 1300")
                extension = st.text_input("Extension")
                sel_usr   = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
                if st.form_submit_button("✅ Agregar"):
                    if extension:
                        cur2 = get_conn().cursor()
                        cur2.execute("INSERT INTO telefonos (numero_general, extension, id_usuario) VALUES (%s, %s, %s)",
                                    (numero, extension, opciones_usr[sel_usr]))
                        get_conn().commit()
                        cur2.close()
                        st.success("✅ Telefono agregado correctamente.")
                    else:
                        st.warning("Por favor escribe la extension.")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

# ════════════════════════════════════════
# IMPRESORAS
# ════════════════════════════════════════
elif menu == "🖨️ Impresoras":
    st.subheader("🖨️ Impresoras")
    tab1, tab2 = st.tabs(["Gestionar", "Agregar"])

    with tab1:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_usuario, nombre || ' ' || apellido_paterno FROM usuarios ORDER BY apellido_paterno")
            rows_u = cur.fetchall()
            usuarios_map   = {r[1]: r[0] for r in rows_u}
            usuarios_names = list(usuarios_map.keys())

            cur.execute("""
                SELECT i.id_impresora, i.marca, i.modelo, i.serie,
                    COALESCE(i.ip_address::text, '') as ip,
                    COALESCE(i.firmware, '') as firmware,
                    COALESCE(u.nombre || ' ' || u.apellido_paterno, '') as usuario
                FROM impresoras i LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
                ORDER BY u.apellido_paterno, i.marca
            """)
            rows = cur.fetchall()
            cur.close()

            df = pd.DataFrame(rows, columns=["id_impresora", "marca", "modelo", "serie", "ip", "firmware", "usuario"])

            marcas_uniq = ["Todas"] + sorted(df["marca"].dropna().unique().tolist())
            col_f1, col_f2, col_f3 = st.columns([1, 1, 2])
            marca_f   = col_f1.selectbox("Marca", marcas_uniq, key="imp_marca_f")
            ip_f      = col_f2.text_input("IP", placeholder="Ej: 192.168...", key="imp_ip_f")
            usuario_f = col_f3.text_input("Buscar usuario", placeholder="Nombre o apellido...", key="imp_usr_f")

            if marca_f != "Todas":
                df = df[df["marca"] == marca_f].reset_index(drop=True)
            if ip_f:
                df = df[df["ip"].str.contains(ip_f, case=False, na=False)].reset_index(drop=True)
            if usuario_f:
                df = df[df["usuario"].str.contains(usuario_f, case=False, na=False)].reset_index(drop=True)

            st.caption(f"{len(df)} impresoras — edita en la tabla y presiona **Guardar cambios**. Cambia Usuario para reasignar.")

            df_edited = st.data_editor(
                df,
                use_container_width=True,
                hide_index=True,
                num_rows="fixed",
                key="imp_data_editor",
                column_config={
                    "id_impresora": None,
                    "marca":        st.column_config.TextColumn("Marca"),
                    "modelo":       st.column_config.TextColumn("Modelo"),
                    "serie":        st.column_config.TextColumn("Serie"),
                    "ip":           st.column_config.TextColumn("IP"),
                    "firmware":     st.column_config.TextColumn("Firmware"),
                    "usuario":      st.column_config.SelectboxColumn("Usuario", options=usuarios_names),
                },
            )

            col_btn, col_exp, col_pdf = st.columns([1, 2, 1])
            with col_btn:
                guardar_imp = st.button("💾 Guardar cambios", type="primary", use_container_width=True, key="imp_guardar")
            with col_exp:
                if not df_edited.empty:
                    _df_exp = df_edited.drop(columns=["id_impresora"], errors="ignore").rename(columns={
                        "marca": "Marca", "modelo": "Modelo", "serie": "Serie",
                        "ip": "IP Address", "firmware": "Firmware", "usuario": "Usuario",
                    })
                    excel_buf = generar_excel_formateado(_df_exp, "Impresoras")
                    st.download_button("📥 Exportar Excel", data=excel_buf.getvalue(),
                                    file_name=f"impresoras_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                    use_container_width=True, key="imp_export")
            with col_pdf:
                if not df_edited.empty:
                    try:
                        _df_p = df_edited.drop(columns=["id_impresora"], errors="ignore").rename(columns={
                            "marca": "Marca", "modelo": "Modelo", "serie": "Serie",
                            "ip": "IP Address", "firmware": "Firmware", "usuario": "Usuario",
                        })
                        st.download_button("📄 PDF", data=generar_pdf_generico(_df_p, "Impresoras").read(),
                                        file_name=f"impresoras_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                                        mime="application/pdf", use_container_width=True, key="imp_pdf")
                    except Exception as e:
                        st.error(f"Error PDF: {e}")

            if guardar_imp:
                errores = []
                for _, row in df_edited.iterrows():
                    ip_val = str(row.get("ip", "") or "").strip()
                    if ip_val and not validar_ip_format(ip_val):
                        errores.append(f"IP invalida: {ip_val}")
                if errores:
                    for err in errores:
                        st.warning(err)
                else:
                    try:
                        cur2 = get_conn().cursor()
                        count = 0
                        for _, row in df_edited.iterrows():
                            id_usuario = usuarios_map.get(row.get("usuario"))
                            ip_db = str(row.get("ip") or "").strip() or None
                            cur2.execute("""
                                UPDATE impresoras
                                SET marca=%s, modelo=%s, serie=%s,
                                    ip_address=%s, firmware=%s, id_usuario=%s
                                WHERE id_impresora=%s
                            """, (
                                row.get("marca") or None,
                                row.get("modelo") or None,
                                row.get("serie") or None,
                                ip_db,
                                row.get("firmware") or None,
                                id_usuario,
                                int(row["id_impresora"]),
                            ))
                            count += 1
                        get_conn().commit()
                        cur2.close()
                        st.success(f"✅ {count} impresora(s) guardadas correctamente.")
                        st.rerun()
                    except Exception as e:
                        safe_rollback()
                        st.error(f"Error al guardar: {e}")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab2:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_usuario, nombre, apellido_paterno FROM usuarios ORDER BY apellido_paterno")
            usuarios = cur.fetchall()
            cur.close()
            opciones_usr = {f"{u[1]} {u[2]}": u[0] for u in usuarios}
            with st.form("agregar_impresora"):
                col1, col2 = st.columns(2)
                marca   = col1.text_input("Marca")
                modelo  = col2.text_input("Modelo")
                col3, col4 = st.columns(2)
                serie   = col3.text_input("Serie")
                ip      = col4.text_input("IP Address")
                firmware = st.text_input("Firmware")
                sel_usr  = st.selectbox("Asignar a usuario", list(opciones_usr.keys()))
                if st.form_submit_button("✅ Agregar"):
                    errores = []
                    if not marca or not modelo:
                        errores.append("Llena al menos Marca y Modelo.")
                    if ip and not validar_ip_format(ip):
                        errores.append("Formato de IP no valido. Usa X.X.X.X")
                    if errores:
                        for err in errores:
                            st.warning(err)
                    else:
                        cur2 = get_conn().cursor()
                        cur2.execute("""
                            INSERT INTO impresoras (marca, modelo, serie, ip_address, firmware, id_usuario)
                            VALUES (%s, %s, %s, %s, %s, %s)
                        """, (marca, modelo, serie, ip, firmware, opciones_usr[sel_usr]))
                        get_conn().commit()
                        cur2.close()
                        st.success(f"✅ Impresora {marca} {modelo} agregada.")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

# ════════════════════════════════════════
# INSUMOS
# ════════════════════════════════════════
elif menu == "📦 Insumos":
    st.subheader("📦 Insumos")
    tab1, tab2, tab3 = st.tabs(["Gestionar", "Agregar", "Suministro"])

    with tab1:
        try:
            cur = get_conn().cursor()
            cur.execute("""
                SELECT id_insumo, nombre_insumo, numero_parte,
                    stock_minimo, stock_maximo, stock_actual
                FROM insumos ORDER BY nombre_insumo
            """)
            rows = cur.fetchall()
            cur.close()

            df = pd.DataFrame(rows, columns=[
                "id_insumo", "nombre", "numero_parte", "stock_min", "stock_max", "stock_actual"
            ])

            col_f1, col_f2 = st.columns([2, 1])
            busqueda_ins = col_f1.text_input("Buscar insumo", placeholder="Nombre o numero de parte...", key="ins_busq")
            stock_f = col_f2.selectbox("Stock", ["Todos", "Stock bajo / agotado", "Stock normal"], key="ins_stock_f")

            if busqueda_ins:
                mask = df[["nombre", "numero_parte"]].apply(
                    lambda col: col.astype(str).str.contains(busqueda_ins, case=False, na=False)
                ).any(axis=1)
                df = df[mask].reset_index(drop=True)
            if stock_f == "Stock bajo / agotado":
                df = df[df["stock_actual"] <= df["stock_min"]].reset_index(drop=True)
            elif stock_f == "Stock normal":
                df = df[df["stock_actual"] > df["stock_min"]].reset_index(drop=True)

            st.caption(f"{len(df)} insumos — edita nombre, numero de parte y niveles min/max. El stock actual se gestiona en la pestaña Suministro.")

            df_edited = st.data_editor(
                df,
                use_container_width=True,
                hide_index=True,
                num_rows="fixed",
                key="ins_data_editor",
                column_config={
                    "id_insumo":    None,
                    "nombre":       st.column_config.TextColumn("Insumo"),
                    "numero_parte": st.column_config.TextColumn("No. Parte"),
                    "stock_min":    st.column_config.NumberColumn("Stock Min",  min_value=0, step=1),
                    "stock_max":    st.column_config.NumberColumn("Stock Max",  min_value=0, step=1),
                    "stock_actual": st.column_config.NumberColumn("Stock Actual", disabled=True),
                },
            )

            col_btn, col_exp, col_pdf = st.columns([1, 2, 1])
            with col_btn:
                guardar_ins = st.button("💾 Guardar cambios", type="primary", use_container_width=True, key="ins_guardar")
            with col_exp:
                if not df_edited.empty:
                    _df_exp = df_edited.drop(columns=["id_insumo"], errors="ignore").rename(columns={
                        "nombre": "Insumo", "numero_parte": "No. Parte",
                        "stock_min": "Stock Min", "stock_max": "Stock Max", "stock_actual": "Stock Actual",
                    })
                    excel_buf = generar_excel_formateado(_df_exp, "Insumos")
                    st.download_button("📥 Exportar Excel", data=excel_buf.getvalue(),
                                    file_name=f"insumos_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                    use_container_width=True, key="ins_export")
            with col_pdf:
                if not df_edited.empty:
                    try:
                        _df_p = df_edited.drop(columns=["id_insumo"], errors="ignore").rename(columns={
                            "nombre": "Insumo", "numero_parte": "No. Parte",
                            "stock_min": "Stock Min", "stock_max": "Stock Max", "stock_actual": "Stock Actual",
                        })
                        st.download_button("📄 PDF", data=generar_pdf_generico(_df_p, "Insumos").read(),
                                        file_name=f"insumos_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                                        mime="application/pdf", use_container_width=True, key="ins_pdf")
                    except Exception as e:
                        st.error(f"Error PDF: {e}")

            if guardar_ins:
                try:
                    cur2 = get_conn().cursor()
                    count = 0
                    for _, row in df_edited.iterrows():
                        cur2.execute("""
                            UPDATE insumos
                            SET nombre_insumo=%s, numero_parte=%s,
                                stock_minimo=%s, stock_maximo=%s
                            WHERE id_insumo=%s
                        """, (
                            row.get("nombre") or None,
                            row.get("numero_parte") or None,
                            int(row.get("stock_min") or 0),
                            int(row.get("stock_max") or 0),
                            int(row["id_insumo"]),
                        ))
                        count += 1
                    get_conn().commit()
                    cur2.close()
                    st.success(f"✅ {count} insumo(s) guardados correctamente.")
                    st.rerun()
                except Exception as e:
                    safe_rollback()
                    st.error(f"Error al guardar: {e}")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab2:
        try:
            with st.form("agregar_insumo"):
                nombre    = st.text_input("Nombre del insumo")
                num_parte = st.text_input("Numero de parte")
                col1, col2, col3 = st.columns(3)
                stock_min = col1.number_input("Stock minimo", min_value=0, value=5)
                stock_max = col2.number_input("Stock maximo", min_value=0, value=50)
                stock_act = col3.number_input("Stock actual",  min_value=0, value=0)
                if st.form_submit_button("✅ Agregar"):
                    if nombre:
                        cur2 = get_conn().cursor()
                        cur2.execute("""
                            INSERT INTO insumos (nombre_insumo, numero_parte, stock_minimo, stock_maximo, stock_actual)
                            VALUES (%s, %s, %s, %s, %s)
                        """, (nombre, num_parte, stock_min, stock_max, stock_act))
                        get_conn().commit()
                        cur2.close()
                        st.success(f"✅ Insumo '{nombre}' agregado.")
                    else:
                        st.warning("Escribe el nombre del insumo.")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab3:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT id_insumo, nombre_insumo, stock_actual FROM insumos ORDER BY nombre_insumo")
            insumos = cur.fetchall()
            cur.execute("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre")
            deptos = cur.fetchall()
            cur.close()
            opciones_ins = {f"{i[1]} (stock: {i[2]})": (i[0], i[2]) for i in insumos}
            sel_ins = st.selectbox("Selecciona el insumo", list(opciones_ins.keys()), key="sum_ins_sel")
            id_insumo, stock_actual = opciones_ins[sel_ins]
            depto_sel = st.selectbox("Departamento solicitante", [d[1] for d in deptos], key="sum_depto_sel")
            id_depto  = next(d[0] for d in deptos if d[1] == depto_sel)
            cantidad  = st.number_input("Cantidad a entregar", min_value=1, value=1, key="sum_cant")
            if st.button("📤 Registrar entrega", key="sum_btn"):
                if cantidad > stock_actual:
                    st.error(f"❌ Stock insuficiente. Stock actual: {stock_actual}")
                else:
                    cur2 = get_conn().cursor()
                    cur2.execute("""
                        INSERT INTO suministros (id_insumo, id_departamento, fecha_solicitud,
                                                cantidad_requerida, cantidad_entregada, estatus)
                        VALUES (%s, %s, CURRENT_DATE, %s, %s, %s)
                    """, (id_insumo, id_depto, cantidad, cantidad, "entregado"))
                    cur2.execute("UPDATE insumos SET stock_actual = stock_actual - %s WHERE id_insumo = %s",
                                (cantidad, id_insumo))
                    get_conn().commit()
                    cur2.close()
                    st.success(f"✅ Entrega registrada. Nuevo stock: {stock_actual - cantidad}")
                    st.rerun()
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

# ════════════════════════════════════════
# DIRECCIONAMIENTO IP
# ════════════════════════════════════════
elif menu == "🌐 Direccionamiento IP":
    st.subheader("🌐 Gestion de Direccionamiento IP - IMJUVE")
    tab1, tab2 = st.tabs(["Rangos y Disponibilidad", "Gestionar IPs"])

    with tab1:
        try:
            cur = get_conn().cursor()
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
            cur.close()

            if resumen_data:
                df_r = pd.DataFrame(resumen_data, columns=["Area", "Rango de IPs", "Total IPs", "Ocupadas", "Libres", "En Conflicto"])
                df_r['%_num'] = (df_r['Ocupadas'] / df_r['Total IPs'] * 100).round(1)
                df_r['% Usado'] = df_r['%_num'].astype(str) + '%'

                def color_semaforo(val):
                    try:
                        num = float(val.replace('%', ''))
                        if num >= 90:   color = '#ff4b4b'
                        elif num >= 70: color = '#ffa500'
                        else:           color = '#09ab3b'
                        return f'background-color: {color}; color: white; font-weight: bold'
                    except Exception:
                        return ''

                df_styled = df_r.drop(columns=['%_num']).style.map(color_semaforo, subset=['% Usado'])
                st.table(df_styled)
            else:
                st.info("No hay datos en el catalogo de rangos. Verifica 'cat_rangos_ips'.")
        except Exception as e:
            safe_rollback()
            st.error(f"Error: {e}")

    with tab2:
        try:
            cur = get_conn().cursor()
            cur.execute("SELECT DISTINCT departamento_pestana FROM inventario_ips_completo WHERE departamento_pestana IS NOT NULL ORDER BY departamento_pestana")
            areas_db = [a[0] for a in cur.fetchall()]
            cur.execute("SELECT id_usuario, nombre || ' ' || apellido_paterno FROM usuarios ORDER BY apellido_paterno")
            rows_u_ip = cur.fetchall()
            cur.close()

            usuarios_ip_map   = {r[1]: r[0] for r in rows_u_ip}
            usuarios_ip_names = [""] + list(usuarios_ip_map.keys())

            col_f1, col_f2, col_f3 = st.columns([1, 1, 2])
            area_sel    = col_f1.selectbox("Area", ["Todas"] + areas_db, key="gest_ip_area")
            estatus_sel = col_f2.selectbox("Estatus", ["Todos", "Libre", "Ocupada", "En Conflicto"], key="gest_ip_est")
            busqueda    = col_f3.text_input("Buscar IP, usuario, MAC, marca...", placeholder="Ej: 172.17... o Juan...", key="gest_ip_busq")

            query = """
                SELECT i.ip,
                    i.usuario AS usuario_nombre,
                    i.tipo_equipo, i.institucional_o_personal,
                    i.mac, i.marca, i.modelo, i.serie, i.estatus,
                    i.departamento_pestana, i.observaciones
                FROM inventario_ips_completo i
                WHERE 1=1
            """
            params = []
            if area_sel != "Todas":
                query += " AND i.departamento_pestana = %s"
                params.append(area_sel)
            if estatus_sel != "Todos":
                query += " AND i.estatus ILIKE %s"
                params.append(f"{estatus_sel}%")
            if busqueda:
                query += """
                    AND (i.ip ILIKE %s OR i.usuario ILIKE %s OR i.mac ILIKE %s
                        OR i.marca ILIKE %s OR i.serie ILIKE %s
                        OR u.nombre ILIKE %s OR u.apellido_paterno ILIKE %s)
                """
                t = f"%{busqueda}%"
                params.extend([t, t, t, t, t, t, t])
            query += " ORDER BY i.departamento_pestana, i.ip"

            cur2 = get_conn().cursor()
            cur2.execute(query, params)
            cols = ["ip","usuario_nombre","tipo_equipo","institucional_o_personal",
                    "mac","marca","modelo","serie","estatus","departamento_pestana","observaciones"]
            df = pd.DataFrame(cur2.fetchall(), columns=cols)
            cur2.close()

            st.caption(f"{len(df)} IPs encontradas — edita directamente en la tabla y presiona **Guardar cambios**. Para liberar una IP cambia el Estatus a **Libre**.")

            TIPOS = ["PC", "Laptop", "Impresora", "Servidor", "Switch", "Camara", "Otro"]
            USOS  = ["Institucional", "Personal"]
            ESTAT = ["Libre", "Ocupada", "En Conflicto"]

            df_edited = st.data_editor(
                df,
                use_container_width=True,
                hide_index=True,
                num_rows="fixed",
                key="ip_data_editor",
                column_config={
                    "ip":                      st.column_config.TextColumn("IP", disabled=True),
                    "departamento_pestana":    st.column_config.TextColumn("Area", disabled=True),
                    "usuario_nombre":          st.column_config.TextColumn("Usuario"),
                    "tipo_equipo":             st.column_config.SelectboxColumn("Tipo Equipo", options=TIPOS),
                    "institucional_o_personal":st.column_config.SelectboxColumn("Uso", options=USOS),
                    "mac":                     st.column_config.TextColumn("MAC"),
                    "marca":                   st.column_config.TextColumn("Marca"),
                    "modelo":                  st.column_config.TextColumn("Modelo"),
                    "serie":                   st.column_config.TextColumn("Serie"),
                    "estatus":                 st.column_config.SelectboxColumn("Estatus", options=ESTAT, required=True),
                    "observaciones":           st.column_config.TextColumn("Observaciones"),
                },
            )

            _RENAME_IPS = {
                "ip": "IP", "usuario_nombre": "Usuario", "tipo_equipo": "Tipo Equipo",
                "institucional_o_personal": "Uso", "mac": "MAC", "marca": "Marca",
                "modelo": "Modelo", "serie": "Serie", "estatus": "Estatus",
                "departamento_pestana": "Area", "observaciones": "Observaciones",
            }

            col_btn, col_exp, col_pdf_ip, col_full = st.columns([1, 1, 1, 1])
            with col_btn:
                guardar = st.button("💾 Guardar cambios", type="primary", use_container_width=True)
            with col_exp:
                if not df_edited.empty:
                    _df_exp = df_edited.drop(columns=["id_usuario"], errors="ignore").rename(columns=_RENAME_IPS)
                    excel_data = generar_excel_formateado(_df_exp, "IPs")
                    st.download_button("📥 Excel filtro", data=excel_data.getvalue(),
                                    file_name=f"ips_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                                    mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                    use_container_width=True)
            with col_pdf_ip:
                if not df_edited.empty:
                    try:
                        _df_p = df_edited.drop(columns=["id_usuario"], errors="ignore").rename(columns=_RENAME_IPS)
                        st.download_button("📄 PDF filtro", data=generar_pdf_generico(_df_p, "Direccionamiento IP").read(),
                                        file_name=f"ips_{datetime.now().strftime('%Y%m%d_%H%M')}.pdf",
                                        mime="application/pdf", use_container_width=True, key="ip_pdf")
                    except Exception as e:
                        st.error(f"Error PDF: {e}")
            with col_full:
                try:
                    cur_all = get_conn().cursor()
                    cur_all.execute("""
                        SELECT i.ip,
                            i.usuario AS usuario_nombre,
                            i.tipo_equipo, i.institucional_o_personal,
                            i.mac, i.marca, i.modelo, i.serie, i.estatus,
                            i.departamento_pestana, i.observaciones
                        FROM inventario_ips_completo i
                        ORDER BY i.departamento_pestana,
                            CASE WHEN i.estatus = 'Ocupada'      THEN 1
                                    WHEN i.estatus = 'Libre'        THEN 2
                                    ELSE 3 END,
                                i.ip
                    """)
                    rows_all = cur_all.fetchall()
                    cur_all.close()

                    if rows_all:
                        from openpyxl import Workbook
                        from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
                        from openpyxl.utils import get_column_letter

                        COLS_DISPLAY = {
                            "ip": "IP", "usuario_nombre": "Usuario", "tipo_equipo": "Tipo Equipo",
                            "institucional_o_personal": "Uso", "mac": "MAC",
                            "marca": "Marca", "modelo": "Modelo", "serie": "Serie",
                            "estatus": "Estatus", "departamento_pestana": "Area",
                            "observaciones": "Observaciones",
                        }
                        col_keys = list(COLS_DISPLAY.keys())
                        col_headers = list(COLS_DISPLAY.values())

                        df_all = pd.DataFrame(rows_all, columns=col_keys)
                        areas = df_all["departamento_pestana"].dropna().unique().tolist()

                        AZUL    = "1A3C5E"
                        CELESTE = "EAF1FB"
                        NARANJA = "FFF2CC"
                        BORDE   = "BFBFBF"
                        borde = Border(
                            left=Side(style="thin", color=BORDE),
                            right=Side(style="thin", color=BORDE),
                            top=Side(style="thin", color=BORDE),
                            bottom=Side(style="thin", color=BORDE),
                        )
                        fill_libre   = PatternFill("solid", fgColor=CELESTE)
                        fill_ocup    = PatternFill("solid", fgColor="FFFFFF")
                        fill_header  = PatternFill("solid", fgColor=AZUL)

                        wb = Workbook()
                        wb.remove(wb.active)

                        for area in sorted(areas):
                            df_area = df_all[df_all["departamento_pestana"] == area].copy()
                            nombre_hoja = area[:31]
                            ws = wb.create_sheet(title=nombre_hoja)

                            for ci, h in enumerate(col_headers, 1):
                                c = ws.cell(row=1, column=ci, value=h)
                                c.font      = Font(bold=True, color="FFFFFF", size=10)
                                c.fill      = fill_header
                                c.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
                                c.border    = borde
                            ws.row_dimensions[1].height = 26
                            ws.freeze_panes = "A2"

                            for ri, (_, row) in enumerate(df_area.iterrows(), 2):
                                es_libre = str(row.get("estatus", "")).strip().lower() == "libre"
                                for ci, key in enumerate(col_keys, 1):
                                    val = row[key]
                                    if val is None or (isinstance(val, float) and pd.isna(val)):
                                        val = ""
                                    c = ws.cell(row=ri, column=ci, value=val)
                                    c.font      = Font(size=9)
                                    c.alignment = Alignment(vertical="center")
                                    c.border    = borde
                                    c.fill      = fill_libre if es_libre else fill_ocup

                            for ci, (key, header) in enumerate(COLS_DISPLAY.items(), 1):
                                vals = [header] + [
                                    str(r[key]) if r[key] is not None and not (isinstance(r[key], float) and pd.isna(r[key])) else ""
                                    for _, r in df_area.iterrows()
                                ]
                                ws.column_dimensions[get_column_letter(ci)].width = min(max(len(v) for v in vals) + 4, 40)

                        buf_all = BytesIO()
                        wb.save(buf_all)
                        buf_all.seek(0)

                        st.download_button(
                            "📊 Descargar TODAS las IPs (por area)",
                            data=buf_all.getvalue(),
                            file_name=f"IPs_completo_{datetime.now().strftime('%Y%m%d_%H%M')}.xlsx",
                            mime="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                            use_container_width=True,
                            type="secondary",
                        )
                except Exception as e:
                    st.error(f"Error al generar Excel completo: {e}")

            if guardar:
                def limpiar(v):
                    return None if (v is None or (isinstance(v, float) and pd.isna(v)) or str(v).strip() in ('', 'nan', 'None')) else str(v).strip()

                try:
                    cur3 = get_conn().cursor()
                    count = 0
                    for _, row in df_edited.iterrows():
                        estatus = limpiar(row.get('estatus')) or 'Libre'
                        if estatus.lower() == 'libre':
                            cur3.execute("""
                                UPDATE inventario_ips_completo
                                SET usuario=NULL,
                                    tipo_equipo=NULL, institucional_o_personal=NULL,
                                    mac=NULL, marca=NULL, modelo=NULL, serie=NULL,
                                    estatus='Libre', observaciones=NULL
                                WHERE ip=%s
                            """, (row['ip'],))
                        else:
                            nombre_sel  = limpiar(row.get('usuario_nombre'))
                            cur3.execute("""
                                UPDATE inventario_ips_completo
                                SET usuario=%s,
                                    tipo_equipo=%s, institucional_o_personal=%s,
                                    mac=%s, marca=%s, modelo=%s, serie=%s,
                                    estatus=%s, observaciones=%s
                                WHERE ip=%s
                            """, (
                                nombre_sel,
                                limpiar(row.get('tipo_equipo')),
                                limpiar(row.get('institucional_o_personal')),
                                limpiar(row.get('mac')),
                                limpiar(row.get('marca')),
                                limpiar(row.get('modelo')),
                                limpiar(row.get('serie')),
                                estatus,
                                limpiar(row.get('observaciones')),
                                row['ip'],
                            ))
                        count += 1
                    get_conn().commit()
                    cur3.close()
                    st.success(f"✅ {count} IP(s) guardadas correctamente.")
                    st.rerun()
                except Exception as e:
                    safe_rollback()
                    st.error(f"Error al guardar: {e}")

        except Exception as e:
            safe_rollback()
            st.error(f"Error en el sistema: {e}")
            st.warning("Verifica la conexion y las tablas en pgAdmin.")
