# Casos de uso

## Vocabulario de `estado_equipo`

El campo `estado_equipo` en `movimientos_equipos` tiene dos vocabularios según el tipo de evento:

**Eventos de ciclo de vida del equipo** — reflejan el badge del inventario:

| Valor | Badge | Cuándo se registra |
|---|---|---|
| `Almacén` | Almacén (azul) | `Entrada` de equipo nuevo, equipo devuelto al almacén (`almacen`), equipo sin responsable |
| `Asignado` | Asignado (verde) | `Asignación` a responsable (incluyendo la segunda en CU-01), guardado desde panel con responsable activo |
| `Mantenimiento` | Mantenimiento (amarillo) | Estado cambiado a mantenimiento desde el panel, el proveedor maneja esete estado, se deduce que se devolvera el euipo funcional |
| `Baja` | Baja (gris) | Estado cambiado a baja desde el panel, el proveedor maneja este estado el es el que determina este estado, el imjuve determina este caso si el euipo es propiedad del empleado y se da de baja ese empleado |

> **Aclaración:** `Entrada` siempre lleva `estado_equipo = 'Almacén'` — el equipo entra al inventario bajo custodia de Subdirección de Sistemas independientemente de si es físicamente nuevo o usado. No se usa `'Nuevo'` porque es imposible determinarlo retroactivamente en equipos importados. `Asignación` siempre lleva `estado_equipo = 'Asignado'`, que es el mismo valor que calcula el badge (`!is_null(user_id) → 'Asignado'`).

**Equipos personales vs. institucionales**

Un equipo es **personal** (propiedad del empleado) si no tiene ningún evento `Entrada` en `movimientos_equipos`. Esto ocurre cuando el equipo fue registrado directamente desde CRM sin subir un resguardo PDF. El IMJUVE no se hace responsable de estos equipos.

Un equipo es **institucional** si tiene al menos un evento `Entrada` — esto confirma que fue ingresado con resguardo oficial en Kardex.

| Tipo | Eventos de ciclo permitidos | Eventos de IP permitidos |
|---|---|---|
| **Personal** (sin `Entrada`) | Solo `Asignación` y `Baja` | `Asignación IP`, `Cambio IP`, `Liberación IP` |
| **Institucional** (con `Entrada`) | Todos: `Almacén`, `Asignación`, `Mantenimiento`, `Baja` | `Asignación IP`, `Cambio IP`, `Liberación IP` |
> **Swicheo** el evento swiche involucra un intercabio de equipo entre dos resposable con equipos, no se modifica ningun dato exepto a los responsables, haciedo que [responsable1 : equipo1 <---> responsable2 : equipo2] ---> [responsable1 : equipo2 <---> responsable2 : equipo1] por ende son dos eventos consecutivos de asignacion

El sistema bloquea Almacén y Mantenimiento en backend (HTTP 422) y oculta esas opciones en el dropdown del panel lateral cuando detecta un equipo personal.

**Eventos de IP** — reflejan el `estatus` de `inventario_ips_completo`:

| Valor | Filtro Network | Cuándo se registra |
|---|---|---|
| `Ocupada` | Ocupada | IP asignada o cambiada en un equipo |
| `Libre` | Libre | IP liberada (equipo a baja/mantenimiento, o switcheo) |

**FALTA acarar, como o que significa estado reservado ya que si esta en la base de ddatos pero no se sabe que quierer decir**
**Cómo se deriva el `estado_equipo` cuando se guarda desde el panel lateral** (dropdown en primera opción, sin cambio explícito de estado): el sistema revisa si el equipo tiene `user_id` asignado — si sí, registra `Asignado`; si no, registra `Almacén`. Así el historial siempre refleja lo mismo que el badge.

## Convención de eventos de IP

Los eventos `Asignación IP`, `Cambio IP` y `Liberación IP` usan `origen` y `destino` de forma distinta a los eventos de ciclo de vida:

| Campo | Contenido en eventos de IP |
|---|---|
| `origen` | `cpu_serie` del equipo que **pierde** la IP (o `Sin equipo` si no había) |
| `destino` | `cpu_serie` del equipo que **recibe** la IP (o `Sin equipo` si se libera) |
| `notas` | La dirección IP en cuestión (ej. `IP: 10.10.0.45`) |
| `estado_equipo` | `Ocupada` al asignar / `Libre` al liberar |

En los demás eventos (`Entrada`, `Asignación`, `Reasignación`, etc.), `origen` y `destino` siguen siendo nombres de personas o ubicaciones.

---

### CU-01 — Equipo nuevo con resguardo

**Descripción:** Ingreso de un equipo de cómputo al inventario a partir de un documento de resguardo (PDF).  
**Punto de entrada:** Botón "Registrar equipo" en el tab Resguardos de `/kardex` → flujo de carga de PDF.

**Datos mínimos obligatorios para un equipo:**
- Número de serie del equipo (CPU — no periféricos)
- Área donde opera
- Responsable (usuario formal que firmó el resguardo)
- IPv4 (se puede sugerir automáticamente por área)

**Responsable — formas de asignarlo:**
1. Coincidencias detectadas automáticamente desde el nombre extraído del PDF
2. Búsqueda libre entre todos los usuarios registrados (modal AJAX)
3. Acceso rápido a Directores y Subdirectores (dropdown)
4. Crear nueva persona en el mismo formulario (genera correo temporal)

**Regla:** El campo `usuario_actual_id` (Usuario físico) se inicializa igual al `user_id` (Responsable) en todo registro inicial. Se puede cambiar después desde el panel sin afectar el resguardo.

**Advertencia de responsable erróneo:** Si el PDF detectó un nombre y el usuario seleccionado no coincide, se muestra un banner naranja antes de guardar. El evento de movimiento queda anotado con el aviso y el correo del admin que lo registró.

**Cadena de eventos registrada automáticamente en `movimientos_equipos`:**

| # | Evento | Origen | Destino | Estado equipo | Notas |
|---|---|---|---|---|---|
| 1 | `Entrada` | Proveedor | Subdirección de Sistemas | Almacén | — |
| 2 | `Asignación` | Subdirección de Sistemas | Nombre del responsable | Asignado | Advertencia de mismatch si aplica |
| 3 | `Asignación IP` | `Sin equipo` | `cpu_serie` del equipo | Ocupada | `IP: 10.x.x.x` |

El evento 3 solo se registra si se capturó una IPv4. Los eventos 1 y 2 siempre se registran — no existe equipo nuevo sin responsable.

**Variante — switcheo de IP:** Si la IPv4 ingresada ya estaba asignada a otro equipo del mismo responsable, el sistema la libera de ese equipo antes de asignarla. Se registran dos eventos IP adicionales:

| # | Evento | Origen | Destino | Estado equipo | Notas |
|---|---|---|---|---|---|
| 3 | `Liberación IP` | `cpu_serie` del equipo anterior | `cpu_serie` del equipo nuevo | Libre | `IP: … — liberada por switcheo` |
| 4 | `Asignación IP` | `cpu_serie` del equipo anterior | `cpu_serie` del equipo nuevo | Ocupada | `IP: 10.x.x.x` |

**Variante — equipo propio del empleado:** Cuando el empleado llega con su propio equipo (no pasó por almacén físico), el flujo es idéntico. La `Entrada` modela la recepción documental en Subdirección de Sistemas; la `Asignación` inmediata refleja que ya estaba en manos del responsable. El `estado_equipo` de la `Entrada` queda como `Almacén` por consistencia del modelo, aunque en la práctica física el equipo nunca estuvo en almacén.

**Tab Resguardos en `/kardex`:** Muestra todos los equipos **institucionales** (con al menos un evento `Entrada` en `movimientos_equipos`), tengan o no PDF adjunto. Cada fila muestra un badge "PDF" (verde) si `pdf_resguardo` tiene valor, o "Sin PDF" (gris) si no. El criterio anterior (filtrar por `pdf_resguardo IS NOT NULL`) fue reemplazado porque dejaba fuera equipos importados del sistema legado que son institucionales pero nunca tuvieron PDF en esta base de datos.

**Archivos clave:**
- `Modules/Kardex/resources/views/resguardo-preview.blade.php` — formulario de confirmación
- `Modules/Kardex/app/Http/Controllers/KardexController.php@guardarResguardo` — lógica de guardado y movimientos
- `Modules/Kardex/app/Http/Controllers/KardexController.php@index` — query de `$resguardos` filtrada por `Entrada` events
- `app/Support/KardexMovimiento.php` — helper de inserción de eventos

---

### CU-02 — Equipo dado de baja

**Descripción:** Un equipo de cómputo o impresora sale del inventario activo por obsolescencia, robo, daño irreparable u otra causa. El equipo permanece en el sistema como registro histórico pero deja de contar como activo asignado.  
**Punto de entrada:** Panel lateral del equipo en `/kardex` → selector "Estado del equipo" → opción "Baja" → botón Guardar.

**Qué ocurre al ejecutar la baja:**
- `estado` se actualiza a `'baja'` en `inventario_equipos`
- `user_id`, `usuario_actual_id` y `nombre_usuario` se limpian — la baja desvincula al responsable
- La IP asignada se **libera automáticamente** (`IpAssigner::liberarEquipo`) y queda disponible en el pool
- El badge en la tabla cambia a "Baja" (gris)

**Eventos registrados en `movimientos_equipos`:**

| # | Evento | Origen | Destino | Estado equipo |
|---|---|---|---|---|
| 1 | `Baja` | Área del equipo (`area`) | `Proveedor` | `Baja` |
| 2 | `Liberación IP` | `cpu_serie` del equipo | `Sin equipo` | `Libre` |

El evento 2 solo se registra si el equipo tenía IP asignada. Se registra antes de llamar a `IpAssigner::liberarEquipo()`, que limpia `ip_id` e `ipv4` en `inventario_equipos` y devuelve la fila a `estatus='Libre'` en `inventario_ips_completo`.

**Diferencia con Almacén:** Almacén desvincula al responsable (`user_id = null`) pero **no libera la IP** — el equipo queda en Almacén con su IP conservada. Baja libera la IP y desvincula al responsable. El equipo de baja queda "muerto" pero trazable.

**Diferencia con Mantenimiento:** Mantenimiento libera la IP y registra `estado_equipo = 'Mantenimiento'` pero **no** desvincula al responsable — el equipo puede volver a Asignado. Baja es terminal — no hay evento de reingreso implementado aún.

**Archivos clave:**
- `Modules/Kardex/app/Http/Controllers/KardexController.php@cambiarEstadoEquipo` — lógica para equipos
- `Modules/Kardex/app/Http/Controllers/KardexController.php@cambiarEstadoImpresora` — misma lógica para impresoras
- `app/Support/IpAssigner.php@liberarEquipo` — libera la IP en `inventario_ips_completo`

---

### CU-03 — Reasignación de equipo entre empleados

**Descripción:** Un equipo ya registrado cambia de manos: el usuario físico que lo opera día a día es distinto al responsable formal. Caso típico: un director tiene el resguardo de una laptop pero se la da a un becario o persona de servicio social.  
**Punto de entrada:** Panel lateral del equipo en `/kardex` → sección "Responsable / Estado" → campo "Usuario actual" → botón "Cambiar" → selector de usuario → Guardar.

**Modelo Responsable vs. Usuario (regla central):**

| Campo | Alias | Quién es |
|---|---|---|
| `user_id` | **Responsable** | Quien firmó el resguardo. Permanece hasta que se haga un Almacén o se suba un nuevo resguardo. |
| `usuario_actual_id` | **Usuario** | Quien opera físicamente el equipo. Se puede cambiar en cualquier momento sin tocar el resguardo. |

**Qué ocurre al cambiar el usuario:**
- Solo se actualiza `usuario_actual_id`; `user_id` (responsable/resguardo) no se modifica
- El panel muestra ambos si son personas distintas (nombre del responsable + línea `↳ Usuario` en la tabla)
- La IP, el estado y el PDF de resguardo no cambian

**Evento registrado en `movimientos_equipos`:**

| Campo | Valor |
|---|---|
| `tipo_evento` | `Reasignación` |
| `origen` | Nombre del usuario anterior |
| `destino` | Nombre del nuevo usuario |
| `user_from_id` | `usuario_actual_id` antes del cambio |
| `user_to_id` | Nuevo `usuario_actual_id` |
| `notas` | `"Cambio de usuario físico del equipo."` |

**Lo que este caso NO hace:** No cambia el responsable formal ni el resguardo. Para cambiar el responsable formal hace falta subir un nuevo PDF de resguardo (CU-01).

**Archivos clave:**
- `Modules/Kardex/app/Http/Controllers/KardexController.php@cambiarUsuarioEquipo` — lógica
- `Modules/Kardex/routes/web.php` → `POST /kardex/equipo/{id}/usuario`
- `Modules/Kardex/resources/views/index.blade.php` → `toggleCambioUsuario()` / `guardarUsuario()` en el panel lateral

---

### CU-04 — Dar de baja a un empleado

**Descripción:** Un empleado deja la institución (renuncia, término de contrato, servicio social, etc.). El registro del usuario no se elimina — queda como baja lógica para preservar el historial de movimientos y resguardos.  
**Punto de entrada:** Panel lateral del empleado en `/crm` → botón "Dar de baja" → confirmación → `DELETE /crm/empleados/{id}`.

**Qué ocurre al ejecutar la baja:**

| Recurso | Acción |
|---|---|
| `users` | `activo=false`, `fecha_baja=now()`, contraseña aleatoria, `recovery_code_hash=null` |
| Equipos **personales** (sin evento `Entrada`) | `estado='baja'`, `user_id=null`, `usuario_actual_id=null`, `nombre_usuario=null`; IP liberada |
| Equipos **institucionales** (con evento `Entrada`) | `estado=null` (Almacén), `user_id=null`, `usuario_actual_id=null`, `nombre_usuario=null`; IP **conservada** |
| `impresoras` donde era responsable | `user_id=null` — desvinculadas |
| Tickets abiertos | No se cierran ni reasignan — quedan activos en el sistema |

**Teléfonos:** Al estar en `inventario_equipos`, los teléfonos del empleado siguen la misma clasificación personal/institucional que los equipos de cómputo.

**Cuenta queda bloqueada:** La contraseña se reemplaza por un hash aleatorio y el `recovery_code_hash` se anula, por lo que ninguno de los dos mecanismos de acceso (contraseña o código de recuperación) sigue funcionando aunque el registro exista.

**Eventos registrados en `movimientos_equipos` por cada equipo:**

| Tipo de equipo | Evento | Origen | Destino | Estado equipo | Notas |
|---|---|---|---|---|---|
| Personal | `Baja` | Área del empleado | `Proveedor` | `Baja` | `Desvinculado por baja del empleado — ref. usuario #{id}` |
| Personal con IP | `Liberación IP` | `cpu_serie` | `Sin equipo` | `Libre` | `IP: x.x.x.x` |
| Institucional | `Almacén` | Nombre del empleado | `Almacén` | `Almacén` | `Desvinculado por baja del empleado — ref. usuario #{id}` |

**Ticket automático por cada equipo institucional:**

Al desvincularse un equipo institucional, el sistema crea automáticamente un ticket en Tickets para que el administrador asigne un nuevo responsable de resguardo:

| Campo | Valor |
|---|---|
| `nombre` / `correo` | Admin del sistema |
| `area` | Departamento del empleado dado de baja |
| `tipo` | `Solicitud de Equipo` |
| `descripcion` | `"El empleado {nombre} fue dado de baja (ref. usuario #{id}). El equipo {tipo} {marca} {modelo} (No. serie: {serie}) quedó en Almacén. Se requiere asignar nuevo responsable de resguardo."` |
| `estado` | `1` (abierto) |

> **Pendiente — id de orden de baja:** La referencia `ref. usuario #{id}` usa el `id` del empleado en `users`. Cuando se implemente un catálogo formal de órdenes de baja, este campo se actualizará. Por ahora es suficiente para rastrear el motivo de la desvinculación en Kardex y en el ticket.

**Reactivar empleado (`PATCH /crm/empleados/{id}/reactivar`):**
- Restaura `activo=true` y anula `fecha_baja`
- **No re-vincula** ningún equipo — el empleado queda activo en el directorio pero sin activos asignados
- El administrador debe reasignar los equipos manualmente desde los tickets generados durante la baja

**Estado de los problemas identificados:**

| # | Estado | Descripción |
|---|---|---|
| 1 | ✅ Resuelto | Los equipos que regresan a Almacén ahora generan evento `Almacén` en `movimientos_equipos` con nota de baja del empleado |
| 2 | ✅ Resuelto | Las impresoras vinculadas al empleado ahora se desvinculan (`user_id = null`) al ejecutar la baja |
| 3 | ⚠️ Pendiente | `CRMController@store` todavía inserta en la tabla `telefonos` aunque los teléfonos ya se migraron a `inventario_equipos` |
| 4 | ✅ Resuelto | Campo `ipv4_actual` eliminado del INSERT de `store()` — ya no causa error al dar de alta empleados con equipos |

**Archivos clave:**
- `Modules/CRM/app/Http/Controllers/CRMController.php@destroy` — clasificación personal/institucional, eventos Kardex, tickets automáticos, baja de impresoras
- `Modules/CRM/app/Http/Controllers/CRMController.php@reactivar` — restauración del usuario
- `Modules/CRM/routes/web.php` → `DELETE /crm/empleados/{id}` / `PATCH /crm/empleados/{id}/reactivar`
- `Modules/CRM/resources/views/index.blade.php` → botón en panel lateral, llama DELETE o PATCH según `activo`

---

### CU-05 — Cambio o asignación de IP desde CRM

**Descripción:** Un administrador actualiza la IP de un equipo de cómputo desde el modal de edición del empleado en `/crm`, sin pasar por el flujo de resguardo.  
**Punto de entrada:** Panel lateral del empleado en `/crm` → botón "Editar" → sección del equipo → botón "Agregar IP" o "Cambiar IP" → mini-modal de selección → "Asignar" → guardar formulario.

**Por qué existe este caso:** El campo de IP en el formulario de edición era antes un `<input type="text">` libre que permitía escribir cualquier IP, incluyendo una ya ocupada, sin pasar por `IpAssigner`. Ahora el campo es de solo lectura y la selección se hace desde un listado de IPs libres del área del equipo.

**Flujo del mini-modal:**
1. El botón llama a `abrirModalLiberar(idx, area, ipActual)` con el índice del equipo, su área y su IP actual
2. El modal hace `GET /crm/ips-libres?area={area}` → devuelve IPs con `estatus='Libre'` en `inventario_ips_completo` filtradas por `area_excel` o `departamento_pestana`
3. Si no hay IPs libres para esa área, aparece un enlace "Ver todas las IPs libres" que repite la búsqueda sin filtro
4. Al seleccionar una IP y presionar "Asignar", se actualiza el `<input type="hidden">` del formulario
5. Al guardar el formulario, el backend valida con `IpAssigner::assignedElsewhere()` antes de actualizar

**Eventos registrados en `movimientos_equipos`:**

| Situación | Evento | Origen | Destino | Estado equipo | Notas |
|---|---|---|---|---|---|
| Primera IP asignada al equipo | `Asignación IP` | `Sin equipo` | `cpu_serie` | `Ocupada` | `IP: 10.x.x.x` |
| IP cambiada (equipo ya tenía IP) | `Cambio IP` | `cpu_serie` | `cpu_serie` | `Ocupada` | `IP: vieja → nueva` |

En `Cambio IP`, `origen` y `destino` son la misma serie porque el mismo equipo cambia de IP. La diferencia entre las IPs queda en `notas`.

**Equipo nuevo agregado desde CRM:** Si en el modal de edición se agrega un equipo que no existía previamente, y ese equipo tiene IP, también se registra `Asignación IP` con `origen='Sin equipo'`. Este equipo queda marcado como **personal** (ver CU-06).

**Archivos clave:**
- `Modules/CRM/app/Http/Controllers/CRMController.php@update` — valida IP con IpAssigner y registra evento Kardex
- `Modules/CRM/routes/web.php` → `GET /crm/ips-libres` — devuelve IPs libres por área
- `Modules/CRM/resources/views/index.blade.php` → `abrirModalLiberar()`, `asignarIpSeleccionada()`, `cerrarModalIp()`

---

### CU-06 — Registro de activo personal desde CRM

**Descripción:** Un administrador registra un equipo, teléfono o impresora directamente desde el modal de alta o edición de un empleado en `/crm`, sin pasar por el flujo de resguardo PDF de Kardex. El activo queda registrado como **propiedad personal del empleado**: el IMJUVE no se hace responsable de daños, pérdida ni mantenimiento.  
**Punto de entrada:** Modal de nuevo/editar empleado en `/crm` → sección "Activos asignados" → botón "Agregar activo" → opción "Registrar nuevo".

**Por qué existe este caso:** El flujo de resguardo (CU-01) requiere subir un PDF. Para empleados que llegan con equipo propio o en situaciones donde aún no se tiene el documento, se permite un registro rápido con las advertencias correspondientes.

**Flujo:**
1. El botón "Agregar activo" muestra un selector de modo: **Buscar existente** o **Registrar nuevo**
2. Al elegir "Registrar nuevo" se muestra un banner de advertencia y el selector de tipo
3. Tipos disponibles para registro nuevo: `Laptop`, `PC Avanzada`, `PC Especializada`, `Teléfono`
4. Las impresoras **no se pueden crear** desde este flujo; solo se pueden vincular si ya existen (ver CU-07)
5. Al guardar, el equipo se inserta en `inventario_equipos` con `user_id = id_empleado` sin evento `Entrada`

**Cómo se identifica como personal:** La ausencia del evento `Entrada` en `movimientos_equipos` es el único criterio. No hay columna adicional.

**Eventos registrados en `movimientos_equipos`:**

| Condición | Evento | Notas |
|---|---|---|
| Equipo creado, sin IP | Ninguno | Sin historial inicial |
| Equipo creado, con IP | `Asignación IP` | Igual que CU-05 |

No se registran eventos `Entrada` ni `Asignación` porque no hay resguardo que los respalde.

**Restricciones que aplican por ser personal (ver vocabulario):**
- Solo puede cambiar a estado `Baja` desde el panel de Kardex
- `Almacén` y `Mantenimiento` están bloqueados en backend (HTTP 422) y ocultos en el dropdown del panel
- No puede reasignarse a otro responsable (ver CU-07)
- Sí puede participar en todos los eventos de IP: `Asignación IP`, `Cambio IP`, `Liberación IP`

**Cómo deja de ser personal:** Si alguien sube un resguardo oficial para este equipo desde Kardex (CU-01), se genera un evento `Entrada` y automáticamente pasa a ser institucional — sin necesidad de cambiar ningún campo.

**Archivos clave:**
- `Modules/CRM/resources/views/index.blade.php` → `agregarEquipo()`, `modoEquipoNuevo()`, `camposEquipo()` (caso Telefono)
- `Modules/CRM/app/Http/Controllers/CRMController.php@store` y `@update` — INSERT sin `Entrada`
- `Modules/Kardex/app/Http/Controllers/KardexController.php@cambiarEstadoEquipo` — bloqueo de Almacén/Mantenimiento
- `Modules/Kardex/routes/web.php` → `GET /kardex/equipo/{id}` — incluye `es_personal` como subquery

---

### CU-07 — Búsqueda y asignación de activo existente desde CRM

**Descripción:** Un administrador busca un equipo ya registrado en el inventario y lo asigna a otro empleado desde el modal de edición del CRM, sin tener que crear un duplicado. Aplica a equipos institucionales (`inventario_equipos`) e impresoras (`impresoras`).  
**Punto de entrada:** Modal de editar empleado en `/crm` → sección "Activos asignados" → "Agregar activo" → opción "Buscar existente" → campo de búsqueda → seleccionar resultado → guardar formulario.

**Flujo de búsqueda:**
1. El campo de búsqueda hace `GET /crm/equipos/buscar?q=...` (debounce 350 ms, mínimo 2 caracteres)
2. La ruta busca en `inventario_equipos` (serie, nombre, marca, modelo) y en `impresoras` (serie, marca, modelo)
3. Los resultados muestran tipo, serie, marca/modelo y responsable actual
4. Equipos **personales** (sin `Entrada`) que ya tienen responsable aparecen atenuados con ícono de candado — no se pueden seleccionar
5. Al hacer clic en un equipo institucional o sin responsable, el bloque se transforma mostrando todos sus campos editables pre-llenados

**Distinción por tabla de origen:**

| Tipo de activo | Tabla | Qué hace el backend al guardar |
|---|---|---|
| Equipo (Laptop / PC / Teléfono) | `inventario_equipos` | Actualiza `user_id` al empleado actual; registra `Reasignación` si había otro responsable |
| Impresora | `impresoras` | Solo actualiza `user_id`; no registra eventos Kardex |

**Switch de responsable — equipo institucional:**

Cuando el equipo seleccionado pertenecía a otra persona (`user_id` diferente al empleado actual), `CRMController@update` detecta el cambio y registra automáticamente:

| Campo | Valor |
|---|---|
| `tipo_evento` | `Reasignación` |
| `origen` | Nombre del responsable anterior |
| `destino` | Nombre del nuevo responsable |
| `user_from_id` | `user_id` anterior |
| `user_to_id` | `id` del empleado actual |
| `estado_equipo` | `Asignado` |
| `notas` | `"Cambio de responsable desde panel CRM."` |

**Distinción con CU-03:** CU-03 cambia `usuario_actual_id` (quién usa físicamente el equipo) desde el panel de Kardex. CU-07 cambia `user_id` (responsable formal / resguardo) desde CRM. Ambos generan un evento `Reasignación` pero se distinguen por el campo `notas`.

**Reglas de bloqueo:**
- Equipos personales con responsable → bloqueados en frontend (alert) y backend (HTTP 422 en `cambiarEstadoEquipo`)
- Equipos personales sin responsable (no asignados) → sí se pueden vincular
- Impresoras → solo se actualiza `user_id`; sin restricción de personal (las impresoras no tienen historial Kardex)

**Archivos clave:**
- `Modules/CRM/routes/web.php` → `GET /crm/equipos/buscar` — búsqueda unificada con `es_personal` computado
- `Modules/CRM/resources/views/index.blade.php` → `modoEquipoBuscar()`, `buscarEquipos()`, `seleccionarEquipoExistente()`
- `Modules/CRM/app/Http/Controllers/CRMController.php@update` — switch de `user_id` + evento `Reasignación`

---

### CU-08 — Gestión de IPs desde el panel Network

**Descripción:** Un administrador opera directamente sobre la tabla maestra de IPs (`inventario_ips_completo`) desde `/network`: libera una IP poniéndola en baja/mantenimiento, o la transfiere a otro equipo (switcheo). Ambas acciones quedan registradas en `movimientos_equipos`.  
**Punto de entrada:** `/network` → clic en una fila de IP ocupada → panel lateral → botón "Liberar IP".

**Precondición:** El botón "Liberar IP" solo está habilitado cuando `inventario_ips_completo.estatus = 'Ocupada'`. Si la IP figura como `'Libre'` aunque `inventario_equipos.ip_id` apunte a ella (desincronía por importación legada), `IpAssigner::resolveId()` la corrige a `'Ocupada'` la próxima vez que se asigne desde CRM o Kardex.

#### Variante A — Dar de baja / mantenimiento al equipo actual

El equipo que ocupa la IP pasa al estado elegido y la IP queda libre.

**Eventos registrados en `movimientos_equipos`:**

| # | Evento | `tipo_activo` | Origen | Destino | Estado equipo | Notas |
|---|---|---|---|---|---|---|
| 1 | `Liberación IP` | equipo o impresora | `cpu_serie` del equipo | `Sin equipo` | `Libre` | `IP: x.x.x.x` |
| 2 | `Baja` o `Mantenimiento` | equipo | Área del equipo | `Proveedor` (solo Baja) | `Baja` / `Mantenimiento` | `Operación ejecutada desde panel Network.` |

El evento 2 no se registra para impresoras (no tienen columna `estado`).

**Qué actualiza en la BD:**
- `inventario_equipos.estado` → `'baja'` o `'mantenimiento'`
- `inventario_equipos.ip_id` y `.ipv4` → `null` (vía `IpAssigner::liberarEquipo()`)
- `inventario_ips_completo.estatus` → `'Libre'`

#### Variante B — Switcheo (mover IP a otro equipo)

La IP se mueve del equipo actual (origen) a otro equipo seleccionado (destino). Si el destino ya tenía una IP distinta, esa IP queda huérfana como `'Libre'` en el registro maestro.

**Eventos registrados en `movimientos_equipos`:**

| # | Evento | `tipo_activo` | Origen | Destino | Estado equipo | Notas |
|---|---|---|---|---|---|---|
| 1 | `Liberación IP` | equipo u impresora | `cpu_serie` del equipo que pierde la IP | `cpu_serie` del equipo que la recibe | `Libre` | `IP: x.x.x.x — liberada por switcheo` |
| 2 | `Asignación IP` o `Cambio IP` | equipo | `cpu_serie` del equipo que pierde la IP | `cpu_serie` del equipo que la recibe | `Ocupada` | `IP: x.x.x.x` |

El evento 2 es `Asignación IP` si el equipo destino no tenía IP previa, o `Cambio IP` si ya tenía una.

**Convención de origen/destino en eventos de IP (aplica a todos los CU):**  
`origen` y `destino` siempre son el `cpu_serie` del equipo involucrado, **nunca el nombre de la persona**. La dirección IP va en `notas`. Esto permite rastrear el historial de una IP buscando por serie en `movimientos_equipos`.

**Nota sobre `IpAssigner::resolveId()`:**  
Cuando CRM o Kardex asignan una IP, `resolveId()` busca primero la fila en `inventario_ips_completo`. Si la encuentra con `estatus='Libre'` (situación común en el dump importado), la actualiza a `'Ocupada'` antes de devolver el ID. Esto garantiza que el panel Network siempre vea el estado correcto sin necesidad de intervención manual.

**Archivos clave:**
- `Modules/Network/app/Http/Controllers/NetworkController.php@liberarPorEstado` — variante A
- `Modules/Network/app/Http/Controllers/NetworkController.php@liberarPorSwitch` — variante B
- `Modules/Network/routes/web.php` → `POST /network/ip/{id}/liberar-estado` / `POST /network/ip/{id}/liberar-switch`
- `app/Support/IpAssigner.php@resolveId` — garantiza `estatus='Ocupada'` en fila maestra al asignar
- `Modules/Network/resources/views/index.blade.php` → `abrirModalLiberar()`, `confirmarLiberar()`

---

### CU-09 — Actualización masiva de equipos por importación Excel

**Descripción:** Un administrador descarga un archivo Excel pre-armado con los datos actuales de los equipos sin resguardo, edita los campos necesarios (periféricos, área, responsable, IP, observaciones) y lo reimporta para actualizar múltiples registros de una sola vez. El proceso siempre pasa por una fase de previsualización antes de aplicar cualquier cambio.  
**Punto de entrada:** Módulo Kardex (`/kardex`) → tab "Inventario de Equipos" → botón "Importar" → modal de importación.

**Actores:** Administrador de TI.

**Precondición:** El administrador debe tener rol `admin`. Los equipos que se desean actualizar no deben tener resguardo activo (`pdf_resguardo IS NULL` y `user_id IS NULL`).

---

#### Flujo principal

**Paso 1 — Descarga del formato**

El administrador hace clic en **"Descargar formato (.xlsx)"** dentro del modal. El sistema genera un Excel con tres hojas:

| Hoja | Visibilidad | Contenido |
|---|---|---|
| `INSTRUCCIONES` | Visible | Reglas de uso, advertencia de equipos excluidos, guía de columnas |
| `EQUIPOS` | Visible (activa) | Una fila por equipo sin resguardo, con los datos actuales precargados |
| `CATALOGOS` | Oculta | Fuente de verdad de los desplegables: áreas, tipos, responsables, IPs |

El formato **respeta los filtros activos** en la UI al momento de hacer clic. Si hay filtro `tipo=Laptop`, el Excel solo contiene Laptops. Los filtros se pasan como query params al endpoint de descarga.

Los equipos con resguardo activo son excluidos automáticamente. Si hubo exclusiones, la hoja `INSTRUCCIONES` lo indica: `"ℹ️ Se excluyeron N equipo(s) con resguardo activo..."`.

**Columnas del Excel y su comportamiento:**

| Color celda | Significado |
|---|---|
| Gris | Bloqueada — protegida por contraseña vacía; el importer la ignora aunque se modifique |
| Naranja suave | `No. Serie` — identificador único, nunca se modifica |
| Blanca | Editable — puede modificarse; los cambios se importarán |

| Columna | Campo DB | Editable | Desplegable |
|---|---|---|---|
| A — No. Inventario | `num_inventario` | Sí | No |
| B — Nombre Equipo | `nombre_equipo` | Sí | No |
| C — Tipo | `tipo` | No | — |
| D — No. Serie | `cpu_serie` | No (identificador) | — |
| E — Área | `area` | Sí | Catálogo `cat_rangos_ips` |
| F — Responsable | `nombre_usuario` / `user_id` | Sí | Usuarios activos del sistema |
| G — Estado | derivado | No | — |
| H-I — Marca / Modelo CPU | `cpu_marca`, `cpu_modelo` | Sí | No |
| J-Q — Periféricos | series de teclado, mouse, monitor, no-break, cargador | Sí | No |
| R-U — Docking | marca, modelo, serie + candado | Sí | No |
| V — Candado | `candado` | Sí | No |
| W — MAC | `mac` | Sí | No (validación de formato) |
| X — IP | `ipv4` / `ip_id` | Sí | IPs no bloqueadas por resguardo |
| Y — Observaciones | `observaciones` | Sí | No |

**Regla de celdas vacías:** Una celda editable vacía **conserva el valor actual** en BD — no lo borra.

**Paso 2 — Edición y carga**

El administrador edita el Excel en Excel, LibreOffice o Google Sheets. Los desplegables de `Área`, `Responsable` e `IP` limitan los valores válidos directamente en la celda. Al terminar, regresa al modal y carga el archivo usando el botón **"Seleccionar archivo Excel"** → **"Validar archivo"**.

**Paso 3 — Previsualización (dry-run)**

El sistema procesa el archivo sin tocar la base de datos. El modal muestra tres contadores y el detalle por fila:

| Indicador | Significado |
|---|---|
| ✅ Actualizables | Filas con cambios válidos que se aplicarán |
| ➖ Sin cambios | Filas en que todos los valores coinciden con los de la BD |
| ❌ Errores | Filas rechazadas — el motivo se muestra por fila |

El botón **"Aplicar (N) cambios"** solo se habilita si hay al menos 1 fila actualizable.

**Paso 4 — Aplicación**

El administrador hace clic en **"Aplicar"**. El sistema aplica solo las filas marcadas como `ok` y registra un evento `Actualización Masiva` en `movimientos_equipos` por cada equipo modificado. La `notas` del evento contiene el diff campo por campo (`"Monitor S/N: (vacío) → MON-123 | Área: Dir. Finanzas → DIRECCIÓN DE FINANZAS"`).

---

#### Reglas de validación (por orden de prioridad)

| Regla | Resultado si falla |
|---|---|
| `No. Serie` debe existir en `inventario_equipos` | ❌ Error: "Serie no encontrada en el sistema." |
| Equipo tiene `pdf_resguardo IS NOT NULL` o `user_id IS NOT NULL` | ❌ Error: "Este equipo tiene resguardo activo. Para modificar sus datos, introduce un nuevo PDF de resguardo desde el módulo Kardex." |
| `Área` nueva no existe en `cat_rangos_ips` (comparación `mb_strtolower`, en PHP para evitar limitaciones de `LOWER()` en SQLite con acentos) | ❌ Error por fila |
| `MAC` no cumple el patrón `XX:XX:XX:XX:XX:XX` | ❌ Error por fila |
| `IP` no está registrada en `inventario_ips_completo` | ❌ Error por fila |
| `IP` está asignada a un equipo con resguardo activo | ❌ Error por fila |
| `IP` está asignada a otro equipo sin resguardo (distinto al que se está importando) | ❌ Error por fila |
| El valor de un campo es igual al valor actual en BD | No genera cambio — se omite silenciosamente |
| `Responsable` no coincide con ningún usuario activo (comparación `mb_strtolower` en PHP) | Se guarda como texto en `nombre_usuario` sin asignar `user_id`; no bloquea la fila |

**Por qué los errores de `Área` antes causaban falsos negativos:** SQLite solo baja letras ASCII con `LOWER()`. Caracteres como `É`, `Ó`, `Ú` no se convierten, así que `LOWER('DIRECCIÓN')` devuelve `'DIRECCIÓN'` en SQLite. La validación ahora se hace en PHP con `mb_strtolower()` en ambos lados.

---

#### Manejo especial de Responsable e IP

**Responsable:**
- El importer intenta coincidencia exacta (`mb_strtolower`) entre el valor del Excel y `TRIM(name || ' ' || apellido_paterno)` de todos los usuarios activos, cargados en memoria.
- Si hay coincidencia → actualiza `user_id` + `nombre_usuario`.
- Si no hay coincidencia → actualiza solo `nombre_usuario` (campo de texto legacy). No se bloquea la fila. El diff indica `"Responsable (texto): ..."`.
- Razón: los equipos sin resguardo suelen tener `nombre_usuario` en formato legacy APELLIDO NOMBRE (todo mayúsculas), que es diferente al formato `name apellido_paterno` del sistema. Bloquear por esto haría la importación inútil.

**IP:**
- Si cambia → libera la IP anterior (actualiza `inventario_ips_completo.estatus = 'Libre'`) antes de llamar a `IpAssigner::resolveId()` con la nueva.
- Si la IP nueva no estaba registrada en `inventario_ips_completo` → error (no se crean IPs nuevas por importación).
- El dropdown del Excel solo muestra IPs que no estén asignadas a equipos con resguardo. IPs asignadas a equipos sin resguardo sí aparecen (pueden reasignarse).

---

#### Evento Kardex generado

```
tipo_evento:   'Actualización Masiva'
tipo_activo:   'equipo'
activo_id:     id del equipo
origen:        'Importación Excel'
destino:       'Importación Excel'
estado_equipo: null
notas:         "Monitor S/N: (vacío) → MON-123 | Área: Dir. Finanzas → DIRECCIÓN DE FINANZAS"
registrado_by: Auth::id()  (admin que ejecutó el import)
```

---

#### Sesión temporal del archivo

El archivo se guarda en `storage/app/temp_imports/` durante la fase dry-run. Al aplicar, el sistema usa ese archivo (identificado por la sesión PHP del admin, TTL 30 min). Si la sesión expira, la respuesta del backend es `422 "Sesión de importación expirada"` y el modal pide subir el archivo de nuevo.

---

#### Restricciones de diseño (no negociables)

1. **Solo actualización** — no se crean ni eliminan filas en `inventario_equipos`.
2. **Equipos con resguardo son intocables** — la única forma de modificarlos es subir un nuevo PDF desde Kardex (CU-01).
3. **Dos fases obligatorias** — siempre dry-run antes de aplicar. No hay modo "aplicar directo".
4. **La verdad absoluta son los resguardos PDF** — cualquier dato del equipo que tenga resguardo prevalece sobre el Excel.

---

#### Archivos clave

- `app/Imports/EquiposImporter.php` — lógica de dry-run y aplicación; manejo especial de Responsable e IP
- `app/Exports/EquiposExporter.php@downloadFormato` — genera el XLSX con 3 hojas, protección, desplegables y filtros
- `Modules/Kardex/app/Http/Controllers/KardexController.php@validarImport` — recibe el archivo, ejecuta dry-run, guarda temp en sesión
- `Modules/Kardex/app/Http/Controllers/KardexController.php@aplicarImport` — aplica los cambios usando el temp de sesión
- `Modules/Kardex/routes/web.php` → `POST /kardex/importar/validar` / `POST /kardex/importar/aplicar`
- `resources/views/components/tabla-encabezado.blade.php` — modal de importación (5 fases: inicio / validando / preview / aplicando / listo); `descargarFormato()` pasa filtros activos

---

## Herramientas de desarrollo

### `php artisan kardex:seed-entradas`

Comando para ambientes de prueba. Crea eventos `Entrada` (y opcionalmente `Asignación`) sintéticos en `movimientos_equipos` para simular equipos institucionales sin necesidad de subir PDFs reales.

**Por qué existe:** El criterio `es_personal` se deriva de la ausencia de evento `Entrada`. El sistema legado importado via `app:boot` no genera estos eventos, por lo que todo el equipo importado aparecería como personal. Este comando "formaliza" retroactivamente los registros para pruebas.

**Uso:**

```bash
# Marcar el 60% del equipo sin Entrada como institucional
php artisan kardex:seed-entradas --porcentaje=60

# Marcar el resto (idempotente: solo procesa los que aún no tienen Entrada)
php artisan kardex:seed-entradas --porcentaje=100

# Eliminar todos los eventos generados por este comando
php artisan kardex:seed-entradas --rollback
```

**Comportamiento:**
- Solo procesa equipos que **no tienen** ningún evento `Entrada` — es seguro correr múltiples veces
- Crea un evento `Entrada` con `origen='Proveedor'`, `destino='Subdirección de Sistemas'`, `estado_equipo='Almacén'`
- Si el equipo tiene `user_id` y ese usuario existe en `users`, crea también un evento `Asignación` con `estado_equipo='Asignado'` fechado un día después
- Si el `user_id` apunta a un usuario que no existe (registro huérfano del dump), solo crea `Entrada`
- Las fechas son retroactivas aleatorias (30–730 días atrás) para que el historial se vea natural
- Todos los eventos seed incluyen `[seed-pruebas]` al inicio de `notas` — identificables para rollback

**No usar en producción:** El rollback elimina todos los eventos con `notas LIKE '[seed-pruebas]%'`; en producción podría haber colisión si alguien escribe esa cadena manualmente en notas reales.

---

## CU-10 — Gestión de Licencias Microsoft 365

### Contexto

IMJUVE paga licencias de Microsoft 365 para sus empleados. Estas licencias son activos de TI igual que los equipos o impresoras, y deben administrarse en el inventario. A diferencia de un equipo (1 equipo → 1 responsable), una licencia puede tener múltiples titulares y puede estar instalada en varios equipos según el tipo.

La gestión vive en el módulo Kardex, tab **Licencias**.

---

### Modelo de datos

#### Tabla `licencias`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | PK | |
| `correo` | string(150), unique | Dirección de correo de la cuenta M365 (ej. `jgarcia@imjuve.gob.mx`) |
| `tipo` | string(50) | Tipo de licencia: `E1`, `E3`, `Exchange Plan 1`, `Microsoft 365 Business Standard`, etc. |
| `max_usuarios` | integer | Cuántos empleados pueden ser titulares de esta cuenta al mismo tiempo |
| `max_equipos` | integer | Cuántos equipos del inventario pueden tener la licencia instalada (0 = solo acceso web) |
| `area` | text, nullable | Área o departamento dueño de la licencia |
| `estado` | string(30) | `Activa`, `Inactiva`, `Suspendida` |
| `caducidad` | date, nullable | Fecha de vencimiento del contrato/licencia |
| `observaciones` | text, nullable | Notas adicionales |

#### Tabla `licencia_users` (pivot)

Relaciona una licencia con los empleados que la usan como titulares.

| Columna | Descripción |
|---|---|
| `licencia_id` | FK → `licencias` |
| `user_id` | FK → `users` |
| Unique: `(licencia_id, user_id)` | Un empleado no puede estar duplicado en la misma licencia |

El sistema valida que `COUNT(*) WHERE licencia_id = X ≤ licencias.max_usuarios` antes de agregar.

#### Tabla `licencia_equipos` (pivot)

Relaciona una licencia con los equipos del inventario donde está instalada.

| Columna | Descripción |
|---|---|
| `licencia_id` | FK → `licencias` |
| `equipo_id` | FK → `inventario_equipos` |
| Unique: `(licencia_id, equipo_id)` | Un equipo no puede estar duplicado en la misma licencia |

El sistema valida que `COUNT(*) WHERE licencia_id = X ≤ licencias.max_equipos` antes de vincular. Si `max_equipos = 0`, el botón "Vincular equipo" está deshabilitado.

---

### Cupos por defecto según tipo

El formulario de nueva licencia auto-rellena los cupos cuando el admin selecciona el tipo. Pueden ser editados manualmente si el contrato es diferente.

| Tipo | `max_usuarios` por defecto | `max_equipos` por defecto | Razón |
|---|---|---|---|
| E1 | 1 | 0 | Solo acceso web, no permite instalación de aplicaciones de escritorio |
| E3 | 1 | 5 | Permite Office en hasta 5 PC/Mac |
| Exchange Plan 1 | 1 | 0 | Solo servicio de correo, sin apps de Office |
| Microsoft 365 Business Basic | 1 | 0 | Solo web, sin Office de escritorio |
| Microsoft 365 Business Standard | 1 | 5 | Incluye Office instalable en 5 equipos |
| Otro | 1 | 0 | El admin define manualmente |

> **Nota:** Microsoft define los cupos por usuario (cada usuario puede instalar en N dispositivos). En este sistema los cupos están definidos **por cuenta/correo**, no por usuario, porque IMJUVE puede tener cuentas compartidas entre empleados.

---

### Flujo principal: crear una licencia

1. Admin navega a Kardex → tab **Licencias**
2. Hace click en **Nueva licencia**
3. En el modal rellena:
   - **Correo M365** (único en el sistema)
   - **Tipo** → los cupos se auto-rellenan
   - **Estado** (Activa por defecto)
   - **Caducidad** (opcional, pero recomendado para alertas)
   - Ajusta cupos si el contrato difiere del estándar
4. Guarda → la licencia aparece en la tabla
5. Al hacer click en la fila se abre el panel lateral para gestionar titulares y equipos

---

### Flujo: asignar un titular

Un **titular** es el empleado que usa esa cuenta de correo.

1. En el panel lateral de la licencia, sección **Titulares** → click en **Agregar**
2. Se muestra un `prompt` para buscar por nombre o email del empleado
3. Si hay coincidencias, el admin selecciona el número del empleado deseado
4. El sistema verifica `n_titulares < max_usuarios` antes de insertar en `licencia_users`
5. Si el cupo está lleno → error `"Cupo de titulares alcanzado (N)."`
6. El badge de la tabla se actualiza: `N/max_usuarios` con color verde/amarillo/rojo

Para quitar un titular: botón `person_remove` junto al nombre en el panel → confirmación → delete en `licencia_users`.

---

### Flujo: vincular un equipo

Aplica solo cuando `max_equipos > 0` (el tipo de licencia permite instalación en PC).

1. En el panel lateral, sección **Equipos instalados** → click en **Vincular**
2. Se pide el **No. de Serie** del equipo
3. El sistema resuelve la serie a `id` vía `/kardex/resguardo/verificar-serie`
4. Verifica `n_equipos < max_equipos` antes de insertar en `licencia_equipos`
5. Si el cupo está lleno → error `"Cupo de equipos alcanzado (N)."`
6. Si `max_equipos = 0` → el botón muestra "Solo web" y está deshabilitado

Para desvincular: botón `link_off` junto al equipo → confirmación → delete en `licencia_equipos`.

---

### Caducidad — semáforo de color

| Color | Condición |
|---|---|
| 🟢 Verde | Fecha futura con más de 30 días restantes |
| 🟡 Amarillo | Vence en 30 días o menos |
| 🔴 Rojo | Ya venció o sin fecha registrada |

El color aparece tanto en la tabla principal como en el panel lateral.

---

### Integración con el directorio de empleados

El export de CRM (directorio de empleados en Excel) incluye una columna **Licencia** que muestra el tipo y correo de cada licencia asociada al empleado:

- Sin licencias → `—`
- Una licencia → `E3 (jgarcia@imjuve.gob.mx)`
- Múltiples → `E3 (cuenta1@…) | Exchange Plan 1 (cuenta2@…)`

Esto es útil para auditorías y para saber qué tipo de acceso tiene cada empleado antes de darle soporte.

---

### Reglas de negocio

1. El `correo` de una licencia es único en todo el sistema — no puede haber dos licencias con el mismo correo.
2. Un empleado puede ser titular de múltiples licencias (ej. su correo personal E3 + una cuenta de departamento Exchange).
3. Un equipo puede estar vinculado a múltiples licencias (ej. tiene E3 y también un antivirus con licencia separada si se agrega ese tipo).
4. Los cupos son independientes: el cupo de titulares y el de equipos no se suman — se validan por separado.
5. Las licencias no están vinculadas al ciclo de vida de los equipos (Kardex de movimientos). Si un equipo vinculado se da de baja, la vinculación en `licencia_equipos` persiste hasta que el admin la retire manualmente.
6. Las licencias **no generan eventos en `movimientos_equipos`** — no son activos físicos con transferencias de custodia.

---

### Archivos clave

| Archivo | Rol |
|---|---|
| `Modules/Kardex/database/migrations/2026_07_31_000001_create_licencias_table.php` | Tabla principal |
| `Modules/Kardex/database/migrations/2026_07_31_000002_create_licencia_users_table.php` | Pivot titulares |
| `Modules/Kardex/database/migrations/2026_07_31_000003_create_licencia_equipos_table.php` | Pivot equipos |
| `Modules/Kardex/app/Http/Controllers/KardexController.php` | Métodos: `licenciasJson`, `licenciaDetalle`, `storeLicencia`, `updateLicencia`, `asignarLicenciaUsuario`, `desasignarLicenciaUsuario`, `asignarLicenciaEquipo`, `desasignarLicenciaEquipo` |
| `Modules/Kardex/routes/web.php` | 8 rutas bajo `/kardex/licencias` |
| `Modules/Kardex/resources/views/index.blade.php` | Tab Licencias, panel lateral, modal crear/editar, JS de carga y gestión |
| `app/Exports/EmpleadosExporter.php` | Columna Licencia en el export del directorio CRM |

---

## CU-11 — Kanban: ocultar tickets cerrados antiguos

### Contexto

La vista Kanban del módulo Tickets muestra tres columnas: ABIERTO, ATENDIENDO y CERRADO. Con el tiempo la columna CERRADO acumula tickets que ya no requieren atención, haciendo difícil distinguir los recientemente cerrados (que aún pueden necesitar seguimiento) de los archivados.

**Regla:** Tickets en estado CERRADO (`estado = 2`) cerrados hace más de 24 horas **no se muestran en el Kanban**. Siguen visibles en la vista Lista.

---

### Implementación

En `Modules/Tickets/resources/views/index.blade.php`, al renderizar la columna CERRADO del Kanban, se filtra `$colTickets` en PHP:

```php
$umbral        = \Carbon\Carbon::now()->subDay();
$kanbanTickets = $colTickets->filter(fn($t) =>
    $t->cerrado_at && \Carbon\Carbon::parse($t->cerrado_at)->gt($umbral)
);
$nOcultos = $colTickets->count() - $kanbanTickets->count();
```

- `$colTickets` proviene de `$porEstado[2]` — no se modifica, la vista Lista sigue usándolo íntegro.
- Solo se muestran tickets con `cerrado_at` registrado **y** dentro de las últimas 24 horas.
- Tickets con `cerrado_at = NULL` también se ocultan (datos migrados sin fecha de cierre).

### Campo `cerrado_at`

Se establece automáticamente en `TicketsController` cuando el estado cambia a `2`:

```php
if ($estado === 2) $extra = ['cerrado_at' => now(), 'cerrado_by' => Auth::user()->email];
DB::table('tickets')->where('id', $id)->update(array_merge(['estado' => $estado], $extra));
```

### Badge y contador

- El badge numérico de la columna CERRADO muestra `$kanbanTickets->count()` (solo los visibles).
- Si `$nOcultos > 0`, aparece junto al badge un texto `"+N oculto(s)"` en cursiva.
- La vista Lista no cambia: muestra todos los tickets cerrados sin filtro de fecha.

**Archivo:** `app/Console/Commands/SeedEntradasCommand.php`
