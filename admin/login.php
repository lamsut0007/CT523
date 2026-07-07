<?php
include "includes/auth.php";
include "../includes/db.php";
include "../includes/functions.php";

if (da_dang_nhap_admin()) {
    header("Location: rooms.php");
    exit;
}

$pageTitle = "Đăng nhập quản trị";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

    if ($conn == null) {
        $error = $dbError;
    } elseif (dang_nhap_admin($conn, $username, $password)) {
        header("Location: rooms.php");
        exit;
    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?></title>
    <link rel="icon" href="../assets/logo/oasis-logo-short.png" type="image/svg+xml">
    <link rel="stylesheet" href="includes/admin.css">
</head>
<body>
    <main class="login-page">
        <section class="login-card">
            <a class="login-brand" href="../index.php" aria-label="OASIS Homestay">
                <img class="login-brand-mark" src="../assets/logo/oasis-logo.png" alt="">
                <span class="login-brand-name">OASIS Homestay</span>
            </a>
            <h1>Đăng nhập Admin</h1>
            <p>Quản lý trạng thái phòng của OASIS Homestay.</p>

            <?php if ($error != "") { ?>
                <p class="alert"><?php echo h($error); ?></p>
            <?php } ?>

            <form method="post" action="login.php">
                <label>
                    Tên đăng nhập
                    <input type="text" name="username" required>
                </label>

                <label>
                    Mật khẩu
                    <input type="password" name="password" required>
                </label>

                <button type="submit">Đăng nhập</button>
            </form>
        </section>
    </main>
</body>
</html>
