<?php
include 'includes/auth.php';
require_admin_login();
include '../includes/db.php';
include '../includes/functions.php';

$pageTitle = 'Quản lý đặt phòng';
$assetPathPrefix = '../';
$hideBrandText = true;
$brandHref = 'index.php';
$headerNavItems = array(
    array('href' => 'index.php', 'label' => 'Tổng quan'),
    array('href' => 'rooms.php', 'label' => 'Quản lý phòng'),
    array('href' => 'bookings.php', 'label' => 'Quản lý đặt phòng'),
    array('href' => 'logout.php', 'label' => 'Đăng xuất'),
);

$message = trim($_GET['msg'] ?? '');
$error = '';
$showForm = ($_GET['action'] ?? '') === 'add';
$selectedRoom = (int) ($_GET['id_phong'] ?? 0);
$rooms = array();
$bookings = array();

if ($conn) {
    $result = $conn->query("SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.trang_thai,
        lp.ten_loai_phong, lp.gia_theo_gio, lp.gia_theo_ngay
        FROM phong p INNER JOIN loai_phong lp ON lp.id_loai_phong = p.id_loai_phong
        WHERE p.trang_thai = 'dang_hoat_dong' ORDER BY p.ma_so_phong");
    if ($result) while ($row = $result->fetch_assoc()) $rooms[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_direct') {
        $roomId = (int) ($_POST['id_phong'] ?? 0);
        $name = trim($_POST['ho_ten'] ?? '');
        $phone = trim($_POST['so_dien_thoai'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['dia_chi'] ?? '');
        $dateIn = trim($_POST['ngay_nhan'] ?? '');
        $timeIn = trim($_POST['gio_nhan'] ?? '');
        $dateOut = trim($_POST['ngay_tra'] ?? '');
        $timeOut = trim($_POST['gio_tra'] ?? '');
        $type = ($_POST['kieu_dat'] ?? 'ngay') === 'gio' ? 'gio' : 'ngay';
        $note = trim($_POST['ghi_chu'] ?? '');
        $status = in_array($_POST['trang_thai'] ?? '', array('cho_xac_nhan', 'da_xac_nhan', 'da_nhan_phong'), true)
            ? $_POST['trang_thai'] : 'da_xac_nhan';

        $room = lay_phong_theo_id($conn, $roomId, false);
        $dateErrors = validate_booking_window($dateIn, $timeIn, $dateOut, $timeOut, $type, 0);
        if (!$room) {
            $error = 'Phòng không tồn tại hoặc đã bị ngừng quản lý.';
        } elseif ($name === '' || !preg_match('/^0[0-9]{9}$/', $phone)) {
            $error = 'Vui lòng nhập họ tên và số điện thoại hợp lệ.';
        } elseif (!empty($dateErrors)) {
            $error = (string) array_values($dateErrors)[0];
        }

        if ($error === '') {
            $start = tao_datetime_tu_ngay_gio($dateIn, $timeIn);
            $end = tao_datetime_tu_ngay_gio($dateOut, $timeOut);
            $minutes = (int) ceil((strtotime($end) - strtotime($start)) / 60);
            $units = $type === 'ngay' ? max(1, (int) ceil($minutes / 1440)) : max(1, (int) ceil($minutes / 60));
            $price = (float) ($type === 'ngay' ? $room['gia_theo_ngay'] : $room['gia_theo_gio']);
            if (!phong_co_the_dat($conn, $roomId, $start, $end)) {
                $error = 'Phòng không còn trống trong khung thời gian này.';
            } else {
                $stmt = $conn->prepare("INSERT INTO dat_phong
                    (id_phong, ho_ten, so_dien_thoai, email, dia_chi, ngay_nhan_phong, ngay_tra_phong,
                     kieu_dat, so_luong, tong_tien, nguon_dat, trang_thai, ghi_chu)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'dien_thoai', ?, ?)");
                if ($stmt) {
                    $total = $units * $price;
                    $stmt->bind_param('i' . str_repeat('s', 7) . 'idss', $roomId, $name, $phone, $email, $address, $start, $end, $type, $units, $total, $status, $note);
                    if ($stmt->execute()) {
                        $message = 'Đã tạo đơn đặt phòng trực tiếp #' . $stmt->insert_id;
                        $showForm = false;
                    } else {
                        $error = 'Không lưu được đơn trực tiếp.';
                    }
                    $stmt->close();
                } else {
                    $error = 'Câu lệnh đặt phòng chưa sẵn sàng. Hãy chạy migration database.';
                }
            }
        }
    } elseif ($action === 'update_status') {
        $id = (int) ($_POST['id_dat_phong'] ?? 0);
        $newStatus = $_POST['trang_thai'] ?? '';
        $allowed = array('cho_xac_nhan', 'da_xac_nhan', 'da_nhan_phong', 'hoan_thanh', 'da_huy');
        $item = null;
        if ($id > 0) {
            $stmt = $conn->prepare('SELECT * FROM dat_phong WHERE id_dat_phong = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        if (!$item || !in_array($newStatus, $allowed, true)) {
            $error = 'Không xác định được đơn cần cập nhật.';
        } elseif ($newStatus !== 'da_huy' && !phong_co_the_dat($conn, (int) $item['id_phong'], $item['ngay_nhan_phong'], $item['ngay_tra_phong'], $id)) {
            $conflict = lay_don_trung_lich($conn, (int) $item['id_phong'], $item['ngay_nhan_phong'], $item['ngay_tra_phong'], $id);
            if ($conflict) {
                $error = 'Không thể xác nhận đơn #' . $id . ' (' . date('d/m/Y H:i', strtotime($item['ngay_nhan_phong'])) . ' - ' . date('d/m/Y H:i', strtotime($item['ngay_tra_phong'])) . ')' . "\n" . 'Vì trùng với đơn #' . $conflict['id_dat_phong'] . ' đang giữ chỗ (' . date('d/m/Y H:i', strtotime($conflict['ngay_nhan_phong'])) . ' - ' . date('d/m/Y H:i', strtotime($conflict['ngay_tra_phong'])) . ').';
            } else {
                $error = 'Không thể xác nhận đơn #' . $id . ' vì lịch đang trùng với một đơn giữ chỗ khác.';
            }
        } else {
            $stmt = $conn->prepare('UPDATE dat_phong SET trang_thai = ? WHERE id_dat_phong = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $message = $stmt->execute() ? 'Đã chuyển đơn #' . $id . ' sang ' . ten_trang_thai_dat($newStatus) . '.' : 'Không cập nhật được trạng thái.';
            $stmt->close();
        }
    }

    if ($error === '' && $message !== '') {
        header('Location: bookings.php?msg=' . urlencode($message));
        exit;
    }
}

if ($conn) {
    $sql = "SELECT dp.*, p.ten_phong, p.ma_so_phong, lp.ten_loai_phong
            FROM dat_phong dp INNER JOIN phong p ON p.id_phong = dp.id_phong
            INNER JOIN loai_phong lp ON lp.id_loai_phong = p.id_loai_phong";
    if ($selectedRoom > 0) $sql .= ' WHERE dp.id_phong = ' . $selectedRoom;
    $sql .= " ORDER BY CASE dp.trang_thai WHEN 'cho_xac_nhan' THEN 0 WHEN 'da_xac_nhan' THEN 1
              WHEN 'da_nhan_phong' THEN 2 WHEN 'hoan_thanh' THEN 3 ELSE 4 END, dp.ngay_nhan_phong DESC";
    $result = $conn->query($sql);
    if ($result) while ($row = $result->fetch_assoc()) $bookings[] = $row;
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
include '../includes/header.php';
?>
<main class="admin-main"><div class="admin-container">
    <section class="page-title"><div><h1>Quản lý đặt phòng</h1><p>Đặt trên web và khách gọi điện đều nằm chung một lịch. Khi khách tới, đổi sang “Đã nhận phòng”.</p></div><a class="button" href="bookings.php?action=add">+ Đặt phòng từ cuộc gọi</a></section>
    <?php if ($message) { ?><p class="success"><?php echo h($message); ?></p><?php } ?>
    <?php if ($error) { ?><p class="alert"><?php echo h($error); ?></p><?php } ?>
    <?php if ($showForm) { ?>
    <section class="room-admin-form-card room-admin-form-section"><div class="room-admin-form-title"><h2>Tạo đặt phòng trực tiếp</h2><p>Dùng khi khách gọi điện. Đơn được lưu với nguồn “Điện thoại”, không có bước thanh toán.</p></div>
        <form method="post" class="room-admin-form"><input type="hidden" name="action" value="create_direct"><div class="room-admin-form-grid">
            <label><span>Phòng *</span><select name="id_phong" required><option value="">-- Chọn phòng --</option><?php foreach ($rooms as $room) { ?><option value="<?php echo (int) $room['id_phong']; ?>" <?php echo $selectedRoom === (int) $room['id_phong'] ? 'selected' : ''; ?>><?php echo h($room['ma_so_phong'] . ' - ' . $room['ten_phong']); ?></option><?php } ?></select></label>
            <label><span>Họ tên *</span><input name="ho_ten" required></label><label><span>Số điện thoại *</span><input name="so_dien_thoai" required pattern="0[0-9]{9}"></label><label><span>Email</span><input type="email" name="email"></label>
            <label><span>Ngày nhận *</span><input type="date" name="ngay_nhan" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required></label><label><span>Giờ nhận *</span><input type="time" name="gio_nhan" value="14:00" required></label><label><span>Ngày trả *</span><input type="date" name="ngay_tra" min="<?php echo h($today); ?>" value="<?php echo h($tomorrow); ?>" required></label><label><span>Giờ trả *</span><input type="time" name="gio_tra" value="12:00" required></label>
            <label><span>Kiểu đặt</span><select name="kieu_dat"><option value="ngay">Theo đêm</option><option value="gio">Theo giờ</option></select></label><label><span>Trạng thái ban đầu</span><select name="trang_thai"><option value="da_xac_nhan">Đã xác nhận</option><option value="da_nhan_phong">Đã nhận phòng</option><option value="cho_xac_nhan">Chờ xác nhận</option></select></label>
            <label style="grid-column:1/-1"><span>Ghi chú</span><textarea class="bk-input" name="ghi_chu" rows="3"></textarea></label>
        </div><div class="room-admin-actions"><button type="submit">Lưu đặt phòng</button><a class="button button-secondary" href="bookings.php">Đóng form</a></div></form>
    </section>
    <?php } ?>
    <section class="room-admin-list-card room-admin-list-section booking-list-section"><div class="room-admin-list-intro"><h2>Danh sách đơn (<?php echo count($bookings); ?>)</h2><p>Bộ lọc nhanh: <a href="bookings.php">Tất cả</a><?php foreach ($rooms as $room) { ?> · <a href="bookings.php?id_phong=<?php echo (int) $room['id_phong']; ?>"><?php echo h($room['ma_so_phong']); ?></a><?php } ?></p></div>
        <div class="booking-table-wrap"><table class="booking-table"><thead><tr><th>Khách / nguồn</th><th>Phòng</th><th>Nhận - trả</th><th>Thời lượng</th><th>Trạng thái</th><th>Đổi trạng thái</th></tr></thead><tbody>
        <?php foreach ($bookings as $item) { $minutes = max(0, (int) ceil((strtotime($item['ngay_tra_phong']) - strtotime($item['ngay_nhan_phong'])) / 60)); $statusClass = match ($item['trang_thai']) { 'cho_xac_nhan' => 'booking-badge--pending', 'da_xac_nhan' => 'booking-badge--confirmed', 'da_nhan_phong' => 'booking-badge--occupied', 'hoan_thanh' => 'booking-badge--done', 'da_huy' => 'booking-badge--cancelled', default => 'booking-badge--pending' }; ?>
            <tr><td><strong>#<?php echo (int) $item['id_dat_phong']; ?> · <?php echo h($item['ho_ten']); ?></strong><br><?php echo h($item['so_dien_thoai']); ?><br><span class="booking-email"><?php echo $item['nguon_dat'] === 'dien_thoai' ? 'Điện thoại' : 'Website'; ?></span></td><td><strong><?php echo h($item['ma_so_phong']); ?></strong><br><?php echo h($item['ten_phong']); ?></td><td class="booking-date-cell"><span class="booking-sublabel">Nhận:</span> <?php echo date('d/m/Y H:i', strtotime($item['ngay_nhan_phong'])); ?><br><span class="booking-sublabel">Trả:</span> <?php echo date('d/m/Y H:i', strtotime($item['ngay_tra_phong'])); ?></td><td><?php echo h(format_duration_minutes($minutes)); ?><br><span class="booking-email"><?php echo h($item['kieu_dat'] === 'gio' ? 'Theo giờ' : 'Theo đêm'); ?></span></td><td><span class="booking-badge <?php echo $statusClass; ?>"><?php echo h(ten_trang_thai_dat($item['trang_thai'])); ?></span></td><td><form method="post" class="status-form"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id_dat_phong" value="<?php echo (int) $item['id_dat_phong']; ?>"><select name="trang_thai" onchange="this.form.submit()"><option value="cho_xac_nhan" <?php echo $item['trang_thai'] === 'cho_xac_nhan' ? 'selected' : ''; ?>>Chờ xác nhận</option><option value="da_xac_nhan" <?php echo $item['trang_thai'] === 'da_xac_nhan' ? 'selected' : ''; ?>>Đã xác nhận</option><option value="da_nhan_phong" <?php echo $item['trang_thai'] === 'da_nhan_phong' ? 'selected' : ''; ?>>Đã nhận phòng</option><option value="hoan_thanh" <?php echo $item['trang_thai'] === 'hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option><option value="da_huy" <?php echo $item['trang_thai'] === 'da_huy' ? 'selected' : ''; ?>>Đã hủy</option></select></form></td></tr>
        <?php } ?>
        <?php if (!$bookings) { ?><tr><td colspan="6" class="booking-empty">Chưa có đơn đặt phòng.</td></tr><?php } ?>
        </tbody></table></div>
    </section>
</div></main>
<?php include '../includes/footer.php'; ?>
