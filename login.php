<?php
require_once 'includes/config.php';
// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepared statement: fetch hashed password and user data by email
    $stmt = mysqli_prepare(
        $conn,
        "SELECT employee_id, first_name, last_name, role, password FROM Employee WHERE email = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify password using password_verify()
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['employee_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: pages/dashboard.php');
            exit();
        } else {
            // Legacy MD5 fallback: if stored hash is md5 of the password, accept and rehash
            if ($user['password'] === md5($password)) {
                // Rehash password using password_hash and update DB
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = mysqli_prepare($conn, "UPDATE Employee SET password = ? WHERE employee_id = ?");
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, "si", $newHash, $user['employee_id']);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                }

                // Set session and log in
                $_SESSION['user_id'] = $user['employee_id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: pages/dashboard.php');
                exit();
            }

            $error = 'Invalid email or password!';
        }
    } else {
        $error = 'Invalid email or password!';
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PharmaTrust</title>
    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >
    <style>
        body{
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
        }
        .login-card{
            background:#fff;
            border-radius:20px;
            padding:40px;
            width:100%;
            max-width:400px;
            box-shadow:0 10px 40px rgba(0,0,0,.30);
        }
        .logo{
            font-size:60px;
            text-align:center;
            color:#667eea;
            margin-bottom:20px;
        }
        .login-card h2{
            text-align:center;
            font-weight:bold;
            margin-bottom:10px;
        }
        .btn-primary{
            width:100%;
            padding:12px;
            border:none;
            font-weight:bold;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo">
        <i class="fas fa-hospital-alt"></i>
    </div>
    <h2>PharmaTrust</h2>
    <p class="text-center text-muted">
        Pharmacy Management System
    </p>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">
                Email Address
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                </span>
                <input
                    type="email"
                    class="form-control"
                    name="email"
                    autocomplete="email"
                    required
                >
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">
                Password
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input
                    type="password"
                    class="form-control"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i>
            Login
        </button>
    </form>
    <div class="text-center mt-3">
        <span class="text-muted">New employee?</span>
        <a href="register.php" class="fw-semibold">Register now</a>
    </div>
</div>
<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>