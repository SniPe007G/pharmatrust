<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
requirePharmacistOrAdmin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

/* =========================
   ADD CONSULTATION (SAFE)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_consultation'])) {

    $customer_id = intval($_POST['customer_id']);
    $employee_id = $_SESSION['user_id'];
    $consultation_date = $_POST['consultation_date'];
    $notes = trim($_POST['notes']);
    $duration_minutes = intval($_POST['duration_minutes']);

    /* =========================
       VALIDATION
    ========================= */
    if ($customer_id <= 0) {
        $_SESSION['message'] = 'Invalid customer selected!';
        $_SESSION['message_type'] = 'danger';
        header('Location: consultations.php');
        exit();
    }

    if ($duration_minutes < 5) {
        $_SESSION['message'] = 'Duration must be at least 5 minutes!';
        $_SESSION['message_type'] = 'danger';
        header('Location: consultations.php');
        exit();
    }

    /* =========================
       INSERT CONSULTATION (SAFE)
    ========================= */
    $stmt = $conn->prepare("
        INSERT INTO Consultation
        (customer_id, employee_id, consultation_date, notes, duration_minutes)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iissi",
        $customer_id,
        $employee_id,
        $consultation_date,
        $notes,
        $duration_minutes
    );

    if ($stmt->execute()) {
        logActivity('Add Consultation', 'Customer ID: ' . $customer_id . ', Duration: ' . $duration_minutes . ' min');
        $_SESSION['message'] = 'Consultation added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding consultation!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: consultations.php');
    exit();
}


/* =========================
   DELETE CONSULTATION (SAFE POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    $id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("
        DELETE FROM Consultation
        WHERE consultation_id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        logActivity('Delete Consultation', 'Consultation ID: ' . $id);
        $_SESSION['message'] = 'Consultation deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error deleting consultation!';
        $_SESSION['message_type'] = 'danger';
    }

    $stmt->close();
    header('Location: consultations.php');
    exit();
}


/* =========================
   GET CONSULTATIONS
========================= */
$consultations = mysqli_query($conn, "
    SELECT c.*,
           cust.first_name, cust.last_name,
           emp.first_name AS emp_first,
           emp.last_name AS emp_last
    FROM Consultation c
    JOIN Customer cust ON c.customer_id = cust.customer_id
    JOIN Employee emp ON c.employee_id = emp.employee_id
    ORDER BY c.consultation_id DESC
");


/* =========================
   DROPDOWN DATA
========================= */
$customers = mysqli_query($conn, "
    SELECT customer_id, first_name, last_name
    FROM Customer
    ORDER BY first_name
");

require_once __DIR__ . '/../includes/header.php';
?>

<!-- =========================
     HEADER
========================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-stethoscope"></i> Consultations</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConsultationModal">
        <i class="fas fa-plus"></i> Add Consultation
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

            <table class="table table-striped table-hover" id="consultationTable">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Pharmacist</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($consultations)): ?>
                    <tr>
                        <td><?= $row['consultation_id'] ?></td>

                        <td><?= $row['first_name'] . ' ' . $row['last_name'] ?></td>

                        <td><?= $row['emp_first'] . ' ' . $row['emp_last'] ?></td>

                        <td><?= date('M d, Y H:i', strtotime($row['consultation_date'])) ?></td>

                        <td><?= $row['duration_minutes'] ?> min</td>

                        <td>
                            <?= htmlspecialchars(substr($row['notes'] ?? 'N/A', 0, 30)) ?>...
                        </td>

                        <td>
                            <button class="btn btn-sm btn-info"
                                onclick="viewConsultation(<?= $row['consultation_id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>

                            <!-- SAFE DELETE -->
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Delete this consultation?')">
                                <input type="hidden" name="delete_id"
                                       value="<?= $row['consultation_id'] ?>">
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
<div class="modal fade" id="addConsultationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">Add Consultation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Customer</label>
                        <select class="form-control" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                                <option value="<?= $customer['customer_id'] ?>">
                                    <?= $customer['first_name'] . ' ' . $customer['last_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Date & Time</label>
                        <input type="datetime-local" class="form-control"
                               name="consultation_date" required>
                    </div>

                    <div class="mb-3">
                        <label>Duration (Minutes)</label>
                        <input type="number" class="form-control"
                               name="duration_minutes" value="15" min="5" required>
                    </div>

                    <div class="mb-3">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes"
                                  rows="3" placeholder="Consultation notes..."></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" name="add_consultation"
                            class="btn btn-primary">
                        Add Consultation
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
function viewConsultation(id) {
    alert('View consultation ' + id + ' - not implemented yet');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>