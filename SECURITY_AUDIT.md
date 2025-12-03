# Báo cáo Đánh giá Bảo mật - Demolition Traders

**Ngày đánh giá:** 2025-12-03
**Người đánh giá:** Jules (AI Software Engineer)

## Tóm tắt Tổng quan

Trang web Demolition Traders được xây dựng trên nền tảng PHP tùy chỉnh, với logic nghiệp vụ được triển khai tốt và các biện pháp bảo mật cốt lõi vững chắc. Ứng dụng đã thực hiện xuất sắc việc phòng chống các lỗ hổng nghiêm trọng như **SQL Injection** và **giả mạo giá (Price Tampering)**.

Tuy nhiên, có một số lỗ hổng bảo mật nghiêm trọng cần được ưu tiên khắc phục, đặc biệt là **Cross-Site Request Forgery (CSRF)** trong khu vực quản trị và **Cross-Site Scripting (XSS)** trên các trang hiển thị dữ liệu do người dùng tạo. Ngoài ra, việc quản lý phiên và xử lý tệp tải lên cũng cần được cải thiện.

Báo cáo này sẽ trình bày chi tiết các phát hiện, phân loại theo mức độ nghiêm trọng và cung cấp các hướng dẫn khắc phục cụ thể.

---

## Danh sách Lỗ hổng và Đề xuất

### Mức độ Nghiêm trọng: Cao

---

### 1. Lỗ hổng Cross-Site Request Forgery (CSRF) trong Khu vực Quản trị

*   **Mô tả:** Tất cả các endpoint trong khu vực quản trị (`/backend/api/admin/`) đều thiếu cơ chế bảo vệ chống lại tấn công CSRF. Kẻ tấn công có thể lừa một quản trị viên đang đăng nhập truy cập vào một trang web độc hại, trang này sẽ tự động gửi một yêu cầu hợp lệ đến các API nhạy cảm (ví dụ: nâng cấp người dùng thành quản trị viên, xóa người dùng) mà không có sự đồng ý của quản trị viên.
*   **Vị trí:**
    *   `backend/api/admin/promote-to-admin.php`
    *   `backend/api/admin/delete-user.php`
    *   `backend/api/admin/reset-user-password.php`
    *   Và tất cả các endpoint quản trị khác.
*   **Tác động:** Cho phép kẻ tấn công thực hiện các hành động quản trị trái phép, dẫn đến việc chiếm quyền kiểm soát hoàn toàn trang web.
*   **Đề xuất khắc phục:**
    1.  **Triển khai Anti-CSRF Token (Synchronizer Token Pattern):**
        *   Khi người dùng đăng nhập, tạo một token CSRF ngẫu nhiên, duy nhất và lưu nó vào session của người dùng (`$_SESSION['csrf_token']`).
        *   Khi hiển thị bất kỳ form nào hoặc chuẩn bị cho một request AJAX nhạy cảm, nhúng token này vào một thẻ `<meta>` trong HTML hoặc cung cấp nó cho JavaScript.
        *   Yêu cầu JavaScript gửi token này trong một header HTTP tùy chỉnh (ví dụ: `X-CSRF-Token`) với mỗi request `POST`, `PUT`, `DELETE`.
        *   Ở phía máy chủ, trong mỗi endpoint quản trị, so sánh token nhận được trong header với token được lưu trong session. Nếu chúng không khớp, từ chối yêu cầu.

---

### 2. Mật khẩu Quản trị viên Mặc định trong Schema Cơ sở dữ liệu

*   **Mô tả:** Tệp `database/schema.sql` chứa một câu lệnh `INSERT` để tạo tài khoản quản trị viên với mật khẩu mặc định là `admin123`. Nếu tệp này được sử dụng để triển khai trong môi trường production, một tài khoản quản trị viên với mật khẩu yếu sẽ tồn tại, tạo ra một cửa hậu (backdoor) cho kẻ tấn công.
*   **Vị trí:** `database/schema.sql` (dòng 230)
*   **Tác động:** Kẻ tấn công có thể dễ dàng đoán hoặc brute-force mật khẩu mặc định để có được quyền truy cập quản trị.
*   **Đề xuất khắc phục:**
    1.  **Xóa câu lệnh INSERT:** Xóa hoàn toàn câu lệnh `INSERT INTO users ...` khỏi tệp `database/schema.sql`.
    2.  **Tạo Kịch bản Cài đặt (Installation Script):** Tạo một kịch bản PHP riêng (chỉ chạy một lần) để thiết lập trang web. Kịch bản này nên:
        *   Yêu cầu người dùng nhập thông tin chi tiết cho tài khoản quản trị viên.
        *   Hoặc, tạo một mật khẩu ngẫu nhiên, mạnh và hiển thị nó cho người dùng một lần duy nhất.

---

### Mức độ Nghiêm trọng: Trung bình

---

### 3. Lỗ hổng Cross-Site Scripting (XSS)

*   **Mô tả:** Dữ liệu do người dùng cung cấp (ví dụ: tên sản phẩm, thuật ngữ tìm kiếm) không được mã hóa (escape) một cách nhất quán trước khi hiển thị trên trang. Phía máy chủ không thực hiện mã hóa nào, và phía client chỉ mã hóa một phần, để lại lỗ hổng trong các thuộc tính HTML.
*   **Vị trí:**
    *   **Phía Client (Lỗ hổng):** `frontend/user/shop.php` - Tên sản phẩm không được mã hóa khi được sử dụng trong thuộc tính `alt` của thẻ `<img>`.
    *   **Phía Máy chủ (Nguyên nhân gốc rễ):** `backend/controllers/ProductController.php` - Trả về dữ liệu thô mà không áp dụng `htmlspecialchars()`.
*   **Tác động:** Kẻ tấn công có thể chèn mã JavaScript độc hại vào tên sản phẩm. Khi người dùng khác xem sản phẩm đó, mã độc sẽ được thực thi trong trình duyệt của họ, cho phép kẻ tấn công đánh cắp cookie phiên, thực hiện các hành động thay mặt người dùng, hoặc chuyển hướng họ đến các trang web lừa đảo.
*   **Đề xuất khắc phục:**
    1.  **(Ưu tiên) Mã hóa ở Phía Máy chủ:** Sửa đổi `ProductController.php` và các controller khác để áp dụng `htmlspecialchars()` cho tất cả dữ liệu văn bản trước khi gửi nó đến client.
        ```php
        // Ví dụ trong ProductController.php
        foreach ($products as &$product) {
            $product['name'] = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
            // ... mã hóa các trường khác ...
        }
        return ['data' => $products, ...];
        ```
    2.  **(Bổ sung) Sửa lỗi ở Phía Client:** Sửa lỗi mã hóa trong `frontend/user/shop.php` để đảm bảo `escapedName` được sử dụng cho cả thuộc tính `alt`.

---

### 4. Thiếu các Cờ Bảo mật cho Session Cookie

*   **Mô tả:** Các cookie của phiên không được thiết lập với các cờ bảo mật quan trọng là `HttpOnly`, `Secure`, và `SameSite`.
    *   **Thiếu `HttpOnly`:** Cho phép JavaScript truy cập vào cookie, làm tăng nguy cơ bị đánh cắp cookie thông qua tấn công XSS.
    *   **Thiếu `Secure`:** Cho phép cookie được gửi qua các kết nối HTTP không được mã hóa.
    *   **Thiếu `SameSite`:** Làm tăng khả năng bị tấn công CSRF.
*   **Vị trí:** Tất cả các tệp gọi `session_start()` (ví dụ: `backend/api/user/login.php`).
*   **Tác động:** Làm giảm khả năng phòng thủ của ứng dụng trước các cuộc tấn công đánh cắp phiên và CSRF.
*   **Đề xuất khắc phục:**
    1.  **Tạo một Tệp Khởi tạo Chung:** Tạo một tệp `bootstrap.php` được `require` bởi tất cả các endpoint API. Trong tệp này, cấu hình các thiết lập phiên trước khi gọi `session_start()`.
        ```php
        // backend/core/bootstrap.php
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);

        // Chỉ bật cờ Secure nếu trang web sử dụng HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }

        session_start([
            'cookie_lifetime' => 86400,
            'cookie_samesite' => 'Lax' // 'Lax' là một giá trị cân bằng tốt, 'Strict' an toàn hơn nhưng có thể ảnh hưởng đến trải nghiệm người dùng.
        ]);
        ```

---

### Mức độ Nghiêm trọng: Thấp

---

### 5. Lộ thông tin Lỗi Chi tiết

*   **Mô tả:** Trong chế độ gỡ lỗi, một số endpoint API có thể trả về thông tin lỗi chi tiết, bao gồm cả tên tệp và số dòng.
*   **Vị trí:** `backend/api/user/login.php`
*   **Tác động:** Cung cấp cho kẻ tấn công thông tin về cấu trúc của ứng dụng, có thể được sử dụng để lên kế hoạch cho các cuộc tấn công khác.
*   **Đề xuất khắc phục:** Cấu hình một trình xử lý lỗi chung (global error handler) để chỉ trả về các thông báo lỗi chung chung trong môi trường production, trong khi vẫn ghi lại các lỗi chi tiết vào tệp log ở phía máy chủ.

---

### 6. Điểm yếu trong Chức năng Tải lên Tệp

*   **Mô tả:** Chức năng tải lên hình ảnh sản phẩm không kiểm tra loại MIME của tệp và không có giới hạn kích thước tệp ở phía máy chủ.
*   **Vị trí:** `backend/controllers/ProductController.php` (phương thức `handleProductImages`)
*   **Tác động:** Cho phép tải lên các tệp có nội dung độc hại (ví dụ: một tệp PHP được đổi tên thành `.jpg`) và có thể bị lạm dụng để thực hiện tấn công từ chối dịch vụ bằng cách tải lên các tệp rất lớn.
*   **Đề xuất khắc phục:**
    1.  **Kiểm tra Loại MIME:** Sử dụng `finfo_file` để xác minh loại MIME thực sự của tệp.
    2.  **Thực thi Giới hạn Kích thước Tệp:** Kiểm tra kích thước của tệp trong `$_FILES['product_images']['size']` và so sánh nó với giá trị `MAX_UPLOAD_SIZE` từ tệp cấu hình.

---

### 7. Thông tin Nhạy cảm trong Repository

*   **Mô tả:** Repository chứa các tệp không cần thiết có chứa thông tin nhạy cảm hoặc có thể gây rủi ro.
*   **Vị trí:**
    *   `demolitiontraders.sql`: Một tệp dump cơ sở dữ liệu chứa mật khẩu đã băm.
    *   `SETUP.md`: Tài liệu chứa mật khẩu và API key mẫu.
*   **Tác động:** Tăng bề mặt tấn công và có thể bị gắn cờ bởi các công cụ quét bảo mật.
*   **Đề xuất khắc phục:**
    1.  Xóa `demolitiontraders.sql` khỏi repository.
    2.  Thay thế các giá trị nhạy cảm trong `SETUP.md` bằng các placeholder.
    3.  Sử dụng tệp `.gitignore` để đảm bảo các tệp dump hoặc các tệp nhạy cảm khác không vô tình bị commit trong tương lai.
