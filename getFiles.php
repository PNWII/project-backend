<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

include('db_connection.php');

if ($conn->connect_error) {
    die(json_encode(["error" => $conn->connect_error]));
}

$sql = "SELECT id, file_name FROM files ORDER BY uploaded_at DESC";
$result = $conn->query($sql);

$files = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
}

echo json_encode($files);
$conn->close();
?>