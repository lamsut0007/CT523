<?php
include "includes/auth.php";
include "../includes/functions.php";

if (is_admin_logged_in()) {
    header("Location: rooms.php");
    exit;
}

$pageTitle = "Đăng nhập quản trị";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

    if ($username == $adminUsername && $password == $adminPassword) {
        $_SESSION["admin_logged_in"] = true;
        $_SESSION["admin_username"] = $username;
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
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <main class="login-page">
        <section class="login-card">
            <h1>Đăng nhập Admin</h1>
            <p>Quản lý trạng thái phòng của Local Homestay.</p>

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
