#!/usr/bin/env python3
"""
Direct import from MySQL to PostgreSQL
Install: pip install psycopg2-binary mysql-connector-python
Run: python database/import-mysql-to-pg.py
"""

import psycopg2
import mysql.connector
import sys

print("=== MySQL to PostgreSQL Import ===\n")

# Connect to MySQL
print("Connecting to MySQL...")
mysql_conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='demolitiontraders'
)
mysql_cursor = mysql_conn.cursor(dictionary=True)
print("✓ MySQL connected\n")

# Connect to PostgreSQL
print("Connecting to PostgreSQL...")
pg_conn_string = "postgresql://demolition_user:y0XviqttYTB1D18x0IVrKtJZP6Ck4Hz6@dpg-d4n486a4d50c73f5ksng-a.oregon-postgres.render.com:5432/demolitiontraders"
pg_conn = psycopg2.connect(pg_conn_string)
pg_cursor = pg_conn.cursor()
print("✓ PostgreSQL connected\n")

# Get products from MySQL
print("Fetching products from MySQL...")
mysql_cursor.execute("""
    SELECT id, sku, name, slug, description, short_description,
           price, cost_price, compare_at_price, category_id,
           stock_quantity, min_stock_level, weight, dimensions,
           condition_type, is_featured, is_active, show_collection_options,
           created_at, updated_at
    FROM products 
    WHERE is_active = 1
    ORDER BY id
    LIMIT 1000
""")

products = mysql_cursor.fetchall()
print(f"✓ Found {len(products)} products\n")

# Import to PostgreSQL
print("Importing to PostgreSQL...")
imported = 0
skipped = 0

for product in products:
    try:
        # Clean text fields
        for key in ['name', 'slug', 'description', 'short_description']:
            if product[key]:
                # Remove non-ASCII characters
                product[key] = ''.join(char for char in product[key] if ord(char) < 128 or char in '\r\n')
        
        pg_cursor.execute("""
            INSERT INTO products (
                id, sku, name, slug, description, short_description,
                price, cost_price, compare_at_price, category_id,
                stock_quantity, min_stock_level, weight, dimensions,
                condition_type, is_featured, is_active, show_collection_options,
                created_at, updated_at
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
            ) ON CONFLICT (sku) DO NOTHING
        """, (
            product['id'],
            product['sku'],
            product['name'],
            product['slug'],
            product['description'],
            product['short_description'],
            product['price'],
            product['cost_price'],
            product['compare_at_price'],
            product['category_id'],
            product['stock_quantity'] or 0,
            product['min_stock_level'] or 0,
            product['weight'],
            product['dimensions'],
            product['condition_type'],
            bool(product['is_featured']),
            bool(product['is_active']),
            bool(product['show_collection_options']),
            product['created_at'],
            product['updated_at']
        ))
        
        pg_conn.commit()
        imported += 1
        
        if imported % 50 == 0:
            print(f"  Imported {imported}/{len(products)}...")
            
    except Exception as e:
        skipped += 1
        # print(f"  Skipped {product['sku']}: {e}")
        pg_conn.rollback()
        continue

print(f"\n✓ Import complete!")
print(f"  Imported: {imported}")
print(f"  Skipped: {skipped}")

# Verify
pg_cursor.execute("SELECT COUNT(*) FROM products WHERE is_active = TRUE")
count = pg_cursor.fetchone()[0]
print(f"\nTotal active products in database: {count}")

# Import images
print("\n=== Importing Product Images ===")
mysql_cursor.execute("""
    SELECT pi.* 
    FROM product_images pi
    JOIN products p ON pi.product_id = p.id
    WHERE p.is_active = 1
    ORDER BY pi.product_id, pi.display_order
    LIMIT 5000
""")

images = mysql_cursor.fetchall()
print(f"✓ Found {len(images)} images\n")

img_imported = 0
for image in images:
    try:
        # Clean text
        if image['alt_text']:
            image['alt_text'] = ''.join(char for char in image['alt_text'] if ord(char) < 128)
        
        pg_cursor.execute("""
            INSERT INTO product_images (
                product_id, image_url, alt_text, display_order, is_primary, created_at
            ) VALUES (%s, %s, %s, %s, %s, %s)
            ON CONFLICT DO NOTHING
        """, (
            image['product_id'],
            image['image_url'],
            image['alt_text'],
            image['display_order'] or 0,
            bool(image['is_primary']),
            image['created_at']
        ))
        pg_conn.commit()
        img_imported += 1
        
        if img_imported % 100 == 0:
            print(f"  Imported {img_imported}/{len(images)} images...")
            
    except Exception as e:
        pg_conn.rollback()
        continue

print(f"\n✓ Images imported: {img_imported}")

# Close connections
pg_cursor.close()
pg_conn.close()
mysql_cursor.close()
mysql_conn.close()

print("\n✓ Done! Visit https://demolitiontraders.onrender.com")
