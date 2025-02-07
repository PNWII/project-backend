<?php
//ไฟล์ backend/fetch_documentFormSubmit_id.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

include('db_connection.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(["error" => "กรุณาระบุรหัสเอกสาร"]);
    exit; 
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            f.formsubmit_id, 
            f.formsubmit_type,
            f.idstd_student,
            f.formsubmit_dataform,
            f.formsubmit_status,
            f.formsubmit_at,
            s.name_student,
            gs10.id_gs10report,
            gs10.name_gs10report,
            gs10.projectType_gs10report,
            gs10.projectThai_gs10report,
            gs10.projectEng_gs10report,
            gs10.advisorType_gs10report,
            gs10.advisorMainNew_gs10report,
            gs10.advisorSecondNew_gs10report,
            gs10.advisorMainOld_gs10report,
            gs10.advisorSecondOld_gs10report,
            gs10.document_gs10report,
            gs10.signature_gs10report,
            gs10.signName_gs10report,
            gs11.projectThai_gs11report,
            gs11.projectEng_gs11report,
            gs11.id_gs11report,
            gs11.name_gs11report
        FROM 
            formsubmit f
        LEFT JOIN 
            gs10report gs10 ON f.formsubmit_dataform = gs10.id_gs10report AND f.formsubmit_type = 'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 

        
            gs11report gs11 ON f.formsubmit_dataform = gs11.id_gs11report AND f.formsubmit_type = 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 
            student s ON f.idstd_student = s.idstd_student
        WHERE f.formsubmit_id = ?"); 
    $stmt->bind_param("s", $id); 

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $documents = [];

        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }

        echo json_encode($documents);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

$conn->close();

?>