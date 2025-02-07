<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// ตรวจสอบว่าเป็น POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // รับข้อมูลจาก POST request
    $data = json_decode(file_get_contents("php://input"), true);

    // ตรวจสอบข้อมูลที่จำเป็น
    if (isset($data['usernameAdmin']) && isset($data['emailAdmin']) && isset($data['passwordAdmin'])) {
        $username_admin = $data['usernameAdmin'];
        $email_admin = $data['emailAdmin'];
        $password_admin = $data['passwordAdmin'];
    } else {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
        exit(); // หยุดการทำงานหากข้อมูลไม่ครบถ้วน
    }

    include('db_connection.php');

    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // ตรวจสอบว่า usernameAdmin และ emailAdmin ตรงกับข้อมูลในฐานข้อมูลหรือไม่
    $sql = "SELECT * FROM admin WHERE username_admin = ? AND email_admin = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('MySQL prepare statement error: ' . $conn->error);
    }

    $stmt->bind_param("ss", $username_admin, $email_admin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ผู้ใช้พบในฐานข้อมูลแล้ว ตรวจสอบรหัสผ่าน
        $row = $result->fetch_assoc();
        if (password_verify($passwordAdmin, $row['passwordAdmin'])) {
            // รหัสผ่านถูกต้อง
            echo json_encode(["status" => "success", "message" => "เข้าสู่ระบบสำเร็จ"]);
        } else {
            // รหัสผ่านไม่ถูกต้อง
            echo json_encode(["status" => "error", "message" => "รหัสผ่านไม่ถูกต้อง"]);
        }
    } else {
        // ไม่พบข้อมูลนักศึกษาในฐานข้อมูล
        echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลผู้ดูแลนี้"]);
    }

    // ปิดการเชื่อมต่อ
    $stmt->close();
    $conn->close();
} else {
    // Method ไม่ถูกต้อง
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>