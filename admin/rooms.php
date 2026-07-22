<?php
include "includes/auth.php";
require_admin_login();

include "../includes/db.php";
include "../includes/functions.php";

$assetPathPrefix = '../';
$hideBrandText = true;
$brandHref = 'index.php';

$pageTitle = "Quản lý phòng";
$thongBao = "";
$loi = "";

$formValues = array(
    'id_phong' => 0,
    'id_loai_phong' => '',
    'ma_so_phong' => '',
    'ten_phong' => '',
    'duong_dan_hinh' => '',
    'trang_thai' => 'dang_hoat_dong'
);

$editingRoom = null;
$roomTypes = array();
$showRoomForm = isset($_GET['add']) || isset($_GET['edit_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST["action"]) ? trim($_POST["action"]) : "";

    $formValues = array(
        'id_phong' => isset($_POST["id_phong"]) ? (int) $_POST["id_phong"] : 0,
        'id_loai_phong' => isset($_POST["id_loai_phong"]) ? (int) $_POST["id_loai_phong"] : 0,
        'ma_so_phong' => isset($_POST["ma_so_phong"]) ? trim($_POST["ma_so_phong"]) : "",
        'ten_phong' => isset($_POST["ten_phong"]) ? trim($_POST["ten_phong"]) : "",
        'duong_dan_hinh' => isset($_POST["duong_dan_hinh"]) ? trim($_POST["duong_dan_hinh"]) : "",
        'trang_thai' => isset($_POST["trang_thai"]) ? trim($_POST["trang_thai"]) : "dang_hoat_dong"
    );

    if ($action === "save_room") {
        $showRoomForm = true;
        if ($conn == null) {
            $loi = $dbError;
        } else {
            $idPhong = $formValues['id_phong'];
            $maSoPhong = $formValues['ma_so_phong'];
            $tenPhong = $formValues['ten_phong'];
            $idLoaiPhong = $formValues['id_loai_phong'];
            $trangThai = in_array($formValues['trang_thai'], array("dang_hoat_dong", "tu_sua")) ? $formValues['trang_thai'] : "dang_hoat_dong";
            $anhPhongUploaded = isset($_FILES['anh_phong']) && $_FILES['anh_phong']['error'] === UPLOAD_ERR_OK;

            if ($maSoPhong === "" || $tenPhong === "" || $idLoaiPhong <= 0) {
                $loi = "Vui lòng nhập đầy đủ mã phòng, tên phòng và loại phòng.";
            } else {
                $checkSql = "SELECT id_phong FROM phong WHERE ma_so_phong = ? AND id_phong != ?";
                $checkStmt = $conn->prepare($checkSql);

                if ($checkStmt) {
                    $checkStmt->bind_param("si", $maSoPhong, $idPhong);
                    $checkStmt->execute();
                    $checkStmt->store_result();

                    if ($checkStmt->num_rows > 0) {
                        $loi = "Mã phòng này đã tồn tại. Vui lòng chọn mã khác.";
                    } else {
                        if ($idPhong > 0) {
                            $sql = "UPDATE phong SET id_loai_phong = ?, ma_so_phong = ?, ten_phong = ?, trang_thai = ? WHERE id_phong = ?";
                            $stmt = $conn->prepare($sql);
                            if ($stmt) {
                                $stmt->bind_param("isssi", $idLoaiPhong, $maSoPhong, $tenPhong, $trangThai, $idPhong);
                                if ($stmt->execute()) {
                                    if ($anhPhongUploaded) {
                                        $targetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'room';
                                        if (!is_dir($targetDir)) {
                                            mkdir($targetDir, 0777, true);
                                        }
                                        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $idPhong . '.jpg';
                                        if (move_uploaded_file($_FILES['anh_phong']['tmp_name'], $targetPath)) {
                                            $relativePath = 'assets/room/' . $idPhong . '.jpg';
                                            $pathSql = "UPDATE phong SET duong_dan_hinh = ? WHERE id_phong = ?";
                                            $pathStmt = $conn->prepare($pathSql);
                                            if ($pathStmt) {
                                                $pathStmt->bind_param("si", $relativePath, $idPhong);
                                                $pathStmt->execute();
                                                $pathStmt->close();
                                            }
                                            $thongBao = "Đã thay đổi thông tin phòng thành công.";
                                            $showRoomForm = false;
                                            $editingRoom = null;
                                            $formValues['id_phong'] = 0;
                                            $formValues['id_loai_phong'] = '';
                                            $formValues['ma_so_phong'] = '';
                                            $formValues['ten_phong'] = '';
                                            $formValues['duong_dan_hinh'] = '';
                                            $formValues['trang_thai'] = 'dang_hoat_dong';
                                        } else {
                                            $loi = "Không lưu được file ảnh. Vui lòng thử lại.";
                                        }
                                    } else {
                                        $thongBao = "Đã cập nhật thông tin phòng.";
                                        $showRoomForm = false;
                                        $editingRoom = null;
                                        $formValues['id_phong'] = 0;
                                        $formValues['id_loai_phong'] = '';
                                        $formValues['ma_so_phong'] = '';
                                        $formValues['ten_phong'] = '';
                                        $formValues['duong_dan_hinh'] = '';
                                        $formValues['trang_thai'] = 'dang_hoat_dong';
                                    }
                                } else {
                                    $loi = "Không lưu được thông tin phòng. Thử lại sau.";
                                }
                                $stmt->close();
                            } else {
                                $loi = "Không chuẩn bị được câu lệnh cập nhật phòng.";
                            }
                        } else {
                            $sql = "INSERT INTO phong (id_loai_phong, ma_so_phong, ten_phong, duong_dan_hinh, trang_thai) VALUES (?, ?, ?, ?, ?)";
                            $stmt = $conn->prepare($sql);
                            if ($stmt) {
                                $defaultImagePath = '';
                                $stmt->bind_param("issss", $idLoaiPhong, $maSoPhong, $tenPhong, $defaultImagePath, $trangThai);
                                if ($stmt->execute()) {
                                    $newRoomId = $conn->insert_id;
                                    if ($anhPhongUploaded) {
                                        $targetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'room';
                                        if (!is_dir($targetDir)) {
                                            mkdir($targetDir, 0777, true);
                                        }
                                        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $newRoomId . '.jpg';
                                        if (move_uploaded_file($_FILES['anh_phong']['tmp_name'], $targetPath)) {
                                            $relativePath = 'assets/room/' . $newRoomId . '.jpg';
                                            $pathSql = "UPDATE phong SET duong_dan_hinh = ? WHERE id_phong = ?";
                                            $pathStmt = $conn->prepare($pathSql);
                                            if ($pathStmt) {
                                                $pathStmt->bind_param("si", $relativePath, $newRoomId);
                                                $pathStmt->execute();
                                                $pathStmt->close();
                                            }
                                            $thongBao = "Đã thêm phòng mới.";
                                            $showRoomForm = false;
                                            $editingRoom = null;
                                            $formValues['id_phong'] = 0;
                                            $formValues['id_loai_phong'] = '';
                                            $formValues['ma_so_phong'] = '';
                                            $formValues['ten_phong'] = '';
                                            $formValues['duong_dan_hinh'] = '';
                                            $formValues['trang_thai'] = 'dang_hoat_dong';
                                        } else {
                                            $conn->query("DELETE FROM phong WHERE id_phong = $newRoomId");
                                            $loi = "Không lưu được file ảnh. Phòng chưa được tạo.";
                                        }
                                    } else {
                                        $thongBao = "Đã thêm phòng mới thành công.";
                                        $showRoomForm = false;
                                        $editingRoom = null;
                                        $formValues['id_phong'] = 0;
                                        $formValues['id_loai_phong'] = '';
                                        $formValues['ma_so_phong'] = '';
                                        $formValues['ten_phong'] = '';
                                        $formValues['duong_dan_hinh'] = '';
                                        $formValues['trang_thai'] = 'dang_hoat_dong';
                                    }
                                } else {
                                    $loi = "Không thêm được phòng mới. Thử lại sau.";
                                }
                                $stmt->close();
                            } else {
                                $loi = "Không chuẩn bị được câu lệnh thêm phòng.";
                            }
                        }
                    }
                    $checkStmt->close();
                } else {
                    $loi = "Không kiểm tra được mã phòng trùng.";
                }
            }
        }
    } elseif ($action === "delete_room") {
        $idPhong = isset($_POST["id_phong"]) ? (int) $_POST["id_phong"] : 0;
        if ($idPhong <= 0) {
            $loi = "Không xác định được phòng cần xóa.";
        } elseif ($conn == null) {
            $loi = $dbError;
        } else {
            $countSql = "SELECT COUNT(*) AS total FROM dat_phong WHERE id_phong = ? AND (DATE(ngay_nhan_phong) >= CURDATE() OR DATE(ngay_tra_phong) >= CURDATE())";
            $countStmt = $conn->prepare($countSql);
            if ($countStmt) {
                $countStmt->bind_param("i", $idPhong);
                $countStmt->execute();
                $countStmt->bind_result($totalDatPhong);
                $countStmt->fetch();
                $countStmt->close();

                if ($totalDatPhong > 0) {
                    $loi = "Không thể xóa phòng vì phòng này vẫn có người đã đặt từ hôm nay trở đi.";
                } else {
                    $deleteSql = "DELETE FROM phong WHERE id_phong = ?";
                    $deleteStmt = $conn->prepare($deleteSql);
                    if ($deleteStmt) {
                        $deleteStmt->bind_param("i", $idPhong);
                        if ($deleteStmt->execute()) {
                            $thongBao = "Đã xóa phòng khỏi hệ thống.";
                        } else {
                            $loi = "Không xóa được phòng. Thử lại sau.";
                        }
                        $deleteStmt->close();
                    } else {
                        $loi = "Không chuẩn bị được câu lệnh xóa phòng.";
                    }
                }
            } else {
                $loi = "Không kiểm tra được dữ liệu đặt phòng.";
            }
        }
    }
}

$editId = isset($_GET["edit_id"]) ? (int) $_GET["edit_id"] : 0;
if ($editId > 0 && $conn != null) {
    $editSql = "SELECT id_phong, id_loai_phong, ma_so_phong, ten_phong, duong_dan_hinh, trang_thai FROM phong WHERE id_phong = ?";
    $editStmt = $conn->prepare($editSql);
    if ($editStmt) {
        $editStmt->bind_param("i", $editId);
        $editStmt->execute();
        $editResult = $editStmt->get_result();
        if ($editResult && $editResult->num_rows > 0) {
            $editingRoom = $editResult->fetch_assoc();
            $formValues = array(
                'id_phong' => (int) $editingRoom['id_phong'],
                'id_loai_phong' => (int) $editingRoom['id_loai_phong'],
                'ma_so_phong' => $editingRoom['ma_so_phong'],
                'ten_phong' => $editingRoom['ten_phong'],
                'duong_dan_hinh' => $editingRoom['duong_dan_hinh'],
                'trang_thai' => $editingRoom['trang_thai']
            );
            $showRoomForm = true;
        }
        $editStmt->close();
    }
}

$danhSachPhong = array();

if ($conn != null) {
    $roomTypeSql = "SELECT id_loai_phong, ten_loai_phong, suc_chua, gia_theo_ngay FROM loai_phong ORDER BY id_loai_phong ASC";
    $roomTypeResult = $conn->query($roomTypeSql);
    if ($roomTypeResult) {
        while ($roomType = $roomTypeResult->fetch_assoc()) {
            $roomTypes[] = $roomType;
        }
    }

    $sql = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.duong_dan_hinh, p.trang_thai,
                   lp.ten_loai_phong, lp.suc_chua, lp.gia_theo_ngay,
                   (SELECT COUNT(*)
                    FROM dat_phong dp
                    WHERE dp.id_phong = p.id_phong
                      AND (DATE(dp.ngay_nhan_phong) >= CURDATE() OR DATE(dp.ngay_tra_phong) >= CURDATE())) AS co_booking_tuong_lai
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

$headerNavItems = array(
    array('href' => 'index.php', 'label' => 'Trang chủ'),
    array('href' => 'rooms.php', 'label' => 'Quản lý phòng'),
    array('href' => 'logout.php', 'label' => 'Đăng xuất'),
);

include "../includes/header.php";
?>

<main class="admin-main">
    <div class="admin-container">
        <section class="page-title">
            <div>
                <h1>Quản lý phòng</h1>
                <p>Quản trị có thể thêm phòng mới, chỉnh sửa thông tin phòng và cập nhật trạng thái hoạt động.</p>
            </div>
            <?php if (!$showRoomForm) { ?>
                <a class="button" href="rooms.php?add=1">Thêm phòng</a>
            <?php } ?>
        </section>

        <?php if ($thongBao != "") { ?>
            <p class="success"><?php echo h($thongBao); ?></p>
        <?php } ?>

        <?php if ($loi != "") { ?>
            <p class="alert"><?php echo h($loi); ?></p>
        <?php } ?>

        <?php if ($showRoomForm) { ?>
        <section class="room-admin-form-card room-admin-form-section">
            <form class="room-admin-form" method="post" action="rooms.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_room">
                    <?php if ($editingRoom != null) { ?>
                        <input type="hidden" name="id_phong" value="<?php echo h($formValues['id_phong']); ?>">
                    <?php } ?>
                    <div class="room-admin-form-title">
                        <h3><?php echo $editingRoom != null ? 'Chỉnh sửa phòng' : 'Thêm phòng mới'; ?></h3>
                        <p><?php echo $editingRoom != null ? 'Cập nhật thông tin cho phòng này.' : 'Điền đầy đủ thông tin để tạo phòng mới.'; ?></p>
                    </div>
                    <div class="room-admin-form-grid">
                        <label>
                            <span>Mã phòng</span>
                            <input type="text" name="ma_so_phong" value="<?php echo h($formValues['ma_so_phong']); ?>" required>
                        </label>
                        <label>
                            <span>Tên phòng</span>
                            <input type="text" name="ten_phong" value="<?php echo h($formValues['ten_phong']); ?>" required>
                        </label>
                        <label>
                            <span>Loại phòng</span>
                            <select name="id_loai_phong" required>
                                <option value="">-- Chọn loại phòng --</option>
                                <?php foreach ($roomTypes as $loai) { ?>
                                    <option value="<?php echo h($loai['id_loai_phong']); ?>" <?php if ((int) $formValues['id_loai_phong'] === (int) $loai['id_loai_phong']) echo 'selected'; ?>><?php echo h($loai['ten_loai_phong']); ?> (<?php echo h($loai['suc_chua']); ?> khách)</option>
                                <?php } ?>
                            </select>
                        </label>
                        <label>
                            <span>Ảnh phòng</span>
                            <input type="file" name="anh_phong" accept="image/*">
                        </label>
                        <label>
                            <span>Trạng thái</span>
                            <select name="trang_thai">
                                <option value="dang_hoat_dong" <?php if ($formValues['trang_thai'] == 'dang_hoat_dong') echo 'selected'; ?>>Hoạt động</option>
                                <option value="tu_sua" <?php if ($formValues['trang_thai'] == 'tu_sua') echo 'selected'; ?>>Đang tu sửa</option>
                            </select>
                        </label>
                    </div>
                    <div class="room-admin-actions">
                        <button type="submit"><?php echo $editingRoom != null ? 'Lưu thay đổi' : 'Thêm phòng'; ?></button>
                        <a class="button button-secondary" href="rooms.php">Đóng</a>
                    </div>
                </form>
        </section>
        <?php } ?>

        <?php if (!$showRoomForm) { ?>
        <section class="room-admin-list-card room-admin-list-section">
            <div class="room-admin-list-intro">
                <h2>Danh sách phòng</h2>
                <p>Nhấn vào tên phòng để mở form chỉnh sửa. Chỉ có thể xóa phòng khi chưa có booking từ hôm nay trở đi.</p>
            </div>
            <table class="room-table">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Phòng</th>
                        <th>Loại phòng</th>
                        <th>Sức chứa</th>
                        <th>Giá ngày</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($danhSachPhong) > 0) { ?>
                        <?php foreach ($danhSachPhong as $phong) { ?>
                            <tr>
                                <td class="room-image-cell">
                                    <a class="room-image-link" href="rooms.php?edit_id=<?php echo h($phong["id_phong"]); ?>" aria-label="Chỉnh sửa phòng <?php echo h($phong["ten_phong"]); ?>">
                                        <img class="room-thumb" src="<?php echo h(asset_url($assetPathPrefix . $phong["duong_dan_hinh"])); ?>" alt="<?php echo h($phong["ten_phong"]); ?>" draggable="false" loading="lazy">
                                    </a>
                                </td>
                                <td>
                                    <a class="room-name-link" href="rooms.php?edit_id=<?php echo h($phong["id_phong"]); ?>">
                                        <strong><?php echo h($phong["ten_phong"]); ?></strong><br>
                                        <span><?php echo h($phong["ma_so_phong"]); ?></span>
                                    </a>
                                </td>
                                <td><?php echo h($phong["ten_loai_phong"]); ?></td>
                                <td><?php echo h($phong["suc_chua"]); ?> khách</td>
                                <td><?php echo tien_viet_nam($phong["gia_theo_ngay"]); ?></td>
                                <td>
                                    <?php if ($phong["trang_thai"] == "dang_hoat_dong") { ?>
                                        <span class="nhan-trang-thai trang-thai-con-trong">Hoạt động</span>
                                    <?php } else { ?>
                                        <span class="nhan-trang-thai trang-thai-tu-sua">Đang tu sửa</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <div class="room-action-stack">
                                        <form method="post" action="rooms.php" onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?');">
                                            <input type="hidden" name="action" value="delete_room">
                                            <input type="hidden" name="id_phong" value="<?php echo h($phong["id_phong"]); ?>">
                                            <button type="submit" <?php if ((int) $phong["co_booking_tuong_lai"] > 0) echo 'disabled title="Phòng này có booking nên không thể xóa"'; ?>>Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7">Chưa có phòng nào trong hệ thống. Chắc database đang trống.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
        <?php } ?>
    </div>
</main>

<?php include "../includes/footer.php"; ?>
