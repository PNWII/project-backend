<?php
// ไฟล์ backend/uploadFile11.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['files']) && isset($_POST['idstd_student'])) {
        $idstdStudent = $_POST['idstd_student'];
        $files = $_FILES['files'];
        $upload_dir = "uploads/";

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $uploaded_files = [];
        foreach ($files['name'] as $index => $fileName) {
            $fileTmpPath = $files['tmp_name'][$index];
            $filePath = $upload_dir . basename($fileName);

            // Move the uploaded file to the desired location
            if (move_uploaded_file($fileTmpPath, $filePath)) {
                // Save only the file path to the array
                $uploaded_files[] = $filePath;
            } else {
                echo json_encode(["error" => "Failed to upload file: $fileName"]);
                exit();
            }
        }

        // ส่งแค่ filePath ในรูปแบบ array ของ string
        echo json_encode([
            "success" => true,
            "message" => "Files uploaded successfully",
            "uploadedFiles" => $uploaded_files, // ส่งแค่ filePath เท่านั้น
        ]);
    } else {
        echo json_encode(["error" => "No files or student ID provided"]);
    }
}
?>