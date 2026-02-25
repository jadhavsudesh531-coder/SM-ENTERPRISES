<?php 
// Include database connection
include('conn.php'); 
include('header.php');
include('product_header.php');
?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.95);
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    body {
        background-color: #f4f7f6;
        font-family: 'Inter', sans-serif;
    }

    .main-container {
        padding: 30px 12px;
    }

    /* Stats Card Styling */
    .stat-card {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s ease;
        background: var(--primary-gradient);
        color: white;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    /* Table Styling */
    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        border: none;
    }

    .custom-table thead {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
    }

    .table-scroll {
        max-height: 58vh;
        overflow: auto;
    }

    .custom-table {
        min-width: 980px;
    }

    .custom-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa;
    }

    .material-info {
        max-width: 260px;
        white-space: normal;
        word-break: break-word;
    }

    .action-cell {
        white-space: nowrap;
    }

    .action-form {
        margin: 0;
    }

    .product-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .badge-category {
        background-color: #eef2ff;
        color: #4338ca;
        font-weight: 500;
        padding: 0.5em 1em;
    }

    .btn-action {
        width: 35px;
        height: 35px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: 0.2s;
    }

    .price-text {
        color: #2d3748;
        font-weight: 700;
    }

    .stock-low {
        color: #e53e3e;
        font-weight: bold;
    }
</style>

<div class="container-fluid main-container">
    <div class="card table-container">
        <div class="table-responsive table-scroll">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Product</th>
                        <th>Material/Info</th>
                        <th>Pricing</th>
                        <th>Stock</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $sqlq = "SELECT * FROM `product` ORDER BY pid DESC";
                    $result = mysqli_query($con, $sqlq);
                    
                    if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <tr>
                        <td class="ps-4 text-muted small"><?php echo $i++; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="../productimg/<?php echo htmlspecialchars($row['pimg']); ?>" class="product-img me-3">
                                <div>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['pname']); ?></div>
                                    <span class="badge badge-category rounded-pill"><?php echo htmlspecialchars($row['pitem']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small text-dark fw-medium"><?php echo htmlspecialchars($row['pcompany']); ?></div>
                            <div class="small text-muted material-info">
                                <?php echo htmlspecialchars($row['pdis']); ?>
                            </div>
                        </td>
                        <td>
                            <div class="price-text">₹<?php echo number_format($row['pprice'], 2); ?></div>
                            <div class="small text-muted">Total: ₹<?php echo number_format($row['pamount'] ?? 0, 2); ?></div>
                        </td>
                        <td>
                            <div class="<?php echo ($row['pqty'] < 5) ? 'stock-low' : 'text-dark'; ?>">
                                <?php echo $row['pqty']; ?> pcs
                            </div>
                        </td>
                        <td class="text-center action-cell">
                            <div class="d-flex justify-content-center gap-2">
                                <form action="update.php" method="post" class="action-form">
                                    <input type="hidden" name="uid" value="<?php echo $row['pid']; ?>">
                                    <button class="btn btn-outline-primary btn-action" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </form>
                                <form action="delete.php" method="post" class="action-form" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="did" value="<?php echo $row['pid']; ?>">
                                    <button class="btn btn-outline-danger btn-action" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center py-5 text-muted'>No products available.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>