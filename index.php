<?php

$conn = new mysqli("localhost", "root", "", "test");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT NOW() AS current_datetime");

if ($result) {
    $row = $result->fetch_assoc();
    echo "Connected successfully<br>";
    echo "Current time: " . $row['current_datetime'];
}

$conn->close();