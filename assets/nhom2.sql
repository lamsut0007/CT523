CREATE DATABASE IF NOT EXISTS homestay_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE homestay_app;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS danh_gia;
DROP TABLE IF EXISTS dat_phong;
DROP TABLE IF EXISTS phong;
DROP TABLE IF EXISTS loai_phong;
DROP TABLE IF EXISTS tai_khoan;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE loai_phong (
    id_loai_phong INT AUTO_INCREMENT PRIMARY KEY,
    ten_loai_phong VARCHAR(100) NOT NULL,
    mo_ta TEXT,
    suc_chua INT NOT NULL,
    gia_theo_gio DECIMAL(12,2) NOT NULL,
    gia_theo_ngay DECIMAL(12,2) NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE phong (
    id_phong INT AUTO_INCREMENT PRIMARY KEY,
    id_loai_phong INT NOT NULL,
    ma_so_phong VARCHAR(20) NOT NULL UNIQUE,
    ten_phong VARCHAR(100) NOT NULL,
    duong_dan_hinh VARCHAR(255) NOT NULL,
    trang_thai ENUM('dang_hoat_dong', 'tu_sua') DEFAULT 'dang_hoat_dong',
    FOREIGN KEY (id_loai_phong) REFERENCES loai_phong(id_loai_phong)
);

CREATE TABLE dat_phong (
    id_dat_phong INT AUTO_INCREMENT PRIMARY KEY,
    id_phong INT NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    dia_chi VARCHAR(255),
    ngay_nhan_phong DATETIME NOT NULL,
    ngay_tra_phong DATETIME NOT NULL,
    kieu_dat ENUM('gio', 'ngay') DEFAULT 'ngay',
    tong_tien DECIMAL(12,2) NOT NULL DEFAULT 0,
    trang_thai ENUM('cho_xac_nhan', 'da_xac_nhan', 'da_huy', 'hoan_thanh') DEFAULT 'cho_xac_nhan',
    ghi_chu VARCHAR(255),
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_phong) REFERENCES phong(id_phong),
    INDEX idx_lich_phong (id_phong, ngay_nhan_phong, ngay_tra_phong)
);

CREATE TABLE tai_khoan (
    id_tai_khoan INT AUTO_INCREMENT PRIMARY KEY,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    vai_tro ENUM('admin') DEFAULT 'admin',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE danh_gia (
    id_danh_gia INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(20),
    email VARCHAR(100),
    so_sao TINYINT NOT NULL CHECK (so_sao BETWEEN 1 AND 5),
    noi_dung TEXT NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tai_khoan (ten_dang_nhap, mat_khau, vai_tro)
VALUES ('admin', SHA2('Admin@123', 256), 'admin');

INSERT INTO loai_phong (ten_loai_phong, mo_ta, suc_chua, gia_theo_gio, gia_theo_ngay) VALUES
('Hướng biển', 'Phòng gọn gàng với ô cửa mở ra gió mặn và ánh sáng sớm.', 2, 120000, 550000),
('Sân hiên đảo', 'Không gian thoáng cho buổi tối ngồi uống trà và ngắm sóng xa.', 3, 170000, 780000),
('Gia đình', 'Phòng rộng cho nhóm bạn hoặc gia đình với đầy đủ tiện nghi.', 5, 260000, 1300000);

INSERT INTO phong (id_loai_phong, ma_so_phong, ten_phong, duong_dan_hinh, trang_thai) VALUES
(1, 'HS101', 'Hướng biển 101', 'assets/room/1.jpg', 'dang_hoat_dong'),
(1, 'HS102', 'Hướng biển 102', 'assets/room/2.jpg', 'dang_hoat_dong'),
(2, 'HS201', 'Phòng sân hiên 201', 'assets/room/3.jpg', 'dang_hoat_dong'),
(2, 'HS202', 'Phòng sân hiên 202', 'assets/room/4.jpg', 'tu_sua'),
(3, 'HS301', 'Phòng gia đình 301', 'assets/room/5.jpg', 'dang_hoat_dong');

INSERT INTO dat_phong (id_phong, ho_ten, so_dien_thoai, email, dia_chi, ngay_nhan_phong, ngay_tra_phong, kieu_dat, tong_tien, trang_thai, ghi_chu) VALUES
-- Bookings sau ngày 15/8
(1, 'Nguyễn Văn An',   '0909000111', 'an@example.com',   'Rạch Giá, Kiên Giang', '2026-08-16 14:00:00', '2026-08-18 12:00:00', 'ngay', 1100000, 'da_xac_nhan', 'Khách muốn phòng thật yên để nghe sóng.'),
(3, 'Trần Thị Bình',   '0911000222', 'binh@example.com', 'Rạch Giá, Kiên Giang', '2026-08-18 08:00:00', '2026-08-18 18:00:00', 'gio',  1700000, 'cho_xac_nhan', 'Cần nhận phòng sớm để kịp chuyến tàu ra đảo.'),
(5, 'Lê Minh Châu',     '0922000333', 'chau@example.com', 'TP. Hồ Chí Minh',     '2026-08-20 14:00:00', '2026-08-22 12:00:00', 'ngay', 2600000, 'da_xac_nhan', 'Gia đình có trẻ nhỏ, cần phòng rộng và thoáng.'),

-- Booking sau 15/8 liên tục
(1, 'Phạm Thanh Long',   '0933001011', 'long@example.com',  'Cần Thơ',              '2026-08-23 14:00:00', '2026-08-25 12:00:00', 'ngay', 1100000, 'da_xac_nhan', NULL),
(3, 'Hoàng Thị Mai',    '0944002022', 'mai@example.com',   'TP. Hồ Chí Minh',     '2026-08-26 09:00:00', '2026-08-26 17:00:00', 'gio',   1360000, 'da_xac_nhan', 'Nhóm bạn 3 người cần thư giãn cuối tuần.'),
(5, 'Đặng Văn Hùng',   '0955003033', 'hung@example.com',  'Hà Nội',                '2026-08-28 14:00:00', '2026-09-01 12:00:00', 'ngay', 5200000, 'da_xac_nhan', 'Gia đình 5 người nghỉ hè.'),
(2, 'Nguyễn Thị Lan',   '0966004044', 'lan@example.com',   'Vip Vĩnh Long',         '2026-09-02 14:00:00', '2026-09-04 12:00:00', 'ngay',  1100000, 'cho_xac_nhan', NULL),
(1, 'Trương Quốc Bảo', '0977005055', 'bao@example.com',   'Đà Nẵng',               '2026-09-05 08:00:00', '2026-09-05 14:00:00', 'gio',   720000,  'da_xac_nhan', 'Ghé đảo hầu như nửa ngày.'),
(3, 'Võ Kim Oanh',       '0988006066', 'oanh@example.com',  'Kiên Giang',             '2026-09-07 14:00:00', '2026-09-10 12:00:00', 'ngay', 2340000, 'da_xac_nhan', NULL),
(5, 'Lý Thành Phút',    '0999007077', 'phat@example.com',  'Tiền Giang',             '2026-09-11 14:00:00', '2026-09-13 12:00:00', 'ngay', 2600000, 'cho_xac_nhan', 'Nhóm 4 người, có trẻ em.'),
(1, 'Bùi Xuân Tuấn',    '0900008088', 'tuan@example.com',  'An Giang',               '2026-09-14 14:00:00', '2026-09-17 12:00:00', 'ngay', 1650000, 'da_xac_nhan', NULL),
(2, 'Trần Huỳnh Như',   '0911009099', 'nhu@example.com',   'Bến Tre',               '2026-09-18 10:00:00', '2026-09-18 18:00:00', 'gio',   960000,  'cho_xac_nhan', 'Nghỉ giải lao giữa chuyến.'),

-- Tháng 9 mở rộng
(3, 'Nguyễn Anh Tuấn',  '0922010100', 'atuan@example.com', 'TP. Hồ Chí Minh',     '2026-09-20 14:00:00', '2026-09-22 12:00:00', 'ngay', 2340000, 'da_xac_nhan', NULL),
(1, 'Phương Thị Ngọc','0933011111', 'ngoc@example.com',  'Đồng Nai',              '2026-09-23 09:00:00', '2026-09-23 15:00:00', 'gio',   720000,  'da_xac_nhan', 'Khách lỗ chuyến tàu, cần chỗ ở 6 tiếng.'),
(5, 'Lê Thanh Tùng',     '0944012122', 'tung@example.com',  'Hải Phòng',             '2026-09-25 14:00:00', '2026-09-29 12:00:00', 'ngay', 6500000, 'da_xac_nhan', 'Nhóm đồng nghiệp 5 người.'),
(2, 'Trịnh Minh Khôi',  '0955013133', 'khoi@example.com',  'Vũng Tàu',              '2026-09-30 14:00:00', '2026-10-02 12:00:00', 'ngay', 1100000, 'cho_xac_nhan', NULL),
(3, 'Cao Thị Thuỳ',     '0966014144', 'thuy@example.com',  'Long An',                '2026-10-03 08:00:00', '2026-10-03 20:00:00', 'gio',  2040000,  'da_xac_nhan', 'Team building nhóm 8 người ban ngày.'),
(1, 'Mạc Thanh Hà',     '0977015155', 'ha@example.com',    'Cần Thơ',              '2026-10-05 14:00:00', '2026-10-07 12:00:00', 'ngay', 1100000, 'da_xac_nhan', NULL),
(5, 'Đinh Quốc Khánh', '0988016166', 'khanh@example.com', 'Bình Dương',            '2026-10-08 14:00:00', '2026-10-11 12:00:00', 'ngay', 3900000, 'cho_xac_nhan', 'Gia đình 4 người nghỉ cuối hè.');

INSERT INTO danh_gia (ho_ten, so_dien_thoai, email, so_sao, noi_dung) VALUES
('Nguyễn Văn An', '0909000111', 'an@example.com', 5, 'Không khí biển rất dễ chịu, phòng sạch và chủ nhà hỗ trợ cực kỳ nhanh.'),
('Trần Thị Bình', '0911000222', 'binh@example.com', 4, 'Đúng chất nghỉ đảo, buổi sáng mở cửa ra là thấy gió biển rất thích.'),
('Lê Minh Châu', '0922000333', 'chau@example.com', 5, 'Gia đình mình ở rất thoải mái, trẻ con thích không gian gần biển và yên tĩnh.');
