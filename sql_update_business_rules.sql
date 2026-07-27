-- Ejecutar una sola vez sobre una base existente del proyecto.
-- Agrega configuracion de empresa, planes parametrizados y asignacion de plan a miembros.

ALTER TABLE companies
  ADD COLUMN logo_url VARCHAR(500) DEFAULT NULL AFTER country,
  ADD COLUMN checkin_duplicate_policy ENUM('allow','confirm','block') NOT NULL DEFAULT 'confirm' AFTER logo_url;

CREATE TABLE IF NOT EXISTS payment_plans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  duration_days INT UNSIGNED NOT NULL DEFAULT 30,
  description TEXT DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payment_plans_company (company_id, status),
  CONSTRAINT fk_payment_plans_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE members
  ADD COLUMN plan_id INT UNSIGNED DEFAULT NULL AFTER gender,
  ADD COLUMN membership_valid_until DATE DEFAULT NULL AFTER plan_id,
  ADD KEY idx_members_plan (company_id, plan_id),
  ADD KEY idx_members_validity (company_id, status, membership_valid_until),
  ADD CONSTRAINT fk_members_plan FOREIGN KEY (plan_id) REFERENCES payment_plans (id) ON DELETE SET NULL;

INSERT INTO payment_plans (company_id, name, price, duration_days, description, status)
SELECT id, 'Mensual', 5000.00, 30, 'Acceso mensual general', 'active'
FROM companies
WHERE NOT EXISTS (
  SELECT 1 FROM payment_plans WHERE payment_plans.company_id = companies.id
);

UPDATE members m
JOIN payment_plans p ON p.company_id = m.company_id AND p.name = 'Mensual'
SET m.plan_id = p.id
WHERE m.plan_id IS NULL;

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
SET m.membership_valid_until = validity.valid_until
WHERE m.membership_valid_until IS NULL;
