-- ============================================================
--  MLM BINARY SYSTEM — FULL SCHEMA + SEED DATA
--  Run once: mysql -u root -p kensue2_db < install.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS kensue2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kensue2_db;

-- ─── PACKAGES ────────────────────────────────────────────────
CREATE TABLE packages (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(80)      NOT NULL,
  entry_fee        DECIMAL(12,2)    NOT NULL,
  pairing_bonus    DECIMAL(12,2)    NOT NULL,
  daily_pair_cap   TINYINT UNSIGNED NOT NULL DEFAULT 3,
  direct_ref_bonus DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── INDIRECT REFERRAL LEVELS ────────────────────────────────
CREATE TABLE package_indirect_levels (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id INT UNSIGNED     NOT NULL,
  level      TINYINT UNSIGNED NOT NULL,
  bonus      DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  UNIQUE KEY uq_pkg_level (package_id, level)
) ENGINE=InnoDB;

-- ─── USERS ────────────────────────────────────────────────────
CREATE TABLE users (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username          VARCHAR(40)  NOT NULL UNIQUE,
  password_hash     VARCHAR(255) NOT NULL,
  role              ENUM('member','admin') NOT NULL DEFAULT 'member',
  package_id        INT UNSIGNED NULL,
  reg_code_id       INT UNSIGNED NULL,

  -- Binary tree placement
  sponsor_id        INT UNSIGNED NULL,
  binary_parent_id  INT UNSIGNED NULL,
  binary_position   ENUM('left','right') NULL,

  -- Pair counters (pairs_paid + pairs_flushed = total ever processed)
  left_count        INT UNSIGNED NOT NULL DEFAULT 0,
  right_count       INT UNSIGNED NOT NULL DEFAULT 0,
  pairs_paid        INT UNSIGNED NOT NULL DEFAULT 0,
  pairs_flushed     INT UNSIGNED NOT NULL DEFAULT 0,
  pairs_paid_today  INT UNSIGNED NOT NULL DEFAULT 0,  -- reset by midnight cron

  -- Profile
  full_name         VARCHAR(120) NULL,
  email             VARCHAR(120) NULL,
  mobile            VARCHAR(20)  NULL,
  gcash_number      VARCHAR(20)  NULL,
  address           TEXT         NULL,
  photo             VARCHAR(200) NULL,

  ewallet_balance   DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status            ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
  joined_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login        TIMESTAMP NULL,

  FOREIGN KEY (sponsor_id)       REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (binary_parent_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (package_id)       REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── REGISTRATION CODES ───────────────────────────────────────
CREATE TABLE reg_codes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(20)   NOT NULL UNIQUE,
  package_id  INT UNSIGNED  NOT NULL,
  price       DECIMAL(12,2) NOT NULL,
  status      ENUM('unused','used','expired') NOT NULL DEFAULT 'unused',
  used_by     INT UNSIGNED  NULL,
  created_by  INT UNSIGNED  NOT NULL,
  used_at     TIMESTAMP     NULL,
  expires_at  DATE          NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (package_id) REFERENCES packages(id),
  FOREIGN KEY (used_by)    REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tie reg_codes FKs back to users (added after users table)
ALTER TABLE users ADD FOREIGN KEY (reg_code_id) REFERENCES reg_codes(id) ON DELETE SET NULL;

-- ─── COMMISSIONS ──────────────────────────────────────────────
CREATE TABLE commissions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  type           ENUM('pairing','direct_referral','indirect_referral') NOT NULL,
  amount         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  source_user_id INT UNSIGNED  NULL,
  level          TINYINT UNSIGNED NULL,
  pairs_count    TINYINT UNSIGNED NULL,
  description    VARCHAR(255)  NULL,
  status         ENUM('credited','flushed') NOT NULL DEFAULT 'credited',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)        REFERENCES users(id),
  FOREIGN KEY (source_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── E-WALLET LEDGER ──────────────────────────────────────────
CREATE TABLE ewallet_ledger (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  type          ENUM('credit','debit') NOT NULL,
  amount        DECIMAL(12,2) NOT NULL,
  reference_id  INT UNSIGNED  NULL,
  ref_type      ENUM('commission','payout') NULL,
  balance_after DECIMAL(14,2) NOT NULL,
  note          VARCHAR(255)  NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ─── PAYOUT REQUESTS ──────────────────────────────────────────
CREATE TABLE payout_requests (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  amount        DECIMAL(12,2) NOT NULL,
  gcash_number  VARCHAR(20)   NOT NULL,
  status        ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  admin_note    TEXT          NULL,
  processed_by  INT UNSIGNED  NULL,
  requested_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at  TIMESTAMP NULL,
  FOREIGN KEY (user_id)      REFERENCES users(id),
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─── SYSTEM SETTINGS ──────────────────────────────────────────
CREATE TABLE settings (
  key_name   VARCHAR(80) NOT NULL PRIMARY KEY,
  value      TEXT        NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── INDEXES ──────────────────────────────────────────────────
ALTER TABLE users          ADD INDEX idx_sponsor       (sponsor_id);
ALTER TABLE users          ADD INDEX idx_binary_parent (binary_parent_id, binary_position);
ALTER TABLE users          ADD INDEX idx_role_status   (role, status);
ALTER TABLE commissions    ADD INDEX idx_user_type     (user_id, type, created_at);
ALTER TABLE commissions    ADD INDEX idx_source        (source_user_id);
ALTER TABLE commissions    ADD INDEX idx_status        (status, created_at);
ALTER TABLE reg_codes      ADD INDEX idx_status        (status);
ALTER TABLE ewallet_ledger ADD INDEX idx_user          (user_id, created_at);
ALTER TABLE payout_requests ADD INDEX idx_user_status  (user_id, status);
ALTER TABLE payout_requests ADD INDEX idx_status       (status, requested_at);

-- ─── SEED DATA ────────────────────────────────────────────────

-- Default admin account (password: Admin@1234 — CHANGE ON FIRST LOGIN)
INSERT INTO users (username, password_hash, role, status, full_name, email)
VALUES (
  'admin',
  '$2y$12$h3j0mO9NbtMyLg6EsC4M6eGy6buk0zanOgPmFBIgaI8V5/CUbaYqq', -- Admin@1234
  'admin',
  'active',
  'System Administrator',
  'admin@mlm.local'
);

-- Default starter package
INSERT INTO packages (name, entry_fee, pairing_bonus, daily_pair_cap, direct_ref_bonus)
VALUES ('Starter', 10000.00, 2000.00, 3, 500.00);

-- Indirect referral levels for starter package
INSERT INTO package_indirect_levels (package_id, level, bonus) VALUES
  (1, 1,  300.00),
  (1, 2,  200.00),
  (1, 3,  150.00),
  (1, 4,  100.00),
  (1, 5,  100.00),
  (1, 6,   50.00),
  (1, 7,   50.00),
  (1, 8,   50.00),
  (1, 9,   50.00),
  (1, 10,  50.00);

-- System settings
INSERT INTO settings (key_name, value) VALUES
  ('site_name',         'NetPro MLM'),
  ('site_tagline',      'Build Your Network. Grow Your Income.'),
  ('min_payout',        '500'),
  ('last_reset',        ''),
  ('maintenance_mode',  '0'),
  ('contact_email',     'support@mlm.local');

-- Demo registration code (package 1, price 10500)
-- Will be linked to admin (id=1) as created_by after admin is inserted
INSERT INTO reg_codes (code, package_id, price, created_by)
VALUES ('DEMO-STAR-TKIT', 1, 10500.00, 1);
