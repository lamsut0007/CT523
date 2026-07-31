CREATE DATABASE IF NOT EXISTS homestay_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE homestay_app;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS danh_gia;
DROP TABLE IF EXISTS dat_phong_ngay;
DROP TABLE IF EXISTS phong_hinh;
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
    gia_theo_gio DECIMAL(12,2) NOT NULL DEFAULT 0,
    gia_theo_ngay DECIMAL(12,2) NOT NULL DEFAULT 0,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE phong (
    id_phong INT AUTO_INCREMENT PRIMARY KEY,
    id_loai_phong INT NOT NULL,
    ma_so_phong VARCHAR(20) NOT NULL UNIQUE,
    ten_phong VARCHAR(100) NOT NULL,
    tang INT NOT NULL DEFAULT 1,
    vi_tri VARCHAR(150) DEFAULT NULL,
    huong_view VARCHAR(150) DEFAULT NULL,
    dien_tich DECIMAL(8,2) DEFAULT NULL,
    tien_nghi TEXT,
    duong_dan_hinh VARCHAR(255) NOT NULL,
    hinh_2 VARCHAR(255) DEFAULT NULL,
    hinh_3 VARCHAR(255) DEFAULT NULL,
    hinh_4 VARCHAR(255) DEFAULT NULL,
    gia_theo_gio DECIMAL(12,2) DEFAULT NULL,
    gia_theo_ngay DECIMAL(12,2) DEFAULT NULL,
    trang_thai ENUM('dang_hoat_dong', 'tu_sua', 'tam_dung') DEFAULT 'dang_hoat_dong',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_loai_phong) REFERENCES loai_phong(id_loai_phong),
    INDEX idx_phong_trang_thai (trang_thai)
) ENGINE=InnoDB;

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
    so_luong INT NOT NULL DEFAULT 1,
    tong_tien DECIMAL(12,2) NOT NULL DEFAULT 0,
    nguon_dat ENUM('web', 'dien_thoai') DEFAULT 'web',
    trang_thai ENUM('cho_xac_nhan', 'da_xac_nhan', 'da_nhan_phong', 'hoan_thanh', 'da_huy') DEFAULT 'cho_xac_nhan',
    ghi_chu VARCHAR(500),
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_phong) REFERENCES phong(id_phong),
    INDEX idx_lich_phong (id_phong, ngay_nhan_phong, ngay_tra_phong),
    INDEX idx_trang_thai_dat (trang_thai)
) ENGINE=InnoDB;

CREATE TABLE tai_khoan (
    id_tai_khoan INT AUTO_INCREMENT PRIMARY KEY,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    vai_tro ENUM('admin') DEFAULT 'admin',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE danh_gia (
    id_danh_gia INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(20),
    email VARCHAR(100),
    so_sao TINYINT NOT NULL,
    noi_dung TEXT NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tai_khoan (ten_dang_nhap, mat_khau, vai_tro)
VALUES ('admin', SHA2('Admin@123', 256), 'admin');

INSERT INTO loai_phong (ten_loai_phong, mo_ta, suc_chua, gia_theo_gio, gia_theo_ngay) VALUES
('Phòng đơn hướng biển', 'Phòng 1 giường, phù hợp cho 1–2 khách, có view nhìn ra biển.', 2, 120000, 550000),
('Phòng đôi sân hiên', 'Phòng 2 giường rộng rãi, có sân hiên riêng thoáng mát, phù hợp cho 2–4 khách.', 3, 170000, 780000),
('Phòng VIP gia đình', 'Phòng rộng cho nhóm gia đình, view đẹp và đầy đủ tiện nghi.', 5, 260000, 1300000);

INSERT INTO phong (id_loai_phong, ma_so_phong, ten_phong, tang, vi_tri, huong_view, dien_tich, tien_nghi, duong_dan_hinh, hinh_2, hinh_3, hinh_4, trang_thai) VALUES
(1, 'HS101', 'Hướng biển 101', 1, 'Dãy A - tầng 1', 'Biển phía Đông', 24, 'Máy lạnh, tủ lạnh mini, máy nước nóng, Wi-Fi, phòng tắm riêng', 'assets/room/1.jpg', 'assets/room/2.jpg', 'assets/room/3.jpg', NULL, 'dang_hoat_dong'),
(1, 'HS102', 'Hướng biển 102', 1, 'Dãy A - tầng 1', 'Biển phía Đông', 24, 'Máy lạnh, tủ lạnh mini, máy nước nóng, Wi-Fi, phòng tắm riêng', 'assets/room/2.jpg', 'assets/room/1.jpg', 'assets/room/4.jpg', NULL, 'dang_hoat_dong'),
(2, 'HS201', 'Phòng sân hiên 201', 2, 'Dãy A - tầng 2', 'Sân hiên và đồi', 32, 'Máy lạnh, tủ lạnh mini, sân hiên, máy nước nóng, Wi-Fi', 'assets/room/3.jpg', 'assets/room/4.jpg', 'assets/room/5.jpg', NULL, 'dang_hoat_dong'),
(2, 'HS202', 'Phòng sân hiên 202', 2, 'Dãy A - tầng 2', 'Sân hiên và đồi', 32, 'Máy lạnh, tủ lạnh mini, sân hiên, máy nước nóng, Wi-Fi', 'assets/room/4.jpg', 'assets/room/3.jpg', 'assets/room/6.jpg', NULL, 'tu_sua'),
(3, 'HS301', 'Phòng VIP gia đình 301', 3, 'Dãy B - tầng 3', 'Toàn cảnh biển', 48, 'Máy lạnh, tủ lạnh, bếp nhỏ, sofa, bồn tắm, Wi-Fi', 'assets/room/5.jpg', 'assets/room/6.jpg', 'assets/room/1.jpg', NULL, 'dang_hoat_dong');

INSERT INTO dat_phong (id_phong, ho_ten, so_dien_thoai, email, dia_chi, ngay_nhan_phong, ngay_tra_phong, kieu_dat, so_luong, tong_tien, nguon_dat, trang_thai, ghi_chu) VALUES
(1, 'Nguyễn Văn An', '0909000111', 'an@example.com', 'Rạch Giá, Kiên Giang', '2026-08-16 14:00:00', '2026-08-18 12:00:00', 'ngay', 2, 1100000, 'web', 'da_xac_nhan', 'Khách muốn phòng yên tĩnh.'),
(3, 'Trần Thị Bình', '0911000222', 'binh@example.com', 'Rạch Giá, Kiên Giang', '2026-08-18 08:00:00', '2026-08-18 18:00:00', 'gio', 10, 1700000, 'dien_thoai', 'cho_xac_nhan', 'Gọi trực tiếp để giữ phòng.'),
(5, 'Lê Minh Châu', '0922000333', 'chau@example.com', 'TP. Hồ Chí Minh', '2026-08-20 14:00:00', '2026-08-22 12:00:00', 'ngay', 2, 2600000, 'web', 'da_xac_nhan', 'Gia đình có trẻ nhỏ.'),
(1, 'Phạm Thanh Long', '0933001011', 'long@example.com', 'Cần Thơ', '2026-08-23 14:00:00', '2026-08-25 12:00:00', 'ngay', 2, 1100000, 'web', 'da_xac_nhan', NULL),
(5, 'Đặng Văn Hùng', '0955003033', 'hung@example.com', 'Hà Nội', '2026-08-28 14:00:00', '2026-09-01 12:00:00', 'ngay', 4, 5200000, 'web', 'cho_xac_nhan', 'Gia đình 5 người nghỉ hè.'),
-- Đơn chờ xác nhận cố tình trùng đơn #1 (phòng HS101, 16-18/08) để kiểm thử không cho xác nhận trùng lịch.
(1, 'Khách thử trùng lịch', '0901234999', 'conflict@example.com', 'Kiên Giang', '2026-08-17 14:00:00', '2026-08-19 12:00:00', 'ngay', 2, 1100000, 'web', 'cho_xac_nhan', 'Dữ liệu mẫu kiểm thử xung đột lịch.');

-- Lich mau thang 7/2026 de kiem tra trang thai Da nhan phong va Hoan thanh.
INSERT INTO dat_phong (id_phong, ho_ten, so_dien_thoai, email, dia_chi, ngay_nhan_phong, ngay_tra_phong, kieu_dat, so_luong, tong_tien, nguon_dat, trang_thai, ghi_chu) VALUES
(2, 'Nguyen Thi Mai', '0907001111', 'mai@example.com', 'Rach Gia, Kien Giang', '2026-07-04 14:00:00', '2026-07-06 12:00:00', 'ngay', 2, 1100000, 'web', 'hoan_thanh', 'Khach da luu tru va hoan tat thanh toan.'),
(5, 'Vo Minh Khang', '0907002222', 'khang@example.com', 'Can Tho', '2026-07-12 14:00:00', '2026-07-14 12:00:00', 'ngay', 3, 2600000, 'dien_thoai', 'hoan_thanh', 'Khach danh gia tot ve khong gian phong.'),
(1, 'Pham Ngoc Lan', '0907003333', 'lan@example.com', 'Ho Chi Minh', '2026-07-29 14:00:00', '2026-08-01 12:00:00', 'ngay', 2, 1650000, 'web', 'da_nhan_phong', 'Khach da nhan phong, du kien tra phong dau thang 8.'),
(3, 'Do Hoang Nam', '0907004444', 'nam@example.com', 'Ha Noi', '2026-07-30 14:00:00', '2026-08-01 12:00:00', 'ngay', 2, 2600000, 'dien_thoai', 'da_nhan_phong', 'Khach da nhan phong tai phong VIP.');

-- Them nhieu lich trong thang 7 de bang thong ke admin co du lieu da dang hon.
INSERT INTO dat_phong (id_phong, ho_ten, so_dien_thoai, email, dia_chi, ngay_nhan_phong, ngay_tra_phong, kieu_dat, so_luong, tong_tien, nguon_dat, trang_thai, ghi_chu) VALUES
(1, 'Tran Gia Huy', '0907111001', 'huy@example.com', 'Rach Gia, Kien Giang', '2026-07-02 14:00:00', '2026-07-04 12:00:00', 'ngay', 2, 1100000, 'web', 'hoan_thanh', 'Nghi duong cuoi tuan.'),
(1, 'Le Thu Ha', '0907111002', 'ha@example.com', 'Bac Lieu', '2026-07-10 14:00:00', '2026-07-12 12:00:00', 'ngay', 2, 1100000, 'dien_thoai', 'hoan_thanh', 'Khach yeu cau phong huong bien.'),
(1, 'Bui Thanh Tam', '0907111003', 'tam@example.com', 'Can Tho', '2026-07-18 14:00:00', '2026-07-20 12:00:00', 'ngay', 2, 1100000, 'web', 'hoan_thanh', 'Dat phong cho ky nghi ngan ngay.'),
(4, 'Pham Quoc Bao', '0907222001', 'bao@example.com', 'Kien Giang', '2026-07-01 14:00:00', '2026-07-03 12:00:00', 'ngay', 2, 1560000, 'web', 'hoan_thanh', 'Khach di cung gia dinh.'),
(4, 'Nguyen Khanh Linh', '0907222002', 'linh@example.com', 'Ho Chi Minh', '2026-07-08 14:00:00', '2026-07-10 12:00:00', 'ngay', 2, 1560000, 'web', 'hoan_thanh', 'Muon phong gan san hien.'),
(4, 'Hoang Minh Duc', '0907222003', 'duc@example.com', 'Dong Thap', '2026-07-18 14:00:00', '2026-07-20 12:00:00', 'ngay', 2, 1560000, 'dien_thoai', 'hoan_thanh', 'Khach da thanh toan day du.'),
(3, 'Vo Ngoc Anh', '0907333001', 'anh@example.com', 'Da Nang', '2026-07-02 14:00:00', '2026-07-05 12:00:00', 'ngay', 3, 3900000, 'web', 'hoan_thanh', 'Nghi duong cung gia dinh.'),
(3, 'Dang Hoai Nam', '0907333002', 'hoai@example.com', 'Vinh Long', '2026-07-18 14:00:00', '2026-07-21 12:00:00', 'ngay', 2, 3900000, 'web', 'hoan_thanh', 'Khach yeu cau them khan tam.'),
(3, 'Mai Thanh Son', '0907333003', 'son@example.com', 'Ha Noi', '2026-07-24 14:00:00', '2026-07-27 12:00:00', 'ngay', 2, 3900000, 'dien_thoai', 'hoan_thanh', 'Dat phong nghi he.'),
(5, 'Nguyen Hoang Yen', '0907555001', 'yen@example.com', 'An Giang', '2026-07-03 14:00:00', '2026-07-06 12:00:00', 'ngay', 4, 3900000, 'web', 'hoan_thanh', 'Gia dinh co tre nho.'),
(5, 'Truong Minh Quan', '0907555002', 'quan@example.com', 'TP. Ho Chi Minh', '2026-07-18 14:00:00', '2026-07-20 12:00:00', 'ngay', 3, 2600000, 'web', 'hoan_thanh', 'Khach uu tien khong gian rong.'),
(5, 'Doan Kim Chi', '0907555003', 'chi@example.com', 'Soc Trang', '2026-07-24 14:00:00', '2026-07-27 12:00:00', 'ngay', 4, 3900000, 'dien_thoai', 'hoan_thanh', 'Dat phong qua dien thoai.');

INSERT INTO dat_phong (id_phong, ho_ten, so_dien_thoai, email, dia_chi, ngay_nhan_phong, ngay_tra_phong, kieu_dat, so_luong, tong_tien, nguon_dat, trang_thai, ghi_chu) VALUES
(1, 'Vu Minh Phuc', '0907111004', 'phuc@example.com', 'Kien Giang', '2026-07-22 14:00:00', '2026-07-24 12:00:00', 'ngay', 2, 1100000, 'web', 'hoan_thanh', 'Khach quay lai dat phong lan hai.');

INSERT INTO danh_gia (ho_ten, so_dien_thoai, email, so_sao, noi_dung) VALUES
('Nguyễn Văn An', '0909000111', 'an@example.com', 5, 'Không khí biển dễ chịu, phòng sạch và chủ nhà hỗ trợ nhanh.'),
('Trần Thị Bình', '0911000222', 'binh@example.com', 4, 'Không gian yên tĩnh, phù hợp nghỉ dưỡng.'),
('Lê Minh Châu', '0922000333', 'chau@example.com', 5, 'Gia đình ở rất thoải mái, trẻ con thích không gian gần biển.'),
('Phạm Gia Bảo', '0908111001', 'baoo@example.com', 5, 'Phòng sạch, giường êm và view biển rất đẹp.'),
('Ngọc Hà', '0908111002', 'ngocha@example.com', 4, 'Nhân viên hỗ trợ nhanh, khu vực yên tĩnh.'),
('Minh Tuấn', '0908111003', 'tuan@example.com', 5, 'Sân hiên thoáng, buổi sáng ngồi uống cà phê rất thích.'),
('Thanh Thảo', '0908111004', 'thao@example.com', 4, 'Phòng rộng rãi, tiện nghi đầy đủ và dễ tìm.'),
('Quốc Khánh', '0908111005', 'khanh@example.com', 3, 'Không gian ổn, mong phần cách âm được cải thiện thêm.'),
('Hoài An', '0908111006', 'hoai.an@example.com', 5, 'Gia đình có kỳ nghỉ rất thoải mái, sẽ quay lại.'),
('Đức Anh', '0908111007', 'ducanh@example.com', 4, 'Phòng đẹp hơn hình, chủ nhà thân thiện.'),
('Kim Ngân', '0908111008', 'ngan@example.com', 5, 'Vị trí thuận tiện, phòng sạch và ánh sáng đẹp.');
