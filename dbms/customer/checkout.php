<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../index.php");
    exit();
}

// Fetch cart items
$sql = "SELECT c.*, j.name, j.price, j.image_path FROM cart c JOIN jewellery j ON c.jewellery_id = j.id WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$total = 0;
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

// Handle payment simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($items)) {
    // Insert order
    $order_sql = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')";
    $order_stmt = mysqli_prepare($conn, $order_sql);
    mysqli_stmt_bind_param($order_stmt, "id", $_SESSION['user_id'], $total);
    if (mysqli_stmt_execute($order_stmt)) {
        $order_id = mysqli_insert_id($conn);
        // Insert order items
        $item_sql = "INSERT INTO order_items (order_id, jewellery_id, quantity, price) VALUES (?, ?, ?, ?)";
        $item_stmt = mysqli_prepare($conn, $item_sql);
        foreach ($items as $item) {
            mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $item['jewellery_id'], $item['quantity'], $item['price']);
            mysqli_stmt_execute($item_stmt);
        }
        // Clear cart
        $clear_sql = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = mysqli_prepare($conn, $clear_sql);
        mysqli_stmt_bind_param($clear_stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($clear_stmt);
        // Redirect to success page
        $_SESSION['success'] = "Payment successful! Your order has been placed.";
        header("Location: checkout.php?success=1");
        exit();
    } else {
        $error = "Failed to place order. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Jewellery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Checkout</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Payment successful! Your order has been placed.</div>
        <a href="dashboard.php" class="btn btn-primary">Back to Shop</a>
    <?php elseif (empty($items)): ?>
        <div class="alert alert-warning">Your cart is empty.</div>
        <a href="dashboard.php" class="btn btn-primary">Back to Shop</a>
    <?php else: ?>
        <table class="table mt-4">
            <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <h4>Total: ₹<?php echo number_format($total, 2); ?></h4>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" id="paymentForm">
            <button type="submit" class="btn btn-success" id="payBtn">Pay Now</button>
        </form>
        <!-- Payment Animation -->
        <div id="paymentAnimation" style="display:none; text-align:center; margin-top:30px;">
            <div class="spinner-border text-success" style="width: 4rem; height: 4rem;" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <div style="margin-top:15px; font-size:1.2rem; color:#28a745;">Processing Payment...</div>
        </div>
        <script>
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            document.getElementById('payBtn').style.display = 'none';
            document.getElementById('paymentAnimation').style.display = 'block';
        });
        </script>
    <?php endif; ?>
</div>
</body>
</html> 