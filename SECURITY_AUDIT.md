# Báo cáo Đánh giá Bảo mật - Demolition Traders

**Ngày đánh giá:** 2025-12-03
**Người đánh giá:** Jules (AI Software Engineer)
**Ngày cập nhật (Giai đoạn 3):** 2025-12-04

## Tóm tắt Tổng quan

Trang web Demolition Traders được xây dựng trên nền tảng PHP tùy chỉnh. Các giai đoạn trước đã khắc phục các lỗ hổng ứng dụng nghiêm trọng. Giai đoạn 3 tập trung vào việc củng cố bảo mật ở cấp độ máy chủ và cơ sở hạ tầng dựa trên kết quả quét của OWASP ZAP.

**Bản cập nhật Giai đoạn 3 này xác nhận rằng các vấn đề về cấu hình máy chủ, security header, và thiếu rate limiting đã được giải quyết.**

---

## Tình trạng sau khi Khắc phục (Giai đoạn 3)

Các thay đổi trong giai đoạn này giúp bảo vệ ứng dụng khỏi việc rò rỉ thông tin, các cuộc tấn công phía client (như clickjacking), và các cuộc tấn công tự động ở mức độ cơ bản.

---

## Danh sách Lỗ hổng (Đã khắc phục)

### ~~Mức độ Nghiêm trọng: Cao~~

---

### 1. Truy cập Công khai vào `/server-status` - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 3)</span>

*   **Tóm tắt Lỗ hổng:** Endpoint `/server-status` của Apache bị lộ công khai, có thể làm rò rỉ thông tin nhạy cảm về máy chủ.
*   **Hành động Khắc phục:**
    *   Đã thêm một quy tắc `RewriteRule ^server-status/?$ - [F,L]` vào tệp `.htaccess` ở thư mục gốc.
    *   Quy tắc này chặn tất cả các yêu cầu đến `/server-status` và trả về lỗi 403 Forbidden.

---

### 2. Thiếu các Security Header Quan trọng - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 3)</span>

*   **Tóm tắt Lỗ hổng:** Ứng dụng không gửi các security header được khuyến nghị, làm tăng nguy cơ bị tấn công phía client.
*   **Hành động Khắc phục:** Đã thêm các header sau vào tệp `.htaccess`:
    *   `Content-Security-Policy`: Hạn chế các nguồn tài nguyên mà trình duyệt có thể tải.
    *   `X-Frame-Options: SAMEORIGIN`: Chống clickjacking.
    *   `X-Content-Type-Options: nosniff`: Ngăn trình duyệt đoán sai loại MIME.
    *   `Referrer-Policy: no-referrer-when-downgrade`: Kiểm soát thông tin referrer được gửi đi.
    *   `Strict-Transport-Security`: Yêu cầu trình duyệt luôn sử dụng HTTPS.
    *   `Permissions-Policy`: Kiểm soát quyền truy cập vào các tính năng của trình duyệt.

---

### 3. Thiếu Cơ chế Rate Limiting - <span style="color:green;">ĐÃ KHẮC PHỤC (Giai đoạn 3)</span>

*   **Tóm tắt Lỗ hổng:** Các API không có giới hạn yêu cầu, có thể bị lạm dụng bởi các cuộc tấn công brute-force hoặc DoS.
*   **Hành động Khắc phục:**
    *   Đã tạo một middleware rate limiting tại `backend/middleware/rate_limit.php`.
    *   Middleware sử dụng cơ chế lưu trữ dựa trên tệp để giới hạn các yêu cầu ở mức 60/phút/IP.
    *   Các quản trị viên đã đăng nhập được bỏ qua khỏi giới hạn này.
    *   Middleware đã được tích hợp vào router API chính (`backend/api/index.php`) để áp dụng cho tất cả các endpoint.

---

*Ghi chú: Các lỗ hổng đã được khắc phục trong các giai đoạn trước (CSRF, XSS, Session Hardening, v.v.) vẫn được duy trì ở trạng thái đã sửa.*

---

## Checklist Bảo mật để Duy trì trong Tương lai

Để duy trì và cải thiện tình hình bảo mật của ứng dụng, hãy xem xét các thực hành tốt nhất sau đây:

*   **Rà soát Phụ thuộc (Dependencies):** Thường xuyên quét các thư viện của bên thứ ba.
*   **Mã hóa tất cả Đầu ra:** Đảm bảo mọi dữ liệu động đều được mã hóa.
*   **Sử dụng Prepared Statements:** Tiếp tục sử dụng cho tất cả các truy vấn cơ sở dữ liệu.
*   **Thực thi Kiểm soát Truy cập:** Luôn kiểm tra quyền cho các endpoint mới.
*   **Xác thực Đầu vào:** Xác thực tất cả dữ liệu từ người dùng.
*   **Quản lý Bí mật:** Không hardcode thông tin nhạy cảm.
*   **Cập nhật Thường xuyên:** Giữ cho máy chủ, PHP, và các thư viện được cập nhật.
*   **Quét Bảo mật Định kỳ:** Chạy các công cụ như OWASP ZAP định kỳ sau khi có các thay đổi lớn.
