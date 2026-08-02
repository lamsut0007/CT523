<?php
$pageTitle = "Đánh giá - OASIS Homestay";
include "includes/db.php";


$formSuccess = false;
$formError   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "gui_danh_gia") {
    $ho_ten      = trim($_POST["ho_ten"]      ?? "");
    $so_dien_thoai = trim($_POST["so_dien_thoai"] ?? "");
    $email       = trim($_POST["email"]       ?? "");
    $so_sao      = (int)($_POST["so_sao"]     ?? 0);
    $noi_dung    = trim($_POST["noi_dung"]    ?? "");

    if ($ho_ten === "") {
        $formError = "Vui lòng nhập họ tên.";
    } elseif ($so_dien_thoai !== "" && !preg_match('/^0[0-9]{9}$/', $so_dien_thoai)) {
        $formError = "Số điện thoại không hợp lệ (10 chữ số, bắt đầu bằng 0).";
    } elseif ($so_sao < 1 || $so_sao > 5) {
        $formError = "Vui lòng chọn số sao đánh giá (1–5).";
    } elseif ($noi_dung === "") {
        $formError = "Vui lòng nhập nội dung đánh giá.";
    } elseif ($conn === null) {
        $formError = "Không kết nối được cơ sở dữ liệu.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO danh_gia (ho_ten, so_dien_thoai, email, so_sao, noi_dung)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssis", $ho_ten, $so_dien_thoai, $email, $so_sao, $noi_dung);
        if ($stmt->execute()) {
            $formSuccess = true;
        } else {
            $formError = "Có lỗi xảy ra, vui lòng thử lại.";
        }
        $stmt->close();
    }
}


$reviews    = [];
$avgRating  = 0;
$totalCount = 0;
$starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

if ($conn !== null) {
    $res = $conn->query(
        "SELECT * FROM danh_gia ORDER BY ngay_tao DESC"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $reviews[] = $row;
            $starCounts[(int)$row["so_sao"]]++;
        }
        $totalCount = count($reviews);
        if ($totalCount > 0) {
            $sum = 0;
            foreach ($reviews as $r) $sum += (int)$r["so_sao"];
            $avgRating = round($sum / $totalCount, 1);
        }
    }
}


function renderStars(int $count, bool $small = false): string {
    $cls = $small ? "star-sm" : "star-md";
    $out = '<span class="stars-display ' . $cls . '">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="star ' . ($i <= $count ? "star-on" : "star-off") . '">★</span>';
    }
    $out .= '</span>';
    return $out;
}


$avatarPalette = [
    "#1c6b7a","#0f4e5d","#b98b2d","#5d7177","#2e7d6e",
    "#7a5015","#4a6fa5","#6b5b95","#2d6a4f","#c77542"
];
function avatarColor(string $name, array $palette): string {
    $hash = 0;
    for ($i = 0; $i < strlen($name); $i++) $hash += ord($name[$i]);
    return $palette[$hash % count($palette)];
}

include "includes/header.php";
?>

<!-- --- Hero nhỏ -------------------------------------------------------- -->
<section class="rv-hero">
    <div class="rv-hero-inner">
        <p class="eyebrowlight">OASIS Homestay</p>
        <h1>Cảm nhận của khách lưu trú</h1>
        <p class="rv-hero-sub">
             Mỗi đánh giá là một câu chuyện nhỏ giúp chúng tôi cải thiện từng ngày.
        </p>
    </div>
    <div class="rv-wave" aria-hidden="true"></div>
</section>

<main>

    <!-- --- Tổng quan đánh giá ------------------------------------------- -->
    <?php if ($totalCount > 0): ?>
    <section class="rv-summary-band">
        <div class="rv-summary-left">
            <div class="rv-big-score"><?= number_format($avgRating, 1) ?></div>
            <div>
                <?= renderStars((int)round($avgRating)) ?>
                <p class="rv-total-label"><?= $totalCount ?> lượt đánh giá</p>
            </div>
        </div>
        <div class="rv-bars">
            <?php for ($s = 5; $s >= 1; $s--): 
                $cnt  = $starCounts[$s];
                $pct  = $totalCount > 0 ? round(($cnt / $totalCount) * 100) : 0;
            ?>
            <div class="rv-bar-row">
                <span class="rv-bar-label"><?= $s ?>★</span>
                <div class="rv-bar-track">
                    <div class="rv-bar-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="rv-bar-count"><?= $cnt ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="rv-layout">

        <!-- --- Cột trái: form gửi đánh giá ---------------------------- -->
        <aside class="rv-form-col">
            <div class="rv-form-card">
                <h2 class="rv-form-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                    Chia sẻ trải nghiệm của bạn
                </h2>

                <?php if ($formSuccess): ?>
                <div class="rv-alert rv-alert-success" id="success-msg">
                    <span class="rv-alert-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </span>
                    <div>
                        <strong>Cảm ơn bạn!</strong><br>
                        Đánh giá của bạn đã được ghi nhận.
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($formError): ?>
                <div class="rv-alert rv-alert-error">
                    <span class="rv-alert-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </span>
                    <div><?= htmlspecialchars($formError) ?></div>
                </div>
                <?php endif; ?>

                <form method="post" action="feedback.php#success-msg" class="rv-form" id="review-form">
                    <input type="hidden" name="action" value="gui_danh_gia">

                    <!-- Chọn sao -->
                    <div class="rv-star-field">
                        <label class="rv-field-label">Đánh giá của bạn <span class="req">*</span></label>
                        <div class="rv-star-picker" id="star-picker">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="so_sao" value="<?= $i ?>"
                                <?= (!$formSuccess && isset($_POST["so_sao"]) && (int)$_POST["so_sao"] === $i) ? "checked" : "" ?>>
                            <label for="star<?= $i ?>" title="<?= $i ?> sao">★</label>
                            <?php endfor; ?>
                        </div>
                        <span class="rv-star-hint" id="star-hint">Chọn số sao bên trên</span>
                    </div>

                    <div class="rv-field-group">
                        <label class="rv-field-label" for="ho_ten">Họ tên <span class="req">*</span></label>
                        <input id="ho_ten" type="text" name="ho_ten" placeholder="Nguyễn Văn A"
                            value="<?= !$formSuccess ? htmlspecialchars($_POST["ho_ten"] ?? "") : "" ?>"
                            autocomplete="name" required>
                    </div>

                    <div class="rv-field-row">
                        <div class="rv-field-group">
                            <label class="rv-field-label" for="so_dien_thoai">Số điện thoại</label>
                            <input id="so_dien_thoai" type="tel" name="so_dien_thoai"
                                value="<?= !$formSuccess ? htmlspecialchars($_POST["so_dien_thoai"] ?? "") : "" ?>"
                                autocomplete="tel">
                        </div>
                        <div class="rv-field-group">
                            <label class="rv-field-label" for="email">Email</label>
                            <input id="email" type="email" name="email" placeholder="email@example.com"
                                value="<?= !$formSuccess ? htmlspecialchars($_POST["email"] ?? "") : "" ?>"
                                autocomplete="email">
                        </div>
                    </div>

                    <div class="rv-field-group">
                        <label class="rv-field-label" for="noi_dung">Nội dung đánh giá <span class="req">*</span></label>
                        <textarea id="noi_dung" name="noi_dung" class="rv-textarea"
                            placeholder="Chia sẻ cảm nhận của bạn về phòng, dịch vụ, không gian, trải nghiệm..." rows="4"
                            required><?php if (!$formSuccess && isset($_POST['noi_dung'])) { echo htmlspecialchars($_POST['noi_dung']); } ?></textarea>
                    </div>

                    <button type="submit" class="rv-submit-btn" id="submit-btn">
                        <span>Gửi đánh giá</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22 11 13 2 9l20-7z"/></svg>
                    </button>
                </form>
            </div>
        </aside>

        <!-- --- Cột phải: danh sách review ----------------------------- -->
        <section class="rv-list-col">
            <div class="rv-list-header">
                <h2 class="rv-list-title">
                    Tất cả đánh giá
                    <?php if ($totalCount > 0): ?>
                    <span class="rv-count-badge"><?= $totalCount ?></span>
                    <?php endif; ?>
                </h2>
            </div>

            <?php if (empty($reviews)): ?>
            <div class="rv-empty">
                <div class="rv-empty-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.35"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3>Chưa có đánh giá nào</h3>
                <p>Hãy là người đầu tiên chia sẻ cảm nhận về OASIS!</p>
            </div>
            <?php else: ?>
            <div class="rv-cards" id="rv-cards">
                <?php foreach ($reviews as $idx => $r):
                    $initial = mb_strtoupper(mb_substr(trim($r["ho_ten"]), 0, 1, "UTF-8"), "UTF-8");
                    $bgColor = avatarColor($r["ho_ten"], $avatarPalette);
                    $dateStr = date("d/m/Y", strtotime($r["ngay_tao"]));
                    $hidden  = $idx >= 6; 
                ?>
                <div class="rv-card <?= $hidden ? "rv-card-hidden" : "" ?>" 
                     data-index="<?= $idx ?>">
                    <div class="rv-card-header">
                        <div class="rv-avatar" style="background:<?= $bgColor ?>">
                            <?= htmlspecialchars($initial) ?>
                        </div>
                        <div class="rv-card-meta">
                            <strong class="rv-card-name"><?= htmlspecialchars($r["ho_ten"]) ?></strong>
                            <span class="rv-card-date"><?= $dateStr ?></span>
                        </div>
                        <div class="rv-card-stars">
                            <?= renderStars((int)$r["so_sao"], true) ?>
                        </div>
                    </div>
                    <p class="rv-card-body"><?= nl2br(htmlspecialchars($r["noi_dung"])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalCount > 6): ?>
            <div class="rv-load-more-wrap">
                <button class="rv-load-more-btn" id="load-more-btn"
                        onclick="loadMoreReviews()">
                    Xem thêm <?= $totalCount - 6 ?> đánh giá
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>

</main>

<script>
/* --- Star picker hint -------------------------------------------------- */
const hints = { 1:"Rất tệ", 2:"Tệ", 3:"Bình thường", 4:"Tốt", 5:"Xuất sắc!" };
document.querySelectorAll('#star-picker input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const hint = document.getElementById('star-hint');
        if (hint) hint.textContent = hints[radio.value] || '';
    });
});

/* --- Load more cards -------------------------------------------------- */
function loadMoreReviews() {
    const hidden = document.querySelectorAll('.rv-card-hidden');
    hidden.forEach((card, i) => {
        setTimeout(() => {
            card.classList.remove('rv-card-hidden');
            card.classList.add('rv-card-reveal');
        }, i * 60);
    });
    const btn = document.getElementById('load-more-btn');
    if (btn) btn.closest('.rv-load-more-wrap').remove();
}

/* --- Animate cards on load -------------------------------------------- */
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('rv-card-in');
            observer.unobserve(e.target);
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.rv-card:not(.rv-card-hidden)').forEach(card => {
    observer.observe(card);
});
</script>

<?php include "includes/footer.php"; ?>
