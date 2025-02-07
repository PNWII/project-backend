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
$projectType = $data['projectType_gs12report'];
$projectThai = $data['projectThai_gs12report'];
$projectEng = $data['projectEng_gs12report'];
//$examRequestDateTime = $data['examRequestDateTime_gs12report'];
$examRequestDate = $data['examRequestDate_gs12report'];
$examRequestTime = $data['examRequestTime_gs12report'];
$examRequestRoom = $data['examRequestRoom_gs12report'];
$examRequestFloor = $data['examRequestFloor_gs12report'];
$examRequestBuilding = $data['examRequestBuilding_gs12report'];
$datepicker = $data['date_gs12report'];
$advisorMain = $data['advisorMain_gs12report'];
$advisorSecond = $data['advisorSecond_gs12report'];
$docProjectdetailsGs20rp = $data['docProjectdetailsGs20rp_gs12report'];
$signature = $data['signature_gs12report'];
$signName = $data['signName_gs12report'];

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
$insertQuery = "INSERT INTO gs12report (idstd_student,
    projectType_gs12report,
    projectThai_gs12report,
    projectEng_gs12report,
    examRequestDate_gs12report,
    examRequestTime_gs12report,
    examRequestRoom_gs12report,
    examRequestFloor_gs12report,
    examRequestBuilding_gs12report,
    date_gs12report,
    advisorMain_gs12report,
    advisorSecond_gs12report,
    docProjectdetailsGs20rp_gs12report,
    signature_gs12report,
    signName_gs12report) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmtInsert = $conn->prepare($insertQuery);
if (!$stmtInsert) {
    echo json_encode(["status" => "error", "message" => "SQL Preparation error: " . $conn->error]);
    $stmtCheck->close();
    $conn->close();
    exit();
}

// ตรวจสอบว่าตัวแปรถูกส่งตามลำดับที่ถูกต้อง
$stmtInsert->bind_param(
    "sssssssssssssss",
    $idstdStudent,
    $projectType,
    $projectThai,
    $projectEng,
    $examRequestDate,
    $examRequestTime,
    $examRequestRoom,
    $examRequestFloor,
    $examRequestBuilding,
    $datepicker,
    $advisorMain,
    $advisorSecond,
    $docProjectdetailsGs20rp,
    $signature,
    $signName
);



if ($stmtInsert->execute()) {
    $gs12reportId = $stmtInsert->insert_id;

    // หลังจากข้อมูลใน gs12report ถูกบันทึกแล้ว ส่งข้อมูลไปที่ 
    $insertFormQuery = "INSERT INTO formsubmit (formsubmit_type, idstd_student, formsubmit_dataform) VALUES (?, ?, ?)";
    $stmtInsertForm = $conn->prepare($insertFormQuery);
    if (!$stmtInsertForm) {
        echo json_encode(["status" => "error", "message" => "SQL Preparation error for formgs12report_submissions: " . $conn->error]);
        $stmtInsert->close();
        $stmtCheck->close();
        $conn->close();
        exit();
    }

    $formType = 'คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ';
    $formData = $gs12reportId;
    $stmtInsertForm->bind_param("sss", $formType, $idstdStudent, $formData);

    if ($stmtInsertForm->execute()) {
        $response = ["status" => "success", "message" => "Record inserted successfully into gs12report and formsubmit"];
    } else {
        $response = ["status" => "error", "message" => "Execution error in formsubmit: " . $stmtInsertForm->error];
    }
} else {
    $response = ["status" => "error", "message" => "Execution error in gs12report insertion: " . $stmtInsert->error];
}
// ปิดการเชื่อมต่อฐานข้อมูล
$stmtInsertForm->close();
$stmtInsert->close();
$stmtCheck->close();
$conn->close();

echo json_encode($response);
exit();
?>