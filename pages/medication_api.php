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
    if (!canEditMedications()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid medication ID.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT medication_id, name, generic_name, category, strength, unit_price, stock_quantity, expiry_date, reorder_level, supplier_id FROM Medication WHERE medication_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medication = $result->fetch_assoc();
    $stmt->close();

    if (!$medication) {
        echo json_encode(['success' => false, 'message' => 'Medication not found.']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $medication]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEditMedications()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'update_medication') {
        echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
        exit();
    }

    $medication_id = isset($_POST['medication_id']) ? intval($_POST['medication_id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $generic_name = trim($_POST['generic_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $strength = trim($_POST['strength'] ?? '');
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? null;
    $reorder_level = intval($_POST['reorder_level'] ?? 0);
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;

    if ($medication_id <= 0 || $name === '' || $unit_price < 0 || $stock_quantity < 0 || $reorder_level < 0) {
        echo json_encode(['success' => false, 'message' => 'Medication ID, name, price, stock, and reorder level are required.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE Medication SET name = ?, generic_name = ?, category = ?, strength = ?, unit_price = ?, stock_quantity = ?, expiry_date = ?, reorder_level = ?, supplier_id = ? WHERE medication_id = ?");
    $stmt->bind_param('sssdisisii', $name, $generic_name, $category, $strength, $unit_price, $stock_quantity, $expiry_date, $reorder_level, $supplier_id, $medication_id);

    if ($stmt->execute()) {
        logActivity('Update Medication', 'Medication ID: ' . $medication_id);
        echo json_encode(['success' => true, 'message' => 'Medication updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to update medication.']);
    }

    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Request method not allowed.']);
exit();
