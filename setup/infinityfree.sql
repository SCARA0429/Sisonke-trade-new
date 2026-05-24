CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  role ENUM('user','buyer','seller','admin') NOT NULL,
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

-- Demo data
-- All demo accounts use the password: Password123
SET @demo_password_hash = '$2y$10$RZXu.X.VgUq.QhuKDg7jdu6yzSRHYaYZmNdSZ01JHttZgvBlx7jTS';

INSERT INTO users (email, password_hash, full_name, role, is_active)
VALUES
  ('admin@sisonke.test', @demo_password_hash, 'Nandi Mokoena', 'admin', 1),
  ('seller@sisonke.test', @demo_password_hash, 'Thabo Dlamini', 'seller', 1),
  ('buyer@sisonke.test', @demo_password_hash, 'Lerato Nkosi', 'buyer', 1)
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash),
  full_name = VALUES(full_name),
  role = VALUES(role),
  is_active = VALUES(is_active);

SET @admin_id = (SELECT user_id FROM users WHERE email = 'admin@sisonke.test' LIMIT 1);
SET @seller_id = (SELECT user_id FROM users WHERE email = 'seller@sisonke.test' LIMIT 1);
SET @buyer_id = (SELECT user_id FROM users WHERE email = 'buyer@sisonke.test' LIMIT 1);

INSERT INTO admins (admin_id, permission_level, can_resolve_disputes, can_manage_users)
VALUES (@admin_id, 'super_admin', 1, 1)
ON DUPLICATE KEY UPDATE
  permission_level = VALUES(permission_level),
  can_resolve_disputes = VALUES(can_resolve_disputes),
  can_manage_users = VALUES(can_manage_users);

INSERT INTO sellers (seller_id, business_name, verification_status, reputation_score, total_sales)
VALUES (@seller_id, 'Bhekizizwe Traders', 'verified', 4.80, 0)
ON DUPLICATE KEY UPDATE
  business_name = VALUES(business_name),
  verification_status = VALUES(verification_status),
  reputation_score = VALUES(reputation_score);

INSERT INTO buyers (buyer_id, delivery_address, total_purchases, total_confirmations)
VALUES (@buyer_id, '321 Vilakazi Street, Soweto', 0, 0)
ON DUPLICATE KEY UPDATE
  delivery_address = VALUES(delivery_address);

INSERT INTO products (seller_id, name, description, category, unit_price, quantity_available, image_url, is_active)
SELECT @seller_id, '10KG Maize Meal', 'Premium white maize meal for household and spaza shop bulk buying.', 'Groceries', 105.00, 180, 'https://images.unsplash.com/photo-1551754655-cd27e38d2076?w=800&q=80&auto=format&fit=crop', 1
WHERE NOT EXISTS (
  SELECT 1 FROM products WHERE seller_id = @seller_id AND name = '10KG Maize Meal'
);

INSERT INTO products (seller_id, name, description, category, unit_price, quantity_available, image_url, is_active)
SELECT @seller_id, 'School Shoes', 'Durable black school shoes for primary learners and uniform resellers.', 'School goods', 165.00, 80, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80&auto=format&fit=crop', 1
WHERE NOT EXISTS (
  SELECT 1 FROM products WHERE seller_id = @seller_id AND name = 'School Shoes'
);

INSERT INTO products (seller_id, name, description, category, unit_price, quantity_available, image_url, is_active)
SELECT @seller_id, 'Grocery Mix', 'Community pantry pack with oil, sugar, tea, beans, rice, and soap.', 'Household essentials', 520.00, 45, 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&q=80&auto=format&fit=crop', 1
WHERE NOT EXISTS (
  SELECT 1 FROM products WHERE seller_id = @seller_id AND name = 'Grocery Mix'
);

SET @maize_id = (SELECT product_id FROM products WHERE seller_id = @seller_id AND name = '10KG Maize Meal' LIMIT 1);
SET @shoes_id = (SELECT product_id FROM products WHERE seller_id = @seller_id AND name = 'School Shoes' LIMIT 1);
SET @grocery_id = (SELECT product_id FROM products WHERE seller_id = @seller_id AND name = 'Grocery Mix' LIMIT 1);

INSERT INTO group_buy_campaigns
  (seller_id, product_id, campaign_price, min_participants, max_participants, target_quantity, target_amount, deadline, status)
SELECT @seller_id, @maize_id, 89.00, 10, 120, 80, 7120.00, DATE_ADD(NOW(), INTERVAL 21 DAY), 'active'
WHERE NOT EXISTS (
  SELECT 1 FROM group_buy_campaigns WHERE seller_id = @seller_id AND product_id = @maize_id
);

INSERT INTO group_buy_campaigns
  (seller_id, product_id, campaign_price, min_participants, max_participants, target_quantity, target_amount, deadline, status)
SELECT @seller_id, @shoes_id, 120.00, 8, 60, 40, 4800.00, DATE_ADD(NOW(), INTERVAL 14 DAY), 'active'
WHERE NOT EXISTS (
  SELECT 1 FROM group_buy_campaigns WHERE seller_id = @seller_id AND product_id = @shoes_id
);

INSERT INTO group_buy_campaigns
  (seller_id, product_id, campaign_price, min_participants, max_participants, target_quantity, target_amount, deadline, status)
SELECT @seller_id, @grocery_id, 450.00, 5, 35, 25, 11250.00, DATE_ADD(NOW(), INTERVAL 10 DAY), 'active'
WHERE NOT EXISTS (
  SELECT 1 FROM group_buy_campaigns WHERE seller_id = @seller_id AND product_id = @grocery_id
);

SET @maize_campaign_id = (
  SELECT campaign_id FROM group_buy_campaigns
  WHERE seller_id = @seller_id AND product_id = @maize_id
  LIMIT 1
);

INSERT INTO escrow_payments (campaign_id, total_amount, status, confirmations_required)
SELECT @maize_campaign_id, 267.00, 'held', 1
WHERE NOT EXISTS (
  SELECT 1 FROM campaign_participants WHERE buyer_id = @buyer_id AND campaign_id = @maize_campaign_id
);

SET @demo_escrow_id = (
  SELECT escrow_id FROM escrow_payments
  WHERE campaign_id = @maize_campaign_id AND total_amount = 267.00
  ORDER BY escrow_id DESC
  LIMIT 1
);

INSERT INTO campaign_participants (campaign_id, buyer_id, quantity, amount_paid)
SELECT @maize_campaign_id, @buyer_id, 3, 267.00
WHERE NOT EXISTS (
  SELECT 1 FROM campaign_participants WHERE buyer_id = @buyer_id AND campaign_id = @maize_campaign_id
);

SET @demo_participant_id = (
  SELECT participant_id FROM campaign_participants
  WHERE buyer_id = @buyer_id AND campaign_id = @maize_campaign_id
  LIMIT 1
);

UPDATE group_buy_campaigns
SET current_quantity = GREATEST(current_quantity, 3)
WHERE campaign_id = @maize_campaign_id;

INSERT INTO transactions
  (escrow_id, participant_id, buyer_id, seller_id, amount, payment_method, status, reference_number)
SELECT @demo_escrow_id, @demo_participant_id, @buyer_id, @seller_id, 267.00, 'payfast_sandbox', 'completed', 'ST-DEMO-000001'
WHERE NOT EXISTS (
  SELECT 1 FROM transactions WHERE reference_number = 'ST-DEMO-000001'
);

INSERT INTO disputes (participant_id, campaign_id, buyer_id, seller_id, reason, details, status)
SELECT
  @demo_participant_id,
  @maize_campaign_id,
  @buyer_id,
  @seller_id,
  'Pickup window missed',
  'Demo case for admin moderation and escrow review screenshots.',
  'reviewing'
WHERE NOT EXISTS (
  SELECT 1 FROM disputes WHERE participant_id = @demo_participant_id
);
