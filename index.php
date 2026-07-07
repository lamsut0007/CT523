<?php
$pageTitle = "OASIS Homestay";
include "includes/header.php";
?>

<section class="hero">
    <div class="hero-content">
        <p class="eyebrowlight">OASIS Homestay</p>
        <h1>Một chỗ nghỉ nép mình bên biển, gió mát và rất đúng chất đảo</h1>
        <p>
            OASIS Homestay mang tinh thần nghỉ dưỡng gần biển, nhẹ nhàng và thoáng đãng.
            Không gian sạch sẽ, riêng tư, phù hợp cho khách muốn rời phố một nhịp để nghe sóng và thở chậm lại.
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
                    <input type="date" name="ngay_nhan_phong">
                </label>

                <label>
                    Ngày trả phòng
                    <input type="date" name="ngay_tra_phong">
                </label>

                <label>
                    Số khách
                    <input type="number" name="so_khach" min="1" value="2">
                </label>

                <button type="submit">Kiểm tra phòng</button>
            </div>
        </form>
    </section>

    <section class="section home-intro">
        <div>
            <p class="eyebrow">Không gian nghỉ dưỡng</p>
            <h2>Ấm cúng, thoáng gió và đủ tiện nghi cho một chuyến đi ra đảo thật nhẹ đầu</h2>
            <p>
                Homestay được thiết kế theo cảm hứng biển đảo, nhiều ánh sáng tự nhiên và
                gam màu dịu mắt để khách vừa nghỉ ngơi vừa cảm nhận được chất Hòn Sơn rất riêng.
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
            <img src="assets/room/2.jpg" alt="Phòng nghỉ sáng sủa">
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
                <h3>Gần biển, dễ thở</h3>
                <p>Không gian hợp cho khách thích đi bộ ra bờ biển, ngắm bình minh và trở về nghỉ ngơi thật nhanh.</p>
            </div>
            <div class="amenity-card">
                <h3>Phòng rõ giá</h3>
                <p>Mỗi loại phòng có giá theo ngày và theo giờ, giúp khách chủ động chọn theo lịch trình.</p>
            </div>
            <div class="amenity-card">
                <h3>Lịch đặt minh bạch</h3>
                <p>Khi nhập ngày lưu trú, hệ thống chỉ hiển thị những phòng còn trống và đủ sức chứa.</p>
            </div>
        </div>
    </section>

    <section class="section location-band">
        <div>
            <p class="eyebrow">Khu vực Hòn Sơn</p>
            <h2>Gần biển nhưng vẫn giữ được sự riêng tư và yên tĩnh</h2>
            <p>
                Phù hợp cho khách muốn nghỉ qua đêm trên đảo, đi chơi, ăn hải sản, hoặc cần một nơi gọn gàng
                để dừng chân trước khi tiếp tục hành trình.
            </p>
        </div>
        <a class="button" href="rooms.php">Chọn phòng phù hợp</a>
    </section>
</main>

<?php include "includes/footer.php"; ?>
