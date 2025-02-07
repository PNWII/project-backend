<?php
//ไฟล์ backend/RejectDocument-ChairpersonCurriculum.php
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

    if (!$docId || !$type || !$teacherId) {
        throw new Exception("Missing required parameters: id, type, or teacherId");
    }

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

    // INSERT ข้อมูลใน ccurrsigna
    $sqlInsert = "INSERT INTO ccurrsigna (id_teacher, ccurrsigna_nameDocs, ccurrsigna_IdDocs, ccurrsigna_nameChairpersonCurriculum, ccurrsigna_description, ccurrsigna_sign, ccurrsigna_status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    if (!$stmtInsert)
        throw new Exception("Error preparing INSERT SQL: " . $conn->error);

    $status = "ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว";
    $stmtInsert->bind_param("issssss", $teacherId, $type, $docId, $name, $comment, $signature, $status);

    if (!$stmtInsert->execute()) {
        throw new Exception("Error executing INSERT SQL: " . $stmtInsert->error);
    }


    // UPDATE ตาราง gs10report
    $sqlUpdateGS10 = "UPDATE gs10report SET status_gs10report = ? WHERE id_gs10report = ?";
    $stmtUpdateGS10 = $conn->prepare($sqlUpdateGS10);
    if (!$stmtUpdateGS10)
        throw new Exception("Error preparing UPDATE SQL for gs10report: " . $conn->error);

    $stmtUpdateGS10->bind_param("si", $status, $docId);
    if (!$stmtUpdateGS10->execute()) {
        throw new Exception("Error executing UPDATE for gs10report: " . $stmtUpdateGS10->error);
    }

    // UPDATE ตาราง gs11report
    $sqlUpdateGS11 = "UPDATE gs11report SET status_gs11report = ? WHERE id_gs11report = ?";
    $stmtUpdateGS11 = $conn->prepare($sqlUpdateGS11);
    if (!$stmtUpdateGS11)
        throw new Exception("Error preparing UPDATE SQL for gs11report: " . $conn->error);

    $stmtUpdateGS11->bind_param("si", $status, $docId);
    if (!$stmtUpdateGS11->execute()) {
        throw new Exception("Error executing UPDATE for gs11report: " . $stmtUpdateGS11->error);
    }
    // UPDATE ตาราง gs12report
    $sqlUpdateGS12 = "UPDATE gs12report SET status_gs12report = ? WHERE id_gs12report = ?";
    $stmtUpdateGS12 = $conn->prepare($sqlUpdateGS12);
    if (!$stmtUpdateGS12)
        throw new Exception("Error preparing UPDATE SQL for gs12report: " . $conn->error);

    $stmtUpdateGS12->bind_param("si", $status, $docId);
    if (!$stmtUpdateGS12->execute()) {
        throw new Exception("Error executing UPDATE for gs12report: " . $stmtUpdateGS12->error);
    }
    // UPDATE ตาราง gs13report
    $sqlUpdateGS13 = "UPDATE gs13report SET status_gs13report = ? WHERE id_gs13report = ?";
    $stmtUpdateGS13 = $conn->prepare($sqlUpdateGS13);
    if (!$stmtUpdateGS13)
        throw new Exception("Error preparing UPDATE SQL for gs13report: " . $conn->error);

    $stmtUpdateGS13->bind_param("si", $status, $docId);
    if (!$stmtUpdateGS13->execute()) {
        throw new Exception("Error executing UPDATE for gs13report: " . $stmtUpdateGS13->error);
    }
    // UPDATE ตาราง gs14report
    $sqlUpdateGS14 = "UPDATE gs14report SET status_gs14report = ? WHERE id_gs14report = ?";
    $stmtUpdateGS14 = $conn->prepare($sqlUpdateGS14);
    if (!$stmtUpdateGS14)
        throw new Exception("Error preparing UPDATE SQL for gs14report: " . $conn->error);

    $stmtUpdateGS14->bind_param("si", $status, $docId);
    if (!$stmtUpdateGS14->execute()) {
        throw new Exception("Error executing UPDATE for gs14report: " . $stmtUpdateGS14->error);
    }
    if ($type === "คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ") {
        // UPDATE ตาราง gs15report
        $sqlUpdateGS15 = "UPDATE gs15report SET status_gs15report = ? WHERE id_gs15report = ?";
        $stmtUpdateGS15 = $conn->prepare($sqlUpdateGS15);
        if (!$stmtUpdateGS15)
            throw new Exception("Error preparing UPDATE SQL for gs15report: " . $conn->error);

        $stmtUpdateGS15->bind_param("si", $status, $docId);
        $stmtUpdateGS15->execute();
        $sqlInsertGS15 = "INSERT INTO ccurrsignags15 (id_teacher, 
       ccurrsignags15_nameDocs, 
       ccurrsignags15_IdDocs, 
       ccurrsignags15_nameChairpersonCurriculum, 
       ccurrsignags15_description, 
       ccurrsignags15_sign, 
        ccurrsigna15_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtInsertGS15 = $conn->prepare($sqlInsertGS15);
        if (!$stmtInsertGS15)
            throw new Exception("Error preparing INSERT SQL forccurrsignags15: " . $conn->error);

        $statusGS15 = "ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตร";
        $stmtInsertGS15->bind_param(
            "issssss",
            $teacherId,
            $type,
            $docId,
            $name,
            $comment,
            $signature,
            $statusGS15
        );
        $stmtInsertGS15->execute();
    }
     // UPDATE ตาราง gs16report
     $sqlUpdateGS16 = "UPDATE gs16report SET status_gs16report = ? WHERE id_gs16report = ?";
     $stmtUpdateGS16 = $conn->prepare($sqlUpdateGS16);
     if (!$stmtUpdateGS16)
         throw new Exception("Error preparing UPDATE SQL for gs16report: " . $conn->error);
 
     $stmtUpdateGS16->bind_param("si", $status, $docId);
     if (!$stmtUpdateGS16->execute()) {
         throw new Exception("Error executing UPDATE for gs16report: " . $stmtUpdateGS16->error);
     }

      // UPDATE ตาราง gs17report
    $sqlUpdateGS17 = "UPDATE gs17report SET status_gs17report = ? WHERE id_gs17report = ?";
    $stmtUpdateGS17 = $conn->prepare($sqlUpdateGS17);
    if (!$stmtUpdateGS17)
        throw new Exception("Error preparing UPDATE SQL for gs17report: " . $conn->error);

    $stmtUpdateGS17->bind_param("si", $status, $docId);
    if (!$stmtUpdateGS17->execute()) {
        throw new Exception("Error executing UPDATE for gs17report: " . $stmtUpdateGS17->error);
    }

     // UPDATE ตาราง gs18report
     $sqlUpdateGS18 = "UPDATE gs18report SET status_gs18report = ? WHERE id_gs18report = ?";
     $stmtUpdateGS18 = $conn->prepare($sqlUpdateGS18);
     if (!$stmtUpdateGS18)
         throw new Exception("Error preparing UPDATE SQL for gs18report: " . $conn->error);
 
     $stmtUpdateGS18->bind_param("si", $status, $docId);
     if (!$stmtUpdateGS18->execute()) {
         throw new Exception("Error executing UPDATE for gs18report: " . $stmtUpdateGS18->error);
     }

      // UPDATE ตาราง gs19report
    $sqlUpdateGS19 = "UPDATE gs19report SET status_gs19report = ? WHERE id_gs19report = ?";
    $stmtUpdateGS19 = $conn->prepare($sqlUpdateGS19);
    if (!$stmtUpdateGS19)
        throw new Exception("Error preparing UPDATE SQL for gs19report: " . $conn->error);

    $stmtUpdateGS19->bind_param("si", $status, $docId);
    if (!$stmtUpdateGS19->execute()) {
        throw new Exception("Error executing UPDATE for gs19report: " . $stmtUpdateGS19->error);
    }
     // UPDATE ตาราง gs23report
     $sqlUpdateGS23 = "UPDATE gs23report SET status_gs23report = ? WHERE id_gs23report = ?";
     $stmtUpdateGS23 = $conn->prepare($sqlUpdateGS23);
     if (!$stmtUpdateGS23)
         throw new Exception("Error preparing UPDATE SQL for gs23report: " . $conn->error);
 
     $stmtUpdateGS23->bind_param("si", $status, $docId);
     if (!$stmtUpdateGS23->execute()) {
         throw new Exception("Error executing UPDATE for gs23report: " . $stmtUpdateGS23->error);
     }

    // UPDATE ตาราง formsubmit
    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_type = ? AND formsubmit_dataform = ?";
    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
    if (!$stmtUpdateFormsubmit) {
        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
    }


    $formsubmitStatus = "ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตร";
    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $type, $docId);
    if (!$stmtUpdateFormsubmit->execute()) {
        throw new Exception("Error executing UPDATE for formsubmit: " . $stmtUpdateFormsubmit->error);
    }

    // Commit การทำงาน
    $conn->commit();

    echo json_encode(["status" => "success", "message" => "Document rejected successfully"]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} finally {
    $conn->close();
}
?>