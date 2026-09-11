-- ================================================================
-- Medicine Shop Management System — database
--
-- Delete your old database first, then import this file. It creates
-- everything it needs (database, tables, sample data) on its own.
-- ================================================================

CREATE DATABASE IF NOT EXISTS medicine_shop;
USE medicine_shop;

-- ---------------------------------------------------------------
-- users  (admin, pharmacist, supplier, customer)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    role            ENUM('admin','pharmacist','supplier','customer') NOT NULL DEFAULT 'customer',
    phone           VARCHAR(20)   DEFAULT NULL,
    address         TEXT          DEFAULT NULL,
    profile_picture VARCHAR(255)  DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- categories  (liquid / solid)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    category_type ENUM('liquid','solid') NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- medicines
--   + expiry_date / is_damaged / damage_notes   (Admin feature)
--   + low_stock_threshold                       (Supplier feature)
--   + requires_prescription                     (Pharmacist feature)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medicines (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(200)   NOT NULL,
    category_id         INT            NOT NULL,
    vendor_name         VARCHAR(150)   NOT NULL,
    price               DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    availability        INT            NOT NULL DEFAULT 0,
    description         TEXT           DEFAULT NULL,
    image_path          VARCHAR(255)   DEFAULT NULL,
    expiry_date         DATE           DEFAULT NULL,
    is_damaged          TINYINT(1)     NOT NULL DEFAULT 0,
    damage_notes        VARCHAR(255)   DEFAULT NULL,
    low_stock_threshold INT            NOT NULL DEFAULT 10,
    requires_prescription TINYINT(1)   NOT NULL DEFAULT 0,
    created_at          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- cart
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    added_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_medicine (user_id, medicine_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- orders
--   + delivery_type / pickup_time   (Customer unique feature)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT            NOT NULL,
    total_amount     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    shipping_address TEXT           NOT NULL,
    status           ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    payment_method   VARCHAR(50)    NOT NULL,
    delivery_type    ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
    pickup_time      DATETIME       DEFAULT NULL,
    order_date       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- order_items
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT            NOT NULL,
    medicine_id INT            NOT NULL,
    quantity    INT            NOT NULL,
    unit_price  DECIMAL(10,2)  NOT NULL,
    FOREIGN KEY (order_id)    REFERENCES orders(id)    ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- payments
--   + status / verified_by / verified_at   (Pharmacist feature)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT            NOT NULL,
    amount         DECIMAL(10,2)  NOT NULL,
    payment_method VARCHAR(50)    NOT NULL,
    transaction_id VARCHAR(100)   DEFAULT NULL,
    status         ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    verified_by    INT            DEFAULT NULL,
    verified_at    DATETIME       DEFAULT NULL,
    payment_date   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)    REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- prescriptions  (Pharmacist CRUD + "Prescription verification")
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS prescriptions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT       NOT NULL,
    medicine_id    INT       NOT NULL,
    customer_id    INT       NOT NULL,
    pharmacist_id  INT       DEFAULT NULL,
    note           TEXT      DEFAULT NULL,
    status         ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    verified_at    DATETIME  DEFAULT NULL,
    created_at     DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)      REFERENCES orders(id)    ON DELETE CASCADE,
    FOREIGN KEY (medicine_id)   REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id)   REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (pharmacist_id) REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- medicine_limits  (Pharmacist CRUD + "Limit per person")
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medicine_limits (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id           INT NOT NULL UNIQUE,
    max_qty_per_customer  INT NOT NULL,
    set_by                INT DEFAULT NULL,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (set_by)      REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- stock_supplies  (Supplier CRUD; feeds low-stock alert + sale history)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_supplies (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id  INT NOT NULL,
    supplier_id  INT NOT NULL,
    quantity     INT NOT NULL,
    notes        VARCHAR(255) DEFAULT NULL,
    supply_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- store_ratings  (Customer submits; Supplier feature "Store rating")
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS store_ratings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    rating      TINYINT NOT NULL,
    comment     TEXT DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- reviews  (Customer submits; Admin feature "Review" moderation)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating      TINYINT NOT NULL,
    comment     TEXT DEFAULT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- feedback  (Customer submits; Admin feature "Feedback section")
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    subject     VARCHAR(150) NOT NULL,
    message     TEXT NOT NULL,
    admin_reply TEXT DEFAULT NULL,
    status      ENUM('new','reviewed','resolved') NOT NULL DEFAULT 'new',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- NOTE: The admin account is created automatically by config.php
-- the first time the app runs — you don't need to insert it here.
--   Admin: admin@medicine.com / admin123
-- Pharmacist and supplier accounts are created by signing up on
-- the register page (customer, pharmacist and supplier can all
-- self-register; admin cannot).
-- ---------------------------------------------------------------

-- ---------------------------------------------------------------
-- Seed: sample categories
-- ---------------------------------------------------------------
INSERT IGNORE INTO categories (id, name, category_type) VALUES
(1, 'Painkiller',      'solid'),
(2, 'Antibiotic',      'solid'),
(3, 'Antacid Syrup',   'liquid'),
(4, 'Cough Syrup',     'liquid'),
(5, 'Vitamin',         'solid'),
(6, 'Antiseptic',      'liquid');

-- ---------------------------------------------------------------
-- Seed: sample medicines
-- ---------------------------------------------------------------
INSERT IGNORE INTO medicines (id, name, category_id, vendor_name, price, availability, description, expiry_date, requires_prescription) VALUES
(1, 'Napa Extra 500mg',   1, 'Beximco Pharma',  12.00, 200, 'Effective painkiller and fever reducer.', '2027-06-30', 0),
(2, 'Azithromycin 250mg', 2, 'Square Pharma',   45.00, 100, 'Broad-spectrum antibiotic.',               '2027-01-15', 1),
(3, 'Antacid Plus Syrup', 3, 'Opsonin Pharma',  55.00,  80, 'Relieves acidity and heartburn.',          '2026-11-10', 0),
(4, 'Tussikof Syrup',     4, 'Renata Ltd',      70.00,  60, 'Cough suppressant syrup.',                 '2026-09-05', 0),
(5, 'Vitamin C 500mg',    5, 'ACI Limited',     20.00, 300, 'Immunity booster vitamin supplement.',     '2028-02-20', 0),
(6, 'Savlon Antiseptic',  6, 'Reckitt',         85.00,  50, 'Antiseptic liquid for wound cleaning.',    '2027-08-01', 0);

-- Demo thumbnail images (also backfills these rows if the app was set up before this update)
UPDATE medicines SET image_path = 'uploads/medicines/tablet-blister.svg'    WHERE id = 1;
UPDATE medicines SET image_path = 'uploads/medicines/capsule-bottle.svg'    WHERE id = 2;
UPDATE medicines SET image_path = 'uploads/medicines/syrup-bottle.svg'      WHERE id = 3;
UPDATE medicines SET image_path = 'uploads/medicines/syrup-bottle.svg'      WHERE id = 4;
UPDATE medicines SET image_path = 'uploads/medicines/capsule-bottle.svg'    WHERE id = 5;
UPDATE medicines SET image_path = 'uploads/medicines/antiseptic-bottle.svg' WHERE id = 6;
