<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jewellery Shop Management - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #2c3e50;
            font-size: 24px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
        }
        .btn-login {
            background: #2c3e50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
            margin-top: 20px;
        }
        .btn-login:hover {
            background: #34495e;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Jewellery Shop Management</h1>
                <p>Please login to continue</p>
            </div>
            <form action="login_process.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="user_type" class="form-label">Login as</label>
                    <select class="form-control" id="user_type" name="user_type" required>
                        <option value="customer">Customer</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-login">Login</button>
                <div class="text-center mt-3">
                    <a href="register.php">Don't have an account? Register here</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 