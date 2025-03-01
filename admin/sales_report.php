<?php
require_once '../config.php';
check_auth();
$page_title = 'Sales Report';
$_SESSION['active_menu'] = 'sales_report'; // Make sure this matches the navbar menu ID

include 'header.php';
include 'navbar.php';

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get sales summary
$summary_query = "SELECT 
    COUNT(DISTINCT b.id) as total_bills,
    COUNT(DISTINCT b.customer_id) as total_customers,
    COALESCE(SUM(b.amount), 0) as total_amount,
    COALESCE(SUM(CASE WHEN b.status = 'paid' THEN b.amount ELSE 0 END), 0) as paid_amount,
    COALESCE(SUM(CASE WHEN b.status = 'unpaid' THEN b.amount ELSE 0 END), 0) as unpaid_amount,
    COALESCE(SUM(CASE WHEN b.status = 'overdue' THEN b.amount ELSE 0 END), 0) as overdue_amount
    FROM billing b
    WHERE b.created_at BETWEEN ? AND ?";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Get sales by plan
$plans_query = "SELECT 
    p.name as plan_name,
    COUNT(b.id) as bill_count,
    COALESCE(SUM(b.amount), 0) as total_amount
    FROM billing b
    JOIN customers c ON b.customer_id = c.id
    JOIN plans p ON c.plan_id = p.id
    WHERE b.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_amount DESC";

$stmt = $conn->prepare($plans_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$plans_data = $stmt->get_result();

// Get daily sales data for chart
$daily_query = "SELECT 
    DATE(created_at) as date,
    COUNT(id) as bill_count,
    SUM(amount) as daily_total
    FROM billing
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date";

$stmt = $conn->prepare($daily_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_data = $stmt->get_result();

// Prepare chart data
$dates = [];
$amounts = [];
while ($row = $daily_data->fetch_assoc()) {
    $dates[] = date('M d', strtotime($row['date']));
    $amounts[] = $row['daily_total'];
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Sales Report</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToPDF()">
                        <i class="bx bxs-file-pdf"></i> Export PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()">
                        <i class="bx bxs-file-export"></i> Export Excel
                    </button>
                </div>
                <form class="d-flex">
                    <input type="date" class="form-control form-control-sm me-2" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                    <input type="date" class="form-control form-control-sm me-2" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                    <button class="btn btn-sm btn-primary" type="submit">Filter</button>
                </form>
            </div>
        </div>

        <!-- Sales Summary Cards -->
        <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
            <!-- Total Sales -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Sales</h6>
                                <h3 class="card-title mb-0">₱<?php echo number_format($summary['total_amount'], 2); ?></h3>
                                <small class="text-muted"><?php echo $summary['total_bills']; ?> bills</small>
                            </div>
                            <div class="icon-shape bg-light text-primary rounded p-3">
                                <i class="bx bx-money"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paid Amount -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Paid Amount</h6>
                                <h3 class="card-title mb-0 text-success">₱<?php echo number_format($summary['paid_amount'], 2); ?></h3>
                                <small class="text-muted"><?php 
                                    echo $summary['total_amount'] > 0 ? round(($summary['paid_amount'] / $summary['total_amount']) * 100, 1) : 0; 
                                ?>% of total</small>
                            </div>
                            <div class="icon-shape bg-light text-success rounded p-3">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unpaid Amount -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Unpaid Amount</h6>
                                <h3 class="card-title mb-0 text-warning">₱<?php echo number_format($summary['unpaid_amount'], 2); ?></h3>
                                <small class="text-muted"><?php 
                                    echo $summary['total_amount'] > 0 ? round(($summary['unpaid_amount'] / $summary['total_amount']) * 100, 1) : 0; 
                                ?>% of total</small>
                            </div>
                            <div class="icon-shape bg-light text-warning rounded p-3">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Amount -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Overdue Amount</h6>
                                <h3 class="card-title mb-0 text-danger">₱<?php echo number_format($summary['overdue_amount'], 2); ?></h3>
                                <small class="text-muted"><?php 
                                    echo $summary['total_amount'] > 0 ? round(($summary['overdue_amount'] / $summary['total_amount']) * 100, 1) : 0; 
                                ?>% of total</small>
                            </div>
                            <div class="icon-shape bg-light text-danger rounded p-3">
                                <i class="bx bx-error"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Sales Chart -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Daily Sales Trend</h5>
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sales by Plan -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Sales by Plan</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Bills</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($plan = $plans_data->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                                        <td><?php echo $plan['bill_count']; ?></td>
                                        <td>₱<?php echo number_format($plan['total_amount'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Recent Sales</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice ID</th>
                                <th>Customer</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_query = "SELECT b.*, c.name as customer_name, p.name as plan_name 
                                           FROM billing b
                                           JOIN customers c ON b.customer_id = c.id
                                           JOIN plans p ON c.plan_id = p.id
                                           WHERE b.created_at BETWEEN ? AND ?
                                           ORDER BY b.created_at DESC
                                           LIMIT 10";
                            $stmt = $conn->prepare($recent_query);
                            $stmt->bind_param("ss", $start_date, $end_date);
                            $stmt->execute();
                            $recent_sales = $stmt->get_result();
                            
                            while ($sale = $recent_sales->fetch_assoc()):
                                $statusClass = '';
                                switch ($sale['status']) {
                                    case 'paid':
                                        $statusClass = 'success';
                                        break;
                                    case 'unpaid':
                                        $statusClass = 'warning';
                                        break;
                                    case 'overdue':
                                        $statusClass = 'danger';
                                        break;
                                }
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($sale['created_at'])); ?></td>
                                <td>
                                    <a href="billing_view.php?id=<?php echo $sale['id']; ?>">
                                        <?php echo htmlspecialchars($sale['invoiceid']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($sale['plan_name']); ?></td>
                                <td>₱<?php echo number_format($sale['amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($sale['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Daily Sales',
            data: <?php echo json_encode($amounts); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1,
            fill: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Daily Sales Trend'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value, index, values) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Export functions
function exportToPDF() {
    window.location.href = `export_sales.php?type=pdf&start_date=${document.getElementById('start_date').value}&end_date=${document.getElementById('end_date').value}`;
}

function exportToExcel() {
    window.location.href = `export_sales.php?type=excel&start_date=${document.getElementById('start_date').value}&end_date=${document.getElementById('end_date').value}`;
}

// Date filter validation
document.querySelector('form').addEventListener('submit', function(e) {
    const startDate = new Date(document.getElementById('start_date').value);
    const endDate = new Date(document.getElementById('end_date').value);
    
    if (startDate > endDate) {
        e.preventDefault();
        alert('Start date cannot be later than end date');
    }
});
</script>

<?php include 'footer.php'; ?>
