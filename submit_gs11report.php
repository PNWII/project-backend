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

$idstdStudent = $data["idstd_student"];
$projectType = $data['projectType_gs11report'];
$projectThai = $data['projectThai_gs11report'];
$projectEng = $data['projectEng_gs11report'];
$datepicker = $data['date_gs11report'];
$subjects = $data['subjects_gs11report'];
$gpa = $data['gpa_gs11report'];
$subjectsProject = $data['subjectsProject_gs11report'];
$advisorMain = $data['advisorMain_gs11report'];
$advisorSecond = $data['advisorSecond_gs11report'];
$docGs10rp = isset($data['docGs10rp_gs11report']) && is_array($data['docGs10rp_gs11report']) 
    ? implode(",", $data['docGs10rp_gs11report']) 
    : (is_string($data['docGs10rp_gs11report']) ? $data['docGs10rp_gs11report'] : '');

$docProjectdetails = isset($data['docProjectdetails_gs11report']) && is_array($data['docProjectdetails_gs11report']) 
    ? implode(",", $data['docProjectdetails_gs11report']) 
    : (is_string($data['docProjectdetails_gs11report']) ? $data['docProjectdetails_gs11report'] : '');
$signature = $data['signature_gs11report'];
$signName = $data['signName_gs11report'];

// Check if student exists
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

// Prepare the INSERT query for gs11report
$insertQuery = "INSERT INTO gs11report (idstd_student, projectType_gs11report, projectThai_gs11report, projectEng_gs11report, date_gs11report, subjects_gs11report, gpa_gs11report, subjectsProject_gs11report, advisorMain_gs11report, advisorSecond_gs11report, docGs10rp_gs11report, docProjectdetails_gs11report, signature_gs11report, signName_gs11report) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmtInsert = $conn->prepare($insertQuery);
if (!$stmtInsert) {
    echo json_encode(["status" => "error", "message" => "SQL Preparation error: " . $conn->error]);
    $stmtCheck->close();
    $conn->close();
    exit();
}

// Bind parameters for the query
$stmtInsert->bind_param("sssssidissssss", $idstdStudent, $projectType, $projectThai, $projectEng, $datepicker, $subjects, $gpa, $subjectsProject, $advisorMain, $advisorSecond, $docGs10rp, $docProjectdetails, $signature, $signName);

if ($stmtInsert->execute()) {
    $gs11reportId = $stmtInsert->insert_id;

    // Insert into formsubmit table
    $insertFormQuery = "INSERT INTO formsubmit (formsubmit_type, idstd_student, formsubmit_dataform) VALUES (?, ?, ?)";
    $stmtInsertForm = $conn->prepare($insertFormQuery);
    
    if (!$stmtInsertForm) {
        echo json_encode(["status" => "error", "message" => "SQL Preparation error for formsubmit: " . $conn->error]);
        $stmtInsert->close();
        $stmtCheck->close();
        $conn->close();
        exit();
    }

    $formType = 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ';
    $formData = $gs11reportId;

    $stmtInsertForm->bind_param("ssi", $formType, $idstdStudent, $formData);

    if ($stmtInsertForm->execute()) {
        $response = ["status" => "success", "message" => "Record inserted successfully into gs11report and formsubmit"];
    } else {
        $response = ["status" => "error", "message" => "Execution error in formsubmit: " . $stmtInsertForm->error];
    }
} else {
    $response = ["status" => "error", "message" => "Execution error in gs11report insertion: " . $stmtInsert->error];
}

// Close the connections
$stmtInsertForm->close();
$stmtInsert->close();
$stmtCheck->close();
$conn->close();

echo json_encode($response);
exit();

?>