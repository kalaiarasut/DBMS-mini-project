<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_id'])) {
    $cart_id = mysqli_real_escape_string($conn, $_POST['cart_id']);
    $user_id = $_SESSION['user_id'];

    // Delete the cart item
    $sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);

    $_SESSION['success'] = "Item removed from cart successfully!";
}

// Redirect back to cart
header("Location: cart.php");
exit();
?> 