-- 001: core tables for AM Tuff Tiles
-- Rules for migration files: every statement ends with ";" at the end of a line,
-- no ";" inside text values, and each statement is safe to run twice (IF NOT EXISTS / INSERT IGNORE).
-- All prices and totals are whole numbers in paisa (PKR 1,250 = 125000).

-- Customers and staff. portal_role decides who sees the admin panel.
CREATE TABLE IF NOT EXISTS contacts (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    phone         VARCHAR(30)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    portal_role   ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    address       VARCHAR(255) NULL,
    city          VARCHAR(100) NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contacts_email (email),
    KEY idx_contacts_role (portal_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product groups shown in the shop filter bar (admin can add more later).
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products. size and material are plain text so the shop can offer them as filters.
-- Hide a product with is_active = 0 instead of deleting it, so old orders stay intact.
CREATE TABLE IF NOT EXISTS products (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id       INT UNSIGNED NOT NULL,
    name              VARCHAR(190) NOT NULL,
    slug              VARCHAR(160) NOT NULL,
    short_description VARCHAR(255) NULL,
    description       TEXT         NULL,
    price_paisa       INT UNSIGNED NOT NULL,
    size              VARCHAR(80)  NULL,
    material          VARCHAR(80)  NULL,
    stock_qty         INT UNSIGNED NOT NULL DEFAULT 0,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    is_featured       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    KEY idx_products_shop (is_active, category_id, price_paisa),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Several images per product. file_path is relative to public/uploads/, e.g. products/abc123.jpg
CREATE TABLE IF NOT EXISTS product_images (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    file_path  VARCHAR(255) NOT NULL,
    alt_text   VARCHAR(190) NULL,
    is_primary TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order INT          NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_images_product (product_id, sort_order),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders. Customer and delivery details are copied here at checkout so later profile
-- changes never alter an old order. Cash on delivery is the only payment method for now.
CREATE TABLE IF NOT EXISTS orders (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number     VARCHAR(24)  NOT NULL,
    contact_id       INT UNSIGNED NOT NULL,
    customer_name    VARCHAR(120) NOT NULL,
    customer_phone   VARCHAR(30)  NOT NULL,
    customer_email   VARCHAR(190) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city    VARCHAR(100) NOT NULL,
    notes            VARCHAR(500) NULL,
    status           ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    payment_method   ENUM('cod') NOT NULL DEFAULT 'cod',
    subtotal_paisa   INT UNSIGNED NOT NULL,
    delivery_paisa   INT UNSIGNED NOT NULL DEFAULT 0,
    total_paisa      INT UNSIGNED NOT NULL,
    placed_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at     DATETIME     NULL,
    completed_at     DATETIME     NULL,
    cancelled_at     DATETIME     NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_number (order_number),
    KEY idx_orders_contact (contact_id),
    KEY idx_orders_status (status, placed_at),
    KEY idx_orders_completed (completed_at),
    CONSTRAINT fk_orders_contact FOREIGN KEY (contact_id)
        REFERENCES contacts (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lines of an order. Name and price are copied from the product at the time of purchase.
CREATE TABLE IF NOT EXISTS order_items (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id         INT UNSIGNED NOT NULL,
    product_id       INT UNSIGNED NULL,
    product_name     VARCHAR(190) NOT NULL,
    unit_price_paisa INT UNSIGNED NOT NULL,
    quantity         INT UNSIGNED NOT NULL,
    line_total_paisa INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
        REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset links. Only a SHA-256 hash of the emailed token is stored.
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    contact_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_resets_token (token_hash),
    KEY idx_password_resets_contact (contact_id),
    CONSTRAINT fk_password_resets_contact FOREIGN KEY (contact_id)
        REFERENCES contacts (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shop-wide settings the admin can change (contact details, delivery charge).
CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(80) NOT NULL,
    setting_value TEXT        NULL,
    updated_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
