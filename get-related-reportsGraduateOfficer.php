<?php
// ไฟล์ backend/get-related-reportsAcademicResearchAssociateDean.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include 'db_connection.php';

// เปิดการแสดงข้อผิดพลาด
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // เรียกข้อมูลจาก fetch_documentFormSubmit.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/TestPHP-API2/backend/fetch_documentFormSubmit.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception("CURL Error: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        throw new Exception("Failed to fetch data from fetch_documentFormSubmit.php. HTTP Code: $httpCode");
    }

    curl_close($ch);

    // ตรวจสอบว่าเป็น JSON หรือไม่
    $formSubmitData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON response: " . json_last_error_msg());
    }

    if (!is_array($formSubmitData)) {
        throw new Exception("Invalid or empty data from fetch_documentFormSubmit.php");
    }

    // ดึงข้อมูล statusMapping จากฐานข้อมูล
    $statusMapping = [];

    // Query แรก
    $sql1 = "
       SELECT
            ccurrsigna_IdDocs AS docId,
            ccurrsigna_status AS status,
            ccurrsigna_nameDocs AS docName,
            ccurrsigna_at AS timeSubmit
        FROM 
            ccurrsigna
        WHERE 
            ccurrsigna_status = 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว'
    ";

    // Query ที่สอง

    // รันทั้งสอง Query
    $queries = [$sql1];
    foreach ($queries as $query) {
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception("Error fetching data: " . $conn->error);
        }

        while ($row = $result->fetch_assoc()) {
            // ใช้ docId เป็น key สำหรับเก็บข้อมูล
            $statusMapping[$row['docId']] = [
                'status' => $row['status'],
                'docName' => $row['docName'],
                'timeSubmit' => $row['timeSubmit']
            ];
        }
    }

    // ประมวลผล $formSubmitData
    $documentsToSend = [];
    foreach ($formSubmitData as $form) {
        $formId = $form['formsubmit_id'];
        $formDataForm = $form['formsubmit_dataform'];
        $formStatus = $form['formsubmit_status'];
        $formTimeSubmit = $form['formsubmit_at'];

        // ตรวจสอบข้อมูลใน $statusMapping
        if (isset($statusMapping[$formDataForm])) {
            $additionalMessage = '';
            if ($formStatus === 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษา กำลังรอการพิจารณาจากรองคณบดีฝ่ายวิชาการและวิจัย' || 
            $formStatus === 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัย' || 
            $formStatus === 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรม' || 
            $formStatus === 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรม' ||
            $formStatus === 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัย กำลังรอการพิจารณาจากคณบดีคณะครุศาสตร์อุตสาหกรรม') {
                $additionalMessage = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว';
            } elseif ($formStatus === 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตร กำลังรอการพิจารณาจากเจ้าหน้าที่บัณฑิตศึกษา') {
                $additionalMessage = 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตร กำลังรอการพิจารณาจากเจ้าหน้าที่บัณฑิตศึกษา';
            } elseif ($formStatus === 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษา') {
                $additionalMessage = 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว';
            }

            // เพิ่มข้อมูลใน $documentsToSend
            $documentsToSend[] = [
                'formId' => $formId,
                'id' => $formDataForm,
                'docName' => $form['formsubmit_type'],
                'formStatus' => $formStatus,
                'timeSubmit' => $formTimeSubmit,
                'idStudent' => $form['idstd_student'],
                'nameStudent' => $form['name_student'],
                'ccurrsignaStatus' => $statusMapping[$formDataForm]['status'],
                'message' => $additionalMessage
            ];
        }
    }

    // ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode(['documentsToSend' => $documentsToSend], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // ส่งข้อความผิดพลาดในรูป JSON
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    $conn->close();
}
?>