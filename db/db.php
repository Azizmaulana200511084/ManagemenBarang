<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "ayutoys";

$connection = mysqli_connect($host, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>