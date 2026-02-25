<?php
include('header.php');
include('conn.php');
$uid=$_POST['uid'];
$sqlq="select * from product where pid='$uid'";
$result=mysqli_query($con,$sqlq);
while($row=mysqli_fetch_assoc($result)){
?>
   <div class="col-sm-6 col-md-6 col-lg-6">
            <!-- header  -->
            
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h3 class="mb-0">Update Product</h3>
                  <a href="view_product.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>View Products</a>
                </div>
             
            <!-- header end  -->
             <form action="update_product.php" method="post" class="shadow-lg p-4">
                
             <div class="mb-2">
                    <label for="product_name" class="form-label">Product Name</label>
                    <input type="text" class="form-control"  name="pname" value="<?php echo $row['pname'];?>" required>
                    <input type="hidden" name="update_id" value="<?php echo $row['pid'];?>">
                </div>
                <div class="mb-2">
                    <label for="product_item" class="form-label">Product Category</label>
                    <input type="text" class="form-control"  name="pitem"value="<?php echo $row['pitem'];?>" required>
                </div>
                <div class="mb-2">
                    <label for="product_company" class="form-label">Product Material</label>
                    <input type="text" class="form-control"  name="pcompany"value="<?php echo $row['pcompany'];?>" required>
                </div>
                <div class="mb-2">
                    <label for="product_price" class="form-label">Product Price</label>
                    <input type="text" class="form-control"  name="pprice" value="<?php echo $row['pprice'];?>" required>
                </div>
                <div class="mb-2">
                    <label for="product_qty" class="form-label">Product Qty</label>
                    <input type="text" class="form-control"  name="pqty" value="<?php echo $row['pqty'];?>" required>
                </div>
                <div class="mb-2">
                    <label for="product_amount" class="form-label">Product Amount</label>
                    <input type="text" class="form-control"  name="pamount"value="<?php echo $row['pamount'];?>" required>
                </div>
                <div class="mb-2">
                    <label for="product_description" class="form-label">Product Description</label>
                    <input type="text" class="form-control"  value="<?php echo $row['pdis'];?>" name="product_description" required>
                </div>
                <div class="mb-3">
                    <label for="formFile" class="form-label">Upload Product Image</label>
                    <input class="form-control" type="file" id="formFile" name="pimg" required>
                </div>
                <button type="submit" class="btn btn-warning" name="add_product">Update Product</button>
             
                
             </div> 
            </form>
            
        </div>
    </div>
</div



<?php
 
}
?>



