<?php
include "includes/auth.php";
require_admin_login();

include "../includes/db.php";
include "../includes/functions.php";

$pageTitle = "Quản lý phòng";
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $roomId = isset($_POST["room_id"]) ? (int) $_POST["room_id"] : 0;
    $status = isset($_POST["status"]) ? trim($_POST["status"]) : "";

    if ($roomId <= 0 || !in_array($status, array("available", "maintenance"))) {
        $error = "Dữ liệu cập nhật không hợp lệ.";
    } elseif ($conn == null) {
        $error = $dbError;
    } else {
        $sql = "UPDATE rooms SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("si", $status, $roomId);
            if ($stmt->execute()) {
                $message = "Đã cập nhật trạng thái phòng.";
            } else {
                $error = "Không cập nhật được trạng thái phòng.";
            }
            $stmt->close();
        } else {
            $error = "Không chuẩn bị được câu lệnh cập nhật.";
        }
    }
}

$rooms = array();

if ($conn != null) {
    $sql = "SELECT r.id, r.room_code, r.room_name, r.image_path, r.status,
                   rt.name AS type_name, rt.capacity, rt.price_per_day
            FROM rooms r
            INNER JOIN room_types rt ON r.room_type_id = rt.id
            ORDER BY r.room_code ASC";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    } else {
        $error = "Không tải được danh sách phòng. Vui lòng import lại files/db/schema.sql.";
    }
} elseif ($error == "") {
    $error = $dbError;
}

include "includes/header.php";
?>

<main class="admin-main">
    <div class="admin-container">
        <section class="page-title">
            <h1>Quản lý phòng</h1>
            <p>Admin chỉ thao tác phần người dùng có thể xem: danh sách phòng và trạng thái còn trống hoặc tu sửa.</p>
        </section>

        <?php if ($message != "") { ?>
            <p class="success"><?php echo h($message); ?></p>
        <?php } ?>

        <?php if ($error != "") { ?>
            <p class="alert"><?php echo h($error); ?></p>
        <?php } ?>

        <section class="admin-card">
            <table class="room-table">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Phòng</th>
                        <th>Loại phòng</th>
                        <th>Sức chứa</th>
                        <th>Giá ngày</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rooms) > 0) { ?>
                        <?php foreach ($rooms as $room) { ?>
                            <tr>
                                <td>
                                    <img class="room-thumb" src="../<?php echo h($room["image_path"]); ?>" alt="<?php echo h($room["room_name"]); ?>">
                                </td>
                                <td>
                                    <strong><?php echo h($room["room_name"]); ?></strong><br>
                                    <span><?php echo h($room["room_code"]); ?></span>
                                </td>
                                <td><?php echo h($room["type_name"]); ?></td>
                                <td><?php echo h($room["capacity"]); ?> khách</td>
                                <td><?php echo money_vnd($room["price_per_day"]); ?></td>
                                <td>
                                    <?php if ($room["status"] == "available") { ?>
                                        <span class="status-badge status-available">Còn trống</span>
                                    <?php } else { ?>
                                        <span class="status-badge status-maintenance">Tu sửa</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <form class="status-form" method="post" action="rooms.php">
                                        <input type="hidden" name="room_id" value="<?php echo h($room["id"]); ?>">
                                        <select name="status">
                                            <option value="available" <?php if ($room["status"] == "available") echo "selected"; ?>>Còn trống</option>
                                            <option value="maintenance" <?php if ($room["status"] == "maintenance") echo "selected"; ?>>Tu sửa</option>
                                        </select>
                                        <button type="submit">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7">Chưa có dữ liệu phòng.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </div>
</main>

<?php include "includes/footer.php"; ?>
