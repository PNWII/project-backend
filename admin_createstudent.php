<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('db_connection.php');

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);

// รับข้อมูลจาก POST
$idstd_student = isset($data['idstd_student']) ? $data['idstd_student'] : null;
$name_student = isset($data['name_student']) ? $data['name_student'] : null;
$name_studentEng = isset($data['name_studentEng']) ? $data['name_studentEng'] : null;
$email_student = isset($data['email_student']) ? $data['email_student'] : null;
$prefix_student = isset($data['prefix_student']) ? $data['prefix_student'] : null;
$prefix_studentEng = isset($data['prefix_studentEng']) ? $data['prefix_studentEng'] : null;
$branch_student = isset($data['branch_student']) ? $data['branch_student'] : null;
$major_student = isset($data['major_student']) ? $data['major_student'] : null;
$address_student = isset($data['address_student']) ? $data['address_student'] : null;
$tel_student = isset($data['tel_student']) ? $data['tel_student'] : null;
$password_student = isset($data['password_student']) ? $data['password_student'] : null;
$studyplan_student = isset($data['studyplan_student']) ? $data['studyplan_student'] : null;
$abbreviate_student = isset($data['abbreviate_student']) ? $data['abbreviate_student'] : null;

if (!$idstd_student || !$name_student || !$email_student || !$password_student || !$studyplan_student) {
    echo json_encode(["status" => "error", "message" => "Required fields are missing!"]);
    exit();
}

// Check if the student ID or email already exists
$checkDuplicate = "SELECT * FROM student WHERE idstd_student = ? OR email_student = ? LIMIT 1";
$stmt = $conn->prepare($checkDuplicate);
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Error preparing checkDuplicate query: " . $conn->error]);
    exit();
}
$stmt->bind_param("ss", $idstd_student, $email_student);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // If there's a match, send an error response
    echo json_encode(["status" => "error", "message" => "รหัสนักศึกษาหรืออีเมลนี้ลงทะเบียนแล้ว"]);
    exit();
}

// Check if the selected study plan exists
$checkStudyplan = "SELECT id_studyplan FROM studyplan WHERE id_studyplan = ? LIMIT 1";
$stmt = $conn->prepare($checkStudyplan);
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Error preparing checkStudyplan query: " . $conn->error]);
    exit();
}
$stmt->bind_param("s", $studyplan_student);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "The selected study plan does not exist!"]);
    exit();
}

// Insert the new student into the database with 'status_student' set to 'รออนุมัติ'
$stmt = $conn->prepare("INSERT INTO student (id_studyplan, prefix_student, name_student,prefix_studentEng, name_studentEng, idstd_student, major_student, branch_student, address_student, email_student, tel_student, password_student, status_student, abbreviate_student) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)");

if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Error preparing INSERT query: " . $conn->error]);
    exit();
}

$status_student = 'อนุมัติแล้ว'; 
$stmt->bind_param("ssssssssssssss", $studyplan_student, $prefix_student, $name_student,$prefix_studentEng, $name_studentEng, $idstd_student, $major_student, $branch_student, $address_student, $email_student, $tel_student, $password_student, $status_student, $abbreviate_student);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "New record created successfully, awaiting approval."]);
} else {
    // Improved error logging
    echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>