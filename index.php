<?php
$pageTitle = "Local Homestay";
include "includes/header.php";
?>

<section class="hero">
    <div class="hero-content">
        <p class="eyebrow">Homestay Cần Thơ</p>
        <h1>Một chỗ nghỉ yên tĩnh gần trung tâm Ninh Kiều</h1>
        <p>
            Local Homestay mang lại cảm giác gần gũi như ở nhà, có không gian thoáng,
            phòng sạch sẽ và lịch đặt rõ ràng cho khách đi du lịch, công tác hoặc nghỉ cuối tuần.
        </p>
        <a class="button" href="rooms.php">Xem phòng trống</a>
    </div>
</section>

<main>
    <section class="search-box">
        <h2>Tìm phòng nhanh</h2>
        <form method="get" action="rooms.php">
            <div class="form-row">
                <label>
                    Ngày nhận phòng
                    <input type="date" name="check_in">
                </label>

                <label>
                    Ngày trả phòng
                    <input type="date" name="check_out">
                </label>

                <label>
                    Số khách
                    <input type="number" name="guests" min="1" value="2">
                </label>

                <button type="submit">Kiểm tra phòng</button>
            </div>
        </form>
    </section>

    <section class="section home-intro">
        <div>
            <p class="eyebrow">Không gian nghỉ ngơi</p>
            <h2>Ấm cúng, riêng tư và đủ tiện nghi cho một chuyến đi thoải mái</h2>
            <p>
                Homestay được thiết kế theo phong cách giản dị, nhiều ánh sáng tự nhiên,
                phù hợp cho khách muốn nghỉ lại gần trung tâm nhưng vẫn cần một nơi yên tĩnh.
            </p>
            <div class="stats">
                <div>
                    <strong>5+</strong>
                    <span>phòng lưu trú</span>
                </div>
                <div>
                    <strong>2-5</strong>
                    <span>khách mỗi phòng</span>
                </div>
                <div>
                    <strong>24h</strong>
                    <span>hỗ trợ đặt lịch</span>
                </div>
            </div>
        </div>

        <div class="image-grid">
            <img class="image-large" src="assets/room/1.jpg" alt="Không gian phòng homestay">
            <img src="assets/room/3.jpg" alt="Phòng nghỉ sáng sủa">
            <img src="assets/room/5.jpg" alt="Phòng gia đình tại homestay">
        </div>
    </section>

    <section class="section">
        <div class="section-title">
            <p class="eyebrow">Tiện ích</p>
            <h2>Những gì khách thường cần đều được chuẩn bị sẵn</h2>
        </div>

        <div class="amenity-grid">
            <div class="amenity-card">
                <h3>Vị trí thuận tiện</h3>
                <p>Dễ di chuyển đến bến Ninh Kiều, chợ đêm, quán ăn và các điểm tham quan trong thành phố.</p>
            </div>
            <div class="amenity-card">
                <h3>Phòng rõ giá</h3>
                <p>Mỗi loại phòng có giá theo ngày và theo giờ, giúp khách chủ động chọn theo lịch trình.</p>
            </div>
            <div class="amenity-card">
                <h3>Lịch đặt minh bạch</h3>
                <p>Khi khách nhập ngày lưu trú, hệ thống chỉ hiển thị những phòng còn trống và đủ sức chứa.</p>
            </div>
        </div>
    </section>

    <section class="section location-band">
        <div>
            <p class="eyebrow">Khu vực Ninh Kiều</p>
            <h2>Gần trung tâm nhưng vẫn giữ được sự riêng tư</h2>
            <p>
                Phù hợp cho khách muốn khám phá Cần Thơ trong ngày, nghỉ lại qua đêm,
                hoặc cần một nơi gọn gàng để làm việc và thư giãn.
            </p>
        </div>
        <a class="button" href="rooms.php">Chọn phòng phù hợp</a>
    </section>
</main>

<?php include "includes/footer.php"; ?>
