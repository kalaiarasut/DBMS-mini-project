<?php
session_start();
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);

    $sql = "SELECT * FROM users WHERE email = ? AND user_type = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt === false) {
        die("Error preparing statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $email, $user_type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['name'] = $row['name'];

            if ($user_type == 'manager') {
                header("Location: manager/dashboard.php");
            } else {
                header("Location: customer/dashboard.php");
            }
            exit();
        }
    }
    
    $_SESSION['error'] = "Invalid email or password";
    header("Location: index.php");
    exit();
}
?> 