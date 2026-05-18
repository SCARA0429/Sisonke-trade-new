CREATE DATABASE IF NOT EXISTS sisonke_trade
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sisonke_trade;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  role ENUM('buyer','seller','admin') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buyers (
  buyer_id INT PRIMARY KEY,
  delivery_address VARCHAR(255) NOT NULL,
  total_purchases INT DEFAULT 0,
  total_confirmations INT DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT buyers_user_fk FOREIGN KEY (buyer_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sellers (
  seller_id INT PRIMARY KEY,
  business_name VARCHAR(100) NOT NULL,
  verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
  reputation_score DECIMAL(3,2) DEFAULT 5.00,
  total_sales INT DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT sellers_user_fk FOREIGN KEY (seller_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
  admin_id INT PRIMARY KEY,
  permission_level ENUM('super_admin','moderator','support') NOT NULL,
  can_resolve_disputes TINYINT(1) DEFAULT 0,
  can_manage_users TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT admins_user_fk FOREIGN KEY (admin_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  category VARCHAR(50) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity_available INT NOT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (seller_id),
  CONSTRAINT products_seller_fk FOREIGN KEY (seller_id)
    REFERENCES sellers (seller_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS group_buy_campaigns (
  campaign_id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  product_id INT NOT NULL,
  campaign_price DECIMAL(10,2) NOT NULL,
  min_participants INT NOT NULL,
  max_participants INT NOT NULL,
  target_quantity INT NOT NULL,
  target_amount DECIMAL(10,2) NOT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  current_quantity INT DEFAULT 0,
  deadline DATETIME NOT NULL,
  status ENUM('active','fulfilled','closed','cancelled') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (seller_id),
  INDEX (product_id),
  CONSTRAINT campaigns_seller_fk FOREIGN KEY (seller_id)
    REFERENCES sellers (seller_id) ON DELETE CASCADE,
  CONSTRAINT campaigns_product_fk FOREIGN KEY (product_id)
    REFERENCES products (product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_participants (
  participant_id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  buyer_id INT NOT NULL,
  quantity INT NOT NULL,
  amount_paid DECIMAL(10,2) NOT NULL,
  has_confirmed_delivery TINYINT(1) DEFAULT 0,
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmed_at TIMESTAMP NULL DEFAULT NULL,
  INDEX (campaign_id),
  INDEX (buyer_id),
  CONSTRAINT participants_campaign_fk FOREIGN KEY (campaign_id)
    REFERENCES group_buy_campaigns (campaign_id) ON DELETE CASCADE,
  CONSTRAINT participants_buyer_fk FOREIGN KEY (buyer_id)
    REFERENCES buyers (buyer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS escrow_payments (
  escrow_id INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT DEFAULT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('held','released','refunded','disputed') DEFAULT 'held',
  confirmations_received INT DEFAULT 0,
  confirmations_required INT NOT NULL,
  held_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  released_at TIMESTAMP NULL DEFAULT NULL,
  refunded_at TIMESTAMP NULL DEFAULT NULL,
  INDEX (campaign_id),
  CONSTRAINT escrow_campaign_fk FOREIGN KEY (campaign_id)
    REFERENCES group_buy_campaigns (campaign_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  escrow_id INT NOT NULL,
  participant_id INT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  status ENUM('pending','completed','failed') DEFAULT 'pending',
  reference_number VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (escrow_id),
  INDEX (participant_id),
  INDEX (buyer_id),
  INDEX (seller_id),
  CONSTRAINT transactions_escrow_fk FOREIGN KEY (escrow_id)
    REFERENCES escrow_payments (escrow_id) ON DELETE CASCADE,
  CONSTRAINT transactions_participant_fk FOREIGN KEY (participant_id)
    REFERENCES campaign_participants (participant_id) ON DELETE SET NULL,
  CONSTRAINT transactions_buyer_fk FOREIGN KEY (buyer_id)
    REFERENCES buyers (buyer_id) ON DELETE CASCADE,
  CONSTRAINT transactions_seller_fk FOREIGN KEY (seller_id)
    REFERENCES sellers (seller_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disputes (
  dispute_id INT AUTO_INCREMENT PRIMARY KEY,
  participant_id INT NULL,
  campaign_id INT NULL,
  buyer_id INT NULL,
  seller_id INT NULL,
  reason VARCHAR(255) NOT NULL,
  details TEXT NULL,
  status ENUM('open','reviewing','resolved','rejected') DEFAULT 'open',
  resolution_note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL DEFAULT NULL,
  INDEX (participant_id),
  INDEX (campaign_id),
  INDEX (buyer_id),
  INDEX (seller_id),
  CONSTRAINT disputes_participant_fk FOREIGN KEY (participant_id)
    REFERENCES campaign_participants (participant_id) ON DELETE SET NULL,
  CONSTRAINT disputes_campaign_fk FOREIGN KEY (campaign_id)
    REFERENCES group_buy_campaigns (campaign_id) ON DELETE SET NULL,
  CONSTRAINT disputes_buyer_fk FOREIGN KEY (buyer_id)
    REFERENCES buyers (buyer_id) ON DELETE SET NULL,
  CONSTRAINT disputes_seller_fk FOREIGN KEY (seller_id)
    REFERENCES sellers (seller_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
