<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
include('db_connection.php');

if ($conn->connect_error) {
    die("Database connection failed");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT file_name, file_path FROM files WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($file_name, $file_path);
    $stmt->fetch();
    $stmt->close();

    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
        readfile($file_path);
        exit;
    } else {
        echo "File not found.";
    }
} else {
    echo "Invalid file ID.";
}

$conn->close();
?>