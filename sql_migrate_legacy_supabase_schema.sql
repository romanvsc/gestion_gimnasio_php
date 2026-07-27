-- ============================================================
-- MIGRACION LEGACY SUPABASE -> SISTEMA ACTUAL
-- Ejecutar una vez antes de backend/tools/import_legacy_supabase.php
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- Ampliaciones de tablas existentes
-- ------------------------------------------------------------

ALTER TABLE companies
  ADD COLUMN opening_hours VARCHAR(255) NULL AFTER logo_url;

ALTER TABLE users
  ADD COLUMN legacy_staff_id CHAR(36) NULL AFTER company_id,
  ADD UNIQUE KEY uq_users_company_legacy_staff (company_id, legacy_staff_id);

ALTER TABLE payment_plans
  ADD COLUMN legacy_plan_id INT UNSIGNED NULL AFTER company_id,
  ADD COLUMN club_member_price DECIMAL(10,2) NULL AFTER price,
  ADD UNIQUE KEY uq_payment_plans_company_legacy (company_id, legacy_plan_id);

ALTER TABLE members
  ADD COLUMN legacy_member_id CHAR(36) NULL AFTER company_id,
  ADD COLUMN photo_url VARCHAR(500) NULL AFTER gender,
  ADD COLUMN medical_certificate_valid_until DATE NULL AFTER photo_url,
  ADD COLUMN weight_kg DECIMAL(6,2) NULL AFTER medical_certificate_valid_until,
  ADD COLUMN height_cm DECIMAL(6,2) NULL AFTER weight_kg,
  ADD COLUMN joined_at DATE NULL AFTER height_cm,
  ADD COLUMN is_club_member TINYINT(1) NOT NULL DEFAULT 0 AFTER joined_at,
  ADD UNIQUE KEY uq_members_company_legacy (company_id, legacy_member_id),
  ADD KEY idx_members_medical_certificate (company_id, medical_certificate_valid_until);

ALTER TABLE payments
  ADD COLUMN legacy_payment_id CHAR(36) NULL AFTER company_id,
  ADD COLUMN period_start DATE NULL AFTER payment_date,
  ADD COLUMN period_end DATE NULL AFTER period_start,
  ADD COLUMN legacy_method_name VARCHAR(80) NULL AFTER method,
  ADD UNIQUE KEY uq_payments_company_legacy (company_id, legacy_payment_id),
  ADD KEY idx_payments_period (company_id, period_start, period_end);

ALTER TABLE checkins
  ADD COLUMN legacy_attendance_id CHAR(36) NULL AFTER company_id,
  ADD COLUMN access_allowed TINYINT(1) NOT NULL DEFAULT 1 AFTER checkin_at,
  ADD UNIQUE KEY uq_checkins_company_legacy (company_id, legacy_attendance_id),
  ADD KEY idx_checkins_access_allowed (company_id, access_allowed, checkin_at);

-- ------------------------------------------------------------
-- Catalogos legacy y caja
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS payment_methods (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  legacy_method_id INT UNSIGNED NULL,
  name VARCHAR(80) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_methods_company_legacy (company_id, legacy_method_id),
  KEY idx_payment_methods_company (company_id, status),
  CONSTRAINT fk_payment_methods_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_concepts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  legacy_concept_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  type ENUM('income','expense','both') NOT NULL DEFAULT 'income',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_financial_concepts_company_legacy (company_id, legacy_concept_id),
  KEY idx_financial_concepts_company (company_id, type, status),
  CONSTRAINT fk_financial_concepts_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cash_transactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  legacy_transaction_id CHAR(36) NULL,
  type ENUM('income','expense') NOT NULL,
  category VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_id INT UNSIGNED NULL,
  registered_by INT UNSIGNED NULL,
  transaction_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cash_transactions_company_legacy (company_id, legacy_transaction_id),
  KEY idx_cash_transactions_company_date (company_id, transaction_at),
  KEY idx_cash_transactions_payment (company_id, payment_id),
  KEY idx_cash_transactions_payment_id (payment_id),
  KEY idx_cash_transactions_registered_by (registered_by),
  CONSTRAINT fk_cash_transactions_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
  CONSTRAINT fk_cash_transactions_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE SET NULL,
  CONSTRAINT fk_cash_transactions_user FOREIGN KEY (registered_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS legacy_import_runs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NULL,
  source_name VARCHAR(120) NOT NULL,
  csv_dir VARCHAR(500) NOT NULL,
  mode ENUM('dry-run','execute') NOT NULL,
  status ENUM('started','completed','failed') NOT NULL DEFAULT 'started',
  expected_counts JSON NULL,
  actual_counts JSON NULL,
  errors JSON NULL,
  started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_legacy_import_runs_company (company_id, status),
  CONSTRAINT fk_legacy_import_runs_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
