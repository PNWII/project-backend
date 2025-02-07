<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require('fpdf.php');
include('../db_connection.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(["error" => "Missing or empty 'id' query parameter."]);
    exit;
}


$docId = $_GET['id'];
function fetchDocumentById($docId)
{
    global $conn;

    $stmt = $conn->prepare("
    SELECT 
        s.name_student,
        s.major_student,
        s.branch_student,
        s.prefix_student,
        s.prefix_studentEng,
        s.name_studentEng,
        s.id_studyplan,
        sp.name_studyplan,
        s.abbreviate_student,
        s.address_student,
        s.email_student,
        s.tel_student,
        s.idstd_student,
        gs17.id_gs17report,
        gs17.name_gs17report,
        gs17.semesterAt_gs17report,
        gs17.academicYear_gs17report,
        gs17.courseCredits_gs17report,
        gs17.cumulativeGPA_gs17report,
        gs17.projectDefenseDate_gs17report,
        gs17.additionalDetails_gs17report,
        gs17.docCheck15_gs17report,
        gs17.thesisAdvisor_gs17report,
        gs17.status_gs17report,
        gs17.at_gs17report,
        gs17.signature_gs17report,
        gs17.date_gs17report,
        gs17.signName_gs17report
    FROM 
        gs17report gs17
    LEFT JOIN 
        student s ON gs17.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs17.id_gs17report = ?
");

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}
// ฟังก์ชันดึงข้อมูลจากตาราง teachersigna
function fetchTeacherSignaturesByDocId($docId)
{
    global $conn;

    $stmt = $conn->prepare("
    SELECT 
        teachersign_IdDocs,
        teachersign_nameTeacher,
        teachersign_sign,
        teachersign_status,
        teachersign_at,
        teachersign_description
    FROM 
        teachersigna
    WHERE 
        teachersign_IdDocs = ?
    ");

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacherSignatures = [];
    while ($row = $result->fetch_assoc()) {
        $timestamp = strtotime($row['teachersign_at']);
        $thai_months = [
            "01" => "มกราคม",
            "02" => "กุมภาพันธ์",
            "03" => "มีนาคม",
            "04" => "เมษายน",
            "05" => "พฤษภาคม",
            "06" => "มิถุนายน",
            "07" => "กรกฎาคม",
            "08" => "สิงหาคม",
            "09" => "กันยายน",
            "10" => "ตุลาคม",
            "11" => "พฤศจิกายน",
            "12" => "ธันวาคม"
        ];

        $thai_day = date('d', $timestamp);
        $thai_month = $thai_months[date('m', $timestamp)];
        $thai_year = date('Y', $timestamp) + 543;

        $row['thai_date_formattedteachersign'] = "วันที่ $thai_day เดือน $thai_month พ.ศ. $thai_year";

        $teacherSignatures[] = $row;
    }

    return $teacherSignatures;
}


function fetchChairpersonCurriculumSignaturesByDocId($docId)
{
    global $conn;

    $stmt = $conn->prepare("
    SELECT 
        ccurrsigna_IdDocs,
        ccurrsigna_nameDocs,
        ccurrsigna_nameChairpersonCurriculum,
        ccurrsigna_description,
        ccurrsigna_sign,
        ccurrsigna_status,
        ccurrsigna_at
    FROM 
        ccurrsigna
    WHERE 
        ccurrsigna_IdDocs = ?
    ");

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $chairpersoncurriculumSignatures = [];
    while ($row = $result->fetch_assoc()) {
        // แปลงวันที่ timestamp เป็นรูปแบบวันที่ไทย
        $timestamp = strtotime($row['ccurrsigna_at']); // แปลง timestamp
        $thai_months = [
            "01" => "มกราคม",
            "02" => "กุมภาพันธ์",
            "03" => "มีนาคม",
            "04" => "เมษายน",
            "05" => "พฤษภาคม",
            "06" => "มิถุนายน",
            "07" => "กรกฎาคม",
            "08" => "สิงหาคม",
            "09" => "กันยายน",
            "10" => "ตุลาคม",
            "11" => "พฤศจิกายน",
            "12" => "ธันวาคม"
        ];

        $thai_day = date('d', $timestamp);
        $thai_month = $thai_months[date('m', $timestamp)];
        $thai_year = date('Y', $timestamp) + 543; // เพิ่ม 543 เพื่อให้เป็นปีไทย

        $thai_date_formattedchairpersoncurriculum = "วันที่ $thai_day เดือน $thai_month พ.ศ. $thai_year"; // วันที่ในรูปแบบไทย

        // เพิ่มข้อมูลลายเซ็นและวันที่ที่แปลงแล้ว
        $row['thai_date_formattedchairpersoncurriculum'] = $thai_date_formattedchairpersoncurriculum;

        // แปลง Base64 เป็นไฟล์ PNG
        $signatureDatachairpersoncurriculum = $row['ccurrsigna_sign'];  // ถ้าคุณใช้ชื่อ 'ccurrsigna_sign' สำหรับลายเซ็น
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png';
        if (strpos($signatureDatachairpersoncurriculum, 'data:image/png;base64,') === 0) {
            $signatureData = str_replace('data:image/png;base64,', '', $signatureDatachairpersoncurriculum);
            file_put_contents($signatureImagechairpersoncurriculum, base64_decode($signatureData));  // บันทึกไฟล์ PNG
        }

        // เพิ่มข้อมูลลงในอาร์เรย์
        $chairpersoncurriculumSignatures[] = $row;
    }

    return $chairpersoncurriculumSignatures;
}

function fetchGraduateOfficerSignaturesByDocId($docId)
{
    global $conn;

    // Check if the connection is valid
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("
    SELECT 
        gradofficersignGs17_IdDocs,
        gradofficersignGs17_nameDocs,
        gradofficersignGs17_nameGradoffice,
        gradofficersignGs17_description,
        gradofficersignGs17_sign,
        gradofficersignGs17_status,
        gradofficersignGs17_masterPlanOneApprovalDoc,
        gradofficersignGs17_at
    FROM 
        gradofficersigngs17
    WHERE 
        gradofficersignGs17_IdDocs = ?
    ");

    if (!$stmt) {
        die("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $GraduateOfficerSignatures = [];

    while ($row = $result->fetch_assoc()) {
        $timestamp = strtotime($row['gradofficersignGs17_at']);
        $thai_months = [
            "01" => "มกราคม",
            "02" => "กุมภาพันธ์",
            "03" => "มีนาคม",
            "04" => "เมษายน",
            "05" => "พฤษภาคม",
            "06" => "มิถุนายน",
            "07" => "กรกฎาคม",
            "08" => "สิงหาคม",
            "09" => "กันยายน",
            "10" => "ตุลาคม",
            "11" => "พฤศจิกายน",
            "12" => "ธันวาคม"
        ];

        $thai_day = date('d', $timestamp);
        $thai_month = $thai_months[date('m', $timestamp)];
        $thai_year = date('Y', $timestamp) + 543;

        $thai_date_formattedGraduateOfficer = "วันที่ $thai_day เดือน $thai_month พ.ศ. $thai_year";
        $row['thai_date_formattedGraduateOfficer'] = $thai_date_formattedGraduateOfficer;

        $signatureDataGraduateOfficer = $row['gradofficersignGs17_sign'];
        if (strpos($signatureDataGraduateOfficer, 'data:image/png;base64,') === 0) {
            $signatureData = str_replace('data:image/png;base64,', '', $signatureDataGraduateOfficer);
            $uniqueFileName = 'signature_GraduateOfficer_' . uniqid() . '.png';
            file_put_contents($uniqueFileName, base64_decode($signatureData));
            $row['signature_file_path'] = $uniqueFileName;
        }

        $GraduateOfficerSignatures[] = $row;
    }

    return $GraduateOfficerSignatures;
}

function fetchAcademicResearchAssociateDeanSignaturesByDocId($docId)
{
    global $conn;

    $stmt = $conn->prepare("
    SELECT 
        vdAcrsign_IdDocs,
        vdAcrsign_nameDocs,
        vdAcrsign_nameViceDeanAcademicResearch,
        vdAcrsign_description,
        vdAcrsign_sign,
        vdAcrsign_status,
        vdAcrsign_at
    FROM 
        vdacrsign
    WHERE 
        vdAcrsign_IdDocs = ?
    ");

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $AcademicResearchAssociateDeanSignatures = [];

    while ($row = $result->fetch_assoc()) {
        // แปลงวันที่ timestamp เป็นรูปแบบวันที่ไทย
        $timestamp = strtotime($row['vdAcrsign_at']);
        $thai_months = [
            "01" => "มกราคม",
            "02" => "กุมภาพันธ์",
            "03" => "มีนาคม",
            "04" => "เมษายน",
            "05" => "พฤษภาคม",
            "06" => "มิถุนายน",
            "07" => "กรกฎาคม",
            "08" => "สิงหาคม",
            "09" => "กันยายน",
            "10" => "ตุลาคม",
            "11" => "พฤศจิกายน",
            "12" => "ธันวาคม"
        ];

        $thai_day = date('d', $timestamp);
        $thai_month = $thai_months[date('m', $timestamp)];
        $thai_year = date('Y', $timestamp) + 543;

        $thai_date_formattedAcademicResearchAssociateDean = "วันที่ $thai_day เดือน $thai_month พ.ศ. $thai_year";

        $row['thai_date_formattedAcademicResearchAssociateDean'] = $thai_date_formattedAcademicResearchAssociateDean;

        // แปลง Base64 เป็นไฟล์ PNG และสร้างชื่อไฟล์แบบไดนามิก
        $signatureDataAcademicResearchAssociateDean = $row['vdAcrsign_sign'];
        if (strpos($signatureDataAcademicResearchAssociateDean, 'data:image/png;base64,') === 0) {
            $signatureData = str_replace('data:image/png;base64,', '', $signatureDataAcademicResearchAssociateDean);
            $uniqueFileName = 'signature_AcademicResearchAssociateDean_' . uniqid() . '.png';
            file_put_contents($uniqueFileName, base64_decode($signatureData));
            $row['signature_file_path'] = $uniqueFileName; // เก็บชื่อไฟล์ในข้อมูล
        }

        $AcademicResearchAssociateDeanSignatures[] = $row;
    }

    return $AcademicResearchAssociateDeanSignatures;
}
function fetchIndustrialEducationDeanSignaturesByDocId($docId)
{
    global $conn;

    $stmt = $conn->prepare("
    SELECT 
        deanfiesign_IdDocs,
        deanfiesign_nameDocs,
        deanfiesign_nameDeanIndEdu,
        deanfiesign_description,
        deanfiesign_sign,
        deanfiesign_status,
        deanfiesign_at
    FROM 
        deanfiesign
    WHERE 
        deanfiesign_IdDocs = ?
    ");

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $IndustrialEducationDeanSignatures = [];

    while ($row = $result->fetch_assoc()) {
        // แปลงวันที่ timestamp เป็นรูปแบบวันที่ไทย
        $timestamp = strtotime($row['deanfiesign_at']);
        $thai_months = [
            "01" => "มกราคม",
            "02" => "กุมภาพันธ์",
            "03" => "มีนาคม",
            "04" => "เมษายน",
            "05" => "พฤษภาคม",
            "06" => "มิถุนายน",
            "07" => "กรกฎาคม",
            "08" => "สิงหาคม",
            "09" => "กันยายน",
            "10" => "ตุลาคม",
            "11" => "พฤศจิกายน",
            "12" => "ธันวาคม"
        ];

        $thai_day = date('d', $timestamp);
        $thai_month = $thai_months[date('m', $timestamp)];
        $thai_year = date('Y', $timestamp) + 543;

        $thai_date_formattedIndustrialEducationDean = "วันที่ $thai_day เดือน $thai_month พ.ศ. $thai_year";

        $row['thai_date_formattedIndustrialEducationDean'] = $thai_date_formattedIndustrialEducationDean;

        // แปลง Base64 เป็นไฟล์ PNG และสร้างชื่อไฟล์แบบไดนามิก
        $signatureDataIndustrialEducationDean = $row['deanfiesign_sign'];
        if (strpos($signatureDataIndustrialEducationDean, 'data:image/png;base64,') === 0) {
            $signatureData = str_replace('data:image/png;base64,', '', $signatureDataIndustrialEducationDean);
            $uniqueFileName = 'signature_IndustrialEducationDean_' . uniqid() . '.png';
            file_put_contents($uniqueFileName, base64_decode($signatureData));
            $row['signature_file_path'] = $uniqueFileName; // เก็บชื่อไฟล์ในข้อมูล
        }

        $IndustrialEducationDeanSignatures[] = $row;
    }

    return $IndustrialEducationDeanSignatures;
}

//ดึงข้อมูลลายเซ็นจากคณบดีครุศาสตร์
$IndustrialEducationDeanSignatures = fetchIndustrialEducationDeanSignaturesByDocId($docId);

//ดึงข้อมูลลายเซ็นรองคณบดี
$AcademicResearchAssociateDeanSignatures = fetchAcademicResearchAssociateDeanSignaturesByDocId($docId);

//ดึงข้อมูลลายเซ็นเจ้าหน้าบัณฑิต
$graduateOfficerSignatures = fetchGraduateOfficerSignaturesByDocId($docId);

//ดึงข้อมูลลายเซ็นประธานหลักสูตร
$chairpersoncurriculumSignatures = fetchChairpersonCurriculumSignaturesByDocId($docId);
//print_r($chairpersoncurriculumSignatures);

// ดึงข้อมูลลายเซ็นครู
$teacherSignatures = fetchTeacherSignaturesByDocId($docId);

// Fetch the document data
$document = fetchDocumentById($docId);

if ($document) {
    function convertToThaiDate($original_date)
    {
        if (!$original_date) {
            return '';
        }

        $timestamp = strtotime($original_date);
        $thai_months = [
            "01" => "มกราคม",
            "02" => "กุมภาพันธ์",
            "03" => "มีนาคม",
            "04" => "เมษายน",
            "05" => "พฤษภาคม",
            "06" => "มิถุนายน",
            "07" => "กรกฎาคม",
            "08" => "สิงหาคม",
            "09" => "กันยายน",
            "10" => "ตุลาคม",
            "11" => "พฤศจิกายน",
            "12" => "ธันวาคม"
        ];

        $thai_day = date('d', $timestamp);
        $thai_month = $thai_months[date('m', $timestamp)];
        $thai_year = date('Y', $timestamp) + 543;

        return "วันที่ $thai_day เดือน $thai_month พ.ศ. $thai_year";
    }

    // แปลงวันที่ทั้งสอง
    $date1_thai = convertToThaiDate($document['date_gs17report'] ?? '');
    $date2_thai = convertToThaiDate($document['projectDefenseDate_gs17report'] ?? '');

    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs17report'];
    $signatureImage = 'signature_temp.png';
    if (strpos($signatureData, 'data:image/png;base64,') === 0) {
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
    }
    file_put_contents($signatureImage, base64_decode($signatureData));
    class PDF extends FPDF
    {
        function CheckBox($x, $y, $size = 4)
        {
            $this->Rect($x, $y, $size, $size);
        }
        function checkboxMark($checked = TRUE, $checkbox_size = 5, $ori_font_family = 'Arial', $ori_font_size = 10, $ori_font_style = '')
        {
            if ($checked == TRUE)
                $check = chr(51); // Use character 51 from ZapfDingbats for check mark
            else
                $check = "";

            $this->SetFont('ZapfDingbats', '', $ori_font_size);
            $this->Cell($checkbox_size, $checkbox_size, $check, 1, 0);
            $this->SetFont($ori_font_family, $ori_font_style, $ori_font_size);
        }
    }
    $pdf = new FPDF();
    $pdf = new PDF();

    // Add Thai font 
    $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
    $pdf->AddFont('THSarabunNew', 'B', 'THSarabunNew_b.php');
    $pdf->AddPage();

    $pdf->SetFillColor(192);
    $pdf->Image('img/logo.png', 15, 5, 15, 0);
    $pdf->SetXY(170, 15);
    $pdf->SetFont('THSarabunNew', 'B', 14);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'คคอ. บว. 17'));
    $pdf->SetXY(150, 6.5);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 40, iconv('UTF-8', 'cp874', 'ครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(74, 42);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์'));

    $pdf->SetXY(68, 50);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ'));

    $pdf->SetXY(187, 60);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date1_thai), 0, 1, 'R');
    $pdf->SetXY(20, 65);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรื่อง'));
    $pdf->SetXY(35, 65);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ขออนุมัติผลการสำเร็จการศึกษา'));

    $pdf->SetXY(20, 72);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(15, 10, iconv('UTF-8', 'cp874', 'เรียน'));
    $pdf->SetXY(35, 72);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(x: 70, y: 92.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(x: 147, y: 92.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(36, 88);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', "ข้าพเจ้า (" . $document['prefix_student'] . ")...................................................................รหัสประจำตัว..................................................."));

    $pdf->SetXY(20, 96);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท         แผน 1 แบบวิชาการ'));

    $pdf->SetXY(102, 99);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคปกติ') !== false,
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

    $pdf->SetXY(125, 99);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคสมทบ') !== false,
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(x: 37, y: 108);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(x: 100, y: 108);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(x: 177, y: 108);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['abbreviate_student']));
    $pdf->SetXY(20, 103.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'สาขาวิชา......................................................สาขา................................................................อักษรย่อสาขาวิชา.................'));

    $pdf->SetXY(x: 65, y: 115.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20, 111);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก...................................................................................................................................................'));
    $pdf->SetXY(x: 40, y: 123);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $document['email_student']));
    $pdf->SetXY(x: 150, y: 123);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(20, 118.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'E-mail:......................................................................................หมายเลขโทรศัพท์ มือถือ..................................................'));

    $pdf->SetXY(20, 137);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'มีความประสงค์ขอสอบหัวข้อ  ขออนุมัติผลการสำเร็จการศึกษานักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ'));

    $pdf->SetXY(x: 140, y: 149.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['semesterAt_gs17report']));
    $pdf->SetXY(x: 168, y: 149.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['academicYear_gs17report']));
    $pdf->SetXY(20, 145);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ทั้งนี้ได้ปฏิบัติตามเงื่อนไขเพื่อขออนุมัติผลการสำเร็จการศึกษาในภาคการศึกษาที่.............ปีการศึกษา................  ดังนี้'));

    $pdf->SetXY(x: 103, y: 157.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['courseCredits_gs17report']));
    $pdf->SetXY(x: 153, y: 157.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['cumulativeGPA_gs17report']));
    $pdf->SetXY(21, 156);
    $pdf->checkboxMark(
        !empty($document['courseCredits_gs17report']) && !empty($document['cumulativeGPA_gs17report']),
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ศึกษารายวิชาครบตามที่กำหนดในหลักสูตรจำนวน..............หน่วยกิต มีคะแนนเฉลี่ย.....................'), 0, 1, 'L');

    $pdf->SetXY(21, 164);
    $pdf->checkboxMark($document['docCheck15_gs17report'] == 1, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ส่ง คคอ. บว. 50 คำร้องขอส่งเอกสารผลงานที่ได้รับการตีพิมพ์หรือการนำเสนอ'), 0, 1, 'L');
    // $pdf->SetXY(x:90, y:172.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '1545'));
    // $pdf->SetXY(x:110, y:172.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '1545'));
    $pdf->SetXY(x: 80.5, y: 173);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date2_thai));
    $pdf->SetXY(21, 171);
    $pdf->checkboxMark(
        !empty($document['projectDefenseDate_gs17report']),
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  สอบป้องกันวิทยานิพนธ์ผ่านแล่วเมื่อ '), 0, 1, 'L');
    $pdf->SetXY(x: 40, y: 179.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['additionalDetails_gs17report']));
    $pdf->SetXY(21, 178);
    $pdf->checkboxMark(
        !empty($document['additionalDetails_gs17report']),
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ .........................................................................................................................................................................'), 0, 1, 'L');

    $pdf->SetXY(33, 194);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));
    $pdf->SetXY(123, 208);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ขอแสดงความนับถือ'));

    // $pdf->SetXY(x:115, y:241); 
    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 120, 225, 30, 0, 'PNG');
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    }
    $pdf->SetXY(102, 240);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
    $pdf->SetXY(x: 120, y: 248);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(110, 247);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(.............................................................)'));

    $pdf->SetXY(165, 272);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '/...ความเห็นประธาน...'));


    ////////////------------------ Page 2 ----------------------------------------------------------------- 2 //
    $pdf->AddPage();
    $pdf->SetXY(20, 30);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของอาจารย์ที่ปรึกษา'));
    $pdf->SetXY(20, 34);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ได้พิจารณารายละเอียดตามคำร้องของนักศึกษาแล้ว'));

    $pdf->SetXY(22, 40.5);
    $pdf->checkboxMark(
        isset($teacherSignatures[0]['teachersign_status']) &&
            $teacherSignatures[0]['teachersign_status'] == 'ได้รับการอนุมัติจากครูอาจารย์ที่ปรึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรให้ดำเนินการตามเสนอได้'));

    if (
        isset($teacherSignatures[0]['teachersign_status']) &&
        $teacherSignatures[0]['teachersign_status'] == 'ถูกปฏิเสธจากครูอาจารย์ที่ปรึกษาแล้ว'
    ) {
        $teacherSignaturesDescription = isset($teacherSignatures[0]['teachersign_description'])
            ? $teacherSignatures[0]['teachersign_description']
            : '';

        $pdf->SetXY(x: 40, y: 48);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $teacherSignaturesDescription));
        $pdf->SetXY(22, 47);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(22, 47);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    }

    // $pdf->SetXY(x:125, y:63); 
    foreach ($teacherSignatures as $index => $teacherSignature) {
        $teacherSignatureData = $teacherSignature['teachersign_sign'];
        if (strpos($teacherSignatureData, 'data:image/png;base64,') === 0) {
            $teacherSignatureData = str_replace('data:image/png;base64,', '', $teacherSignatureData);
        }
        $teacherImage = 'signature_temp_teacher' . ($index + 1) . '.png';
        file_put_contents($teacherImage, base64_decode($teacherSignatureData));

        if (getimagesize($teacherImage) === false) {
            unlink($teacherImage);
            die("Error: Not a valid PNG file for teacher " . $teacherSignature['teachersign_nameTeacher']);
        }

        if ($teacherSignature['teachersign_nameTeacher'] === $document['thesisAdvisor_gs17report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 126, 55, 30, 0, 'PNG');
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }
    $pdf->SetXY(110, 62);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $teacherSignaturesName = isset($teacherSignatures[0]['teachersign_nameTeacher'])
        ? $teacherSignatures[0]['teachersign_nameTeacher']
        : '';
    $pdf->SetXY(x: 130, y: 70);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $teacherSignaturesName));
    $pdf->SetXY(118, 69);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(............................................................................)'));


    $pdf->SetXY(127, 78);
    $pdf->SetFont('THSarabunNew', '', 16);
    foreach ($teacherSignatures as $signature) {
        $thai_date_formattedteachersign = $signature['thai_date_formattedteachersign'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedteachersign), 0, 1, 'L');
    }
    $pdf->SetXY(20, 84);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'));
    $pdf->SetXY(26, 88);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ได้พิจารณารายละเอียดตามเสนอแล้ว'));
    $pdf->SetXY(27, 94);
    $pdf->checkboxMark(
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
            $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรให้ดำเนินการตามเสนอได้'), 0, 1, 'L');
    if (
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
        $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว'
    ) {

        $description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';
        $pdf->SetXY(x: 45, y: 101.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $description));
        $pdf->SetXY(27, 100.5);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(27, 100.5);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    }
    // $pdf->SetXY(x:125, y:114); 
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png';
        $pdf->Image($signatureImagechairpersoncurriculum, 125, 103, 40);
    }
    $pdf->SetXY(110, 113);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(x: 130, y: 120);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $chairpersonName));
    $pdf->SetXY(118, 119);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(............................................................................)'));
    $pdf->SetXY(127, 128);
    $pdf->SetFont('THSarabunNew', '', 16);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }
    /////////////////////////////////////////////
    $pdf->SetXY(20, 133);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะ ฯ'));
    $pdf->SetXY(20, 137);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'เรียน  คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(26, 144);
    $pdf->checkboxMark(
        isset($graduateOfficerSignatures[0]['gradofficersignGs17_status']) &&
            $graduateOfficerSignatures[0]['gradofficersignGs17_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ได้ตรวจสอบแล้วเห็นสมควรอนุมัติ พร้อมนี้ได้แนบแบบรายงานการอนุมัติผลการสำเร็จการศึกษา'));
    $pdf->SetXY(30, 150);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  (สำหรับนักศึกษาปริญญาโท แผน 1 แบบวิชาการ) เพื่อโปรดลงนาม'));

    if (
        isset($graduateOfficerSignatures[0]['gradofficersignGs17_status']) &&
        $graduateOfficerSignatures[0]['gradofficersignGs17_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ) {

        $DescriptiontGs17 = isset($graduateOfficerSignatures[0]['gradofficersignGs17_description']) ?
            $graduateOfficerSignatures[0]['gradofficersignGs17_description'] : '';
        $pdf->SetXY(x: 45, y: 157);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $DescriptiontGs17));
        $pdf->SetXY(26, 156);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(26, 156);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    }


    // $pdf->SetXY(x:125, y:170); 
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 125, 160, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }
    $pdf->SetXY(110, 169);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersignGs17_nameGradoffice'])
        ? $graduateOfficerSignatures[0]['gradofficersignGs17_nameGradoffice'] : '';
    $pdf->SetXY(x: 130, y: 176);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $GradOfficeName));
    $pdf->SetXY(118, 175);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(............................................................................)'));
    $pdf->SetXY(127, 182);
    $pdf->SetFont('THSarabunNew', '', 16);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];


            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer));
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
    }
    /////////////////////////////
    $pdf->SetXY(20, 190);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(25,  194);
    $pdf->checkboxMark(
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
            $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นควรอนุมัติ'), 0, 1, 'L');

    if (
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
        $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว'
    ) {
        $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';

        $pdf->SetXY(x: 90, y: 195);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription));
        $pdf->SetXY(70, 194);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ...................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(70, 194);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ...................................................................................................................'), 0, 1, 'L');
    }

    // $pdf->SetXY(x:125, y:208); 
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 125, 198, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
    }
    $pdf->SetXY(110, 207);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(117, 213);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'));
    $pdf->SetXY(124, 220);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(123, 228);
    $pdf->SetFont('THSarabunNew', '', 16);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
    }


    $pdf->SetXY(20, 234);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(25, 238.5);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
            $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติ'), 0, 1, 'L');

    if (
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'
    ) {

        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ?
            $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';
        $pdf->SetXY(x: 90, y: 239.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription));
        $pdf->SetXY(70, 238.5);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................'), 0, 1, 'L');
    } else {
        // $pdf->SetXY(x:90, y:239.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '1545'));
        $pdf->SetXY(70, 238.5);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................'), 0, 1, 'L');
    }

    // $pdf->SetXY(x:125, y:250); 
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 125, 243, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is not an array or object.');
    }
    $pdf->SetXY(110, 249);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(121, 256);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์ ยาวระ)'));
    $pdf->SetXY(123, 263);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(123, 272);
    $pdf->SetFont('THSarabunNew', '', 16);
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            $thai_date_formattedIndustrialEducationDean = $signature['thai_date_formattedIndustrialEducationDean'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedIndustrialEducationDean), 0, 1, 'L');
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is null or not valid. Please check the data source.');
    }
    $pdf->Output();

    unlink($signatureImage);
    unlink($signatureImagechairpersoncurriculum);
    unlink($signatureImageAcademicResearchAssociateDean);
} else {
    echo json_encode(["error" => "Document not found."]);
}
