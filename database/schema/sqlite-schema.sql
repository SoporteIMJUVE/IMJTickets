CREATE TABLE "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" varchar not null,
  "queue" varchar not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" on "failed_jobs"(
  "connection",
  "queue",
  "failed_at"
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE "departamentos"(
  "id_departamento" integer primary key autoincrement not null,
  "nombre" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "departamentos_nombre_unique" on "departamentos"("nombre");
CREATE TABLE "empleados"(
  "id_empleado" integer primary key autoincrement not null,
  "nombre" varchar not null,
  "apellido_paterno" varchar,
  "apellido_materno" varchar,
  "puesto" varchar,
  "correo" varchar,
  "id_departamento" integer,
  "activo" tinyint(1) not null default '1',
  "fecha_alta" datetime not null default CURRENT_TIMESTAMP,
  "fecha_baja" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("id_departamento") references "departamentos"("id_departamento") on delete set null
);
CREATE TABLE "insumos"(
  "id_insumo" integer primary key autoincrement not null,
  "nombre_insumo" varchar not null,
  "numero_parte" varchar,
  "stock_minimo" integer not null default '0',
  "stock_maximo" integer not null default '0',
  "stock_actual" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE "suministros"(
  "id_suministro" integer primary key autoincrement not null,
  "id_insumo" integer,
  "id_departamento" integer,
  "fecha_solicitud" date not null default CURRENT_DATE,
  "cantidad_requerida" integer,
  "cantidad_entregada" integer,
  "estatus" varchar not null default 'PENDIENTE DE ENTREGA',
  "notas" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("id_insumo") references "insumos"("id_insumo") on delete set null,
  foreign key("id_departamento") references "departamentos"("id_departamento") on delete set null
);
CREATE TABLE "cat_rangos_ips"(
  "id_rango" integer primary key autoincrement not null,
  "area_nombre" varchar not null,
  "siglas" varchar,
  "ip_inicial" varchar,
  "ip_final" varchar,
  "capacidad_total" integer,
  "ocupadas" integer not null default '0',
  "libres" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE "tickets"(
  "id" integer primary key autoincrement not null,
  "nombre" varchar not null,
  "correo" varchar not null,
  "area" varchar not null,
  "tipo" varchar not null,
  "descripcion" text not null,
  "estado" integer not null default '0',
  "comentarios" text,
  "atendido_at" datetime,
  "atendido_by" varchar,
  "cerrado_at" datetime,
  "cerrado_by" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "ip" varchar,
  "mac" varchar
);
CREATE TABLE "areas"(
  "id" integer primary key autoincrement not null,
  "nombre" varchar not null
);
CREATE UNIQUE INDEX "areas_nombre_unique" on "areas"("nombre");
CREATE TABLE "tipos"(
  "id" integer primary key autoincrement not null,
  "nombre" varchar not null
);
CREATE UNIQUE INDEX "tipos_nombre_unique" on "tipos"("nombre");
CREATE TABLE "ticket_comentarios"(
  "id" integer primary key autoincrement not null,
  "ticket_id" integer not null,
  "autor_nombre" varchar not null,
  "autor_email" varchar not null,
  "texto" text not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "ticket_comentarios_ticket_id_index" on "ticket_comentarios"(
  "ticket_id"
);
CREATE TABLE "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "role" varchar not null default 'user',
  "apellido_paterno" varchar,
  "apellido_materno" varchar,
  "puesto" varchar,
  "id_departamento" integer,
  "activo" tinyint(1) not null default '1',
  "fecha_alta" datetime,
  "fecha_baja" datetime,
  foreign key("id_departamento") references "departamentos"("id_departamento") on delete set null
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE "telefonos"(
  "id_telefono" integer primary key autoincrement not null,
  "numero_general" varchar,
  "extension" integer,
  "id_empleado" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "user_id" integer,
  foreign key("id_empleado") references empleados("id_empleado") on delete set null on update no action,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE "inventario_equipos"(
  "id" integer primary key autoincrement not null,
  "tipo" varchar not null,
  "consecutivo" integer,
  "num_inventario" varchar,
  "nombre_equipo" varchar,
  "nombre_usuario" text,
  "perfil" varchar,
  "area" text,
  "cpu_marca" varchar,
  "cpu_modelo" varchar,
  "cpu_serie" varchar,
  "teclado_serie" varchar,
  "mouse_serie" varchar,
  "monitor_marca" varchar,
  "monitor_modelo" varchar,
  "monitor_serie" varchar,
  "nobreak_marca" varchar,
  "nobreak_modelo" varchar,
  "nobreak_serie" varchar,
  "cargador_serie" varchar,
  "docking_marca" varchar,
  "docking_modelo" varchar,
  "docking_serie" varchar,
  "candado" varchar,
  "ipv4" varchar,
  "ipv4_actual" varchar,
  "mac" varchar,
  "responsiva" varchar,
  "check_entrega" varchar,
  "observaciones" text,
  "id_empleado" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "pdf_resguardo" varchar,
  "estado" varchar,
  "user_id" integer,
  foreign key("id_empleado") references empleados("id_empleado") on delete set null on update no action,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "inventario_equipos_area_index" on "inventario_equipos"("area");
CREATE INDEX "inventario_equipos_tipo_index" on "inventario_equipos"("tipo");
CREATE TABLE "impresoras"(
  "id_impresora" integer primary key autoincrement not null,
  "area" text,
  "marca" varchar,
  "modelo" varchar,
  "firmware" varchar,
  "serie" varchar,
  "ip_address" varchar,
  "id_empleado" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "user_id" integer,
  foreign key("id_empleado") references empleados("id_empleado") on delete set null on update no action,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE "inventario_ips_completo"(
  "id" integer primary key autoincrement not null,
  "ip" varchar not null,
  "usuario" text,
  "tipo_equipo" varchar,
  "institucional_o_personal" varchar,
  "marca" varchar,
  "modelo" varchar,
  "serie" varchar,
  "mac" varchar,
  "tipo_conexion" varchar,
  "config_red" varchar,
  "area_excel" varchar,
  "departamento_pestana" varchar,
  "restricciones" varchar,
  "youtube" varchar,
  "vimeo" varchar,
  "spotify" varchar,
  "otros_streaming" varchar,
  "facebook" varchar,
  "tiktok" varchar,
  "instagram" varchar,
  "whatsapp_web" varchar,
  "otra_red_social" varchar,
  "sitios_gub" varchar,
  "noticias" varchar,
  "otro_permiso" varchar,
  "estatus" varchar not null default('Libre'),
  "observaciones" text,
  "id_empleado" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "user_id" integer,
  foreign key("id_empleado") references empleados("id_empleado") on delete set null on update no action,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "inventario_ips_completo_departamento_pestana_index" on "inventario_ips_completo"(
  "departamento_pestana"
);
CREATE INDEX "inventario_ips_completo_estatus_index" on "inventario_ips_completo"(
  "estatus"
);
CREATE UNIQUE INDEX "inventario_ips_completo_ip_unique" on "inventario_ips_completo"(
  "ip"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_01_01_000000_add_role_to_users_table',1);
INSERT INTO migrations VALUES(5,'2025_01_01_000001_create_departamentos_table',1);
INSERT INTO migrations VALUES(6,'2025_01_01_000002_create_empleados_table',1);
INSERT INTO migrations VALUES(7,'2025_01_01_000003_create_telefonos_table',1);
INSERT INTO migrations VALUES(8,'2025_01_01_000004_create_inventario_equipos_table',1);
INSERT INTO migrations VALUES(9,'2025_01_01_000005_create_impresoras_table',1);
INSERT INTO migrations VALUES(10,'2025_01_01_000006_create_insumos_table',1);
INSERT INTO migrations VALUES(11,'2025_01_01_000007_add_pdf_resguardo_to_inventario_equipos',1);
INSERT INTO migrations VALUES(12,'2025_01_01_000007_create_network_tables',1);
INSERT INTO migrations VALUES(13,'2025_01_01_000010_create_tickets_table',1);
INSERT INTO migrations VALUES(14,'2025_01_01_000011_add_ip_mac_to_tickets_table',1);
INSERT INTO migrations VALUES(15,'2025_01_01_000012_create_ticket_comentarios_table',1);
INSERT INTO migrations VALUES(16,'2026_07_09_000001_add_estado_to_inventario_equipos',1);
INSERT INTO migrations VALUES(17,'2026_07_09_000002_link_equipos_ips_to_empleados',1);
INSERT INTO migrations VALUES(18,'2026_07_12_000001_add_empleado_fields_to_users_table',2);
INSERT INTO migrations VALUES(19,'2026_07_12_000002_add_user_id_to_telefonos_table',2);
INSERT INTO migrations VALUES(20,'2026_07_12_000003_add_user_id_to_inventario_equipos_table',2);
INSERT INTO migrations VALUES(21,'2026_07_12_000004_add_user_id_to_impresoras_table',2);
INSERT INTO migrations VALUES(22,'2026_07_12_000005_add_user_id_to_inventario_ips_completo_table',2);
