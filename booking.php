<?php
$pageTitle = "Đặt phòng - OASIS Homestay";
include "includes/db.php";
include "includes/functions.php";

// ── Lấy tham số từ URL ────────────────────────────────────────────────────
date_default_timezone_set('Asia/Ho_Chi_Minh');
$now = new DateTimeImmutable('now');
$nowPlusOne = $now->modify('+1 hour');
if ((int)$nowPlusOne->format('i') > 0) {
    $nowPlusOne = $nowPlusOne->modify('+1 hour');
}
$nowPlusOne = $nowPlusOne->setTime((int)$nowPlusOne->format('H'), 0);
$defaultGioNhan = $nowPlusOne->format('H:i');
$defaultGioTra = $nowPlusOne->modify('+1 hour')->format('H:i');
$today = $now->format('Y-m-d');
$tomorrow = $now->modify('+1 day')->format('Y-m-d');
$dailyMin = ((int)$now->format('H') >= 13) ? $tomorrow : $today;
$hourlyMin = ($nowPlusOne->format('Y-m-d') === $today) ? $today : $tomorrow;
$defaultNgayNhan = $today;
$defaultNgayTra = $tomorrow;
$minDayBooking = $now->modify('+1 hour')->format('Y-m-d');

$idPhong  = isset($_GET['id'])        ? (int)$_GET['id']           : 0;
$ngayNhan = isset($_GET['ngay_nhan']) ? trim($_GET['ngay_nhan'])   : $defaultNgayNhan;
$ngayTra  = isset($_GET['ngay_tra'])  ? trim($_GET['ngay_tra'])    : $defaultNgayTra;
$gioNhan  = isset($_GET['gio_nhan'])  ? trim($_GET['gio_nhan'])    : $defaultGioNhan;
$gioTra   = isset($_GET['gio_tra'])   ? trim($_GET['gio_tra'])     : $defaultGioTra;

$today = date('Y-m-d');

// ── Lấy thông tin phòng ──────────────────────────────────────────────────
$phong = ($idPhong > 0) ? lay_phong_theo_id($conn, $idPhong) : null;
if ($phong === null) {
    header('Location: rooms.php');
    exit;
}

// ── Xử lý POST ──────────────────────────────────────────────────────────
$formSuccess = false;
$maDatPhong  = null;
$formError   = '';
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dat_phong') {
    $hoTen        = trim($_POST['ho_ten']        ?? '');
    $sdt          = trim($_POST['so_dien_thoai'] ?? '');
    $email        = trim($_POST['email']         ?? '');
    $diaChi       = trim($_POST['dia_chi']       ?? '');
    $ngayNhanPost = trim($_POST['ngay_nhan']     ?? '');
    $gioNhanPost  = trim($_POST['gio_nhan']      ?? $defaultGioNhan);
    $ngayTraPost  = trim($_POST['ngay_tra']      ?? '');
    $gioTraPost   = trim($_POST['gio_tra']       ?? $defaultGioTra);
    $kieuDat      = ($_POST['kieu_dat'] ?? 'ngay') === 'gio' ? 'gio' : 'ngay';
    $soLuong      = max(1, (int)($_POST['so_luong'] ?? 1));
    $ghiChu       = trim($_POST['ghi_chu']       ?? '');

    // Validate
    if ($hoTen === '') {
        $fieldErrors['ho_ten'] = 'Vui lòng nhập họ tên.';
    }
    if ($sdt === '') {
        $fieldErrors['so_dien_thoai'] = 'Vui lòng nhập số điện thoại.';
    } elseif (!preg_match('/^0[0-9]{9}$/', $sdt)) {
        $fieldErrors['so_dien_thoai'] = 'Số điện thoại không hợp lệ (10 chữ số, bắt đầu bằng 0).';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Email không hợp lệ.';
    }
    if ($ngayNhanPost === '') {
        $fieldErrors['ngay_nhan'] = 'Vui lòng chọn ngày nhận phòng.';
    } elseif ($ngayNhanPost < $today) {
        $fieldErrors['ngay_nhan'] = 'Ngày nhận phòng không được trước hôm nay.';
    }
    if ($ngayTraPost === '') {
        $fieldErrors['ngay_tra'] = 'Vui lòng chọn ngày trả phòng.';
    }
    if (empty($fieldErrors) && $ngayNhanPost !== '' && $ngayTraPost !== '') {
        $dtCheckObj = DateTimeImmutable::createFromFormat('Y-m-d H:i', $ngayNhanPost . ' ' . $gioNhanPost);
        $dtTraObj   = DateTimeImmutable::createFromFormat('Y-m-d H:i', $ngayTraPost . ' ' . $gioTraPost);

        if (!$dtCheckObj || !$dtTraObj) {
            $fieldErrors['ngay_nhan'] = 'Thời gian đặt phòng không hợp lệ.';
        } else {
            if ($dtTraObj <= $dtCheckObj) {
                $fieldErrors['ngay_tra'] = 'Thời gian trả phòng phải sau thời gian nhận phòng.';
            }

            $now = new DateTimeImmutable('now');
            $minBooking = $now->modify('+1 hour');
            if ($dtCheckObj < $minBooking) {
                $fieldErrors['ngay_nhan'] = 'Thời gian nhận phòng phải cách thời điểm hiện tại ít nhất 1 giờ.';
            }

            if ($kieuDat === 'ngay' && $ngayNhanPost === $today && (int)$now->format('H') >= 13) {
                $fieldErrors['ngay_nhan'] = 'Sau 13:00 hôm nay chỉ có thể đặt phòng bắt đầu từ ngày mai.';
            }
        }
    }

    if (empty($fieldErrors)) {
        $dtNhan   = tao_datetime_tu_ngay_gio($ngayNhanPost, $gioNhanPost);
        $dtTra    = tao_datetime_tu_ngay_gio($ngayTraPost,  $gioTraPost);
        $durationMinutes = (int) ceil((strtotime($dtTra) - strtotime($dtNhan)) / 60);
        $soLuong = $kieuDat === 'ngay'
            ? max(1, (int) ceil($durationMinutes / 1440))
            : max(1, (int) ceil($durationMinutes / 60));
        $tongTien = $soLuong * (float)($kieuDat === 'gio' ? $phong['gia_theo_gio'] : $phong['gia_theo_ngay']);

        if ($conn === null) {
            $formError = 'Không kết nối được cơ sở dữ liệu.';
        } else {
            $checkStmt = $conn->prepare(
                "SELECT 1 FROM dat_phong
                 WHERE id_phong = ?
                   AND trang_thai IN ('da_xac_nhan', 'da_nhan_phong')
                   AND ngay_nhan_phong < ?
                   AND ngay_tra_phong > ?
                 LIMIT 1"
            );
            if ($checkStmt) {
                $checkStmt->bind_param('iss', $idPhong, $dtTra, $dtNhan);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult && $checkResult->num_rows > 0) {
                    $formError = 'Phòng đã có người đặt trùng thời gian này. Vui lòng chọn khung khác.';
                }
                $checkStmt->close();
            }

            if ($formError === '') {
                $stmt = $conn->prepare(
                    "INSERT INTO dat_phong
                        (id_phong, ho_ten, so_dien_thoai, email, dia_chi,
                         ngay_nhan_phong, ngay_tra_phong, kieu_dat, so_luong, tong_tien, nguon_dat, trang_thai, ghi_chu)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'web', 'cho_xac_nhan', ?)"
                );
                $stmt->bind_param(
                    'isssssssids',
                    $idPhong, $hoTen, $sdt, $email, $diaChi,
                    $dtNhan, $dtTra, $kieuDat, $soLuong, $tongTien, $ghiChu
                );
                if ($stmt->execute()) {
                    $maDatPhong  = $stmt->insert_id;
                    $formSuccess = true;
                    $ngayNhan = $ngayNhanPost;
                    $ngayTra  = $ngayTraPost;
                    $gioNhan  = $gioNhanPost;
                    $gioTra   = $gioTraPost;
                } else {
                    $formError = 'Có lỗi xảy ra khi lưu đặt phòng, vui lòng thử lại.';
                }
                $stmt->close();
            }
        }
    } else {
        $ngayNhan = $ngayNhanPost;
        $ngayTra  = $ngayTraPost;
        $gioNhan  = $gioNhanPost;
        $gioTra   = $gioTraPost;
    }
}

include "includes/header.php";
?>

<!-- ── Hero ──────────────────────────────────────────────────────────── -->
<section class="bk-hero">
    <div class="bk-hero-inner">
        <p class="eyebrowlight">OASIS Homestay</p>
        <h1>Đặt phòng</h1>
        <p class="bk-hero-sub">Điền thông tin bên dưới - tụi mình sẽ liên hệ xác nhận và sắp xếp lịch cho bạn.</p>
    </div>
    <div class="rv-wave" aria-hidden="true"></div>
</section>

<main class="bk-main">

<?php if ($formSuccess): ?>
<!-- ── Thành công ─────────────────────────────────────────────────────── -->
<section class="bk-success-wrap">
    <div class="bk-success-box">
        <div class="bk-success-icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="9 12 11 14 15 10"/>
            </svg>
        </div>
        <h2 class="bk-success-title">Đặt phòng thành công!</h2>
        <p class="bk-success-sub">
            Yêu cầu của bạn đã được ghi nhận.<br>
            Homestay sẽ liên hệ xác nhận trong thời gian sớm nhất.
        </p>
        <div class="bk-success-meta">
            <div class="bk-success-meta-row">
                <span class="bk-meta-label">Mã đặt phòng</span>
                <span class="bk-meta-val bk-meta-code">#<?php echo str_pad($maDatPhong, 5, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="bk-success-meta-row">
                <span class="bk-meta-label">Phòng</span>
                <span class="bk-meta-val"><?php echo h($phong['ten_phong']); ?> (<?php echo h($phong['ma_so_phong']); ?>)</span>
            </div>
            <div class="bk-success-meta-row">
                <span class="bk-meta-label">Nhận phòng</span>
                <span class="bk-meta-val"><?php echo h(dinh_dang_ngay_gio($ngayNhan, $gioNhan)); ?></span>
            </div>
            <div class="bk-success-meta-row">
                <span class="bk-meta-label">Trả phòng</span>
                <span class="bk-meta-val"><?php echo h(dinh_dang_ngay_gio($ngayTra, $gioTra)); ?></span>
            </div>
            <div class="bk-success-meta-row">
                <span class="bk-meta-label">Trạng thái</span>
                <span class="bk-meta-val bk-badge-pending">Chờ xác nhận</span>
            </div>
        </div>
        <div class="bk-success-actions">
            <a href="index.php" class="bk-btn-primary">Về trang chủ</a>
        </div>
    </div>
</section>

<?php else: ?>
<!-- ── Layout 2 cột ──────────────────────────────────────────────────── -->
<div class="bk-layout">

    <!-- Cột trái: thông tin phòng -->
    <aside class="bk-room-card">
        <div class="bk-room-img-wrap">
            <img src="<?php echo h(asset_url($phong['duong_dan_hinh'])); ?>"
                 alt="<?php echo h($phong['ten_phong']); ?>"
                 class="bk-room-img">
            <span class="bk-room-badge"><?php echo h($phong['ma_so_phong']); ?></span>
        </div>
        <div class="bk-room-info">
            <p class="eyebrow" style="margin:0 0 4px"><?php echo h($phong['ten_loai_phong']); ?></p>
            <h2 class="bk-room-name"><?php echo h($phong['ten_phong']); ?></h2>
            <p class="bk-room-desc"><?php echo h($phong['mo_ta']); ?></p>
            <div class="bk-room-pricing">
                <div class="bk-price-row">
                    <span class="bk-price-label">Theo đêm</span>
                    <span class="bk-price-val"><?php echo tien_viet_nam($phong['gia_theo_ngay']); ?></span>
                </div>
                <div class="bk-price-row">
                    <span class="bk-price-label">Theo giờ</span>
                    <span class="bk-price-val"><?php echo tien_viet_nam($phong['gia_theo_gio']); ?></span>
                </div>
                <div class="bk-price-row">
                    <span class="bk-price-label">Sức chứa</span>
                    <span class="bk-price-val"><?php echo h($phong['suc_chua']); ?> khách</span>
                </div>
            </div>

            <div class="room-modal-calendar-section" style="margin-top: 18px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <h3 class="room-modal-calc-title" style="margin: 0;">Lịch tình trạng phòng</h3>
                </div>
                <div class="room-cal-legend">
                    <span class="room-cal-legend-item room-cal-legend-avail">Còn trống</span>
                    <span class="room-cal-legend-item room-cal-legend-booked">Đã đặt</span>
                    <span class="room-cal-legend-item room-cal-legend-today">Hôm nay</span>
                </div>
                <div id="booking-room-calendar" class="room-cal-wrap">
                    <div class="room-cal-loading">Đang tải lịch…</div>
                </div>
            </div>
        </div>
        <a href="rooms.php" class="bk-back-link">← Chọn phòng khác</a>
    </aside>

    <!-- Cột phải: form đặt phòng -->
    <section class="bk-form-wrap">
        <h2 class="bk-form-title">Thông tin đặt phòng</h2>

        <?php if ($formError !== ''): ?>
            <p class="alert bk-alert"><?php echo h($formError); ?></p>
        <?php endif; ?>

        <form method="post" action="booking.php?id=<?php echo $idPhong; ?>" class="bk-form" id="booking-form" novalidate>
            <input type="hidden" name="action" value="dat_phong">

            <!-- Thông tin khách -->
            <fieldset class="bk-fieldset">
                <legend class="bk-legend">Thông tin khách</legend>

                <div class="bk-field">
                    <label for="ho_ten" class="bk-label">Họ và tên <span class="bk-required">*</span></label>
                    <input id="ho_ten" type="text" name="ho_ten"
                           class="bk-input <?php echo isset($fieldErrors['ho_ten']) ? 'bk-input-err' : ''; ?>"
                           placeholder="Nguyễn Văn A"
                           value="<?php echo h($_POST['ho_ten'] ?? ''); ?>" required>
                    <?php if (isset($fieldErrors['ho_ten'])): ?>
                        <p class="bk-err-msg"><?php echo h($fieldErrors['ho_ten']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="bk-field">
                    <label for="so_dien_thoai" class="bk-label">Số điện thoại <span class="bk-required">*</span></label>
                    <input id="so_dien_thoai" type="tel" name="so_dien_thoai"
                           class="bk-input <?php echo isset($fieldErrors['so_dien_thoai']) ? 'bk-input-err' : ''; ?>"
                           placeholder="0901 234 567"
                           value="<?php echo h($_POST['so_dien_thoai'] ?? ''); ?>" required>
                    <?php if (isset($fieldErrors['so_dien_thoai'])): ?>
                        <p class="bk-err-msg"><?php echo h($fieldErrors['so_dien_thoai']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="bk-field">
                    <label for="email" class="bk-label">Email</label>
                    <input id="email" type="email" name="email"
                           class="bk-input <?php echo isset($fieldErrors['email']) ? 'bk-input-err' : ''; ?>"
                           placeholder="example@email.com"
                           value="<?php echo h($_POST['email'] ?? ''); ?>">
                    <?php if (isset($fieldErrors['email'])): ?>
                        <p class="bk-err-msg"><?php echo h($fieldErrors['email']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="bk-field">
                    <label for="dia_chi" class="bk-label">Địa chỉ</label>
                    <input id="dia_chi" type="text" name="dia_chi" class="bk-input"
                           placeholder="Quận / Tỉnh thành"
                           value="<?php echo h($_POST['dia_chi'] ?? ''); ?>">
                </div>
                <div class="bk-field">
                    <label for="ghi_chu" class="bk-label">Ghi chú</label>
                    <textarea id="ghi_chu" name="ghi_chu" class="bk-input bk-textarea"
                              placeholder="Yêu cầu đặc biệt, giờ tàu / xe, trẻ em…" rows="3"><?php echo h($_POST['ghi_chu'] ?? ''); ?></textarea>
                </div>
            </fieldset>

            <!-- Kiểu thuê (moved up) -->
            <fieldset class="bk-fieldset">
                <legend class="bk-legend">Kiểu thuê</legend>
                <div class="bk-type-toggle" role="group" aria-label="Kiểu thuê phòng">
                    <label class="bk-type-opt">
                        <input type="radio" name="kieu_dat" value="ngay"
                               <?php echo (($_POST['kieu_dat'] ?? 'ngay') !== 'gio') ? 'checked' : ''; ?>
                               id="kieu-ngay">
                        <span class="bk-type-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                            Theo đêm
                        </span>
                    </label>
                    <label class="bk-type-opt">
                        <input type="radio" name="kieu_dat" value="gio"
                               <?php echo (($_POST['kieu_dat'] ?? '') === 'gio') ? 'checked' : ''; ?>
                               id="kieu-gio">
                        <span class="bk-type-label">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            Theo giờ
                        </span>
                    </label>
                </div>
            </fieldset>

            <!-- Thời gian -->
            <fieldset class="bk-fieldset">
                <legend class="bk-legend">Thời gian lưu trú</legend>

                <!-- Ngày nhận (always visible) -->
                <div class="bk-field">
                    <label for="ngay_nhan" class="bk-label">Ngày nhận phòng <span class="bk-required">*</span></label>
                    <input id="ngay_nhan" type="date" name="ngay_nhan"
                           class="bk-input <?php echo isset($fieldErrors['ngay_nhan']) ? 'bk-input-err' : ''; ?>"
                           min="<?php echo h($minDayBooking); ?>"
                           value="<?php echo h($ngayNhan); ?>" required>
                    <?php if (isset($fieldErrors['ngay_nhan'])): ?>
                        <p class="bk-err-msg"><?php echo h($fieldErrors['ngay_nhan']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Ngày trả (visible only for theo đêm) -->
                <div class="bk-field" id="bk-ngay-tra-wrap">
                    <label for="ngay_tra" class="bk-label">Ngày trả phòng <span class="bk-required">*</span></label>
                    <input id="ngay_tra" type="date" name="ngay_tra"
                           class="bk-input <?php echo isset($fieldErrors['ngay_tra']) ? 'bk-input-err' : ''; ?>"
                           min="<?php echo h($ngayNhan); ?>"
                           value="<?php echo h($ngayTra); ?>" required>
                    <?php if (isset($fieldErrors['ngay_tra'])): ?>
                        <p class="bk-err-msg"><?php echo h($fieldErrors['ngay_tra']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Giờ nhận (visible only for theo giờ) -->
                <div class="bk-field" id="bk-gio-nhan-wrap" style="display:none;">
                    <label for="gio_nhan" class="bk-label">Giờ nhận phòng</label>
                    <input id="gio_nhan" type="time" name="gio_nhan" class="bk-input"
                           value="<?php echo h($gioNhan); ?>">
                </div>

                <!-- Giờ trả (visible only for theo giờ) -->
                <div class="bk-field" id="bk-gio-tra-wrap" style="display:none;">
                    <label for="gio_tra" class="bk-label">Giờ trả phòng</label>
                    <input id="gio_tra" type="time" name="gio_tra" class="bk-input"
                           value="<?php echo h($gioTra); ?>">
                </div>
            </fieldset>

            <!-- Duration -->
            <div class="bk-duration-row">
                <span class="bk-label bk-duration-label">Thời lượng</span>
                <div class="bk-duration-display" id="bk-duration">1 đêm</div>
                <input type="hidden" id="so_luong" name="so_luong" value="<?php echo max(1,(int)($_POST['so_luong']??1)); ?>">
            </div>

            <!-- Tổng tiền -->
            <div class="bk-total-line">
                <span class="bk-total-label">Tổng tiền ước tính</span>
                <span class="bk-total-val" id="bk-total-price">-</span>
            </div>

            <button type="submit" class="bk-submit-btn" id="bk-submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Xác nhận đặt phòng
            </button>
            <p class="bk-note">Homestay sẽ liên hệ xác nhận qua số điện thoại / email bạn cung cấp.</p>
        </form>
    </section>
</div>
<?php endif; ?>

</main>

<script>
(function () {
    var giaGio  = <?php echo json_encode((float)$phong['gia_theo_gio']); ?>;
    var giaNgay = <?php echo json_encode((float)$phong['gia_theo_ngay']); ?>;
    var roomId  = <?php echo json_encode((int)$phong['id_phong']); ?>;
    var calendarWrap = document.getElementById('booking-room-calendar');

    function fmtMoney(n) {
        return n.toLocaleString('vi-VN') + ' đồng';
    }

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
            var tuParts = d.tu.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
            var denParts = d.den.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
            if (!tuParts || !denParts) return;

            var startYear = parseInt(tuParts[1], 10);
            var startMonth = parseInt(tuParts[2], 10);
            var startDay = parseInt(tuParts[3], 10);
            var startHour = parseInt(tuParts[4], 10);
            var startMin = parseInt(tuParts[5], 10);
            var endYear = parseInt(denParts[1], 10);
            var endMonth = parseInt(denParts[2], 10);
            var endDay = parseInt(denParts[3], 10);
            var endHour = parseInt(denParts[4], 10);
            var endMin = parseInt(denParts[5], 10);

            var cur = new Date(startYear, startMonth - 1, startDay, 0, 0, 0);
            var loopEnd = new Date(endYear, endMonth - 1, endDay, 0, 0, 0);

            if (loopEnd <= cur) {
                loopEnd.setDate(loopEnd.getDate() + 1);
            } else if (endHour > 0 || endMin > 0) {
                loopEnd.setDate(loopEnd.getDate() + 1);
            }

            while (cur < loopEnd) {
                var y = cur.getFullYear();
                var m = String(cur.getMonth() + 1).padStart(2, '0');
                var dd = String(cur.getDate()).padStart(2, '0');
                var key = y + '-' + m + '-' + dd;
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

    function renderBookingCalendar(datList) {
        if (!calendarWrap) return;
        var map = buildBookedMap(datList);
        var today = new Date();
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
                var dateKey = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
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
                html += '<div class="' + cls + '" data-date="' + dateKey + '"' + tipAttr + '>' + inner + '</div>';
            }

            html += '</div></div>';
        }

        calendarWrap.innerHTML = html;
        calendarWrap.querySelectorAll('.room-cal-avail').forEach(function (dayCell) {
            dayCell.addEventListener('click', function () {
                updateCalendarSelection(dayCell.getAttribute('data-date'));
            });
        });
    }

    function loadBookingCalendar() {
        if (!calendarWrap || !roomId) return;
        calendarWrap.innerHTML = '<div class="room-cal-loading">Đang tải lịch…</div>';
        var cacheBuster = '&t=' + new Date().getTime();
        console.log('Loading calendar for room:', roomId);
        fetch('rooms.php?action=calendar&id=' + roomId + '&months=3' + cacheBuster)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                console.log('Calendar API response:', data);
                if (data.ok) {
                    console.log('Bookings found:', data.dat ? data.dat.length : 0);
                    renderBookingCalendar(data.dat);
                } else {
                    console.warn('API error:', data.error);
                    calendarWrap.innerHTML = '<p class="room-cal-err">Không tải được lịch.</p>';
                }
            })
            .catch(function (err) {
                console.error('Fetch error:', err);
                calendarWrap.innerHTML = '<p class="room-cal-err">Lỗi kết nối.</p>';
            });
    }

    var soLuongInput      = document.getElementById('so_luong');
    var durationDisplay   = document.getElementById('bk-duration');
    var totalEl           = document.getElementById('bk-total-price');
    var radios            = document.querySelectorAll('input[name="kieu_dat"]');
    var inputNhan         = document.getElementById('ngay_nhan');
    var inputTra          = document.getElementById('ngay_tra');
    var inputGioNhan      = document.getElementById('gio_nhan');
    var inputGioTra       = document.getElementById('gio_tra');
    var calendarSelected  = null;
    var defaultGioNhan    = '<?php echo h($defaultGioNhan); ?>';
    var defaultGioTra     = '<?php echo h($defaultGioTra); ?>';
    var dailyMinDate      = '<?php echo h($dailyMin); ?>';
    var hourlyMinDate     = '<?php echo h($hourlyMin); ?>';
    var todayDate         = '<?php echo h($today); ?>';

    function getKieu() {
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) return radios[i].value;
        }
        return 'ngay';
    }

    function parseDateTime(dateValue, timeValue) {
        if (!dateValue) return null;
        var t = timeValue || '00:00';
        return new Date(dateValue + 'T' + t);
    }

    function renderTotal() {
        if (!soLuongInput || !totalEl) return;
        var kieu = getKieu();
        var donGia = kieu === 'gio' ? giaGio : giaNgay;
        totalEl.textContent = fmtMoney(donGia * parseInt(soLuongInput.value || '1', 10));
    }

    function syncDuration() {
        if (!durationDisplay || !soLuongInput || !totalEl) return;
        var kieu = getKieu();
        var dtNhan = parseDateTime(inputNhan ? inputNhan.value : '', inputGioNhan ? inputGioNhan.value : '');
        var dtTra  = parseDateTime(inputTra ? inputTra.value : '', inputGioTra ? inputGioTra.value : '');

        if (!dtNhan || !dtTra || isNaN(dtNhan.getTime()) || isNaN(dtTra.getTime())) {
            durationDisplay.textContent = kieu === 'gio' ? 'Chọn ngày/giờ từ lịch' : 'Chọn ngày từ lịch';
            soLuongInput.value = 1;
            renderTotal();
            return;
        }

        if (dtTra <= dtNhan) {
            var dayNhan = new Date(dtNhan);
            dayNhan.setHours(0, 0, 0, 0);
            var dayTra = new Date(dtTra);
            dayTra.setHours(0, 0, 0, 0);
            
            if (dayTra < dayNhan) {
                durationDisplay.textContent = 'Ngày trả phải sau ngày nhận';
            } else {
                durationDisplay.textContent = 'Giờ trả phải sau giờ nhận';
            }
            soLuongInput.value = 1;
            renderTotal();
            return;
        }

        if (kieu === 'gio') {
            var diffHours = Math.ceil((dtTra - dtNhan) / 3600000);
            soLuongInput.value = diffHours;
            durationDisplay.textContent = diffHours + ' giờ';
        } else {
            var diffDays = Math.ceil((dtTra - dtNhan) / 86400000);
            if (diffDays < 1) diffDays = 1;
            soLuongInput.value = diffDays;
            durationDisplay.textContent = diffDays + ' đêm';
        }
        renderTotal();
    }

    function addOneHour(timeValue) {
        if (!timeValue) return '01:00';
        var parts = timeValue.split(':');
        if (parts.length !== 2) return '01:00';
        var d = new Date('1970-01-01T' + parts[0].padStart(2, '0') + ':' + parts[1].padStart(2, '0'));
        d.setHours(d.getHours() + 1);
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        return h + ':' + m;
    }

    function updateCalendarSelection(dateKey) {
        if (!dateKey || !inputNhan || !inputTra) return;
        inputNhan.value = dateKey;
        var currentDate = new Date(dateKey + 'T00:00');
        var nextDay = new Date(currentDate);
        nextDay.setDate(nextDay.getDate() + 1);

        if (!inputTra.value || inputTra.value <= dateKey) {
            inputTra.value = localDateKey(nextDay);
        }

        if (getKieu() === 'gio' && inputGioNhan && inputGioTra) {
            if (!inputGioNhan.value) inputGioNhan.value = '<?php echo h($defaultGioNhan); ?>';
            if (!inputGioTra.value || inputGioTra.value <= inputGioNhan.value) inputGioTra.value = addOneHour(inputGioNhan.value);
            if (inputGioNhan.value < '<?php echo h($defaultGioNhan); ?>') {
                inputGioNhan.value = '<?php echo h($defaultGioNhan); ?>';
                inputGioTra.value = addOneHour(inputGioNhan.value);
            }
        }

        if (calendarSelected) calendarSelected.classList.remove('selected');
        calendarSelected = calendarWrap.querySelector('[data-date="' + dateKey + '"]');
        if (calendarSelected) calendarSelected.classList.add('selected');

        syncDuration();
    }

    // Bấm ngày còn trống trên lịch để điền nhanh ngày nhận và ngày trả.

    if (inputNhan && inputTra) {
        inputNhan.addEventListener('change', function () {
            var kieu = getKieu();
            if (kieu === 'ngay') {
                // For theo đêm: set ngay_tra = ngay_nhan + 1 day
                if (inputNhan.value) {
                    var nhanDate = new Date(inputNhan.value + 'T00:00');
                    var traDate = new Date(nhanDate);
                    traDate.setDate(traDate.getDate() + 1);
                    inputTra.value = localDateKey(traDate);
                }
            } else {
                // For theo giờ: set ngay_tra = ngay_nhan (same day)
                if (inputNhan.value) {
                    inputTra.value = inputNhan.value;
                }
            }
            syncDuration();
        });
        inputTra.addEventListener('change', syncDuration);
    }
    if (inputGioNhan) {
        inputGioNhan.addEventListener('change', function () {
            if (inputGioTra && inputGioTra.value <= inputGioNhan.value) {
                inputGioTra.value = addOneHour(inputGioNhan.value);
            }
            syncDuration();
        });
    }
    if (inputGioTra) inputGioTra.addEventListener('change', syncDuration);
    
    // Handle radio change: show/hide fields based on booking type
    function updateBookingTypeUI() {
        var kieu = getKieu();
        var ngayTraWrap = document.getElementById('bk-ngay-tra-wrap');
        var gioNhanWrap = document.getElementById('bk-gio-nhan-wrap');
        var gioTraWrap = document.getElementById('bk-gio-tra-wrap');

        if (kieu === 'ngay') {
            // Theo đêm: show ngay_tra, hide giờ nhận/trả
            if (ngayTraWrap) ngayTraWrap.style.display = 'block';
            if (gioNhanWrap) gioNhanWrap.style.display = 'none';
            if (gioTraWrap) gioTraWrap.style.display = 'none';

            if (inputNhan) {
                inputNhan.min = dailyMinDate;
                if (!inputNhan.value || inputNhan.value < dailyMinDate) {
                    inputNhan.value = dailyMinDate;
                }
            }
            if (inputTra) {
                if (inputNhan && inputNhan.value) {
                    var nhanDate = new Date(inputNhan.value + 'T00:00');
                    var traDate = new Date(nhanDate);
                    traDate.setDate(traDate.getDate() + 1);
                    inputTra.value = localDateKey(traDate);
                    inputTra.min = inputNhan.value;
                }
            }
        } else {
            // Theo giờ: hide ngay_tra, show giờ nhận/trả
            if (ngayTraWrap) ngayTraWrap.style.display = 'none';
            if (gioNhanWrap) gioNhanWrap.style.display = 'block';
            if (gioTraWrap) gioTraWrap.style.display = 'block';

            if (inputNhan) {
                inputNhan.min = hourlyMinDate;
                if (!inputNhan.value || inputNhan.value < hourlyMinDate) {
                    inputNhan.value = hourlyMinDate;
                }
            }
            if (inputGioNhan) {
                inputGioNhan.min = defaultGioNhan;
                if (!inputGioNhan.value || inputGioNhan.value < inputGioNhan.min) {
                    inputGioNhan.value = inputGioNhan.min;
                }
            }
            if (inputGioTra && inputGioNhan && inputGioNhan.value) {
                inputGioTra.value = addOneHour(inputGioNhan.value);
            }

            if (inputNhan && inputNhan.value && inputTra) {
                inputTra.value = inputNhan.value;
            }
        }
        syncDuration();
    }
    
    radios.forEach(function (r) { 
        r.addEventListener('change', updateBookingTypeUI);
    });
    
    // Initialize UI based on current selection
    updateBookingTypeUI();

    var refreshBtn = document.getElementById('booking-calendar-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function (e) {
            e.preventDefault();
            loadBookingCalendar();
        });
    }

    loadBookingCalendar();
    syncDuration();
}());
</script>

<?php include "includes/footer.php"; ?>
