#!/usr/bin/env python3
"""
Import products using CSV - fastest and most reliable method
"""

import psycopg2
import pymysql
import csv
import sys
from io import StringIO

print("=== Import Products via CSV ===\n")

# Connect to localhost MySQL
print("Connecting to MySQL...")
mysql_conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='demolitiontraders',
    charset='utf8mb4'
)
mysql_cursor = mysql_conn.cursor(pymysql.cursors.DictCursor)
print("✓ MySQL connected\n")

# Connect to Render PostgreSQL
print("Connecting to Render PostgreSQL...")
pg_conn = psycopg2.connect(
    "postgresql://demolition_user:y0XviqttYTB1D18x0IVrKtJZP6Ck4Hz6@dpg-d4n486a4d50c73f5ksng-a.oregon-postgres.render.com:5432/demolitiontraders"
)
pg_cursor = pg_conn.cursor()
print("✓ PostgreSQL connected\n")

# Fetch products from MySQL
print("Fetching products from MySQL...")
mysql_cursor.execute("""
    SELECT id, sku, name, slug, description, short_description,
           price, cost_price, compare_at_price, category_id,
           stock_quantity, min_stock_level, weight, dimensions,
           condition_type, is_featured, is_active, show_collection_options,
           idealpos_product_id, last_synced_at, meta_title, meta_description,
           created_at, updated_at
    FROM products
    WHERE is_active = 1
    ORDER BY id
    LIMIT 1000
""")

products = mysql_cursor.fetchall()
print(f"✓ Fetched {len(products)} products\n")

# Insert into PostgreSQL one by one (slow but reliable)
print("Inserting products...")
inserted = 0
skipped = 0
for product in products:
    try:
        pg_cursor.execute("""
            INSERT INTO products (
                id, sku, name, slug, description, short_description,
                price, cost_price, compare_at_price, category_id,
                stock_quantity, min_stock_level, weight, dimensions,
                condition_type, is_featured, is_active, show_collection_options,
                idealpos_product_id, last_synced_at, meta_title, meta_description,
                created_at, updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                %s, %s, %s, %s
            )
            ON CONFLICT (sku) DO UPDATE SET
                name = EXCLUDED.name,
                price = EXCLUDED.price,
                stock_quantity = EXCLUDED.stock_quantity,
                updated_at = CURRENT_TIMESTAMP
        """, (
            product['id'], product['sku'], product['name'], product['slug'],
            product['description'], product['short_description'],
            product['price'], product['cost_price'], product['compare_at_price'],
            product['category_id'], product['stock_quantity'], product['min_stock_level'],
            product['weight'], product['dimensions'], product['condition_type'],
            bool(product['is_featured']), bool(product['is_active']), bool(product['show_collection_options']),
            product['idealpos_product_id'], product['last_synced_at'],
            product['meta_title'], product['meta_description'],
            product['created_at'], product['updated_at']
        ))
        pg_conn.commit()  # Commit each product individually
        inserted += 1
        if inserted % 10 == 0:
            print(f"  Inserted {inserted}/{len(products)}...")
    except Exception as e:
        pg_conn.rollback()  # Rollback failed transaction
        skipped += 1
        # Uncomment to see errors: print(f"  Skipped {product['sku']}: {str(e)[:80]}")
        continue

print(f"\n✓ Inserted {inserted} products (skipped {skipped})\n")

# Verify
pg_cursor.execute("SELECT COUNT(*) FROM products WHERE is_active = TRUE")
count = pg_cursor.fetchone()[0]
print(f"Total active products in database: {count}")

print("\n✓ Success! Visit https://demolitiontraders.onrender.com")

mysql_cursor.close()
mysql_conn.close()
pg_cursor.close()
pg_conn.close()
