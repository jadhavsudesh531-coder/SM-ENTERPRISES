<?php
define('page','categories');
include('header.php');  
?>

<style>
.filter-btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border-radius: 10px;
    padding: 0.6rem 1.2rem;
    font-weight: 600;
}
.btn-danger.filter-btn,
#categoryFilters .btn-danger,
#materialFilters .btn-danger {
    background: linear-gradient(135deg, #c8a646 0%, #a88424 100%) !important;
    border-color: #a88424 !important;
    color: #1f1a0b !important;
}

.btn-outline-danger.filter-btn,
#categoryFilters .btn-outline-danger,
#materialFilters .btn-outline-danger {
    border-color: #c8a646 !important;
    color: #a88424 !important;
    background: #fffdf6 !important;
}

.btn-outline-danger.filter-btn:hover,
#categoryFilters .btn-outline-danger:hover,
#materialFilters .btn-outline-danger:hover {
    background: #fff4d8 !important;
    border-color: #a88424 !important;
    color: #7e631d !important;
}
.filter-btn:hover:not(.active) {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}
.filter-btn.active {
    font-weight: 700;
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%) !important;
    border-color: #000000 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}
.filter-section {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    padding: 25px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
}
.price-range-container {
    padding: 20px;
    background: white;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
}
.product-grid-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.product-grid-item:hover {
    transform: translateY(-8px);
}
.loading-spinner {
    text-align: center;
    padding: 50px;
    display: none;
}
.filter-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 25px;
}
.filter-tag {
    background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
    color: white;
    padding: 8px 18px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.4);
}
.filter-tag .remove-tag {
    cursor: pointer;
    font-weight: 700;
    font-size: 1.2rem;
    transition: transform 0.2s;
}
.filter-tag .remove-tag:hover {
    transform: scale(1.2);
}
</style>

<div class="container-fluid px-2 mt-1">
    
    <!-- Active Filters Display -->
    <div id="activeFilters" class="filter-tags"></div>
    
    <!-- Filters Section -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3" style="color: #a88424; font-weight: 700;">
                    <i class="bi bi-funnel me-2"></i>Category
                </h5>
                <div class="d-flex flex-wrap gap-2" id="categoryFilters">
                    <button class="btn btn-danger active filter-btn" data-category="" onclick="filterByCategory('')">
                        All Categories
                    </button>
                    <?php
                    include('../admin/conn.php');
                    
                    // Get all distinct categories
                    $cat_sql = "SELECT DISTINCT pitem FROM `product` WHERE pitem IS NOT NULL AND pitem != '' ORDER BY pitem";
                    $cat_result = mysqli_query($con, $cat_sql);
                    
                    while($cat_row = mysqli_fetch_assoc($cat_result)) {
                        $category = htmlspecialchars($cat_row['pitem']);
                        echo '<button class="btn btn-outline-danger filter-btn" data-category="'.htmlspecialchars($category).'" onclick="filterByCategory(\''.htmlspecialchars($category, ENT_QUOTES).'\')">';
                        echo $category;
                        echo '</button>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="col-md-6">
                <h5 class="mb-3" style="color: #a88424; font-weight: 700;">
                    <i class="bi bi-tag me-2"></i>Material/Type
                </h5>
                <div class="d-flex flex-wrap gap-2" id="materialFilters">
                    <button class="btn btn-danger active filter-btn" data-material="" onclick="filterByMaterial('')">
                        All Materials
                    </button>
                    <?php
                    // Get all distinct materials
                    $mat_sql = "SELECT DISTINCT pcompany FROM `product` WHERE pcompany IS NOT NULL AND pcompany != '' ORDER BY pcompany";
                    $mat_result = mysqli_query($con, $mat_sql);
                    
                    while($mat_row = mysqli_fetch_assoc($mat_result)) {
                        $material = htmlspecialchars($mat_row['pcompany']);
                        echo '<button class="btn btn-outline-danger filter-btn" data-material="'.htmlspecialchars($material).'" onclick="filterByMaterial(\''.htmlspecialchars($material, ENT_QUOTES).'\')">';
                        echo $material;
                        echo '</button>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Price Range Filter -->
        <div class="price-range-container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label"><strong>Price Range:</strong></label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" id="minPrice" class="form-control" placeholder="Min" value="0" min="0">
                        <span>to</span>
                        <input type="number" id="maxPrice" class="form-control" placeholder="Max" value="999999" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Stock Status:</strong></label>
                    <select id="stockFilter" class="form-select">
                        <option value="all">All Products</option>
                        <option value="in-stock">In Stock Only</option>
                        <option value="out-of-stock">Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Sort By:</strong></label>
                    <select id="sortBy" class="form-select">
                        <option value="default">Default</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="name">Name: A-Z</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" onclick="applyFilters()"><i class="bi bi-check2-circle me-2"></i>Apply Filters</button>
                <button class="btn btn-outline-secondary" onclick="resetAllFilters()"><i class="bi bi-arrow-clockwise me-2"></i>Reset All</button>
                <span class="ms-3 text-muted"><span id="resultCount">0</span> products found</span>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;"></div>
        <p class="mt-3 text-muted">Loading products...</p>
    </div>

    <!-- Products Display -->
    <div class="row" id="productsGrid"></div>
    
    <!-- No Results Message -->
    <div id="noResults" class="text-center py-5" style="display:none;">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #a88424;"></i>
        <h4 class="mt-3">No products found</h4>
        <p class="text-muted">Try adjusting your filters</p>
    </div>
</div>

<script>
let allProducts = [];
let currentFilters = {
    category: '',
    material: '',
    minPrice: 0,
    maxPrice: 999999,
    stock: 'all',
    sort: 'default'
};

// Load all products data
<?php
$sql = "SELECT * FROM `product`";
$result = mysqli_query($con, $sql);
echo 'allProducts = [';
$first = true;
while($row = mysqli_fetch_assoc($result)) {
    if(!$first) echo ',';
    $first = false;
    echo json_encode([
        'pid' => $row['pid'],
        'pname' => $row['pname'],
        'pcompany' => $row['pcompany'],
        'pitem' => $row['pitem'] ?? '',
        'pprice' => floatval($row['pprice']),
        'pqty' => intval($row['pqty']),
        'pimg' => $row['pimg']
    ]);
}
echo '];';
?>

function filterByCategory(category) {
    currentFilters.category = category;
    
    // Update button states
    document.querySelectorAll('#categoryFilters .filter-btn').forEach(btn => {
        if (btn.getAttribute('data-category') === category) {
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-danger', 'active');
        } else {
            btn.classList.remove('btn-danger', 'active');
            btn.classList.add('btn-outline-danger');
        }
    });
    
    applyFilters();
}

function filterByMaterial(material) {
    currentFilters.material = material;
    
    // Update button states
    document.querySelectorAll('#materialFilters .filter-btn').forEach(btn => {
        if (btn.getAttribute('data-material') === material) {
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-danger', 'active');
        } else {
            btn.classList.remove('btn-danger', 'active');
            btn.classList.add('btn-outline-danger');
        }
    });
    
    applyFilters();
}

function applyFilters() {
    // Get current filter values
    currentFilters.minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    currentFilters.maxPrice = parseFloat(document.getElementById('maxPrice').value) || 999999;
    currentFilters.stock = document.getElementById('stockFilter').value;
    currentFilters.sort = document.getElementById('sortBy').value;
    
    // Show loading
    document.getElementById('loadingSpinner').style.display = 'block';
    document.getElementById('productsGrid').style.display = 'none';
    
    setTimeout(() => {
        // Filter products
        let filtered = allProducts.filter(product => {
            // Category filter
            if (currentFilters.category && product.pitem !== currentFilters.category) {
                return false;
            }
            
            // Material filter
            if (currentFilters.material && product.pcompany !== currentFilters.material) {
                return false;
            }
            
            // Price filter
            if (product.pprice < currentFilters.minPrice || product.pprice > currentFilters.maxPrice) {
                return false;
            }
            
            // Stock filter
            if (currentFilters.stock === 'in-stock' && product.pqty <= 0) {
                return false;
            }
            if (currentFilters.stock === 'out-of-stock' && product.pqty > 0) {
                return false;
            }
            
            return true;
        });
        
        // Sort products
        switch(currentFilters.sort) {
            case 'price-low':
                filtered.sort((a, b) => a.pprice - b.pprice);
                break;
            case 'price-high':
                filtered.sort((a, b) => b.pprice - a.pprice);
                break;
            case 'name':
                filtered.sort((a, b) => a.pname.localeCompare(b.pname));
                break;
        }
        
        renderProducts(filtered);
        updateActiveFilters();
        
        // Hide loading
        document.getElementById('loadingSpinner').style.display = 'none';
        document.getElementById('productsGrid').style.display = 'flex';
    }, 300);
}

function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    const noResults = document.getElementById('noResults');
    const resultCount = document.getElementById('resultCount');
    
    resultCount.textContent = products.length;
    
    if (products.length === 0) {
        grid.innerHTML = '';
        noResults.style.display = 'block';
        return;
    }
    
    noResults.style.display = 'none';
    
    let html = '';
    products.forEach(product => {
        const isOutOfStock = product.pqty <= 0;
        const stockBadge = isOutOfStock ? 
            '<span class=\"badge bg-danger\">Out of Stock</span>' : 
            '<span class=\"badge bg-success\">In Stock: '+product.pqty+'</span>';
        
        html += `
        <div class="col-md-3 mb-4 product-grid-item">
            <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <img src="../productimg/${product.pimg}" class="card-img-top" alt="${product.pname}" style="height: 250px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${product.pname}</h5>
                    <p class="text-muted small mb-1"><i class="bi bi-tag me-1"></i>${product.pitem || 'General'}</p>
                    <p class="text-muted small mb-2"><i class="bi bi-building me-1"></i>${product.pcompany}</p>
                    <p class="mb-2"><strong style="font-size: 1.2rem; color: #28a745;">₹${product.pprice.toFixed(2)}</strong></p>
                    <div class="mb-2">${stockBadge}</div>
                    ${!isOutOfStock ? `
                    <form action="purchase.php" method="post" class="mt-auto" onsubmit="return validatePurchase(this, ${product.pqty})">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label class="mb-0 small">Qty:</label>
                            <input type="number" value="1" min="1" max="${product.pqty}" name="qty" class="form-control form-control-sm" style="width:70px;" required>
                        </div>
                        <input type="hidden" name="pid" value="${product.pid}">
                        <input type="hidden" name="pname" value="${product.pname}">
                        <input type="hidden" name="pprice" value="${product.pprice}">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-cart3 me-2"></i>Buy Now</button>
                    </form>
                    ` : '<button class="btn btn-secondary w-100 mt-auto" disabled>Out of Stock</button>'}
                </div>
            </div>
        </div>
        `;
    });
    
    grid.innerHTML = html;
}

function updateActiveFilters() {
    const container = document.getElementById('activeFilters');
    let tags = [];
    
    if (currentFilters.category) {
        tags.push(`<span class="filter-tag">Category: ${currentFilters.category} <span class="remove-tag" onclick="removeFilter('category')">×</span></span>`);
    }
    
    if (currentFilters.material) {
        tags.push(`<span class="filter-tag">Material: ${currentFilters.material} <span class="remove-tag" onclick="removeFilter('material')">×</span></span>`);
    }
    
    if (currentFilters.minPrice > 0 || currentFilters.maxPrice < 999999) {
        tags.push(`<span class="filter-tag">Price: ₹${currentFilters.minPrice} - ₹${currentFilters.maxPrice} <span class="remove-tag" onclick="removeFilter('price')">×</span></span>`);
    }
    
    if (currentFilters.stock !== 'all') {
        tags.push(`<span class="filter-tag">Stock: ${currentFilters.stock} <span class="remove-tag" onclick="removeFilter('stock')">×</span></span>`);
    }
    
    container.innerHTML = tags.join('');
}

function removeFilter(type) {
    switch(type) {
        case 'category':
            filterByCategory('');
            break;
        case 'material':
            filterByMaterial('');
            break;
        case 'price':
            document.getElementById('minPrice').value = '0';
            document.getElementById('maxPrice').value = '999999';
            applyFilters();
            break;
        case 'stock':
            document.getElementById('stockFilter').value = 'all';
            applyFilters();
            break;
    }
}

function resetAllFilters() {
    filterByCategory('');
    filterByMaterial('');
    document.getElementById('minPrice').value = '0';
    document.getElementById('maxPrice').value = '999999';
    document.getElementById('stockFilter').value = 'all';
    document.getElementById('sortBy').value = 'default';
    applyFilters();
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
    
    const btn = form.querySelector('button[type="submit"]');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    btn.disabled = true;
    
    return true;
}

// Initial render
document.addEventListener('DOMContentLoaded', function() {
    applyFilters();
});
</script>


