-- Run this only on an existing college_lost_found database.
-- It preserves existing users/items/claims. Back up the database first.
USE college_lost_found;
SET SQL_SAFE_UPDATES = 0;

ALTER TABLE users
  ADD COLUMN usn VARCHAR(30) NULL AFTER email,
  ADD COLUMN phone VARCHAR(20) NULL AFTER usn;

-- Give existing accounts temporary unique values; students can update them manually in Workbench.
UPDATE users SET usn = CONCAT('TEMP-', id), phone = 'Not provided' WHERE usn IS NULL OR phone IS NULL;

ALTER TABLE users
  MODIFY usn VARCHAR(30) NOT NULL,
  MODIFY phone VARCHAR(20) NOT NULL,
  ADD UNIQUE KEY uq_users_usn (usn);

-- Attach bundled sample photos to the four seeded example reports if those reports exist.
UPDATE items SET photo = 'wallet.jpg' WHERE title = 'Black leather wallet' AND (photo IS NULL OR photo = '');
UPDATE items SET photo = 'bottle.jpg' WHERE title = 'Blue water bottle' AND (photo IS NULL OR photo = '');
UPDATE items SET photo = 'calculator.jpg' WHERE title = 'Scientific calculator' AND (photo IS NULL OR photo = '');
UPDATE items SET photo = 'keys.jpg' WHERE title = 'Set of keys' AND (photo IS NULL OR photo = '');

SET SQL_SAFE_UPDATES = 1;
