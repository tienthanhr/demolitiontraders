# Báo cáo Đánh giá Bảo mật - Demolition Traders

**Ngày đánh giá:** 2025-12-03  
**Người đánh giá:** Jules (AI Software Engineer)  
**Ngày cập nhật (Giai đoạn 3):** 2025-12-04

## Tóm tắt Tổng quan

Trang web Demolition Traders được xây dựng trên nền tảng PHP tùy chỉnh. Các giai đoạn trước đã khắc phục các lỗ hổng ứng dụng nghiêm trọng (CSRF, XSS, hardening session, v.v.). Giai đoạn 3 tập trung củng cố bảo mật ở cấp độ máy chủ và cơ sở hạ tầng dựa trên kết quả quét của OWASP ZAP.

**Bản cập nhật Giai đoạn 3 này xác nhận rằng các vấn đề về cấu hình máy chủ, security header, và thiếu rate limiting đã được giải quyết, trong khi tất cả bản vá ứng dụng ở Giai đoạn 2 vẫn được duy trì.**

---

## Tình trạng sau khi Khắc phục (Giai đoạn 3)

Các thay đổi trong giai đoạn này giúp bảo vệ ứng dụng khỏi việc rò rỉ thông tin, các cuộc tấn công phía client (như clickjacking), và các cuộc tấn công tự động ở mức độ cơ bản. Những bản vá ứng dụng từ Giai đoạn 2 vẫn giữ nguyên hiệu lực.

---

## Danh sách Lỗ hổng (Đã khắc phục)

### ~~Mức độ Nghiêm trọng: Cao~~

---

### 1. Lỗ hổng Cross-Site Request Forgery (CSRF) trong Khu vực Quản trị - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 2)</span>

*   **Tóm tắt Lỗ hổng:** Các endpoint quản trị thiếu cơ chế bảo vệ chống lại tấn công CSRF.
*   **Hành động Khắc phục:**
    1.  **Triển khai Anti-CSRF Token:** Một hệ thống "Synchronizer Token Pattern" đã được triển khai.
    2.  Token CSRF được tạo khi quản trị viên đăng nhập và được lưu trong session.
    3.  Một tệp middleware trung tâm (`backend/api/admin/csrf_middleware.php`) đã được tạo để xác minh `X-CSRF-Token` trên tất cả các request `POST`, `PUT`, `DELETE` đến các endpoint quản trị.
    4.  Tất cả các endpoint trong `/backend/api/admin/` đã được cập nhật để sử dụng middleware này.

---

### 2. Mật khẩu Quản trị viên Mặc định trong Schema Cơ sở dữ liệu - <span style="color:green;">ĐÃ KHẮC PHỤC</span>

*   **Tóm tắt Lỗ hổng:** Tệp `database/schema.sql` chứa một tài khoản quản trị viên mặc định với mật khẩu yếu.
*   **Hành động Khắc phục:**
    1.  **Xóa Người dùng Mặc định:** Câu lệnh `INSERT` tạo ra người dùng quản trị viên mặc định đã bị xóa khỏi `database/schema.sql`.
    2.  **Tạo Kịch bản An toàn:** Một kịch bản dòng lệnh (`backend/scripts/create_admin.php`) đã được tạo để cho phép tạo tài khoản quản trị viên một cách an toàn, theo yêu cầu.

---

### ~~Mức độ Nghiêm trọng: Trung bình~~

---

### 3. Lỗ hổng Cross-Site Scripting (XSS) - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 2)</span>

*   **Tóm tắt Lỗ hổng:** Dữ liệu không được mã hóa ở phía máy chủ, dẫn đến các lỗ hổng XSS tiềm ẩn ở phía client.
*   **Hành động Khắc phục:**
    1.  **Tạo Hàm Tiện ích Mã hóa:** Một hàm `escape_output()` có thể tái sử dụng đã được tạo trong `backend/utils/security.php` để áp dụng `htmlspecialchars()` một cách đệ quy.
    2.  **Triển khai Mã hóa Phía Máy chủ:** Hàm này đã được áp dụng cho tất cả dữ liệu trả về trong các controller chính (`ProductController`, `CategoryController`, `OrderController`), đảm bảo rằng tất cả dữ liệu được gửi đến client đều được mã hóa an toàn.

---

### 4. Thiếu các Cờ Bảo mật cho Session Cookie - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 2)</span>

*   **Tóm tắt Lỗ hổng:** Session cookie thiếu các cờ `HttpOnly`, `Secure`, và `SameSite`.
*   **Hành động Khắc phục:**
    1.  **Tạo Tệp Bootstrap Trung tâm:** Một tệp `backend/core/bootstrap.php` đã được tạo để cấu hình các thiết lập session an toàn.
    2.  **Áp dụng Toàn cục:** Tất cả các tệp API đã được sửa đổi để bao gồm tệp bootstrap này, đảm bảo rằng tất cả các session đều được khởi tạo với các cờ bảo mật `HttpOnly`, `Secure` (khi có HTTPS), và `SameSite=Lax`.

---

### ~~Mức độ Nghiêm trọng: Thấp~~

---

### 5. Lộ thông tin Lỗi Chi tiết - <span style="color:green;">ĐÃ KHẮC PHỤC (Một phần)</span>

*   **Tóm tắt Lỗ hổng:** Một số endpoint trả về thông tin gỡ lỗi chi tiết.
*   **Hành động Khắc phục:** Tệp `backend/api/index.php` (router chính) đã được cấu hình để tắt `display_errors` và chỉ ghi lỗi vào tệp log trong môi trường production (dựa trên biến `APP_DEBUG`).

---

### 6. Điểm yếu trong Chức năng Tải lên Tệp - <span style="color:green;">ĐÃ KHẮC PHỤC</span>

*   **Tóm tắt Lỗ hổng:** Chức năng tải lên tệp không kiểm tra loại MIME hoặc kích thước tệp.
*   **Hành động Khắc phục:**
    1.  **Kiểm tra Loại MIME:** Logic đã được thêm vào `ProductController` để sử dụng `finfo_file` nhằm xác minh rằng các tệp được tải lên thực sự là hình ảnh.
    2.  **Thực thi Giới hạn Kích thước Tệp:** Logic cũng đã được thêm vào để kiểm tra kích thước tệp so với giá trị `MAX_UPLOAD_SIZE` từ tệp cấu hình.

---

### 7. Thông tin Nhạy cảm trong Repository - <span style="color:green;">ĐÃ KHẮC PHỤC</span>

*   **Tóm tắt Lỗ hổng:** Repository chứa một tệp dump SQL và các tài liệu có chứa mật khẩu mẫu.
*   **Hành động Khắc phục:**
    1.  Tệp `demolitiontraders.sql` đã bị xóa.
    2.  Tệp `SETUP.md` đã được cập nhật để sử dụng các placeholder thay vì các giá trị nhạy cảm.
    3.  Tệp `.gitignore` đã được củng cố.

---

### 8. Truy cập Công khai vào `/server-status` - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 3)</span>

*   **Tóm tắt Lỗ hổng:** Endpoint `/server-status` của Apache bị lộ công khai, có thể làm rò rỉ thông tin nhạy cảm về máy chủ.
*   **Hành động Khắc Phục:**
    *   Đã thêm một quy tắc `RewriteRule ^server-status/?$ - [F,L]` vào tệp `.htaccess` ở thư mục gốc.
    *   Quy tắc này chặn tất cả các yêu cầu đến `/server-status` và trả về lỗi 403 Forbidden.

---

### 9. Thiếu các Security Header Quan trọng - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 3)</span>

*   **Tóm tắt Lỗ hổng:** Ứng dụng không gửi các security header được khuyến nghị, làm tăng nguy cơ bị tấn công phía client.
*   **Hành động Khắc phục:** Đã thêm các header sau vào tệp `.htaccess`:
    *   `Content-Security-Policy`: Hạn chế các nguồn tài nguyên mà trình duyệt có thể tải.
    *   `X-Frame-Options: SAMEORIGIN`: Chống clickjacking.
    *   `X-Content-Type-Options: nosniff`: Ngăn trình duyệt đoán sai loại MIME.
    *   `Referrer-Policy: no-referrer-when-downgrade`: Kiểm soát thông tin referrer được gửi đi.
    *   `Strict-Transport-Security`: Yêu cầu trình duyệt luôn sử dụng HTTPS.
    *   `Permissions-Policy`: Kiểm soát quyền truy cập vào các tính năng của trình duyệt.

---

### 10. Thiếu Cơ chế Rate Limiting - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 3)</span>

*   **Tóm tắt Lỗ hổng:** Các API không có giới hạn yêu cầu, có thể bị lạm dụng bởi các cuộc tấn công brute-force hoặc DoS.
*   **Hành động Khắc phục:**
    *   Đã tạo một middleware rate limiting tại `backend/middleware/rate_limit.php`.
    *   Middleware sử dụng cơ chế lưu trữ dựa trên tệp để giới hạn các yêu cầu ở mức 60/phút/IP.
    *   Các quản trị viên đã đăng nhập được bỏ qua khỏi giới hạn này.
    *   Middleware đã được tích hợp vào router API chính (`backend/api/index.php`) để áp dụng cho tất cả các endpoint.

---

## Checklist Bảo mật để Duy trì trong Tương lai

Để duy trì và cải thiện tình hình bảo mật của ứng dụng, hãy xem xét các thực hành tốt nhất sau đây:

*   **Rà soát Phụ thuộc (Dependencies):** Thường xuyên quét các thư viện của bên thứ ba (ví dụ: `composer`, `npm`) để tìm các lỗ hổng đã biết.
*   **Mã hóa tất cả Đầu ra:** Đảm bảo rằng bất kỳ dữ liệu động nào được hiển thị trên trang đều được mã hóa bằng `htmlspecialchars()` hoặc một cơ chế tương đương.
*   **Sử dụng Prepared Statements:** Tiếp tục sử dụng prepared statements cho tất cả các truy vấn cơ sở dữ liệu.
*   **Thực thi Kiểm soát Truy cập:** Đối với bất kỳ endpoint API mới nào, hãy luôn kiểm tra xem người dùng đã được xác thực và có đủ quyền để thực hiện hành động đó hay không.
*   **Xác thực Đầu vào:** Xác thực tất cả dữ liệu đến từ người dùng.
*   **Quản lý Bí mật:** Không bao giờ hardcode các khóa API, mật khẩu, hoặc các bí mật khác trong mã nguồn.
*   **Cập nhật Thường xuyên:** Giữ cho máy chủ, PHP, và các thư viện khác được cập nhật lên các phiên bản mới nhất.
*   **Quét Bảo mật Định kỳ:** Chạy các công cụ như OWASP ZAP sau mỗi thay đổi lớn để phát hiện sớm các vấn đề mới.
