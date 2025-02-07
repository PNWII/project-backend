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
$projectType = $data['projectType_gs14report'];
$projectThai = $data['projectThai_gs14report'];
$projectEng = $data['projectEng_gs14report'];
$projectApprovalDate = $data['projectApprovalDate_gs14report'];
$progressExamRequestDate = $data['progressExamRequestDate_gs14report'];
$progressExamRequestTime = $data['progressExamRequestTime_gs14report'];
$progressExamRequestRoom = $data['progressExamRequestRoom_gs14report'];
$progressExamRequestFloor = $data['progressExamRequestFloor_gs14report'];
$progressExamRequestBuilding = $data['progressExamRequestBuilding_gs14report'];
$datepicker = $data['date_gs14report'];
$advisorMain = $data['advisorMain_gs14report'];
$advisorSecond = $data['advisorSecond_gs14report'];
$docProjectdetailsGs22rp = $data['docProjectdetailsGs22rp_gs14report'];
$signature = $data['signature_gs14report'];
$signName = $data['signName_gs14report'];

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
$insertQuery = "INSERT INTO gs14report (idstd_student,
    projectType_gs14report,
    projectThai_gs14report,
    projectEng_gs14report,
    projectApprovalDate_gs14report,
    progressExamRequestDate_gs14report,
    progressExamRequestTime_gs14report,
    progressExamRequestRoom_gs14report,
    progressExamRequestFloor_gs14report,
    progressExamRequestBuilding_gs14report,
    date_gs14report,
    advisorMain_gs14report,
    advisorSecond_gs14report,
    docProjectdetailsGs22rp_gs14report,
    signature_gs14report,
    signName_gs14report) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmtInsert = $conn->prepare($insertQuery);
if (!$stmtInsert) {
    echo json_encode(["status" => "error", "message" => "SQL Preparation error: " . $conn->error]);
    $stmtCheck->close();
    $conn->close();
    exit();
}

// ตรวจสอบว่าตัวแปรถูกส่งตามลำดับที่ถูกต้อง
$stmtInsert->bind_param(
    "ssssssssssssssss",
    $idstdStudent,
    $projectType,
    $projectThai,
    $projectEng,
    $projectApprovalDate,
    $progressExamRequestDate,
    $progressExamRequestTime,
    $progressExamRequestRoom,
    $progressExamRequestFloor,
    $progressExamRequestBuilding,
    $datepicker,
    $advisorMain,
    $advisorSecond,
    $docProjectdetailsGs22rp,
    $signature,
    $signName
);



if ($stmtInsert->execute()) {
    $gs14reportId = $stmtInsert->insert_id;

    // หลังจากข้อมูลใน gs14report ถูกบันทึกแล้ว ส่งข้อมูลไปที่ 
    $insertFormQuery = "INSERT INTO formsubmit (formsubmit_type, idstd_student, formsubmit_dataform) VALUES (?, ?, ?)";
    $stmtInsertForm = $conn->prepare($insertFormQuery);
    if (!$stmtInsertForm) {
        echo json_encode(["status" => "error", "message" => "SQL Preparation error for formgs14report_submissions: " . $conn->error]);
        $stmtInsert->close();
        $stmtCheck->close();
        $conn->close();
        exit();
    }

    $formType = 'คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ';
    $formData = $gs14reportId;
    $stmtInsertForm->bind_param("sss", $formType, $idstdStudent, $formData);

    if ($stmtInsertForm->execute()) {
        $response = ["status" => "success", "message" => "Record inserted successfully into gs14report and formsubmit"];
    } else {
        $response = ["status" => "error", "message" => "Execution error in formsubmit: " . $stmtInsertForm->error];
    }
} else {
    $response = ["status" => "error", "message" => "Execution error in gs14report insertion: " . $stmtInsert->error];
}
// ปิดการเชื่อมต่อฐานข้อมูล
$stmtInsertForm->close();
$stmtInsert->close();
$stmtCheck->close();
$conn->close();

echo json_encode($response);
exit();
?>