<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// เชื่อมต่อกับฐานข้อมูล MySQL โดยใช้ mysqli
include('db_connection.php');

// คำสั่ง SQL เพื่อดึงข้อมูลอาจารย์ที่ปรึกษา
$query = "SELECT id_teacher, name_teacher FROM teacher WHERE type_teacher = 'ครูอาจารย์ที่ปรึกษา'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    echo json_encode($teachers);
} else {
    echo json_encode(["error" => "No data found"]);
}

$conn->close();
?>