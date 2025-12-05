# Fly.io Deployment Setup Guide

## Prerequisites for Fly.io Deployment

Your application is now on fly.io but needs a few critical configurations to work:

### 1. **Set Up PostgreSQL Database** (CRITICAL - This is why you're getting 500 errors)

You have two options:

#### Option A: Use Fly.io PostgreSQL (Recommended)
```bash
# Create a PostgreSQL database on fly.io
fly postgres create --name demolitiontraders-db

# Attach it to your app
fly postgres attach --app demolitiontraders demolitiontraders-db

# This automatically sets DATABASE_URL in your app's environment
```

#### Option B: Use External PostgreSQL
If you already have a PostgreSQL database elsewhere (e.g., AWS RDS, Railway):
```bash
# Set the DATABASE_URL environment variable
fly secrets set DATABASE_URL="postgresql://user:password@host:5432/dbname"
```

### 2. **Verify Environment Variables**

Check that your environment is set correctly:
```bash
# List all set variables
fly config show

# Or check specific vars
fly secrets list
```

### 3. **Check Deployment Logs**

Monitor your app's logs for errors:
```bash
# View real-time logs
fly logs

# View specific machine logs
fly logs --instance <instance-id>
```

### 4. **Database Schema Setup**

After database is attached, you need to set up the schema:

```bash
# Get the DATABASE_URL (if not using fly postgres attach)
fly secrets list

# Then run migration from your local machine or via SSH
fly ssh console

# Inside the console:
cd /var/www/html
php -r "require 'backend/config/database.php'; echo 'Database connected';"
```

### 5. **Session Storage**

The fly.toml configuration includes a persistent volume for session storage at `/var/lib/php/sessions`. This ensures sessions persist across deployments.

### 6. **Health Check**

Visit: `https://demolitiontraders.fly.dev/backend/api/health.php`

This endpoint shows:
- Environment variables status
- Database connection status
- File permissions
- PHP version

### 7. **Common Issues and Solutions**

**Issue**: "Unexpected token '<'" - HTML error page instead of JSON
- **Cause**: Server error (usually database connection failure)
- **Solution**: Check logs with `fly logs` and verify DATABASE_URL is set

**Issue**: 500 errors on all API endpoints
- **Cause**: Database not connected
- **Solution**: Attach PostgreSQL using Option A above

**Issue**: Sessions not persisting
- **Cause**: Multiple app instances with no shared session storage
- **Solution**: Fly.io volume mount handles this (already configured in fly.toml)

## Deployment Checklist

- [ ] PostgreSQL database created/attached
- [ ] DATABASE_URL environment variable set (verify with `fly secrets list`)
- [ ] Database schema initialized (if new database)
- [ ] Health check endpoint returns 200
- [ ] Cart API returns JSON (not HTML error)
- [ ] Products load successfully
- [ ] Sessions persist across requests

## Monitoring

Keep an eye on:
```bash
fly status
fly logs
fly apps info
```
