<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
// ตรวจสอบว่าไฟล์ถูกส่งมาหรือไม่
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];

    // กำหนดตำแหน่งที่จะบันทึกไฟล์
    $uploadDir = 'uploads/';
    $destPath = $uploadDir . $fileName;

    // ย้ายไฟล์ไปยังโฟลเดอร์ uploads
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        echo json_encode(['success' => true, 'file' => $fileName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่มีไฟล์ที่ถูกส่งมา']);
}
?>