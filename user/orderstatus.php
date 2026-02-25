<?php
define('page','orderstatus');
include('header.php');
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <?php
            include('../admin/conn.php');
            
            if(isset($_POST['order_id'])) {
                $order_id = mysqli_real_escape_string($con, $_POST['order_id']);
                $username = $_SESSION['username'];
                
                // Fetch order details
                $sql = "SELECT * FROM `purchase` WHERE (pid='$order_id' OR order_id='$order_id') AND user='$username'";
                $result = mysqli_query($con, $sql);
                
                if(mysqli_num_rows($result) > 0) {
                    $order = mysqli_fetch_assoc($result);
                    $status = strtolower($order['status']);

                    $displayStatus = 'Order Placed';
                    $current_stage = 0;

                    if (in_array($status, ['assigned', 'out_for_delivery', 'out for delivery', 'shipped'], true)) {
                        $displayStatus = 'Out for Delivery';
                        $current_stage = 1;
                    } elseif (in_array($status, ['delivered', 'completed'], true)) {
                        $displayStatus = 'Delivered';
                        $current_stage = 2;
                    } elseif (in_array($status, ['cancelled', 'canceled', 'rejected'], true)) {
                        $displayStatus = 'Cancelled';
                        $current_stage = 0;
                    }
                    
                    echo '<div class="card shadow-lg">';
                    echo '<div class="card-header bg-danger text-white">';
                    echo '<h5 class="mb-0">Order Tracking #'.$order_id.'</h5>';
                    echo '</div>';
                    echo '<div class="card-body">';
                    
                    // Order details
                    echo '<div class="row mb-4">';
                    echo '<div class="col-md-6">';
                    echo '<p><strong>Product:</strong> '.$order['pname'].'</p>';
                    echo '<p><strong>Quantity:</strong> '.$order['pqty'].'</p>';
                    echo '</div>';
                    echo '<div class="col-md-6">';
                    echo '<p><strong>Price:</strong> ₹'.$order['pprice'].'</p>';
                    echo '<p><strong>Total:</strong> ₹'.($order['pprice'] * $order['pqty']).'</p>';
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<hr>';
                    
                    // Tracking timeline
                    echo '<h5 class="mb-4">Order Status Timeline</h5>';
                    echo '<div class="timeline">';
                    
                    $timeline_stages = [
                        ['icon' => '✅', 'title' => 'Order Placed', 'desc' => 'Your order is placed and confirmed by the store'],
                        ['icon' => '🚚', 'title' => 'Out for Delivery', 'desc' => 'Admin assigned your order to delivery agent'],
                        ['icon' => '📦', 'title' => 'Delivered', 'desc' => 'Product delivered successfully']
                    ];
                    
                    for($i = 0; $i < count($timeline_stages); $i++) {
                        $is_completed = $i <= $current_stage;
                        $is_current = $i == $current_stage;
                        
                        $status_class = $is_completed ? 'completed' : '';
                        $current_class = $is_current ? 'current' : '';
                        
                        echo '<div class="timeline-item '.$status_class.' '.$current_class.'">';
                        echo '<div class="timeline-marker">'.$timeline_stages[$i]['icon'].'</div>';
                        echo '<div class="timeline-content">';
                        echo '<h6>'.$timeline_stages[$i]['title'].'</h6>';
                        echo '<p class="text-muted">'.$timeline_stages[$i]['desc'].'</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    
                    // Status badge
                    echo '<hr>';
                    echo '<div class="alert alert-info text-center">';
                    echo '<h6>Current Status</h6>';
                    echo '<span class="badge bg-primary" style="font-size: 16px;">'.htmlspecialchars($displayStatus).'</span>';
                    echo '</div>';
                    
                    echo '</div>';
                    echo '</div>';
                    
                    echo '<div class="mt-4 text-center">';
                    echo '<a href="myorder.php" class="btn btn-secondary">Back to Orders</a>';
                    echo '</div>';
                    
                } else {
                    echo '<div class="alert alert-danger">Order not found or you do not have access to this order.</div>';
                    echo '<a href="myorder.php" class="btn btn-secondary">Back to Orders</a>';
                }
            } else {
                echo '<div class="alert alert-danger">Invalid request.</div>';
                echo '<a href="myorder.php" class="btn btn-secondary">Back to Orders</a>';
            }
            ?>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    display: flex;
    margin-bottom: 30px;
    position: relative;
}

.timeline-item.completed .timeline-marker {
    background-color: #28a745;
    color: white;
}

.timeline-item.current .timeline-marker {
    background-color: #dc3545;
    color: white;
    box-shadow: 0 0 0 10px rgba(220, 53, 69, 0.1);
}

.timeline-item:not(.completed) .timeline-marker {
    background-color: #e9ecef;
    color: #6c757d;
}

.timeline-marker {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    margin-right: 20px;
    flex-shrink: 0;
}

.timeline-content {
    padding-top: 5px;
}

.timeline-content h6 {
    margin-bottom: 5px;
    font-weight: 600;
}

.timeline-content p {
    margin: 0;
    font-size: 14px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 24px;
    top: 50px;
    width: 2px;
    height: 30px;
    background-color: #e9ecef;
}

.timeline-item.completed:not(:last-child)::before {
    background-color: #28a745;
}
</style>

<?php
include('footer.php');
?>
