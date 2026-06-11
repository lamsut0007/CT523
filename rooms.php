<?php
$pageTitle = "Danh sách phòng - Local Homestay";
include "includes/db.php";
include "includes/functions.php";

$checkIn = isset($_GET["check_in"]) ? trim($_GET["check_in"]) : "";
$checkOut = isset($_GET["check_out"]) ? trim($_GET["check_out"]) : "";
$guests = isset($_GET["guests"]) ? (int) $_GET["guests"] : 2;
$dateError = "";
$roomQueryError = "";
$hasSearch = isset($_GET["check_in"]) || isset($_GET["check_out"]) || isset($_GET["guests"]);

if ($guests < 1) {
    $guests = 1;
}

if (($checkIn == "" && $checkOut != "") || ($checkIn != "" && $checkOut == "")) {
    $dateError = "Vui lòng nhập đầy đủ ngày nhận phòng và ngày trả phòng.";
    $checkIn = "";
    $checkOut = "";
} elseif (!is_valid_search_date($checkIn, $checkOut)) {
    $dateError = "Ngày trả phòng phải sau ngày nhận phòng.";
    $checkIn = "";
    $checkOut = "";
}

$rooms = get_available_rooms($conn, $checkIn, $checkOut, $guests);
include "includes/header.php";
?>

<main>
    <section class="page-heading">
        <p class="eyebrow">Danh sách phòng</p>
        <h1>Chọn phòng phù hợp với lịch lưu trú</h1>
        <p>
            Nhập ngày nhận, ngày trả và số khách để xem những phòng còn trống.
            Nếu không nhập ngày, trang sẽ hiển thị các phòng đang sẵn sàng nhận khách.
        </p>
    </section>

    <section class="search-box search-box-normal">
        <h2>Kiểm tra phòng còn trống</h2>
        <form method="get" action="rooms.php">
            <div class="form-row">
                <label>
                    Ngày nhận phòng
                    <input type="date" name="check_in" value="<?php echo h($checkIn); ?>">
                </label>

                <label>
                    Ngày trả phòng
                    <input type="date" name="check_out" value="<?php echo h($checkOut); ?>">
                </label>

                <label>
                    Số khách
                    <input type="number" name="guests" min="1" value="<?php echo h($guests); ?>">
                </label>

                <button type="submit">Tìm phòng</button>
            </div>
        </form>

        <?php if ($dbError != "") { ?>
            <p class="alert"><?php echo h($dbError); ?></p>
        <?php } ?>

        <?php if ($dateError != "") { ?>
            <p class="alert"><?php echo h($dateError); ?></p>
        <?php } ?>

        <?php if ($roomQueryError != "") { ?>
            <p class="alert"><?php echo h($roomQueryError); ?></p>
        <?php } ?>
    </section>

    <section class="section" id="rooms">
        <div class="section-title">
            <p class="eyebrow">Phòng lưu trú</p>
            <h2>
                <?php if ($checkIn != "" && $checkOut != "") { ?>
                    Phòng còn trống từ <?php echo h(format_date_vi($checkIn)); ?> đến <?php echo h(format_date_vi($checkOut)); ?>
                <?php } else { ?>
                    Phòng đang nhận khách
                <?php } ?>
            </h2>
            <?php if ($hasSearch && $dateError == "" && count($rooms) > 0) { ?>
                <p class="section-note">Tìm thấy <?php echo count($rooms); ?> phòng phù hợp cho <?php echo h($guests); ?> khách.</p>
            <?php } ?>
        </div>

        <div class="room-list">
            <?php if (count($rooms) > 0) { ?>
                <?php foreach ($rooms as $room) { ?>
                    <article class="room-card">
                        <div class="room-image">
                            <img src="<?php echo h($room["image_path"]); ?>" alt="<?php echo h($room["room_name"]); ?>">
                        </div>
                        <div class="room-info">
                            <p class="badge"><?php echo h($room["room_code"]); ?></p>
                            <h3><?php echo h($room["room_name"]); ?></h3>
                            <p><?php echo h($room["description"]); ?></p>
                            <p class="room-meta">
                                Loại phòng: <?php echo h($room["type_name"]); ?> |
                                Sức chứa: <?php echo h($room["capacity"]); ?> khách
                            </p>
                            <p class="price"><?php echo money_vnd($room["price_per_day"]); ?> / đêm</p>
                            <p class="muted"><?php echo money_vnd($room["price_per_hour"]); ?> / giờ</p>
                        </div>
                    </article>
                <?php } ?>
            <?php } else { ?>
                <p class="empty">Hiện chưa có phòng phù hợp. Vui lòng thử ngày khác hoặc giảm số khách.</p>
            <?php } ?>
        </div>
    </section>
</main>

<?php include "includes/footer.php"; ?>
