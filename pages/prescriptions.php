<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
requirePharmacistOrAdmin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

/* =========================
   ADD PRESCRIPTION (SAFE)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {

    $customer_id   = intval($_POST['customer_id']);
    $medication_id = intval($_POST['medication_id']);
    $issue_date    = $_POST['issue_date'];
    $expiry_date   = $_POST['expiry_date'];
    $refill_count  = intval($_POST['refill_count']);
    $doctor_name   = trim($_POST['doctor_name']);

    /* =========================
       VALIDATION (basic)
    ========================= */
    if ($customer_id <= 0 || $medication_id <= 0) {
        $_SESSION['message'] = 'Invalid customer or medication selected!';
        $_SESSION['message_type'] = 'danger';
        header('Location: prescriptions.php');
        exit();
    }

    /* =========================
       INSERT (SAFE)
    ========================= */
    $stmt = $conn->prepare("
        INSERT INTO Prescription
        (customer_id, medication_id, issue_date, refill_count, doctor_name, expiry_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iissss",
        $customer_id,
        $medication_id,
        $issue_date,
        $refill_count,
        $doctor_name,
        $expiry_date
    );

    if ($stmt->execute()) {
        logActivity('Add Prescription', 'Customer ID: ' . $customer_id . ', Medication ID: ' . $medication_id);
        $_SESSION['message'] = 'Prescription added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding prescription!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: prescriptions.php');
    exit();
}


/* =========================
   DELETE PRESCRIPTION (SAFE POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    $id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("
        DELETE FROM Prescription
        WHERE prescription_id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        logActivity('Delete Prescription', 'Prescription ID: ' . $id);
        $_SESSION['message'] = 'Prescription deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error deleting prescription!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: prescriptions.php');
    exit();
}


/* =========================
   GET PRESCRIPTIONS
========================= */
$prescriptions = mysqli_query($conn, "
    SELECT p.*,
           c.first_name, c.last_name,
           m.name AS medication_name
    FROM Prescription p
    JOIN Customer c ON p.customer_id = c.customer_id
    JOIN Medication m ON p.medication_id = m.medication_id
    ORDER BY p.prescription_id DESC
");


/* =========================
   DROPDOWNS
========================= */
$customerResult = mysqli_query($conn, "
    SELECT customer_id, first_name, last_name
    FROM Customer
    ORDER BY first_name
");

$customers = mysqli_fetch_all($customerResult, MYSQLI_ASSOC);

$medicationResult = mysqli_query($conn, "
    SELECT medication_id, name
    FROM Medication
    ORDER BY name
");

$medications = mysqli_fetch_all($medicationResult, MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
     HEADER
========================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-prescription"></i> Prescriptions</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
        <i class="fas fa-plus"></i> Add Prescription
    </button>
</div>

<?php
if (isset($_SESSION['message'])) {
    echo showMessage($_SESSION['message_type'], $_SESSION['message']);
    unset($_SESSION['message'], $_SESSION['message_type']);
}
?>

<!-- =========================
     TABLE
========================= -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">

            <table class="table table-striped table-hover" id="prescriptionTable">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Medication</th>
                        <th>Issue Date</th>
                        <th>Refills</th>
                        <th>Doctor</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($prescriptions)):

                    $isExpired = strtotime($row['expiry_date']) < time();
                    $status = $isExpired ? 'Expired' : 'Active';
                    $statusClass = $isExpired ? 'danger' : 'success';
                ?>
                    <tr>
                        <td><?= $row['prescription_id'] ?></td>

                        <td><?= $row['first_name'] . ' ' . $row['last_name'] ?></td>

                        <td><?= $row['medication_name'] ?></td>

                        <td><?= date('M d, Y', strtotime($row['issue_date'])) ?></td>

                        <td><?= $row['refill_count'] ?></td>

                        <td><?= $row['doctor_name'] ?></td>

                        <td><?= date('M d, Y', strtotime($row['expiry_date'])) ?></td>

                        <td>
                            <span class="badge bg-<?= $statusClass ?>">
                                <?= $status ?>
                            </span>
                        </td>

                        <td>
                            <button class="btn btn-sm btn-info"
                                onclick="editPrescription(<?= $row['prescription_id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>

                            <!-- SAFE DELETE -->
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Delete this prescription?')">
                                <input type="hidden" name="delete_id"
                                       value="<?= $row['prescription_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>

                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>

        </div>
    </div>
</div>

<!-- =========================
     ADD MODAL (UNCHANGED UI)
========================= -->
<div class="modal fade" id="addPrescriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">Add New Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Customer</label>
                        <select class="form-control" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['customer_id'] ?>">
                                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Medication</label>
                        <select class="form-control" name="medication_id" required>
                            <option value="">Select Medication</option>
                            <?php foreach ($medications as $med): ?>
                                <option value="<?= $med['medication_id'] ?>">
                                    <?= htmlspecialchars($med['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Issue Date</label>
                            <input type="date" class="form-control" name="issue_date" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" required>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>Refill Count</label>
                            <input type="number" class="form-control"
                                   name="refill_count" value="0" min="0">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Doctor Name</label>
                            <input type="text" class="form-control" name="doctor_name">
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" name="add_prescription"
                            class="btn btn-primary">
                        Add Prescription
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- EDIT PRESCRIPTION MODAL -->
<div class="modal fade" id="editPrescriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editPrescriptionForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editPrescriptionId">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading prescription details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="savePrescriptionBtn" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const prescriptionCustomerOptions = <?= json_encode($customers) ?>;
const prescriptionMedicationOptions = <?= json_encode($medications) ?>;

function buildCustomerOptions(selectedId) {
    return '<option value="">Select Customer</option>' + prescriptionCustomerOptions.map(customer => {
        const selected = customer.customer_id == selectedId ? ' selected' : '';
        return `<option value="${customer.customer_id}"${selected}>${customer.first_name} ${customer.last_name}</option>`;
    }).join('');
}

function buildMedicationOptions(selectedId) {
    return '<option value="">Select Medication</option>' + prescriptionMedicationOptions.map(med => {
        const selected = med.medication_id == selectedId ? ' selected' : '';
        return `<option value="${med.medication_id}"${selected}>${med.name}</option>`;
    }).join('');
}

function editPrescription(id) {
    const apiUrl = '<?= BASE_PATH ?>/pages/prescription_api.php';
    const modalEl = document.getElementById('editPrescriptionModal');
    const modal = new bootstrap.Modal(modalEl);

    modalEl.querySelector('.modal-body').innerHTML = showLoading('Loading prescription details...');
    modal.show();

    fetch(`${apiUrl}?id=${encodeURIComponent(id)}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showToast('error', data.message || 'Unable to load prescription.');
                modal.hide();
                return;
            }

            const prescription = data.data;
            const body = modalEl.querySelector('.modal-body');

            body.innerHTML = `
                <input type="hidden" id="editPrescriptionId" value="${prescription.prescription_id}">
                <div class="mb-3">
                    <label>Customer</label>
                    <select id="editPrescriptionCustomer" class="form-control">${buildCustomerOptions(prescription.customer_id)}</select>
                </div>
                <div class="mb-3">
                    <label>Medication</label>
                    <select id="editPrescriptionMedication" class="form-control">${buildMedicationOptions(prescription.medication_id)}</select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Issue Date</label>
                        <input type="date" id="editPrescriptionIssueDate" class="form-control" value="${prescription.issue_date || ''}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Expiry Date</label>
                        <input type="date" id="editPrescriptionExpiryDate" class="form-control" value="${prescription.expiry_date || ''}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Refill Count</label>
                        <input type="number" id="editPrescriptionRefill" class="form-control" value="${prescription.refill_count || 0}" min="0" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Doctor Name</label>
                        <input type="text" id="editPrescriptionDoctor" class="form-control" value="${prescription.doctor_name || ''}">
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to load prescription.');
            modal.hide();
        });
}

document.addEventListener('click', function (event) {
    if (!event.target.closest('#savePrescriptionBtn')) {
        return;
    }

    const apiUrl = '<?= BASE_PATH ?>/pages/prescription_api.php';
    const prescriptionId = document.getElementById('editPrescriptionId').value;
    const customerId = document.getElementById('editPrescriptionCustomer').value;
    const medicationId = document.getElementById('editPrescriptionMedication').value;
    const issueDate = document.getElementById('editPrescriptionIssueDate').value;
    const expiryDate = document.getElementById('editPrescriptionExpiryDate').value;
    const refillCount = document.getElementById('editPrescriptionRefill').value;
    const doctorName = document.getElementById('editPrescriptionDoctor').value.trim();

    if (!customerId || !medicationId || !issueDate || !expiryDate) {
        showToast('warning', 'Please fill all required fields.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_prescription');
    formData.append('prescription_id', prescriptionId);
    formData.append('customer_id', customerId);
    formData.append('medication_id', medicationId);
    formData.append('issue_date', issueDate);
    formData.append('expiry_date', expiryDate);
    formData.append('refill_count', refillCount);
    formData.append('doctor_name', doctorName);

    fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Prescription updated successfully.');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast('error', data.message || 'Unable to update prescription.');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to update prescription.');
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>