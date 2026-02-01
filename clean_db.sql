SET FOREIGN_KEY_CHECKS = 0;

-- Drop Views/Tables (Handle conflicts where View might be a Table)
DROP VIEW IF EXISTS low_stock_products;
DROP TABLE IF EXISTS low_stock_products;
DROP VIEW IF EXISTS order_summary;
DROP TABLE IF EXISTS order_summary;
DROP VIEW IF EXISTS product_details;
DROP TABLE IF EXISTS product_details;

-- Drop Tables
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS product_attributes;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS migrations;
DROP TABLE IF EXISTS personal_access_tokens;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS cache;
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS job_batches;
DROP TABLE IF EXISTS jobs;

SET FOREIGN_KEY_CHECKS = 1;
