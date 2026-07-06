<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($customer_id <= 0) {
    header('Location: customers.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM Customer WHERE customer_id = ?');
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    header('Location: customers.php');
    exit();
}

$sales = mysqli_query($conn, sprintf(
    "SELECT s.sale_id, s.total_amount, s.sale_date, e.first_name AS emp_first, e.last_name AS emp_last FROM Sale s JOIN Employee e ON s.employee_id = e.employee_id WHERE s.customer_id = %d ORDER BY s.sale_date DESC",
    $customer_id
));

$prescriptions = mysqli_query($conn, sprintf(
    "SELECT p.prescription_id, p.issue_date, p.expiry_date, p.refill_count, p.doctor_name, m.name AS medication_name FROM Prescription p JOIN Medication m ON p.medication_id = m.medication_id WHERE p.customer_id = %d ORDER BY p.issue_date DESC",
    $customer_id
));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2><i class="fas fa-user"></i> Customer Details</h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?> details and history.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="customers.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
        <button class="btn btn-primary" onclick="window.location.href='customers.php'">
            <i class="fas fa-users"></i> All Customers
        </button>
    </div>
</div>

<div class="row gy-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Customer Info</h5>
                <p class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></p>
                <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?></p>
                <p class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($customer['phone']) ?></p>
                <p class="mb-2"><strong>DOB:</strong> <?= $customer['dob'] ? date('M d, Y', strtotime($customer['dob'])) : '<span class="text-muted">Not set</span>' ?></p>
                <p class="mb-2"><strong>Joined:</strong> <?= $customer['registration_date'] ? date('M d, Y', strtotime($customer['registration_date'])) : '<span class="text-muted">Unknown</span>' ?></p>
                <p class="mb-0"><strong>Address:</strong><br><?= nl2br(htmlspecialchars($customer['address'])) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Sales History</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Amount</th>
                                <th>Employee</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($sales) > 0): ?>
                                <?php while ($sale = mysqli_fetch_assoc($sales)): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($sale['sale_id']) ?></td>
                                        <td><?= formatCurrency($sale['total_amount']) ?></td>
                                        <td><?= htmlspecialchars($sale['emp_first'] . ' ' . $sale['emp_last']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($sale['sale_date'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No sales found for this customer.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Prescription Records</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Prescription ID</th>
                                <th>Medication</th>
                                <th>Doctor</th>
                                <th>Issue Date</th>
                                <th>Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($prescriptions) > 0): ?>
                                <?php while ($prescription = mysqli_fetch_assoc($prescriptions)): ?>
                                    <?php $expired = strtotime($prescription['expiry_date']) < time(); ?>
                                    <tr>
                                        <td><?= htmlspecialchars($prescription['prescription_id']) ?></td>
                                        <td><?= htmlspecialchars($prescription['medication_name']) ?></td>
                                        <td><?= htmlspecialchars($prescription['doctor_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($prescription['issue_date'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($prescription['expiry_date'])) ?></td>
                                        <td><span class="badge bg-<?= $expired ? 'danger' : 'success' ?>"><?= $expired ? 'Expired' : 'Active' ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No prescriptions found for this customer.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
