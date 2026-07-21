<?php
if (!function_exists('asset_url')) {
    require_once __DIR__ . '/../../includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" href="<?php echo h(asset_url('../assets/logo/oasis-logo-short.png')); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo h(asset_url('includes/admin.css')); ?>">
</head>
<body>
    <header class="admin-header">
        <div class="admin-container admin-nav">
            <a class="admin-logo" href="index.php" aria-label="OASIS Homestay Admin">
                <img class="admin-logo-mark" src="<?php echo h(asset_url('../assets/logo/oasis-logo-short.png')); ?>" alt="OASIS Homestay">
                <span>OASIS Homestay Admin</span>
            </a>
            <nav>
                <a href="index.php">Trang chủ</a>
                <a href="rooms.php">Quản lý phòng</a>
                <a href="logout.php">Đăng xuất</a>
            </nav>
        </div>
    </header>
