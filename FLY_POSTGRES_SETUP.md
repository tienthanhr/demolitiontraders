# Fly.io PostgreSQL Database Setup Guide

## Database Information
- **App Name**: demolitiontraders-db
- **Database Type**: PostgreSQL (Beta)
- **Region**: Sydney (syd)
- **Hostname**: postgresql://demolitiontraders-db.flycast
- **Status**: ✅ Already deployed on Fly.io

## Step 1: Get Database Credentials

1. Go to https://fly.io/dashboard/organizations/personal/apps/demolitiontraders-db
2. Click on **Settings** tab
3. Look for database credentials (username, password, database name)
4. Or use Fly CLI: `flyctl postgres connect -a demolitiontraders-db`

Typical format:
```
postgresql://username:password@demolitiontraders-db.flycast/databasename
```

## Step 2: Connect to Database

### Option A: Using Fly CLI (Recommended)
```bash
flyctl postgres connect -a demolitiontraders-db
```

### Option B: Using psql directly
```bash
psql postgresql://username:password@demolitiontraders-db.flycast/databasename
```

### Option C: Using pgAdmin or DBeaver
- Host: `demolitiontraders-db.flycast`
- Port: 5432
- Database: (from credentials)
- Username: (from credentials)
- Password: (from credentials)

## Step 3: Import Database Schema

### Method 1: Via Fly Console (Easiest)
```bash
# SSH into app
flyctl ssh console -a demolitiontraders

# Inside the app console, connect to postgres and import:
psql postgresql://username:password@demolitiontraders-db.flycast/databasename < demolitiontraders_pg_fly.sql
```

### Method 2: Direct Import from Local Machine
```bash
# On your local machine, assuming PostgreSQL client is installed:
psql postgresql://username:password@demolitiontraders-db.flycast/databasename < demolitiontraders_pg_fly.sql
```

### Method 3: Using a SQL tool
1. Connect to database using DBeaver, pgAdmin, or similar tool
2. Open file: `demolitiontraders_pg_fly.sql`
3. Execute the SQL script

## Step 4: Update Application Configuration

Update `backend/config/database.php`:

```php
// Detect if running on Fly.io
$isFlying = !empty(getenv('FLY_APP_NAME'));

if ($isFlying) {
    // Fly.io PostgreSQL
    $config = [
        'driver' => 'postgres',
        'host' => getenv('DATABASE_URL') ?: 'demolitiontraders-db.flycast',
        'port' => 5432,
        'database' => getenv('DB_NAME') ?: 'demolitiontraders',
        'username' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4'
    ];
} else {
    // Local MySQL
    $config = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'demolitiontraders',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
}
```

Or set Fly secrets:
```bash
flyctl secrets set \
  DATABASE_URL="postgresql://user:password@demolitiontraders-db.flycast/dbname" \
  --app demolitiontraders
```

## Step 5: Test Connection

1. Go to https://demolitiontraders.fly.dev/
2. Check that:
   - ✅ Database queries work (products load)
   - ✅ Cart operations work
   - ✅ No "database connection" errors
3. Check logs: `flyctl logs --app demolitiontraders`

## Troubleshooting

### "Connection refused" error
- Database might not be running or accessible from your app
- Check: `flyctl postgres attach demolitiontraders-db --app demolitiontraders`

### "Authentication failed" error
- Wrong username/password
- Check credentials in Fly dashboard

### "Database does not exist" error
- Schema not imported yet
- Run import script from Step 3

### "Foreign key constraint" error
- Import order matters in PostgreSQL
- Ensure all tables are created before foreign keys

## Files Included

- **demolitiontraders_mysql.sql** - Original MySQL dump
- **demolitiontraders_pg_fly.sql** - Converted PostgreSQL dump
- **convert_to_postgres.py** - Conversion script (if you need to re-export)

## Next Steps

1. ✅ Get database credentials
2. ✅ Import schema using one of the methods above
3. ✅ Update app configuration
4. ✅ Test database connection
5. ✅ Deploy app updates with `git push`

## References

- Fly PostgreSQL Docs: https://fly.io/docs/postgres/
- PostgreSQL Connection String: https://www.postgresql.org/docs/current/libpq-connect.html#LIBPQ-CONNSTRING
