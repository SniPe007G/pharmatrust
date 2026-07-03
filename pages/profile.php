<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'] ?? null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Please provide valid name and email.';
        $_SESSION['message_type'] = 'danger';
        header('Location: profile.php');
        exit();
    }

    // Check email unique
    $stmt = mysqli_prepare($conn, "SELECT employee_id FROM Employee WHERE email = ? AND employee_id != ?");
    mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $_SESSION['message'] = 'Email already in use by another account.';
        $_SESSION['message_type'] = 'danger';
        mysqli_stmt_close($stmt);
        header('Location: profile.php');
        exit();
    }
    mysqli_stmt_close($stmt);

    // If password fields provided, validate
    $updatePassword = false;
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $_SESSION['message'] = 'Passwords do not match.';
            $_SESSION['message_type'] = 'danger';
            header('Location: profile.php');
            exit();
        }
        if (strlen($password) < 6) {
            $_SESSION['message'] = 'Password must be at least 6 characters.';
            $_SESSION['message_type'] = 'danger';
            header('Location: profile.php');
            exit();
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updatePassword = true;
    }

    // Build update query
    if ($updatePassword) {
        $upd = mysqli_prepare($conn, "UPDATE Employee SET first_name = ?, last_name = ?, phone = ?, email = ?, password = ? WHERE employee_id = ?");
        mysqli_stmt_bind_param($upd, 'sssssi', $first_name, $last_name, $phone, $email, $hashed, $user_id);
    } else {
        $upd = mysqli_prepare($conn, "UPDATE Employee SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE employee_id = ?");
        mysqli_stmt_bind_param($upd, 'ssssi', $first_name, $last_name, $phone, $email, $user_id);
    }

    if ($upd && mysqli_stmt_execute($upd)) {
        $_SESSION['message'] = 'Profile updated successfully.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error updating profile.';
        $_SESSION['message_type'] = 'danger';
    }
    if ($upd) mysqli_stmt_close($upd);
    header('Location: profile.php');
    exit();
}

// Load user data
$user = $user_id ? getUserData($user_id) : null;

if (!$user) {
    $_SESSION['message'] = 'User not found.';
    $_SESSION['message_type'] = 'danger';
    header('Location: dashboard.php');
    exit();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8 offset-md-2">
        <div class="card tilt-card">
            <div class="card-header">
                <h5><i class="fas fa-user"></i> Profile</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <div class="me-4">
                        <div class="rounded-circle bg-light" style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--primary-1)">
                            <?php echo strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)); ?>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Email</strong>
                        <div><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Phone</strong>
                        <div><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Hire Date</strong>
                        <div><?php echo htmlspecialchars($user['hire_date'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Salary</strong>
                        <div><?php echo isset($user['salary']) ? 'GH₵' . number_format($user['salary'],2) : 'N/A'; ?></div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-secondary">Back</a>
                    <button id="editProfileBtn" type="button" class="btn btn-primary">Edit Profile</button>
                </div>

                <!-- Edit Form (hidden by default) -->
                <div id="editProfileForm" class="mt-4 d-none">
                    <form method="POST" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>First Name</label>
                                <input type="text" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($user['first_name']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($user['last_name']); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" name="update_profile" class="btn btn-success">Save Changes</button>
                            <button id="cancelEditBtn" type="button" class="btn btn-link text-muted">Cancel</button>
                        </div>
                    </form>
                </div>

                <script>
                    (function(){
                        var btn = document.getElementById('editProfileBtn');
                        var form = document.getElementById('editProfileForm');
                        var cancel = document.getElementById('cancelEditBtn');
                        if (btn && form) {
                            btn.addEventListener('click', function(){
                                form.classList.toggle('d-none');
                                form.scrollIntoView({behavior:'smooth', block:'center'});
                            });
                        }
                        if (cancel && form) {
                            cancel.addEventListener('click', function(){ form.classList.add('d-none'); });
                        }
                    })();
                </script>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
