<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('db_connection.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    if (isset($_POST['idstdStudent'], $_POST['prefixStudent'], $_POST['nameStudent'], $_POST['studyplanStudent'], $_POST['majorStudent'], $_POST['branchStudent'], $_POST['abbreviateStudent'], $_POST['addressStudent'], $_POST['telStudent'], $_POST['emailStudent'], $_POST['passwordStudent'])) {

        $idstdStudent = $conn->real_escape_string($_POST['idstdStudent']);
        $prefixStudent = $conn->real_escape_string($_POST['prefixStudent']);
        $nameStudent = $conn->real_escape_string($_POST['nameStudent']);
        $prefixStudentEng = $conn->real_escape_string($_POST['prefixStudentEng']);
        $nameStudentEng = $conn->real_escape_string($_POST['nameStudentEng']);
        $studyplanStudent = $conn->real_escape_string($_POST['studyplanStudent']);
        $majorStudent = $conn->real_escape_string($_POST['majorStudent']);
        $branchStudent = $conn->real_escape_string($_POST['branchStudent']);
        $abbreviateStudent = $conn->real_escape_string($_POST['abbreviateStudent']);
        $addressStudent = $conn->real_escape_string($_POST['addressStudent']);
        $telStudent = $conn->real_escape_string($_POST['telStudent']);
        $emailStudent = $conn->real_escape_string($_POST['emailStudent']);
        $passwordStudent = $_POST['passwordStudent']; // Raw password

        // Check if the student ID or email already exists
        $checkDuplicate = "SELECT * FROM student WHERE idstd_student = '$idstdStudent' OR email_student = '$emailStudent' LIMIT 1";
        $result = $conn->query($checkDuplicate);

        if ($result->num_rows > 0) {
            // If there's a match, send an error response
            echo json_encode(["status" => "error", "message" => "รหัสนักศึกษาหรืออีเมลนี้ลงทะเบียนแล้ว"]);
            exit();
        }

        // Check if the selected study plan exists
        $checkStudyplan = "SELECT id_studyplan FROM studyplan WHERE id_studyplan = '$studyplanStudent' LIMIT 1";
        $result = $conn->query($checkStudyplan);

        if ($result->num_rows == 0) {
            echo json_encode(["status" => "error", "message" => "The selected study plan does not exist!"]);
            exit();
        }

        if (!empty($studyplanStudent) && !empty($idstdStudent) && !empty($nameStudent) && !empty($emailStudent) && !empty($passwordStudent)) {

            // Insert the new student into the database with 'status_student' set to 'รออนุมัติ'
            $stmt = $conn->prepare("INSERT INTO student (id_studyplan, prefix_student, name_student,prefix_studentEng, name_studentEng, idstd_student, major_student, branch_student, abbreviate_student, address_student, email_student, tel_student, password_student, status_student) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)");

            $status_student = 'รออนุมัติ';  // Default value to 'รออนุมัติ'
            $stmt->bind_param("isssssssssssss", $studyplanStudent, $prefixStudent, $nameStudent, $prefixStudentEng, $nameStudentEng, $idstdStudent, $majorStudent, $branchStudent, $abbreviateStudent, $addressStudent, $emailStudent, $telStudent, $passwordStudent, $status_student);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "New record created successfully, awaiting approval."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
            }

            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบถ้วน!"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบถ้วน!"]);
    }

    $conn->close();
}
