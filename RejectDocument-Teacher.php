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
    $status = "ถูกปฏิเสธจากครูอาจารย์ที่ปรึกษาแล้ว"; // สถานะ
    $stmt->bind_param("issssss", $teacherId, $type, $docId, $name, $comment, $signature, $status);

    // Execute INSERT statement
    if (!$stmt->execute()) {
        throw new Exception("Error executing INSERT SQL: " . $stmt->error);
    }
    // UPDATE ตาราง gs10report
    $sqlUpdateGS10 = "UPDATE gs10report SET status_gs10report = ? WHERE id_gs10report = ?";
    $stmtUpdateGS10 = $conn->prepare($sqlUpdateGS10);
    if (!$stmtUpdateGS10)
        throw new Exception("Error preparing UPDATE SQL for gs10report: " . $conn->error);

    $stmtUpdateGS10->bind_param("si", $status, $docId);
    $stmtUpdateGS10->execute();

    // UPDATE ตาราง gs11report
    $sqlUpdateGS11 = "UPDATE gs11report SET status_gs11report = ? WHERE id_gs11report = ?";
    $stmtUpdateGS11 = $conn->prepare($sqlUpdateGS11);
    if (!$stmtUpdateGS11)
        throw new Exception("Error preparing UPDATE SQL for gs11report: " . $conn->error);

    $stmtUpdateGS11->bind_param("si", $status, $docId);
    $stmtUpdateGS11->execute();

    // UPDATE ตาราง gs12report
    $sqlUpdateGS12 = "UPDATE gs12report SET status_gs12report = ? WHERE id_gs12report = ?";
    $stmtUpdateGS12 = $conn->prepare($sqlUpdateGS12);
    if (!$stmtUpdateGS12)
        throw new Exception("Error preparing UPDATE SQL for gs12report: " . $conn->error);

    $stmtUpdateGS12->bind_param("si", $status, $docId);
    $stmtUpdateGS12->execute();

    // UPDATE ตาราง gs13report
    $sqlUpdateGS13 = "UPDATE gs13report SET status_gs13report = ? WHERE id_gs13report = ?";
    $stmtUpdateGS13 = $conn->prepare($sqlUpdateGS13);
    if (!$stmtUpdateGS13)
        throw new Exception("Error preparing UPDATE SQL for gs13report: " . $conn->error);

    $stmtUpdateGS13->bind_param("si", $status, $docId);
    $stmtUpdateGS13->execute();

    // UPDATE ตาราง gs14report
    $sqlUpdateGS14 = "UPDATE gs14report SET status_gs14report = ? WHERE id_gs14report = ?";
    $stmtUpdateGS14 = $conn->prepare($sqlUpdateGS14);
    if (!$stmtUpdateGS14)
        throw new Exception("Error preparing UPDATE SQL for gs14report: " . $conn->error);

    $stmtUpdateGS14->bind_param("si", $status, $docId);
    $stmtUpdateGS14->execute();

    // UPDATE ตาราง gs15report
    $sqlUpdateGS15 = "UPDATE gs15report SET status_gs15report = ? WHERE id_gs15report = ?";
    $stmtUpdateGS15 = $conn->prepare($sqlUpdateGS15);
    if (!$stmtUpdateGS15)
        throw new Exception("Error preparing UPDATE SQL for gs15report: " . $conn->error);

    $stmtUpdateGS15->bind_param("si", $status, $docId);
    $stmtUpdateGS15->execute();

    // UPDATE ตาราง gs16report
    $sqlUpdateGS16 = "UPDATE gs16report SET status_gs16report = ? WHERE id_gs16report = ?";
    $stmtUpdateGS16 = $conn->prepare($sqlUpdateGS16);
    if (!$stmtUpdateGS16)
        throw new Exception("Error preparing UPDATE SQL for gs16report: " . $conn->error);
   
    $stmtUpdateGS16->bind_param("si", $status, $docId);
    $stmtUpdateGS16->execute();

       // UPDATE ตาราง gs17report
       $sqlUpdateGS17 = "UPDATE gs17report SET status_gs17report = ? WHERE id_gs17report = ?";
       $stmtUpdateGS17 = $conn->prepare($sqlUpdateGS17);
       if (!$stmtUpdateGS17)
           throw new Exception("Error preparing UPDATE SQL for gs17report: " . $conn->error);
   
       $stmtUpdateGS17->bind_param("si", $status, $docId);
       $stmtUpdateGS17->execute();

          // UPDATE ตาราง gs18report
    $sqlUpdateGS18 = "UPDATE gs18report SET status_gs18report = ? WHERE id_gs18report = ?";
    $stmtUpdateGS18 = $conn->prepare($sqlUpdateGS18);
    if (!$stmtUpdateGS18)
        throw new Exception("Error preparing UPDATE SQL for gs18report: " . $conn->error);

    $stmtUpdateGS18->bind_param("si", $status, $docId);
    $stmtUpdateGS18->execute();

       // UPDATE ตาราง gs19report
       $sqlUpdateGS19 = "UPDATE gs19report SET status_gs19report = ? WHERE id_gs19report = ?";
       $stmtUpdateGS19 = $conn->prepare($sqlUpdateGS19);
       if (!$stmtUpdateGS19)
           throw new Exception("Error preparing UPDATE SQL for gs19report: " . $conn->error);
   
       $stmtUpdateGS19->bind_param("si", $status, $docId);
       $stmtUpdateGS19->execute();

          // UPDATE ตาราง gs23report
    $sqlUpdateGS23 = "UPDATE gs23report SET status_gs23report = ? WHERE id_gs23report = ?";
    $stmtUpdateGS23 = $conn->prepare($sqlUpdateGS23);
    if (!$stmtUpdateGS23)
        throw new Exception("Error preparing UPDATE SQL for gs23report: " . $conn->error);

    $stmtUpdateGS23->bind_param("si", $status, $docId);
    $stmtUpdateGS23->execute();

    // UPDATE ตาราง formsubmit
    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_type = ? AND formsubmit_dataform = ?";
    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
    if (!$stmtUpdateFormsubmit) {
        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
    }

    $formsubmitStatus = "ถูกปฏิเสธจากอาจารย์ที่ปรึกษา";

    // แก้ไขการใช้ bind_param ให้ตรงกับจำนวนและประเภทของตัวแปร
    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $type, $docId);
    if (!$stmtUpdateFormsubmit->execute()) {
        throw new Exception("Error executing UPDATE for formsubmit: " . $stmtUpdateFormsubmit->error);
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