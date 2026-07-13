# Flujo de datos: registro de equipos (resguardo)

`resguardo-preview.blade.php` es el punto principal de captura de datos del
sistema: desde ahí se da de alta un equipo (Laptop, PC Avanzada, PC
Especializada) y se resuelve tanto su responsable como su dirección IP.

## 1. Flujo completo

```
Subir PDF (resguardo-subir)
        │  KardexController::extraerResguardo()
        │  - Extrae texto del PDF (Smalot\PdfParser)
        │  - Si tiene texto: intenta leer nombre/tipo/serie/área del texto
        │  - Busca candidatos en `users` por coincidencia de nombre/apellido
        │  - Guarda todo en sesión, mueve el PDF a resguardos_tmp/
        ▼
Preview (resguardo-preview) — KardexController::mostrarPreview()
        │  Aquí se decide:
        │  - Tipo, marca, modelo, serie, periféricos (formulario editable)
        │  - RESPONSABLE (ver §2)
        │  - IP (ver §3)
        ▼
Guardar — KardexController::guardarResguardo()
        │  - Resuelve/crea el responsable en `users`
        │  - Resuelve la IP contra `inventario_ips_completo` (tabla maestra)
        │  - Inserta la fila en `inventario_equipos`
        │  - Mueve el PDF de resguardos_tmp/ a resguardos/{id}.pdf
        │    (Storage::disk('local'); solo la RUTA queda en
        │     inventario_equipos.pdf_resguardo)
        ▼
Redirect a /kardex con mensaje de éxito
```

## 2. Cómo se resuelve el responsable

Tres formas, todas terminan en un único campo de formulario
`name="id_empleado"`:

| Forma | Cómo funciona |
|---|---|
| **Candidato detectado por OCR** | `extraerResguardo()` busca en `users` por nombre/apellido extraído del PDF. Aparecen como radios. |
| **Acceso rápido: Directores** | Dropdown con `users.puesto LIKE '%Director%'`. Al elegir uno, si ya está en la lista de candidatos se marca ese radio; si no, se inyecta uno nuevo con JS. |
| **Crear nueva persona** | Radio `value="__nuevo__"` que revela un mini-formulario (nombre, apellidos, correo opcional, puesto opcional). Al guardar, se crea la fila en `users` con el mismo mecanismo que usa CRM (`App\Support\NewAccountProvisioner`): correo temporal tipo `empleadoXXXX@placeholder.imjuve.local` si no se captura uno real, password aleatoria hasheada, `role='user'`. |

**El enlace usa `users.id` (número estable), nunca correo/nombre/área.** Esto
es deliberado: el correo de las cuentas migradas es un placeholder temporal
que cambiará cuando se construya el sistema de código de recuperación (ver
`Doc/decisiones-pendientes-cliente.md` §1) — un enlace basado en texto se
rompería justo en ese momento. `users.id` nunca cambia.

## 3. Cómo se resuelve la IP

`inventario_ips_completo` es la tabla maestra de IPs (única, con FK real
desde `inventario_equipos.ip_id` — ver `Doc/` para la migración que la
introdujo). Dos caminos:

- **Switcheo** — si la persona elegida (existente) ya tiene una IP en algún
  otro equipo suyo, el formulario la autocompleta (`data-ip` en cada radio,
  calculado con una subconsulta correlacionada en `mostrarPreview()`). Al
  guardar, `guardarResguardo()` libera esa IP del equipo viejo
  (`ip_id`/`ipv4` a `null`) y la reasigna al nuevo — la persona conserva su
  identidad de red al cambiar de equipo.
- **Sugerencia por área** — si la persona no tiene IP previa (o es una
  persona nueva), el botón "Sugerir IP" consulta el rango de
  `cat_rangos_ips` para esa área y ofrece la primera IP con
  `estatus <> 'Libre'` ausente en `inventario_ips_completo` (ver
  `KardexController::sugerirIp()`).

Si la IP resuelta ya pertenece a **otro dispositivo** (equipo de otra
persona, o una impresora), se bloquea con un error — eso sigue siendo un
conflicto real, no un switcheo.

## 4. Por qué no hay una base de datos separada para PDFs

El PDF nunca vive dentro de la base de datos relacional: se guarda en disco
(`storage/app/private/resguardos/`, vía `Storage::disk('local')`) y
`inventario_equipos.pdf_resguardo` solo guarda la ruta del archivo. Este
patrón ya evita cualquier impacto de los PDFs sobre el rendimiento de la
base de datos principal — no hace falta una conexión ni un esquema aparte
para lograrlo.
