<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['jewellery_id'])) {
    $jewellery_id = mysqli_real_escape_string($conn, $_POST['jewellery_id']);
    $user_id = $_SESSION['user_id'];

    // Check if item already exists in cart
    $check_sql = "SELECT * FROM cart WHERE user_id = ? AND jewellery_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $jewellery_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        // Update quantity if item exists
        $update_sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND jewellery_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $user_id, $jewellery_id);
        mysqli_stmt_execute($update_stmt);
    } else {
        // Add new item to cart
        $insert_sql = "INSERT INTO cart (user_id, jewellery_id, quantity) VALUES (?, ?, 1)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $jewellery_id);
        mysqli_stmt_execute($insert_stmt);
    }

    $_SESSION['success'] = "Item added to cart successfully!";
}

// Redirect back to the previous page
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?> 