<?php
// ไฟล์ backend/ApproveDocument-GraduateOfficer.php
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
    $advisorMain = $input['advisorMain'] ?? null;
    $advisorMainCriteria = $input['advisorMainCriteria'] ?? null;
    $advisorSecond = $input['advisorSecond'] ?? null;
    $advisorSecondCriteria = $input['advisorSecondCriteria'] ?? null;
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

    // เริ่มการทำธุรกรรม
    $conn->begin_transaction();

    // ตรวจสอบ type
    if ($type === "คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
        // เพิ่มข้อมูลลงตาราง gradofficersigngs10
        $sqlInsertGS10 = "INSERT INTO gradofficersigngs10 (id_teacher, gradofficersignGs10_nameDocs, gradofficersignGs10_IdDocs, gradofficersignGs10_nameGradOffice, gradofficersignGs10_description, gradofficersignGs10_sign, gradofficersignGs10_status,gradofficersignGs10_advisorMain,gradofficersignGs10_advisorMainCriteria,gradofficersignGs10_advisorSecond,gradofficersignGs10_advisorSecondCriteria) 
                          VALUES (?, ?, ?, ?, ?, ?, ?,?,?,?,?)";
        $stmtInsertGS10 = $conn->prepare($sqlInsertGS10);
        if (!$stmtInsertGS10)
            throw new Exception("Error preparing INSERT SQL for gradofficersigngs10: " . $conn->error);

        $statusGS10 = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsertGS10->bind_param("issssssssss", $teacherId, $type, $docId, $name, $comment, $signature, $statusGS10, $advisorMain, $advisorMainCriteria, $advisorSecond, $advisorSecondCriteria);
        $stmtInsertGS10->execute();
        // UPDATE ตาราง gs10report
        $sqlUpdateGS10 = "UPDATE gs10report SET status_gs10report = ? WHERE id_gs10report = ?";
        $stmtUpdateGS10 = $conn->prepare($sqlUpdateGS10);
        if (!$stmtUpdateGS10)
            throw new Exception("Error preparing UPDATE SQL for gs10report: " . $conn->error);

        $stmtUpdateGS10->bind_param("si", $statusGS10, $docId);
        $stmtUpdateGS10->execute();


    } else if ($type === "คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
        // เพิ่มข้อมูลลงตาราง gradofficersigngs12
        $sqlInsertGS12 = "INSERT INTO gradofficersigngs12 (id_teacher, 
        gradofficersignGs12_nameDocs, 
        gradofficersignGs12_IdDocs, 
        gradofficersignGs12_nameGradOffice, 
        gradofficersignGs12_description, 
        gradofficersignGs12_sign, 
        gradofficersignGs12_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertGS12 = $conn->prepare($sqlInsertGS12);
        if (!$stmtInsertGS12)
            throw new Exception("Error preparing INSERT SQL for gradofficersigngs12: " . $conn->error);

        $statusGS12 = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsertGS12->bind_param(
            "issssss",
            $teacherId,
            $type,
            $docId,
            $name,
            $comment,
            $signature,
            $statusGS12
        );
        $stmtInsertGS12->execute();
        // UPDATE ตาราง gs12report
        $sqlUpdateGS12 = "UPDATE gs12report SET status_gs12report = ? WHERE id_gs12report = ?";
        $stmtUpdateGS12 = $conn->prepare($sqlUpdateGS12);
        if (!$stmtUpdateGS12)
            throw new Exception("Error preparing UPDATE SQL for gs12report: " . $conn->error);

        $stmtUpdateGS12->bind_param("si", $statusGS12, $docId);
        $stmtUpdateGS12->execute();
    } else if ($type === "คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข") {
        // เพิ่มข้อมูลลงตาราง gradofficersigngs13
        $sqlInsertGS13 = "INSERT INTO gradofficersigngs13 (id_teacher, 
        gradofficersignGs13_nameDocs, 
        gradofficersignGs13_IdDocs, 
        gradofficersignGs13_nameGradOffice, 
        gradofficersignGs13_description, 
        gradofficersignGs13_sign, 
        gradofficersignGs13_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertGS13 = $conn->prepare($sqlInsertGS13);
        if (!$stmtInsertGS13)
            throw new Exception("Error preparing INSERT SQL for gradofficersigngs13: " . $conn->error);

        $statusGS13 = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsertGS13->bind_param(
            "issssss",
            $teacherId,
            $type,
            $docId,
            $name,
            $comment,
            $signature,
            $statusGS13
        );
        $stmtInsertGS13->execute();
        // UPDATE ตาราง gs13report
        $sqlUpdateGS13 = "UPDATE gs13report SET status_gs13report = ? WHERE id_gs13report = ?";
        $stmtUpdateGS13 = $conn->prepare($sqlUpdateGS13);
        if (!$stmtUpdateGS13)
            throw new Exception("Error preparing UPDATE SQL for gs13report: " . $conn->error);

        $stmtUpdateGS13->bind_param("si", $statusGS13, $docId);
        $stmtUpdateGS13->execute();
    }else if ($type === "คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์") {
        // เพิ่มข้อมูลลงตาราง gradofficersigngs16
        $sqlInsertGS16 = "INSERT INTO gradofficersigngs16 (id_teacher, 
        gradofficersignGs16_nameDocs, 
        gradofficersignGs16_IdDocs, 
        gradofficersignGs16_nameGradOffice, 
        gradofficersignGs16_description, 
        gradofficersignGs16_sign, 
        gradofficersignGs16_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertGS16 = $conn->prepare($sqlInsertGS16);
        if (!$stmtInsertGS16)
            throw new Exception("Error preparing INSERT SQL for gradofficersigngs16: " . $conn->error);

        $statusGS16 = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsertGS16->bind_param(
            "issssss",
            $teacherId,
            $type,
            $docId,
            $name,
            $comment,
            $signature,
            $statusGS16
          
        );
        $stmtInsertGS16->execute();
        // UPDATE ตาราง gs16report
        $sqlUpdateGS16 = "UPDATE gs16report SET status_gs16report = ? WHERE id_gs16report = ?";
        $stmtUpdateGS16 = $conn->prepare($sqlUpdateGS16);
        if (!$stmtUpdateGS16)
            throw new Exception("Error preparing UPDATE SQL for gs16report: " . $conn->error);

        $stmtUpdateGS16->bind_param("si", $statusGS16, $docId);
        $stmtUpdateGS16->execute();
    }else if ($type === "คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ") {
        // เพิ่มข้อมูลลงตาราง gradofficersigngs17
        $sqlInsertGS17 = "INSERT INTO gradofficersigngs17 (id_teacher, 
        gradofficersignGs17_nameDocs, 
        gradofficersignGs17_IdDocs, 
        gradofficersignGs17_nameGradOffice, 
        gradofficersignGs17_description, 
        gradofficersignGs17_sign, 
        gradofficersignGs17_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertGS17 = $conn->prepare($sqlInsertGS17);
        if (!$stmtInsertGS17)
            throw new Exception("Error preparing INSERT SQL for gradofficersigngs17: " . $conn->error);

        $statusGS17 = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsertGS17->bind_param(
            "issssss",
            $teacherId,
            $type,
            $docId,
            $name,
            $comment,
            $signature,
            $statusGS17
        );
        $stmtInsertGS17->execute();
        // UPDATE ตาราง gs17report
        $sqlUpdateGS17 = "UPDATE gs17report SET status_gs17report = ? WHERE id_gs17report = ?";
        $stmtUpdateGS17 = $conn->prepare($sqlUpdateGS17);
        if (!$stmtUpdateGS17)
            throw new Exception("Error preparing UPDATE SQL for gs17report: " . $conn->error);

        $stmtUpdateGS17->bind_param("si", $statusGS17, $docId);
        $stmtUpdateGS17->execute();
    }else if ($type === "คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ") {
        // เพิ่มข้อมูลลงตาราง gradofficersigngs19
        $sqlInsertGS19 = "INSERT INTO gradofficersigngs19 (id_teacher, 
        gradofficersignGs19_nameDocs, 
        gradofficersignGs19_IdDocs, 
        gradofficersignGs19_nameGradOffice, 
        gradofficersignGs19_description, 
        gradofficersignGs19_sign, 
        gradofficersignGs19_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertGS19 = $conn->prepare($sqlInsertGS19);
        if (!$stmtInsertGS19)
            throw new Exception("Error preparing INSERT SQL for gradofficersigngs19: " . $conn->error);

        $statusGS19 = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsertGS19->bind_param(
            "issssss",
            $teacherId,
            $type,
            $docId,
            $name,
            $comment,
            $signature,
            $statusGS19
        );
        $stmtInsertGS19->execute();
        // UPDATE ตาราง gs19report
        $sqlUpdateGS19 = "UPDATE gs19report SET status_gs19report = ? WHERE id_gs19report = ?";
        $stmtUpdateGS19 = $conn->prepare($sqlUpdateGS19);
        if (!$stmtUpdateGS19)
            throw new Exception("Error preparing UPDATE SQL for gs19report: " . $conn->error);

        $stmtUpdateGS19->bind_param("si", $statusGS19, $docId);
        $stmtUpdateGS19->execute();
    }else if ($type === "คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์") {
        // เพิ่มข้อมูลลงตาราง gradofficersigngs23
        $sqlInsertGS23 = "INSERT INTO gradofficersigngs23 (id_teacher, 
        gradofficersignGs23_nameDocs, 
        gradofficersignGs23_IdDocs, 
        gradofficersignGs23_nameGradOffice, 
        gradofficersignGs23_description, 
        gradofficersignGs23_sign, 
        gradofficersignGs23_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertGS23 = $conn->prepare($sqlInsertGS23);
        if (!$stmtInsertGS23)
            throw new Exception("Error preparing INSERT SQL for gradofficersigngs23: " . $conn->error);

        $statusGS23 = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsertGS23->bind_param(
            "issssss",
            $teacherId,
            $type,
            $docId,
            $name,
            $comment,
            $signature,
            $statusGS23
        );
        $stmtInsertGS23->execute();
        // UPDATE ตาราง gs23report
        $sqlUpdateGS23 = "UPDATE gs23report SET status_gs23report = ? WHERE id_gs23report = ?";
        $stmtUpdateGS23 = $conn->prepare($sqlUpdateGS23);
        if (!$stmtUpdateGS23)
            throw new Exception("Error preparing UPDATE SQL for gs23report: " . $conn->error);

        $stmtUpdateGS23->bind_param("si", $statusGS23, $docId);
        $stmtUpdateGS23->execute();
    } else {
        // เพิ่มข้อมูลลงตาราง gradofficersign
        $sqlInsert = "INSERT INTO gradofficersign (id_teacher, gradofficersign_nameDocs, gradofficersign_IdDocs, gradofficersign_nameGradofficer, gradofficersign_description, gradofficersign_sign, gradofficersign_status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        if (!$stmtInsert)
            throw new Exception("Error preparing INSERT SQL: " . $conn->error);

        $status = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว";
        $stmtInsert->bind_param("issssss", $teacherId, $type, $docId, $name, $comment, $signature, $status);
        $stmtInsert->execute();
        if ($type === "คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
            // UPDATE ตาราง gs11report
            $sqlUpdateGS11 = "UPDATE gs11report SET status_gs11report = ? WHERE id_gs11report = ?";
            $stmtUpdateGS11 = $conn->prepare($sqlUpdateGS11);
            if (!$stmtUpdateGS11)
                throw new Exception("Error preparing UPDATE SQL for gs11report: " . $conn->error);

            $stmtUpdateGS11->bind_param("si", $status, $docId);
            $stmtUpdateGS11->execute();
        }
        if ($type === "คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
            // UPDATE ตาราง gs14report
            $sqlUpdateGS14 = "UPDATE gs14report SET status_gs14report = ? WHERE id_gs14report = ?";
            $stmtUpdateGS14 = $conn->prepare($sqlUpdateGS14);
            if (!$stmtUpdateGS14)
                throw new Exception("Error preparing UPDATE SQL for gs14report: " . $conn->error);

            $stmtUpdateGS14->bind_param("si", $status, $docId);
            $stmtUpdateGS14->execute();
        }
        if ($type === "คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
            // UPDATE ตาราง gs15report
            $sqlUpdateGS15 = "UPDATE gs15report SET status_gs15report = ? WHERE id_gs15report = ?";
            $stmtUpdateGS15 = $conn->prepare($sqlUpdateGS15);
            if (!$stmtUpdateGS15)
                throw new Exception("Error preparing UPDATE SQL for gs15report: " . $conn->error);

            $stmtUpdateGS15->bind_param("si", $status, $docId);
            $stmtUpdateGS15->execute();
        }
        if ($type === "คคอ. บว. 18 แบบขอสอบประมวลความรู้") {
            // UPDATE ตาราง gs18report
            $sqlUpdateGS18 = "UPDATE gs18report SET status_gs18report = ? WHERE id_gs18report = ?";
            $stmtUpdateGS18 = $conn->prepare($sqlUpdateGS18);
            if (!$stmtUpdateGS18)
                throw new Exception("Error preparing UPDATE SQL for gs18report: " . $conn->error);

            $stmtUpdateGS18->bind_param("si", $status, $docId);
            $stmtUpdateGS18->execute();
        }
    }

    // UPDATE ตาราง formsubmit
    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_type = ? AND formsubmit_dataform = ?";
    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
    if (!$stmtUpdateFormsubmit) {
        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
    }

    $formsubmitStatus = "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษา";

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