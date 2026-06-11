<?php
include "includes/auth.php";
require_admin_login();

include "../includes/db.php";
include "../includes/functions.php";

$pageTitle = "Trang chủ Admin";
$totalRooms = 0;
$availableRooms = 0;
$maintenanceRooms = 0;
$error = "";

if ($conn != null) {
    $sql = "SELECT
                COUNT(*) AS total_rooms,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_rooms,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_rooms
            FROM rooms";
    $result = $conn->query($sql);

    if ($result) {
        $row = $result->fetch_assoc();
        $totalRooms = (int) $row["total_rooms"];
        $availableRooms = (int) $row["available_rooms"];
        $maintenanceRooms = (int) $row["maintenance_rooms"];
    } else {
        $error = "Không tải được thống kê phòng.";
    }
} else {
    $error = $dbError;
}

include "includes/header.php";
?>

<main class="admin-main">
    <div class="admin-container">
        <section class="page-title">
            <h1>Trang chủ Admin</h1>
            <p>Khu quản trị riêng cho Local Homestay. Các menu trong admin không chuyển về trang người dùng.</p>
        </section>

        <?php if ($error != "") { ?>
            <p class="alert"><?php echo h($error); ?></p>
        <?php } ?>

        <section class="admin-dashboard">
            <div class="summary-card">
                <strong><?php echo h($totalRooms); ?></strong>
                <span>Tổng số phòng</span>
            </div>
            <div class="summary-card">
                <strong><?php echo h($availableRooms); ?></strong>
                <span>Phòng còn trống</span>
            </div>
            <div class="summary-card">
                <strong><?php echo h($maintenanceRooms); ?></strong>
                <span>Phòng đang tu sửa</span>
            </div>
        </section>

        <section class="admin-card admin-action-card">
            <div>
                <h2>Quản lý trạng thái phòng</h2>
                <p>Xem danh sách phòng và đổi trạng thái giữa còn trống hoặc tu sửa.</p>
            </div>
            <a class="button" href="rooms.php">Vào quản lý phòng</a>
        </section>
    </div>
</main>

<?php include "includes/footer.php"; ?>
