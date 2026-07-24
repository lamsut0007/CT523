<?php
include "includes/auth.php";
require_admin_login();

include "../includes/db.php";
include "../includes/functions.php";

$assetPathPrefix = '../';
$hideBrandText   = true;
$brandHref       = 'index.php';
$pageTitle       = "Quản lý đặt phòng";

$thongBao = "";
$loi      = "";

// ── Xử lý POST ───────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action      = trim($_POST["action"]);
    $idDatPhong  = isset($_POST["id_dat_phong"]) ? (int) $_POST["id_dat_phong"] : 0;
    $idPhongPost = isset($_POST["id_phong"])      ? (int) $_POST["id_phong"]     : 0;

    if ($conn === null) {
        $loi = $dbError;
    } elseif ($idDatPhong <= 0) {
        $loi = "Không xác định được đơn đặt phòng.";
    } elseif ($action === "toggle_xac_nhan") {
        // Lấy trạng thái hiện tại
        $stmtGet = $conn->prepare("SELECT trang_thai FROM dat_phong WHERE id_dat_phong = ?");
        if ($stmtGet) {
            $stmtGet->bind_param("i", $idDatPhong);
            $stmtGet->execute();
            $stmtGet->bind_result($trangThaiHienTai);
            $stmtGet->fetch();
            $stmtGet->close();

            $trangThaiMoi = ($trangThaiHienTai === "da_xac_nhan") ? "cho_xac_nhan" : "da_xac_nhan";

            $stmtUpd = $conn->prepare("UPDATE dat_phong SET trang_thai = ? WHERE id_dat_phong = ?");
            if ($stmtUpd) {
                $stmtUpd->bind_param("si", $trangThaiMoi, $idDatPhong);
                if ($stmtUpd->execute()) {
                    $thongBao = $trangThaiMoi === "da_xac_nhan"
                        ? "Đã xác nhận đơn đặt phòng."
                        : "Đã chuyển đơn về trạng thái chờ xác nhận.";
                } else {
                    $loi = "Không cập nhật được trạng thái. Thử lại sau.";
                }
                $stmtUpd->close();
            } else {
                $loi = "Lỗi chuẩn bị câu lệnh cập nhật.";
            }
        } else {
            $loi = "Không tìm được đơn đặt phòng.";
        }
    } elseif ($action === "xoa_don") {
        $stmtDel = $conn->prepare("DELETE FROM dat_phong WHERE id_dat_phong = ?");
        if ($stmtDel) {
            $stmtDel->bind_param("i", $idDatPhong);
            if ($stmtDel->execute()) {
                $thongBao = "Đã xóa đơn đặt phòng.";
            } else {
                $loi = "Không xóa được đơn. Thử lại sau.";
            }
            $stmtDel->close();
        } else {
            $loi = "Lỗi chuẩn bị câu lệnh xóa.";
        }
    }

    // PRG redirect
    $redirectUrl = "bookings.php";
    if ($idPhongPost > 0) {
        $redirectUrl .= "?id_phong=" . $idPhongPost;
    }
    if ($thongBao !== "") {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "msg=" . urlencode($thongBao);
    }
    if ($loi !== "") {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "err=" . urlencode($loi);
    }
    header("Location: " . $redirectUrl);
    exit;
}

// Flash messages từ redirect
if ($thongBao === "" && isset($_GET["msg"])) {
    $thongBao = trim($_GET["msg"]);
}
if ($loi === "" && isset($_GET["err"])) {
    $loi = trim($_GET["err"]);
}

// ── Chọn phòng ────────────────────────────────────────────────────────────
$idPhongChon = isset($_GET["id_phong"]) ? (int) $_GET["id_phong"] : 0;

// ── Lấy danh sách phòng ──────────────────────────────────────────────────
$danhSachPhong = [];
if ($conn !== null) {
    $sqlPhong = "SELECT p.id_phong, p.ma_so_phong, p.ten_phong, p.duong_dan_hinh, p.trang_thai,
                        lp.ten_loai_phong,
                        SUM(CASE WHEN dp.trang_thai = 'cho_xac_nhan' THEN 1 ELSE 0 END) AS cho_xac_nhan
                 FROM phong p
                 INNER JOIN loai_phong lp ON p.id_loai_phong = lp.id_loai_phong
                 LEFT JOIN dat_phong dp ON dp.id_phong = p.id_phong
                 GROUP BY p.id_phong
                 ORDER BY p.ma_so_phong ASC";
    $resPhong = $conn->query($sqlPhong);
    if ($resPhong) {
        while ($row = $resPhong->fetch_assoc()) {
            $danhSachPhong[] = $row;
        }
    } else {
        $loi = "Không tải được danh sách phòng.";
    }
} else {
    $loi = $dbError;
}

// ── Lấy danh sách đặt phòng cho phòng được chọn ──────────────────────────
$danhSachDatPhong = [];
$phongDuocChon    = null;

if ($idPhongChon > 0 && $conn !== null) {
    foreach ($danhSachPhong as $p) {
        if ((int)$p['id_phong'] === $idPhongChon) {
            $phongDuocChon = $p;
            break;
        }
    }

    // Sort theo trạng thái: chưa xác nhận lên trước, đã xác nhận xuống dưới, rồi theo ngày tạo sớm nhất.
    $sqlDat = "SELECT id_dat_phong, ho_ten, so_dien_thoai, email,
                      ngay_nhan_phong, ngay_tra_phong, kieu_dat,
                      tong_tien, trang_thai, ghi_chu, ngay_tao
               FROM dat_phong
               WHERE id_phong = ?
               ORDER BY
                   CASE
                       WHEN trang_thai = 'cho_xac_nhan' THEN 0
                       WHEN trang_thai = 'da_xac_nhan' THEN 1
                       ELSE 2
                   END,
                   ngay_tao ASC";
    $stmtDat = $conn->prepare($sqlDat);
    if ($stmtDat) {
        $stmtDat->bind_param("i", $idPhongChon);
        $stmtDat->execute();
        $resDat = $stmtDat->get_result();
        while ($row = $resDat->fetch_assoc()) {
            $danhSachDatPhong[] = $row;
        }
        $stmtDat->close();
    }

    // ── Phát hiện trùng lịch ─────────────────────────────────────────────
    $lichDaDat = [];
    foreach ($danhSachDatPhong as $dp) {
        if (in_array($dp['trang_thai'], ['da_xac_nhan', 'hoan_thanh'])) {
            $lichDaDat[] = [
                'tu'   => strtotime($dp['ngay_nhan_phong']),
                'den'  => strtotime($dp['ngay_tra_phong']),
                'id'   => (int)$dp['id_dat_phong'],
                'nhan' => $dp['ngay_nhan_phong'],
                'tra'  => $dp['ngay_tra_phong'],
            ];
        }
    }

    foreach ($danhSachDatPhong as &$dp) {
        $dp['trung_lich'] = false;
        $dp['trung_voi']  = [];

        if (in_array($dp['trang_thai'], ['da_xac_nhan', 'hoan_thanh', 'da_huy'])) {
            continue;
        }

        $tuDp  = strtotime($dp['ngay_nhan_phong']);
        $denDp = strtotime($dp['ngay_tra_phong']);

        foreach ($lichDaDat as $lich) {
            if ($lich['id'] === (int)$dp['id_dat_phong']) continue;
            if ($tuDp < $lich['den'] && $denDp > $lich['tu']) {
                $dp['trung_lich'] = true;
                $dp['trung_voi'][] = [
                    'tu'  => $lich['nhan'],
                    'den' => $lich['tra'],
                    'id'  => $lich['id'],
                ];
            }
        }
    }
    unset($dp);
}

$headerNavItems = [
    ['href' => 'index.php',    'label' => 'Trang chủ'],
    ['href' => 'rooms.php',    'label' => 'Quản lý phòng'],
    ['href' => 'bookings.php', 'label' => 'Quản lý đặt phòng'],
    ['href' => 'logout.php',   'label' => 'Đăng xuất'],
];

include "../includes/header.php";
?>

<main class="admin-main">
    <div class="admin-container">

        <section class="page-title">
            <div>
                <h1>Quản lý đặt phòng</h1>
                <p>Chọn phòng để xem danh sách đặt phòng và xác nhận thanh toán.</p>
            </div>
        </section>

        <?php if ($thongBao !== "") { ?>
            <p class="success"><?php echo h($thongBao); ?></p>
        <?php } ?>

        <?php if ($loi !== "") { ?>
            <p class="alert"><?php echo h($loi); ?></p>
        <?php } ?>

        <!-- ── Danh sách phòng ── -->
        <section class="room-admin-list-card room-admin-list-section booking-room-list-section">
            <div class="room-admin-list-intro">
                <h2>Danh sách phòng</h2>
                <p>Nhấn vào một phòng để xem và quản lý các đơn đặt phòng của phòng đó.</p>
            </div>

            <?php if (count($danhSachPhong) > 0) { ?>
                <div class="booking-room-grid">
                    <?php foreach ($danhSachPhong as $phong) { ?>
                        <?php
                        $isSelected = ((int)$phong['id_phong'] === $idPhongChon);
                        $imgSrc = h(asset_url($assetPathPrefix . $phong['duong_dan_hinh']));
                        ?>
                        <a class="booking-room-card <?php echo $isSelected ? 'booking-room-card--active' : ''; ?>"
                           href="bookings.php?id_phong=<?php echo h($phong['id_phong']); ?>#booking-list">
                            <div class="booking-room-img-wrap">
                                <img class="booking-room-img" src="<?php echo $imgSrc; ?>" alt="<?php echo h($phong['ten_phong']); ?>" loading="lazy">
                                <span class="booking-room-badge <?php echo $phong['trang_thai'] === 'dang_hoat_dong' ? 'trang-thai-con-trong' : 'trang-thai-tu-sua'; ?>">
                                    <?php echo $phong['trang_thai'] === 'dang_hoat_dong' ? 'Hoạt động' : 'Tu sửa'; ?>
                                </span>
                            </div>
                            <div class="booking-room-info">
                                <strong class="booking-room-name"><?php echo h($phong['ten_phong']); ?></strong>
                                <span class="booking-room-meta"><?php echo h($phong['ma_so_phong']); ?> · <?php echo h($phong['ten_loai_phong']); ?></span>
                                <span class="booking-room-count"><?php echo (int)$phong['cho_xac_nhan']; ?> đơn chờ</span>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p class="booking-empty">Chưa có phòng nào trong hệ thống.</p>
            <?php } ?>
        </section>

        <!-- ── Danh sách đặt phòng của phòng được chọn ── -->
        <?php if ($idPhongChon > 0) { ?>
        <section class="room-admin-list-card room-admin-list-section booking-list-section" id="booking-list">
            <div class="booking-list-header">
                <div class="room-admin-list-intro" style="margin-bottom:0">
                    <h2>
                        Đơn đặt phòng -
                        <?php echo $phongDuocChon ? h($phongDuocChon['ten_phong']) : 'Phòng #' . $idPhongChon; ?>
                    </h2>
                </div>
            </div>

            <?php if (count($danhSachDatPhong) > 0) { ?>
            <div class="booking-table-wrap">
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>Thông tin</th>
                            <th>Ngày gửi</th>
                            <th>Nhận & Trả phòng</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSachDatPhong as $dp) { ?>
                            <?php
                            $trangThai   = $dp['trang_thai'];
                            $trungLich   = $dp['trung_lich'];
                            $isXacNhan   = ($trangThai === 'da_xac_nhan');
                            $isHoanThanh = ($trangThai === 'hoan_thanh');
                            $isHuy       = ($trangThai === 'da_huy');

                            // Chuỗi trùng lịch cho tooltip title
                            $trungVoiStr = "";
                            if ($trungLich && count($dp['trung_voi']) > 0) {
                                $parts = [];
                                foreach ($dp['trung_voi'] as $tv) {
                                    $parts[] = date('d/m/Y H:i', strtotime($tv['tu'])) . ' – ' . date('d/m/Y H:i', strtotime($tv['den']));
                                }
                                $trungVoiStr = implode(', ', $parts);
                            }

                            // Badge label & class
                            $badgeClass = match($trangThai) {
                                'da_xac_nhan' => 'booking-badge--confirmed',
                                'hoan_thanh'  => 'booking-badge--done',
                                'da_huy'      => 'booking-badge--cancelled',
                                default       => 'booking-badge--pending',
                            };
                            $badgeLabel = match($trangThai) {
                                'da_xac_nhan' => '✓ Đã xác nhận',
                                'hoan_thanh'  => 'Hoàn thành',
                                'da_huy'      => 'Đã hủy',
                                default       => 'Chờ xác nhận',
                            };
                            $kieuLabel = ($dp['kieu_dat'] === 'gio') ? 'Đặt theo giờ' : 'Đặt theo ngày';
                            ?>
                            <tr class="<?php echo $trungLich ? 'booking-row--conflict' : ''; ?>">
                                <td>
                                    <strong><?php echo h($dp['ho_ten']); ?></strong>
                                    <br><?php echo h($dp['so_dien_thoai']); ?>
                                    <?php if ($dp['email']) { ?>
                                        <br><span class="booking-email"><?php echo h($dp['email']); ?></span>
                                    <?php } ?>
                                    <?php if ($dp['ghi_chu']) { ?>
                                        <br><span class="booking-note"><?php echo h($dp['ghi_chu']); ?></span>
                                    <?php } ?>
                                </td>
                                <td class="booking-date-cell"><?php echo date('d/m/Y H:i', strtotime($dp['ngay_tao'])); ?></td>
                                <td class="booking-date-cell">
                                    <div class="booking-date-pair">
                                        <span class="booking-sublabel">Nhận:</span>
                                        <span><?php echo date('d/m/Y H:i', strtotime($dp['ngay_nhan_phong'])); ?></span>
                                        <span class="booking-sublabel">Trả:</span>
                                        <span><?php echo date('d/m/Y H:i', strtotime($dp['ngay_tra_phong'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <strong class="booking-price" title="<?php echo h($kieuLabel); ?>">
                                        <?php echo tien_viet_nam($dp['tong_tien']); ?>
                                    </strong>
                                </td>
                                <td class="booking-status-cell">
                                    <?php if ($isHoanThanh || $isHuy) { ?>
                                        <span class="booking-badge <?php echo $badgeClass; ?>">
                                            <?php echo $badgeLabel; ?>
                                        </span>
                                    <?php } elseif ($trungLich) { ?>
                                        <span class="booking-badge booking-badge--conflict"
                                              title="<?php echo h('Đã được đặt vào: ' . $trungVoiStr); ?>">
                                            ⚠ Trùng lịch
                                        </span>
                                    <?php } else { ?>
                                        <form method="post" action="bookings.php?id_phong=<?php echo h($idPhongChon); ?>#booking-list">
                                            <input type="hidden" name="action"       value="toggle_xac_nhan">
                                            <input type="hidden" name="id_dat_phong" value="<?php echo h($dp['id_dat_phong']); ?>">
                                            <input type="hidden" name="id_phong"     value="<?php echo h($idPhongChon); ?>">
                                            <button type="submit"
                                                    class="booking-toggle-btn <?php echo $isXacNhan ? 'booking-toggle-btn--confirmed' : 'booking-toggle-btn--pending'; ?>">
                                                <?php echo $badgeLabel; ?>
                                            </button>
                                        </form>
                                    <?php } ?>
                                </td>
                                <td class="booking-delete-cell">
                                    <form method="post"
                                          action="bookings.php?id_phong=<?php echo h($idPhongChon); ?>#booking-list"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa đơn đặt phòng của <?php echo h(addslashes($dp['ho_ten'])); ?>?');">
                                        <input type="hidden" name="action"       value="xoa_don">
                                        <input type="hidden" name="id_dat_phong" value="<?php echo h($dp['id_dat_phong']); ?>">
                                        <input type="hidden" name="id_phong"     value="<?php echo h($idPhongChon); ?>">
                                        <button type="submit" class="booking-delete-btn">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } else { ?>
                <p class="booking-empty">Phòng này chưa có đơn đặt nào.</p>
            <?php } ?>
        </section>
        <?php } ?>

    </div>
</main>

<?php include "../includes/footer.php"; ?>
