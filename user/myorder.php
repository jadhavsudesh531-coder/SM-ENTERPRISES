<?php
define('page','myorder');
include('header.php');
include('../admin/conn.php');
// Ensure session username exists; redirect if not
$username = $_SESSION['username'] ?? null;
if (empty($username)) {
    header('Location: login.php');
    exit;
}
$username_safe = mysqli_real_escape_string($con, $username);

function userStatusMeta($rawStatus)
{
  $status = strtolower(trim((string)$rawStatus));

  if (in_array($status, ['assigned', 'out_for_delivery', 'out for delivery', 'shipped'], true)) {
    return ['label' => 'Out for Delivery', 'badge' => 'primary', 'icon' => 'truck'];
  }

  if (in_array($status, ['delivered', 'completed'], true)) {
    return ['label' => 'Delivered', 'badge' => 'success', 'icon' => 'check-circle'];
  }

  if (in_array($status, ['cancelled', 'canceled', 'rejected'], true)) {
    return ['label' => 'Cancelled', 'badge' => 'danger', 'icon' => 'x-circle'];
  }

  if (in_array($status, ['partial_paid'], true)) {
    return ['label' => '50% Paid - Pending', 'badge' => 'warning', 'icon' => 'hourglass-split'];
  }

  if (in_array($status, ['pending'], true)) {
    return ['label' => 'Awaiting Payment', 'badge' => 'info', 'icon' => 'credit-card'];
  }

  return ['label' => 'Order Placed', 'badge' => 'info', 'icon' => 'clock-history'];
}
?>

<style>
.order-tracker {
    position: relative;
    margin: 20px 0;
}
.order-timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    padding: 20px 0;
}
.timeline-step {
    flex: 1;
    text-align: center;
    position: relative;
}
.timeline-step::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 3px;
    background: #e0e0e0;
    top: 15px;
    left: 50%;
    z-index: 0;
}
.timeline-step:last-child::before {
    display: none;
}
.timeline-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e0e0e0;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}
.timeline-step.active .timeline-icon {
    background: #28a745;
    color: white;
    transform: scale(1.2);
}
.search-bar-container {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.order-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}
.order-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateX(5px);
}
.auto-refresh-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 10px 15px;
    border-radius: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    font-size: 0.85rem;
    display: none;
    z-index: 1000;
}
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
</style>
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
</div>

<div class="auto-refresh-indicator" id="autoRefreshIndicator">
    <i class="bi bi-arrow-clockwise me-2"></i>Auto-refresh in <span id="refreshTimer">30</span>s
</div>

<div class="container-fluid px-2 mt-1">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0"><i class="bi bi-box-seam me-2"></i>My Orders</h2>
      <button class="btn btn-outline-primary btn-sm" onclick="toggleAutoRefresh()">
          <i class="bi bi-arrow-clockwise me-1"></i><span id="refreshBtnText">Enable Auto-Refresh</span>
      </button>
  </div>
  
  <?php if (!empty($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <!-- Search Bar -->
  <div class="search-bar-container">
      <div class="row g-2">
          <div class="col-md-8">
              <input type="text" id="orderSearch" class="form-control" placeholder="🔍 Search by Order ID, Product Name, or Status..." onkeyup="filterOrders()">
          </div>
          <div class="col-md-4">
              <select id="statusFilter" class="form-select" onchange="filterOrders()">
                  <option value="">All Statuses</option>
                  <option value="pending">Order Placed</option>
                  <option value="shipping">Out for Delivery</option>
                  <option value="delivered">Delivered</option>
                  <option value="cancelled">Cancelled</option>
              </select>
          </div>
      </div>
  </div>
  
  <ul class="nav nav-tabs mb-3" id="ordersTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
          <i class="bi bi-hourglass-split me-1"></i>Pending Orders <span class="badge bg-primary" id="pendingCount">0</span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
          <i class="bi bi-clock-history me-1"></i>Order History <span class="badge bg-secondary" id="historyCount">0</span>
      </button>
    </li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="pending" role="tabpanel">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-dark">
            <tr>
              <th>Order ID</th>
              <th>Product</th>
              <th>Name</th>
              <th>Email</th>
              <th>Qty</th>
              <th>Price</th>
              <th>Order Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT m.*, p.status AS purchase_status, p.delivery_agent_id, p.delivered_at AS purchase_delivered_at, p.canceled_at AS purchase_canceled_at, c.status AS customization_status, c.customization_unit_price, COALESCE(p.status, m.status) AS effective_status FROM myorder m LEFT JOIN purchase p ON (p.order_id = m.order_id OR p.pid = m.order_id) AND p.user = m.user LEFT JOIN customization c ON c.id = m.customization_id WHERE (m.`user`='$username_safe' OR m.`name`='$username_safe') AND (COALESCE(p.status, m.status) NOT IN ('delivered','cancelled') OR COALESCE(p.status, m.status) IS NULL) ORDER BY m.order_id DESC";
            $res = mysqli_query($con, $sql);
            if ($res && mysqli_num_rows($res) > 0) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $order_id = isset($row['order_id']) ? $row['order_id'] : (isset($row['pid']) ? $row['pid'] : 'N/A');
                  $effectiveStatus = $row['effective_status'] ?? ($row['status'] ?? '');
                  $statusMeta = userStatusMeta($effectiveStatus);
                    $display_qty = intval($row['pqty'] ?? 0);
                    if ($display_qty < 1) $display_qty = 1;
                    $total = $row['pprice'] * $display_qty;
                    echo '<tr>';
                    echo '<td><strong>#'.htmlspecialchars($order_id).'</strong></td>';
                    echo '<td>'.htmlspecialchars($row['pname'] ?? 'N/A').'</td>';
                    echo '<td>'.htmlspecialchars($row['name'] ?? 'N/A').'</td>';

                    echo '<td>'.htmlspecialchars($row['user'] ?? 'N/A').'</td>';
                    echo '<td>'.htmlspecialchars($display_qty).'</td>';
                    echo '<td>₹'.htmlspecialchars($row['pprice']).'</td>';
                    // Removed extra total column to match Pending table headers
                    echo '<td>'.htmlspecialchars($row['pdate'] ?? $row['created_at'] ?? '').'</td>';
                    echo '<td><span class="badge bg-'.$statusMeta['badge'].'">'.htmlspecialchars($statusMeta['label']).'</span></td>';
                    // Action (cancel) - allowed only before admin assigns order to delivery agent
                    $status_lc = strtolower(trim((string)$effectiveStatus));
                    $assigned_delivery_agent_id = (int)($row['delivery_agent_id'] ?? 0);
                    $blocked_cancel_statuses = ['assigned', 'out_for_delivery', 'out for delivery', 'out_for_order', 'out for order', 'shipped', 'delivered', 'cancelled'];
                    $can_cancel = empty($row['customization_id']) && $assigned_delivery_agent_id <= 0 && !in_array($status_lc, $blocked_cancel_statuses, true);
                    
                    // Check if partial paid customization needing final payment (check customization status)
                    $customization_status = strtolower(trim((string)($row['customization_status'] ?? '')));
                    $is_partial_paid = $customization_status === 'partial_paid' && !empty($row['customization_id']);
                    $custom_unit_price = (float)($row['customization_unit_price'] ?? 0);
                    $is_pending_customization = in_array($customization_status, ['pending', 'accepted'], true)
                        && !empty($row['customization_id'])
                        && $custom_unit_price > 0;
                    
                    echo '<td>';
                    if ($is_partial_paid) {
                        // Show button to pay remaining 50%
                        $customization_id = (int)$row['customization_id'];
                        echo '<a href="remaining_payment.php?id='.$customization_id.'&order_id='.$order_id.'" class="btn btn-sm btn-warning" style="background: linear-gradient(135deg, #ff9800, #f57c00); border: none; color: white;">';
                        echo '<i class="bi bi-credit-card me-1"></i>Pay Remaining 50%</a>';
                    } elseif ($is_pending_customization) {
                        // Show button to proceed to 50% payment
                        $customization_id = (int)$row['customization_id'];
                        echo '<a href="customization_payment.php?id='.$customization_id.'" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #2196f3, #1976d2); border: none; color: white;">';
                        echo '<i class="bi bi-credit-card me-1"></i>Proceed to Payment</a>';
                    } elseif ($can_cancel) {
                        echo '<form action="cancel_order.php" method="post" style="display:inline-block" onsubmit="return confirm(\'Are you sure you want to cancel this order?\')">';
                        echo '<input type="hidden" name="order_id" value="'.htmlspecialchars($order_id).'">';
                        echo '<button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancel</button>';
                        echo '</form>';
                    } else {
                        echo '&mdash;';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="9" class="text-center">No pending orders</td></tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade" id="history" role="tabpanel">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-dark">
            <tr>
              <th>Order ID</th>
              <th>Product</th>
              <th>User Name</th>
              <th>Email</th>
              <th>Qty</th>
              <th>Price</th>
              <th>Total Price</th>
              <th>Order Date</th>
              <th>Delivered Date</th>
              <th>Cancelled Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql2 = "SELECT m.*, p.status AS purchase_status, p.delivered_at AS purchase_delivered_at, p.canceled_at AS purchase_canceled_at, COALESCE(p.status, m.status) AS effective_status FROM myorder m LEFT JOIN purchase p ON (p.order_id = m.order_id OR p.pid = m.order_id) AND p.user = m.user WHERE (m.`user`='$username_safe' OR m.`name`='$username_safe') AND COALESCE(p.status, m.status) IN ('delivered','cancelled') ORDER BY m.order_id DESC";
            $res2 = mysqli_query($con, $sql2);
            if ($res2 && mysqli_num_rows($res2) > 0) {
                while ($row = mysqli_fetch_assoc($res2)) {
                    $order_id = isset($row['order_id']) ? $row['order_id'] : (isset($row['pid']) ? $row['pid'] : 'N/A');
                  $effectiveStatus = $row['effective_status'] ?? ($row['status'] ?? '');
                  $statusMeta = userStatusMeta($effectiveStatus);
                    $display_qty = intval($row['pqty'] ?? 0);
                    if ($display_qty < 1) $display_qty = 1;
                    $total = $row['pprice'] * $display_qty;
                    echo '<tr>';
                    echo '<td><strong>#'.htmlspecialchars($order_id).'</strong></td>';
                    echo '<td>'.htmlspecialchars($row['pname'] ?? 'N/A').'</td>';
                    echo '<td>'.htmlspecialchars($row['name'] ?? 'N/A').'</td>';
                    echo '<td>'.htmlspecialchars($row['user'] ?? 'N/A').'</td>';
                    echo '<td>'.htmlspecialchars($display_qty).'</td>';
                    echo '<td>₹'.htmlspecialchars(number_format($row['pprice'],2)).'</td>';
                    echo '<td>₹'.htmlspecialchars(number_format($total,2)).'</td>';
                    $order_date = !empty($row['pdate']) ? date('d M Y, H:i', strtotime($row['pdate'])) : (!empty($row['created_at']) ? date('d M Y, H:i', strtotime($row['created_at'])) : '');
                    echo '<td>'.htmlspecialchars($order_date).'</td>';
                    $effectiveDeliveredAt = $row['purchase_delivered_at'] ?? ($row['delivered_at'] ?? '');
                    $effectiveCanceledAt = $row['purchase_canceled_at'] ?? ($row['canceled_at'] ?? '');
                    echo '<td>'.htmlspecialchars(!empty($effectiveDeliveredAt) ? date('d M Y, H:i', strtotime($effectiveDeliveredAt)) : '').'</td>';
                    echo '<td>'.htmlspecialchars(!empty($effectiveCanceledAt) ? date('d M Y, H:i', strtotime($effectiveCanceledAt)) : '').'</td>';
                    echo '<td><span class="badge bg-'.$statusMeta['badge'].'">'.htmlspecialchars($statusMeta['label']).'</span></td>';
                    
                    echo '<form action="orderstatus.php" method="post" style="display: inline;">';
                    echo '<input type="hidden" name="order_id" value="'.htmlspecialchars($order_id).'">';
                    $prod_id_val = isset($row['prod_id']) ? $row['prod_id'] : (isset($row['pid']) ? $row['pid'] : '');
                    echo '<input type="hidden" name="prod_id" value="'.htmlspecialchars($prod_id_val).'">';
                    echo '</form> ';
                    echo '<form action="myorder_details.php" method="post" style="display: inline;">';
                    echo '<input type="hidden" name="order_id" value="'.htmlspecialchars($order_id).'">';
                    
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="11" class="text-center">No history found</td></tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
let autoRefreshEnabled = false;
let refreshInterval = null;
let refreshCountdown = 30;

// Update order counts
document.addEventListener('DOMContentLoaded', function() {
    updateOrderCounts();
});

function updateOrderCounts() {
    const pendingRows = document.querySelectorAll('#pending tbody tr:not([style*="display: none"])');
    const historyRows = document.querySelectorAll('#history tbody tr:not([style*="display: none"])');
    
    const pendingCount = pendingRows.length;
    const historyCount = historyRows.length;
    
    // Check if there's a "no orders" message
    const pendingNoData = pendingRows.length === 1 && pendingRows[0].querySelector('td[colspan]');
    const historyNoData = historyRows.length === 1 && historyRows[0].querySelector('td[colspan]');
    
    document.getElementById('pendingCount').textContent = pendingNoData ? '0' : pendingCount;
    document.getElementById('historyCount').textContent = historyNoData ? '0' : historyCount;
}

function filterOrders() {
    const searchTerm = document.getElementById('orderSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    
    ['pending', 'history'].forEach(tabId => {
        const rows = document.querySelectorAll(`#${tabId} tbody tr`);
        
        rows.forEach(row => {
            // Skip "no orders" rows
            if (row.querySelector('td[colspan]')) {
                return;
            }
            
            const orderId = row.cells[0]?.textContent.toLowerCase() || '';
            const product = row.cells[1]?.textContent.toLowerCase() || '';
            const statusCell = row.querySelector('.badge')?.textContent.toLowerCase() || '';
            
            const matchesSearch = orderId.includes(searchTerm) || 
                                 product.includes(searchTerm) || 
                                 statusCell.includes(searchTerm);
            
            let matchesStatus = true;
            if (statusFilter) {
                if (statusFilter === 'pending' && !statusCell.includes('placed')) {
                    matchesStatus = false;
                } else if (statusFilter === 'shipping' && !statusCell.includes('delivery')) {
                    matchesStatus = false;
                } else if (statusFilter === 'delivered' && !statusCell.includes('delivered')) {
                    matchesStatus = false;
                } else if (statusFilter === 'cancelled' && !statusCell.includes('cancelled')) {
                    matchesStatus = false;
                }
            }
            
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    });
    
    updateOrderCounts();
}

function toggleAutoRefresh() {
    autoRefreshEnabled = !autoRefreshEnabled;
    const indicator = document.getElementById('autoRefreshIndicator');
    const btnText = document.getElementById('refreshBtnText');
    
    if (autoRefreshEnabled) {
        btnText.textContent = 'Disable Auto-Refresh';
        indicator.style.display = 'block';
        startRefreshCountdown();
    } else {
        btnText.textContent = 'Enable Auto-Refresh';
        indicator.style.display = 'none';
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }
}

function startRefreshCountdown() {
    refreshCountdown = 30;
    document.getElementById('refreshTimer').textContent = refreshCountdown;
    
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    refreshInterval = setInterval(() => {
        refreshCountdown--;
        document.getElementById('refreshTimer').textContent = refreshCountdown;
        
        if (refreshCountdown <= 0) {
            refreshOrders();
        }
    }, 1000);
}

function refreshOrders() {
    if (!autoRefreshEnabled) return;
    
    const overlay = document.getElementById('loadingOverlay');
    overlay.style.display = 'flex';
    
    // Reload the page to get fresh data
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// Enhance cancel confirmation
const cancelForms = document.querySelectorAll('form[action="cancel_order.php"]');
cancelForms.forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (confirm('⚠️ Are you sure you want to cancel this order?\n\nThis action cannot be undone.')) {
            const btn = this.querySelector('button');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Cancelling...';
            btn.disabled = true;
            this.submit();
        }
    });
});

// Add smooth scroll to tabs
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function() {
        updateOrderCounts();
    });
});
</script>

<?php include('footer.php'); ?>
