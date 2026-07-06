<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

/* =========================================================
   ADD SUPPLIER
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    if (!canEditSuppliers()) {
        denyAccess();
    }

    $name           = $_POST['name'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $phone          = $_POST['phone'] ?? '';
    $email          = $_POST['email'] ?? '';
    $address        = $_POST['address'] ?? '';

    $stmt = $conn->prepare(
        "INSERT INTO Supplier (name, contact_person, phone, email, address)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssss", $name, $contact_person, $phone, $email, $address);

    if ($stmt->execute()) {
        logActivity('Add Supplier', 'Supplier: ' . $name);
        $_SESSION['message'] = 'Supplier added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error: ' . $stmt->error;
        $_SESSION['message_type'] = 'danger';
    }
    $stmt->close();

    header('Location: suppliers.php');
    exit();
}

/* =========================================================
   DELETE SUPPLIER
========================================================= */
if (isset($_GET['delete'])) {
    if (!canEditSuppliers()) {
        denyAccess();
    }

    $id = intval($_GET['delete']);

    // Check dependencies using prepared statement
    $checkStmt = $conn->prepare("SELECT 1 FROM Medication WHERE supplier_id = ? LIMIT 1");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        $_SESSION['message'] = 'Cannot delete supplier with associated medications!';
        $_SESSION['message_type'] = 'danger';
    } else {
        $delStmt = $conn->prepare("DELETE FROM Supplier WHERE supplier_id = ?");
        $delStmt->bind_param("i", $id);
        if ($delStmt->execute()) {
            logActivity('Delete Supplier', 'Supplier ID: ' . $id);
            $_SESSION['message'] = 'Supplier deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting supplier!';
            $_SESSION['message_type'] = 'danger';
        }
        $delStmt->close();
    }
    $checkStmt->close();

    header('Location: suppliers.php');
    exit();
}

/* =========================================================
   FETCH SUPPLIERS
========================================================= */
$suppliers = mysqli_query(
    $conn,
    "SELECT * FROM Supplier ORDER BY supplier_id DESC"
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-truck"></i> Suppliers</h2>

    <?php if (canEditSuppliers()): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="fas fa-plus"></i> Add Supplier
        </button>
    <?php endif; ?>
</div>

<?php
if (isset($_SESSION['message'])) {
    echo showMessage($_SESSION['message_type'], $_SESSION['message']);
    unset($_SESSION['message'], $_SESSION['message_type']);
}
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="supplierTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <?php if (canEditSuppliers()): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($suppliers)): ?>
                    <tr>
                        <td><?= $row['supplier_id']; ?></td>
                        <td><strong><?= $row['name']; ?></strong></td>
                        <td><?= $row['contact_person'] ?? 'N/A'; ?></td>
                        <td><?= $row['phone'] ?? 'N/A'; ?></td>
                        <td><?= $row['email'] ?? 'N/A'; ?></td>
                        <td><?= substr($row['address'] ?? 'N/A', 0, 30) . '...'; ?></td>

                        <?php if (canEditSuppliers()): ?>
                        <td>
                            <button class="btn btn-sm btn-info"
                                    onclick="editSupplier(<?= $row['supplier_id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="?delete=<?= $row['supplier_id']; ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this supplier?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>
</div>

<?php if (canEditSuppliers()): ?>
<!-- =========================================================
    ADD SUPPLIER MODAL
========================================================= -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Company Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label>Contact Person</label>
                        <input type="text" class="form-control" name="contact_person">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Address</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" name="add_supplier" class="btn btn-primary">
                        Add Supplier
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- EDIT SUPPLIER MODAL -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editSupplierForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editSupplierId">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading supplier details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveSupplierBtn" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSupplier(id) {
    const apiUrl = '<?= BASE_PATH ?>/pages/supplier_api.php';
    const modalEl = document.getElementById('editSupplierModal');
    const modal = new bootstrap.Modal(modalEl);

    modalEl.querySelector('.modal-body').innerHTML = showLoading('Loading supplier details...');
    modal.show();

    fetch(`${apiUrl}?id=${encodeURIComponent(id)}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showToast('error', data.message || 'Unable to load supplier.');
                modal.hide();
                return;
            }

            const supplier = data.data;
            const body = modalEl.querySelector('.modal-body');

            body.innerHTML = `
                <input type="hidden" id="editSupplierId" value="${supplier.supplier_id}">
                <div class="mb-3">
                    <label>Company Name</label>
                    <input type="text" id="editSupplierName" class="form-control" value="${supplier.name || ''}" required>
                </div>
                <div class="mb-3">
                    <label>Contact Person</label>
                    <input type="text" id="editSupplierContact" class="form-control" value="${supplier.contact_person || ''}">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Phone</label>
                        <input type="text" id="editSupplierPhone" class="form-control" value="${supplier.phone || ''}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Email</label>
                        <input type="email" id="editSupplierEmail" class="form-control" value="${supplier.email || ''}">
                    </div>
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <textarea id="editSupplierAddress" class="form-control" rows="2">${supplier.address || ''}</textarea>
                </div>
            `;
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to load supplier.');
            modal.hide();
        });
}

document.addEventListener('click', function (event) {
    if (!event.target.closest('#saveSupplierBtn')) {
        return;
    }

    const apiUrl = '<?= BASE_PATH ?>/pages/supplier_api.php';
    const supplierId = document.getElementById('editSupplierId').value;
    const name = document.getElementById('editSupplierName').value.trim();
    const contactPerson = document.getElementById('editSupplierContact').value.trim();
    const phone = document.getElementById('editSupplierPhone').value.trim();
    const email = document.getElementById('editSupplierEmail').value.trim();
    const address = document.getElementById('editSupplierAddress').value.trim();

    if (!name) {
        showToast('warning', 'Company name is required.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_supplier');
    formData.append('supplier_id', supplierId);
    formData.append('name', name);
    formData.append('contact_person', contactPerson);
    formData.append('phone', phone);
    formData.append('email', email);
    formData.append('address', address);

    fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Supplier updated successfully.');
                setTimeout(() => window.location.reload(), 700);
            } else {
                showToast('error', data.message || 'Unable to update supplier.');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to update supplier.');
        });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>