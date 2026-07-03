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
    if (!canEditSuppliers()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid supplier ID.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT supplier_id, name, contact_person, phone, email, address FROM Supplier WHERE supplier_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();

    if (!$supplier) {
        echo json_encode(['success' => false, 'message' => 'Supplier not found.']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $supplier]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEditSuppliers()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'update_supplier') {
        echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
        exit();
    }

    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($supplier_id <= 0 || $name === '') {
        echo json_encode(['success' => false, 'message' => 'Supplier ID and name are required.']);
        exit();
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE Supplier SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE supplier_id = ?");
    $stmt->bind_param('sssssi', $name, $contact_person, $phone, $email, $address, $supplier_id);

    if ($stmt->execute()) {
        logActivity('Update Supplier', 'Supplier ID: ' . $supplier_id);
        echo json_encode(['success' => true, 'message' => 'Supplier updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to update supplier.']);
    }

    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Request method not allowed.']);
exit();
