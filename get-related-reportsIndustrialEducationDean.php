<?php
// ไฟล์ backend/get-related-reportsIndustrialEducationDean.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include 'db_connection.php';

// เปิดการแสดงข้อผิดพลาด
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // ดึงข้อมูลจาก API fetch_documentFormSubmit.php
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
    $sql = "
        SELECT 
            vdAcrsign_IdDocs,
            vdAcrsign_status,
            vdAcrsign_nameDocs,
            vdAcrsign_at 
        FROM 
            vdacrsign 
        WHERE 
            vdAcrsign_status = 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว'
    ";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Error fetching data from database: " . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $statusMapping[$row['vdAcrsign_IdDocs']] = [
            'status' => $row['vdAcrsign_status'],
            'docName' => $row['vdAcrsign_nameDocs'],
            'timeSubmit' => $row['vdAcrsign_at']
        ];
    }

    // ประมวลผลข้อมูล
    $documentsToSend = [];
    foreach ($formSubmitData as $form) {
        $formId = $form['formsubmit_id'];
        $formDataForm = $form['formsubmit_dataform'];
        $formType = $form['formsubmit_type'];
        $formStatus = $form['formsubmit_status'];
        $formTimeSubmit = $form['formsubmit_at'];

        if (isset($statusMapping[$formDataForm])) {
            $additionalMessage = '';
            switch ($formStatus) {
                case 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรม':
                    $additionalMessage = 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว';
                    break;
                case 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัย กำลังรอการพิจารณาจากคณบดีคณะครุศาสตร์อุตสาหกรรม':
                    $additionalMessage = 'ได้รับการอนุมัติจากรองคณบดีแล้ว กำลังรอการพิจารณาจากคณบดีคณะครุศาสตร์อุตสาหกรรม';
                    break;
                case 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรม':
                    $additionalMessage = 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว';
                    break;
                default:
                    $additionalMessage = '';
            }

            if (!empty($additionalMessage)) {
                $documentsToSend[] = [
                    'formId' => $formId,
                    'id' => $formDataForm,
                    'docName' => $formType,
                    'formStatus' => $formStatus,
                    'timeSubmit' => $formTimeSubmit,
                    'idStudent' => $form['idstd_student'],
                    'nameStudent' => $form['name_student'],
                    'ccurrsignaStatus' => $statusMapping[$formDataForm]['status'],
                    'message' => $additionalMessage
                ];
            }
        } else {
            error_log("Status mapping not found for formDataForm: $formDataForm");
        }
    }

    // ส่งข้อมูลกลับเป็น JSON
    echo json_encode(['documentsToSend' => $documentsToSend], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // ส่งข้อความผิดพลาดในรูป JSON
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    $conn->close();
}
?>