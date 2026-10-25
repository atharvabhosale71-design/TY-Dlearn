<?php
echo "1. PHP is working!<br>";
flush();
echo "2. Trying to connect...<br>";
flush();

// Timeout set kiya taaki page infinite load na ho
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);

// 'localhost' ki jagah seedha 127.0.0.1 aur port 3306 define kiya
$connected = @$conn->real_connect('127.0.0.1', 'root', '', 'dlearn', 3306);

if (!$connected) {
    echo "3. Connection Failed: " . mysqli_connect_error();
} else {
    echo "3. Success!";
}
?>