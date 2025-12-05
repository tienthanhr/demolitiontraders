# Báo cáo Đánh giá Bảo mật - Demolition Traders

**Ngày đánh giá:** 2025-12-03
**Người đánh giá:** Jules (AI Software Engineer)
**Ngày cập nhật (Giai đoạn 3):** 2025-12-04

## Tóm tắt Tổng quan

Trang web Demolition Traders được xây dựng trên nền tảng PHP tùy chỉnh. Các giai đoạn trước đã khắc phục các lỗ hổng ứng dụng nghiêm trọng (CSRF, XSS, hardening session, kiểm soát tải lên). Giai đoạn 3 củng cố bảo mật ở cấp độ máy chủ và hạ tầng (cấu hình Apache, security header, rate limiting) dựa trên kết quả quét OWASP ZAP. Ứng dụng và hạ tầng hiện tuân thủ các biện pháp bảo vệ cốt lõi cho cả phía client và máy chủ.

---

## Tình trạng sau khi Khắc phục (Giai đoạn 3)

Các thay đổi đã khóa chặt cả lớp ứng dụng lẫn lớp hạ tầng: request quản trị yêu cầu CSRF token và phiên an toàn, dữ liệu phản hồi được mã hóa để tránh XSS, tải lên tệp được xác thực MIME/kích thước, Apache chặn `/server-status` và gửi đầy đủ security header, còn API được giới hạn tốc độ cơ bản để giảm lạm dụng.

---

## Danh sách Lỗ hổng (Đã khắc phục)

1. **Cross-Site Request Forgery (CSRF) trong Khu vực Quản trị** — *ĐÃ KHẮC PHỤC (Giai đoạn 2)*
   * **Vị trí mã:** `backend/api/admin/csrf_middleware.php` được include vào tất cả endpoint quản trị trong `backend/api/admin/`.
   * **Biện pháp:** Kiểm tra đăng nhập admin và xác thực header `X-CSRF-Token` cho mọi request `POST/PUT/DELETE`.

2. **Thiếu mã hóa đầu ra dẫn đến Cross-Site Scripting (XSS)** — *ĐÃ KHẮC PHỤC (Giai đoạn 2)*
   * **Vị trí mã:** Hàm `escape_output()` trong `backend/utils/security.php`; áp dụng trong các controller chính (sản phẩm, danh mục, đơn hàng).
   * **Biện pháp:** Mã hóa đệ quy mọi dữ liệu trả về trước khi render ra client.

3. **Session cookie thiếu cờ bảo mật** — *ĐÃ KHẮC PHỤC (Giai đoạn 2)*
   * **Vị trí cấu hình:** `backend/core/bootstrap.php`, được include đầu mọi request API.
   * **Biện pháp:** Thiết lập `HttpOnly`, `Secure` (khi có HTTPS), `SameSite=Lax`, và ép dùng cookie cho session.

4. **Điểm yếu trong chức năng tải lên tệp** — *ĐÃ KHẮC PHỤC (Giai đoạn 2)*
   * **Vị trí mã:** `backend/controllers/ProductController.php` trong luồng upload ảnh sản phẩm.
   * **Biện pháp:** Kiểm tra `is_uploaded_file`, giới hạn kích thước qua `MAX_UPLOAD_SIZE`, xác thực MIME bằng `finfo_file`, và kiểm tra phần mở rộng cho whitelist.

5. **Truy cập công khai `/server-status`** — *ĐÃ KHẮC PHỤC (Giai đoạn 3)*
   * **Vị trí cấu hình:** Quy tắc chặn trong file `.htaccess` gốc.
   * **Biện pháp:** `RewriteRule ^server-status/?$ - [F,L]` trả về 403 cho mọi truy cập.

6. **Thiếu các security header quan trọng** — *ĐÃ KHẮC PHỤC (Giai đoạn 3)*
   * **Vị trí cấu hình:** Phần header trong `.htaccess` (khối `<IfModule mod_headers.c>`).
   * **Biện pháp:** Thêm `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Strict-Transport-Security`, `Permissions-Policy`.

7. **Thiếu cơ chế rate limiting cho API** — *ĐÃ KHẮC PHỤC (Giai đoạn 3)*
   * **Vị trí mã:** Middleware `backend/middleware/rate_limit.php` được include từ `backend/api/index.php`.
   * **Biện pháp:** Giới hạn 60 request/phút/IP (bỏ qua admin), trả về 429 và header `Retry-After` khi vượt ngưỡng.

---

## Checklist Bảo mật để Duy trì trong Tương lai

* **Rà soát Phụ thuộc (Dependencies):** Thường xuyên quét các thư viện của bên thứ ba.
* **Mã hóa tất cả Đầu ra:** Đảm bảo mọi dữ liệu động đều được mã hóa.
* **Sử dụng Prepared Statements:** Tiếp tục sử dụng cho tất cả các truy vấn cơ sở dữ liệu.
* **Thực thi Kiểm soát Truy cập:** Luôn kiểm tra quyền cho các endpoint mới.
* **Xác thực Đầu vào:** Xác thực tất cả dữ liệu từ người dùng.
* **Quản lý Bí mật:** Không hardcode thông tin nhạy cảm.
* **Cập nhật Thường xuyên:** Giữ cho máy chủ, PHP, và các thư viện được cập nhật.
* **Quét Bảo mật Định kỳ:** Chạy các công cụ như OWASP ZAP định kỳ sau khi có các thay đổi lớn.
