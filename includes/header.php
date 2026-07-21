<?php
if (!function_exists('asset_url')) {
    require_once __DIR__ . '/functions.php';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" href="<?php echo h(asset_url('assets/logo/oasis-logo-short.png')); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo h(asset_url('includes/site.css')); ?>">
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="brand" href="index.php" aria-label="OASIS Homestay">
                <img class="brand-mark" src="<?php echo h(asset_url('assets/logo/oasis-logo.png')); ?>" alt="OASIS Homestay">
            </a>
            <nav class="menu">
                <a href="index.php">Trang chủ</a>
                <a href="rooms.php">Phòng</a>
                <a href="feedback.php">Đánh giá</a>
            </nav>
        </div>
    </header>
