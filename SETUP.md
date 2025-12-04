# Demolition Traders - Setup Instructions

## Quick Start Guide

### Step 1: Configure Environment

1. The `.env` file has been created with default values
2. Update these important settings:

```
# Database (if different from defaults)
DB_NAME=demolitiontraders
DB_USER=root
DB_PASS=

# IdealPOS Credentials (IMPORTANT!)
IDEALPOS_API_KEY=<your-idealpos-api-key>
IDEALPOS_STORE_ID=<your-idealpos-store-id>
```

### Step 2: Import Database

1. Start XAMPP and ensure MySQL is running
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Click "Import" tab
4. Select file: `database/schema.sql`
5. Click "Go" to import

OR use command line:
```bash
cd C:\xampp\mysql\bin
mysql -u root -p < C:\xampp\htdocs\demolitiontraders\database\schema.sql
```

### Step 3: Create Admin User

After importing the database, create your first admin user by running the script from the project root:
```bash
php backend/scripts/create_admin.php <email> <password> <first_name> <last_name>
```
See `CREATE_ADMIN_USER.md` for more details.

### Step 4: Test the Website

1. Make sure Apache and MySQL are running in XAMPP
2. Open browser: http://localhost/demolitiontraders
3. Login with the admin credentials you just created: http://localhost/demolitiontraders/admin

### Step 4: Setup IdealPOS Integration

1. Login to your IdealPOS dashboard
2. Go to Settings > API
3. Generate new API Key
4. Copy the API Key and Store ID
5. Update them in `.env` file
6. Test sync: Go to admin panel > IdealPOS > Sync Now

### Step 5: Setup Automatic Sync (Optional)

#### Using Windows Task Scheduler:

1. Open Task Scheduler
2. Create New Task:
   - Name: "IdealPOS Sync"
   - Trigger: Every 5 minutes
   - Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\demolitiontraders\backend\cron\sync-idealpos.php`

## API Endpoints

Base URL: `http://localhost/demolitiontraders/api`

### Products
- `GET /products` - List all products
- `GET /products/{id}` - Get product details
- `POST /products` - Create product (admin)
- `PUT /products/{id}` - Update product (admin)
- `DELETE /products/{id}` - Delete product (admin)

### Cart
- `GET /cart/get` - Get cart contents
- `POST /cart/add` - Add to cart
- `PUT /cart/update` - Update cart item
- `DELETE /cart/remove/{id}` - Remove item
- `DELETE /cart/clear` - Clear cart

### Orders
- `GET /orders` - Get user orders
- `GET /orders/{id}` - Get order details
- `POST /orders` - Create order

### Auth
- `POST /auth/login` - Login
- `POST /auth/register` - Register
- `POST /auth/logout` - Logout
- `GET /auth/me` - Get current user

### IdealPOS
- `GET /idealpos/sync-products` - Sync products from POS
- `GET /idealpos/sync-inventory` - Sync inventory
- `POST /idealpos/push-order/{id}` - Push order to POS
- `GET /idealpos/status` - Get sync status

## Troubleshooting

### Database Connection Error
- Check MySQL is running in XAMPP
- Verify credentials in `.env`
- Make sure database `demolitiontraders` exists

### API 404 Errors
- Check `.htaccess` file exists in root
- Verify Apache `mod_rewrite` is enabled
- Check Apache `AllowOverride` is set to `All`

### IdealPOS Sync Not Working
- Verify API credentials in `.env`
- Check IdealPOS API status
- View logs in `logs/cron-sync.log`
- Test connection in admin panel

### Images Not Loading
- Check `uploads` folder exists
- Verify folder permissions (755)
- Check Apache is serving static files

## File Structure

```
demolitiontraders/
├── backend/
│   ├── api/              # API endpoints
│   ├── config/           # Configuration
│   ├── controllers/      # Business logic
│   ├── services/         # External services
│   └── cron/             # Scheduled tasks
├── frontend/
│   ├── assets/           # CSS, JS, images
│   ├── components/       # Reusable components
│   └── pages/            # Website pages
├── database/
│   └── schema.sql        # Database structure
├── logs/                 # Log files
├── uploads/              # Product images
├── .env                  # Environment config
├── .htaccess            # Apache config
└── README.md            # This file
```

## Default Admin Account

**IMPORTANT:** Change these credentials after first login!

- Email: admin@demolitiontraders.co.nz
- Password: admin123

## Support

For issues or questions:
- Check logs in `logs/` folder
- Review error messages in browser console
- Check Apache error log: `C:\xampp\apache\logs\error.log`

## Next Steps

1. ✅ Import database
2. ✅ Configure .env
3. ✅ Test website
4. ✅ Setup IdealPOS credentials
5. ✅ Test sync
6. ✅ Change admin password
7. ✅ Upload product images
8. ✅ Setup automatic sync cron job

## Production Deployment

Before going live:
1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Change all default passwords
4. Enable HTTPS in `.htaccess`
5. Setup proper email SMTP
6. Configure payment gateway
7. Setup regular database backups
8. Test all IdealPOS sync operations
