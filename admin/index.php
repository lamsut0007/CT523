<?php
include 'includes/auth.php';
require_admin_login();
include '../includes/db.php';
include '../includes/functions.php';

$pageTitle = 'Tổng quan quản trị';
$assetPathPrefix = '../'; $hideBrandText = true; $brandHref = 'index.php';
$headerNavItems = array(
    array('href' => 'index.php', 'label' => 'Tổng quan'),
    array('href' => 'rooms.php', 'label' => 'Quản lý phòng'),
    array('href' => 'bookings.php', 'label' => 'Quản lý đặt phòng'),
    array('href' => 'logout.php', 'label' => 'Đăng xuất'),
);
$roomStats = $conn ? lay_thong_ke_phong($conn) : array();
$roomsLive = array(); $bookingCounts = array('cho_xac_nhan' => 0, 'da_xac_nhan' => 0, 'da_nhan_phong' => 0, 'hoan_thanh' => 0, 'da_huy' => 0); $upcoming = array();
if ($conn) {
    $result = $conn->query("SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.trang_thai, lp.ten_loai_phong,
        dp.id_dat_phong, dp.ho_ten, dp.ngay_nhan_phong, dp.ngay_tra_phong, dp.trang_thai AS trang_thai_dat
        FROM phong p INNER JOIN loai_phong lp ON lp.id_loai_phong=p.id_loai_phong
        LEFT JOIN dat_phong dp ON dp.id_dat_phong=(SELECT d2.id_dat_phong FROM dat_phong d2 WHERE d2.id_phong=p.id_phong
          AND d2.trang_thai IN ('da_xac_nhan','da_nhan_phong') AND d2.ngay_nhan_phong < NOW() AND d2.ngay_tra_phong > NOW() ORDER BY d2.ngay_nhan_phong LIMIT 1)
        ORDER BY p.ma_so_phong");
    if ($result) while ($row = $result->fetch_assoc()) $roomsLive[] = $row;
    $result = $conn->query("SELECT trang_thai, COUNT(*) AS total FROM dat_phong GROUP BY trang_thai");
    if ($result) while ($row = $result->fetch_assoc()) $bookingCounts[$row['trang_thai']] = (int) $row['total'];
    $result = $conn->query("SELECT dp.id_dat_phong, dp.ho_ten, dp.ngay_nhan_phong, dp.ngay_tra_phong, dp.trang_thai, p.ten_phong, p.ma_so_phong FROM dat_phong dp INNER JOIN phong p ON p.id_phong=dp.id_phong WHERE dp.ngay_tra_phong >= NOW() AND dp.trang_thai <> 'da_huy' ORDER BY dp.ngay_nhan_phong LIMIT 8");
    if ($result) while ($row = $result->fetch_assoc()) $upcoming[] = $row;
}
$active = count(array_filter($roomsLive, function ($r) { return $r['trang_thai'] === 'dang_hoat_dong'; }));
$occupied = count(array_filter($roomsLive, function ($r) { return !empty($r['id_dat_phong']); }));
$empty = max(0, $active - $occupied);
include '../includes/header.php';
?>
<main class="admin-main"><div class="admin-container">
    <section class="page-title"><div><h1>Tổng quan sử dụng phòng</h1><p>Nhìn nhanh phòng nào đang ở, phòng nào trống và hiệu quả sử dụng để điều phối khi khách gọi điện.</p></div><a class="button" href="bookings.php?action=add">+ Tạo đặt phòng trực tiếp</a></section>
    <?php if (!$conn) { ?><p class="alert"><?php echo h($dbError); ?></p><?php } ?>
    <section class="admin-dashboard">
        <div class="summary-card"><strong><?php echo count($roomsLive); ?></strong><span>Tổng số phòng</span></div>
        <div class="summary-card"><strong><?php echo $empty; ?></strong><span>Đang trống hiện tại</span></div>
        <div class="summary-card"><strong><?php echo $occupied; ?></strong><span>Đang có khách</span></div>
        <div class="summary-card"><strong><?php echo $bookingCounts['cho_xac_nhan']; ?></strong><span>Đơn chờ xác nhận</span></div>
        <div class="summary-card"><strong><?php echo $bookingCounts['da_nhan_phong']; ?></strong><span>Đã nhận phòng</span></div>
        <div class="summary-card"><strong><?php echo $bookingCounts['hoan_thanh']; ?></strong><span>Đơn hoàn thành</span></div>
    </section>
    <section class="room-admin-list-card room-admin-list-section"><div class="room-admin-list-intro"><h2>Trạng thái phòng hiện tại</h2><p>Phòng đang ở được xác định theo thời điểm hiện tại; phòng tu sửa không được nhận đặt mới.</p></div><div class="booking-table-wrap"><table class="booking-table"><thead><tr><th>Phòng</th><th>Loại</th><th>Tình trạng</th><th>Khách / lịch hiện tại</th><th>Thao tác</th></tr></thead><tbody><?php foreach ($roomsLive as $room) { $status = $room['trang_thai'] !== 'dang_hoat_dong' ? ten_trang_thai_phong($room['trang_thai']) : (!empty($room['id_dat_phong']) ? ($room['trang_thai_dat'] === 'da_nhan_phong' ? 'Đang ở' : 'Đã đặt') : 'Đang trống'); ?><tr><td><strong><?php echo h($room['ten_phong']); ?></strong><br><span class="booking-email"><?php echo h($room['ma_so_phong']); ?></span></td><td><?php echo h($room['ten_loai_phong']); ?></td><td><span class="nhan-trang-thai <?php echo $status === 'Đang trống' ? 'trang-thai-con-trong' : 'trang-thai-tu-sua'; ?>"><?php echo h($status); ?></span></td><td><?php if (!empty($room['id_dat_phong'])) { echo h($room['ho_ten']); ?><br><span class="booking-email"><?php echo date('d/m H:i', strtotime($room['ngay_nhan_phong'])) . ' → ' . date('d/m H:i', strtotime($room['ngay_tra_phong'])); ?></span><?php } elseif ($room['trang_thai'] !== 'dang_hoat_dong') { ?><span class="booking-email">Không nhận khách</span><?php } else { ?><span class="booking-email">Sẵn sàng nhận khách</span><?php } ?></td><td><a class="button button-small" href="bookings.php?id_phong=<?php echo (int) $room['id_phong']; ?>">Xem lịch</a></td></tr><?php } ?></tbody></table></div></section>
    <section class="admin-two-column"><div class="room-admin-list-card"><div class="room-admin-list-intro"><h2>Thời lượng sử dụng / lượt đặt</h2><p>Tính từ đầu tháng đến hiện tại, dùng để biết phòng best seller hay phòng ế.</p></div><div class="booking-table-wrap"><table class="booking-table"><thead><tr><th>Phòng</th><th>Lượt đặt</th><th>Thời lượng</th><th>Đánh giá</th></tr></thead><tbody><?php foreach ($roomStats as $stat) { $soDon = (int) $stat['so_don_hop_le']; ?><tr><td><strong><?php echo h($stat['ma_so_phong']); ?></strong><br><?php echo h($stat['ten_phong']); ?></td><td><?php echo $soDon; ?></td><td><?php echo h(format_duration_minutes($stat['so_phut_su_dung'])); ?></td><td><?php echo $soDon >= 4 ? 'Rất được ưa chuộng' : ($soDon >= 2 ? 'Nhu cầu tốt' : ($soDon > 0 ? 'Đang có nhu cầu' : 'Chưa có lượt')); ?></td></tr><?php } ?></tbody></table></div></div><div class="room-admin-list-card"><div class="room-admin-list-intro"><h2>Lịch sắp tới</h2><p>Danh sách ngắn các lượt nhận/trả gần nhất.</p></div><?php foreach ($upcoming as $item) { ?><div class="admin-list-item"><strong>#<?php echo (int) $item['id_dat_phong']; ?> · <?php echo h($item['ten_phong']); ?></strong><span><?php echo h($item['ho_ten']); ?></span><span><?php echo date('d/m H:i', strtotime($item['ngay_nhan_phong'])); ?> → <?php echo date('d/m H:i', strtotime($item['ngay_tra_phong'])); ?></span><small><?php echo h(ten_trang_thai_dat($item['trang_thai'])); ?></small></div><?php } ?></div></section>
</div></main>
<?php include '../includes/footer.php'; ?>
