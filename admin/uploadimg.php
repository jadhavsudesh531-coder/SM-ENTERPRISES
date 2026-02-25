<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image</title>
</head>
<body>
    <form action="uploadimg.php" method="post" enctype="multipart/form-data">
        <label for="imageUpload">Select image to upload:</label>
        <input type="file" name="image" id="imageUpload">
        <input type="submit" value="Upload Image" name="submit">
    </form>
</body>
</html>
<?php
if(isset($_POST['submit'])){  
    include 'conn.php';  
    $target_dir = "../productimg/";
    $filename = $_FILES["image"]["name"];
    $target_file = $target_dir . basename($filename);
    $sqlq="INSERT INTO `product_img`(`img_name`) VALUES ('$filename')";
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        if(mysqli_query($con,$sqlq)){
            echo "The file ". htmlspecialchars( basename( $_FILES["image"]["name"])). " has been uploaded.";
        } else {
            echo "Error uploading to database.";
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    } 

 
    // Check if image file is a actual image or fake image
    
}
?>
<?php
$slq="SELECT * FROM `product_img`";
$result=mysqli_query($con,$slq);    
while($row=mysqli_fetch_assoc($result)){
    echo '<img src="../productimg/'.$row['img_name'].'" alt="Image" style="width:150px;height:150px;margin:10px;">';
}


?>