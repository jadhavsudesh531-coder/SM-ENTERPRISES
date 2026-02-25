<?php
include('conn.php');

function ensureRevenueColumns($con)
{
    $checkDelivered = mysqli_query($con, "SHOW COLUMNS FROM purchase LIKE 'delivered_at'");
    if (!$checkDelivered || mysqli_num_rows($checkDelivered) === 0) {
        mysqli_query($con, "ALTER TABLE purchase ADD COLUMN delivered_at DATETIME NULL");
    }

    $checkCost = mysqli_query($con, "SHOW COLUMNS FROM product LIKE 'cost_price'");
    if (!$checkCost || mysqli_num_rows($checkCost) === 0) {
        mysqli_query($con, "ALTER TABLE product ADD COLUMN cost_price DECIMAL(12,2) NULL AFTER pprice");
        mysqli_query($con, "UPDATE product SET cost_price = pprice WHERE cost_price IS NULL");
    }
}

function getPeriodSummary($con, $whereSql)
{
    $sql = "SELECT
                COUNT(*) AS orders_count,
                COALESCE(SUM(purchase.pprice * purchase.pqty), 0) AS revenue,
                COALESCE(SUM((purchase.pprice - COALESCE(product.cost_price, purchase.pprice)) * purchase.pqty), 0) AS profit
            FROM purchase
            LEFT JOIN product ON purchase.prod_id = product.pid
            WHERE purchase.status='delivered' AND " . $whereSql;

    $res = mysqli_query($con, $sql);
    return ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : ['orders_count' => 0, 'revenue' => 0, 'profit' => 0];
}

ensureRevenueColumns($con);

$daily = getPeriodSummary($con, "DATE(COALESCE(purchase.delivered_at, purchase.pdate)) = CURDATE()");
$weekly = getPeriodSummary($con, "YEARWEEK(COALESCE(purchase.delivered_at, purchase.pdate), 1) = YEARWEEK(CURDATE(), 1)");
$monthly = getPeriodSummary($con, "YEAR(COALESCE(purchase.delivered_at, purchase.pdate)) = YEAR(CURDATE()) AND MONTH(COALESCE(purchase.delivered_at, purchase.pdate)) = MONTH(CURDATE())");
$allTime = getPeriodSummary($con, "1=1");

$avgOrderValue = ((float)$allTime['orders_count'] > 0)
    ? ((float)$allTime['revenue'] / (float)$allTime['orders_count'])
    : 0;

$monthlyTrend = [];
$maxRevenue = 0;
for ($i = 5; $i >= 0; $i--) {
    $start = date('Y-m-01', strtotime("-$i month"));
    $end = date('Y-m-t', strtotime("-$i month"));

    $trendSql = "SELECT
                    COALESCE(SUM(purchase.pprice * purchase.pqty), 0) AS revenue,
                    COALESCE(SUM((purchase.pprice - COALESCE(product.cost_price, purchase.pprice)) * purchase.pqty), 0) AS profit
                 FROM purchase
                 LEFT JOIN product ON purchase.prod_id = product.pid
                 WHERE purchase.status='delivered'
                   AND DATE(COALESCE(purchase.delivered_at, purchase.pdate)) BETWEEN '$start' AND '$end'";
    $trendRes = mysqli_query($con, $trendSql);
    $trendRow = ($trendRes && mysqli_num_rows($trendRes) > 0) ? mysqli_fetch_assoc($trendRes) : ['revenue' => 0, 'profit' => 0];

    $revenue = (float)($trendRow['revenue'] ?? 0);
    $profit = (float)($trendRow['profit'] ?? 0);
    if ($revenue > $maxRevenue) {
        $maxRevenue = $revenue;
    }

    $monthlyTrend[] = [
        'label' => date('M Y', strtotime($start)),
        'revenue' => $revenue,
        'profit' => $profit
    ];
}

$recentDays = [];
$dailySql = "SELECT
                DATE(COALESCE(purchase.delivered_at, purchase.pdate)) AS day_date,
                COUNT(*) AS orders_count,
                COALESCE(SUM(purchase.pprice * purchase.pqty), 0) AS revenue,
                COALESCE(SUM((purchase.pprice - COALESCE(product.cost_price, purchase.pprice)) * purchase.pqty), 0) AS profit
             FROM purchase
             LEFT JOIN product ON purchase.prod_id = product.pid
             WHERE purchase.status='delivered'
               AND DATE(COALESCE(purchase.delivered_at, purchase.pdate)) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(COALESCE(purchase.delivered_at, purchase.pdate))
             ORDER BY day_date DESC";
$dailyRes = mysqli_query($con, $dailySql);
if ($dailyRes) {
    while ($row = mysqli_fetch_assoc($dailyRes)) {
        $recentDays[] = $row;
    }
}

$topProducts = [];
$topSql = "SELECT
            COALESCE(product.pname, purchase.pname) AS pname,
            SUM(purchase.pqty) AS total_qty,
            COALESCE(SUM(purchase.pprice * purchase.pqty), 0) AS total_revenue,
            COALESCE(SUM((purchase.pprice - COALESCE(product.cost_price, purchase.pprice)) * purchase.pqty), 0) AS total_profit
          FROM purchase
          LEFT JOIN product ON purchase.prod_id = product.pid
          WHERE purchase.status='delivered'
          GROUP BY COALESCE(product.pname, purchase.pname)
          ORDER BY total_revenue DESC
          LIMIT 6";
$topRes = mysqli_query($con, $topSql);
if ($topRes) {
    while ($row = mysqli_fetch_assoc($topRes)) {
        $topProducts[] = $row;
    }
}

include('header.php');
?>

<style>
    :root {
        --brand: #5a67d8;
        --brand2: #7f9cf5;
        --profit: #198754;
        --danger: #dc3545;
    }

    body { background: #f4f7fb; }
    .rev-wrap { margin-top: 10px; padding: 0 12px; }

    .hero-card {
        border: 0;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--brand) 0%, #764ba2 100%);
        color: #fff;
        box-shadow: 0 10px 30px rgba(90, 103, 216, 0.25);
    }

    .kpi-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(0,0,0,0.06);
        transition: transform .2s ease;
    }
    .kpi-card:hover { transform: translateY(-4px); }

    .kpi-title { font-size: .82rem; text-transform: uppercase; color: #6c757d; letter-spacing: .8px; }
    .kpi-value { font-weight: 700; font-size: 1.55rem; margin-bottom: 0; }

    .panel-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 10px 28px rgba(0,0,0,0.05);
    }

    .bar-wrap {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
        align-items: end;
        min-height: 220px;
        padding-top: 10px;
    }
    .bar-col { text-align: center; }
    .bar-revenue {
        width: 22px;
        margin: 0 auto;
        border-radius: 10px;
        background: linear-gradient(180deg, #60a5fa, #2563eb);
    }
    .bar-profit {
        width: 22px;
        margin: 6px auto 0;
        border-radius: 10px;
        background: linear-gradient(180deg, #34d399, #059669);
    }

    .legend-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }

    .table td, .table th { vertical-align: middle; }
</style>

<div class="container-fluid rev-wrap pb-4">
    <div class="card hero-card p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="fw-bold mb-1">Revenue & Profit Dashboard</h3>
                <p class="mb-0 opacity-75">Track daily, weekly, monthly sales performance and profit.</p>
            </div>
            <div class="text-end">
                <div class="small opacity-75">All-time Revenue</div>
                <h4 class="fw-bold mb-0">₹<?php echo number_format((float)$allTime['revenue'], 2); ?></h4>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-md-4">
            <div class="card kpi-card p-3">
                <div class="kpi-title">Daily Sales</div>
                <p class="kpi-value text-primary">₹<?php echo number_format((float)$daily['revenue'], 2); ?></p>
                <small class="text-muted">Orders: <?php echo (int)$daily['orders_count']; ?> | Profit: ₹<?php echo number_format((float)$daily['profit'], 2); ?></small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card p-3">
                <div class="kpi-title">Weekly Sales</div>
                <p class="kpi-value text-primary">₹<?php echo number_format((float)$weekly['revenue'], 2); ?></p>
                <small class="text-muted">Orders: <?php echo (int)$weekly['orders_count']; ?> | Profit: ₹<?php echo number_format((float)$weekly['profit'], 2); ?></small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card p-3">
                <div class="kpi-title">Monthly Sales</div>
                <p class="kpi-value text-primary">₹<?php echo number_format((float)$monthly['revenue'], 2); ?></p>
                <small class="text-muted">Orders: <?php echo (int)$monthly['orders_count']; ?> | Profit: ₹<?php echo number_format((float)$monthly['profit'], 2); ?></small>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="card kpi-card p-3">
                <div class="kpi-title">All-Time Delivered Orders</div>
                <p class="kpi-value"><?php echo (int)$allTime['orders_count']; ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card p-3">
                <div class="kpi-title">All-Time Profit</div>
                <p class="kpi-value" style="color: var(--profit);">₹<?php echo number_format((float)$allTime['profit'], 2); ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card p-3">
                <div class="kpi-title">Average Order Value</div>
                <p class="kpi-value text-dark">₹<?php echo number_format($avgOrderValue, 2); ?></p>
            </div>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-lg-7">
            <div class="card panel-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">6-Month Revenue vs Profit</h5>
                    <div class="small text-muted">
                        <span class="legend-dot" style="background:#2563eb;"></span>Revenue
                        <span class="ms-3 legend-dot" style="background:#059669;"></span>Profit
                    </div>
                </div>

                <div class="bar-wrap">
                    <?php foreach ($monthlyTrend as $item): ?>
                        <?php
                            $rev = (float)$item['revenue'];
                            $pro = (float)$item['profit'];
                            $revHeight = $maxRevenue > 0 ? max(8, (int)(($rev / $maxRevenue) * 160)) : 8;
                            $proHeight = $maxRevenue > 0 ? max(8, (int)(($pro / $maxRevenue) * 160)) : 8;
                        ?>
                        <div class="bar-col">
                            <div class="small text-muted mb-1">₹<?php echo number_format($rev, 0); ?></div>
                            <div class="bar-revenue" style="height: <?php echo $revHeight; ?>px;"></div>
                            <div class="bar-profit" style="height: <?php echo $proHeight; ?>px;"></div>
                            <div class="small mt-2"><?php echo htmlspecialchars($item['label']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card panel-card p-3 h-100">
                <h5 class="mb-3">Last 7 Days Performance</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Orders</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentDays) === 0): ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">No delivered orders in last 7 days.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentDays as $day): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('d M, Y', strtotime($day['day_date']))); ?></td>
                                        <td class="text-end"><?php echo (int)$day['orders_count']; ?></td>
                                        <td class="text-end">₹<?php echo number_format((float)$day['revenue'], 2); ?></td>
                                        <td class="text-end">₹<?php echo number_format((float)$day['profit'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 mt-3">
            <div class="card panel-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Top Selling Products (Delivered)</h5>
                    <small class="text-muted">Profit = (Selling Price - Cost Price) × Quantity</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Units Sold</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($topProducts) === 0): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No delivered product sales yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($product['pname'] ?? 'N/A'); ?></td>
                                        <td class="text-end"><?php echo (int)$product['total_qty']; ?></td>
                                        <td class="text-end">₹<?php echo number_format((float)$product['total_revenue'], 2); ?></td>
                                        <td class="text-end">₹<?php echo number_format((float)$product['total_profit'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 mb-0 py-2">
                    <small>For accurate profit, keep product <strong>cost price</strong> updated in your product records. Existing products are initialized with cost price = selling price.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
