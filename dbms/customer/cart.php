<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../index.php");
    exit();
}

// Fetch cart items
$sql = "SELECT c.*, j.name, j.price, j.image_path, j.stock_quantity 
        FROM cart c 
        JOIN jewellery j ON c.jewellery_id = j.id 
        WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Calculate total
$total = 0;
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Jewellery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --light-bg: #f8f9fa;
            --card-shadow: 0 0 15px rgba(0,0,0,0.1);
            --hover-transform: translateY(-5px);
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--primary-color);
            color: white;
            position: fixed;
            width: inherit;
            max-width: inherit;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
            border-radius: 5px;
            margin: 5px 0;
        }

        .sidebar a:hover {
            background: var(--secondary-color);
            transform: translateX(5px);
        }

        .sidebar .active {
            background: var(--accent-color);
        }

        .main-content {
            padding: 30px;
            margin-left: 16.666667%;
        }

        .cart-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s;
        }

        .cart-item:hover {
            transform: var(--hover-transform);
        }

        .cart-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .quantity-input {
            width: 80px;
            text-align: center;
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 5px;
        }

        .quantity-input:focus {
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .remove-btn {
            color: var(--accent-color);
            background: none;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .remove-btn:hover {
            background: #fde8e8;
            transform: scale(1.1);
        }

        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .total-row {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .checkout-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            font-size: 1.1rem;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            background: #c0392b;
            transform: var(--hover-transform);
        }

        .empty-cart {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-cart h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .continue-shopping {
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 10px;
            display: inline-block;
            transition: all 0.3s;
        }

        .continue-shopping:hover {
            background: var(--secondary-color);
            color: white;
            transform: var(--hover-transform);
        }

        .alert {
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }

        .update-quantity-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .update-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .update-btn:hover {
            background: var(--secondary-color);
            transform: scale(1.05);
        }

        .item-price {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 1.1rem;
        }

        .item-total {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.2rem;
        }

        @keyframes tick {
            to { stroke-dashoffset: 0; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="mb-4">Customer Panel</h3>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="cart.php" class="nav-link active">
                            <i class="fas fa-shopping-cart me-2"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="orders.php" class="nav-link">
                            <i class="fas fa-box me-2"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="../logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Shopping Cart</h2>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                    </a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Cart Items -->
                    <div class="col-lg-8">
                        <?php if (empty($items)): ?>
                            <div class="empty-cart">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>Your cart is empty</h3>
                                <p>Looks like you haven't added any items to your cart yet.</p>
                                <a href="dashboard.php" class="continue-shopping">
                                    <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <div class="cart-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <?php 
                                            $image_path = !empty($item['image_path']) ? '../uploads/' . $item['image_path'] : '../assets/images/default-jewellery.jpg';
                                            if (!file_exists($image_path)) {
                                                error_log("Image not found in cart: " . $image_path);
                                                $image_path = '../assets/images/default-jewellery.jpg';
                                            }
                                            ?>
                                            <img src="<?php echo $image_path; ?>" 
                                                 alt="<?php echo $item['name']; ?>" 
                                                 class="cart-image"
                                                 onerror="this.src='../assets/images/default-jewellery.jpg'">
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="mb-2"><?php echo $item['name']; ?></h5>
                                            <p class="item-price mb-0">₹<?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <form action="update_cart.php" method="POST" class="update-quantity-form">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                       min="1" max="<?php echo $item['stock_quantity']; ?>" 
                                                       class="quantity-input">
                                                <button type="submit" class="update-btn">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <div class="col-md-2">
                                            <p class="item-total mb-0">
                                                ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <form action="remove_from_cart.php" method="POST" class="d-inline">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="remove-btn" 
                                                        onclick="return confirm('Are you sure you want to remove this item?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Cart Summary -->
                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h4 class="mb-4">Order Summary</h4>
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₹<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <div class="summary-row total-row">
                                <span>Total</span>
                                <span>₹<?php echo number_format($total, 2); ?></span>
                            </div>
                            <?php if (!empty($items)): ?>
                                <form action="checkout.php" method="POST" id="proceedToCheckoutForm">
                                    <button type="submit" class="checkout-btn" id="proceedBtn">
                                        <i class="fas fa-lock me-2"></i> Proceed to Checkout
                                    </button>
                                </form>
                                <!-- Custom Animation -->
                                <div id="checkoutAnimation" style="display:none; text-align:center; margin-top:30px;">
                                    <div id="spinnerAnim">
                                        <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                                            <span class="visually-hidden">Redirecting...</span>
                                        </div>
                                        <div style="margin-top:15px; font-size:1.2rem; color:#2c3e50;">Redirecting to Payment...</div>
                                    </div>
                                    <div id="tickAnim" style="display:none; flex-direction:column; align-items:center;">
                                        <svg width="80" height="80" viewBox="0 0 80 80">
                                            <circle cx="40" cy="40" r="38" stroke="#28a745" stroke-width="4" fill="none"/>
                                            <polyline points="24,44 36,56 56,32" style="fill:none;stroke:#28a745;stroke-width:6;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:50;stroke-dashoffset:50;" id="tickMark"/>
                                        </svg>
                                        <div style="margin-top:15px; font-size:1.3rem; color:#28a745;">Payment Initiated</div>
                                    </div>
                                </div>
                                <script>
                                document.getElementById('proceedToCheckoutForm').addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    document.getElementById('proceedBtn').style.display = 'none';
                                    document.getElementById('checkoutAnimation').style.display = 'block';
                                    // Show spinner for 1s, then tick for 0.7s, then submit
                                    setTimeout(() => {
                                        document.getElementById('spinnerAnim').style.display = 'none';
                                        document.getElementById('tickAnim').style.display = 'flex';
                                        // Animate the tick
                                        var tick = document.getElementById('tickMark');
                                        tick.style.strokeDasharray = 50;
                                        tick.style.strokeDashoffset = 50;
                                        tick.style.animation = 'tick 0.5s linear forwards';
                                        setTimeout(() => {
                                            e.target.submit();
                                        }, 700);
                                    }, 1000);
                                });
                                </script>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 