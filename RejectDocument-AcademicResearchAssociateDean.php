<?php
//ไฟล์ backend/RejectDocument-AcademicResearchAssociateDean.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

try {
    $input = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }

    // ดึงค่าจาก JSON
    $docId = $input['id'] ?? null;
    $type = $input['type'] ?? null;
    $signature = $input['signature'] ?? null;
    $comment = $input['comment'] ?? null;
    $name = $input['name'] ?? null;
    $teacherId = $input['teacherId'] ?? null;

    // ตรวจสอบว่า teacherId มีอยู่หรือไม่
    $stmtCheck = $conn->prepare("SELECT id_teacher FROM teacher WHERE id_teacher = ? LIMIT 1");
    if (!$stmtCheck)
        throw new Exception("Error preparing teacher check SQL: " . $conn->error);

    $stmtCheck->bind_param("i", $teacherId);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Teacher ID not found in 'teacher' table");
    }

    $conn->begin_transaction();
    // เพิ่มข้อมูลลงตาราง vdacrsign
    $sqlInsert = "INSERT INTO vdacrsign (id_teacher, vdAcrsign_nameDocs, vdAcrsign_IdDocs, vdAcrsign_nameViceDeanAcademicResearch	, vdAcrsign_description, vdAcrsign_sign, vdAcrsign_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    if (!$stmtInsert)
        throw new Exception("Error preparing INSERT SQL: " . $conn->error);

    $status = "ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว";
    $stmtInsert->bind_param("issssss", $teacherId, $type, $docId, $name, $comment, $signature, $status);
    $stmtInsert->execute();

    // ตรวจสอบ type
    if ($type === "คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
        // UPDATE ตาราง gs10report
        $sqlUpdateGS10 = "UPDATE gs10report SET status_gs10report = ? WHERE id_gs10report = ?";
        $stmtUpdateGS10 = $conn->prepare($sqlUpdateGS10);
        if (!$stmtUpdateGS10)
            throw new Exception("Error preparing UPDATE SQL for gs10report: " . $conn->error);

        $stmtUpdateGS10->bind_param("si", $status, $docId);
        $stmtUpdateGS10->execute();
    } else if ($type === "คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
        // UPDATE ตาราง gs11report
        $sqlUpdateGS11 = "UPDATE gs11report SET status_gs11report = ? WHERE id_gs11report = ?";
        $stmtUpdateGS11 = $conn->prepare($sqlUpdateGS11);
        if (!$stmtUpdateGS11)
            throw new Exception("Error preparing UPDATE SQL for gs11report: " . $conn->error);

        $stmtUpdateGS11->bind_param("si", $status, $docId);
        $stmtUpdateGS11->execute();

    } else if ($type === "คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {

        // UPDATE ตาราง gs12report
        $sqlUpdateGS12 = "UPDATE gs12report SET status_gs12report = ? WHERE id_gs12report = ?";
        $stmtUpdateGS12 = $conn->prepare($sqlUpdateGS12);
        if (!$stmtUpdateGS12)
            throw new Exception("Error preparing UPDATE SQL for gs12report: " . $conn->error);

        $stmtUpdateGS12->bind_param("si", $status, $docId);
        $stmtUpdateGS12->execute();

    } else if ($type === "คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข") {

        // UPDATE ตาราง gs13report
        $sqlUpdateGS13 = "UPDATE gs13report SET status_gs13report = ? WHERE id_gs13report = ?";
        $stmtUpdateGS13 = $conn->prepare($sqlUpdateGS13);
        if (!$stmtUpdateGS13)
            throw new Exception("Error preparing UPDATE SQL for gs13report: " . $conn->error);

        $stmtUpdateGS13->bind_param("si", $status, $docId);
        $stmtUpdateGS13->execute();

    } else if ($type === "คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {

        // UPDATE ตาราง gs14report
        $sqlUpdateGS14 = "UPDATE gs14report SET status_gs14report = ? WHERE id_gs14report = ?";
        $stmtUpdateGS14 = $conn->prepare($sqlUpdateGS14);
        if (!$stmtUpdateGS14)
            throw new Exception("Error preparing UPDATE SQL for gs14report: " . $conn->error);

        $stmtUpdateGS14->bind_param("si", $status, $docId);
        $stmtUpdateGS14->execute();

    } else if ($type === "คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {

        // UPDATE ตาราง gs15report
        $sqlUpdateGS15 = "UPDATE gs15report SET status_gs15report = ? WHERE id_gs15report = ?";
        $stmtUpdateGS15 = $conn->prepare($sqlUpdateGS15);
        if (!$stmtUpdateGS15)
            throw new Exception("Error preparing UPDATE SQL for gs15report: " . $conn->error);

        $stmtUpdateGS15->bind_param("si", $status, $docId);
        $stmtUpdateGS15->execute();

    } else if ($type === "คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์") {

        // UPDATE ตาราง gs16report
        $sqlUpdateGS16 = "UPDATE gs16report SET status_gs16report = ? WHERE id_gs16report = ?";
        $stmtUpdateGS16 = $conn->prepare($sqlUpdateGS16);
        if (!$stmtUpdateGS16)
            throw new Exception("Error preparing UPDATE SQL for gs16report: " . $conn->error);

        $stmtUpdateGS16->bind_param("si", $status, $docId);
        $stmtUpdateGS16->execute();

    }else if ($type === "คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ") {

        // UPDATE ตาราง gs17report
        $sqlUpdateGS17 = "UPDATE gs17report SET status_gs17report = ? WHERE id_gs17report = ?";
        $stmtUpdateGS17 = $conn->prepare($sqlUpdateGS17);
        if (!$stmtUpdateGS17)
            throw new Exception("Error preparing UPDATE SQL for gs17report: " . $conn->error);

        $stmtUpdateGS17->bind_param("si", $status, $docId);
        $stmtUpdateGS17->execute();

    }else if ($type === "คคอ. บว. 18 แบบขอสอบประมวลความรู้") {

        // UPDATE ตาราง gs18report
        $sqlUpdateGS18 = "UPDATE gs18report SET status_gs18report = ? WHERE id_gs18report = ?";
        $stmtUpdateGS18 = $conn->prepare($sqlUpdateGS18);
        if (!$stmtUpdateGS18)
            throw new Exception("Error preparing UPDATE SQL for gs18report: " . $conn->error);

        $stmtUpdateGS18->bind_param("si", $status, $docId);
        $stmtUpdateGS18->execute();

    }else if ($type === "คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ") {

        // UPDATE ตาราง gs19report
        $sqlUpdateGS19 = "UPDATE gs19report SET status_gs19report = ? WHERE id_gs19report = ?";
        $stmtUpdateGS19 = $conn->prepare($sqlUpdateGS19);
        if (!$stmtUpdateGS19)
            throw new Exception("Error preparing UPDATE SQL for gs19report: " . $conn->error);

        $stmtUpdateGS19->bind_param("si", $status, $docId);
        $stmtUpdateGS19->execute();

    }else if ($type === "คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์") {

        // UPDATE ตาราง gs23report
        $sqlUpdateGS23 = "UPDATE gs23report SET status_gs23report = ? WHERE id_gs23report = ?";
        $stmtUpdateGS23 = $conn->prepare($sqlUpdateGS23);
        if (!$stmtUpdateGS23)
            throw new Exception("Error preparing UPDATE SQL for gs23report: " . $conn->error);

        $stmtUpdateGS23->bind_param("si", $status, $docId);
        $stmtUpdateGS23->execute();

    }

    // UPDATE ตาราง formsubmit
    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_type = ? AND formsubmit_dataform = ?";
    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
    if (!$stmtUpdateFormsubmit) {
        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
    }

    $formsubmitStatus = "ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัย";

    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $type, $docId);
    if (!$stmtUpdateFormsubmit->execute()) {
        throw new Exception("Error executing UPDATE for formsubmit: " . $stmtUpdateFormsubmit->error);
    }

    // Commit การทำงาน
    $conn->commit();

    echo json_encode(["status" => "success", "message" => "Document approved"]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    $conn->close();
}
?>