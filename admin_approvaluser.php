<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $student_id = $data['student_id'];
    $approval_status = $data['approval_status'];

    if (isset($student_id) && isset($approval_status)) {
        $stmt = $conn->prepare("UPDATE student SET status_student = ? WHERE idstd_student = ?");
        $stmt->bind_param("si", $approval_status, $student_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Status updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update status"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid input"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

$conn->close();
?>