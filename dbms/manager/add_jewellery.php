<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $material = mysqli_real_escape_string($conn, $_POST['material']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $purity_ratio = mysqli_real_escape_string($conn, $_POST['purity_ratio']);
    $weight = mysqli_real_escape_string($conn, $_POST['weight']);
    $stock_quantity = mysqli_real_escape_string($conn, $_POST['stock_quantity']);

    // Server-side validation for negative and out-of-range values
    if ($price < 0) {
        $_SESSION['error'] = "Price cannot be negative.";
        header("Location: add_jewellery.php");
        exit();
    }
    if ($purity_ratio < 0 || $purity_ratio > 100) {
        $_SESSION['error'] = "Purity ratio must be between 0 and 100.";
        header("Location: add_jewellery.php");
        exit();
    }
    if ($weight < 0) {
        $_SESSION['error'] = "Weight cannot be negative.";
        header("Location: add_jewellery.php");
        exit();
    }
    if ($stock_quantity < 0) {
        $_SESSION['error'] = "Stock quantity cannot be negative.";
        header("Location: add_jewellery.php");
        exit();
    }

    // Handle image upload
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_path = "";
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_file = $target_dir . time() . '_' . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            // Check file size (5MB max)
            if ($_FILES["image"]["size"] <= 5000000) {
                // Allow certain file formats
                if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg") {
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_path = time() . '_' . basename($_FILES["image"]["name"]);
                    } else {
                        $_SESSION['error'] = "Error moving uploaded file. Error: " . error_get_last()['message'];
                    }
                } else {
                    $_SESSION['error'] = "Sorry, only JPG, JPEG & PNG files are allowed.";
                }
            } else {
                $_SESSION['error'] = "Sorry, your file is too large. Maximum size is 5MB.";
            }
        } else {
            $_SESSION['error'] = "File is not an image.";
        }
    } else {
        $_SESSION['error'] = "Error uploading file. Error code: " . $_FILES["image"]["error"];
    }

    if ($image_path) {
        $sql = "INSERT INTO jewellery (name, description, price, category_id, material, color, purity_ratio, weight, image_path, stock_quantity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdsisddds", $name, $description, $price, $category_id, $material, $color, $purity_ratio, $weight, $image_path, $stock_quantity);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Jewellery added successfully!";
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Error adding jewellery: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Error uploading image. Please try again.";
    }
}

// Fetch categories for dropdown
$cat_sql = "SELECT * FROM categories";
$cat_result = mysqli_query($conn, $cat_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Jewellery - Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
        }
        .sidebar a:hover {
            background: #34495e;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="mb-4">Manager Panel</h3>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-home me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="add_jewellery.php" class="nav-link active">
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
                    <h2>Add New Jewellery</h2>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php while ($cat = mysqli_fetch_assoc($cat_result)) { ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo $cat['name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price (₹)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Material</label>
                                    <select name="material" class="form-select" required>
                                        <option value="">Select Material</option>
                                        <option value="gold">Gold</option>
                                        <option value="silver">Silver</option>
                                        <option value="platinum">Platinum</option>
                                        <option value="diamond">Diamond</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Color</label>
                                    <input type="text" name="color" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Purity Ratio (%)</label>
                                    <input type="number" name="purity_ratio" class="form-control" step="0.01" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Weight (grams)</label>
                                    <input type="number" name="weight" class="form-control" step="0.01" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" name="stock_quantity" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                                <small class="text-muted">Max file size: 5MB. Allowed formats: JPG, JPEG, PNG</small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Jewellery
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 