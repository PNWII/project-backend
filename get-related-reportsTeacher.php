<?php
// ไฟล์ backend/get-related-reportsTeacher.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

// ตรวจสอบว่ามีการส่งคำขอแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $nameTeacher = isset($input['nameTeacher']) ? trim($input['nameTeacher']) : null;

    if (!$nameTeacher) {
        echo json_encode(["status" => "error", "message" => "Missing teacher name in request"]);
        exit();
    }

    // สร้าง SQL Query สำหรับดึงข้อมูลรายงาน gs10report และ gs11report
    $sql = "
    SELECT 
        g.id_gs10report AS id,
        g.name_gs10report AS name,
        g.idstd_student AS idstd_student,
        s.name_student AS name_student,
        g.advisorMainNew_gs10report AS advisorMainNew,
        g.advisorSecondNew_gs10report AS advisorSecondNew,
        g.at_gs10report AS timeSubmit,
        g.advisorMainNew_gs10report,
        g.advisorSecondNew_gs10report,
        g.status_gs10report AS statusGs,
        'gs10report' AS source
    FROM gs10report g
    LEFT JOIN student s ON g.idstd_student = s.idstd_student
    WHERE 
        g.advisorMainNew_gs10report = ? 
        OR g.advisorSecondNew_gs10report = ? 
        OR g.advisorMainOld_gs10report = ? 
        OR g.advisorSecondOld_gs10report = ?
    UNION
    SELECT 
        g.id_gs11report AS id,
        g.name_gs11report AS name,
        g.idstd_student AS idstd_student,
        s.name_student AS name_student,
        g.advisorMain_gs11report AS advisorMainNew,
        g.advisorSecond_gs11report AS advisorSecondNew,
        g.at_gs11report AS timeSubmit,
        g.advisorMain_gs11report,
        g.advisorSecond_gs11report,
        g.status_gs11report AS statusGs,
        'gs11report' AS source
    FROM gs11report g
    LEFT JOIN student s ON g.idstd_student = s.idstd_student
    WHERE 
        g.advisorMain_gs11report = ? 
        OR g.advisorSecond_gs11report = ?
     UNION
    SELECT 
        g.id_gs12report AS id,
        g.name_gs12report AS name,
        g.idstd_student AS idstd_student,
        s.name_student AS name_student,
        g.advisorMain_gs12report AS advisorMainNew,
        g.advisorSecond_gs12report AS advisorSecondNew,
        g.at_gs12report AS timeSubmit,
        g.advisorMain_gs12report,
        g.advisorSecond_gs12report,
        g.status_gs12report AS statusGs,
        'gs12report' AS source
    FROM gs12report g
    LEFT JOIN student s ON g.idstd_student = s.idstd_student
    WHERE 
        g.advisorMain_gs12report = ? 
        OR g.advisorSecond_gs12report = ?
     UNION
    SELECT 
        g.id_gs13report AS id,
        g.name_gs13report AS name,
        g.idstd_student AS idstd_student,
        s.name_student AS name_student,
        g.advisorMain_gs13report AS advisorMainNew,
        g.advisorSecond_gs13report AS advisorSecondNew,
        g.at_gs13report AS timeSubmit,
        g.advisorMain_gs13report,
        g.advisorSecond_gs13report,
        g.status_gs13report AS statusGs,
        'gs13report' AS source
    FROM gs13report g
    LEFT JOIN student s ON g.idstd_student = s.idstd_student
    WHERE 
        g.advisorMain_gs13report = ? 
        OR g.advisorSecond_gs13report = ?
     UNION
    SELECT 
        g.id_gs14report AS id,
        g.name_gs14report AS name,
        g.idstd_student AS idstd_student,
        s.name_student AS name_student,
        g.advisorMain_gs14report AS advisorMainNew,
        g.advisorSecond_gs14report AS advisorSecondNew,
        g.at_gs14report AS timeSubmit,
        g.advisorMain_gs14report,
        g.advisorSecond_gs14report,
        g.status_gs14report AS statusGs,
        'gs14report' AS source
    FROM gs14report g
    LEFT JOIN student s ON g.idstd_student = s.idstd_student
    WHERE 
        g.advisorMain_gs14report = ? 
        OR g.advisorSecond_gs14report = ?
     UNION
    SELECT 
        g.id_gs15report AS id,
        g.name_gs15report AS name,
        g.idstd_student AS idstd_student,
        s.name_student AS name_student,
        g.advisorMain_gs15report AS advisorMainNew,
        g.advisorSecond_gs15report AS advisorSecondNew,
        g.at_gs15report AS timeSubmit,
        g.advisorMain_gs15report,
        g.advisorSecond_gs15report,
        g.status_gs15report AS statusGs,
        'gs15report' AS source
    FROM gs15report g
    LEFT JOIN student s ON g.idstd_student = s.idstd_student
    WHERE 
        g.advisorMain_gs15report = ? 
        OR g.advisorSecond_gs15report = ?
    UNION
SELECT 
    g.id_gs16report AS id,
    g.name_gs16report AS name,
    g.idstd_student AS idstd_student,
    s.name_student AS name_student,
    g.thesisAdvisor_gs16report AS advisorMainNew,
    NULL AS advisorSecondNew, -- ใช้ NULL สำหรับคอลัมน์ที่ไม่มี
    g.at_gs16report AS timeSubmit,
    g.thesisAdvisor_gs16report,
    NULL AS advisorSecond_gs16report, -- ใช้ NULL หากไม่มีค่านี้ใน gs16report
    g.status_gs16report AS statusGs,
    'gs16report' AS source
FROM gs16report g
LEFT JOIN student s ON g.idstd_student = s.idstd_student
WHERE 
    g.thesisAdvisor_gs16report = ?
    UNION
SELECT 
    g.id_gs17report AS id,
    g.name_gs17report AS name,
    g.idstd_student AS idstd_student,
    s.name_student AS name_student,
    g.thesisAdvisor_gs17report AS advisorMainNew,
    NULL AS advisorSecondNew, -- ใช้ NULL สำหรับคอลัมน์ที่ไม่มี
    g.at_gs17report AS timeSubmit,
    g.thesisAdvisor_gs17report,
    NULL AS advisorSecond_gs17report, -- ใช้ NULL หากไม่มีค่านี้ใน gs17report
    g.status_gs17report AS statusGs,
    'gs17report' AS source
FROM gs17report g
LEFT JOIN student s ON g.idstd_student = s.idstd_student
WHERE 
    g.thesisAdvisor_gs17report = ?
    UNION
SELECT 
    g.id_gs18report AS id,
    g.name_gs18report AS name,
    g.idstd_student AS idstd_student,
    s.name_student AS name_student,
    g.thesisAdvisor_gs18report AS advisorMainNew,
    NULL AS advisorSecondNew, -- ใช้ NULL สำหรับคอลัมน์ที่ไม่มี
    g.at_gs18report AS timeSubmit,
    g.thesisAdvisor_gs18report,
    NULL AS advisorSecond_gs18report, -- ใช้ NULL หากไม่มีค่านี้ใน gs18report
    g.status_gs18report AS statusGs,
    'gs18report' AS source
FROM gs18report g
LEFT JOIN student s ON g.idstd_student = s.idstd_student
WHERE 
    g.thesisAdvisor_gs18report = ?
    UNION
SELECT 
    g.id_gs19report AS id,
    g.name_gs19report AS name,
    g.idstd_student AS idstd_student,
    s.name_student AS name_student,
    g.thesisAdvisor_gs19report AS advisorMainNew,
    NULL AS advisorSecondNew, -- ใช้ NULL สำหรับคอลัมน์ที่ไม่มี
    g.at_gs19report AS timeSubmit,
    g.thesisAdvisor_gs19report,
    NULL AS advisorSecond_gs19report, -- ใช้ NULL หากไม่มีค่านี้ใน gs19report
    g.status_gs19report AS statusGs,
    'gs19report' AS source
FROM gs19report g
LEFT JOIN student s ON g.idstd_student = s.idstd_student
WHERE 
    g.thesisAdvisor_gs19report = ?
    UNION
SELECT 
    g.id_gs23report AS id,
    g.name_gs23report AS name,
    g.idstd_student AS idstd_student,
    s.name_student AS name_student,
    g.IndependentStudyAdvisor_gs23report AS advisorMainNew,
    NULL AS advisorSecondNew, -- ใช้ NULL สำหรับคอลัมน์ที่ไม่มี
    g.at_gs23report AS timeSubmit,
    g.IndependentStudyAdvisor_gs23report,
    NULL AS advisorSecond_gs23report, -- ใช้ NULL หากไม่มีค่านี้ใน gs23report
    g.status_gs23report AS statusGs,
    'gs23report' AS source
FROM gs23report g
LEFT JOIN student s ON g.idstd_student = s.idstd_student
WHERE 
    g.IndependentStudyAdvisor_gs23report = ?
";




    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare the SQL statement. Error: " . $conn->error]);
        exit();
    }

    $stmt->bind_param(
        "sssssssssssssssssss",
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
        $nameTeacher,
    );



    // Execute statement
    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => "Failed to execute SQL statement. Error: " . $stmt->error]);
        exit();
    }

    // Get results
    $result = $stmt->get_result();

    // เก็บผลลัพธ์ลงใน array
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    // ขั้นตอนที่ 2: ดึงสถานะการอนุมัติจาก teachersigna
    $statusSql = "
    SELECT teachersign_IdDocs, teachersign_nameTeacher, teachersign_status
    FROM teachersigna
    WHERE teachersign_nameTeacher = ?
    ";

    $statusStmt = $conn->prepare($statusSql);
    if (!$statusStmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare the teachersign query. Error: " . $conn->error]);
        exit();
    }

    // ผูกค่าชื่ออาจารย์
    $statusStmt->bind_param("s", $nameTeacher);

    // Execute statement
    if (!$statusStmt->execute()) {
        echo json_encode(["status" => "error", "message" => "Failed to execute teachersign query. Error: " . $statusStmt->error]);
        exit();
    }

    // Get status results
    $statusResult = $statusStmt->get_result();
    $statusMapping = [];
    while ($statusRow = $statusResult->fetch_assoc()) {
        $statusMapping[$statusRow['teachersign_IdDocs']] = $statusRow['teachersign_status'];
    }

    // ประมวลผลผลลัพธ์จาก reports และเพิ่มสถานะการอนุมัติ
    foreach ($reports as &$report) {
        $docId = $report['id'];
        if (isset($statusMapping[$docId])) {
            $report['status'] = $statusMapping[$docId];
        } else {
            $report['status'] = 'รอการพิจารณาจากครูอาจารย์ที่ปรึกษา'; // ถ้าไม่มีสถานะใน teachersigna
        }
    }

    // ส่งผลลัพธ์กลับไปยัง frontend
    echo json_encode([
        "status" => "success",
        "reports" => $reports
    ]);

    // ปิดการเชื่อมต่อกับฐานข้อมูล
    $stmt->close();
    $statusStmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}
