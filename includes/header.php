<?php
if (!isset($basePath)) {
    if (defined('BASE_PATH')) {
        $basePath = BASE_PATH;
    } else {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if (basename($basePath) === 'pages') {
            $basePath = dirname($basePath);
        }
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaTrust - Pharmacy Management System</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css">

    <!-- jQuery (Required for script.js and inline page scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap 5 Bundle (Includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="<?= $basePath ?>/assets/js/script.js"></script>
</head>

<body>
    
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">

    <div class="container-fluid">

        <!-- BRAND -->
        <a class="navbar-brand" href="<?= $basePath ?>/pages/dashboard.php">
            <i class="fas fa-hospital-alt"></i> PharmaTrust
        </a>

        <!-- TOGGLER -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- MENU -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- LEFT LINKS -->
            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/dashboard.php">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>

                <?php if (isAdmin() || isPharmacist()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/customers.php">
                        <i class="fas fa-users"></i> Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/medications.php">
                        <i class="fas fa-pills"></i> Medications
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isSalesAssistant()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/sales.php">
                        <i class="fas fa-shopping-cart"></i> Sales
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isAdmin() || isPharmacist()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/prescriptions.php">
                        <i class="fas fa-prescription"></i> Prescriptions
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/suppliers.php">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isAdmin() || isPharmacist()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/consultations.php">
                        <i class="fas fa-stethoscope"></i> Consultations
                    </a>
                </li>
                <?php endif; ?>

                <?php if (canViewActivities()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $basePath ?>/pages/dashboard.php#activityLog">
                        <i class="fas fa-list"></i> Activity Log
                    </a>
                </li>
                <?php endif; ?>

            </ul>

            <!-- RIGHT USER MENU -->
            <ul class="navbar-nav">

                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown">

                        <i class="fas fa-user-circle"></i>

                        <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>

                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">

                        <li>
                            <a class="dropdown-item" href="<?= $basePath ?>/pages/profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item text-danger"
                               href="<?= $basePath ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>

                    </ul>

                </li>

            </ul>

        </div>
    </div>
</nav>

<!-- PAGE CONTAINER START -->
<div class="main-container">
    <div class="container mt-4">