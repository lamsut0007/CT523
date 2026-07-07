<?php
$pageTitle = "Danh sách phòng - OASIS Homestay";
include "includes/db.php";
include "includes/functions.php";

$ngayNhanPhong = isset($_GET["ngay_nhan_phong"]) ? trim($_GET["ngay_nhan_phong"]) : "";
$ngayTraPhong = isset($_GET["ngay_tra_phong"]) ? trim($_GET["ngay_tra_phong"]) : "";
$soKhach = isset($_GET["so_khach"]) ? (int) $_GET["so_khach"] : 2;
$loiNgay = "";
$loiTaiPhong = "";
$coTimKiem = isset($_GET["ngay_nhan_phong"]) || isset($_GET["ngay_tra_phong"]) || isset($_GET["so_khach"]);

if ($soKhach < 1) {
    $soKhach = 1;
}

if (($ngayNhanPhong == "" && $ngayTraPhong != "") || ($ngayNhanPhong != "" && $ngayTraPhong == "")) {
    $loiNgay = "Nhập giúp tụi mình đủ ngày nhận và ngày trả nha, chỉ một nửa lịch thì hệ thống không biết lọc sao.";
    $ngayNhanPhong = "";
    $ngayTraPhong = "";
} elseif (!ngay_tim_kiem_hop_le($ngayNhanPhong, $ngayTraPhong)) {
    $loiNgay = "Ngày trả phòng phải sau ngày nhận phòng. Mình đi biển vui thôi, đừng đảo ngược thời gian nha.";
    $ngayNhanPhong = "";
    $ngayTraPhong = "";
}

$danhSachPhong = lay_phong_con_trong($conn, $ngayNhanPhong, $ngayTraPhong, $soKhach);
include "includes/header.php";
?>

<main>
    <section class="page-heading">
        <p class="eyebrow">Danh sách phòng</p>
        <h1>Chọn một căn phòng vừa ngắm biển, vừa nghỉ cho đúng mood Hòn Sơn</h1>
        <p>
            Nhập ngày ở và số khách, tụi mình sẽ lọc ra những phòng còn trống trong đúng khung thời gian đó.<br>
            Không nhập ngày thì trang chỉ hiển thị các phòng đang sẵn sàng nhận khách.
        </p>
    </section>

    <section class="search-box search-box-normal">
        <h2>Kiểm tra phòng còn trống</h2>
        <form method="get" action="rooms.php">
            <div class="form-row">
                <label>
                    Ngày nhận phòng
                    <input type="date" name="ngay_nhan_phong" value="<?php echo h($ngayNhanPhong); ?>">
                </label>

                <label>
                    Ngày trả phòng
                    <input type="date" name="ngay_tra_phong" value="<?php echo h($ngayTraPhong); ?>">
                </label>

                <label>
                    Số khách
                    <input type="number" name="so_khach" min="1" value="<?php echo h($soKhach); ?>">
                </label>

                <button type="submit">Tìm phòng</button>
            </div>
        </form>

        <?php if ($dbError != "") { ?>
            <p class="alert"><?php echo h($dbError); ?></p>
        <?php } ?>

        <?php if ($loiNgay != "") { ?>
            <p class="alert"><?php echo h($loiNgay); ?></p>
        <?php } ?>

        <?php if ($loiTaiPhong != "") { ?>
            <p class="alert"><?php echo h($loiTaiPhong); ?></p>
        <?php } ?>
    </section>

    <section class="section" id="rooms">
        <div class="section-title">
            <p class="eyebrow">Phòng lưu trú</p>
            <h2>
                <?php if ($ngayNhanPhong != "" && $ngayTraPhong != "") { ?>
                    Phòng còn trống từ <?php echo h(dinh_dang_ngay($ngayNhanPhong)); ?> đến <?php echo h(dinh_dang_ngay($ngayTraPhong)); ?>
                <?php } else { ?>
                    Những phòng đang mở cửa chờ khách
                <?php } ?>
            </h2>
        </div>

        <div class="room-list">
            <?php if (count($danhSachPhong) > 0) { ?>
                <?php foreach ($danhSachPhong as $phong) { ?>
                    <article class="room-card">
                        <div class="room-image">
                            <img src="<?php echo h($phong["duong_dan_hinh"]); ?>" alt="<?php echo h($phong["ten_phong"]); ?>">
                        </div>
                        <div class="room-info">
                            <p class="badge"><?php echo h($phong["ma_so_phong"]); ?></p>
                            <h3><?php echo h($phong["ten_phong"]); ?></h3>
                            <p><?php echo h($phong["mo_ta"]); ?></p>
                            <p class="room-meta">
                                Loại phòng: <?php echo h($phong["ten_loai_phong"]); ?> |
                                Sức chứa: <?php echo h($phong["suc_chua"]); ?> khách
                            </p>
                            <p class="price"><?php echo tien_viet_nam($phong["gia_theo_ngay"]); ?> / đêm</p>
                            <p class="muted"><?php echo tien_viet_nam($phong["gia_theo_gio"]); ?> / giờ</p>
                        </div>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <p class="empty">Chưa thấy phòng nào hợp lịch này. Thử đổi ngày hoặc giảm số khách một chút nha.</p>
            <?php } ?>
        </div>
    </section>
</main>

<?php include "includes/footer.php"; ?>
