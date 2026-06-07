<?php
$host = "auth-db1401.hstgr.io"; 
$user = "u121149020_hertz_app";
$pass = "=v+&d8I0qI";
$dbname = "u121149020_hertz_insp"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


mysqli_query($conn, "SET time_zone = '+05:30'");


date_default_timezone_set('Asia/Kolkata');
?>