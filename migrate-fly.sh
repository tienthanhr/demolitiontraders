#!/bin/bash
# Migration script to import schema to PostgreSQL on fly.io

echo "Importing database schema to fly.io PostgreSQL..."

# Copy the SQL file to the app
flyctl sftp shell << 'EOF'
put demolitiontraders_pg_clean.sql /app/demolitiontraders_pg_clean.sql
exit
EOF

# Connect and run the import
flyctl ssh console -a demolitiontraders << 'EOF'
psql $DATABASE_URL < /app/demolitiontraders_pg_clean.sql
echo "Schema import completed!"
exit
EOF

echo "✓ Migration completed!"
