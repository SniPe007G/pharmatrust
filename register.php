<?php
require_once __DIR__ . '/includes/config.php';

/* =========================
   REDIRECT IF LOGGED IN
========================= */
if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit();
}

$error = '';
$success = '';

if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

/* =========================
   HANDLE REGISTRATION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $hire_date = date('Y-m-d');
    $role = $_POST['role'] ?? 'Sales Assistant';
    $salary = 30000.00;

    /* =========================
       VALIDATION
    ========================= */
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'All fields are required!';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format!';

    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';

    } else {

        /* =========================
           CHECK IF EMAIL EXISTS (use separate statements)
        ========================= */
        $checkStmt = $conn->prepare(
            "SELECT employee_id FROM Employee WHERE email = ?"
        );
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult && $checkResult->num_rows > 0) {
            $error = 'Email already registered!';
            $checkStmt->close();
        } else {
            /* =========================
               SECURE PASSWORD HASHING
            ========================= */
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            /* =========================
               INSERT EMPLOYEE
            ========================= */
            $insertStmt = $conn->prepare(
                "INSERT INTO Employee (first_name, last_name, phone, email, password, hire_date, role, salary) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $insertStmt->bind_param(
                "sssssssd",
                $first_name,
                $last_name,
                $phone,
                $email,
                $hashed_password,
                $hire_date,
                $role,
                $salary
            );

            if ($insertStmt->execute()) {
                // Log activity for admin/audit
                if (function_exists('logActivity')) {
                    logActivity('Add Employee', 'Employee: ' . $first_name . ' ' . $last_name . ' (Email: ' . $email . ')');
                }
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Error creating account. Please try again.';
            }

            $insertStmt->close();
            $checkStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PharmaTrust</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,.3);
        }
        .logo {
            font-size: 60px;
            text-align: center;
            color: #667eea;
            margin-bottom: 20px;
        }
        .login-card h2 {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            border: none;
            font-weight: bold;
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo">
        <i class="fas fa-user-plus"></i>
    </div>
    <h2>Employee Signup</h2>
    <p class="text-center text-muted">Create an assistant or pharmacist account.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <?php $selectedRole = $_POST['role'] ?? 'Sales Assistant'; ?>
                <option value="Sales Assistant" <?= $selectedRole === 'Sales Assistant' ? 'selected' : '' ?>>Sales Assistant</option>
                <option value="Pharmacist" <?= $selectedRole === 'Pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
            </select>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-4">
            <i class="fas fa-user-plus"></i> Register
        </button>
    </form>

    <div class="text-center mt-4">
        <span class="text-muted">Already have an account?</span>
        <a href="login.php" class="fw-semibold">Login here</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
