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
$projectType = $data['projectType_gs15report'];
$projectThai = $data['projectThai_gs15report'];
$projectEng = $data['projectEng_gs15report'];
$projectApprovalDate = $data['projectApprovalDate_gs15report'];
$projectProgressDate = $data['projectProgressDate_gs15report'];
$defenseRequestDate = $data['defenseRequestDate_gs15report'];
$defenseRequestTime = $data['defenseRequestTime_gs15report'];
$defenseRequestRoom = $data['defenseRequestRoom_gs15report'];
$defenseRequestFloor = $data['defenseRequestFloor_gs15report'];
$defenseRequestBuilding = $data['defenseRequestBuilding_gs15report'];
$datepicker = $data['date_gs15report'];
$advisorMain = $data['advisorMain_gs15report'];
$advisorSecond = $data['advisorSecond_gs15report'];
$courseCredits = $data['courseCredits_gs15report'];
$signature = $data['signature_gs15report'];
$signName = $data['signName_gs15report'];
$cumulativeGPA = $data['cumulativeGPA_gs15report'];
$thesisCredits = $data['thesisCredits_gs15report'];
$docGs40rpGs41rp = $data['docGs40rpGs41rp_gs15report'];
$docGs50rp = $data['docGs50rp_gs15report'];
$docThesisExamCopy = $data['docThesisExamCopy_gs15report'];
$thesisDefenseDoc = $data['thesisDefenseDoc_gs15report'];

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
$insertQuery = "INSERT INTO gs15report (idstd_student,
    projectType_gs15report,
    projectThai_gs15report,
    projectEng_gs15report,
    projectApprovalDate_gs15report,
    projectProgressDate_gs15report,
    defenseRequestDate_gs15report,
    defenseRequestTime_gs15report,
    defenseRequestRoom_gs15report,
    defenseRequestFloor_gs15report,
    defenseRequestBuilding_gs15report,
    date_gs15report,
    advisorMain_gs15report,
    advisorSecond_gs15report,
    courseCredits_gs15report,
    signature_gs15report,
    signName_gs15report,
    cumulativeGPA_gs15report,
    thesisCredits_gs15report,
    docGs40rpGs41rp_gs15report,
    docGs50rp_gs15report,
    docThesisExamCopy_gs15report,
    thesisDefenseDoc_gs15report) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmtInsert = $conn->prepare($insertQuery);
if (!$stmtInsert) {
    echo json_encode(["status" => "error", "message" => "SQL Preparation error: " . $conn->error]);
    $stmtCheck->close();
    $conn->close();
    exit();
}

// ตรวจสอบว่าตัวแปรถูกส่งตามลำดับที่ถูกต้อง
$stmtInsert->bind_param(
    "sssssssssssssssssssssss",
    $idstdStudent,
    $projectType,
    $projectThai,
    $projectEng,
    $projectApprovalDate,
    $projectProgressDate,
    $defenseRequestDate,
    $defenseRequestTime,
    $defenseRequestRoom,
    $defenseRequestFloor,
    $defenseRequestBuilding,
    $datepicker,
    $advisorMain,
    $advisorSecond,
    $courseCredits,
    $signature,
    $signName,
    $cumulativeGPA,
    $thesisCredits,
    $docGs40rpGs41rp,
    $docGs50rp,
    $docThesisExamCopy,
    $thesisDefenseDoc
);



if ($stmtInsert->execute()) {
    $gs15reportId = $stmtInsert->insert_id;

    // หลังจากข้อมูลใน gs15report ถูกบันทึกแล้ว ส่งข้อมูลไปที่ 
    $insertFormQuery = "INSERT INTO formsubmit (formsubmit_type, idstd_student, formsubmit_dataform) VALUES (?, ?, ?)";
    $stmtInsertForm = $conn->prepare($insertFormQuery);
    if (!$stmtInsertForm) {
        echo json_encode(["status" => "error", "message" => "SQL Preparation error for formgs15report_submissions: " . $conn->error]);
        $stmtInsert->close();
        $stmtCheck->close();
        $conn->close();
        exit();
    }

    $formType = 'คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ';
    $formData = $gs15reportId;
    $stmtInsertForm->bind_param("sss", $formType, $idstdStudent, $formData);

    if ($stmtInsertForm->execute()) {
        $response = ["status" => "success", "message" => "Record inserted successfully into gs15report and formsubmit"];
    } else {
        $response = ["status" => "error", "message" => "Execution error in formsubmit: " . $stmtInsertForm->error];
    }
} else {
    $response = ["status" => "error", "message" => "Execution error in gs15report insertion: " . $stmtInsert->error];
}
// ปิดการเชื่อมต่อฐานข้อมูล
$stmtInsertForm->close();
$stmtInsert->close();
$stmtCheck->close();
$conn->close();

echo json_encode($response);
exit();
?>