-- Tabla para reportes de mantenimiento preventivo y correctivo
-- Ejecutar una sola vez en pgAdmin

CREATE TABLE IF NOT EXISTS reportes_mantenimiento (
    id_reporte        SERIAL PRIMARY KEY,
    folio             VARCHAR(25) UNIQUE,
    fecha_reporte     DATE NOT NULL DEFAULT CURRENT_DATE,
    -- Equipo (copiado al momento de crear el reporte)
    id_equipo         INTEGER REFERENCES inventario_equipos(id),
    tipo_equipo       VARCHAR(25),
    nombre_equipo     VARCHAR(60),
    cpu_marca         VARCHAR(60),
    cpu_modelo        VARCHAR(100),
    cpu_serie         VARCHAR(100),
    num_inventario    VARCHAR(30),
    -- Usuario / Ubicación
    nombre_usuario    TEXT,
    area              TEXT,
    ubicacion_equipo  VARCHAR(100),
    -- Servicio
    tipo_mantenimiento VARCHAR(20) DEFAULT 'Correctivo',  -- Preventivo / Correctivo
    estado            VARCHAR(15) DEFAULT 'Abierto',       -- Abierto / Pendiente / Cerrado
    -- Contenido
    falla_reportada   TEXT,
    acciones_realizadas TEXT,
    quedo_funcionando BOOLEAN,
    -- Tiempos
    fecha_inicio      TIMESTAMP,
    fecha_conclusion  TIMESTAMP,
    -- Evaluación
    eval_servicio     SMALLINT CHECK (eval_servicio BETWEEN 0 AND 10),
    eval_tecnico      SMALLINT CHECK (eval_tecnico BETWEEN 0 AND 10),
    -- Personal
    nombre_tecnico    TEXT,
    observaciones     TEXT,
    fecha_creacion    TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_mant_estado  ON reportes_mantenimiento (estado);
CREATE INDEX IF NOT EXISTS idx_mant_equipo  ON reportes_mantenimiento (id_equipo);
CREATE INDEX IF NOT EXISTS idx_mant_fecha   ON reportes_mantenimiento (fecha_reporte);
