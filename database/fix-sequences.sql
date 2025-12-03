-- Fix PostgreSQL sequences after data import
-- This resets all auto-increment sequences to the correct values

-- Fix orders table sequence
SELECT setval('orders_id_seq', (SELECT COALESCE(MAX(id), 1) FROM orders));

-- Fix order_items table sequence
SELECT setval('order_items_id_seq', (SELECT COALESCE(MAX(id), 1) FROM order_items));

-- Fix users table sequence
SELECT setval('users_id_seq', (SELECT COALESCE(MAX(id), 1) FROM users));

-- Fix products table sequence
SELECT setval('products_id_seq', (SELECT COALESCE(MAX(id), 1) FROM products));

-- Fix product_images table sequence
SELECT setval('product_images_id_seq', (SELECT COALESCE(MAX(id), 1) FROM product_images));

-- Fix categories table sequence
SELECT setval('categories_id_seq', (SELECT COALESCE(MAX(id), 1) FROM categories));

-- Fix cart_items table sequence
SELECT setval('cart_items_id_seq', (SELECT COALESCE(MAX(id), 1) FROM cart_items));

-- Fix wishlist_items table sequence
SELECT setval('wishlist_items_id_seq', (SELECT COALESCE(MAX(id), 1) FROM wishlist_items));

-- Fix addresses table sequence
SELECT setval('addresses_id_seq', (SELECT COALESCE(MAX(id), 1) FROM addresses));

-- Fix password_reset_tokens table sequence (if it has one)
-- SELECT setval('password_reset_tokens_id_seq', (SELECT COALESCE(MAX(id), 1) FROM password_reset_tokens));

-- Verify sequences
SELECT 'orders: ' || last_value FROM orders_id_seq;
SELECT 'order_items: ' || last_value FROM order_items_id_seq;
SELECT 'users: ' || last_value FROM users_id_seq;
SELECT 'products: ' || last_value FROM products_id_seq;
SELECT 'product_images: ' || last_value FROM product_images_id_seq;
SELECT 'categories: ' || last_value FROM categories_id_seq;
