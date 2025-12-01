# ⚡ Hướng Dẫn Nhanh - Sửa Lỗi API

## 🎯 TÓM TẮT

Lỗi "Failed to fetch" đã được sửa! Làm theo 3 bước sau để áp dụng:

## 📋 3 BƯỚC ĐƠN GIẢN

### Bước 1: Clear Browser Cache
```
1. Nhấn Ctrl + Shift + Delete
2. Chọn "Cached images and files"
3. Click "Clear data"
```

### Bước 2: Hard Reload
```
Nhấn Ctrl + Shift + R
(hoặc Ctrl + F5)
```

### Bước 3: Test
Mở một trong các pages:
- http://localhost/demolitiontraders/frontend/index.php
- http://localhost/demolitiontraders/frontend/shop.php
- http://localhost/demolitiontraders/frontend/test-api.php (để test API)

## 🔍 Kiểm Tra Nhanh

### Console không còn lỗi?
1. Nhấn F12
2. Vào tab Console
3. Không còn thấy "Failed to fetch" = ✅ Thành công!

### Vẫn còn lỗi?

**Option 1: Test API trực tiếp**
```
Mở: http://localhost/demolitiontraders/frontend/test-api.php
Click "Test Health Endpoint"
Nếu thấy {"status": "ok"} = API hoạt động!
```

**Option 2: Thử Incognito Mode**
```
Ctrl + Shift + N (Chrome)
hoặc
Ctrl + Shift + P (Firefox)
```

**Option 3: Check XAMPP**
```
XAMPP Control Panel:
- Apache: [✓] Running
- MySQL: [✓] Running
```

## ✨ Những Gì Đã Được Fix

1. ✅ **API Helper mới** (`frontend/assets/js/api-helper.js`)
   - Tự động retry khi lỗi
   - Error handling tốt hơn
   - Logging để debug

2. ✅ **CORS Headers** đã được cập nhật
   - Support credentials
   - Cache preflight requests
   - Thêm các headers cần thiết

3. ✅ **Health Check Endpoint**
   - Test API có hoạt động không
   - URL: `/backend/api/index.php?request=health`

4. ✅ **Test Page**
   - UI để test tất cả endpoints
   - URL: `/frontend/test-api.php`

## 🎮 Test Commands (Optional)

**Test từ PowerShell:**
```powershell
# Test API
curl http://localhost/demolitiontraders/backend/api/index.php?request=health

# Test products
curl "http://localhost/demolitiontraders/backend/api/index.php?request=products&limit=1"
```

**Test từ Browser Console (F12):**
```javascript
// Health check
await window.checkApiHealth()

// Get products
await window.apiGet('/api/index.php', { request: 'products', limit: 5 })
```

## 🆘 Vẫn Cần Giúp?

### Lỗi phổ biến:

**1. "Failed to fetch" vẫn xuất hiện**
```
→ Clear cache chưa đủ
→ Thử Incognito mode
→ Restart browser
```

**2. API trả về HTML thay vì JSON**
```
→ Check PHP errors trong backend/logs/php_errors.log
→ Ensure XAMPP Apache đang chạy
```

**3. CORS errors**
```
→ Đã fix trong backend/api/index.php
→ Clear cache và reload
```

## 🎊 Done!

Sau khi clear cache và reload, website nên hoạt động bình thường:
- ✅ Login form hoạt động
- ✅ Shop page load products
- ✅ Cart functions hoạt động
- ✅ Wishlist hoạt động
- ✅ Opening hours hiển thị

**Nếu có bất kỳ câu hỏi nào, check file `API-FIX-COMPLETE.md` để biết thêm chi tiết!**
