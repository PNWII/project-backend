<?php
// ไฟล์ backend/uploadFile.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่ามีไฟล์และ student ID ถูกส่งมาหรือไม่
    if (isset($_FILES['file']) && isset($_POST['idstd_student'])) {
        $file = $_FILES['file'];
        $upload_dir = "uploads/";
        $file_path = $upload_dir . basename($file['name']);

        // ตรวจสอบและสร้างโฟลเดอร์สำหรับอัปโหลดหากยังไม่มี
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // ย้ายไฟล์ไปยังเซิร์ฟเวอร์
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // ส่งข้อมูลกลับไปยัง client
            echo json_encode([
                "success" => true,
                "message" => "File uploaded successfully",
                "filePath" => $file_path // ส่งคืน path ของไฟล์
            ]);
        } else {
            echo json_encode(["success" => false, "error" => "File upload failed"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "No file or student ID provided"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
}
?>