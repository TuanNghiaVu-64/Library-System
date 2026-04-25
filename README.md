# Library Management System (PHP Vanilla)
Hệ thống quản lý thư viện tập trung vào tính toàn vẹn dữ liệu, sử dụng kiến trúc PHP thuần (Vanilla) và logic xử lý nghiệp vụ trực tiếp tại tầng Database (PostgreSQL).

## 🛠 Tech Stack
- **Backend:** PHP 8.2+ (PDO, Session-based Auth).
- **Frontend:** HTML5, CSS3 (Glassmorphism UI), Vanilla JS (Fetch API/AJAX).
- **Database:** PostgreSQL 16 (Triggers, Functions, PL/pgSQL).
- **Environment:** Docker, Docker Compose.

## 🏗 Đặc điểm hệ thống

### 1. Phân quyền (RBAC)
- **Admin:** Quản trị nhân sự (Librarians) và thực thi batch script thanh toán định kỳ hàng tháng (Monthly Billing Simulation).
- **Librarian:** Điều phối danh mục sách, quản lý hiện trạng bản sao (Physical Copies) và xử lý quy trình mượn/trả.
- **Guest:** Tra cứu đầu sách, quản lý lịch sử mượn và thanh toán công nợ trực tuyến.

### 2. Logic nghiệp vụ tại Database (Data Integrity)
Thay vì xử lý logic tại PHP, hệ thống sử dụng PostgreSQL Triggers & Functions để đảm bảo tính ACID:
- Tự động tính toán phí phạt (Late Fees) ngay khi cập nhật ngày trả sách.
- Tự động đồng bộ trạng thái Available/Unavailable của bản sao dựa trên giao dịch mượn.
- Khóa thẻ thành viên tự động khi phát sinh nợ quá hạn hoặc chưa đóng phí duy trì.

### 3. Tương tác không tải trang (Single-Page Feel)
Hệ thống sử dụng Vanilla JS để điều hướng và xử lý dữ liệu:
- **Asynchronous Search:** Tìm kiếm sách/người dùng theo thời gian thực (Debounced).
- **Instant Update:** Đóng/mở khóa thẻ và cập nhật trạng thái bản sao qua AJAX.

## 🚀 Triển khai nhanh

**Clone Repo:**
```bash
git clone https://github.com/TuanNghiaVu-64/Library-System
```

**Khởi chạy Docker:**
```bash
docker-compose up -d --build
```
*Hệ thống sẽ tự động khởi tạo cấu trúc DB và dữ liệu mẫu từ db.sql.*

**Truy cập:**
- **URL:** http://localhost:8080
- **Default Admin:** admin@library.local / Admin@1234

## 📂 Cấu trúc thư mục chính
- `/src`: Mã nguồn PHP (Controllers, Services, Core).
- `/public`: Entry point, Assets (CSS/JS).
- `/database`: File db.sql chứa cấu trúc bảng và PL/pgSQL.
- `docker-compose.yml`: Cấu hình môi trường.
