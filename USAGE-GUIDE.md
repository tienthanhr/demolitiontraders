# 🎯 HƯỚNG DẪN SỬ DỤNG WEBSITE ĐẦY ĐỦ

## 📍 CÁC TRANG CHÍNH

### 🏠 **TRANG KHÁCH HÀNG:**

1. **Trang chủ:**
   ```
   http://localhost/demolitiontraders/
   hoặc
   http://localhost/demolitiontraders/frontend/index.php
   ```

2. **Shop (Xem sản phẩm):**
   ```
   http://localhost/demolitiontraders/frontend/shop.php
   ```

3. **Giỏ hàng:**
   ```
   http://localhost/demolitiontraders/frontend/cart.php
   ```

4. **Thanh toán:**
   ```
   http://localhost/demolitiontraders/frontend/checkout.php
   ```

---

### 👨‍💼 **TRANG ADMIN:**

1. **Admin Login:**
   ```
   http://localhost/demolitiontraders/frontend/admin-login.php
   ```
   **Credentials:**
   - Email: `admin@demolitiontraders.co.nz`
   - Password: `admin123`

2. **Admin Dashboard:**
   ```
   http://localhost/demolitiontraders/frontend/admin-dashboard.php
   ```
   (Tự động redirect sau khi login)

---

## 🛒 **HƯỚNG DẪN MUA HÀNG (TEST CHECKOUT)**

### **Bước 1: Xem sản phẩm**
1. Vào: `http://localhost/demolitiontraders/frontend/shop.php`
2. Bạn sẽ thấy 4 sản phẩm mẫu:
   - ACP Board 2400 x 1200 White Gloss - $145.00
   - Grooved Plywood Cladding - $89.00
   - Recycled Rimu Door - $450.00
   - Aluminium Window - $280.00

### **Bước 2: Thêm vào giỏ**
1. Click nút **"Add to Cart"** trên bất kỳ sản phẩm nào
2. Sẽ có thông báo: "Product added to cart!"
3. Số lượng trên icon giỏ hàng (góc phải header) sẽ tăng

### **Bước 3: Xem giỏ hàng**
1. Click vào icon giỏ hàng ở header
2. Hoặc vào: `http://localhost/demolitiontraders/frontend/cart.php`
3. Bạn sẽ thấy:
   - Danh sách sản phẩm trong giỏ
   - Có thể tăng/giảm số lượng
   - Có thể xóa sản phẩm
   - Tổng tiền (Subtotal + Tax 15%)

### **Bước 4: Thanh toán**
1. Click **"Proceed to Checkout"**
2. Điền form:
   - **Billing Address:** Thông tin người mua
   - **Shipping Address:** Địa chỉ giao hàng (hoặc tick "Same as billing")
   - **Payment Method:** Chọn Credit Card / Bank Transfer / Cash
   - **Order Notes:** Ghi chú (optional)
3. Click **"Place Order"**
4. Sẽ có popup hiển thị:
   ```
   ✓ Order placed successfully!
   
   Order Number: ORD-20251120-ABC123
   Total: $166.75
   
   Thank you for your order!
   ```

### **Bước 5: Kiểm tra order đã sync với IdealPOS**
1. Order tự động được tạo trong database
2. Order sẽ được sync lên IdealPOS (nếu đã cấu hình API)
3. Admin có thể xem trong dashboard

---

## 👨‍💼 **HƯỚNG DẪN SỬ DỤNG ADMIN**

### **1. Login Admin:**
```
http://localhost/demolitiontraders/frontend/admin-login.php
```
Credentials mặc định:
- Email: `admin@demolitiontraders.co.nz`
- Password: `admin123`

### **2. Admin Dashboard:**
Sau khi login, bạn sẽ thấy:

#### **📊 Statistics (Thống kê):**
- Total Products: Tổng số sản phẩm
- Total Orders: Tổng đơn hàng
- Total Users: Tổng khách hàng
- Last POS Sync: Lần sync IdealPOS cuối

#### **🔌 IdealPOS Integration:**
- **Sync Products:** Click để đồng bộ sản phẩm từ POS → Website
- **Sync Inventory:** Click để cập nhật stock từ POS
- **View Sync Logs:** Xem lịch sử sync

#### **📦 Recent Products:**
Bảng hiển thị 5 sản phẩm mới nhất với:
- SKU
- Name
- Price
- Stock
- Condition (New/Recycled)
- IdealPOS Product ID

#### **📋 Recent Orders:**
Danh sách đơn hàng với:
- Order Number
- Customer Email
- Total Amount
- Status (Pending/Processing/Completed)
- POS Sync Status

---

## 🔌 **HƯỚNG DẪN TÍCH HỢP IDEALPOS**

### **Cấu hình API Credentials:**

1. **Mở file `.env`:**
   ```
   C:\xampp\htdocs\demolitiontraders\.env
   ```

2. **Thêm thông tin IdealPOS:**
   ```env
   IDEALPOS_API_URL=https://api.idealpos.com/v1
   IDEALPOS_API_KEY=your-actual-api-key-here
   IDEALPOS_STORE_ID=your-store-id-here
   IDEALPOS_SYNC_ENABLED=true
   ```

3. **Lấy API Key từ IdealPOS:**
   - Login vào IdealPOS dashboard
   - Vào Settings > API
   - Generate API Key
   - Copy API Key và Store ID

### **Test Sync:**

1. **Login Admin Dashboard:**
   ```
   http://localhost/demolitiontraders/frontend/admin-dashboard.php
   ```

2. **Click "Sync Products":**
   - Hệ thống sẽ gọi API IdealPOS
   - Lấy danh sách products
   - Import vào database
   - Hiển thị kết quả: "✓ 50 products synced"

3. **Click "Sync Inventory":**
   - Cập nhật stock levels từ POS
   - Đảm bảo website luôn có stock chính xác

### **Automatic Sync (Cron Job):**

**Setup trong Windows Task Scheduler:**
1. Mở Task Scheduler
2. Create Task:
   - Name: "IdealPOS Sync"
   - Trigger: Every 5 minutes
   - Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\demolitiontraders\backend\cron\sync-idealpos.php`

**Hoặc test thủ công:**
```bash
cd C:\xampp\php
php.exe C:\xampp\htdocs\demolitiontraders\backend\cron\sync-idealpos.php
```

---

## 🧪 **TEST API ENDPOINTS**

### **Products API:**
```
http://localhost/demolitiontraders/api/products
```
Trả về JSON danh sách sản phẩm

### **Categories API:**
```
http://localhost/demolitiontraders/api/categories
```
Trả về JSON danh mục

### **Cart API (Add product):**
```javascript
POST http://localhost/demolitiontraders/api/cart/add
Body: {"product_id": 1, "quantity": 1}
```

### **Create Order:**
```javascript
POST http://localhost/demolitiontraders/api/orders
Body: {order data}
```

---

## 🎬 **DEMO WORKFLOW HOÀN CHỈNH:**

### **1. Khách hàng mua hàng:**
```
Shop → Add to Cart → View Cart → Checkout → Place Order
```

### **2. Order tự động sync lên IdealPOS:**
```
Order Created → API Call to IdealPOS → Order appears in POS
```

### **3. Admin quản lý:**
```
Login Admin → View Dashboard → Sync Products → View Orders
```

### **4. Cron tự động sync (mỗi 5 phút):**
```
Products: POS → Website
Inventory: POS → Website  
Orders: Website → POS
```

---

## 📊 **DATABASE STRUCTURE:**

Xem danh sách tables và data:
```
http://localhost/demolitiontraders/test-db.php
```

---

## 🐛 **TROUBLESHOOTING:**

### **Nếu không thấy sản phẩm:**
```
http://localhost/demolitiontraders/import-database.php
```

### **Nếu API không hoạt động:**
1. Check Apache mod_rewrite enabled
2. Check .htaccess file exists
3. Restart Apache

### **Nếu IdealPOS sync fail:**
1. Check API credentials in .env
2. Check logs: `logs/cron-sync.log`
3. Test connection trong Admin Dashboard

---

## 🎯 **NEXT STEPS:**

✅ Import database (đã xong)
✅ Test website homepage
✅ Test add to cart
✅ Test checkout process
✅ Login admin
✅ Configure IdealPOS credentials
✅ Test sync products
✅ Setup cron job (optional)

---

**🎉 WEBSITE CỦA BẠN ĐÃ SẴN SÀNG!**
