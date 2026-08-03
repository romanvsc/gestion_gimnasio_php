-- ============================================================
-- SINCRONIZACION DE ESQUEMA PARA FEROZO
-- Ejecutar desde phpMyAdmin sobre la base configurada en backend/.env.
-- Es idempotente: solo agrega columnas e indices que no existen.
-- No elimina ni modifica datos existentes.
-- ============================================================

SET NAMES utf8mb4;
SET @schema_name = DATABASE();

-- El catalogo de planes puede no existir en instalaciones muy antiguas.
CREATE TABLE IF NOT EXISTS `payment_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `club_member_price` DECIMAL(10,2) NULL,
  `duration_days` INT UNSIGNED NOT NULL DEFAULT 30,
  `description` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_plans_company` (`company_id`, `status`),
  CONSTRAINT `fk_payment_plans_company`
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campos de configuracion de empresa. No se usa AFTER para tolerar esquemas antiguos.
SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'logo_url') = 0,
  'ALTER TABLE `companies` ADD COLUMN `logo_url` VARCHAR(500) NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

-- companies.opening_hours
SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'opening_hours') = 0,
  'ALTER TABLE `companies` ADD COLUMN `opening_hours` VARCHAR(255) NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'checkin_duplicate_policy') = 0,
  'ALTER TABLE `companies` ADD COLUMN `checkin_duplicate_policy` ENUM(''allow'',''confirm'',''block'') NOT NULL DEFAULT ''confirm''',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

-- payment_plans.club_member_price
SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'payment_plans' AND COLUMN_NAME = 'club_member_price') = 0,
  'ALTER TABLE `payment_plans` ADD COLUMN `club_member_price` DECIMAL(10,2) NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

-- Campos operativos de socios.
SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'plan_id') = 0,
  'ALTER TABLE `members` ADD COLUMN `plan_id` INT UNSIGNED NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'membership_valid_until') = 0,
  'ALTER TABLE `members` ADD COLUMN `membership_valid_until` DATE NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'photo_url') = 0,
  'ALTER TABLE `members` ADD COLUMN `photo_url` VARCHAR(500) NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'medical_certificate_valid_until') = 0,
  'ALTER TABLE `members` ADD COLUMN `medical_certificate_valid_until` DATE NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'weight_kg') = 0,
  'ALTER TABLE `members` ADD COLUMN `weight_kg` DECIMAL(6,2) NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'height_cm') = 0,
  'ALTER TABLE `members` ADD COLUMN `height_cm` DECIMAL(6,2) NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'joined_at') = 0,
  'ALTER TABLE `members` ADD COLUMN `joined_at` DATE NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND COLUMN_NAME = 'is_club_member') = 0,
  'ALTER TABLE `members` ADD COLUMN `is_club_member` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

-- Campos operativos de pagos.
SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'period_start') = 0,
  'ALTER TABLE `payments` ADD COLUMN `period_start` DATE NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'period_end') = 0,
  'ALTER TABLE `payments` ADD COLUMN `period_end` DATE NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'legacy_method_name') = 0,
  'ALTER TABLE `payments` ADD COLUMN `legacy_method_name` VARCHAR(80) NULL',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

-- Campo operativo de ingresos.
SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'checkins' AND COLUMN_NAME = 'access_allowed') = 0,
  'ALTER TABLE `checkins` ADD COLUMN `access_allowed` TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

-- Indices de lectura; tambien se agregan de forma condicional.
SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND INDEX_NAME = 'idx_members_plan') = 0,
  'ALTER TABLE `members` ADD INDEX `idx_members_plan` (`company_id`, `plan_id`)',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND INDEX_NAME = 'idx_members_validity') = 0,
  'ALTER TABLE `members` ADD INDEX `idx_members_validity` (`company_id`, `status`, `membership_valid_until`)',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'members' AND INDEX_NAME = 'idx_members_medical_certificate') = 0,
  'ALTER TABLE `members` ADD INDEX `idx_members_medical_certificate` (`company_id`, `medical_certificate_valid_until`)',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'payments' AND INDEX_NAME = 'idx_payments_period') = 0,
  'ALTER TABLE `payments` ADD INDEX `idx_payments_period` (`company_id`, `period_start`, `period_end`)',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SET @ddl = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'checkins' AND INDEX_NAME = 'idx_checkins_access_allowed') = 0,
  'ALTER TABLE `checkins` ADD INDEX `idx_checkins_access_allowed` (`company_id`, `access_allowed`, `checkin_at`)',
  'SELECT 1'
);
PREPARE sync_stmt FROM @ddl; EXECUTE sync_stmt; DEALLOCATE PREPARE sync_stmt;

SELECT 'Esquema operativo sincronizado correctamente' AS result;
