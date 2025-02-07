<?php
// ไฟล์ backend/RejectDocument-Teacher.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

// เริ่มต้นการทำงานใน try block
try {
    // รับข้อมูล JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // ตรวจสอบว่า JSON ถูกต้องหรือไม่
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }

    // Log ข้อมูลที่ได้รับ
    error_log("Input Data: " . print_r($input, true));

    // ตรวจสอบข้อมูลที่จำเป็น
    $docId = $input['id'] ?? null;
    $type = $input['type'] ?? null;
    $signature = $input['signature'] ?? null;
    $comment = $input['comment'] ?? null;
    $name = $input['name'] ?? null;
    $teacherId = $input['teacherId'] ?? null;


    // เช็คว่ามี teacher ที่มี id_teacher นี้ในฐานข้อมูลหรือไม่
    // Prepare query for checking teacher existence
    $stmtCheck = $conn->prepare("SELECT id_teacher FROM teacher WHERE id_teacher = ? LIMIT 1");
    if ($stmtCheck === false) {
        error_log("Error preparing statement: " . $conn->error);
        throw new Exception("Error preparing SQL for teacher check: " . $conn->error);
    }

    // Bind parameters
    $stmtCheck->bind_param("i", $teacherId);

    // Execute statement
    if (!$stmtCheck->execute()) {
        throw new Exception("Error executing teacher check SQL: " . $stmtCheck->error);
    }

    // ตรวจสอบว่า teacherId ที่ส่งมามีในฐานข้อมูลหรือไม่
    $result = $stmtCheck->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Teacher ID not found in 'teacher' table");
    }

    // เริ่มการทำงานกับฐานข้อมูล
    $conn->begin_transaction();

    // เพิ่มข้อมูลลงในตาราง teachersigna
    $sql = "INSERT INTO teachersigna (id_teacher, teachersign_nameDocs, teachersign_IdDocs,teachersign_nameTeacher, teachersign_description, teachersign_sign, teachersign_status) 
            VALUES (?, ?, ?, ?, ?,?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error preparing INSERT SQL: " . $conn->error);
    }

    // กำหนดค่าตัวแปรให้กับ SQL
    $status = "ได้รับการอนุมัติจากครูอาจารย์ที่ปรึกษาแล้ว"; // สถานะ
    $stmt->bind_param("issssss", $teacherId, $type, $docId,$name, $comment, $signature, $status);

    // Execute INSERT statement
    if (!$stmt->execute()) {
        throw new Exception("Error executing INSERT SQL: " . $stmt->error);
    }


    // Commit การทำงาน
    $conn->commit();

    // ส่งผลลัพธ์กลับไปยัง Client
    echo json_encode(["status" => "success", "message" => "Document approved"]);

} catch (Exception $e) {
    // Rollback ในกรณีที่มีข้อผิดพลาด
    $conn->rollback();
    // ส่งข้อความข้อผิดพลาดกลับไปยัง Client
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    // ปิดการเชื่อมต่อฐานข้อมูล
    $conn->close();
}
?>