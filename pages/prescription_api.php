<?php
require_once __DIR__ . '/../includes/config.php';
if (!function_exists('requireLoginAjax')) {
    function requireLoginAjax()
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit();
    }
}
requireLoginAjax();
if (!isset($conn) || !$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!canEditPrescriptions()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid prescription ID.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT prescription_id, customer_id, medication_id, issue_date, expiry_date, refill_count, doctor_name FROM Prescription WHERE prescription_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prescription = $result->fetch_assoc();
    $stmt->close();

    if (!$prescription) {
        echo json_encode(['success' => false, 'message' => 'Prescription not found.']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $prescription]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!canEditPrescriptions()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit();
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'update_prescription') {
        echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
        exit();
    }

    $prescription_id = isset($_POST['prescription_id']) ? intval($_POST['prescription_id']) : 0;
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $medication_id = isset($_POST['medication_id']) ? intval($_POST['medication_id']) : 0;
    $issue_date = $_POST['issue_date'] ?? null;
    $expiry_date = $_POST['expiry_date'] ?? null;
    $refill_count = intval($_POST['refill_count'] ?? 0);
    $doctor_name = trim($_POST['doctor_name'] ?? '');

    if ($prescription_id <= 0 || $customer_id <= 0 || $medication_id <= 0 || !$issue_date || !$expiry_date) {
        echo json_encode(['success' => false, 'message' => 'Prescription ID, customer, medication, and dates are required.']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE Prescription SET customer_id = ?, medication_id = ?, issue_date = ?, expiry_date = ?, refill_count = ?, doctor_name = ? WHERE prescription_id = ?");
    $stmt->bind_param('iissisi', $customer_id, $medication_id, $issue_date, $expiry_date, $refill_count, $doctor_name, $prescription_id);

    if ($stmt->execute()) {
        logActivity('Update Prescription', 'Prescription ID: ' . $prescription_id);
        echo json_encode(['success' => true, 'message' => 'Prescription updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to update prescription.']);
    }

    $stmt->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Request method not allowed.']);
exit();
