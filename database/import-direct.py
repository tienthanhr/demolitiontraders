#!/usr/bin/env python3
"""
Direct import to Render PostgreSQL using psycopg2
Install: pip install psycopg2-binary
Run: python database/import-direct.py
"""

import psycopg2
import sys

# Render PostgreSQL connection
conn_string = "postgresql://demolition_user:y0XviqttYTB1D18x0IVrKtJZP6Ck4Hz6@dpg-d4n486a4d50c73f5ksng-a.oregon-postgres.render.com:5432/demolitiontraders"

print("=== Import Products to Render PostgreSQL ===\n")

try:
    # Connect
    print("Connecting to Render PostgreSQL...")
    conn = psycopg2.connect(conn_string)
    cursor = conn.cursor()
    print("✓ Connected\n")
    
    # Read SQL file with proper encoding
    print("Reading SQL file...")
    with open('database/products-data.sql', 'r', encoding='utf-8', errors='ignore') as f:
        sql = f.read()
    
    # Remove BOM if present
    if sql.startswith('\ufeff'):
        sql = sql[1:]
    
    print(f"✓ Read {len(sql)} characters\n")
    
    # Execute entire SQL as one transaction
    print("Importing data (this may take a minute)...")
    cursor.execute(sql)
    conn.commit()
    
    print("✓ Import completed\n")
    
    # Verify
    print("=== Verification ===")
    cursor.execute("SELECT COUNT(*) FROM products WHERE is_active = TRUE")
    product_count = cursor.fetchone()[0]
    print(f"Active products: {product_count}")
    
    cursor.execute("SELECT COUNT(*) FROM product_images")
    image_count = cursor.fetchone()[0]
    print(f"Product images: {image_count}")
    
    cursor.execute("""
        SELECT c.name, COUNT(p.id) as count 
        FROM categories c 
        LEFT JOIN products p ON p.category_id = c.id 
        GROUP BY c.id, c.name 
        ORDER BY count DESC 
        LIMIT 5
    """)
    
    print("\nTop 5 categories:")
    for row in cursor.fetchall():
        print(f"  - {row[0]}: {row[1]} products")
    
    print("\n✓ Success! Visit https://demolitiontraders.onrender.com")
    
    cursor.close()
    conn.close()
    
except Exception as e:
    print(f"\n✗ Error: {e}")
    sys.exit(1)
