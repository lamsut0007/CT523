<?php
session_start();

$adminUsername = "admin";
$adminPassword = "admin123";

function is_admin_logged_in()
{
    return isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true;
}

function dang_nhap_admin($conn, $username, $password)
{
    if ($conn == null) {
        return false;
    }

    $sql = "SELECT id_tai_khoan, ten_dang_nhap
            FROM tai_khoan
            WHERE ten_dang_nhap = ?
              AND mat_khau = SHA2(?, 256)
              AND vai_tro = 'admin'
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($admin) {
        $_SESSION["admin_logged_in"] = true;
        $_SESSION["admin_id"] = (int) $admin["id_tai_khoan"];
        $_SESSION["admin_username"] = $admin["ten_dang_nhap"];
        return true;
    }

    return false;
}

function require_admin_login()
{
    if (!is_admin_logged_in()) {
        header("Location: login.php");
        exit;
    }
}
