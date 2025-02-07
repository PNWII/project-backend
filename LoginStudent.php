<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// ตรวจสอบว่าเป็น POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // รับข้อมูลจาก POST request
    $data = json_decode(file_get_contents("php://input"), true);

    // ตรวจสอบข้อมูลที่จำเป็น
    if (isset($data['idstdStudent']) && isset($data['emailStudent']) && isset($data['passwordStudent'])) {
        $idstd_student = $data['idstdStudent'];
        $email_student = $data['emailStudent'];
        $password_student = $data['passwordStudent'];
    } else {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
        exit(); // หยุดการทำงานหากข้อมูลไม่ครบถ้วน
    }

    include('db_connection.php');

    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // ตรวจสอบว่า idstdStudent และ emailStudent ตรงกับข้อมูลในฐานข้อมูลหรือไม่
    $sql = "SELECT name_student, status_student FROM student WHERE idstd_student = ? AND email_student = ? AND password_student = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('MySQL prepare statement error: ' . $conn->error);
    }

    $stmt->bind_param("sss", $idstd_student, $email_student, $password_student);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // ผู้ใช้พบในฐานข้อมูลแล้ว
        $row = $result->fetch_assoc();

        // ตรวจสอบสถานะบัญชี
        if ($row['status_student'] !== 'อนุมัติแล้ว') {
            // ถ้าสถานะเป็น 'รออนุมัติ' หรือ 'ไม่อนุมัติ'
            echo json_encode(["status" => "error", "message" => "สถานะบัญชีของคุณไม่อนุมัติ"]);
        } else {
            // ถ้าสถานะเป็น 'อนุมัติแล้ว'
            // ตรวจสอบรหัสผ่าน
            if (password_verify($password_student, $row['passwordStudent'])) {
                // รหัสผ่านถูกต้องและสถานะอนุมัติ
                echo json_encode(["status" => "success", "message" => "เข้าสู่ระบบสำเร็จ", "id" => $row['idstd_student'], "name" => $row['name_student']]);
            } else {
                // รหัสผ่านไม่ถูกต้อง
                echo json_encode(["status" => "error", "message" => "รหัสผ่านไม่ถูกต้อง"]);
            }
        }
    } else {
        // ไม่พบข้อมูลนักศึกษาในฐานข้อมูล
        echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลนักศึกษานี้"]);
    }



    // ปิดการเชื่อมต่อ
    $stmt->close();
    $conn->close();
} else {
    // Method ไม่ถูกต้อง
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
