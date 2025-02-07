<?php
header('Content-Type: application/json;');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Origin: *");


include "db_connection.php";

$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit();
}

if (!isset($data["idstd_student"])) {
    echo json_encode(["status" => "error", "message" => "Missing 'idstd_student'"]);
    exit();
}

$idstdStudent = $data['idstd_student'];
$datepicker = $data['date_gs18report'];
$signature = $data['signature_gs18report'];
$signName = $data['signName_gs18report'];
$thesisAdvisor = $data['thesisAdvisor_gs18report'];
$semesterAt = $data['semesterAt_gs18report'];
$academicYear = $data['academicYear_gs18report'];
$examRoundProject = $data['examRoundProject_gs18report'];
$courseCredits = $data['courseCredits_gs18report'];
$cumulativeGPA = $data['cumulativeGPA_gs18report'];
$docGs41rp = $data['docGs41rp_gs18report'];


// เช็คว่ามี student ที่มี idstd_student นี้ในฐานข้อมูลหรือไม่
$checkIdStudentQuery = "SELECT idstd_student FROM student WHERE idstd_student = ? LIMIT 1";
$stmtCheck = $conn->prepare($checkIdStudentQuery);
if (!$stmtCheck) {
    echo json_encode(["status" => "error", "message" => "SQL Preparation error: " . $conn->error]);
    $conn->close();
    exit();
}

$stmtCheck->bind_param("s", $idstdStudent);
$stmtCheck->execute();
$result = $stmtCheck->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Student ID not found in 'student' table"]);
    $stmtCheck->close();
    $conn->close();
    exit();

}


// การเตรียมคำสั่ง INSERT ข้อมูล
$insertQuery = "INSERT INTO gs18report (idstd_student,
    date_gs18report,
    signature_gs18report,
    signName_gs18report,
    thesisAdvisor_gs18report,
    semesterAt_gs18report,
    academicYear_gs18report,
    examRoundProject_gs18report,
    courseCredits_gs18report,
    cumulativeGPA_gs18report,
    docGs41rp_gs18report) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?)";
$stmtInsert = $conn->prepare($insertQuery);
if (!$stmtInsert) {
    echo json_encode(["status" => "error", "message" => "SQL Preparation error: " . $conn->error]);
    $stmtCheck->close();
    $conn->close();
    exit();
}

// ตรวจสอบว่าตัวแปรถูกส่งตามลำดับที่ถูกต้อง
$stmtInsert->bind_param(
    "sssssssssss",
    $idstdStudent,
    $datepicker,
    $signature,
    $signName,
    $thesisAdvisor,
    $semesterAt,
    $academicYear,
    $examRoundProject,
    $courseCredits,
    $cumulativeGPA,
    $docGs41rp
);



if ($stmtInsert->execute()) {
    $gs18reportId = $stmtInsert->insert_id;

    // หลังจากข้อมูลใน gs18report ถูกบันทึกแล้ว ส่งข้อมูลไปที่ 
    $insertFormQuery = "INSERT INTO formsubmit (formsubmit_type, idstd_student, formsubmit_dataform) VALUES (?, ?, ?)";
    $stmtInsertForm = $conn->prepare($insertFormQuery);
    if (!$stmtInsertForm) {
        echo json_encode(["status" => "error", "message" => "SQL Preparation error for formgs18report_submissions: " . $conn->error]);
        $stmtInsert->close();
        $stmtCheck->close();
        $conn->close();
        exit();
    }

    $formType = 'คคอ. บว. 18 แบบขอสอบประมวลความรู้';
    $formData = $gs18reportId;
    $stmtInsertForm->bind_param("sss", $formType, $idstdStudent, $formData);

    if ($stmtInsertForm->execute()) {
        $response = ["status" => "success", "message" => "Record inserted successfully into gs18report and formsubmit"];
    } else {
        $response = ["status" => "error", "message" => "Execution error in formsubmit: " . $stmtInsertForm->error];
    }
} else {
    $response = ["status" => "error", "message" => "Execution error in gs18report insertion: " . $stmtInsert->error];
}
// ปิดการเชื่อมต่อฐานข้อมูล
$stmtInsertForm->close();
$stmtInsert->close();
$stmtCheck->close();
$conn->close();

echo json_encode($response);
exit();
?>