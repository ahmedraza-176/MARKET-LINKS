<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "market-link";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

?>
<!-- Users
│
├── Customer  → website par aakar products/order karega
├── Farmer    → apne products aur orders manage karega
└── Admin     → poora system manage karega -->