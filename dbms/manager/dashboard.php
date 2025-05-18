<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'manager') {
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

// Fetch total orders
$order_sql = "SELECT COUNT(*) as total_orders FROM orders";
$order_result = mysqli_query($conn, $order_sql);
$order_row = mysqli_fetch_assoc($order_result);
$total_orders = $order_row['total_orders'];

// Fetch total customers
$customer_sql = "SELECT COUNT(*) as total_customers FROM users WHERE user_type = 'customer'";
$customer_result = mysqli_query($conn, $customer_sql);
$customer_row = mysqli_fetch_assoc($customer_result);
$total_customers = $customer_row['total_customers'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Jewellery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
            position: fixed;
            width: inherit;
            max-width: inherit;
            z-index: 1000;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover {
            background: #34495e;
            transform: translateX(5px);
        }
        .main-content {
            padding: 20px;
            margin-left: 16.666667%;
        }
        .card {
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border: none;
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .jewellery-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead th {
            background: #2c3e50;
            color: white;
            border: none;
        }
        .btn-primary {
            background: #2c3e50;
            border-color: #2c3e50;
        }
        .btn-primary:hover {
            background: #34495e;
            border-color: #34495e;
        }
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-right: 40px;
            border-radius: 20px;
        }
        .search-box .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .alert {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .action-buttons .btn {
            padding: 5px 10px;
            margin: 0 2px;
        }
        .stats-card {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stats-card h3 {
            font-size: 1.5rem;
            margin: 0;
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
                        <a href="add_jewellery.php" class="nav-link">
                            <i class="fas fa-plus me-2"></i> Add Jewellery
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="categories.php" class="nav-link">
                            <i class="fas fa-tags me-2"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="orders.php" class="nav-link">
                            <i class="fas fa-shopping-cart me-2"></i> Orders
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
                    <h2>Jewellery Management</h2>
                    <a href="add_jewellery.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Jewellery
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

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-gem"></i>
                            <h3>Total Items</h3>
                            <p class="mb-0"><?php echo mysqli_num_rows($result); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-tags"></i>
                            <h3>Categories</h3>
                            <p class="mb-0"><?php echo mysqli_num_rows($cat_result); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Total Orders</h3>
                            <p class="mb-0"><?php echo $total_orders; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <i class="fas fa-users"></i>
                            <h3>Total Customers</h3>
                            <p class="mb-0"><?php echo $total_customers; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
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
                </div>

                <!-- Jewellery List -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Material</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td>
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
                                             class="jewellery-image"
                                             onerror="this.src='../assets/images/default-jewellery.jpg'">
                                    </td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['category_name']; ?></td>
                                    <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo ucfirst($row['material']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['stock_quantity'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $row['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="edit_jewellery.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_jewellery.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this item?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 