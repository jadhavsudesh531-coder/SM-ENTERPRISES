<?php
define('page','view_product');
 include('header.php');
?>

<style>
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    border-radius: 16px;
    overflow: hidden;
}
.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(15, 23, 42, 0.15) !important;
}
.out-of-stock {
    opacity: 0.6;
    position: relative;
}
.out-of-stock::after {
    content: 'OUT OF STOCK';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    padding: 12px 24px;
    font-weight: 700;
    border-radius: 10px;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
}
.search-container {
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
    padding: 35px;
    border-radius: 16px;
    margin-bottom: 30px;
    color: white;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}
.price-badge {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 3px 10px rgba(5, 150, 105, 0.3);
}
.form-control:focus, .form-select:focus {
    border-color: #000000;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}
/* Product grid spacing */
#productsContainer {
    row-gap: 24px;
}
.product-item {
    padding-left: 12px;
    padding-right: 12px;
}
.product-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}
</style>

<div class="container-fluid px-2 mt-1">
    <!-- Search and Filter Section -->
    <div class="search-container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-3"><i class="bi bi-search me-2"></i>Find Your Product</h3>
                <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="Search by product name, category, or company..." onkeyup="filterProducts()">
            </div>
            <div class="col-md-4">
                <label class="form-label">Sort By:</label>
                <select id="sortBy" class="form-select form-select-lg" onchange="sortProducts()">
                    <option value="default">Default</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="name">Name: A to Z</option>
                </select>
            </div>
        </div>
        <div class="mt-3">
            <span class="badge bg-light text-dark me-2"><span id="productCount">0</span> Products Found</span>
            <button class="btn btn-light btn-sm" onclick="resetFilters()"><i class="bi bi-arrow-clockwise me-1"></i>Reset</button>
        </div>
    </div>

    <!-- Products Container -->
    <div id="productsContainer" class="row g-4"></div>
    
    <!-- No Results Message -->
    <div id="noResults" class="text-center py-5" style="display:none;">
        <i class="bi bi-search" style="font-size: 3rem; color: #dc3545;"></i>
        <h4 class="mt-3">No products found</h4>
        <p class="text-muted">Try adjusting your search or filters</p>
    </div>
</div>

<script>
let productsData = [];

// Load products data
<?php
include('../admin/conn.php');
if (!($con instanceof mysqli)) {
    $con = null;
}
/** @var mysqli|null $con */
$sql="SELECT * FROM `product`";
$result = $con ? mysqli_query($con, $sql) : false;
echo 'productsData = [';
$first = true;
while($result && ($row = mysqli_fetch_assoc($result))){
    if(!$first) echo ',';
    $first = false;
    $qty=$row['pqty'];
    $price = floatval($row['pprice']);
    echo json_encode([
        'pid' => $row['pid'],
        'pname' => $row['pname'],
        'pcompany' => $row['pcompany'],
        'pitem' => $row['pitem'] ?? '',
        'pprice' => $row['pprice'],
        'pqty' => $qty,
        'pimg' => $row['pimg']
    ]);
}
echo '];
';
?>

function renderProducts(products) {
    const container = document.getElementById('productsContainer');
    const noResults = document.getElementById('noResults');
    const productCount = document.getElementById('productCount');
    
    if (products.length === 0) {
        container.innerHTML = '';
        noResults.style.display = 'block';
        productCount.textContent = '0';
        return;
    }
    
    noResults.style.display = 'none';
    productCount.textContent = products.length;
    
    let html = '';
    products.forEach(product => {
        const isOutOfStock = product.pqty <= 0;
        const stockClass = isOutOfStock ? 'out-of-stock' : '';
        const requiresAdvance = product.pprice >= 1000;
        
        html += `
        <div class="col-lg-3 col-md-4 col-sm-6 product-item">
            <div class="card h-100 product-card ${stockClass}">
                <img src="../productimg/${product.pimg}" class="card-img-top" alt="${product.pname}" style="height: 250px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${product.pname}</h5>
                    <p class="text-muted small mb-1"><i class="bi bi-building me-1"></i>${product.pcompany}</p>
                    <p class="text-muted small mb-2"><i class="bi bi-tag me-1"></i>${product.pitem || 'General'}</p>
                    <div class="mb-2">
                        <span class="price-badge">₹${parseFloat(product.pprice).toFixed(2)}</span>
                    </div>
                    ${requiresAdvance ? '<div class="alert alert-warning py-2 px-2 small mb-2"><i class="bi bi-info-circle me-1"></i>50% advance required</div>' : ''}
                    <div class="small text-muted mb-2">
                        <i class="bi bi-box-seam me-1"></i>Stock: <strong class="${isOutOfStock ? 'text-danger' : 'text-success'}">${product.pqty} units</strong>
                    </div>
                    ${!isOutOfStock ? `
                    <form action="purchase.php" method="post" class="mt-auto" onsubmit="return validatePurchase(this, ${product.pqty})">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label class="mb-0 small">Qty:</label>
                            <input type="number" value="1" min="1" max="${product.pqty}" name="qty" class="form-control form-control-sm" style="width:70px;" required>
                        </div>
                        <input type="hidden" name="pid" value="${product.pid}">
                        <input type="hidden" name="pname" value="${product.pname}">
                        <input type="hidden" name="pprice" value="${product.pprice}">
                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-cart3 me-2"></i>Buy Now</button>
                    </form>
                    ` : '<button class="btn btn-secondary w-100" disabled>Out of Stock</button>'}
                </div>
            </div>
        </div>
        `;
    });
    
    container.innerHTML = html;
}

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    let filtered = productsData.filter(product => {
        return product.pname.toLowerCase().includes(searchTerm) ||
               product.pcompany.toLowerCase().includes(searchTerm) ||
               (product.pitem && product.pitem.toLowerCase().includes(searchTerm));
    });
    
    renderProducts(filtered);
}

function sortProducts() {
    const sortBy = document.getElementById('sortBy').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    let filtered = productsData.filter(product => {
        return product.pname.toLowerCase().includes(searchTerm) ||
               product.pcompany.toLowerCase().includes(searchTerm) ||
               (product.pitem && product.pitem.toLowerCase().includes(searchTerm));
    });
    
    switch(sortBy) {
        case 'price-low':
            filtered.sort((a, b) => parseFloat(a.pprice) - parseFloat(b.pprice));
            break;
        case 'price-high':
            filtered.sort((a, b) => parseFloat(b.pprice) - parseFloat(a.pprice));
            break;
        case 'name':
            filtered.sort((a, b) => a.pname.localeCompare(b.pname));
            break;
    }
    
    renderProducts(filtered);
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('sortBy').value = 'default';
    renderProducts(productsData);
}

function validatePurchase(form, maxQty) {
    const qtyInput = form.querySelector('input[name="qty"]');
    const qty = parseInt(qtyInput.value);
    
    if (qty < 1) {
        alert('Quantity must be at least 1');
        return false;
    }
    
    if (qty > maxQty) {
        alert(`Only ${maxQty} units available in stock`);
        qtyInput.value = maxQty;
        return false;
    }
    
    // Show loading state
    const btn = form.querySelector('button[type="submit"]');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    btn.disabled = true;
    
    return true;
}

// Initial render
document.addEventListener('DOMContentLoaded', function() {
    renderProducts(productsData);
});
</script>

<?php
include('footer.php');  
?>
