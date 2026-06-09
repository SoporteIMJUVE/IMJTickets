-- Migración: nueva tabla inventario_equipos
-- Ejecutar una sola vez en pgAdmin antes de cargar el nuevo Excel

CREATE TABLE IF NOT EXISTS inventario_equipos (
    id                SERIAL PRIMARY KEY,
    tipo              VARCHAR(25) NOT NULL,   -- 'Laptop', 'PC Avanzada', 'PC Especializada'
    consecutivo       INTEGER,
    num_inventario    VARCHAR(30),
    nombre_equipo     VARCHAR(60),
    nombre_usuario    TEXT,
    perfil            VARCHAR(100),
    area              TEXT,
    -- CPU / equipo principal
    cpu_marca         VARCHAR(60),
    cpu_modelo        VARCHAR(100),
    cpu_serie         VARCHAR(100),
    -- Periféricos (PCs avanzadas y especializadas)
    teclado_serie     VARCHAR(100),
    mouse_serie       VARCHAR(100),
    monitor_marca     VARCHAR(60),
    monitor_modelo    VARCHAR(100),
    monitor_serie     VARCHAR(100),
    nobreak_marca     VARCHAR(60),
    nobreak_modelo    VARCHAR(100),
    nobreak_serie     VARCHAR(100),
    -- Laptop específico
    cargador_serie    VARCHAR(100),
    docking_marca     VARCHAR(60),
    docking_modelo    VARCHAR(100),
    docking_serie     VARCHAR(100),
    candado           VARCHAR(50),
    -- Red
    ipv4              VARCHAR(20),
    ipv4_actual       VARCHAR(20),            -- PC Especializadas
    mac               VARCHAR(30),
    -- Estado / documentación
    responsiva        VARCHAR(50),
    check_entrega     VARCHAR(50),
    observaciones     TEXT,
    -- FK a usuarios (se puebla automáticamente al cargar)
    id_usuario        INTEGER REFERENCES usuarios(id_usuario),
    fecha_carga       TIMESTAMP DEFAULT NOW()
);

-- Índices para búsquedas frecuentes
CREATE INDEX IF NOT EXISTS idx_inv_eq_tipo   ON inventario_equipos (tipo);
CREATE INDEX IF NOT EXISTS idx_inv_eq_area   ON inventario_equipos (area);
CREATE INDEX IF NOT EXISTS idx_inv_eq_usr    ON inventario_equipos (id_usuario);
