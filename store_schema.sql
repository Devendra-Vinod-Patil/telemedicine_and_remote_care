-- Pharmacy Store tables for the existing `medi` database.
-- Import this into the same database you use in `database.php`.

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT DEFAULT NULL,
  `user_role` VARCHAR(20) DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `payment_id` VARCHAR(100) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `product_id` INT DEFAULT NULL,
  `subtotal` DECIMAL(10,2) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_name` VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  CONSTRAINT `fk_order_items_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
