CREATE DATABASE IF NOT EXISTS college_lost_found CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE college_lost_found;

DROP TABLE IF EXISTS claims;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  usn VARCHAR(30) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(50) NOT NULL,
  location VARCHAR(150) NOT NULL,
  item_type ENUM('lost','found') NOT NULL,
  item_date DATE NOT NULL,
  status ENUM('open','claimed','closed') NOT NULL DEFAULT 'open',
  photo VARCHAR(60) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (status), INDEX (category), INDEX (location),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE claims (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  claimant_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (claimant_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample users (admin password: Admin@123, students: Student@123) -- change after first login!
INSERT INTO users (name, email, usn, phone, password, role) VALUES
('Portal Admin', 'admin@college.edu', 'ADMIN0001', '9000000001', '$2b$10$fMa35d0eZLWG7G54DFYvquK.PWcsCDk2QWWps6rpl2bwXCLRv1xI2', 'admin'),
('Asha Student', 'asha@college.edu', '1BM24CS001', '9000000002', '$2b$10$SiOvlab7KkKcpzH76GQV.eXHetDJrACazV4KZ3zQ.lcM7WT2D7nFW', 'student'),
('Ravi Student', 'ravi@college.edu', '1BM24CS002', '9000000003', '$2b$10$SiOvlab7KkKcpzH76GQV.eXHetDJrACazV4KZ3zQ.lcM7WT2D7nFW', 'student');

INSERT INTO items (user_id, title, description, category, location, item_type, item_date, status, photo) VALUES
(2, 'Black leather wallet', 'Black wallet with a college ID card and a few receipts inside.', 'Wallets', 'Library', 'lost', CURDATE() - INTERVAL 3 DAY, 'open', 'wallet.jpg'),
(3, 'Blue water bottle', 'Blue steel bottle with a sticker of a mountain on the side.', 'Accessories', 'Canteen', 'found', CURDATE() - INTERVAL 2 DAY, 'open', 'bottle.jpg'),
(3, 'Scientific calculator', 'Casio FX-991 calculator with initials R.K. on the back.', 'Electronics', 'Computer Lab', 'found', CURDATE() - INTERVAL 1 DAY, 'open', 'calculator.jpg'),
(2, 'Set of keys', 'Three keys on a red keychain.', 'Keys', 'Sports Ground', 'lost', CURDATE() - INTERVAL 5 DAY, 'open', 'keys.jpg');

INSERT INTO claims (item_id, claimant_id, message, status) VALUES
(3, 2, 'This is my calculator, it has my initials A.S. scratched inside the battery cover.', 'pending');
