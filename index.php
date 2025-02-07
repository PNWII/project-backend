<?php
$servername = "bv9jxiylgjkrr9iqmjhw-mysql.services.clever-cloud.com";
$port = 3306;
$username = "uwzafmwbk5f33ppl";
$password = "EGqVBB7eV3QlQ21oXBP1";
$dbname = "bv9jxiylgjkrr9iqmjhw";

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}
