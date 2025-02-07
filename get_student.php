<?php
//ไฟล์ backend/get_student.php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Origin: *'); // Allow all domains (or specify your frontend domain)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Include database connection
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $userIDStudent = $input['id'] ?? null;


    if ($userIDStudent) {
        $stmt = $conn->prepare("
            SELECT 
                s.id_student, 
                s.id_studyplan, 
                s.prefix_student, 
                s.name_student, 
                s.idstd_student, 
                s.major_student, 
                s.branch_student, 
                s.abbreviate_student, 
                s.address_student, 
                s.email_student, 
                s.tel_student,
                s.prefix_studentEng,
                s.name_studentEng,
                sp.name_studyplan
            FROM 
                student s 
            LEFT JOIN 
                studyplan sp 
            ON 
                s.id_studyplan = sp.id_studyplan 
            WHERE 
                s.idstd_student = ?
        ");
        $stmt->bind_param("s", $userIDStudent);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $studentData = $result->fetch_assoc();
            echo json_encode(["status" => "success", "data" => $studentData]);
        } else {
            echo json_encode(["status" => "error", "message" => "User not found"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

$conn->close();
?>