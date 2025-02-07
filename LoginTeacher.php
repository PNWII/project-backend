<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// ตรวจสอบว่าเป็น POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // รับข้อมูลจาก POST request
    $data = json_decode(file_get_contents("php://input"), true);

    // ตรวจสอบข้อมูลที่จำเป็น
    if (isset($data['emailTeacher']) && isset($data['passwordTeacher'])) {
        $email_teacher = $data['emailTeacher'];
        $password_teacher = $data['passwordTeacher'];
    } else {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
        exit(); // หยุดการทำงานหากข้อมูลไม่ครบถ้วน
    }

    include('db_connection.php');

    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if ($conn->connect_error) {
        die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
    }

    // ตรวจสอบในฐานข้อมูลครู
    $sql_teacher = "SELECT id_teacher, type_teacher,name_teacher FROM teacher WHERE email_teacher = ? AND password_teacher = ?";
    $stmt_teacher = $conn->prepare($sql_teacher);

    if ($stmt_teacher === false) {
        die(json_encode(["status" => "error", "message" => "MySQL prepare statement error: " . $conn->error]));
    }

    $stmt_teacher->bind_param("ss", $email_teacher, $password_teacher);
    $stmt_teacher->execute();
    $result_teacher = $stmt_teacher->get_result();

    // หากพบในฐานข้อมูล teacher
    if ($result_teacher->num_rows > 0) {
        $teacher_data = $result_teacher->fetch_assoc();  // ดึงข้อมูลครู
        $role = $teacher_data['type_teacher'];  // ค่า role จาก type_teacher
        $teacherId = $teacher_data['id_teacher'];  // รับค่า teacher_id
        $nameTeacher = $teacher_data['name_teacher'];  // รับค่า name_teacher

        // ตัวอย่าง response ที่ควรส่งกลับจาก Backend
        $response = [
            "status" => "success",
            "message" => "เข้าสู่ระบบสำเร็จ",
            "role" => $role,  // ใช้ role จากฐานข้อมูล
            "name_teacher" => $nameTeacher,  // ใช้ name_teacher จากฐานข้อมูล
            "teacher_id" => $teacherId  // ใช้ teacher_id จากฐานข้อมูล
        ];

        echo json_encode($response);
        error_log(json_encode($response));

        $stmt_teacher->close();
        $conn->close();
        exit();
    }

} else {
    // Method ไม่ถูกต้อง
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}

?>