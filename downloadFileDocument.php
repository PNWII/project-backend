<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

include('db_connection.php');

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    die("Database connection failed");
}

// ตรวจสอบว่า 'id' และ 'filePath' ถูกส่งมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['filePath']) || empty($_GET['filePath'])) {
    echo json_encode(["error" => "กรุณาระบุรหัสเอกสารและเส้นทางไฟล์"]);
    exit;
}

$formsubmitID = $_GET['id']; // รับค่า formsubmitID จาก request
$filePath = $_GET['filePath']; // รับค่า filePath จาก request

// ตรวจสอบว่าไฟล์ที่ระบุมีอยู่ในตำแหน่งที่ระบุ
if (file_exists($filePath)) {
    $fileName = basename($filePath); // รับชื่อไฟล์จาก path

    // ตั้งค่า headers เพื่อดาวน์โหลดไฟล์
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    // อ่านไฟล์และส่งออกไป
    readfile($filePath);
    exit;
} else {
    echo json_encode(["error" => "ไม่พบไฟล์ที่ระบุ"]);
}

$conn->close();
?>