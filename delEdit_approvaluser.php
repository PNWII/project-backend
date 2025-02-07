<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

// Get input data
$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$student_id = $data['student_id'] ?? '';

// Validate request
if (!$action) {
    error_log("Action is missing in the request.");
    echo json_encode(["status" => "error", "message" => "Action is missing."]);
    exit();
}

if (!$student_id) {
    error_log("Student ID is missing in the request.");
    echo json_encode(["status" => "error", "message" => "Student ID is missing."]);
    exit();
}

if ($action === "delete") {
    // Delete the student record
    $stmt = $conn->prepare("DELETE FROM student WHERE idstd_student = ?");
    $stmt->bind_param("s", $student_id);

    // Execute and check the result
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Student deleted successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete student. Please try again."]);
    }
    $stmt->close();
} elseif ($action === "edit") {
    // รับข้อมูลจาก request
    $name = $data['name'] ?? '';
    $nameEng = $data['nameEng'] ?? '';
    $email = $data['email'] ?? '';
    $prefix = $data['prefix'] ?? '';
    $prefixEng = $data['prefixEng'] ?? '';
    $branch = $data['branch'] ?? '';
    $major = $data['major'] ?? '';
    $abbreviate = $data['abbreviate'] ?? '';
    $address = $data['address'] ?? '';
    $tel = $data['tel'] ?? '';
    $password = $data['password'] ?? '';
    $studyplan = $data['studyplan'] ?? '';
    $student_id = $data['student_id'] ?? '';
    $id = $data['id'] ?? '';

    // ตรวจสอบว่าข้อมูลสำคัญมีหรือไม่
    if ($name && $email && $student_id && $id) {
        // เตรียมคำสั่ง SQL
        $stmt = $conn->prepare("UPDATE student SET idstd_student=?, id_studyplan=?, password_student=?, tel_student=?, address_student=?, abbreviate_student=?, major_student=?, branch_student=?, prefix_student=?, name_student=?, prefix_studentEng=?, name_studentEng=?, email_student=? WHERE id_student=?");

        // ตรวจสอบว่าเตรียมคำสั่งสำเร็จหรือไม่
        if ($stmt === false) {
            echo json_encode(["status" => "error", "message" => "Failed to prepare the SQL statement.", "error" => $conn->error]);
            exit();
        }

        // ผูกตัวแปรกับคำสั่ง SQL
        $stmt->bind_param("ssssssssssssss", $student_id, $studyplan, $password, $tel, $address, $abbreviate, $major, $branch, $prefix, $name, $prefixEng, $nameEng, $email, $id);

        // ลอง execute คำสั่ง SQL
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Student updated successfully."]);
        } else {
            // เพิ่มการแสดงข้อผิดพลาดจาก SQL
            echo json_encode(["status" => "error", "message" => "Failed to update student. Please try again.", "error" => $stmt->error]);
        }
        $stmt->close();
    } else {
        // ข้อความแสดงข้อผิดพลาดกรณีข้อมูลไม่ครบถ้วน
        echo json_encode(["status" => "error", "message" => "Name, email, student_id, and id are required for editing."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
}

$conn->close();
