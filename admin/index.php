<?php
include "includes/auth.php";
require_admin_login();

include "../includes/db.php";
include "../includes/functions.php";

$pageTitle = "Trang chủ Admin";
$tongSoPhong = 0;
$soPhongConTrong = 0;
$soPhongTuSua = 0;
$loiThongKe = "";

if ($conn != null) {
    $sql = "SELECT
                COUNT(*) AS tong_so_phong,
                SUM(CASE WHEN trang_thai = 'con_trong' THEN 1 ELSE 0 END) AS so_phong_con_trong,
                SUM(CASE WHEN trang_thai = 'tu_sua' THEN 1 ELSE 0 END) AS so_phong_tu_sua
            FROM phong";
    $ketQua = $conn->query($sql);

    if ($ketQua) {
        $dong = $ketQua->fetch_assoc();
        $tongSoPhong = (int) $dong["tong_so_phong"];
        $soPhongConTrong = (int) $dong["so_phong_con_trong"];
        $soPhongTuSua = (int) $dong["so_phong_tu_sua"];
    } else {
        $loiThongKe = "Chưa đọc được thống kê phòng. Có thể database vẫn là bản cũ, import lại schema giúp mình nha.";
    }
} else {
    $loiThongKe = $dbError;
}

include "includes/header.php";
?>

<main class="admin-main">
    <div class="admin-container">
        <section class="page-title">
            <h1>Trang chủ Admin</h1>
            <p>Một góc nhỏ để nhìn nhanh tình trạng phòng. Không màu mè quá, nhưng đủ biết hôm nay nhà mình đang thở ra sao.</p>
        </section>

        <?php if ($loiThongKe != "") { ?>
            <p class="alert"><?php echo h($loiThongKe); ?></p>
        <?php } ?>

        <section class="admin-dashboard">
            <div class="summary-card">
                <strong><?php echo h($tongSoPhong); ?></strong>
                <span>Tổng số phòng</span>
            </div>
            <div class="summary-card">
                <strong><?php echo h($soPhongConTrong); ?></strong>
                <span>Phòng còn trống</span>
            </div>
            <div class="summary-card">
                <strong><?php echo h($soPhongTuSua); ?></strong>
                <span>Phòng đang tu sửa</span>
            </div>
        </section>

        <section class="admin-card admin-action-card">
            <div>
                <h2>Quản lý trạng thái phòng</h2>
                <p>Phòng nào sẵn sàng thì mở cửa, phòng nào cần chăm lại thì đưa vào tu sửa. Gọn vậy thôi.</p>
            </div>
            <a class="button" href="rooms.php">Vào quản lý phòng</a>
        </section>
    </div>
</main>

<?php include "includes/footer.php"; ?>
