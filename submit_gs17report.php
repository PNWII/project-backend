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
$datepicker = $data['date_gs17report'];
$signature = $data['signature_gs17report'];
$signName = $data['signName_gs17report'];
$thesisAdvisor = $data['thesisAdvisor_gs17report'];
$semesterAt = $data['semesterAt_gs17report'];
$academicYear = $data['academicYear_gs17report'];
$courseCredits = $data['courseCredits_gs17report'];
$cumulativeGPA = $data['cumulativeGPA_gs17report'];
$projectDefenseDate = $data['projectDefenseDate_gs17report'];
$additionalDetails = $data['additionalDetails_gs17report'];
$docCheck15 = $data['docCheck15_gs17report'];

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
$insertQuery = "INSERT INTO gs17report (idstd_student,
    date_gs17report,
    signature_gs17report,
    signName_gs17report,
    thesisAdvisor_gs17report,
    semesterAt_gs17report,
    academicYear_gs17report,
    courseCredits_gs17report,
    cumulativeGPA_gs17report,
    projectDefenseDate_gs17report,
    additionalDetails_gs17report,
    docCheck15_gs17report) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
$stmtInsert = $conn->prepare($insertQuery);
if (!$stmtInsert) {
    echo json_encode(["status" => "error", "message" => "SQL Preparation error: " . $conn->error]);
    $stmtCheck->close();
    $conn->close();
    exit();
}

// ตรวจสอบว่าตัวแปรถูกส่งตามลำดับที่ถูกต้อง
$stmtInsert->bind_param(
    "ssssssssssss",
    $idstdStudent,
    $datepicker,
    $signature,
    $signName,
    $thesisAdvisor,
    $semesterAt,
    $academicYear,
    $courseCredits,
    $cumulativeGPA,
    $projectDefenseDate,
    $additionalDetails,
    $docCheck15
);



if ($stmtInsert->execute()) {
    $gs17reportId = $stmtInsert->insert_id;

    // หลังจากข้อมูลใน gs17report ถูกบันทึกแล้ว ส่งข้อมูลไปที่ 
    $insertFormQuery = "INSERT INTO formsubmit (formsubmit_type, idstd_student, formsubmit_dataform) VALUES (?, ?, ?)";
    $stmtInsertForm = $conn->prepare($insertFormQuery);
    if (!$stmtInsertForm) {
        echo json_encode(["status" => "error", "message" => "SQL Preparation error for formgs17report_submissions: " . $conn->error]);
        $stmtInsert->close();
        $stmtCheck->close();
        $conn->close();
        exit();
    }

    $formType = 'คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ';
    $formData = $gs17reportId;
    $stmtInsertForm->bind_param("sss", $formType, $idstdStudent, $formData);

    if ($stmtInsertForm->execute()) {
        $response = ["status" => "success", "message" => "Record inserted successfully into gs17report and formsubmit"];
    } else {
        $response = ["status" => "error", "message" => "Execution error in formsubmit: " . $stmtInsertForm->error];
    }
} else {
    $response = ["status" => "error", "message" => "Execution error in gs17report insertion: " . $stmtInsert->error];
}
// ปิดการเชื่อมต่อฐานข้อมูล
$stmtInsertForm->close();
$stmtInsert->close();
$stmtCheck->close();
$conn->close();

echo json_encode($response);
exit();
?>