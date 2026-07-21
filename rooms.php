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
                <?php foreach ($danhSachPhong as $phong) { ?>
                    <article class="room-card room-card-clickable"
                        data-id="<?php echo h($phong["id_phong"]); ?>"
                        data-ma="<?php echo h($phong["ma_so_phong"]); ?>"
                        data-ten="<?php echo h($phong["ten_phong"]); ?>"
                        data-loai="<?php echo h($phong["ten_loai_phong"]); ?>"
                        data-mota="<?php echo h($phong["mo_ta"]); ?>"
                        data-suc-chua="<?php echo (int)$phong["suc_chua"]; ?>"
                        data-gia-ngay="<?php echo (float)$phong["gia_theo_ngay"]; ?>"
                        data-gia-gio="<?php echo (float)$phong["gia_theo_gio"]; ?>"
                        data-hinh="<?php echo h($phong["duong_dan_hinh"]); ?>"
                        role="button"
                        tabindex="0"
                        aria-label="Xem chi tiết phòng <?php echo h($phong["ten_phong"]); ?>">
                        <div class="room-image">
                            <img src="<?php echo h($phong["duong_dan_hinh"]); ?>" alt="<?php echo h($phong["ten_phong"]); ?>">
                            <div class="room-card-hover-overlay" aria-hidden="true">
                                <span class="room-card-hover-label">Xem chi tiết</span>
                            </div>
                        </div>
                        <div class="room-info">
                            <p class="badge"><?php echo h($phong["ma_so_phong"]); ?></p>
                            <h3><?php echo h($phong["ten_phong"]); ?></h3>
                            <p><?php echo h($phong["mo_ta"]); ?></p>
                            <p class="room-meta">
                                Loại phòng: <?php echo h($phong["ten_loai_phong"]); ?> |
                                Sức chứa: <?php echo h($phong["suc_chua"]); ?> khách
                            </p>
                            <p class="price"><?php echo tien_viet_nam($phong["gia_theo_ngay"]); ?> / đêm</p>
                            <p class="muted"><?php echo tien_viet_nam($phong["gia_theo_gio"]); ?> / giờ</p>
                            <p class="room-card-cta">Nhấn để xem chi tiết &amp; tính giá →</p>
                        </div>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <p class="empty">Chưa thấy phòng nào hợp lịch này. Thử đổi ngày hoặc giảm số khách một chút nha.</p>
            <?php } ?>
        </div>
    </section>
</main>

<!-- ROOM DETAIL MODAL (fixed, giữa màn hình) -->
<div id="room-modal" class="room-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
    <div class="room-modal-box">

        <div class="room-modal-image-wrap">
            <img id="modal-img" src="" alt="" class="room-modal-img">
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

            <hr class="room-modal-divider">

            <!-- AVAILABILITY CALENDAR -->
            <div class="room-modal-calendar-section">
                <h3 class="room-modal-calc-title">Lịch tình trạng phòng</h3>
                <div class="room-cal-legend">
                    <span class="room-cal-legend-item room-cal-legend-avail">Còn trống</span>
                    <span class="room-cal-legend-item room-cal-legend-booked">Đã đặt (cả ngày)</span>
                    <span class="room-cal-legend-item room-cal-legend-booked-gio">Đã đặt (theo giờ)</span>
                    <span class="room-cal-legend-item room-cal-legend-pending">Chờ xác nhận</span>
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

            <!-- CONTACT -->
            <div class="room-modal-contact">
                <p class="room-modal-contact-title">Liên hệ đặt phòng trực tiếp</p>
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
    var activeCard = null;

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

    /** Build booked map: key='YYYY-MM-DD' -> array of {tu, den, kieu, trang_thai} */
    function buildBookedMap(datList) {
        var bookedMap  = {};
        var pendingMap = {};

        datList.forEach(function (d) {
            var start = new Date(d.tu.replace(' ', 'T'));
            var end   = new Date(d.den.replace(' ', 'T'));

            // For hourly bookings (same day) just mark that one day
            var cur = new Date(start);
            cur.setHours(0, 0, 0, 0);
            var loopEnd = new Date(end);
            loopEnd.setHours(0, 0, 0, 0);
            // If same-day (hourly), loopEnd === cur so we add 1 day to mark it
            if (loopEnd <= cur) loopEnd.setDate(loopEnd.getDate() + 1);

            // Format helpers
            function fmtTime(dt) {
                return String(dt.getHours()).padStart(2,'0') + ':' + String(dt.getMinutes()).padStart(2,'0');
            }
            function fmtDate(dt) {
                return String(dt.getDate()).padStart(2,'0') + '/' + String(dt.getMonth()+1).padStart(2,'0');
            }
            var isGio = d.kieu === 'gio';
            // Theo giờ → hiện giờ cụ thể; theo ngày → hiện khoảng ngày
            var timeLabel = isGio
                ? (fmtTime(start) + ' \u2013 ' + fmtTime(end))
                : (fmtDate(start) + ' \u2013 ' + fmtDate(end));


            while (cur < loopEnd) {
                var key = cur.toISOString().slice(0, 10);
                var info = { time: timeLabel, gio: isGio };
                if (d.trang_thai === 'cho_xac_nhan') {
                    if (!pendingMap[key]) pendingMap[key] = [];
                    pendingMap[key].push(info);
                } else {
                    if (!bookedMap[key]) bookedMap[key] = [];
                    bookedMap[key].push(info);
                }
                cur.setDate(cur.getDate() + 1);
            }
        });
        return { booked: bookedMap, pending: pendingMap };
    }

    /** Render 3 months of calendar starting from today. */
    function renderCalendar(datList) {
        var map    = buildBookedMap(datList);
        var today  = new Date();
        today.setHours(0, 0, 0, 0);
        var todayKey = today.toISOString().slice(0, 10);

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

                if (dateKey === todayKey) {
                    cls += ' room-cal-today';
                    tip = 'Hôm nay';
                } else if (map.booked[dateKey]) {
                    var infos = map.booked[dateKey];
                    var allGio = infos.every(function(x){ return x.gio; });
                    cls += allGio ? ' room-cal-booked-gio' : ' room-cal-booked';
                    tip = allGio
                        ? infos.map(function(x){ return 'Đã đặt theo giờ: ' + x.time; }).join(' | ')
                        : 'Đã đặt';
                    if (allGio) inner += '<span class="cal-dot cal-dot-booked"></span>';
                } else if (map.pending[dateKey]) {
                    var pinfos = map.pending[dateKey];
                    var pallGio = pinfos.every(function(x){ return x.gio; });
                    cls += pallGio ? ' room-cal-pending-gio' : ' room-cal-pending';
                    tip = pallGio
                        ? pinfos.map(function(x){ return 'Chờ xác nhận theo giờ: ' + x.time; }).join(' | ')
                        : 'Chờ xác nhận';
                    if (pallGio) inner += '<span class="cal-dot cal-dot-pending"></span>';
                } else if (new Date(dateKey) < today) {
                    cls += ' room-cal-past';
                } else {
                    cls += ' room-cal-avail';
                    tip = 'Còn trống';
                }
                var tipAttr = tip ? ' title="' + tip + '"' : '';
                html += '<div class="' + cls + '"' + tipAttr + '>' + inner + '</div>';
            }

            html += '</div></div>';
        }
        calWrap.innerHTML = html;
    }

    function loadCalendar(idPhong) {
        calWrap.innerHTML = '<div class="room-cal-loading">Đang tải lịch…</div>';
        fetch('rooms.php?action=calendar&id=' + idPhong + '&months=3')
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

        state.giaGio  = giaGio;
        state.giaNgay = giaNgay;
        state.soLuong = 1;

        imgEl.src             = hinh;
        imgEl.alt             = ten;
        maEl.textContent      = ma;
        loaiEl.textContent    = loai;
        titleEl.textContent   = ten;
        motaEl.textContent    = mota;
        metaEl.innerHTML      = 'Sức chứa: <strong>&nbsp; ' + sucChua + ' khách </strong>' +
                                '  &nbsp; &nbsp;  Giá một đêm: <strong>&nbsp; ' + fmtMoney(giaNgay) + '</strong>' +
                                '  &nbsp; &nbsp;  Giá một giờ: <strong>&nbsp; ' + fmtMoney(giaGio) + '</strong>';
        inputLuong.value = 1;
        setLoai('dem');

        // Load calendar for this room
        loadCalendar(id);

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
