<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $jewellery_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Get image path before deleting
    $sql = "SELECT image_path FROM jewellery WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $jewellery_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $jewellery = mysqli_fetch_assoc($result);

    // Delete the jewellery
    $delete_sql = "DELETE FROM jewellery WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $jewellery_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        // Delete the image file
        if ($jewellery && file_exists("../uploads/" . $jewellery['image_path'])) {
            unlink("../uploads/" . $jewellery['image_path']);
        }
        $_SESSION['success'] = "Jewellery deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting jewellery: " . mysqli_error($conn);
    }
}

header("Location: dashboard.php");
exit();
?> 