"""
Export MySQL database and convert to PostgreSQL format
Then upload directly to Render PostgreSQL
"""
import mysql.connector
import psycopg2
import sys

# MySQL connection
mysql_conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='demolitiontraders'
)

# PostgreSQL connection (Render)
pg_conn_string = "postgresql://demolition_user:y0XviqttYTB1D18x0IVrKtJZP6Ck4Hz6@dpg-d4n486a4d50c73f5ksng-a.oregon-postgres.render.com:5432/demolitiontraders"
pg_conn = psycopg2.connect(pg_conn_string)
pg_conn.autocommit = False

mysql_cursor = mysql_conn.cursor(dictionary=True)
pg_cursor = pg_conn.cursor()

# Tables to export (in order of dependencies)
tables = [
    'users',
    'categories', 
    'products',
    'orders',
    'order_items',
    'contact_submissions',
    'wanted_listings',
    'sell_to_us_submissions'
]

print("🔄 Starting data migration from MySQL to PostgreSQL...")
print(f"📊 Tables to migrate: {', '.join(tables)}\n")

try:
    for table in tables:
        print(f"📦 Processing {table}...", end=' ')
        
        # Get data from MySQL
        mysql_cursor.execute(f"SELECT * FROM {table}")
        rows = mysql_cursor.fetchall()
        
        if not rows:
            print(f"⚠️  No data")
            continue
            
        # Get column names
        columns = list(rows[0].keys())
        
        # Clear existing data in PostgreSQL
        pg_cursor.execute(f'DELETE FROM {table}')
        
        # Insert data
        inserted = 0
        for row in rows:
            # Prepare values
            values = []
            for col in columns:
                val = row[col]
                # Convert MySQL types to PostgreSQL
                if isinstance(val, (bytes, bytearray)):
                    val = val.decode('utf-8', errors='ignore')
                values.append(val)
            
            # Build INSERT query
            placeholders = ', '.join(['%s'] * len(columns))
            col_names = ', '.join([f'"{col}"' for col in columns])
            query = f'INSERT INTO {table} ({col_names}) VALUES ({placeholders})'
            
            try:
                pg_cursor.execute(query, values)
                inserted += 1
            except Exception as e:
                print(f"\n⚠️  Error inserting row: {e}")
                print(f"   Row data: {dict(zip(columns, values))}")
                continue
        
        print(f"✓ Inserted {inserted} rows")
        
    # Commit all changes
    pg_conn.commit()
    print("\n✅ Migration completed successfully!")
    print("🌐 Visit: https://demolitiontraders.onrender.com")
    
except Exception as e:
    pg_conn.rollback()
    print(f"\n❌ Migration failed: {e}")
    sys.exit(1)
    
finally:
    mysql_cursor.close()
    mysql_conn.close()
    pg_cursor.close()
    pg_conn.close()
