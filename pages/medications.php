<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
requirePharmacistOrAdmin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

/* =========================
   ADD MEDICATION (SAFE)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medication'])) {

    $name          = $_POST['name'];
    $generic_name  = $_POST['generic_name'];
    $category      = $_POST['category'];
    $strength      = $_POST['strength'];
    $unit_price    = floatval($_POST['unit_price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $expiry_date   = $_POST['expiry_date'];
    $reorder_level = intval($_POST['reorder_level']);
    $supplier_id   = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;

    // Basic validation
    if ($unit_price < 0 || $stock_quantity < 0) {
        $_SESSION['message'] = "Invalid numeric values";
        $_SESSION['message_type'] = "danger";
        header("Location: medications.php");
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO Medication
        (name, generic_name, category, strength, unit_price, stock_quantity, expiry_date, reorder_level, supplier_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssdisii",
        $name,
        $generic_name,
        $category,
        $strength,
        $unit_price,
        $stock_quantity,
        $expiry_date,
        $reorder_level,
        $supplier_id
    );

    if ($stmt->execute()) {
        logActivity('Add Medication', 'Medication: ' . $name . ' (Supplier ID: ' . ($supplier_id ?: 'none') . ')');
        $_SESSION['message'] = 'Medication added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding medication!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: medications.php');
    exit();
}


/* =========================
   DELETE MEDICATION (SAFE POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    $id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("DELETE FROM Medication WHERE medication_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        logActivity('Delete Medication', 'Medication ID: ' . $id);
        $_SESSION['message'] = 'Medication deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error deleting medication!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: medications.php');
    exit();
}


/* =========================
   GET DATA
========================= */
$medications = mysqli_query($conn, "
    SELECT m.*, s.name AS supplier_name
    FROM Medication m
    LEFT JOIN Supplier s ON m.supplier_id = s.supplier_id
    ORDER BY m.medication_id DESC
");

$supplierResult = mysqli_query($conn, "
    SELECT supplier_id, name
    FROM Supplier
    ORDER BY name
");

$suppliers = mysqli_fetch_all($supplierResult, MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
     PAGE HEADER
========================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-pills"></i> Medications</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicationModal">
        <i class="fas fa-plus"></i> Add Medication
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
            <table class="table table-striped table-hover" id="medicationTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Strength</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Expiry</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($medications)):

                    $lowStock = $row['stock_quantity'] <= $row['reorder_level'];
                    $status = $lowStock ? 'Low Stock' : 'In Stock';
                    $statusClass = $lowStock ? 'danger' : 'success';
                ?>
                    <tr>
                        <td><?= $row['medication_id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['category'] ?></td>
                        <td><?= $row['strength'] ?></td>
                        <td><?= formatCurrency($row['unit_price']) ?></td>
                        <td><?= $row['stock_quantity'] ?></td>

                        <td>
                            <?= $row['expiry_date']
                                ? date('M d, Y', strtotime($row['expiry_date']))
                                : '' ?>
                        </td>

                        <td><?= $row['supplier_name'] ?? 'N/A' ?></td>

                        <td>
                            <span class="badge bg-<?= $statusClass ?>">
                                <?= $status ?>
                            </span>
                        </td>

                        <td>
                            <button class="btn btn-sm btn-info"
                                onclick="editMedication(<?= $row['medication_id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>

                            <!-- SAFE DELETE -->
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Delete this medication?')">
                                <input type="hidden" name="delete_id"
                                       value="<?= $row['medication_id'] ?>">
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
     ADD MODAL
========================= -->
<div class="modal fade" id="addMedicationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Generic Name</label>
                            <input type="text" name="generic_name" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Strength</label>
                            <input type="text" name="strength" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Unit Price</label>
                            <input type="number" step="0.01" name="unit_price" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Stock</label>
                            <input type="number" name="stock_quantity" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Expiry</label>
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="10">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['supplier_id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" name="add_medication"
                            class="btn btn-primary">
                        Add Medication
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- EDIT MEDICATION MODAL -->
<div class="modal fade" id="editMedicationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editMedicationForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Medication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editMedicationId">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading medication details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveMedicationBtn" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const medicationSupplierOptions = <?= json_encode($suppliers) ?>;

function buildSupplierOptions(selectedId) {
    return '<option value="">Select Supplier</option>' + medicationSupplierOptions.map(supplier => {
        const selected = supplier.supplier_id == selectedId ? ' selected' : '';
        return `<option value="${supplier.supplier_id}"${selected}>${supplier.name}</option>`;
    }).join('');
}

function editMedication(id) {
    const apiUrl = '<?= BASE_PATH ?>/pages/medication_api.php';
    const modalEl = document.getElementById('editMedicationModal');
    const modal = new bootstrap.Modal(modalEl);

    modalEl.querySelector('.modal-body').innerHTML = showLoading('Loading medication details...');
    modal.show();

    fetch(`${apiUrl}?id=${encodeURIComponent(id)}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showToast('error', data.message || 'Unable to load medication.');
                modal.hide();
                return;
            }

            const medication = data.data;
            const body = modalEl.querySelector('.modal-body');

            body.innerHTML = `
                <input type="hidden" id="editMedicationId" value="${medication.medication_id}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Name</label>
                        <input type="text" id="editMedicationName" class="form-control" value="${medication.name || ''}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Generic Name</label>
                        <input type="text" id="editMedicationGeneric" class="form-control" value="${medication.generic_name || ''}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Category</label>
                        <input type="text" id="editMedicationCategory" class="form-control" value="${medication.category || ''}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Strength</label>
                        <input type="text" id="editMedicationStrength" class="form-control" value="${medication.strength || ''}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Unit Price</label>
                        <input type="number" step="0.01" id="editMedicationPrice" class="form-control" value="${medication.unit_price || ''}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Stock</label>
                        <input type="number" id="editMedicationStock" class="form-control" value="${medication.stock_quantity || ''}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Expiry Date</label>
                        <input type="date" id="editMedicationExpiry" class="form-control" value="${medication.expiry_date || ''}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Reorder Level</label>
                        <input type="number" id="editMedicationReorder" class="form-control" value="${medication.reorder_level || ''}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Supplier</label>
                    <select id="editMedicationSupplier" class="form-control">${buildSupplierOptions(medication.supplier_id)}</select>
                </div>
            `;
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to load medication.');
            modal.hide();
        });
}

document.addEventListener('click', function (event) {
    if (!event.target.closest('#saveMedicationBtn')) {
        return;
    }

    const apiUrl = '<?= BASE_PATH ?>/pages/medication_api.php';
    const medicationId = document.getElementById('editMedicationId').value;
    const name = document.getElementById('editMedicationName').value.trim();
    const genericName = document.getElementById('editMedicationGeneric').value.trim();
    const category = document.getElementById('editMedicationCategory').value.trim();
    const strength = document.getElementById('editMedicationStrength').value.trim();
    const unitPrice = document.getElementById('editMedicationPrice').value;
    const stockQuantity = document.getElementById('editMedicationStock').value;
    const expiryDate = document.getElementById('editMedicationExpiry').value;
    const reorderLevel = document.getElementById('editMedicationReorder').value;
    const supplierId = document.getElementById('editMedicationSupplier').value;

    if (!name || !unitPrice || !stockQuantity || !expiryDate || !reorderLevel) {
        showToast('warning', 'Please fill all required medication fields.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_medication');
    formData.append('medication_id', medicationId);
    formData.append('name', name);
    formData.append('generic_name', genericName);
    formData.append('category', category);
    formData.append('strength', strength);
    formData.append('unit_price', unitPrice);
    formData.append('stock_quantity', stockQuantity);
    formData.append('expiry_date', expiryDate);
    formData.append('reorder_level', reorderLevel);
    formData.append('supplier_id', supplierId);

    fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Medication updated successfully.');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast('error', data.message || 'Unable to update medication.');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to update medication.');
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>