-- Truncate orders table and reset auto-increment
TRUNCATE TABLE orders;
TRUNCATE TABLE order_items;
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE order_items AUTO_INCREMENT = 1;

ALTER TABLE cart_items ADD COLUMN size VARCHAR(10) DEFAULT NULL; 