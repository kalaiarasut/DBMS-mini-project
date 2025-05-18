<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$jewellery_id = mysqli_real_escape_string($conn, $_GET['id']);

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

    // Handle image upload if new image is provided
    $image_path = $_POST['current_image'];
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . time() . '_' . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
        // Debug information
        error_log("Upload attempt - File: " . $_FILES["image"]["name"]);
        error_log("Target directory: " . $target_dir);
        error_log("Target file: " . $target_file);
        
        if (getimagesize($_FILES["image"]["tmp_name"]) !== false) {
            if ($_FILES["image"]["size"] <= 5000000) {
                if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg") {
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        // Delete old image if it exists and is not the default image
                        if (!empty($image_path) && $image_path !== 'default-jewellery.jpg' && file_exists($target_dir . $image_path)) {
                            unlink($target_dir . $image_path);
                        }
                        $image_path = time() . '_' . basename($_FILES["image"]["name"]);
                        error_log("Image uploaded successfully. New path: " . $image_path);
                    } else {
                        $error = error_get_last();
                        error_log("Error moving uploaded file: " . ($error ? $error['message'] : 'Unknown error'));
                        $_SESSION['error'] = "Error moving uploaded file. Error: " . ($error ? $error['message'] : 'Unknown error');
                    }
                } else {
                    error_log("Invalid file type: " . $imageFileType);
                    $_SESSION['error'] = "Sorry, only JPG, JPEG & PNG files are allowed.";
                }
            } else {
                error_log("File too large: " . $_FILES["image"]["size"]);
                $_SESSION['error'] = "Sorry, your file is too large. Maximum size is 5MB.";
            }
        } else {
            error_log("File is not an image");
            $_SESSION['error'] = "File is not an image.";
        }
    }

    // Update jewellery
    $sql = "UPDATE jewellery SET name=?, description=?, price=?, category_id=?, material=?, color=?, purity_ratio=?, weight=?, image_path=?, stock_quantity=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssdsisdddsi", $name, $description, $price, $category_id, $material, $color, $purity_ratio, $weight, $image_path, $stock_quantity, $jewellery_id);
    
    if (mysqli_stmt_execute($stmt)) {
        error_log("Database updated successfully with image path: " . $image_path);
        $_SESSION['success'] = "Jewellery updated successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        error_log("Database update failed: " . mysqli_error($conn));
        $_SESSION['error'] = "Error updating jewellery: " . mysqli_error($conn);
    }
}

// Fetch jewellery details
$sql = "SELECT * FROM jewellery WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $jewellery_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$jewellery = mysqli_fetch_assoc($result);

if (!$jewellery) {
    header("Location: dashboard.php");
    exit();
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
    <title>Edit Jewellery - Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
            position: fixed;
            width: inherit;
            max-width: inherit;
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
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
            border-color: #2c3e50;
        }
        .btn-primary {
            background: #2c3e50;
            border-color: #2c3e50;
        }
        .btn-primary:hover {
            background: #34495e;
            border-color: #34495e;
        }
        .current-image {
            max-width: 200px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
                    <h2>Edit Jewellery</h2>
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
                            <input type="hidden" name="current_image" value="<?php echo $jewellery['image_path']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo $jewellery['name']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php while ($cat = mysqli_fetch_assoc($cat_result)) { ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    <?php echo ($cat['id'] == $jewellery['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo $cat['name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required><?php echo $jewellery['description']; ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price (₹)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $jewellery['price']; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Material</label>
                                    <select name="material" class="form-select" required>
                                        <option value="">Select Material</option>
                                        <option value="gold" <?php echo ($jewellery['material'] == 'gold') ? 'selected' : ''; ?>>Gold</option>
                                        <option value="silver" <?php echo ($jewellery['material'] == 'silver') ? 'selected' : ''; ?>>Silver</option>
                                        <option value="platinum" <?php echo ($jewellery['material'] == 'platinum') ? 'selected' : ''; ?>>Platinum</option>
                                        <option value="diamond" <?php echo ($jewellery['material'] == 'diamond') ? 'selected' : ''; ?>>Diamond</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Color</label>
                                    <input type="text" name="color" class="form-control" value="<?php echo $jewellery['color']; ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Purity Ratio (%)</label>
                                    <input type="number" name="purity_ratio" class="form-control" step="0.01" value="<?php echo $jewellery['purity_ratio']; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Weight (grams)</label>
                                    <input type="number" name="weight" class="form-control" step="0.01" value="<?php echo $jewellery['weight']; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" name="stock_quantity" class="form-control" value="<?php echo $jewellery['stock_quantity']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Current Image</label><br>
                                <?php 
                                $image_path = !empty($jewellery['image_path']) ? '../uploads/' . $jewellery['image_path'] : '../assets/images/default-jewellery.jpg';
                                if (!file_exists($image_path)) {
                                    error_log("Image not found in edit page: " . $image_path);
                                    $image_path = '../assets/images/default-jewellery.jpg';
                                }
                                ?>
                                <img src="<?php echo $image_path; ?>" 
                                     alt="Current Image" 
                                     class="current-image mb-2"
                                     onerror="this.src='../assets/images/default-jewellery.jpg'">
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image. Max file size: 5MB. Allowed formats: JPG, JPEG, PNG</small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Jewellery
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