<?php
include('conn.php');
$success = '';
$error = '';

if(isset($_POST['add_product'])){
    $pname = mysqli_real_escape_string($con, $_POST['pname']);
    $pitem = mysqli_real_escape_string($con, $_POST['pitem']);
    $pcompany = mysqli_real_escape_string($con, $_POST['pcompany']);
    $pprice = $_POST['pprice'];
    $pqty = $_POST['pqty'];
    $pamount = $_POST['pamount'];
    $pdescription = mysqli_real_escape_string($con, $_POST['product_description']);

    $filename = time() . "_" . $_FILES["pimg"]["name"];
    $target_dir = "../productimg/";
    $target_file = $target_dir . basename($filename);

    if(move_uploaded_file($_FILES["pimg"]["tmp_name"], $target_file)){
        $sqlq = "INSERT INTO `product` (`pname`, `pitem`, `pcompany`, `pqty`, `pprice`, `pamount`, `pdis`, `pimg`) 
                 VALUES ('$pname', '$pitem', '$pcompany', '$pqty', '$pprice', '$pamount', '$pdescription', '$filename')";
        if(mysqli_query($con, $sqlq)) {
            $success = 'Product added successfully.';
        } else {
            $error = 'System error: Product not saved.';
        }
    } else {
        $error = 'Image upload failed.';
    }
}
include('header.php');
include('product_header.php');
?>

<style>
    body { background-color: #f8f9fa; }

    .main-wrapper { padding: 40px 0; }

    .product-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
    }

    .section-label {
        color: #198754;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        font-weight: 600;
        margin-bottom: 6px;
        display: block;
    }

    .image-preview-zone {
        background: #fff;
        border: 2px dashed #ced4da;
        border-radius: 10px;
        height: 230px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    #preview-target { width: 100%; height: 100%; object-fit: contain; display: none; }
</style>

<div class="container main-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Add Product</h3>
                <a href="view_product.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
            </div>

            <div class="card product-card">
                <div class="card-body p-4">
                    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
                    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="mb-3">
                                    <label class="section-label">Product Name</label>
                                    <input type="text" class="form-control" name="pname" placeholder="Product Name" required>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="section-label">Product Category</label>
                                        <input type="text" class="form-control" name="pitem" placeholder="Category" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="section-label">Product Material</label>
                                        <input type="text" class="form-control" name="pcompany" placeholder="Material" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="section-label">Product Description</label>
                                    <textarea class="form-control" name="product_description" rows="6" placeholder="Enter product description..."></textarea>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="section-label">Product Price</label>
                                        <input type="number" step="0.01" class="form-control" name="pprice" id="price" oninput="runMath()" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="section-label">Product Quantity</label>
                                        <input type="number" class="form-control" name="pqty" id="qty" oninput="runMath()" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="section-label">Product Amount</label>
                                    <input type="text" class="form-control fw-semibold" name="pamount" id="total" readonly value="₹0.00">
                                </div>

                                <label class="section-label">Product Image</label>
                                <div class="image-preview-zone mb-3">
                                    <span id="placeholder-text" class="text-muted small">No Image Selected</span>
                                    <img id="preview-target" src="#" alt="Preview">
                                </div>
                                <input type="file" name="pimg" class="form-control mb-4" accept="image/*" required onchange="renderImg(event)">

                                <button type="submit" name="add_product" class="btn btn-success w-100">Add Product</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function runMath() {
        const p = parseFloat(document.getElementById('price').value) || 0;
        const q = parseFloat(document.getElementById('qty').value) || 0;
        document.getElementById('total').value = "₹" + (p * q).toFixed(2);
    }

    function renderImg(e) {
        if (e.target.files.length > 0) {
            const url = URL.createObjectURL(e.target.files[0]);
            const target = document.getElementById('preview-target');
            const placeholder = document.getElementById('placeholder-text');
            target.src = url;
            target.style.display = 'block';
            placeholder.style.display = 'none';
        }
    }
</script>

<?php include('footer.php'); ?>