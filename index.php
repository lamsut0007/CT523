<?php
$pageTitle = "OASIS Homestay";
include "includes/header.php";
?>

<section class="hero">
    <div class="hero-content">
        <p class="eyebrowlight">OASIS Homestay</p>
        <h1>Nép mình bên biển xanh, mang trọn vẻ đẹp nghỉ dưỡng miền đảo</h1>
        <p>
            OASIS Homestay là chốn nghỉ yên bình với không gian thoáng đãng và riêng tư, 
            nơi bạn có thể thả lỏng giữa gió biển và nhịp sống chậm rãi.
        </p>
        <a class="button" href="rooms.php">Xem phòng trống</a>
    </div>
</section>

<main>
    <section class="search-box">
        <h2>Tìm phòng nhanh</h2>
        <form method="get" action="rooms.php">
            <div class="form-row">
                <div class="search-left">
                    <div class="search-left-row">
                        <label for="home_ngay_nhan_phong">
                            Ngày nhận phòng
                            <input id="home_ngay_nhan_phong" type="date" name="ngay_nhan_phong" min="<?php echo date('Y-m-d'); ?>">
                        </label>

                        <label for="home_gio_nhan_phong">
                            Giờ nhận phòng
                            <input id="home_gio_nhan_phong" type="time" name="gio_nhan_phong" value="14:00">
                        </label>
                    </div>

                    <div class="search-left-row">
                        <label for="home_ngay_tra_phong">
                            Ngày trả phòng
                            <input id="home_ngay_tra_phong" type="date" name="ngay_tra_phong" min="<?php echo date('Y-m-d'); ?>">
                        </label>

                        <label for="home_gio_tra_phong">
                            Giờ trả phòng
                            <input id="home_gio_tra_phong" type="time" name="gio_tra_phong" value="12:00">
                        </label>
                    </div>
                </div>

                <div class="search-right">
                    <label for="home_so_khach">
                        Số khách
                        <input id="home_so_khach" class="search-right-label" type="number" name="so_khach" min="1" value="2">
                    </label>
                    <div class="search-right-placeholder" aria-hidden="true"></div>
                    <button type="submit" class="big-search-button">Tìm phòng</button>
                </div>
            </div>
        </form>
    </section>

    <section class="section home-intro">
        <div>
            <p class="eyebrow">Không gian nghỉ dưỡng</p>
            <h2>Thư giãn giữa thiên nhiên, tận hưởng trọn vẹn vẻ đẹp Hòn Sơn</h2>
            <p>
                Được lấy cảm hứng từ biển đảo, không gian ngập tràn ánh sáng tự nhiên, 
                thoáng đãng và đầy đủ tiện nghi. Mỗi góc nhỏ đều được chăm chút để mang đến 
                cho bạn sự thoải mái như đang ở chính ngôi nhà của mình.
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
            <img class="image-large" src="<?php echo h(asset_url('assets/room/1.jpg')); ?>" alt="Không gian phòng homestay">
            <img src="<?php echo h(asset_url('assets/room/2.jpg')); ?>" alt="Phòng nghỉ sáng sủa">
            <img src="<?php echo h(asset_url('assets/room/5.jpg')); ?>" alt="Phòng gia đình tại homestay">
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
                <p>Khi nhập ngày lưu trú, hệ thống chỉ hiển thị những phòng đang hoạt động và đủ sức chứa.</p>
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
        <a class="location-link" href="rooms.php">Chọn phòng phù hợp</a>
    </section>
</main>

<?php include "includes/footer.php"; ?>
