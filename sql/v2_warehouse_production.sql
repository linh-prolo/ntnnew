DROP TABLE IF EXISTS oqc_delivery_items;
DROP TABLE IF EXISTS oqc_deliveries;
DROP TABLE IF EXISTS production_items;
DROP TABLE IF EXISTS production_orders;
DROP TABLE IF EXISTS iqc_receipt_items;
DROP TABLE IF EXISTS iqc_receipts;
DROP TABLE IF EXISTS wa_transactions;
DROP TABLE IF EXISTS wa_items;
DROP TABLE IF EXISTS wa_categories;

-- ── IQC (Kho Sản Xuất - Đầu vào) ─────────────────────────────────────
CREATE TABLE iqc_receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_no VARCHAR(30) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  received_date DATE NOT NULL,
  received_by INT NOT NULL,
  note TEXT,
  status ENUM('open','in_production','done') NOT NULL DEFAULT 'open',
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (received_by) REFERENCES users(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE iqc_receipt_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_id INT NOT NULL,
  product_code_id INT NOT NULL,
  qty DECIMAL(12,2) NOT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'cái',
  note TEXT,
  FOREIGN KEY (receipt_id) REFERENCES iqc_receipts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_code_id) REFERENCES product_codes(id)
);

-- ── PRODUCTION (Sản Xuất) ─────────────────────────────────────────────
CREATE TABLE production_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(30) NOT NULL UNIQUE,
  iqc_receipt_id INT NOT NULL,
  status ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  note TEXT,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (iqc_receipt_id) REFERENCES iqc_receipts(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE production_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  iqc_item_id INT NOT NULL,
  qty_total DECIMAL(12,2) NOT NULL,
  qty_done DECIMAL(12,2) NOT NULL DEFAULT 0,
  qty_error DECIMAL(12,2) NOT NULL DEFAULT 0,
  stage VARCHAR(100),
  status ENUM('in_progress','done','error') NOT NULL DEFAULT 'in_progress',
  note TEXT,
  updated_by INT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (iqc_item_id) REFERENCES iqc_receipt_items(id),
  FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- ── OQC (Kho Thành Phẩm - Đầu ra) ────────────────────────────────────
CREATE TABLE oqc_deliveries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  delivery_no VARCHAR(30) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  delivery_date DATE NOT NULL,
  sender_name VARCHAR(100),
  vehicle_plate VARCHAR(20),
  driver_name VARCHAR(100),
  note TEXT,
  status ENUM('draft','delivered') NOT NULL DEFAULT 'draft',
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE oqc_delivery_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  delivery_id INT NOT NULL,
  production_item_id INT NOT NULL,
  qty_deliver DECIMAL(12,2) NOT NULL,
  type ENUM('done','error') NOT NULL DEFAULT 'done',
  note TEXT,
  FOREIGN KEY (delivery_id) REFERENCES oqc_deliveries(id) ON DELETE CASCADE,
  FOREIGN KEY (production_item_id) REFERENCES production_items(id)
);

-- ── WAREHOUSE ADMIN (Kho Vật Tư) ──────────────────────────────────────
CREATE TABLE wa_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO wa_categories (name) VALUES
  ('Máy móc'),('Thiết bị'),('Văn phòng phẩm'),('Vật tư tiêu hao'),('Khác');

CREATE TABLE wa_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  item_code VARCHAR(50) NOT NULL UNIQUE,
  item_name VARCHAR(200) NOT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'cái',
  min_stock DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES wa_categories(id)
);

CREATE TABLE wa_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  type ENUM('import','export') NOT NULL,
  qty DECIMAL(12,2) NOT NULL,
  ref_no VARCHAR(50),
  note TEXT,
  transacted_by INT NOT NULL,
  transacted_at DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES wa_items(id),
  FOREIGN KEY (transacted_by) REFERENCES users(id)
);
