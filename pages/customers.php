<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
requirePharmacistOrAdmin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

/* =========================
   ADD CUSTOMER (SAFE)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $phone      = $_POST['phone'];
    $email      = $_POST['email'];
    $dob        = $_POST['dob'];
    $address    = $_POST['address'];

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Invalid email format';
        $_SESSION['message_type'] = 'danger';
        header('Location: customers.php');
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO Customer (first_name, last_name, phone, email, dob, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $first_name, $last_name, $phone, $email, $dob, $address);

    if ($stmt->execute()) {
        logActivity('Add Customer', 'Customer: ' . $first_name . ' ' . $last_name . ' (Phone: ' . $phone . ')');
        $_SESSION['message'] = 'Customer added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding customer!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: customers.php');
    exit();
}

/* =========================
   DELETE CUSTOMER (SAFE POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);

    $stmt = $conn->prepare('DELETE FROM Customer WHERE customer_id = ?');
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        logActivity('Delete Customer', 'Customer ID: ' . $id);
        $_SESSION['message'] = 'Customer deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error deleting customer!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: customers.php');
    exit();
}

/* =========================
   GET ALL CUSTOMERS
========================= */
$customers = mysqli_query($conn, 'SELECT * FROM Customer ORDER BY customer_id DESC');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2><i class="fas fa-users"></i> Customers</h2>
        <p class="text-muted mb-0">Manage customers, view related records, and update details quickly.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <input id="customerSearch" type="search" class="form-control search-input" data-table="customerTable" placeholder="Search customers..." style="min-width:240px;">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="fas fa-plus"></i> Add Customer
        </button>
    </div>
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
            <table class="table table-striped table-hover" id="customerTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>DOB</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($customers)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['customer_id']) ?></td>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $row['dob'] ? date('M d, Y', strtotime($row['dob'])) : '' ?></td>
                            <td><?= $row['registration_date'] ? date('M d, Y', strtotime($row['registration_date'])) : '' ?></td>
                            <td>
                                <a href="<?= BASE_PATH ?>/pages/customer_details.php?id=<?= $row['customer_id'] ?>" class="btn btn-sm btn-secondary me-1">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-info me-1" onclick="editCustomer(<?= $row['customer_id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this customer?')">
                                    <input type="hidden" name="delete_id" value="<?= $row['customer_id'] ?>">
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

<!-- ADD CUSTOMER MODAL -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_customer" class="btn btn-primary">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT CUSTOMER MODAL -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCustomerForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="editCustomerId">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Waiting for customer details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveCustomerBtn" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCustomer(id) {
    const editCustomerModal = document.getElementById('editCustomerModal');
    const modal = new bootstrap.Modal(editCustomerModal);
    const apiUrl = '<?= BASE_PATH ?>/pages/customer_api.php';

    document.querySelector('#editCustomerModal .modal-body').innerHTML = showLoading('Loading customer details...');
    modal.show();

    fetch(`${apiUrl}?id=${encodeURIComponent(id)}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(response => {
            if (!response.success) {
                showToast('error', response.message || 'Unable to load customer.');
                modal.hide();
                return;
            }

            const customer = response.data;

            document.querySelector('#editCustomerModal .modal-body').innerHTML = `
                <input type="hidden" name="customer_id" id="editCustomerId" value="${customer.customer_id}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>First Name</label>
                        <input type="text" name="first_name" id="editFirstName" class="form-control" value="${customer.first_name || ''}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" id="editLastName" class="form-control" value="${customer.last_name || ''}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone" id="editPhone" class="form-control" value="${customer.phone || ''}">
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" id="editEmail" class="form-control" value="${customer.email || ''}" required>
                </div>
                <div class="mb-3">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" id="editDob" class="form-control" value="${customer.dob || ''}">
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <textarea name="address" id="editAddress" class="form-control" rows="3">${customer.address || ''}</textarea>
                </div>
            `;
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to load customer.');
            modal.hide();
        });
}

document.addEventListener('click', function (event) {
    if (!event.target.closest('#saveCustomerBtn')) {
        return;
    }

    const data = {
        action: 'update_customer',
        customer_id: document.getElementById('editCustomerId').value,
        first_name: document.getElementById('editFirstName').value.trim(),
        last_name: document.getElementById('editLastName').value.trim(),
        phone: document.getElementById('editPhone').value.trim(),
        email: document.getElementById('editEmail').value.trim(),
        dob: document.getElementById('editDob').value,
        address: document.getElementById('editAddress').value.trim()
    };

    if (!data.first_name || !data.last_name || !data.email) {
        showToast('warning', 'First name, last name and email are required.');
        return;
    }

    const apiUrl = '<?= BASE_PATH ?>/pages/customer_api.php';

    fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(data)
    })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                showToast('success', response.message || 'Customer updated successfully.');
                setTimeout(function () {
                    window.location.reload();
                }, 700);
            } else {
                showToast('error', response.message || 'Unable to update customer.');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('error', 'Unable to update customer.');
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
