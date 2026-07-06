<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
requireSalesOrAdmin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}

/* =========================
   ADD SALE (SAFE TRANSACTION)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    if (!canEditSales()) {
        denyAccess();
    }

    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $employee_id = intval($_SESSION['user_id']);
    $items = $_POST['items'] ?? [];

    if (empty($items)) {
        $_SESSION['message'] = 'No items selected!';
        $_SESSION['message_type'] = 'danger';
        header('Location: sales.php');
        exit();
    }

    mysqli_begin_transaction($conn);

    try {

        // Create sale
        $stmt = $conn->prepare("
            INSERT INTO Sale (customer_id, employee_id, total_amount)
            VALUES (?, ?, 0)
        ");
        $stmt->bind_param("ii", $customer_id, $employee_id);
        $stmt->execute();

        $sale_id = $stmt->insert_id;
        $stmt->close();

        $total_amount = 0;

        foreach ($items as $item) {

            $medication_id = intval($item['medication_id']);
            $quantity = intval($item['quantity']);

            if ($quantity <= 0) {
                throw new Exception("Invalid quantity selected.");
            }

            // Get medication info
            $stmt = $conn->prepare("
                SELECT unit_price, stock_quantity
                FROM Medication
                WHERE medication_id = ?
            ");
            $stmt->bind_param("i", $medication_id);
            $stmt->execute();
            $med = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$med) {
                throw new Exception("Medication not found.");
            }

            if ($med['stock_quantity'] < $quantity) {
                throw new Exception("Insufficient stock for selected medication.");
            }

            $unit_price = $med['unit_price'];
            $subtotal = $unit_price * $quantity;
            $total_amount += $subtotal;

            // Insert sale item
            $stmt = $conn->prepare("
                INSERT INTO SaleItem (sale_id, medication_id, quantity, unit_price_at_sale)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiid", $sale_id, $medication_id, $quantity, $unit_price);
            $stmt->execute();
            $stmt->close();

            // Update stock
            $stmt = $conn->prepare("
                UPDATE Medication
                SET stock_quantity = stock_quantity - ?
                WHERE medication_id = ?
            ");
            $stmt->bind_param("ii", $quantity, $medication_id);
            $stmt->execute();
            $stmt->close();
        }

        // Update total
        $stmt = $conn->prepare("
            UPDATE Sale
            SET total_amount = ?
            WHERE sale_id = ?
        ");
        $stmt->bind_param("di", $total_amount, $sale_id);
        $stmt->execute();
        $stmt->close();

        mysqli_commit($conn);

        logActivity('Add Sale', 'Sale ID: ' . $sale_id . ' Total: ' . formatCurrency($total_amount));
        $_SESSION['message'] = 'Sale completed successfully! Total: ' . formatCurrency($total_amount);
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {

        mysqli_rollback($conn);

        $_SESSION['message'] = 'Error processing sale: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: sales.php');
    exit();
}


/* =========================
   GET SALES
========================= */
$sales = mysqli_query($conn, "
    SELECT s.*, c.first_name, c.last_name,
           e.first_name AS emp_first, e.last_name AS emp_last
    FROM Sale s
    LEFT JOIN Customer c ON s.customer_id = c.customer_id
    JOIN Employee e ON s.employee_id = e.employee_id
    ORDER BY s.sale_date DESC
");


/* =========================
   GET MEDICATIONS
========================= */
$medications = mysqli_query($conn, "
    SELECT medication_id, name, unit_price, stock_quantity
    FROM Medication
    WHERE stock_quantity > 0
    ORDER BY name
");


/* =========================
   GET CUSTOMERS
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
    <h2><i class="fas fa-shopping-cart"></i> Sales</h2>
    <?php if (canEditSales()): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal">
            <i class="fas fa-plus"></i> New Sale
        </button>
    <?php endif; ?>
</div>

<?php
if (isset($_SESSION['message'])) {
    echo showMessage($_SESSION['message_type'], $_SESSION['message']);
    unset($_SESSION['message'], $_SESSION['message_type']);
}
?>

<!-- =========================
     SALES TABLE
========================= -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">

                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Customer</th>
                        <th>Employee</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = mysqli_fetch_assoc($sales)): ?>
                    <tr>
                        <td>#<?= $row['sale_id'] ?></td>

                        <td>
                            <?= $row['customer_id']
                                ? $row['first_name'] . ' ' . $row['last_name']
                                : '<span class="text-muted">Walk-in</span>' ?>
                        </td>

                        <td>
                            <?= $row['emp_first'] . ' ' . $row['emp_last'] ?>
                        </td>

                        <td>
                            <strong><?= formatCurrency($row['total_amount']) ?></strong>
                        </td>

                        <td>
                            <?= date('M d, Y H:i', strtotime($row['sale_date'])) ?>
                        </td>

                        <td>
                            <a href="sale_details.php?id=<?= $row['sale_id'] ?>"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>
</div>

<!-- =========================
     ADD SALE MODAL (UNCHANGED UI)
========================= -->
<div class="modal fade" id="addSaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST" id="saleForm">

                <div class="modal-header">
                    <h5 class="modal-title">New Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Customer (Optional)</label>
                        <select class="form-control" name="customer_id">
                            <option value="">Walk-in Customer</option>
                            <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                                <option value="<?= $customer['customer_id'] ?>">
                                    <?= $customer['first_name'] . ' ' . $customer['last_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Items</label>

                        <div id="itemsContainer">

                            <div class="row item-row mb-2">

                                <div class="col-md-6">
                                    <select class="form-control medication-select"
                                            name="items[0][medication_id]" required>

                                        <option value="">Select Medication</option>

                                        <?php
                                        // reset pointer for reuse safety
                                        mysqli_data_seek($medications, 0);
                                        while ($med = mysqli_fetch_assoc($medications)):
                                        ?>
                                            <option value="<?= $med['medication_id'] ?>"
                                                    data-price="<?= $med['unit_price'] ?>"
                                                    data-stock="<?= $med['stock_quantity'] ?>">
                                                <?= $med['name'] . ' - ' . formatCurrency($med['unit_price']) . ' (Stock: ' . $med['stock_quantity'] . ')' ?>
                                            </option>
                                        <?php endwhile; ?>

                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <input type="number"
                                           class="form-control quantity-input"
                                           name="items[0][quantity]"
                                           placeholder="Qty"
                                           min="1"
                                           required>
                                </div>

                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove-item">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                            </div>

                        </div>

                        <button type="button" class="btn btn-sm btn-success mt-2" id="addItemBtn">
                            <i class="fas fa-plus"></i> Add Item
                        </button>

                    </div>

                    <div class="alert alert-info">
                        <strong>Total: </strong>
                        <span id="saleTotal">GH₵0.00</span>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" name="add_sale" class="btn btn-primary">
                        Complete Sale
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- =========================
     JS (UNCHANGED LOGIC)
========================= -->
<script>
let itemCount = 1;

document.getElementById('addItemBtn').addEventListener('click', function () {

    const container = document.getElementById('itemsContainer');
    const newRow = container.children[0].cloneNode(true);

    const select = newRow.querySelector('.medication-select');
    const input = newRow.querySelector('.quantity-input');

    select.value = '';
    input.value = '';

    select.name = `items[${itemCount}][medication_id]`;
    input.name = `items[${itemCount}][quantity]`;

    newRow.querySelector('.remove-item').addEventListener('click', function () {
        if (container.children.length > 1) {
            newRow.remove();
            updateTotal();
        }
    });

    container.appendChild(newRow);
    itemCount++;
});

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('medication-select') ||
        e.target.classList.contains('quantity-input')) {
        updateTotal();
    }
});

function updateTotal() {
    let total = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const select = row.querySelector('.medication-select');
        const qty = row.querySelector('.quantity-input');

        if (select.value && qty.value) {
            const price = parseFloat(select.options[select.selectedIndex].dataset.price);
            total += price * parseInt(qty.value);
        }
    });

    document.getElementById('saleTotal').textContent = 'GH₵' + total.toFixed(2);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>