<?php
$pageTitle = "Danh sách phòng - OASIS Homestay";
include "includes/db.php";
include "includes/functions.php";

$ngayNhanPhong = isset($_GET["ngay_nhan_phong"]) ? trim($_GET["ngay_nhan_phong"]) : "";
$ngayTraPhong = isset($_GET["ngay_tra_phong"]) ? trim($_GET["ngay_tra_phong"]) : "";
$gioNhanPhong = isset($_GET["gio_nhan_phong"]) ? trim($_GET["gio_nhan_phong"]) : "14:00";
$gioTraPhong = isset($_GET["gio_tra_phong"]) ? trim($_GET["gio_tra_phong"]) : "12:00";
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
} elseif (!ngay_tim_kiem_hop_le($ngayNhanPhong, $ngayTraPhong, $gioNhanPhong, $gioTraPhong)) {
    $loiNgay = "Ngày trả phòng phải sau ngày nhận phòng. Mình đi biển vui thôi, đừng đảo ngược thời gian nha.";
    $ngayNhanPhong = "";
    $ngayTraPhong = "";
}

$danhSachPhong = lay_phong_dang_hoat_dong($conn, $ngayNhanPhong, $ngayTraPhong, $soKhach, $gioNhanPhong, $gioTraPhong);
include "includes/header.php";
?>

<main>
    <section class="page-heading">
        <p class="eyebrow">Danh sách phòng</p>
        <h1>Chọn một căn phòng vừa ngắm biển, vừa nghỉ cho đúng mood Hòn Sơn</h1>
        <p>
            Nhập ngày ở và số khách, tụi mình sẽ lọc ra những phòng còn trống trong đúng khung thời gian đó.<br>
            Không nhập ngày thì trang chỉ hiển thị các phòng theo sức chứa.
        </p>
    </section>

    <section class="search-box search-box-normal">
        <h2>Tìm phòng còn trống</h2>
        <form method="get" action="rooms.php">
            <div class="form-row">
                <div class="search-left">
                    <div class="search-left-row">
                        <label for="ngay_nhan_phong">
                            Ngày nhận phòng
                            <input id="ngay_nhan_phong" type="date" name="ngay_nhan_phong" value="<?php echo h($ngayNhanPhong); ?>">
                        </label>

                        <label for="gio_nhan_phong">
                            Giờ nhận phòng
                            <input id="gio_nhan_phong" type="time" name="gio_nhan_phong" value="<?php echo h($gioNhanPhong); ?>">
                        </label>
                    </div>

                    <div class="search-left-row">
                        <label for="ngay_tra_phong">
                            Ngày trả phòng
                            <input id="ngay_tra_phong" type="date" name="ngay_tra_phong" value="<?php echo h($ngayTraPhong); ?>">
                        </label>

                        <label for="gio_tra_phong">
                            Giờ trả phòng
                            <input id="gio_tra_phong" type="time" name="gio_tra_phong" value="<?php echo h($gioTraPhong); ?>">
                        </label>
                    </div>
                </div>

                <div class="search-right">
                    <label for="so_khach">
                        Số khách
                        <input id="so_khach" class="search-right-label" type="number" name="so_khach" min="1" value="<?php echo h($soKhach); ?>">
                    </label>
                    <div class="search-right-placeholder" aria-hidden="true"></div>
                    <button type="submit" class="big-search-button">Tìm phòng còn trống</button>
                </div>
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
                    Phòng còn trống từ <?php echo h(dinh_dang_ngay_gio($ngayNhanPhong, $gioNhanPhong)); ?> đến <?php echo h(dinh_dang_ngay_gio($ngayTraPhong, $gioTraPhong)); ?>
                <?php } else { ?>
                    Kết quả tìm kiếm theo sức chứa <?php echo h($soKhach); ?> khách
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
