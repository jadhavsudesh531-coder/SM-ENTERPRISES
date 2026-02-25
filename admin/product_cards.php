<?php
include('header.php');  

$showLowStockPopup = !empty($_SESSION['show_low_stock_popup']);
$lowStockItems = $_SESSION['low_stock_items'] ?? [];
unset($_SESSION['show_low_stock_popup']);
?>

<!-- Delivery Agents Dashboard Section -->
<div class="container-fluid" style="margin-top: 20px; margin-bottom: 30px;">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4" style="font-weight: 600; color: #000000;">
                <i class="bi bi-truck me-2"></i>Delivery Operations Overview
            </h2>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <?php
        include('conn.php');
        if (!($con instanceof mysqli)) {
            $con = null;
        }
        /** @var mysqli|null $con */
        if (!$con) {
            echo '<div class="col-12"><div class="alert alert-danger">Database connection not available.</div></div>';
            include('footer.php');
            return;
        }
        
        // Get total delivery agents
        $totalAgentsQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM delivery_agents");
        $totalAgents = mysqli_fetch_assoc($totalAgentsQuery)['total'];
        
        // Get active agents
        $activeAgentsQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM delivery_agents WHERE is_active = 1");
        $activeAgents = mysqli_fetch_assoc($activeAgentsQuery)['total'];
        
        // Get inactive agents
        $inactiveAgentsQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM delivery_agents WHERE is_active = 0");
        $inactiveAgents = mysqli_fetch_assoc($inactiveAgentsQuery)['total'];
        
        // Get pending deliveries
        $pendingDeliveriesQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM purchase WHERE status = 'pending' AND delivery_agent_id IS NOT NULL");
        $pendingDeliveries = mysqli_fetch_assoc($pendingDeliveriesQuery)['total'];
        
        // Get in-transit deliveries
        $inTransitQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM purchase WHERE status = 'assigned' AND delivery_agent_id IS NOT NULL");
        $inTransitDeliveries = mysqli_fetch_assoc($inTransitQuery)['total'];
        
        // Get completed deliveries
        $completedDeliveriesQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM purchase WHERE status = 'delivered'");
        $completedDeliveries = mysqli_fetch_assoc($completedDeliveriesQuery)['total'];
        ?>
        
        <!-- Total Agents Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #006ba6 0%, #0084d1 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 0.85rem; font-weight: 500;">Total Agents</h6>
                            <h2 class="mb-0 mt-2" style="font-weight: 700;"><?php echo $totalAgents; ?></h2>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <a href="delivery_agents.php" class="btn btn-sm text-white w-100" style="background: rgba(255,255,255,0.2); border: none; padding: 8px 16px; border-radius: 6px;">
                            <i class="bi bi-arrow-right me-1"></i>Manage Agents
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Active Agents Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 0.85rem; font-weight: 500;">Active Agents</h6>
                            <h2 class="mb-0 mt-2" style="font-weight: 700;"><?php echo $activeAgents; ?></h2>
                            <small style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">Ready for delivery</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Deliveries Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 0.85rem; font-weight: 500;">Pending Deliveries</h6>
                            <h2 class="mb-0 mt-2" style="font-weight: 700;"><?php echo $pendingDeliveries; ?></h2>
                            <small style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">Awaiting assignment</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delivery Summary Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <h6 class="text-white-50 mb-3" style="font-size: 0.85rem; font-weight: 500;">Delivery Status</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size: 0.9rem;"><i class="bi bi-check-circle-fill text-success me-1"></i>Completed</span>
                        <strong style="font-size: 1.1rem;"><?php echo $completedDeliveries; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size: 0.9rem;"><i class="bi bi-truck text-info me-1"></i>In Transit</span>
                        <strong style="font-size: 1.1rem;"><?php echo $inTransitDeliveries; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size: 0.9rem;"><i class="bi bi-exclamation-circle text-warning me-1"></i>Pending</span>
                        <strong style="font-size: 1.1rem;"><?php echo $pendingDeliveries; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <hr class="my-4" style="border-top: 2px solid #e0e0e0;">
</div>
<!-- End Delivery Agents Dashboard Section -->

<!-- Product Management & Orders Section -->
<div class="container-fluid" style="margin-top: 20px; margin-bottom: 30px;">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4" style="font-weight: 600; color: #000000;">
                <i class="bi bi-speedometer2 me-2"></i>Operations Overview
            </h2>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <?php
        // Get total products
        $totalProductsQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM product");
        $totalProducts = mysqli_fetch_assoc($totalProductsQuery)['total'];
        
        // Get low stock products
        $lowStockQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM product WHERE pqty <= 5");
        $lowStockCount = mysqli_fetch_assoc($lowStockQuery)['total'];
        
        // Get total orders
        $totalOrdersQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM purchase");
        $totalOrders = mysqli_fetch_assoc($totalOrdersQuery)['total'];
        
        // Get pending orders
        $pendingOrdersQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM purchase WHERE status = 'pending'");
        $pendingOrders = mysqli_fetch_assoc($pendingOrdersQuery)['total'];
        
        // Get delivered orders
        $deliveredOrdersQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM purchase WHERE status = 'delivered'");
        $deliveredOrders = mysqli_fetch_assoc($deliveredOrdersQuery)['total'];
        
        // Get cancelled orders
        $cancelledOrdersQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM purchase WHERE status = 'cancelled'");
        $cancelledOrders = mysqli_fetch_assoc($cancelledOrdersQuery)['total'];
        ?>
        
        <!-- Total Products Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 0.85rem; font-weight: 500;">Total Products</h6>
                            <h2 class="mb-0 mt-2" style="font-weight: 700;"><?php echo $totalProducts; ?></h2>
                            <?php if($lowStockCount > 0): ?>
                            <small style="color: rgba(255,255,255,0.8); font-size: 0.8rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo $lowStockCount; ?> Low Stock Items</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <a href="view_product.php" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.2); border: none; padding: 8px 16px; border-radius: 6px;">
                            <i class="bi bi-eye me-1"></i>View All
                        </a>
                        <a href="add_product.php" class="btn btn-sm text-white" style="background: rgba(255,255,255,0.2); border: none; padding: 8px 16px; border-radius: 6px;">
                            <i class="bi bi-plus-circle me-1"></i>Add New
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Total Orders Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 0.85rem; font-weight: 500;">Total Orders</h6>
                            <h2 class="mb-0 mt-2" style="font-weight: 700;"><?php echo $totalOrders; ?></h2>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <a href="orders.php" class="btn btn-sm text-white w-100" style="background: rgba(255,255,255,0.2); border: none; padding: 8px 16px; border-radius: 6px;">
                            <i class="bi bi-list-check me-1"></i>Manage Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Orders Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <div class="mb-3">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 0.85rem; font-weight: 500;">Pending Orders</h6>
                            <h2 class="mb-0 mt-2" style="font-weight: 700;"><?php echo $pendingOrders; ?></h2>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <a href="orders.php?status=pending" class="btn btn-sm text-white w-100" style="background: rgba(255,255,255,0.2); border: none; padding: 8px 16px; border-radius: 6px;">
                            <i class="bi bi-hourglass-split me-1"></i>View Pending
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Summary Card -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); color: white; border-radius: 12px; overflow: hidden; min-height: 185px;">
                <div class="card-body d-flex flex-column">
                    <h6 class="text-white-50 mb-3" style="font-size: 0.85rem; font-weight: 500;">Order Status</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size: 0.9rem;"><i class="bi bi-check-circle-fill text-success me-1"></i>Delivered</span>
                        <strong style="font-size: 1.1rem;"><?php echo $deliveredOrders; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size: 0.9rem;"><i class="bi bi-hourglass-split text-warning me-1"></i>Pending</span>
                        <strong style="font-size: 1.1rem;"><?php echo $pendingOrders; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size: 0.9rem;"><i class="bi bi-x-circle-fill text-danger me-1"></i>Cancelled</span>
                        <strong style="font-size: 1.1rem;"><?php echo $cancelledOrders; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <hr class="my-4" style="border-top: 2px solid #e0e0e0;">
</div>
<!-- End Product Management & Orders Section -->

<?php

$verifiedCustomizationCount = 0;
$verifiedCustomizationAdvance = 0.0;
$pendingScreenshotCount = 0;

$hasCustomizationTableRes = mysqli_query($con, "SHOW TABLES LIKE 'customization'");
if ($hasCustomizationTableRes && mysqli_num_rows($hasCustomizationTableRes) > 0) {
    $hasPaymentVerifiedRes = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE 'payment_verified'");
    $hasUnitPriceRes = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE 'customization_unit_price'");
    $hasQtyRes = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE 'pqty'");

    if ($hasPaymentVerifiedRes && mysqli_num_rows($hasPaymentVerifiedRes) > 0 && $hasUnitPriceRes && mysqli_num_rows($hasUnitPriceRes) > 0 && $hasQtyRes && mysqli_num_rows($hasQtyRes) > 0) {
        $verifiedStatsSql = "SELECT COUNT(*) AS verified_count, SUM((COALESCE(NULLIF(customization_unit_price, 0), 5000) * COALESCE(NULLIF(pqty, 0), 1)) * 0.5) AS verified_advance FROM customization WHERE payment_verified = 1";
        $verifiedStatsRes = mysqli_query($con, $verifiedStatsSql);
        if ($verifiedStatsRes) {
            $verifiedStatsRow = mysqli_fetch_assoc($verifiedStatsRes);
            $verifiedCustomizationCount = (int)($verifiedStatsRow['verified_count'] ?? 0);
            $verifiedCustomizationAdvance = (float)($verifiedStatsRow['verified_advance'] ?? 0);
        }
    }
}

$hasPaymentScreenshotRes = mysqli_query($con, "SHOW COLUMNS FROM myorder LIKE 'payment_screenshot'");
if ($hasPaymentScreenshotRes && mysqli_num_rows($hasPaymentScreenshotRes) > 0) {
    $pendingProductSql = "SELECT COUNT(*) AS cnt FROM myorder WHERE payment_txn_id IS NOT NULL AND payment_txn_id <> '' AND payment_screenshot IS NOT NULL AND payment_screenshot <> '' AND (payment_verified = 0 OR payment_verified IS NULL)";
    $pendingProductRes = mysqli_query($con, $pendingProductSql);
    if ($pendingProductRes) {
        $pendingProductRow = mysqli_fetch_assoc($pendingProductRes);
        $pendingScreenshotCount += (int)($pendingProductRow['cnt'] ?? 0);
    }
}

$hasCustomScreenshotRes = mysqli_query($con, "SHOW COLUMNS FROM customization LIKE 'payment_screenshot'");
if ($hasCustomScreenshotRes && mysqli_num_rows($hasCustomScreenshotRes) > 0) {
    $pendingCustomSql = "SELECT COUNT(*) AS cnt FROM customization WHERE payment_txn_id IS NOT NULL AND payment_txn_id <> '' AND payment_screenshot IS NOT NULL AND payment_screenshot <> '' AND (payment_verified = 0 OR payment_verified IS NULL)";
    $pendingCustomRes = mysqli_query($con, $pendingCustomSql);
    if ($pendingCustomRes) {
        $pendingCustomRow = mysqli_fetch_assoc($pendingCustomRes);
        $pendingScreenshotCount += (int)($pendingCustomRow['cnt'] ?? 0);
    }
}

if ($pendingScreenshotCount > 0) {
    echo '<div class="col-12 mb-4">';
    echo '  <div class="alert alert-warning border-warning shadow-sm d-flex justify-content-between align-items-center">';
    echo '    <div>';
    echo '      <h5 class="mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Pending Payment Verifications</h5>';
    echo '      <div class="text-dark">' . $pendingScreenshotCount . ' payment screenshot(s) awaiting admin verification</div>';
    echo '    </div>';
    echo '    <div>';
    echo '      <a href="product_advance_payments.php?payment_filter=awaiting_verification" class="btn btn-warning me-2">Product Payments</a>';
    echo '      <a href="customization_advance_payments.php?payment_filter=awaiting_verification" class="btn btn-warning">Customization Payments</a>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}

echo '<div class="col-12 mb-4">';
echo '  <div class="card border-success shadow-sm">';
echo '    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">';
echo '      <div>';
echo '        <h5 class="card-title mb-1"><i class="bi bi-patch-check-fill text-success me-2"></i>Verified Customization Payments</h5>';
echo '        <div class="text-muted">'.$verifiedCustomizationCount.' verified request(s)</div>';
echo '      </div>';
echo '      <div class="text-end">';
echo '        <div class="fw-bold text-success" style="font-size:1.2rem;">₹'.number_format($verifiedCustomizationAdvance, 2).'</div>';
echo '        <small class="text-muted">Total verified 50% advance</small>';
echo '      </div>';
echo '      <div>';
echo '        <a href="customization_advance_payments.php?payment_filter=verified" class="btn btn-success btn-sm">View Verified</a>';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div class="container-fluid">';
echo '<div class="row">';
echo '<div class="col-12 mb-3">';
echo '<h4 style="font-weight: 600; color: #000000;"><i class="bi bi-grid-3x3-gap-fill me-2"></i>All Products</h4>';
echo '</div>';
echo '</div>';
echo '<div class="row">';

$sql="SELECT * FROM `product`";
$result=mysqli_query($con,$sql);
while($row=mysqli_fetch_assoc($result)){
    echo '<div class="col-md-3 mb-4">';
    echo '<div class="card product-card">';
    echo '<img src="../productimg/'.$row['pimg'].'" class="card-img-top" alt="Product Image" style="height: 300px; object-fit: cover;">';
    echo '<div class="card-body">';
    echo '<h5 class="card-title">'.htmlspecialchars($row['pname']).'</h5>';
    echo '<p class="muted-small mb-2">'.htmlspecialchars($row['pitem']).' • '.htmlspecialchars($row['pcompany']).'</p>';
    echo '<p class="card-text"><strong>₹'.htmlspecialchars(number_format($row['pprice'],2)).'</strong></p>';
    echo '<p class="card-text">Qty: '.htmlspecialchars($row['pqty']).'</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>'; // Close row
echo '</div>'; // Close container-fluid
?>
<?php if ($showLowStockPopup && !empty($lowStockItems)) { ?>
<div class="modal fade" id="lowStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Low Stock Reminder</h5>
            </div>
            <div class="modal-body">
                <p class="mb-2">Some products have quantity less than or equal to 5. Please restore stock.</p>
                <ul class="mb-0">
                    <?php foreach ($lowStockItems as $stockItem) { ?>
                        <li><?php echo htmlspecialchars($stockItem['pname']); ?> (Qty: <?php echo htmlspecialchars($stockItem['pqty']); ?>)</li>
                    <?php } ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var lowStockModalEl = document.getElementById('lowStockModal');
    if (lowStockModalEl) {
        var lowStockModal = bootstrap.Modal.getOrCreateInstance(lowStockModalEl, {
            backdrop: false,
            keyboard: true
        });
        lowStockModal.show();
    }
});
</script>
<?php } ?>
<?php
include('footer.php');  
?>