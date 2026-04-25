# Library Management System (PHP Vanilla)
Hệ thống quản lý thư viện tập trung vào tính toàn vẹn dữ liệu, sử dụng kiến trúc PHP thuần (Vanilla) và logic xử lý nghiệp vụ trực tiếp tại tầng Database (PostgreSQL).

## 🛠 Tech Stack
- **Backend:** PHP 8.2+ (PDO, Session-based Auth).
- **Frontend:** HTML5, CSS3 (Glassmorphism UI), Vanilla JS (Fetch API/AJAX).
- **Database:** PostgreSQL 16 (Triggers, Functions, PL/pgSQL).
- **Environment:** Docker, Docker Compose.

## 🏗 Đặc điểm hệ thống

### 1. Phân quyền (RBAC) & Use Cases
Hệ thống quản lý 3 cấp độ người dùng riêng biệt, mỗi phân quyền sẽ được chuyển hướng tới các Dashboard tương ứng với các chức năng chuyên biệt:

**A. Vai trò Quản trị viên (Admin)**
Đóng vai trò nhân sự quản lý cấp hạn mức cao nhất, duy trì cấu hình cốt lõi của thư viện.
- **Quản lý Nhân sự / Tài khoản:** Thêm mới tài khoản Librarian, xem danh sách toàn bộ người dùng trong hệ thống. Tìm kiếm, phân loại theo Role và thao tác Đóng/Mở khóa tài khoản (Active/Inactive) tức thì bằng AJAX.
- **Trigger Hệ thống Kế toán:** Thực thi tiến trình "Simulate 1st of Month" (Mô phỏng hóa đơn tháng mới). Hàm này sẽ đóng băng (pause) tất cả các thẻ thư viện định kỳ và chuyển chúng sang trạng thái yêu cầu gia hạn phí tháng.

**B. Vai trò Thủ thư (Librarian)**
Nhân sự vận hành trực tiếp các nghiệp vụ cốt lõi của thư viện hàng ngày.
- **Quản lý Thư mục Sách (Catalog):** Thêm sách mới, khai báo thông tin nguyên bản (Tác giả, ISBN, Năm XB) và tạo danh mục (Category).
- **Quản lý Tồn kho Sách Vật Lý (Book Copies):** Từng quyển sách nguyên bản có thể có hàng trăm bản sao vật lý. Thủ thư có thể thêm bản sao, ghi nhận tình trạng vật lý tĩnh (New, Good, Fair, Poor), bật/tắt trạng thái hiển thị trên kệ bằng nút Trigger và xóa bản sao ảo. 
- **Quy trình Mượn/Trả (Manage Borrows):** Chịu trách nhiệm ghi nhận sách báo trả từ Guest. Khi nhận sách, Thủ thư có thể khai báo số ngày trả trễ (Late Days). Hệ thống sẽ tự đối chiếu và tạo phiếu phạt tài chính nếu có vi phạm.
- **Quản lý Khoản Vay (Pending Fees):** Giao diện chuyên biệt để tra cứu toàn bộ các Guest Account đang nợ phí phạt (`Late Fees`) hoặc chưa đóng phí gia hạn (`Subscriptions`). Thủ thư cũng có quyền chặn Guest này thao tác bằng cách tắt khóa thẻ thư viện trực tiếp trên màn hình báo cáo.

**C. Vai trò Khách hàng (Guest)**
Đại diện cho độc giả/thành viên thư viện sử dụng thẻ để mượn sách.
- **Tra cứu Thư viện:** Sử dụng các bộ lộc (Filter) mượt mà để tra cứu đầu sách theo Title, Author, Publisher, hoặc Year.
- **Mượn Sách (Borrowing):** Guest thực hiện lệnh mượn qua thiết bị. (Hệ thống sẽ từ chối tự động nếu phát hiện thẻ (Card) bị vô hiệu hóa hoặc tài khoản đang tồn đọng nợ).
- **Bảng Cập Nhật Cá Nhân (Dashboard):** Tra cứu thống kê trực quan số sách đang cầm và lịch sử đã hoàn trả.
- **Thanh toán Trực tuyến (Payment Portal):** Trong trang cá nhân, Guest sẽ thấy lưới "Pending Payments". Khách có thể thanh toán các khoản phạt trả trễ phiếu trước đó hoặc thanh toán hóa đơn gia hạn tháng. Giao dịch thành công tạo dòng ghi log thanh toán và unlock thẻ thành công.

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
git clone <your-repo-url>
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
