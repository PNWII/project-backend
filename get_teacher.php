<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

$input = json_decode(file_get_contents("php://input"), true);

$IDTeacher = isset($input['IDTeacher']) ? trim($input['IDTeacher']) : null;

if ($IDTeacher) {
    $stmtTeacher = $conn->prepare("SELECT prefix_teacher, email_teacher, tel_teacher, name_teacher, type_teacher FROM teacher WHERE id_teacher = ?");
    $stmtTeacher->bind_param("s", $IDTeacher);
    $stmtTeacher->execute();
    $stmtTeacher->bind_result($prefixTeacher, $emailTeacher, $telTeacher, $nameTeacher, $role);
    $stmtTeacher->fetch();

    if ($nameTeacher) {
        echo json_encode([
            "status" => "success",
            "prefix" => $prefixTeacher,
            "name" => $nameTeacher,
            "email" => $emailTeacher,
            "tel" => $telTeacher,
            "role" => $role
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "ไม่พบข้อมูลอาจารย์"
        ]);
    }
    $stmtTeacher->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "ข้อมูล ID ไม่ถูกต้อง"
    ]);
}
?>