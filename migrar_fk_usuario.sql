-- Migración: agregar FK id_usuario a inventario_ips_completo
-- Ejecutar una sola vez en pgAdmin o psql

-- 1. Agregar columna (si no existe)
ALTER TABLE inventario_ips_completo
    ADD COLUMN IF NOT EXISTS id_usuario INTEGER REFERENCES usuarios(id_usuario);

-- 2. Poblar FK donde el texto de usuario coincide con apellido_paterno registrado
UPDATE inventario_ips_completo i
SET id_usuario = (
    SELECT u.id_usuario
    FROM usuarios u
    WHERE i.usuario ILIKE '%' || u.apellido_paterno || '%'
    ORDER BY LENGTH(u.apellido_paterno) DESC
    LIMIT 1
)
WHERE i.usuario IS NOT NULL
  AND i.id_usuario IS NULL;

-- 3. Verificar resultados
SELECT
    COUNT(*)                                        AS total_ips,
    COUNT(*) FILTER (WHERE id_usuario IS NOT NULL)  AS con_fk,
    COUNT(*) FILTER (WHERE id_usuario IS NULL
                      AND usuario IS NOT NULL)      AS texto_sin_match,
    COUNT(*) FILTER (WHERE usuario IS NULL)         AS libres
FROM inventario_ips_completo;
