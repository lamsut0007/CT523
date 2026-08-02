<?php
$pageTitle = "Danh sách phòng - OASIS Homestay";
include "includes/db.php";
include "includes/functions.php";

// ── JSON: lịch đặt phòng (thay thế file api/ riêng) ─────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'calendar') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $idPhong = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $soThang = isset($_GET['months']) ? (int) $_GET['months'] : 3;
    $lich = lay_lich_dat_phong($conn, $idPhong, $soThang);
    if ($lich === null) {
        echo json_encode(['ok' => false, 'error' => 'Thiếu tham số hoặc lỗi DB']);
    } else {
        echo json_encode(['ok' => true, 'dat' => $lich, 'months' => $soThang]);
    }
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

$ngayNhanPhong = isset($_GET["ngay_nhan_phong"]) ? trim($_GET["ngay_nhan_phong"]) : "";
$ngayTraPhong = isset($_GET["ngay_tra_phong"]) ? trim($_GET["ngay_tra_phong"]) : "";
$gioNhanPhong = isset($_GET["gio_nhan_phong"]) ? trim($_GET["gio_nhan_phong"]) : "14:00";
$gioTraPhong = isset($_GET["gio_tra_phong"]) ? trim($_GET["gio_tra_phong"]) : "12:00";
$soKhach = isset($_GET["so_khach"]) ? (int) $_GET["so_khach"] : 2;
$loiNgay = "";
$loiTaiPhong = "";
$coTimKiem = isset($_GET["ngay_nhan_phong"]) || isset($_GET["ngay_tra_phong"]) || isset($_GET["so_khach"]);

if ($soKhach < 1) {
    $soKhach = 1;
}

$goiYNgay = array();
$goiYChiaPhong = array();

if ($ngayNhanPhong !== '' && $ngayTraPhong !== '') {
    $loiTimKiem = validate_booking_window($ngayNhanPhong, $gioNhanPhong, $ngayTraPhong, $gioTraPhong, 'gio', BOOKING_MIN_LEAD_SECONDS);
    if (!empty($loiTimKiem)) {
        $loiNgay = 'Thời gian tìm phòng phải lớn hơn hiện tại ít nhất 1 giờ và ngày trả phải sau ngày nhận.';
        $ngayNhanPhong = '';
        $ngayTraPhong = '';
    }
}

if (($ngayNhanPhong == "" && $ngayTraPhong != "") || ($ngayNhanPhong != "" && $ngayTraPhong == "")) {
    $loiNgay = "Nhập giúp tụi mình đủ ngày nhận và ngày trả nha, chỉ một nửa lịch thì hệ thống không biết lọc sao.";
    $ngayNhanPhong = "";
    $ngayTraPhong = "";
} elseif (!ngay_tim_kiem_hop_le($ngayNhanPhong, $ngayTraPhong, $gioNhanPhong, $gioTraPhong)) {
    $loiNgay = "Ngày trả phòng phải sau ngày nhận phòng. Mình đi biển vui thôi, đừng đảo ngược thời gian nha.";
    $ngayNhanPhong = "";
    $ngayTraPhong = "";
}

$danhSachPhong = lay_phong_dang_hoat_dong($conn, $ngayNhanPhong, $ngayTraPhong, $soKhach, $gioNhanPhong, $gioTraPhong);
if ($ngayNhanPhong !== '' && $ngayTraPhong !== '' && count($danhSachPhong) === 0 && $conn !== null) {
    $soDem = max(1, (int) (new DateTimeImmutable($ngayNhanPhong))->diff(new DateTimeImmutable($ngayTraPhong))->days);
    $allRooms = lay_phong_dang_hoat_dong($conn, '', '', $soKhach);
    foreach ($allRooms as $room) {
        $lichGoiY = goi_y_ngay_trong_cho_phong($conn, (int) $room['id_phong'], $soDem, date('Y-m-d', strtotime($ngayTraPhong)), 45);
        if ($lichGoiY) $goiYNgay[] = array('phong' => $room, 'lich' => $lichGoiY);
    }
    $goiYChiaPhong = goi_y_chia_phong_lien_tuc($conn, $ngayNhanPhong, $ngayTraPhong, $soKhach);
}
include "includes/header.php";
?>

<!-- ─── Hero uốn lượn ────────────────────────────────────────────────── -->
<section class="rv-hero">
    <div class="rv-hero-inner">
        <p class="eyebrowlight">OASIS Homestay</p>
        <h1>Chọn một căn phòng vừa ngắm biển, vừa nghỉ cho đúng mood Hòn Sơn</h1>
        <p class="rv-hero-sub">
            Nhập ngày ở và số khách, tụi mình sẽ lọc ra những phòng còn trống trong đúng khung thời gian đó.
        </p>
    </div>
    <div class="rv-wave" aria-hidden="true"></div>
</section>

<main>

    <section class="search-box search-box-normal">
        <h2>Tìm phòng còn trống</h2>
        <form method="get" action="rooms.php">
            <div class="form-row">
                <div class="search-left">
                    <div class="search-left-row">
                        <label for="ngay_nhan_phong">
                            Ngày nhận phòng
                            <input id="ngay_nhan_phong" type="date" name="ngay_nhan_phong" min="<?php echo date('Y-m-d'); ?>" value="<?php echo h($ngayNhanPhong); ?>">
                        </label>

                        <label for="gio_nhan_phong">
                            Giờ nhận phòng
                            <input id="gio_nhan_phong" type="time" name="gio_nhan_phong" value="<?php echo h($gioNhanPhong); ?>">
                        </label>
                    </div>

                    <div class="search-left-row">
                        <label for="ngay_tra_phong">
                            Ngày trả phòng
                            <input id="ngay_tra_phong" type="date" name="ngay_tra_phong" min="<?php echo date('Y-m-d'); ?>" value="<?php echo h($ngayTraPhong); ?>">
                        </label>

                        <label for="gio_tra_phong">
                            Giờ trả phòng
                            <input id="gio_tra_phong" type="time" name="gio_tra_phong" value="<?php echo h($gioTraPhong); ?>">
                        </label>
                    </div>
                </div>

                <div class="search-right">
                    <label for="so_khach">
                        Số khách
                        <input id="so_khach" class="search-right-label" type="number" name="so_khach" min="1" value="<?php echo h($soKhach); ?>">
                    </label>
                    <div class="search-right-placeholder" aria-hidden="true"></div>
                    <button type="submit" class="big-search-button">Tìm phòng còn trống</button>
                </div>
            </div>
        </form>

        <?php if ($dbError != "") { ?>
            <p class="alert"><?php echo h($dbError); ?></p>
        <?php } ?>

        <?php if ($loiNgay != "") { ?>
            <p class="alert"><?php echo h($loiNgay); ?></p>
        <?php } ?>

        <?php if ($loiTaiPhong != "") { ?>
            <p class="alert"><?php echo h($loiTaiPhong); ?></p>
        <?php } ?>
    </section>

    <section class="section" id="rooms">
        <div class="section-title">
            <p class="eyebrow">Phòng lưu trú</p>
            <h2>
                <?php if ($ngayNhanPhong != "" && $ngayTraPhong != "") { ?>
                    Phòng còn trống từ <?php echo h(dinh_dang_ngay_gio($ngayNhanPhong, $gioNhanPhong)); ?> đến <?php echo h(dinh_dang_ngay_gio($ngayTraPhong, $gioTraPhong)); ?>
                <?php } else { ?>
                    Kết quả tìm kiếm theo sức chứa <?php echo h($soKhach); ?> khách
                <?php } ?>
            </h2>
        </div>

        <div class="room-list">
            <?php if (count($danhSachPhong) > 0) { ?>
                <?php foreach ($danhSachPhong as $phong) { 
                    $dsTienNhi = danh_sach_tien_nghi_phong($phong);
                    $h1 = (!empty($phong["duong_dan_hinh"]) && file_exists(__DIR__ . '/' . ltrim($phong["duong_dan_hinh"], '/'))) ? asset_url($phong["duong_dan_hinh"]) : '';
                    $h2 = (!empty($phong["hinh_2"]) && file_exists(__DIR__ . '/' . ltrim($phong["hinh_2"], '/'))) ? asset_url($phong["hinh_2"]) : '';
                    $h3 = (!empty($phong["hinh_3"]) && file_exists(__DIR__ . '/' . ltrim($phong["hinh_3"], '/'))) ? asset_url($phong["hinh_3"]) : '';
                    $h4 = (!empty($phong["hinh_4"]) && file_exists(__DIR__ . '/' . ltrim($phong["hinh_4"], '/'))) ? asset_url($phong["hinh_4"]) : '';
                ?>
                    <article class="room-card room-card-clickable"
                        data-id="<?php echo h($phong["id_phong"]); ?>"
                        data-ma="<?php echo h($phong["ma_so_phong"]); ?>"
                        data-ten="<?php echo h($phong["ten_phong"]); ?>"
                        data-loai="<?php echo h($phong["ten_loai_phong"]); ?>"
                        data-mota="<?php echo h($phong["mo_ta"]); ?>"
                        data-suc-chua="<?php echo (int)$phong["suc_chua"]; ?>"
                        data-gia-ngay="<?php echo (float)$phong["gia_theo_ngay"]; ?>"
                        data-gia-gio="<?php echo (float)$phong["gia_theo_gio"]; ?>"
                        data-hinh="<?php echo h($h1); ?>"
                        data-hinh2="<?php echo h($h2); ?>"
                        data-hinh3="<?php echo h($h3); ?>"
                        data-hinh4="<?php echo h($h4); ?>"
                        data-tien-nghi-json='<?php echo h(json_encode($dsTienNhi, JSON_UNESCAPED_UNICODE)); ?>'
                        role="button"
                        tabindex="0"
                        aria-label="Xem chi tiết phòng <?php echo h($phong["ten_phong"]); ?>">
                        <div class="room-image" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;">
                            <?php if ($h1) { ?>
                                <img src="<?php echo h($h1); ?>" alt="<?php echo h($phong["ten_phong"]); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php } ?>
                            <div class="room-img-placeholder" style="display: <?php echo $h1 ? 'none' : 'flex'; ?>; width:100%; height:100%; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; position:absolute; inset:0;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            </div>
                            <div class="room-card-hover-overlay" aria-hidden="true">
                                <span class="room-card-hover-label">Xem chi tiết</span>
                            </div>
                        </div>
                        <div class="room-info">
                            <div class="room-heading">
                                <p class="badge"><?php echo h($phong["ma_so_phong"]); ?></p>
                                <h3><?php echo h($phong["ten_phong"]); ?></h3>
                            </div>
                            <p><?php echo h($phong["mo_ta"]); ?></p>
                            <p class="room-meta">
                                <span>Loại phòng: <?php echo h($phong["ten_loai_phong"]); ?></span>
                                <span>Sức chứa: <?php echo h($phong["suc_chua"]); ?> khách</span>
                            </p>

                            <p class="price"><?php echo tien_viet_nam($phong["gia_theo_ngay"]); ?> / đêm</p>
                            <p class="muted"><?php echo tien_viet_nam($phong["gia_theo_gio"]); ?> / giờ</p>
                            <p class="room-card-cta">Nhấn để xem chi tiết &amp; tính giá <span class="room-card-arrow" aria-hidden="true">→</span></p>
                        </div>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <p class="empty">Chưa thấy phòng nào hợp lịch này. Thử đổi ngày hoặc giảm số khách một chút nha.</p>
                <?php if ($goiYNgay) { ?>
                    <section class="suggestion-card"><h3>Gợi ý ngày trống tiếp theo</h3><p>Phòng bạn tìm vẫn có thể đặt vào các lịch sau:</p>
                        <?php foreach ($goiYNgay as $suggest) { ?><div class="suggestion-row"><strong><?php echo h($suggest['phong']['ten_phong']); ?></strong><span><?php foreach ($suggest['lich'] as $idx => $slot) { if ($idx) echo ' · '; ?><a href="rooms.php?ngay_nhan_phong=<?php echo h($slot['tu']); ?>&ngay_tra_phong=<?php echo h($slot['den']); ?>&gio_nhan_phong=14:00&gio_tra_phong=12:00&so_khach=<?php echo (int) $soKhach; ?>#rooms"><?php echo h(dinh_dang_ngay($slot['tu'])); ?> - <?php echo h(dinh_dang_ngay($slot['den'])); ?></a><?php } ?></span></div><?php } ?>
                    </section>
                <?php } ?>
                <?php if ($goiYChiaPhong) { ?><section class="suggestion-card"><h3>Gợi ý chia phòng cho <?php echo (int) ((new DateTimeImmutable($ngayNhanPhong))->diff(new DateTimeImmutable($ngayTraPhong))->days); ?> đêm</h3><p>Không có một phòng liền mạch, nhưng có thể chuyển phòng giữa hành trình:</p><div class="suggestion-timeline"><?php foreach ($goiYChiaPhong as $segment) { ?><div><strong><?php echo h($segment['ten_phong']); ?> (<?php echo h($segment['ma_so_phong']); ?>)</strong><span><?php echo h(dinh_dang_ngay($segment['tu'])); ?> → <?php echo h(dinh_dang_ngay($segment['den'])); ?> · <?php echo (int) $segment['so_dem']; ?> đêm</span></div><?php } ?></div></section><?php } ?>
            <?php } ?>
        </div>
    </section>
</main>

<!-- ROOM DETAIL MODAL (fixed, giữa màn hình) -->
<div id="room-modal" class="room-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
    <div class="room-modal-box">

        <div class="room-modal-image-wrap" style="position:relative;">
            <!-- Gallery slider -->
            <div id="modal-gallery" style="position:relative; width:100%; height:100%; overflow:hidden;">
                <img id="modal-img" src="" alt="" class="room-modal-img" style="display:block; width:100%; height:100%; object-fit:cover;">
                <!-- Prev / Next -->
                <button id="gallery-prev" aria-label="Ảnh trước" style="display:none; position:absolute; left:10px; top:50%; transform:translateY(-50%); background:rgba(0,0,0,0.45); color:#fff; border:none; border-radius:50%; width:36px; height:36px; font-size:18px; cursor:pointer; z-index:5; align-items:center; justify-content:center;">&#8249;</button>
                <button id="gallery-next" aria-label="Ảnh sau"   style="display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); background:rgba(0,0,0,0.45); color:#fff; border:none; border-radius:50%; width:36px; height:36px; font-size:18px; cursor:pointer; z-index:5; align-items:center; justify-content:center;">&#8250;</button>
                <!-- Dots -->
                <div id="gallery-dots" style="display:none; position:absolute; bottom:10px; left:50%; transform:translateX(-50%); gap:6px;"></div>
            </div>
            <span class="room-modal-badge" id="modal-ma"></span>
            <button class="room-modal-close" id="modal-close-btn" aria-label="Đóng">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="room-modal-body">
            <p class="eyebrow" id="modal-loai"></p>
            <h2 class="room-modal-title" id="modal-title"></h2>
            <p class="room-modal-desc" id="modal-mota"></p>
            <div class="room-modal-meta" id="modal-meta"></div>

            <div class="room-modal-amenities-section" style="margin-top: 14px;">
                <h4 style="margin:0 0 8px; font-size:0.95rem; color:#475569; font-weight:600; text-transform:uppercase; letter-spacing:0.03em;">Tiện nghi phòng</h4>
                <div id="modal-amenities" style="display:flex; flex-wrap:wrap; gap:8px 14px; color:#1e293b; font-size:0.9rem;"></div>
            </div>

            <hr class="room-modal-divider">

            <!-- AVAILABILITY CALENDAR -->
            <div class="room-modal-calendar-section">
                <h3 class="room-modal-calc-title">Lịch tình trạng phòng</h3>
                <div class="room-cal-legend">
                    <span class="room-cal-legend-item room-cal-legend-avail">Còn trống</span>
                    <span class="room-cal-legend-item room-cal-legend-booked">Đã đặt</span>
                    <span class="room-cal-legend-item room-cal-legend-today">Hôm nay</span>
                </div>
                <div id="modal-calendar" class="room-cal-wrap">
                    <div class="room-cal-loading">Đang tải lịch…</div>
                </div>
            </div>

            <hr class="room-modal-divider">

            <!-- BOOKING CALCULATOR -->
            <div class="room-modal-calc">
                <h3 class="room-modal-calc-title">Tính chi phí lưu trú</h3>
                <div class="room-modal-options">
                    <div class="room-modal-field">
                        <label class="room-modal-label">Loại thuê</label>
                        <div class="room-modal-type-toggle">
                            <button type="button" class="type-btn type-btn-active" id="btn-theo-dem" data-type="dem">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                                Theo đêm
                            </button>
                            <button type="button" class="type-btn" id="btn-theo-gio" data-type="gio">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Theo giờ
                            </button>
                        </div>
                    </div>
                    <div class="room-modal-field" id="field-so-luong">
                        <label for="modal-so-luong" class="room-modal-label" id="label-so-luong">Số đêm</label>
                        <div class="room-modal-stepper">
                            <button type="button" class="stepper-btn" id="modal-luong-minus" aria-label="Giảm">−</button>
                            <input type="number" id="modal-so-luong" min="1" value="1" class="stepper-input" readonly>
                            <button type="button" class="stepper-btn" id="modal-luong-plus" aria-label="Tăng">+</button>
                        </div>
                    </div>
                </div>
                <div class="room-modal-total-wrap">
                    <div class="room-modal-total-label">Tổng chi phí ước tính</div>
                    <div class="room-modal-total-price" id="modal-total-price"> - </div>
                    <p class="room-modal-total-note">Giá tham khảo  -  liên hệ homestay để xác nhận và sắp xếp lịch.</p>
                </div>
            </div>

            <hr class="room-modal-divider">

            <!-- BOOK BUTTON -->
            <div class="room-modal-book-wrap">
                <a href="#" id="modal-book-btn" class="room-modal-book-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="18"/>
                        <line x1="10" y1="16" x2="14" y2="16"/>
                    </svg>
                    Đặt phòng ngay
                </a>
                <p class="room-modal-book-note">Điền form đặt phòng - OASIS sẽ xác nhận trong thời gian sớm nhất.</p>
            </div>

            <hr class="room-modal-divider">

            <!-- CONTACT -->
            <div class="room-modal-contact">
                <p class="room-modal-contact-title">Hoặc liên hệ trực tiếp</p>
                <div class="room-modal-contact-grid">
                    <a href="tel:0795247654" class="room-modal-contact-btn">
                        <span class="contact-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07A19.5 19.5 0 0 1 3.07 10.8 19.8 19.8 0 0 1 .24 2.18 2 2 0 0 1 2.23 0h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </span>
                        <span>
                            <strong>Điện thoại</strong>
                            <span class="contact-sub">0795 247 654</span>
                        </span>
                    </a>
                    <a href="mailto:Oasis@gmail.com" class="room-modal-contact-btn">
                        <span class="contact-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                        </span>
                        <span>
                            <strong>Email</strong>
                            <span class="contact-sub">Oasis@gmail.com</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Helpers ──────────────────────────────────────────────────────────────
    function fmtMoney(n) {
        return n.toLocaleString('vi-VN') + ' đồng';
    }

    // ── State ─────────────────────────────────────────────────────────────────
    var state = {
        giaGio: 0,
        giaNgay: 0,
        loaiThue: 'dem',
        soKhach: 2,
        soLuong: 1,
        sucChua: 2
    };

    // ── DOM refs ─────────────────────────────────────────────────────────────
    var overlay    = document.getElementById('room-modal');
    var closeBtn   = document.getElementById('modal-close-btn');
    var imgEl      = document.getElementById('modal-img');
    var maEl       = document.getElementById('modal-ma');
    var loaiEl     = document.getElementById('modal-loai');
    var titleEl    = document.getElementById('modal-title');
    var motaEl     = document.getElementById('modal-mota');
    var metaEl     = document.getElementById('modal-meta');
    var totalEl    = document.getElementById('modal-total-price');
    var inputLuong = document.getElementById('modal-so-luong');
    var labelLuong = document.getElementById('label-so-luong');
    var btnDem     = document.getElementById('btn-theo-dem');
    var btnGio     = document.getElementById('btn-theo-gio');
    var calWrap    = document.getElementById('modal-calendar');
    var bookBtn    = document.getElementById('modal-book-btn');
    var prevBtn    = document.getElementById('gallery-prev');
    var nextBtn    = document.getElementById('gallery-next');
    var dotsWrap   = document.getElementById('gallery-dots');
    var activeCard = null;

    // ── Gallery state ─────────────────────────────────────────────────────────
    var gallery = { imgs: [], cur: 0 };

    function getOrCreateModalPlaceholder() {
        var placeholder = document.getElementById('modal-img-placeholder');
        if (!placeholder) {
            placeholder = document.createElement('div');
            placeholder.id = 'modal-img-placeholder';
            placeholder.style.cssText = 'width:100%;height:100%;background:#f1f5f9;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;gap:8px;position:absolute;inset:0;';
            placeholder.innerHTML = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span style="font-size:0.85rem;font-weight:600;">Chưa có ảnh</span>';
            imgEl.parentElement.appendChild(placeholder);
        }
        return placeholder;
    }

    imgEl.onerror = function() {
        this.style.display = 'none';
        getOrCreateModalPlaceholder().style.display = 'flex';
    };

    function showGallerySlide(idx) {
        if (!gallery.imgs.length) return;
        idx = (idx + gallery.imgs.length) % gallery.imgs.length;
        gallery.cur = idx;
        var placeholder = document.getElementById('modal-img-placeholder');
        if (placeholder) placeholder.style.display = 'none';
        imgEl.style.display = 'block';
        imgEl.src = gallery.imgs[idx];
        // update dots
        var dots = dotsWrap.querySelectorAll('span');
        dots.forEach(function (d, i) {
            d.style.background = i === idx ? '#fff' : 'rgba(255,255,255,0.45)';
        });
    }

    function initGallery(images) {
        gallery.imgs = images.filter(function (u) { return u && u !== ''; });
        gallery.cur  = 0;
        var hasMany  = gallery.imgs.length > 1;
        prevBtn.style.display = hasMany ? 'flex' : 'none';
        nextBtn.style.display = hasMany ? 'flex' : 'none';
        dotsWrap.style.display = hasMany ? 'flex' : 'none';
        // build dots
        dotsWrap.innerHTML = '';

        if (gallery.imgs.length > 0) {
            gallery.imgs.forEach(function (_, i) {
                var dot = document.createElement('span');
                dot.style.cssText = 'width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,0.45);cursor:pointer;';
                dot.addEventListener('click', function () { showGallerySlide(i); });
                dotsWrap.appendChild(dot);
            });
            showGallerySlide(0);
        } else {
            imgEl.style.display = 'none';
            getOrCreateModalPlaceholder().style.display = 'flex';
        }
    }

    prevBtn.addEventListener('click', function (e) { e.stopPropagation(); showGallerySlide(gallery.cur - 1); });
    nextBtn.addEventListener('click', function (e) { e.stopPropagation(); showGallerySlide(gallery.cur + 1); });

    // swipe support
    (function () {
        var touchStartX = 0;
        imgEl.addEventListener('touchstart', function (e) { touchStartX = e.touches[0].clientX; }, { passive: true });
        imgEl.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(dx) > 40) showGallerySlide(gallery.cur + (dx < 0 ? 1 : -1));
        });
    })();

    // ── Calculate & render total ──────────────────────────────────────────────
    function renderTotal() {
        var donGia = state.loaiThue === 'dem' ? state.giaNgay : state.giaGio;
        var total  = donGia * state.soLuong;
        totalEl.textContent = fmtMoney(total);
    }

    // ── Stepper helper ────────────────────────────────────────────────────────
    function bindStepper(minusId, plusId, inputEl, getVal, setVal, min, max) {
        document.getElementById(minusId).addEventListener('click', function () {
            var v = getVal();
            if (v > min) { setVal(v - 1); inputEl.value = getVal(); renderTotal(); }
        });
        document.getElementById(plusId).addEventListener('click', function () {
            var v = getVal();
            if (!max || v < max) { setVal(v + 1); inputEl.value = getVal(); renderTotal(); }
        });
    }

    bindStepper(
        'modal-luong-minus', 'modal-luong-plus', inputLuong,
        function () { return state.soLuong; },
        function (v) { state.soLuong = v; },
        1,
        null
    );

    // ── Toggle booking type ───────────────────────────────────────────────────
    function setLoai(loai) {
        state.loaiThue = loai;
        if (loai === 'dem') {
            btnDem.classList.add('type-btn-active');
            btnGio.classList.remove('type-btn-active');
            labelLuong.textContent = 'Số đêm';
        } else {
            btnGio.classList.add('type-btn-active');
            btnDem.classList.remove('type-btn-active');
            labelLuong.textContent = 'Số giờ';
        }
        renderTotal();
    }

    btnDem.addEventListener('click', function () { setLoai('dem'); });
    btnGio.addEventListener('click', function () { setLoai('gio'); });

    // ── Calendar helpers ──────────────────────────────────────────────────────
    var THANG_VI = ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6',
                    'Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];
    var THU_VI   = ['CN','T2','T3','T4','T5','T6','T7'];

    function localDateKey(dt) {
        return dt.getFullYear() + '-' +
            String(dt.getMonth() + 1).padStart(2, '0') + '-' +
            String(dt.getDate()).padStart(2, '0');
    }

    function formatTimeLabel(startHour, startMin, endHour, endMin) {
        function fmtTime(hour, min) {
            return String(hour) + ':' + String(min).padStart(2, '0');
        }
        return fmtTime(startHour, startMin) + ' - ' + fmtTime(endHour, endMin);
    }

    function buildBookedTooltip(entries) {
        if (!entries || !entries.length) return 'Đã đặt';
        var lines = ['Đã đặt vào các giờ:'];
        entries.forEach(function (entry) {
            lines.push('- ' + entry.time);
        });
        return lines.join('\n');
    }

    function buildBookedMap(datList) {
        var bookedMap = {};

        datList.forEach(function (d) {
            var tuParts  = d.tu.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
            var denParts = d.den.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
            if (!tuParts || !denParts) return;

            var startYear  = parseInt(tuParts[1], 10);
            var startMonth = parseInt(tuParts[2], 10);
            var startDay   = parseInt(tuParts[3], 10);
            var startHour  = parseInt(tuParts[4], 10);
            var startMin   = parseInt(tuParts[5], 10);
            var endYear    = parseInt(denParts[1], 10);
            var endMonth   = parseInt(denParts[2], 10);
            var endDay     = parseInt(denParts[3], 10);
            var endHour    = parseInt(denParts[4], 10);
            var endMin     = parseInt(denParts[5], 10);

            var cur = new Date(startYear, startMonth - 1, startDay, 0, 0, 0);
            var loopEnd = new Date(endYear, endMonth - 1, endDay, 0, 0, 0);

            if (loopEnd <= cur) {
                loopEnd.setDate(loopEnd.getDate() + 1);
            } else if (endHour > 0 || endMin > 0) {
                loopEnd.setDate(loopEnd.getDate() + 1);
            }

            while (cur < loopEnd) {
                var key = localDateKey(cur);
                var isStartDay = cur.getFullYear() === startYear && cur.getMonth() === startMonth - 1 && cur.getDate() === startDay;
                var isEndDay = cur.getFullYear() === endYear && cur.getMonth() === endMonth - 1 && cur.getDate() === endDay;
                var slotStartHour = isStartDay ? startHour : 0;
                var slotStartMin = isStartDay ? startMin : 0;
                var slotEndHour = isEndDay ? endHour : 23;
                var slotEndMin = isEndDay ? endMin : 59;
                var timeLabel = formatTimeLabel(slotStartHour, slotStartMin, slotEndHour, slotEndMin);

                if (!bookedMap[key]) bookedMap[key] = [];
                bookedMap[key].push({ time: timeLabel });
                cur.setDate(cur.getDate() + 1);
            }
        });
        return bookedMap;
    }

    /** Render 3 months of calendar starting from today. */
    function renderCalendar(datList) {
        var map    = buildBookedMap(datList);
        var today  = new Date();
        today.setHours(0, 0, 0, 0);
        var todayKey = localDateKey(today);

        var html = '';
        var MONTHS = 3;
        for (var m = 0; m < MONTHS; m++) {
            var ref = new Date(today.getFullYear(), today.getMonth() + m, 1);
            var year = ref.getFullYear();
            var month = ref.getMonth();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var firstDow = new Date(year, month, 1).getDay();

            html += '<div class="room-cal-month">';
            html += '<div class="room-cal-month-title">' + THANG_VI[month] + ' ' + year + '</div>';
            html += '<div class="room-cal-grid">';

            THU_VI.forEach(function (t) {
                html += '<div class="room-cal-dow">' + t + '</div>';
            });

            for (var e = 0; e < firstDow; e++) {
                html += '<div class="room-cal-day room-cal-empty"></div>';
            }

            for (var d = 1; d <= daysInMonth; d++) {
                var dateKey = year + '-' +
                    String(month + 1).padStart(2, '0') + '-' +
                    String(d).padStart(2, '0');
                var cls = 'room-cal-day';
                var tip = '';
                var inner = String(d);

                var cellDate = new Date(year, month, d);
                cellDate.setHours(0, 0, 0, 0);

                if (dateKey === todayKey) {
                    cls += ' room-cal-today';
                    tip = 'Hôm nay';
                    if (map[dateKey]) {
                        cls += ' room-cal-booked';
                        tip = buildBookedTooltip(map[dateKey]);
                    }
                } else if (cellDate < today) {
                    cls += ' room-cal-past';
                } else if (map[dateKey]) {
                    cls += ' room-cal-booked';
                    tip = buildBookedTooltip(map[dateKey]);
                } else {
                    cls += ' room-cal-avail';
                    tip = 'Còn trống';
                }
                var tipAttr = tip ? ' title="' + tip.replace(/"/g, '&quot;').replace(/\n/g, '&#10;') + '"' : '';
                html += '<div class="' + cls + '"' + tipAttr + '>' + inner + '</div>';
            }

            html += '</div></div>';
        }
        calWrap.innerHTML = html;
    }

    function loadCalendar(idPhong) {
        calWrap.innerHTML = '<div class="room-cal-loading">Đang tải lịch…</div>';
        var cacheBuster = '&t=' + new Date().getTime();
        fetch('rooms.php?action=calendar&id=' + idPhong + '&months=3' + cacheBuster, {
            cache: 'no-store'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    renderCalendar(data.dat);
                } else {
                    calWrap.innerHTML = '<p class="room-cal-err">Không tải được lịch.</p>';
                }
            })
            .catch(function () {
                calWrap.innerHTML = '<p class="room-cal-err">Lỗi kết nối.</p>';
            });
    }

    // ── Open modal ────────────────────────────────────────────────────────────
    function openModal(card) {
        if (activeCard) activeCard.classList.remove('room-card-active');
        activeCard = card;
        card.classList.add('room-card-active');

        var id      = card.dataset.id;
        var ma      = card.dataset.ma;
        var ten     = card.dataset.ten;
        var loai    = card.dataset.loai;
        var mota    = card.dataset.mota;
        var sucChua = parseInt(card.dataset.sucChua, 10);
        var giaNgay = parseFloat(card.dataset.giaNgay);
        var giaGio  = parseFloat(card.dataset.giaGio);
        var hinh    = card.dataset.hinh;
        var hinh2   = card.dataset.hinh2 || '';
        var hinh3   = card.dataset.hinh3 || '';
        var hinh4   = card.dataset.hinh4 || '';

        state.giaGio  = giaGio;
        state.giaNgay = giaNgay;
        state.soLuong = 1;

        initGallery([hinh, hinh2, hinh3, hinh4]);
        imgEl.alt             = ten;
        maEl.textContent      = ma;
        loaiEl.textContent    = loai;
        titleEl.textContent   = ten;
        motaEl.textContent    = mota;
        metaEl.innerHTML      = 'Sức chứa: <strong>&nbsp; ' + sucChua + ' khách </strong>' +
                                '  &nbsp; &nbsp;  Giá một đêm: <strong>&nbsp; ' + fmtMoney(giaNgay) + '</strong>' +
                                '  &nbsp; &nbsp;  Giá một giờ: <strong>&nbsp; ' + fmtMoney(giaGio) + '</strong>';

        var amenitiesEl = document.getElementById('modal-amenities');
        if (amenitiesEl) {
            var tnList = [];
            try {
                tnList = JSON.parse(card.dataset.tienNghiJson || '[]');
            } catch (e) {}
            var html = '';
            if (tnList && tnList.length) {
                tnList.forEach(function(tn) {
                    html += '<span style="display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; padding:5px 10px; border-radius:6px; font-weight:500;">' +
                            '<span style="display:inline-flex; align-items:center; color:#0f172a;">' + tn.icon + '</span>' +
                            '<span>' + tn.label + '</span>' +
                            '</span>';
                });
            } else {
                html = '<span style="color:#64748b;">Chưa cập nhật tiện nghi</span>';
            }
            amenitiesEl.innerHTML = html;
        }

        inputLuong.value = 1;
        setLoai('dem');

        // Load calendar for this room
        loadCalendar(id);

        // Build booking URL
        if (bookBtn) {
            var searchNhan = document.getElementById('ngay_nhan_phong');
            var searchTra  = document.getElementById('ngay_tra_phong');
            var searchGioN = document.getElementById('gio_nhan_phong');
            var searchGioT = document.getElementById('gio_tra_phong');
            var bkUrl = 'booking.php?id=' + id;
            if (searchNhan && searchNhan.value) bkUrl += '&ngay_nhan=' + encodeURIComponent(searchNhan.value);
            if (searchTra  && searchTra.value)  bkUrl += '&ngay_tra='  + encodeURIComponent(searchTra.value);
            if (searchGioN && searchGioN.value) bkUrl += '&gio_nhan='  + encodeURIComponent(searchGioN.value);
            if (searchGioT && searchGioT.value) bkUrl += '&gio_tra='   + encodeURIComponent(searchGioT.value);
            bookBtn.href = bkUrl;
        }

        overlay.hidden = false;
        document.body.classList.add('modal-open');
        closeBtn.focus();
    }

    // ── Close modal ───────────────────────────────────────────────────────────
    function closeModal() {
        overlay.hidden = true;
        document.body.classList.remove('modal-open');
        if (activeCard) { activeCard.classList.remove('room-card-active'); activeCard = null; }
    }

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !overlay.hidden) closeModal();
    });

    // ── Bind cards ────────────────────────────────────────────────────────────
    var cards = document.querySelectorAll('.room-card-clickable');
    cards.forEach(function (card) {
        card.addEventListener('click', function () { openModal(card); });
        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(card); }
        });
    });
}());
</script>

<?php include "includes/footer.php"; ?>
