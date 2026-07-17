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
- `estado` se actualiza a `'baja'` en `inventario_equipos` (o `impresoras`)
- El `user_id` y `usuario_actual_id` **no se tocan** — el responsable formal sigue registrado (la baja no desvincula)
- La IP asignada se **libera automáticamente** (`IpAssigner::liberarEquipo`) y queda disponible en el pool
- El badge en la tabla cambia a "Baja" (gris)

**Eventos registrados en `movimientos_equipos`:**

| # | Evento | Origen | Destino | Estado equipo |
|---|---|---|---|---|
| 1 | `Baja` | Nombre del responsable | — (null) | `Baja` |
| 2 | `Liberación IP` | `cpu_serie` del equipo | `Sin equipo` | `Libre` |

El evento 2 solo se registra si el equipo tenía IP asignada. Se registra antes de llamar a `IpAssigner::liberarEquipo()`, que limpia `ip_id` e `ipv4` en `inventario_equipos` y devuelve la fila a `estatus='Libre'` en `inventario_ips_completo`.

El mismo par de eventos se genera para **Mantenimiento** (reemplazando `Baja` por `Mantenimiento` y `estado_equipo='Mantenimiento'`).

**Diferencia con Almacén:** Almacén desvincula al responsable (`user_id = null`) pero **no libera la IP** (bug conocido — la IP queda ocupada aunque el equipo regrese a almacén). Baja y Mantenimiento sí liberan la IP y registran el evento `Liberación IP`. El equipo de baja queda "muerto" pero trazable.

**Diferencia con Mantenimiento:** Mantenimiento libera la IP y registra `estado_equipo = 'Mantenimiento'`; el equipo puede volver a Asignado. Baja es terminal — no hay evento de reingreso implementado aún.

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
| `inventario_equipos` (equipo como responsable o usuario) | `user_id=null`, `usuario_actual_id=null`, `estado=null` → pasan a estado Almacén |
| IPs de esos equipos | **No se liberan** — el equipo queda en Almacén con su IP conservada |
| `impresoras` donde era responsable | **No se tocan** ⚠️ — la impresora sigue vinculada al usuario dado de baja |
| Tickets abiertos | **No se cierran ni reasignan** — quedan activos en el sistema |
| `movimientos_equipos` | **No se registra ningún movimiento** ⚠️ — el paso a Almacén no queda en el historial |

**Teléfonos:** Al estar en `inventario_equipos` (tipo=`'Telefono'`), los teléfonos del empleado también quedan desvinculados junto con los equipos de cómputo (misma query).

**Cuenta queda bloqueada:** La contraseña se reemplaza por un hash aleatorio y el `recovery_code_hash` se anula, por lo que ninguno de los dos mecanismos de acceso (contraseña o código de recuperación) sigue funcionando aunque el registro exista.

**Reactivar empleado (`PATCH /crm/empleados/{id}/reactivar`):**
- Restaura `activo=true` y anula `fecha_baja`
- **No re-vincula** ningún equipo que se haya desvinculado durante la baja
- El empleado queda activo en el directorio pero sin activos asignados

**Problemas identificados:**

| # | Estado | Descripción |
|---|---|---|
| 1 | ⚠️ Pendiente | Los equipos que regresan a Almacén no generan evento en `movimientos_equipos` — el historial Kardex queda sin registro del motivo de la desvinculación |
| 2 | ⚠️ Pendiente | Las impresoras vinculadas al empleado **no se desvinculan** — quedan con `user_id` apuntando a un usuario inactivo |
| 3 | ⚠️ Pendiente | `CRMController@store` todavía inserta en la tabla `telefonos` aunque los teléfonos ya se migraron a `inventario_equipos` |
| 4 | ✅ Resuelto | Campo `ipv4_actual` eliminado del INSERT de `store()` — ya no causa error al dar de alta empleados con equipos |

**Archivos clave:**
- `Modules/CRM/app/Http/Controllers/CRMController.php@destroy` — baja lógica + desvinculación de equipos
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

**Archivo:** `app/Console/Commands/SeedEntradasCommand.php`
