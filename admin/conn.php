<?php
mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "smenterprises";

$connectionOptions = [
	["host" => $dbHost, "port" => 3306],
	["host" => "127.0.0.1", "port" => 3306],
	["host" => $dbHost, "port" => 3307],
	["host" => "127.0.0.1", "port" => 3307],
];

$con = false;
foreach ($connectionOptions as $option) {
	$con = @mysqli_connect($option["host"], $dbUser, $dbPass, $dbName, $option["port"]);
	if ($con) {
		break;
	}
}

if (!$con) {
	http_response_code(500);
	die("Database connection failed. Please make sure MySQL is running in XAMPP and the database name is correct.");
}
?>
