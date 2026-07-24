<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = "localhost";
$dbPort = 3306;
$dbUser = "root";
$dbPassword = "";
$dbName = "homestay_app";

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);

if ($conn->connect_error) {
    $dbError = "Không kết nối được cơ sở dữ liệu. Vui lòng import files/db/schema.sql trước.";
    $conn = null;
} else {
    $conn->set_charset("utf8mb4");
    $dbError = "";
}
