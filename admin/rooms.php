<?php
include "includes/auth.php";
require_admin_login();

include "../includes/db.php";
include "../includes/functions.php";

$pageTitle = "Quản lý phòng";
$thongBao = "";
$loi = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idPhong = isset($_POST["id_phong"]) ? (int) $_POST["id_phong"] : 0;
    $trangThai = isset($_POST["trang_thai"]) ? trim($_POST["trang_thai"]) : "";

    if ($idPhong <= 0 || !in_array($trangThai, array("con_trong", "tu_sua"))) {
        $loi = "Dữ liệu gửi lên hơi sai sai. Chọn lại trạng thái phòng giúp mình nha.";
    } elseif ($conn == null) {
        $loi = $dbError;
    } else {
        $sql = "UPDATE phong SET trang_thai = ? WHERE id_phong = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("si", $trangThai, $idPhong);
            if ($stmt->execute()) {
                $thongBao = "Đã cập nhật trạng thái phòng. Một cú lưu nhỏ, căn nhà gọn hơn một chút.";
            } else {
                $loi = "Chưa lưu được trạng thái phòng. Thử lại giúp mình nha.";
            }
            $stmt->close();
        } else {
            $loi = "Chưa chuẩn bị được câu lệnh cập nhật. Khả năng cao database đang lệch schema.";
        }
    }
}

$danhSachPhong = array();

if ($conn != null) {
    $sql = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.duong_dan_hinh, p.trang_thai,
                   lp.ten_loai_phong, lp.suc_chua, lp.gia_theo_ngay
            FROM phong p
            INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong
            ORDER BY p.ma_so_phong ASC";

    $ketQua = $conn->query($sql);

    if ($ketQua) {
        while ($dong = $ketQua->fetch_assoc()) {
            $danhSachPhong[] = $dong;
        }
    } else {
        $loi = "Chưa tải được danh sách phòng. Import lại files/db/schema.sql để admin đọc đúng bảng mới nha.";
    }
} elseif ($loi == "") {
    $loi = $dbError;
}

include "includes/header.php";
?>

<main class="admin-main">
    <div class="admin-container">
        <section class="page-title">
            <h1>Quản lý phòng</h1>
            <p>Admin chỉ chạm vào thứ khách nhìn thấy: phòng nào còn trống, phòng nào đang cần tu sửa. Vậy là đủ cho bước đầu.</p>
        </section>

        <?php if ($thongBao != "") { ?>
            <p class="success"><?php echo h($thongBao); ?></p>
        <?php } ?>

        <?php if ($loi != "") { ?>
            <p class="alert"><?php echo h($loi); ?></p>
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
                    <?php if (count($danhSachPhong) > 0) { ?>
                        <?php foreach ($danhSachPhong as $phong) { ?>
                            <tr>
                                <td>
                                    <img class="room-thumb" src="../<?php echo h($phong["duong_dan_hinh"]); ?>" alt="<?php echo h($phong["ten_phong"]); ?>">
                                </td>
                                <td>
                                    <strong><?php echo h($phong["ten_phong"]); ?></strong><br>
                                    <span><?php echo h($phong["ma_so_phong"]); ?></span>
                                </td>
                                <td><?php echo h($phong["ten_loai_phong"]); ?></td>
                                <td><?php echo h($phong["suc_chua"]); ?> khách</td>
                                <td><?php echo tien_viet_nam($phong["gia_theo_ngay"]); ?></td>
                                <td>
                                    <?php if ($phong["trang_thai"] == "con_trong") { ?>
                                        <span class="nhan-trang-thai trang-thai-con-trong">Còn trống</span>
                                    <?php } else { ?>
                                        <span class="nhan-trang-thai trang-thai-tu-sua">Tu sửa</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <form class="form-trang-thai" method="post" action="rooms.php">
                                        <input type="hidden" name="id_phong" value="<?php echo h($phong["id_phong"]); ?>">
                                        <select name="trang_thai">
                                            <option value="con_trong" <?php if ($phong["trang_thai"] == "con_trong") echo "selected"; ?>>Còn trống</option>
                                            <option value="tu_sua" <?php if ($phong["trang_thai"] == "tu_sua") echo "selected"; ?>>Tu sửa</option>
                                        </select>
                                        <button type="submit">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7">Chưa có phòng nào trong hệ thống. Nhà còn im ắng quá.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </div>
</main>

<?php include "includes/footer.php"; ?>
