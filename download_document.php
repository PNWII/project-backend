<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="document.pdf"');
echo $row['document_gs10report'];  // ตรวจสอบให้แน่ใจว่าไฟล์ที่ส่งกลับเป็นข้อมูล PDF ที่ถูกต้อง



// Include database connection
include('db_connection.php');

// ตรวจสอบว่า 'id' และ 'type' ถูกส่งมาใน request หรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(["error" => "กรุณาระบุรหัสเอกสาร"]);
    exit;
}

if (!isset($_GET['type']) || empty($_GET['type'])) {
    echo json_encode(["error" => "กรุณาระบุประเภทเอกสาร (gs10report หรือ gs11report)"]);
    exit;
}

$id = $_GET['id']; // รับค่า ID จาก request
$type = $_GET['type']; // รับค่าประเภทเอกสารจาก request

// ตรวจสอบประเภทเอกสารว่าเป็น gs10report หรือ gs11report
if ($type != 'gs10report' && $type != 'gs11report') {
    echo json_encode(["error" => "ประเภทเอกสารไม่ถูกต้อง"]);
    exit;
}

try {
    // เตรียม SQL query เพื่อดึงข้อมูลเอกสารจากฐานข้อมูลตามประเภท
    $stmt = $conn->prepare("
        SELECT 
            f.formsubmit_id,
            gs10.document_gs10report,
            gs11.document_gs11report
        FROM 
            formsubmit f
        LEFT JOIN 
            gs10report gs10 ON f.formsubmit_dataform = gs10.id_gs10report
        LEFT JOIN 
            gs11report gs11 ON f.formsubmit_dataform = gs11.id_gs11report
        WHERE f.formsubmit_id = ?");

    // Bind the ID parameter to the SQL query
    $stmt->bind_param("s", $id); // 's' คือการระบุว่า parameter นี้เป็น string

    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    // ตรวจสอบว่ามีข้อมูลหรือไม่
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // ส่งไฟล์ PDF ตามประเภทเอกสารที่เลือก
        if ($type == 'gs10report' && isset($row['document_gs10report']) && !empty($row['document_gs10report'])) {
            ob_clean(); // ทำความสะอาด buffer
            flush();    // ส่งข้อมูลออกทันที
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="document_gs10report.pdf"');
            header('Content-Length: ' . strlen($row['document_gs10report']));
            echo $row['document_gs10report']; // ส่งข้อมูล BLOB ไปยังผู้ใช้
        } elseif ($type == 'gs11report' && isset($row['document_gs11report']) && !empty($row['document_gs11report'])) {
            ob_clean(); // ทำความสะอาด buffer
            flush();    // ส่งข้อมูลออกทันที
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="document_gs11report.pdf"');
            header('Content-Length: ' . strlen($row['document_gs11report']));
            echo $row['document_gs11report']; // ส่งข้อมูล BLOB ไปยังผู้ใช้
        } else {
            echo json_encode(["error" => "ไม่พบไฟล์ PDF สำหรับประเภทเอกสารที่เลือก"]);
        }
    } else {
        echo json_encode(["error" => "ไม่พบข้อมูลเอกสาร"]);
    }
} catch (Exception $e) {
    // จัดการข้อผิดพลาด
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
var_dump($_GET); // ตรวจสอบค่าที่ได้รับจาก URL หรือ request


// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>