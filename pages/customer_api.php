<?php
require_once __DIR__ . '/../includes/config.php';
requireLoginAjax();
if (!isset($conn) || !$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!canEditCustomers()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid customer ID.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, phone, email, dob, address FROM Customer WHERE customer_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found.']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $customer]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEditCustomers()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action !== 'update_customer') {
        echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
        exit();
    }

    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $dob         = $_POST['dob'] ?? null;
    $address     = trim($_POST['address'] ?? '');

    if ($customer_id <= 0 || $first_name === '' || $last_name === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'Customer ID, first name, last name, and email are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE Customer SET first_name = ?, last_name = ?, phone = ?, email = ?, dob = ?, address = ? WHERE customer_id = ?");
    $stmt->bind_param('ssssssi', $first_name, $last_name, $phone, $email, $dob, $address, $customer_id);

    if ($stmt->execute()) {
        logActivity('Update Customer', 'Customer ID: ' . $customer_id);
        echo json_encode(['success' => true, 'message' => 'Customer updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to update customer details.']);
    }

    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Request method not allowed.']);
exit();
