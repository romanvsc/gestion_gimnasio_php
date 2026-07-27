-- ============================================================
-- SISTEMA DE GESTION DE GIMNASIO - SaaS Multiempresa
-- Ejecutar este script en la base de datos: c2650268_gym
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ------------------------------------------------------------
-- TABLA: companies (tenant raiz)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `companies` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150)    NOT NULL,
  `email`      VARCHAR(150)    NOT NULL,
  `phone`      VARCHAR(30)              DEFAULT NULL,
  `address`    VARCHAR(255)             DEFAULT NULL,
  `city`       VARCHAR(100)             DEFAULT NULL,
  `country`    VARCHAR(100)             DEFAULT 'Argentina',
  `logo_url`   VARCHAR(500)             DEFAULT NULL,
  `checkin_duplicate_policy` ENUM('allow','confirm','block') NOT NULL DEFAULT 'confirm',
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_company_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: payment_plans (planes parametrizados por empresa)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_plans` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `company_id`  INT UNSIGNED  NOT NULL,
  `name`        VARCHAR(120)  NOT NULL,
  `price`       DECIMAL(10,2) NOT NULL,
  `duration_days` INT UNSIGNED NOT NULL DEFAULT 30,
  `description` TEXT                  DEFAULT NULL,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_plans_company` (`company_id`, `status`),
  CONSTRAINT `fk_payment_plans_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: users (staff / admin por empresa)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT UNSIGNED NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login`    TIMESTAMP    NULL      DEFAULT NULL,
  `created_at`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email_company` (`email`, `company_id`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: members (socios del gimnasio)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `company_id` INT UNSIGNED  NOT NULL,
  `first_name` VARCHAR(80)   NOT NULL,
  `last_name`  VARCHAR(80)   NOT NULL,
  `email`      VARCHAR(150)           DEFAULT NULL,
  `phone`      VARCHAR(30)            DEFAULT NULL,
  `dni`        VARCHAR(20)            DEFAULT NULL,
  `birthdate`  DATE                   DEFAULT NULL,
  `address`    VARCHAR(255)           DEFAULT NULL,
  `gender`     ENUM('male','female','other') DEFAULT NULL,
  `plan_id`    INT UNSIGNED           DEFAULT NULL,
  `membership_valid_until` DATE       DEFAULT NULL,
  `notes`      TEXT                   DEFAULT NULL,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_members_company` (`company_id`),
  KEY `idx_members_status`  (`company_id`, `status`),
  KEY `idx_members_dni`     (`company_id`, `dni`),
  KEY `idx_members_plan`    (`company_id`, `plan_id`),
  KEY `idx_members_validity` (`company_id`, `status`, `membership_valid_until`),
  CONSTRAINT `fk_members_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_members_plan`    FOREIGN KEY (`plan_id`)    REFERENCES `payment_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: payments (pagos de cuota u otros conceptos)
-- "Cuota al dia" = socio activo con membership_valid_until >= fecha actual
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `company_id`      INT UNSIGNED    NOT NULL,
  `member_id`       INT UNSIGNED    NOT NULL,
  `amount`          DECIMAL(10,2)   NOT NULL,
  `concept`         VARCHAR(200)    NOT NULL DEFAULT 'Cuota mensual',
  `payment_date`    DATE            NOT NULL,
  `method`          ENUM('cash','transfer','card','other') NOT NULL DEFAULT 'cash',
  `notes`           TEXT                     DEFAULT NULL,
  `registered_by`   INT UNSIGNED             DEFAULT NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_company`      (`company_id`),
  KEY `idx_payments_member`       (`company_id`, `member_id`),
  KEY `idx_payments_date`         (`company_id`, `payment_date`),
  KEY `idx_payments_member_date`  (`company_id`, `member_id`, `payment_date`),
  CONSTRAINT `fk_payments_company`  FOREIGN KEY (`company_id`)    REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_member`   FOREIGN KEY (`member_id`)     REFERENCES `members`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_user`     FOREIGN KEY (`registered_by`) REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLA: checkins (registro de ingresos al gym)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checkins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id`    INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `checkin_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registered_by` INT UNSIGNED          DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_checkins_company`      (`company_id`),
  KEY `idx_checkins_member`       (`company_id`, `member_id`),
  KEY `idx_checkins_date`         (`company_id`, `checkin_at`),
  CONSTRAINT `fk_checkins_company` FOREIGN KEY (`company_id`)    REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checkins_member`  FOREIGN KEY (`member_id`)     REFERENCES `members`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checkins_user`    FOREIGN KEY (`registered_by`) REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DEMO
-- Empresa: Demo Gym | Admin: admin@demogym.com / Admin1234!
-- ============================================================

INSERT INTO `companies` (`id`, `name`, `email`, `phone`, `address`, `city`, `status`) VALUES
(1, 'Demo Gym', 'admin@demogym.com', '+54 11 1234-5678', 'Av. Siempre Viva 742', 'Buenos Aires', 'active');

INSERT INTO `payment_plans` (`id`, `company_id`, `name`, `price`, `duration_days`, `description`, `status`) VALUES
(1, 1, 'Mensual', 5000.00, 30, 'Acceso mensual general', 'active'),
(2, 1, 'Trimestral', 13500.00, 90, 'Plan trimestral con descuento', 'active'),
(3, 1, 'Pase diario', 1000.00, 1, 'Acceso por dia', 'active');

-- Contrasena: password (bcrypt, cost 12)
INSERT INTO `users` (`id`, `company_id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 1, 'Administrador Demo', 'admin@demogym.com',
 '$2y$12$IJUSy6owGQ3g4KuYuz/cYeEs0CncjaBVR7sw/9V612Dz6hXSfLL5y', -- password: password
 'admin', 'active');

-- Nota: el hash de arriba corresponde a la contrasena: "password"
-- Para cambiarla, genera un nuevo hash con: password_hash('TuNuevaContrasena', PASSWORD_BCRYPT, ['cost' => 12])

INSERT INTO `members` (`id`, `company_id`, `first_name`, `last_name`, `email`, `phone`, `dni`, `birthdate`, `gender`, `plan_id`, `status`) VALUES
(1,  1, 'Carlos',    'Ramirez',    'carlos@email.com',   '+54911111111', '30111222', '1990-05-15', 'male',   1, 'active'),
(2,  1, 'Maria',     'Gonzalez',   'maria@email.com',    '+54922222222', '31222333', '1995-08-20', 'female', 1, 'active'),
(3,  1, 'Lucas',     'Fernandez',  'lucas@email.com',    '+54933333333', '32333444', '1988-03-10', 'male',   1, 'active'),
(4,  1, 'Sofia',     'Martinez',   'sofia@email.com',    '+54944444444', '33444555', '1998-11-30', 'female', 2, 'active'),
(5,  1, 'Diego',     'Lopez',      'diego@email.com',    '+54955555555', '34555666', '1985-07-25', 'male',   1, 'active'),
(6,  1, 'Valentina', 'Torres',     'val@email.com',      '+54966666666', '35666777', '2000-01-12', 'female', 1, 'active'),
(7,  1, 'Andres',    'Perez',      'andres@email.com',   '+54977777777', '36777888', '1992-09-05', 'male',   1, 'inactive'),
(8,  1, 'Lucia',     'Sanchez',    'lucia@email.com',    '+54988888888', '37888999', '1997-04-18', 'female', 2, 'active'),
(9,  1, 'Martin',    'Castro',     'martin@email.com',   '+54999999999', '38999000', '1983-12-22', 'male',   1, 'active'),
(10, 1, 'Camila',    'Romero',     'camila@email.com',   '+54900000001', '39000111', '2001-06-08', 'female', 1, 'active');

-- Pagos del mes actual (cuota al dia)
INSERT INTO `payments` (`company_id`, `member_id`, `amount`, `concept`, `payment_date`, `method`, `registered_by`) VALUES
(1, 1,  5000.00, 'Cuota mensual', CURDATE(), 'cash',     1),
(1, 2,  5000.00, 'Cuota mensual', CURDATE(), 'transfer', 1),
(1, 3,  5000.00, 'Cuota mensual', CURDATE(), 'cash',     1),
(1, 4,  5000.00, 'Cuota mensual', CURDATE(), 'card',     1),
(1, 6,  5000.00, 'Cuota mensual', CURDATE(), 'cash',     1),
(1, 8,  5000.00, 'Cuota mensual', CURDATE(), 'transfer', 1);

-- Pagos del mes anterior (historial)
INSERT INTO `payments` (`company_id`, `member_id`, `amount`, `concept`, `payment_date`, `method`, `registered_by`) VALUES
(1, 1, 4500.00, 'Cuota mensual', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), 'cash',     1),
(1, 2, 4500.00, 'Cuota mensual', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), 'transfer', 1),
(1, 5, 4500.00, 'Cuota mensual', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), 'card',     1),
(1, 9, 4500.00, 'Cuota mensual', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'), 'cash',     1);

UPDATE members m
JOIN (
  SELECT
    p.company_id,
    p.member_id,
    DATE_ADD(MAX(p.payment_date), INTERVAL COALESCE(pp.duration_days, 30) - 1 DAY) AS valid_until
  FROM payments p
  JOIN members member_for_payment
    ON member_for_payment.id = p.member_id
   AND member_for_payment.company_id = p.company_id
  LEFT JOIN payment_plans pp
    ON pp.id = member_for_payment.plan_id
   AND pp.company_id = member_for_payment.company_id
  GROUP BY p.company_id, p.member_id, pp.duration_days
) validity
  ON validity.company_id = m.company_id
 AND validity.member_id = m.id
SET m.membership_valid_until = validity.valid_until;

-- Check-ins de hoy
INSERT INTO `checkins` (`company_id`, `member_id`, `registered_by`) VALUES
(1, 1, 1), (1, 2, 1), (1, 3, 1), (1, 4, 1), (1, 6, 1), (1, 8, 1);

-- Check-ins de los ultimos dias
INSERT INTO `checkins` (`company_id`, `member_id`, `checkin_at`, `registered_by`) VALUES
(1, 1,  DATE_SUB(NOW(), INTERVAL 1 DAY),  1),
(1, 2,  DATE_SUB(NOW(), INTERVAL 1 DAY),  1),
(1, 5,  DATE_SUB(NOW(), INTERVAL 1 DAY),  1),
(1, 9,  DATE_SUB(NOW(), INTERVAL 1 DAY),  1),
(1, 3,  DATE_SUB(NOW(), INTERVAL 2 DAY),  1),
(1, 4,  DATE_SUB(NOW(), INTERVAL 2 DAY),  1),
(1, 10, DATE_SUB(NOW(), INTERVAL 2 DAY),  1),
(1, 1,  DATE_SUB(NOW(), INTERVAL 3 DAY),  1),
(1, 6,  DATE_SUB(NOW(), INTERVAL 3 DAY),  1),
(1, 8,  DATE_SUB(NOW(), INTERVAL 3 DAY),  1),
(1, 2,  DATE_SUB(NOW(), INTERVAL 4 DAY),  1),
(1, 9,  DATE_SUB(NOW(), INTERVAL 5 DAY),  1),
(1, 3,  DATE_SUB(NOW(), INTERVAL 5 DAY),  1),
(1, 5,  DATE_SUB(NOW(), INTERVAL 6 DAY),  1),
(1, 10, DATE_SUB(NOW(), INTERVAL 6 DAY),  1);

SET foreign_key_checks = 1;

-- ============================================================
-- RESUMEN
-- Empresa demo: "Demo Gym" (company_id = 1)
-- Login: admin@demogym.com / password
-- Miembros: 10 (9 activos, 1 inactivo)
-- Pagos al dia: 6 socios (miembros 1,2,3,4,6,8)
-- En mora: 4 socios activos (5,9,10 + 7 inactivo)
-- Check-ins hoy: 6
-- ============================================================
