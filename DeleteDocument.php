<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

include('db_connection.php');

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// รับค่าพารามิเตอร์ 'id' จาก URL
if (isset($_GET['id'])) {
    $documentId = $_GET['id'];

    // เริ่ม transaction
    $conn->begin_transaction();

    try {
        // ลบข้อมูลที่เกี่ยวข้องในตาราง gs10report
        $sqlGs10 = "
            DELETE gs10 
            FROM gs10report gs10
            INNER JOIN formsubmit f 
            ON f.formsubmit_dataform = gs10.id_gs10report 
            AND f.formsubmit_type = 'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
            WHERE f.formsubmit_dataform = ?";
        $stmtGs10 = $conn->prepare($sqlGs10);
        $stmtGs10->bind_param("i", $documentId);
        $stmtGs10->execute();
        $stmtGs10->close();

        // ลบข้อมูลที่เกี่ยวข้องในตาราง gs11report
        $sqlGs11 = "
            DELETE gs11 
            FROM gs11report gs11
            INNER JOIN formsubmit f 
            ON f.formsubmit_dataform = gs11.id_gs11report 
            AND f.formsubmit_type = 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
            WHERE f.formsubmit_dataform = ?";
        $stmtGs11 = $conn->prepare($sqlGs11);
        $stmtGs11->bind_param("i", $documentId);
        $stmtGs11->execute();
        $stmtGs11->close();

        // ลบข้อมูลที่เกี่ยวข้องในตาราง gs12report
        $sqlGs12 = "
         DELETE gs12 
         FROM gs12report gs12
         INNER JOIN formsubmit f 
         ON f.formsubmit_dataform = gs12.id_gs12report 
         AND f.formsubmit_type = 'คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
         WHERE f.formsubmit_dataform = ?";
        $stmtGs12 = $conn->prepare($sqlGs12);
        $stmtGs12->bind_param("i", $documentId);
        $stmtGs12->execute();
        $stmtGs12->close();

        // ลบข้อมูลที่เกี่ยวข้องในตาราง gs13report
        $sqlGs13 = "
         DELETE gs13 
         FROM gs13report gs13
         INNER JOIN formsubmit f 
         ON f.formsubmit_dataform = gs13.id_gs13report 
         AND f.formsubmit_type = 'คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข'
         WHERE f.formsubmit_dataform = ?";
        $stmtGs13 = $conn->prepare($sqlGs13);
        $stmtGs13->bind_param("i", $documentId);
        $stmtGs13->execute();
        $stmtGs13->close();

        // ลบข้อมูลที่เกี่ยวข้องในตาราง gs14report
        $sqlGs14 = "
            DELETE gs14 
            FROM gs14report gs14
            INNER JOIN formsubmit f 
            ON f.formsubmit_dataform = gs14.id_gs14report 
            AND f.formsubmit_type = 'คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
            WHERE f.formsubmit_dataform = ?";
        $stmtGs14 = $conn->prepare($sqlGs14);
        $stmtGs14->bind_param("i", $documentId);
        $stmtGs14->execute();
        $stmtGs14->close();

        // ลบข้อมูลที่เกี่ยวข้องในตาราง gs15report
        $sqlGs15 = "
    DELETE gs15 
    FROM gs15report gs15
    INNER JOIN formsubmit f 
    ON f.formsubmit_dataform = gs15.id_gs15report 
    AND f.formsubmit_type = 'คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
 WHERE f.formsubmit_dataform = ?";
        $stmtGs15 = $conn->prepare($sqlGs15);
        $stmtGs15->bind_param("i", $documentId);
        $stmtGs15->execute();
        $stmtGs15->close();
      // ลบข้อมูลที่เกี่ยวข้องในตาราง gs16report
      $sqlGs16 = "
      DELETE gs16 
      FROM gs16report gs16
      INNER JOIN formsubmit f 
      ON f.formsubmit_dataform = gs16.id_gs16report 
      AND f.formsubmit_type = 'คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์'
   WHERE f.formsubmit_dataform = ?";
          $stmtGs16 = $conn->prepare($sqlGs16);
          $stmtGs16->bind_param("i", $documentId);
          $stmtGs16->execute();
          $stmtGs16->close();
    // ลบข้อมูลที่เกี่ยวข้องในตาราง gs17report
    $sqlGs17 = "
    DELETE gs17 
    FROM gs17report gs17
    INNER JOIN formsubmit f 
    ON f.formsubmit_dataform = gs17.id_gs17report 
    AND f.formsubmit_type = 'คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ'
    WHERE f.formsubmit_dataform = ?";
        $stmtGs17 = $conn->prepare($sqlGs17);
        $stmtGs17->bind_param("i", $documentId);
        $stmtGs17->execute();
        $stmtGs17->close();
    // ลบข้อมูลที่เกี่ยวข้องในตาราง gs18report
    $sqlGs18 = "
    DELETE gs18 
    FROM gs18report gs18
    INNER JOIN formsubmit f 
    ON f.formsubmit_dataform = gs18.id_gs18report 
    AND f.formsubmit_type = 'คคอ. บว. 18 แบบขอสอบประมวลความรู้'
    WHERE f.formsubmit_dataform = ?";
        $stmtGs18 = $conn->prepare($sqlGs18);
        $stmtGs18->bind_param("i", $documentId);
        $stmtGs18->execute();
        $stmtGs18->close();

      // ลบข้อมูลที่เกี่ยวข้องในตาราง gs19report
      $sqlGs19 = "
      DELETE gs19 
      FROM gs19report gs19
      INNER JOIN formsubmit f 
      ON f.formsubmit_dataform = gs19.id_gs19report 
      AND f.formsubmit_type = 'คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ'
      WHERE f.formsubmit_dataform = ?";
          $stmtGs19 = $conn->prepare($sqlGs19);
          $stmtGs19->bind_param("i", $documentId);
          $stmtGs19->execute();
          $stmtGs19->close();

      // ลบข้อมูลที่เกี่ยวข้องในตาราง gs23report
        $sqlGs23 = "
        DELETE gs23 
        FROM gs23report gs23
        INNER JOIN formsubmit f 
        ON f.formsubmit_dataform = gs23.id_gs23report 
        AND f.formsubmit_type = 'คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์'
    WHERE f.formsubmit_dataform = ?";
            $stmtGs23 = $conn->prepare($sqlGs23);
            $stmtGs23->bind_param("i", $documentId);
            $stmtGs23->execute();
            $stmtGs23->close();
            
        // ลบข้อมูลในตาราง formsubmit
        $sqlFormsubmit = "DELETE FROM formsubmit WHERE formsubmit_dataform = ?";
        $stmtFormsubmit = $conn->prepare($sqlFormsubmit);
        $stmtFormsubmit->bind_param("i", $documentId);
        $stmtFormsubmit->execute();
        $stmtFormsubmit->close();

        // ยืนยันการลบ (commit)
        $conn->commit();
        echo json_encode(["success" => true, "message" => "ลบเอกสารสำเร็จและลบข้อมูลที่เกี่ยวข้องแล้ว"]);

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ยกเลิกการเปลี่ยนแปลง (rollback)
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสเอกสาร"]);
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>