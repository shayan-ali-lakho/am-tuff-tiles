-- 004: order without an account, and pay by EasyPaisa
-- contact_id may now be empty (guest order). payment_method gets 'easypaisa'. For EasyPaisa orders we keep the
-- path of the customer's payment screenshot (stored privately, outside the public folder) and the amount they say they sent.
-- Every statement is safe to run twice: the new columns are only added when they are missing.

ALTER TABLE orders MODIFY contact_id INT UNSIGNED NULL;

ALTER TABLE orders MODIFY payment_method ENUM('cod','easypaisa') NOT NULL DEFAULT 'cod';

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE orders ADD COLUMN payment_proof VARCHAR(255) NULL AFTER payment_method', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_proof');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE orders ADD COLUMN paid_paisa INT UNSIGNED NOT NULL DEFAULT 0 AFTER payment_proof', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'paid_paisa');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
