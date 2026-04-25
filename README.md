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
- Docker
- Docker Compose

### Các bước triển khai:

1. **Clone mã nguồn (Repository)**
   Đưa mã nguồn ứng dụng về thư mục máy tính của bạn.
   
2. **Khởi chạy hệ thống bằng Docker**
   Mở terminal / command prompt tại thư mục gốc của dự án (nơi chứa file `docker-compose.yml`) và chạy dòng lệnh sau:
   ```bash
   docker-compose up -d --build
   ```
   *Quá trình này sẽ tự động build image PHP, pull image PostgreSQL, liên kết chúng với nhau, thiết lập biến môi trường và chạy seed data từ file `db.sql` tạo sẵn cơ sở dữ liệu cho bạn.*

3. **Truy Cập Ứng Dụng**
   - Mở trình duyệt và truy cập: `http://localhost:8080/`
   - Đăng nhập bằng tài khoản Admin mặc định để trải nghiệm:
     - **Email:** `admin@library.local`
     - **Mật khẩu:** `Admin@1234`

   *(Các tài khoản test phân quyền khác có sẵn trong hệ thống: Thủ thư `sarah.c@library.local` / `Librarian@2026` và Khách `john.doe@email.com` / `MemberPass123`)*

4. **Dừng máy chủ**
   Khi không sử dụng nữa, bạn có thể tắt các container bằng lệnh:
   ```bash
   docker-compose down
   ```
