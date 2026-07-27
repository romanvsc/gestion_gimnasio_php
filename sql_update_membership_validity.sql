-- Ejecutar una sola vez si la base ya tiene planes y plan_id en members.
-- Agrega vigencia real de membresia y la inicializa desde el ultimo pago registrado.

ALTER TABLE members
  ADD COLUMN membership_valid_until DATE DEFAULT NULL AFTER plan_id,
  ADD KEY idx_members_validity (company_id, status, membership_valid_until);

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
