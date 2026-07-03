<?php
// ===========================================
// Database Configuration
// ===========================================
/**
 @var mysqli $conn
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password
define('DB_NAME', 'pharmatrust_db');

// ===========================================
// Create Database Connection
// ===========================================
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check Connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set Character Encoding
mysqli_set_charset($conn, "utf8mb4");

// ===========================================
// Application Settings
// ===========================================

// Set Timezone (Ghana)
date_default_timezone_set('Africa/Accra');

// Start Session if Not Already Started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if (basename($basePath) === 'pages') {
    $basePath = dirname($basePath);
}
if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}

define('BASE_PATH', $basePath);

// ===========================================
// Authentication Functions
// ===========================================

// Check if User is Logged In
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Redirect User if Not Logged In
if (!function_exists('requireLogin')) {
    function requireLogin()
    {
        if (!isLoggedIn()) {
            $currentDir = dirname($_SERVER['SCRIPT_NAME']);
            $loginPath = basename($currentDir) === 'pages' ? '../login.php' : 'login.php';
            header("Location: {$loginPath}");
            exit();
        }
    }
}

// Redirect API/AJAX requests if not logged in
if (!function_exists('requireLoginAjax')) {
    function requireLoginAjax()
    {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit();
        }
    }
}

// ===========================================
// User Functions
// ===========================================

// Get Employee Information
function getUserData($user_id)
{
    global $conn;

    $stmt = mysqli_prepare($conn, "SELECT * FROM Employee WHERE employee_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function currentUserRole()
{
    return $_SESSION['user_role'] ?? 'Guest';
}

function isAdmin()
{
    return currentUserRole() === 'Admin';
}

function isSalesAssistant()
{
    return currentUserRole() === 'Sales Assistant';
}

function isPharmacist()
{
    return currentUserRole() === 'Pharmacist';
}

function canEditSales()
{
    return isAdmin() || isSalesAssistant();
}

function canEditCustomers()
{
    return isAdmin() || isPharmacist();
}

function canEditMedications()
{
    return isAdmin() || isPharmacist();
}

function canEditPrescriptions()
{
    return isAdmin() || isPharmacist();
}

function canEditSuppliers()
{
    return isAdmin();
}

function canEditConsultations()
{
    return isAdmin() || isPharmacist();
}

function canViewActivities()
{
    return isAdmin();
}

function denyAccess()
{
    header('HTTP/1.1 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>Permission denied.</p>';
    exit();
}

function requireAdmin()
{
    if (!isAdmin()) {
        denyAccess();
    }
}

function requirePharmacistOrAdmin()
{
    if (!isAdmin() && !isPharmacist()) {
        denyAccess();
    }
}

function requireSalesOrAdmin()
{
    if (!isAdmin() && !isSalesAssistant()) {
        denyAccess();
    }
}

function logActivity($action, $details = null)
{
    global $conn;
    if (!isset($conn) || !$conn) {
        return;
    }

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ActivityLog (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        role VARCHAR(64) NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(employee_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $employee_id = $_SESSION['user_id'] ?? 0;
    $role = currentUserRole();

    $stmt = mysqli_prepare($conn, "INSERT INTO ActivityLog (employee_id, role, action, details) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('isss', $employee_id, $role, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

function getRecentActivities($limit = 20)
{
    global $conn;
    if (!isset($conn) || !$conn) {
        return [];
    }

    $limit = intval($limit);
    $result = mysqli_query($conn, "SELECT a.*, e.first_name, e.last_name
        FROM ActivityLog a
        LEFT JOIN Employee e ON a.employee_id = e.employee_id
        ORDER BY a.created_at DESC
        LIMIT {$limit}");

    if (!$result) {
        return [];
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Format amount as Ghana cedis
function formatCurrency($amount)
{
    return 'GH₵' . number_format((float)$amount, 2);
}

// ===========================================
// Message Display Function
// ===========================================

function showMessage($type, $message)
{
    $class = ($type === 'success') ? 'alert-success' : 'alert-danger';

    return "
    <div class='alert {$class} alert-dismissible fade show' role='alert'>
        {$message}
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}
?>