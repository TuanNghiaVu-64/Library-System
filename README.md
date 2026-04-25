# Hệ thống Quản lý Thư viện (Library Management System)

Một ứng dụng web quản lý thư viện hiện đại, trả phí mượn sách đa cấp độ, được xây dựng với kiến trúc Vanilla PHP, JavaScript AJAX trực tiếp và cơ sở dữ liệu PostgreSQL. Dự án tập trung vào tính tương tác nhanh nhạy (không cần tải lại trang cho nhiều chức năng thao tác), bảo mật nghiêm ngặt và giao diện bắt mắt theo phong cách Glassmorphism.

## 🚀 Các Chức Năng Chính & Use Cases (Vai Trò Người Dùng)

Hệ thống được chia thành 3 vai trò phân quyền chính, mỗi vai trò có các bảng điều khiển (dashboard) chuyên biệt:

### 1. Quản trị viên (Admin)
- **Quản trị người dùng cao cấp:** Thêm mới, phân quyền, xem danh sách, cập nhật trạng thái hoạt động (bằng AJAX) hoặc xóa bỏ tài khoản của Quản trị viên khác và Thủ thư.
- **Mô phỏng Thanh toán (Billing):** Admin có quyền chạy trình mô phỏng "Mùng 1 hàng tháng". Quá trình này sẽ đóng băng (pause) tạm thời tất cả các thẻ thư viện đang hoạt động và chuyển chúng sang trạng thái chưa thanh toán, yêu cầu người dùng phải đóng phí gia hạn tháng.

### 2. Thủ thư (Librarian)
- **Quản lý Thư mục (Catalog Management):** Thêm sách mới, thêm các thể loại sách và quản lý chung toàn bộ đầu sách.
- **Quản lý Bản sao Sách (Book Copies):** 
  - Xem và quản lý tình trạng các bản sao của một đầu sách (Sách mới, tốt, cũ...).
  - Vô hiệu hóa (cập nhật trạng thái "Available/Unavailable") bằng AJAX chặn tức thời nếu sách đang được mượn.
  - Xóa bỏ bản sao hoặc xóa toàn bộ đầu sách (áp dụng các chốt an toàn cơ sở dữ liệu).
- **Quản lý Mượn/Trả (Manage Borrows):** 
  - Xem danh sách Người dùng (Guest) phân lọc linh hoạt bằng AJAX (theo Tên, Phân loại Thẻ, Trạng thái đang mượn). 
  - Xử lý quá trình nhận trả sách và mô phỏng số ngày trễ hạn. Tính năng Trigger SQL sẽ tự động phát sinh biên lai quá hạn (Late Fees).
- **Quản lý Công Nợ (Pending Fees):** Xem toàn bộ các tài khoản đang nợ phí phạt hoặc phí gia hạn. Thủ thư cũng có thể bật/tắt (khóa) thẻ thư viện của khách ngay trên lưới kết quả mà không cần tải lại trang.

### 3. Khách Hàng (Guest)
- **Khám phá Thư viện:** Tìm kiếm đầu sách theo tiêu đề, tác giả, nhà xuất bản, và năm phát hành.
- **Mượn Sách:** Cho phép khách hàng mượn sách (sẽ báo lỗi tự động nếu tài khoản khách đang bị tạm khóa thẻ hoặc chưa hoàn tất thanh toán nợ).
- **Trang Cá Nhân (Dashboard):** 
  - Theo dõi sách đang mượn và lịch sử đã trả.
  - **Khu vực Thanh toán (Pending Payments):** Khách mượn có thể xem và thực hiện "Thanh toán (Pay Now)" cho các hóa đơn đăng ký hàng tháng bị trễ (Subscription) hoặc phí phạt trả trễ hạn (Late Fees). Giao dịch này sẽ mở khóa thẻ ngay lập tức.

## 💻 Tech Stack (Công Nghệ Sử Dụng)

- **Frontend:** 
  - HTML5 & CSS3 thuần thiết kế theo hệ giao diện Glassmorphism cực kỳ hiện đại và Responsive.
  - Vanilla JavaScript thực hiện các xử lý logic DOM, thao tác debounce search và gửi yêu cầu AJAX (Fetch API) để tối ưu hóa trải nghiệm không tải lại trang.
- **Backend:** 
  - Vanilla PHP 8+ xử lý các Router và Service.
  - Tích hợp lớp PDO (PHP Data Objects) chuẩn bảo vệ SQL Injection và Session Token chống CSRF trên toàn bộ form POST.
- **Cơ sở Dữ liệu (Database):** 
  - PostgreSQL 16+ vận hành toàn bộ Logic toàn vẹn dữ liệu gốc.
  - Tích hợp **PostgreSQL Triggers & Functions**: Hệ thống DB tự động kiểm tra số ngày trễ, tự tạo bảng nợ, tự giấu (unavailable) bản copy nếu có người mượn, ... hoàn toàn qua T-SQL/PLpgSQL nhằm giữ tính ACID tuyệt đối cho Database.

## 🛠 Hướng dẫn Cài đặt & Chạy Dự án (Setup Guide)

### Yêu cầu hệ thống:
- PHP >= 8.1 (bật extension `pdo_pgsql`)
- PostgreSQL >= 14

### Các bước triển khai:

1. **Clone mã nguồn (Repository)**
   Đưa mã nguồn ứng dụng về thư mục Local hoặc Workspace của bạn.
   
2. **Thiết lập Database**
   - Mở hệ quản trị PostgreSQL (PgAdmin hoặc PSQL CLI), tạo một Document Database rỗng mang tên: `library_db` (hay tên bạn muốn).
   - Truy xuất file `db.sql` nằm ở thư mục gốc của project. Chạy tuần tự/toàn bộ query từ file này vào Database bạn vừa tạo. Đoạn Script này sẽ tự động khởi tạo Enum, Tables, Triggers, Indexes và quan trọng nhất là **Seed Data** (dữ liệu mẫu) hoàn chỉnh.

3. **Cấu hình Kết nối PHP**
   - Mở file `includes/db.php`.
   - Điều chỉnh thông số `dbname`, `user` (ví dụ: *postgres*) và `password` cho phù hợp với máy chủ Database thực tế của bạn.

4. **Khởi chạy Server**
   Bạn có thể dùng XAMPP, Nginx, hoặc đơn giả là PHP Built-in server tích hợp sẵn.
   Mở terminal lệnh ở thư mục dự án và gõ:
   ```bash
   php -S localhost:8080 -t public
   ```

5. **Truy Cập Ứng Dụng**
   - Mở trình duyệt: `http://localhost:8080/`
   - Đăng nhập bằng tài khoản Admin mặc định có trong Seed Data để trải nghiệm:
     - **Email:** `admin@library.local`
     - **Mật khẩu:** `Admin@1234`
   - *Lưu ý: Mật khẩu Librarian mẫu là `Librarian@2026` và Khách mẫu là `MemberPass123`.*
