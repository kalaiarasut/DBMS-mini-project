<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../index.php");
    exit();
}

// Build the query based on filters and search
$where_conditions = [];
$params = [];
$types = "";

// Search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . mysqli_real_escape_string($conn, $_GET['search']) . "%";
    $where_conditions[] = "(j.name LIKE ? OR j.description LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// Category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "j.category_id = ?";
    $params[] = $_GET['category'];
    $types .= "i";
}

// Price range filter
if (isset($_GET['price_range']) && !empty($_GET['price_range'])) {
    $price_range = explode('-', $_GET['price_range']);
    if (count($price_range) == 2) {
        $where_conditions[] = "j.price BETWEEN ? AND ?";
        $params[] = $price_range[0];
        $params[] = $price_range[1];
        $types .= "dd";
    } elseif ($_GET['price_range'] == '100000+') {
        $where_conditions[] = "j.price > ?";
        $params[] = 100000;
        $types .= "d";
    }
}

// Material filter
if (isset($_GET['material']) && !empty($_GET['material'])) {
    $where_conditions[] = "j.material = ?";
    $params[] = $_GET['material'];
    $types .= "s";
}

// Base query
$sql = "SELECT j.*, c.name as category_name 
        FROM jewellery j 
        LEFT JOIN categories c ON j.category_id = c.id";

// Add where conditions if any
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY j.created_at DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch categories for filter
$cat_sql = "SELECT * FROM categories";
$cat_result = mysqli_query($conn, $cat_sql);

// Get cart count
$cart_sql = "SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?";
$cart_stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($cart_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($cart_stmt);
$cart_result = mysqli_fetch_assoc(mysqli_stmt_get_result($cart_stmt));
$cart_count = $cart_result['cart_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Jewellery Shop</title>
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

        .card {
            box-shadow: var(--card-shadow);
            border: none;
            transition: transform 0.3s;
            border-radius: 15px;
            overflow: hidden;
        }

        .card:hover {
            transform: var(--hover-transform);
        }

        .jewellery-card {
            height: 100%;
        }

        .jewellery-image {
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .jewellery-card:hover .jewellery-image {
            transform: scale(1.05);
        }

        .card-body {
            padding: 1.5rem;
        }

        .price {
            font-size: 1.25rem;
            color: var(--accent-color);
            font-weight: bold;
        }

        .category-badge {
            background: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .material-badge {
            background: var(--secondary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box .form-control {
            padding: 15px 50px 15px 20px;
            border-radius: 30px;
            border: 2px solid #eee;
            font-size: 1rem;
        }

        .search-box .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }

        .search-box .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .filter-section select {
            border-radius: 10px;
            padding: 10px;
            border: 2px solid #eee;
        }

        .filter-section select:focus {
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: var(--hover-transform);
        }

        .cart-badge {
            position: relative;
            top: -8px;
            right: -5px;
            padding: 3px 6px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            font-size: 0.7rem;
        }

        .welcome-section {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .welcome-section h2 {
            margin: 0;
            font-size: 2rem;
        }

        .welcome-section p {
            margin: 10px 0 0;
            opacity: 0.9;
        }

        .alert {
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .stock-badge.in-stock {
            background: #2ecc71;
            color: white;
        }

        .stock-badge.out-of-stock {
            background: #e74c3c;
            color: white;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: var(--accent-color);
            color: white;
            border: none;
            transition: all 0.3s;
        }

        .add-to-cart-btn:hover {
            background: #c0392b;
            transform: var(--hover-transform);
        }

        .add-to-cart-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="mb-4">Meera Jewellery</h3>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="dashboard.php" class="nav-link active">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="cart.php" class="nav-link">
                            <i class="fas fa-shopping-cart me-2"></i> Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
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
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <h2>Welcome, <?php echo $_SESSION['name']; ?>!</h2>
                    <p>Explore our beautiful collection of jewellery</p>
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

                <!-- Search and Filters -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search jewellery..." 
                                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php
                                mysqli_data_seek($cat_result, 0);
                                while ($cat = mysqli_fetch_assoc($cat_result)) {
                                    echo "<option value='{$cat['id']}' " . 
                                         (isset($_GET['category']) && $_GET['category'] == $cat['id'] ? 'selected' : '') . 
                                         ">{$cat['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="price_range" class="form-select">
                                <option value="">All Prices</option>
                                <option value="0-10000" <?php echo (isset($_GET['price_range']) && $_GET['price_range'] == '0-10000') ? 'selected' : ''; ?>>
                                    Under 10,000
                                </option>
                                <option value="10000-20000" <?php echo (isset($_GET['price_range']) && $_GET['price_range'] == '10000-20000') ? 'selected' : ''; ?>>
                                    10,000 - 20,000
                                </option>
                                <option value="20000-30000" <?php echo (isset($_GET['price_range']) && $_GET['price_range'] == '20000-30000') ? 'selected' : ''; ?>>
                                    20,000 - 30,000
                                </option>
                                <option value="30000-100000" <?php echo (isset($_GET['price_range']) && $_GET['price_range'] == '30000-100000') ? 'selected' : ''; ?>>
                                    30,000 - 100,000
                                </option>
                                <option value="100000+" <?php echo (isset($_GET['price_range']) && $_GET['price_range'] == '100000+') ? 'selected' : ''; ?>>
                                    Above 100,000
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="material" class="form-select">
                                <option value="">All Materials</option>
                                <option value="gold" <?php echo (isset($_GET['material']) && $_GET['material'] == 'gold') ? 'selected' : ''; ?>>Gold</option>
                                <option value="silver" <?php echo (isset($_GET['material']) && $_GET['material'] == 'silver') ? 'selected' : ''; ?>>Silver</option>
                                <option value="platinum" <?php echo (isset($_GET['material']) && $_GET['material'] == 'platinum') ? 'selected' : ''; ?>>Platinum</option>
                                <option value="diamond" <?php echo (isset($_GET['material']) && $_GET['material'] == 'diamond') ? 'selected' : ''; ?>>Diamond</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Jewellery Grid -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <div class="col">
                            <div class="card jewellery-card">
                                <div class="position-relative">
                                    <?php 
                                    // Debug the image path
                                    error_log("Original image_path from DB: " . $row['image_path']);
                                    
                                    // Construct absolute path for checking
                                    $absolute_path = dirname(__FILE__) . '/../uploads/' . $row['image_path'];
                                    error_log("Absolute path: " . $absolute_path);
                                    
                                    // Construct relative path for display
                                    $image_path = !empty($row['image_path']) ? '../uploads/' . $row['image_path'] : '../assets/images/default-jewellery.jpg';
                                    error_log("Relative path for display: " . $image_path);
                                    
                                    if (!file_exists($absolute_path)) {
                                        error_log("Image not found at: " . $absolute_path);
                                        $image_path = '../assets/images/default-jewellery.jpg';
                                    } else {
                                        error_log("Image found at: " . $absolute_path);
                                    }
                                    ?>
                                    <img src="<?php echo $image_path; ?>" 
                                         alt="<?php echo $row['name']; ?>" 
                                         class="jewellery-image w-100"
                                         onerror="this.src='../assets/images/default-jewellery.jpg'">
                                    <span class="stock-badge <?php echo $row['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php echo $row['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                    <p class="card-text text-muted"><?php echo substr($row['description'], 0, 100) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="price">₹<?php echo number_format($row['price'], 2); ?></span>
                                        <span class="category-badge"><?php echo $row['category_name']; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="material-badge"><?php echo ucfirst($row['material']); ?></span>
                                        <small class="text-muted">Weight: <?php echo $row['weight']; ?>g</small>
                                    </div>
                                    <form action="add_to_cart.php" method="POST">
                                        <input type="hidden" name="jewellery_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="add-to-cart-btn" 
                                                <?php echo $row['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-cart-plus me-2"></i>
                                            Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 