<?php
include('conn.php');

// Get product statistics
$totalProductsQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM product");
$totalProducts = mysqli_fetch_assoc($totalProductsQuery)['total'];

$lowStockQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM product WHERE pqty <= 5");
$lowStockCount = mysqli_fetch_assoc($lowStockQuery)['total'];

$outOfStockQuery = mysqli_query($con, "SELECT COUNT(*) as total FROM product WHERE pqty = 0");
$outOfStockCount = mysqli_fetch_assoc($outOfStockQuery)['total'];

$totalValueQuery = mysqli_query($con, "SELECT SUM(pprice * pqty) as total_value FROM product");
$totalValueRow = mysqli_fetch_assoc($totalValueQuery);
$totalInventoryValue = $totalValueRow['total_value'] ?? 0;

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Product Management Secondary Header -->
<div class="product-management-header" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 2px solid #e2e8f0; padding: 20px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
  <div class="container-fluid">
    <!-- Title Section -->
    <div class="row align-items-center mb-4">
      <div class="col-md-8">
        <h1 style="font-size: 2rem; font-weight: 700; color: #000000; margin: 0; display: flex; align-items: center; gap: 12px;">
          <i class="bi bi-box-seam" style="font-size: 2rem; color: #1a1a1a;"></i>
          <span>Product Management</span>
        </h1>
        <p style="margin: 8px 0 0 0; color: #64748b; font-size: 0.95rem;">
          <i class="bi bi-info-circle me-2"></i>Manage and monitor your complete product inventory.
        </p>
      </div>
      <div class="col-md-4 text-end">
        <a href="add_product.php" class="btn btn-dark me-2" style="background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600;">
          <i class="bi bi-plus-circle me-2"></i>Add New Product
        </a>
      </div>
    </div>

    <!-- Statistics Row -->
    <div class="row g-3">
      <!-- Total Products Card -->
      <div class="col-md-3" style="min-width: 250px;">
        <div class="stat-card-pm" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-left: 4px solid #000000; transition: all 0.3s ease;">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="color: #64748b; font-size: 0.85rem; margin: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Products</p>
              <h3 style="color: #000000; font-weight: 700; font-size: 1.8rem; margin: 8px 0 0 0;"><?php echo $totalProducts; ?></h3>
            </div>
            <div style="background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); color: white; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
              <i class="bi bi-box"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Low Stock Card -->
      <div class="col-md-3" style="min-width: 250px;">
        <div class="stat-card-pm" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-left: 4px solid #f59e0b; transition: all 0.3s ease;">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="color: #64748b; font-size: 0.85rem; margin: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Low Stock Items</p>
              <h3 style="color: #f59e0b; font-weight: 700; font-size: 1.8rem; margin: 8px 0 0 0;"><?php echo $lowStockCount; ?></h3>
              <small style="color: #94a3b8; font-size: 0.8rem;">≤ 5 units</small>
            </div>
            <div style="background: #fef3c7; color: #f59e0b; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Out of Stock Card -->
      <div class="col-md-3" style="min-width: 250px;">
        <div class="stat-card-pm" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-left: 4px solid #ef4444; transition: all 0.3s ease;">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="color: #64748b; font-size: 0.85rem; margin: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Out of Stock</p>
              <h3 style="color: #ef4444; font-weight: 700; font-size: 1.8rem; margin: 8px 0 0 0;"><?php echo $outOfStockCount; ?></h3>
              <small style="color: #94a3b8; font-size: 0.8rem;">0 units</small>
            </div>
            <div style="background: #fee2e2; color: #ef4444; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
              <i class="bi bi-x-circle"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Inventory Value Card -->
      <div class="col-md-3" style="min-width: 250px;">
        <div class="stat-card-pm" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-left: 4px solid #10b981; transition: all 0.3s ease;">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p style="color: #64748b; font-size: 0.85rem; margin: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Inventory Value</p>
              <h3 style="color: #10b981; font-weight: 700; font-size: 1.6rem; margin: 8px 0 0 0;">₹<?php echo number_format($totalInventoryValue, 0); ?></h3>
              <small style="color: #94a3b8; font-size: 0.8rem;">Total stock value</small>
            </div>
            <div style="background: #d1fae5; color: #10b981; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
              <i class="bi bi-currency-rupee"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs/Filter Section -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="d-flex gap-2 flex-wrap" style="background: white; padding: 12px 16px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
          <a href="view_product.php" class="btn btn-sm <?php echo ($currentPage == 'view_product.php') ? 'btn-dark' : 'btn-outline-dark'; ?>" style="border-radius: 6px; padding: 8px 16px; font-weight: 600;">
            <i class="bi bi-list-ul me-1"></i>All Products
          </a>
          <a href="view_product.php?filter=low_stock" class="btn btn-sm <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'low_stock') ? 'btn-warning' : 'btn-outline-warning'; ?>" style="border-radius: 6px; padding: 8px 16px; font-weight: 600;">
            <i class="bi bi-exclamation-triangle me-1"></i>Low Stock
          </a>
          <a href="add_product.php" class="btn btn-sm <?php echo ($currentPage == 'add_product.php') ? 'btn-success' : 'btn-outline-success'; ?>" style="border-radius: 6px; padding: 8px 16px; font-weight: 600;">
            <i class="bi bi-plus-circle me-1"></i>Add Product
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Hover Effects for Stat Cards -->
<style>
.stat-card-pm:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12) !important;
}

.product-management-header .btn:hover {
  transform: translateY(-2px);
  transition: all 0.2s ease;
}

@media (max-width: 768px) {
  .product-management-header h1 {
    font-size: 1.5rem !important;
  }
  
  .product-management-header .col-md-4 {
    margin-top: 15px;
  }
  
  .product-management-header .col-md-4 .btn {
    display: block;
    width: 100%;
    margin-bottom: 8px;
  }
}
</style>
