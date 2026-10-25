
<?php
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "dlearn";
$port = 3307;

$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database,
    $port
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
