<?php
$servername = "localhost";
$username = "root";
$password = "200404";
$dbname = "project1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}
?>
