# Decisiones pendientes con el cliente — Modelo de identidad y licencias

Este documento junta las preguntas que quedaron abiertas tras el
[paso 1 de la fusión empleados→users](migracion-empleados-a-users.md) y que
necesitan una respuesta definitiva del cliente antes de poder modelarlas en
la base de datos. Mientras no se resuelvan, el sistema sigue con el
comportamiento mínimo descrito en cada sección.

---

## 1. Modelo de identidad de una persona

Hoy el login se resolvió como: **correo único de login + contraseña**, más un
**código de recuperación no fungible** (paso 2, aún no construido) para quien
no tenga correo/contraseña a la mano.

**Pendiente de definir con el cliente:** qué otros datos de una persona se
consideran "fijos" para identificarla y cuáles pueden cambiar con el tiempo
(ej. correo institucional reasignado, cambio de puesto, cambio de área) sin
que eso implique perder el historial de equipos/tickets/IPs asociados a esa
persona.

## 2. Cardinalidades por persona

| Relación | Regla ya acordada |
|---|---|
| Persona → Equipos de cómputo | N equipos por persona |
| Persona → Correos | N correos por persona, pero **solo uno sirve para login** (el de `users.email`). Los demás son **informativos** — igual que un equipo, no dan acceso a nada, solo registran relación/responsabilidad. |
| Persona → Teléfonos | N teléfonos por persona |
| Persona → IPs | N IPs por persona (proporcional a la cantidad de equipos) |
| Persona → Puesto | **Un solo puesto** por persona. Si el puesto es "Director", debe aparecer en un acceso rápido al llenar el formulario de resguardo (ver `Doc/migracion-empleados-a-users.md` y el punto 4 más abajo). |

## 3. Reglas de licencias de correo — PENDIENTE DE DEFINIR

Confirmado: **un correo solo puede tener una licencia a la vez**, y puede ser
de uso individual o grupal según el tipo de licencia.

Falta que el cliente defina, por cada tipo de licencia:

- Qué accesos/servicios incluye.
- Límite de equipos que se pueden ligar a ese correo.
- Lista de programas permitidos.
- Lista de programas explícitamente prohibidos.

No se puede modelar una tabla de licencias sin esta información — cualquier
esquema que se construya ahora sería una suposición. Se recomienda levantar
esto en la misma reunión donde se defina el punto 1.

## 4. Impacto en el formulario de resguardo

`Modules/Kardex/resources/views/resguardo-preview.blade.php` va a incorporar
un acceso rápido (dropdown) a personas con puesto "Director" en la columna
lateral, para relacionar equipos nuevos o existentes con esa persona sin
tener que buscarla manualmente. Ver seguimiento de implementación en el plan
de "Director quick-pick + impresoras + responsable/usuario".

## 5. Responsable vs. usuario de un equipo

Cada equipo de cómputo va a tener dos roles distintos:

- **Responsable** — quien firma/es dueño del resguardo.
- **Usuario** — quien usa el equipo día a día.

Por defecto ambos son la misma persona. Cambiar quién es el "usuario" del
equipo va a ser una función del propio usuario logueado (self-service), pero
esa función depende del sistema de login (paso 2, en construcción) — por
ahora solo se prepara la base de datos para soportarlo.

## 6. Inventario de impresoras

Falta exponer en la UI el inventario de impresoras con su IP y su persona
responsable (ya existen las columnas `impresoras.ip_address` e
`impresoras.user_id` en el esquema — ver `database/schema/sqlite-schema.sql`).
