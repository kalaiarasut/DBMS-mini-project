<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cart_id = mysqli_real_escape_string($conn, $_POST['cart_id']);
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    // Verify that the cart item belongs to the user
    $check_sql = "SELECT c.*, j.stock_quantity 
                  FROM cart c 
                  JOIN jewellery j ON c.jewellery_id = j.id 
                  WHERE c.id = ? AND c.user_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Ensure quantity doesn't exceed stock
        $quantity = min($quantity, $row['stock_quantity']);
        
        if ($quantity > 0) {
            // Update quantity
            $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "iii", $quantity, $cart_id, $user_id);
            mysqli_stmt_execute($update_stmt);
        } else {
            // Remove item if quantity is 0
            $delete_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "ii", $cart_id, $user_id);
            mysqli_stmt_execute($delete_stmt);
        }
    }
}

// Redirect back to cart
header("Location: cart.php");
exit();
?> 