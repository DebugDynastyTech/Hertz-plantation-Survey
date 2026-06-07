<?php
$host = "localhost"; 
$user = "u597023911_plantation_2";
$pass = "d2;eveF6~>G";
$dbname = "u597023911_plantation2"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


mysqli_query($conn, "SET time_zone = '+05:30'");


date_default_timezone_set('Asia/Kolkata');
?>