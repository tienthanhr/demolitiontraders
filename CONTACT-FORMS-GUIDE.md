# Contact Forms Setup Guide

## Overview
This guide explains the new contact forms functionality for:
1. **Contact Us** - General enquiries
2. **Sell to Us** - Customers wanting to sell items
3. **Wanted Listings** - Customers looking for specific items

## Features

### Contact Us
- Simple contact form with subject selection
- Sends email to admin (info@demolitiontraders.co.nz)
- Stores submissions in database for tracking
- Reply-to field set to customer's email

### Sell to Us
- Form with photo upload (up to 5 photos)
- Captures item details, condition, quantity
- Sends email to admin with all details
- Stores submissions with photo paths

### Wanted Listings
**Special Features:**
- If user is logged in:
  - Automatically searches for matching products
  - Adds matching products to user's wishlist
  - Shows count of matched items in success message
- If user opts for notifications:
  - Sends confirmation email to user
  - Stores listing for future matching
- Sends notification to admin with all details

## Installation Steps

### 1. Import Database Tables
Run the import script to create necessary tables:

```
http://localhost/demolitiontraders/import-contact-forms-tables.php
```

This creates 4 tables:
- `contact_submissions`
- `sell_to_us_submissions`
- `wanted_listings`
- `wanted_listing_matches`

### 2. Configure Email Settings
Check and update email configuration in:
```
backend/config/email.php
```

Current settings:
- SMTP: Gmail
- From: nguyenthanh123426@gmail.com
- Admin email: info@demolitiontraders.co.nz (update this!)
- Dev mode: false (set to true for testing)

**Important:** Update the admin email address to receive notifications!

### 3. Test Each Form

#### Test Contact Form
1. Visit: `http://localhost/demolitiontraders/frontend/contact.php`
2. Fill in the form
3. Check:
   - Toast notification appears
   - Email received at admin address
   - Record in `contact_submissions` table

#### Test Sell to Us
1. Visit: `http://localhost/demolitiontraders/frontend/sell-to-us.php`
2. Fill in the form and upload photos
3. Check:
   - Files uploaded to `uploads/sell-to-us/`
   - Email received with details
   - Record in `sell_to_us_submissions` table

#### Test Wanted Listing
1. Visit: `http://localhost/demolitiontraders/frontend/wanted-listing.php`
2. Test as guest (logged out):
   - Fill in form, check "notify" checkbox
   - Verify confirmation email received
3. Test as logged-in user:
   - Login first
   - Submit wanted listing
   - Check wishlist for matched products
   - Success message should show count of matches

## API Endpoints

### Contact Form
```
POST /demolitiontraders/backend/api/contact/submit.php
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "021234567",
  "subject": "General Enquiry",
  "message": "Your message here"
}
```

### Sell to Us
```
POST /demolitiontraders/backend/api/sell-to-us/submit.php
Content-Type: multipart/form-data

Fields:
- name
- email
- phone (required)
- location
- description
- condition
- quantity
- photos[] (files)
```

### Wanted Listing
```
POST /demolitiontraders/backend/api/wanted-listing/submit.php
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "021234567",
  "category": "timber",
  "description": "Looking for rimu flooring",
  "quantity": "50 square meters",
  "notify": "on"
}
```

## Email Service Methods

New methods added to `EmailService.php`:

1. **sendContactFormEmail($data)** - Admin notification for contact forms
2. **sendSellToUsEmail($data)** - Admin notification for sell submissions
3. **sendWantedListingEmail($data)** - Admin notification for wanted listings
4. **sendWantedListingConfirmationEmail($email, $name, $description)** - User confirmation

## Wanted Listing Product Matching

The system automatically searches for matching products when a wanted listing is submitted:

### Search Logic
1. Splits description into search terms
2. Searches product names and descriptions
3. Filters by category if provided
4. Limits to 10 best matches
5. Only matches active products with stock

### Wishlist Auto-Add
- Only works for logged-in users
- Matched products automatically added to wishlist
- Uses `INSERT IGNORE` to prevent duplicates
- Success message shows count of matches

### Future Enhancements
You can create a cron job to:
- Periodically check new products against active wanted listings
- Send email notifications when matches are found
- Mark wanted listings as "matched"

## Admin Dashboard Integration

To view submissions in admin dashboard, add these pages:
1. View contact submissions
2. View sell-to-us submissions
3. View wanted listings
4. Mark listings as replied/resolved

Example query for admin:
```sql
-- Get recent contact submissions
SELECT * FROM contact_submissions 
WHERE status = 'new' 
ORDER BY created_at DESC;

-- Get recent sell-to-us
SELECT * FROM sell_to_us_submissions 
WHERE status = 'new' 
ORDER BY created_at DESC;

-- Get active wanted listings
SELECT * FROM wanted_listings 
WHERE status = 'active' 
ORDER BY created_at DESC;
```

## Troubleshooting

### Emails Not Sending
1. Check `backend/config/email.php` settings
2. Verify SMTP credentials are correct
3. Check error logs in `backend/logs/`
4. Enable dev_mode to test without real SMTP

### Form Submission Errors
1. Check browser console for JavaScript errors
2. Verify API endpoints are accessible
3. Check database tables exist
4. Review PHP error logs

### Photos Not Uploading
1. Check `uploads/sell-to-us/` directory exists
2. Verify directory permissions (755)
3. Check PHP upload_max_filesize setting
4. Review file size limits

## Security Notes

- All form inputs are sanitized
- Email validation on server-side
- File upload restricted to sell-to-us form only
- CSRF protection should be added for production
- Rate limiting recommended for production

## Production Checklist

- [ ] Update admin email address in config
- [ ] Set dev_mode = false
- [ ] Enable HTTPS
- [ ] Add CSRF protection
- [ ] Implement rate limiting
- [ ] Add captcha to prevent spam
- [ ] Review file upload security
- [ ] Set up email backup/logging
- [ ] Configure error monitoring
- [ ] Add admin dashboard views

## Support

For issues or questions:
- Check error logs in `backend/logs/`
- Review EmailService.php for email debugging
- Test forms individually
- Verify database tables created correctly
