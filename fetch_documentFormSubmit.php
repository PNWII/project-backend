<?php
//ไฟล์ backend/fetch_documentFormSubmit.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Include database connection
include('db_connection.php');



try {
    // เตรียม SQL สำหรับดึงข้อมูลเอกสารทั้งหมดโดยไม่ต้องใช้ status
    $stmt = $conn->prepare("
        SELECT 
            f.formsubmit_id, 
            f.formsubmit_type,
            f.idstd_student,
            f.formsubmit_dataform,
            f.formsubmit_status,
            f.formsubmit_at,
            s.name_student,
            gs10.id_gs10report,
            gs10.name_gs10report,
            gs10.projectThai_gs10report,
            gs11.projectThai_gs11report,
            gs11.id_gs11report,
            gs11.name_gs11report
        FROM 
            formsubmit f
        LEFT JOIN 
            gs10report gs10 ON f.formsubmit_dataform = gs10.id_gs10report 
            AND f.formsubmit_type = 'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 
            gs11report gs11 ON f.formsubmit_dataform = gs11.id_gs11report 
            AND f.formsubmit_type = 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 
            student s ON f.idstd_student = s.idstd_student
    ");

    // Execute SQL
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    // ดึงข้อมูลทั้งหมดในรูปแบบอาร์เรย์
    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode($documents);

} catch (Exception $e) {
    // จัดการข้อผิดพลาด
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>