-- Migración: módulo admin-plantas / soporte multi-planta
-- Fecha: 2026-05-09
-- Base core: easyseri
-- Base cámaras: ubicacion
--
-- IMPORTANTE:
-- 1) Hacer copia/export antes de ejecutar.
-- 2) Ejecutar primero el bloque USE easyseri.
-- 3) Ejecutar después el bloque USE ubicacion.
-- 4) Revisar los SELECT finales.

USE easyseri;

CREATE TABLE IF NOT EXISTS core_plants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO core_plants (code, name, is_active)
VALUES
    ('A1', 'Planta A1', 1),
    ('A2', 'Planta A2', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = VALUES(is_active);

CREATE TABLE IF NOT EXISTS core_user_plants (
    user_id INT NOT NULL,
    plant_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, plant_id),
    CONSTRAINT fk_core_user_plants_user
        FOREIGN KEY (user_id) REFERENCES core_users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_core_user_plants_plant
        FOREIGN KEY (plant_id) REFERENCES core_plants(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Añadir planta por defecto al usuario.
-- Si esta columna ya existe, comentar este ALTER antes de ejecutar.
ALTER TABLE core_users
ADD COLUMN default_plant_id INT NULL AFTER is_active;

-- Añadir FK de planta por defecto.
-- Si ya existe una FK equivalente, comentar este ALTER antes de ejecutar.
ALTER TABLE core_users
ADD CONSTRAINT fk_core_users_default_plant
    FOREIGN KEY (default_plant_id) REFERENCES core_plants(id)
    ON DELETE SET NULL;

-- Asignación inicial segura:
-- Todos los usuarios actuales reciben A1 porque el sistema existente trabajaba con cámaras A1.
INSERT IGNORE INTO core_user_plants (user_id, plant_id)
SELECT u.id, p.id
FROM core_users u
JOIN core_plants p ON p.code = 'A1';

-- Pablo recibe también A2 para poder hacer prueba real en planta A2.
INSERT IGNORE INTO core_user_plants (user_id, plant_id)
SELECT u.id, p.id
FROM core_users u
JOIN core_plants p ON p.code = 'A2'
WHERE u.email = 'pablo@test.com';

-- Planta por defecto inicial:
-- Si el usuario es Pablo, A2; el resto A1.
UPDATE core_users u
JOIN core_plants p ON p.code = CASE WHEN u.email = 'pablo@test.com' THEN 'A2' ELSE 'A1' END
SET u.default_plant_id = p.id
WHERE u.default_plant_id IS NULL;

-- Permisos del módulo admin-plantas.
INSERT INTO core_permissions (code, description, module_name)
VALUES
    ('admin-plantas.access', 'Acceso al módulo de gestión de plantas', 'admin-plantas'),
    ('admin-plantas.manage', 'Crear, editar y asignar plantas', 'admin-plantas')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    module_name = VALUES(module_name);

-- Activar módulo si ya fue sincronizado por ModuleManager.
UPDATE core_modules
SET enabled = 1
WHERE name = 'admin-plantas';

-- Dar permisos de admin-plantas a roles que ya tengan admin-usuarios o admin-roles.
-- Si quieres asignarlo manualmente desde admin-roles, puedes omitir este bloque.
INSERT IGNORE INTO core_role_permissions (role_id, permission_id)
SELECT DISTINCT rp.role_id, p_new.id
FROM core_role_permissions rp
JOIN core_permissions p_old ON p_old.id = rp.permission_id
JOIN core_permissions p_new ON p_new.code IN ('admin-plantas.access', 'admin-plantas.manage')
WHERE p_old.code IN ('admin-usuarios.access', 'admin-roles.access', 'admin-modulos.access');

USE ubicacion;

-- Añadir planta a cámaras.
-- Si esta columna ya existe, comentar este ALTER antes de ejecutar.
ALTER TABLE cameras
ADD COLUMN plant_code VARCHAR(20) NULL AFTER code;

-- Todas las cámaras existentes son A1 según verificación actual.
UPDATE cameras
SET plant_code = 'A1'
WHERE plant_code IS NULL;

-- Índice para filtrar cámaras por planta.
CREATE INDEX idx_cameras_plant_code ON cameras (plant_code);

-- Comprobaciones finales
USE easyseri;

SELECT id, code, name, is_active
FROM core_plants
ORDER BY code;

SELECT
    u.id,
    u.name,
    u.email,
    dp.code AS default_plant,
    GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ', ') AS assigned_plants
FROM core_users u
LEFT JOIN core_plants dp ON dp.id = u.default_plant_id
LEFT JOIN core_user_plants up ON up.user_id = u.id
LEFT JOIN core_plants p ON p.id = up.plant_id
GROUP BY u.id, u.name, u.email, dp.code
ORDER BY u.id;

USE ubicacion;

SELECT id, name, code, plant_code, priority
FROM cameras
ORDER BY priority DESC, id ASC;
