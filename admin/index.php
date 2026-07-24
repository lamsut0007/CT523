<?php
include "includes/auth.php";
require_admin_login();

include "../includes/db.php";
include "../includes/functions.php";

$pageTitle = "Trang chủ Admin";
$tongSoPhong = 0;
$soPhongDangHoatDong = 0;
$soPhongTuSua = 0;
$loiThongKe = "";

if ($conn != null) {
    $sql = "SELECT
                COUNT(*) AS tong_so_phong,
                SUM(CASE WHEN trang_thai = 'dang_hoat_dong' THEN 1 ELSE 0 END) AS so_phong_dang_hoat_dong,
                SUM(CASE WHEN trang_thai = 'tu_sua' THEN 1 ELSE 0 END) AS so_phong_tu_sua
            FROM phong";
    $ketQua = $conn->query($sql);

    if ($ketQua) {
        $dong = $ketQua->fetch_assoc();
        $tongSoPhong = (int) $dong["tong_so_phong"];
        $soPhongDangHoatDong = (int) $dong["so_phong_dang_hoat_dong"];
        $soPhongTuSua = (int) $dong["so_phong_tu_sua"];
    } else {
        $loiThongKe = "Chưa đọc được thống kê phòng. Có thể database vẫn là bản cũ, import lại schema giúp mình nha.";
    }
} else {
    $loiThongKe = $dbError;
}

$assetPathPrefix = '../';
$hideBrandText = true;
$brandHref = 'index.php';
$headerNavItems = array(
    array('href' => 'index.php',    'label' => 'Trang chủ'),
    array('href' => 'rooms.php',    'label' => 'Quản lý phòng'),
    array('href' => 'bookings.php', 'label' => 'Quản lý đặt phòng'),
    array('href' => 'logout.php',   'label' => 'Đăng xuất'),
);

include "../includes/header.php";
?>

<main class="admin-main">
    <div class="admin-container">
        <section class="page-title">
            <h1>Trang chủ Admin</h1>
        </section>

        <?php if ($loiThongKe != "") { ?>
            <p class="alert"><?php echo h($loiThongKe); ?></p>
        <?php } ?>

        <section class="admin-card admin-action-card">
            <div>
                <h2>Quản lý trạng thái phòng</h2>
                <p>Phòng nào sẵn sàng thì mở cửa, phòng nào cần chăm lại thì đưa vào tu sửa. Gọn vậy thôi.</p>
            </div>
            <a class="button" href="rooms.php">Vào quản lý phòng</a>
        </section>

        <section class="admin-card admin-action-card" style="margin-top:18px">
            <div>
                <h2>Quản lý đặt phòng</h2>
                <p>Xem toàn bộ đơn đặt phòng, xác nhận thanh toán và phát hiện lịch trùng.</p>
            </div>
            <a class="button" href="bookings.php">Vào quản lý đặt phòng</a>
        </section>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
