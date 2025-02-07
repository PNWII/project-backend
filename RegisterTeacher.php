<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

// Handle preflight requests for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'));
error_log(print_r($data, true));  // Log data to the error log

// Check if data is null or empty
if (empty($data)) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit();
}

// Validate required fields
if (
    empty($data->prefixTeacher) || 
    empty($data->nameTeacher) || 
    empty($data->telTeacher) || 
    empty($data->emailTeacher) || 
    empty($data->passwordTeacher) || 
    empty($data->typeTeacher) ||
    !isset($data->typeTeacher)
) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit();
}

// Validate typeTeacher as a number
if (!is_numeric($data->typeTeacher)) {
    echo json_encode(["status" => "error", "message" => "Invalid typeTeacher"]);
    exit();
}

// Validate email format
if (!filter_var($data->emailTeacher, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit();
}

$sql = "INSERT INTO teacher (
    prefix_teacher,
    name_teacher,
    email_teacher,
    password_teacher,
    tel_teacher,
    type_teacher
) VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare statement"]);
    exit();
}

$stmt->bind_param(
    "ssssss", 
    $data->prefixTeacher, 
    $data->nameTeacher, 
    $data->emailTeacher, 
    $data->passwordTeacher,  
    $data->telTeacher,
    $data->typeTeacher
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Teacher registered successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to register teacher"]);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>