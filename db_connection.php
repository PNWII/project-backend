<?php
$servername = "localhost"; 
$username = "root";
$password = "";
$dbname = "request_submission_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit();
}
?>