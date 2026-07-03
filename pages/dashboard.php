<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!isset($conn) || !$conn) {
    die('Database connection unavailable. Please check includes/config.php.');
}
// ==========================================
// Dashboard Statistics
// ==========================================
$stats = [];
// Total Customers
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM Customer");
$stats['customers'] = mysqli_fetch_assoc($result)['total'];
// Total Medications
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM Medication");
$stats['medications'] = mysqli_fetch_assoc($result)['total'];
// Today's Sales
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Sale
     WHERE DATE(sale_date) = CURDATE()"
);
$stats['sales_today'] = mysqli_fetch_assoc($result)['total'];
// Low Stock Items
$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Medication
     WHERE stock_quantity <= reorder_level"
);
$stats['low_stock'] = mysqli_fetch_assoc($result)['total'];
// ==========================================
// Recent Sales
// ==========================================
$recent_sales = mysqli_query(
    $conn,
    "SELECT
        s.*,
        c.first_name,
        c.last_name,
        e.first_name AS emp_first,
        e.last_name AS emp_last
    FROM Sale s
    JOIN Customer c
        ON s.customer_id = c.customer_id
    JOIN Employee e
        ON s.employee_id = e.employee_id
    ORDER BY s.sale_date DESC
    LIMIT 5"
);
$activities = canViewActivities() ? getRecentActivities(10) : [];
// Admin counts: number of employees and recent sales shown
$employee_count = 0;
$recent_sales_shown = 0;
if (isAdmin()) {
    $empRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM Employee");
    $employee_count = $empRes ? intval(mysqli_fetch_assoc($empRes)['total']) : 0;
    // $recent_sales is the result used for the Recent Sales table (limited to 5)
    $recent_sales_shown = is_resource($recent_sales) || $recent_sales instanceof mysqli_result ? mysqli_num_rows($recent_sales) : 0;
}

require_once __DIR__ . '/../includes/header.php';
?>
<!-- ==========================================
     Page Heading
=========================================== -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2>Dashboard</h2>
        <p class="text-muted">
            Welcome back,
            <strong>
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </strong>!
        </p>
    </div>
</div>
<!-- ==========================================
     Statistics Cards
=========================================== -->
<div class="row mb-4 stagger-parent">
    <div class="col-md-3 app-col">
        <div class="card tilt-card text-white bg-primary stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title stat-label">
                            Total Customers
                        </h6>
                        <h2 class="mb-0 stat-value" data-target="<?php echo (int)$stats['customers']; ?>">
                            <?php echo (int)$stats['customers']; ?>
                        </h2>
                    </div>
                    <i class="fas fa-users fa-3x stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 app-col">
        <div class="card tilt-card text-white bg-success stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title stat-label">Medications</h6>
                        <h2 class="mb-0 stat-value" data-target="<?php echo (int)$stats['medications']; ?>">
                            <?php echo (int)$stats['medications']; ?>
                        </h2>
                    </div>
                    <i class="fas fa-pills fa-3x stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 app-col">
        <div class="card tilt-card text-white bg-info stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title stat-label">Today's Sales</h6>
                        <h2 class="mb-0 stat-value" data-target="<?php echo (int)$stats['sales_today']; ?>">
                            <?php echo (int)$stats['sales_today']; ?>
                        </h2>
                    </div>
                    <i class="fas fa-shopping-cart fa-3x stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 app-col">
        <div class="card tilt-card text-white bg-warning stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title stat-label">Low Stock Items</h6>
                        <h2 class="mb-0 stat-value" data-target="<?php echo (int)$stats['low_stock']; ?>">
                            <?php echo (int)$stats['low_stock']; ?>
                        </h2>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x stat-icon"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ==========================================
     Recent Sales
=========================================== -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="fas fa-clock"></i>
                    Recent Sales
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Customer</th>
                            <th>Employee</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($recent_sales) > 0): ?>
                            <?php while ($sale = mysqli_fetch_assoc($recent_sales)): ?>
                                <tr>
                                    <td>
                                        #<?php echo $sale['sale_id']; ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $sale['first_name'] . ' ' . $sale['last_name']
                                        );
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $sale['emp_first'] . ' ' . $sale['emp_last']
                                        );
                                        ?>
                                    </td>
                                    <td>
                                        <?= formatCurrency($sale['total_amount']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo date(
                                            'M d, Y H:i',
                                            strtotime($sale['sale_date'])
                                        );
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No recent sales found.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (canViewActivities()): ?>
<div class="row mt-4" id="activityLog">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-list"></i> Recent Activity Log</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-secondary">Employees: <?= htmlspecialchars($employee_count) ?></span>
                <span class="badge bg-secondary">Recent Sales Shown: <?= htmlspecialchars($recent_sales_shown) ?></span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Employee</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($activities)): ?>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?= date('M d, Y H:i', strtotime($activity['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?></td>
                                        <td><?= htmlspecialchars($activity['role']) ?></td>
                                        <td><?= htmlspecialchars($activity['action']) ?></td>
                                        <td><?= htmlspecialchars($activity['details'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No recent activity available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>