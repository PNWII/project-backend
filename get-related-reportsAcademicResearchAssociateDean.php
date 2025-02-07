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
            gradofficersign_IdDocs AS docId,
            gradofficersign_status AS status,
            gradofficersign_nameDocs AS docName,
            gradofficersign_at AS timeSubmit
        FROM 
            gradofficersign
        WHERE 
            gradofficersign_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ";

    // Query ที่สอง
    $sql2 = "
        SELECT 
            gradofficersignGs10_IdDocs AS docId,
            gradofficersignGs10_status AS status,
            gradofficersignGs10_nameDocs AS docName,
            gradofficersignGs10_at AS timeSubmit
        FROM 
            gradofficersigngs10
        WHERE 
            gradofficersignGs10_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ";
    $sql3 = "
    SELECT 
        gradofficersignGs12_IdDocs AS docId,
        gradofficersignGs12_status AS status,
        gradofficersignGs12_nameDocs AS docName,
        gradofficersignGs12_at AS timeSubmit
    FROM 
        gradofficersigngs12
    WHERE 
        gradofficersignGs12_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
";
    $sql4 = "
    SELECT 
        gradofficersignGs13_IdDocs AS docId,
        gradofficersignGs13_status AS status,
        gradofficersignGs13_nameDocs AS docName,
        gradofficersignGs13_at AS timeSubmit
    FROM 
        gradofficersigngs13
    WHERE 
        gradofficersignGs13_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
";
$sql5 = "
    SELECT 
        gradofficersignGs16_IdDocs AS docId,
        gradofficersignGs16_status AS status,
        gradofficersignGs16_nameDocs AS docName,
        gradofficersignGs16_at AS timeSubmit
    FROM 
        gradofficersigngs16
    WHERE 
        gradofficersignGs16_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
";
$sql6 = "
    SELECT 
        gradofficersignGs17_IdDocs AS docId,
        gradofficersignGs17_status AS status,
        gradofficersignGs17_nameDocs AS docName,
        gradofficersignGs17_at AS timeSubmit
    FROM 
        gradofficersigngs17
    WHERE 
        gradofficersignGs17_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
";
$sql7 = "
    SELECT 
        gradofficersignGs19_IdDocs AS docId,
        gradofficersignGs19_status AS status,
        gradofficersignGs19_nameDocs AS docName,
        gradofficersignGs19_at AS timeSubmit
    FROM 
        gradofficersigngs19
    WHERE 
        gradofficersignGs19_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
";
$sql8 = "
    SELECT 
        gradofficersignGs23_IdDocs AS docId,
        gradofficersignGs23_status AS status,
        gradofficersignGs23_nameDocs AS docName,
        gradofficersignGs23_at AS timeSubmit
    FROM 
        gradofficersigngs23
    WHERE 
        gradofficersignGs23_status = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
";
    // รันทั้งสอง Query
    $queries = [$sql1, $sql2, $sql3, $sql4, $sql5, $sql6, $sql7, $sql8];
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
            if ($formStatus === 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัย กำลังรอการพิจารณาจากคณบดีคณะครุศาสตร์อุตสาหกรรม' || $formStatus === 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรม' || $formStatus === 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรม') {
                $additionalMessage = 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว';
            } elseif ($formStatus === 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษา กำลังรอการพิจารณาจากรองคณบดีฝ่ายวิชาการและวิจัย') {
                $additionalMessage = 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษา กำลังรอการพิจารณาจากรองคณบดีฝ่ายวิชาการและวิจัย';
            } elseif ($formStatus === 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัย') {
                $additionalMessage = 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว';
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