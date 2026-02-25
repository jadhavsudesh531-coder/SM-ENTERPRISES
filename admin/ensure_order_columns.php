<?php
/**
 * Run this once to ensure purchase table has required columns
 */
include('conn.php');

$alterations = [
    "ALTER TABLE purchase ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL",
    "ALTER TABLE purchase ADD COLUMN IF NOT EXISTS canceled_at DATETIME NULL",
    "ALTER TABLE purchase ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'pending'"
];

foreach ($alterations as $sql) {
    if (mysqli_query($con, $sql)) {
        echo "✓ Column check/add successful<br>";
    } else {
        // Ignore errors if columns already exist
        echo "• " . mysqli_error($con) . "<br>";
    }
}

mysqli_close($con);
echo "<br><strong>Done! You can now delete this file.</strong>";
?>
