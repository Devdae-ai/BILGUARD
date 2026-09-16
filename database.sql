CREATE DATABASE IF NOT EXISTS billguard;
USE billguard;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  currency VARCHAR(10) NOT NULL DEFAULT '₱',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#7dd3fc',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_category (user_id, name),
  KEY idx_categories_user (user_id),
  CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  merchant VARCHAR(255) NOT NULL,
  merchant_key VARCHAR(255) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  category VARCHAR(255) NOT NULL DEFAULT 'Uncategorized',
  category_id INT NULL,
  payment_status ENUM('paid','pending') NOT NULL DEFAULT 'paid',
  due_date DATE NULL,
  txn_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_transactions_user (user_id),
  KEY idx_transactions_merchant_key (merchant_key),
  KEY idx_transactions_category (category_id),
  CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_transactions_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS recurring_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  merchant VARCHAR(255) NOT NULL,
  merchant_key VARCHAR(255) NOT NULL,
  avg_amount DECIMAL(12,2) NOT NULL,
  interval_days INT NOT NULL,
  occurrences INT NOT NULL,
  last_txn_date DATE NOT NULL,
  next_expected_date DATE NOT NULL,
  status ENUM('active','dismissed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_merchant_key (user_id, merchant_key),
  KEY idx_recurring_user (user_id),
  CONSTRAINT fk_recurring_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
