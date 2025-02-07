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
        gs23.id_gs23report,
        gs23.name_gs23report,
        gs23.projectType_gs23report,
       gs23.projectThai_gs23report,
        gs23.projectEng_gs23report,
        gs23.projectDefenseDate_gs23report,
        gs23.projectDefenseResult_gs23report,
        gs23.IndependentStudyAdvisor_gs23report,
        gs23.status_gs23report,
        gs23.at_gs23report,
        gs23.signature_gs23report,
        gs23.date_gs23report,
        gs23.signName_gs23report
    FROM 
        gs23report gs23
    LEFT JOIN 
        student s ON gs23.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs23.id_gs23report = ?
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
       gradofficersignGs23_IdDocs,
       gradofficersignGs23_nameDocs,
       gradofficersignGs23_nameGradoffice,
       gradofficersignGs23_description,
       gradofficersignGs23_sign,
       gradofficersignGs23_status,
       gradofficersignGs23_ThesisDocDate,
       gradofficersignGs23_cumulativeGPAStudent,
       gradofficersignGs23_knowledgeExamPass,
       gradofficersignGs23_at
    FROM 
        gradofficersigngs23
    WHERE 
       gradofficersignGs23_IdDocs = ?
    ");

    if (!$stmt) {
        die("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $GraduateOfficerSignatures = [];

    while ($row = $result->fetch_assoc()) {
        $timestamp = strtotime($row['gradofficersignGs23_at']);
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

        // แปลงวันที่ใน gradofficersignGs23_ThesisDocDate
        if (!empty($row['gradofficersignGs23_ThesisDocDate'])) {
            $timestamp_ThesisDocDate = strtotime($row['gradofficersignGs23_ThesisDocDate']);
            $thai_day_ThesisDocDate = date('d', $timestamp_ThesisDocDate);
            $thai_month_ThesisDocDate = $thai_months[date('m', $timestamp_ThesisDocDate)];
            $thai_year_ThesisDocDate = date('Y', $timestamp_ThesisDocDate) + 543;

            $thai_date_projectThesisDocDate = "วันที่ $thai_day_ThesisDocDate เดือน $thai_month_ThesisDocDate พ.ศ. $thai_year_ThesisDocDate";
            $row['thai_date_projectThesisDocDate'] = $thai_date_projectThesisDocDate;
            $row['thai_day_ThesisDocDate'] = $thai_day_ThesisDocDate;
            $row['thai_month_ThesisDocDate'] = $thai_month_ThesisDocDate;
            $row['thai_year_ThesisDocDate'] = $thai_year_ThesisDocDate;
        } else {
            $row['thai_date_projectThesisDocDate'] = null;
            $row['thai_day_ThesisDocDate'] = null;
            $row['thai_month_ThesisDocDate'] = null;
            $row['thai_year_ThesisDocDate'] = null;
        }

        $signatureDataGraduateOfficer = $row['gradofficersignGs23_sign'];
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
    $date1_thai = convertToThaiDate($document['date_gs23report'] ?? '');
    $date2_thai = convertToThaiDate($document['projectDefenseDate_gs23report'] ?? '');


    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs23report'];
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
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'คคอ. บว. 23'));
    $pdf->SetXY(150, 6.5);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 40, iconv('UTF-8', 'cp874', 'ครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(65, 42);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์'));

    $pdf->SetXY(130, 52);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date1_thai));
    $pdf->SetXY(20, 62);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เรื่อง'));
    $pdf->SetXY(35, 57);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์'));

    $pdf->SetXY(20, 64);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(15, 10, iconv('UTF-8', 'cp874', 'เรียน'));
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));


    $pdf->SetXY(x: 82, y: 80);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(36, 76);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', "ข้าพเจ้า (" . $document['prefix_student'] . ").....................................................................................(พิมพ์หรือเขียนตัวบรรจง)"));

    $pdf->SetXY(x: 79, y: 87);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_studentEng']));
    $pdf->SetXY(48, 83);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', "(" . $document['prefix_studentEng'] . ").............................................................................................(พิมพ์หรือเขียนตัวบรรจง)"));

    $pdf->SetXY(x: 45, y: 94);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(20, 90);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'รหัสประจำตัว............................................................'));

    $pdf->SetXY(x: 70, y: 100);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_studyplan']));
    $pdf->SetXY(20, 96);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท  แผน.............................................................'));

    $pdf->SetXY(125, 98);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคปกติ') !== false,
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

    $pdf->SetXY(150, 98);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคสมทบ') !== false,
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(x: 40, y: 106);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(x: 100, y: 106);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(x: 178, y: 106);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $document['abbreviate_student']));
    $pdf->SetXY(20, 105);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สาขาวิชา......................................................สาขา................................................................อักษรย่อสาขาวิชา.................'));

    $pdf->SetXY(x: 60, y: 113);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20, 112);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก...................................................................................................................................................'));

    $pdf->SetXY(x: 40, y: 119);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['email_student']));
    $pdf->SetXY(x: 152, y: 119);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(20, 118);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'E-mail:......................................................................................หมายเลขโทรศัพท์ มือถือ..................................................'));



    $pdf->SetXY(x: 105, y: 126);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date2_thai));
    $pdf->SetXY(20, 125);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ได้ดำเนินการสอบป้องกันการศึกษาค้นคว้าอิสระแล้วเมื่อ.......................................................................'));

    $pdf->SetXY(20, 128);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'ผลการสอบ'));

    $pdf->SetXY(45, 132);
    $pdf->checkboxMark($document['projectDefenseResult_gs23report'] == 'ผ่าน (ส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ไม่เกิน 15 วันนับจากวันสอบ)', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ผ่าน (ส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ไม่เกิน 15 วันนับจากวันสอบ)'), 0, 1, 'L');

    $pdf->SetXY(45, 139);
    $pdf->checkboxMark($document['projectDefenseResult_gs23report'] == 'ผ่านโดยมีเงื่อนไข (ส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ไม่เกิน 30 วันนับจากวันสอบ)', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ผ่านโดยมีเงื่อนไข (ส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ไม่เกิน 30 วันนับจากวันสอบ) '), 0, 1, 'L');
    $pdf->SetXY(20, 146);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'บัดนี้ได้ปรับปรุงแก้ไขเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ตามข้อเสนอแนะของคณะกรรมการสอบเรียบร้อยแล้ว '), 0, 1, 'L');
    $pdf->SetXY(20, 153);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'แลัวได้ส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ พร้อม Flash drive (file .doc และ .pdf)'), 0, 1, 'L');
    $pdf->SetXY(20, 160);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ชื่อการศึกษาค้นคว้าอิสระ (พิมพ์หรือเขียนตัวบรรจง)'), 0, 1, 'L');

    $pdf->SetXY(x: 50, y: 168);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['projectThai_gs23report']));
    $pdf->SetXY(26, 164);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(ภาษาไทย)....................................................................................................................................................................'));
    $pdf->SetXY(x: 50, y: 174);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(20, 170);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.............................................................................................................................................................................................'));
    $pdf->SetXY(x: 50, y: 180);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['projectEng_gs23report']));
    $pdf->SetXY(26, 176);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(ภาษาอังกฤษ)...............................................................................................................................................................'));
    $pdf->SetXY(x: 50, y: 186);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(20, 182);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.............................................................................................................................................................................................'));

    // $pdf->SetXY(x: 113, y: 201);
    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 115, 185, 30, 0, 'PNG');
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    }
    $pdf->SetXY(100, 200);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
    $pdf->SetXY(x: 113, y: 208);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(108, 207);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(.............................................................)'));

    $pdf->SetXY(20, 216);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของอาจารย์ที่ปรึกษาการศึกษาค้นคว้าอิระ'));
    $pdf->SetXY(20, 220);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'เรียน คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(22, 228);
    $pdf->checkboxMark(
        isset($teacherSignatures[0]['teachersign_status']) &&
            $teacherSignatures[0]['teachersign_status'] == 'ได้รับการอนุมัติจากครูอาจารย์ที่ปรึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ได้พิจารณาแล้ว จึงเรียนมาเพื่อโปรดทราบและดำเนินการต่อไป'));

    if (
        isset($teacherSignatures[0]['teachersign_status']) &&
        $teacherSignatures[0]['teachersign_status'] == 'ถูกปฏิเสธจากครูอาจารย์ที่ปรึกษาแล้ว'
    ) {
        $teacherSignaturesDescription = isset($teacherSignatures[0]['teachersign_description'])
            ? $teacherSignatures[0]['teachersign_description']
            : '';
        $pdf->SetXY(x: 45, y: 236);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $teacherSignaturesDescription));
        $pdf->SetXY(22, 235);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(22, 235);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'), 0, 1, 'L');
    }

    foreach ($teacherSignatures as $index => $teacherSignature) {
        $teacherSignatureData = $teacherSignature['teachersign_sign'];

        // ตรวจสอบและลบ prefix "data:image/png;base64," หากมี
        if (strpos($teacherSignatureData, 'data:image/png;base64,') === 0) {
            $teacherSignatureData = str_replace('data:image/png;base64,', '', $teacherSignatureData);
        }

        // ตั้งชื่อไฟล์ชั่วคราว
        $teacherImage = 'signature_temp_teacher' . ($index + 1) . '.png';

        // สร้างไฟล์ภาพจาก Base64
        file_put_contents($teacherImage, base64_decode($teacherSignatureData));

        // ตรวจสอบว่าไฟล์เป็น PNG ที่ถูกต้องหรือไม่
        if (getimagesize($teacherImage) === false) {
            unlink($teacherImage); // ลบไฟล์ที่ไม่ใช่ PNG
            die("Error: Not a valid PNG file for teacher " . $teacherSignature['teachersign_nameTeacher']);
        }

        // เช็คว่าเป็นลายเซ็นของอาจารย์ที่ต้องการหรือไม่
        if ($teacherSignature['teachersign_nameTeacher'] === $document['IndependentStudyAdvisor_gs23report']) {
            if (file_exists($teacherImage)) {
                // เพิ่มลายเซ็นเข้าไปใน PDF
                $pdf->Image($teacherImage, 125, 240, 30, 0, 'PNG');

                // ลบไฟล์ชั่วคราวหลังใช้งาน
                unlink($teacherImage);
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        } else {
            // ลบไฟล์หากไม่ใช่ลายเซ็นของอาจารย์ที่ต้องการ
            unlink($teacherImage);
        }
    }

    $pdf->SetXY(100, 248);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงนาม..............................................................................'));
    $teacherSignaturesName = isset($teacherSignatures[0]['teachersign_nameTeacher'])
        ? $teacherSignatures[0]['teachersign_nameTeacher']
        : '';
    $pdf->SetXY(x: 126, y: 255);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $teacherSignaturesName));
    $pdf->SetXY(108, 253.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(............................................................................)'));

    $pdf->SetXY(118, 259);
    $pdf->SetFont('THSarabunNew', '', 16);
    foreach ($teacherSignatures as $signature) {
        $thai_date_formattedteachersign = $signature['thai_date_formattedteachersign'];
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', $thai_date_formattedteachersign), 0, 1, 'L');
    }
    $pdf->SetXY(165, 272);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '/...ความเห็นประธาน...'));


    //------------------ Page 2 ----------------------------------------------------------------- 2 //
    $pdf->AddPage();

    $pdf->SetXY(20, 20);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'));
    $pdf->SetXY(20, 24);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'เรียน คณบดีคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(31, 31);
    $pdf->checkboxMark(
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
            $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ได้พิจารณาแล้ว จึงเรียนมาเพื่อโปรดทราบและดำเนินการต่อไป'), 0, 1, 'L');

    if (
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
        $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว'
    ) {
        $description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';
        $pdf->SetXY(x: 50, y: 39);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $description));
        $pdf->SetXY(31, 38);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.............................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(31, 38);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.............................................................................................................................................................'), 0, 1, 'L');
    }

    foreach ($chairpersoncurriculumSignatures as $signature) {
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png';
        $pdf->Image($signatureImagechairpersoncurriculum, 125, 46, 40);
    }
    $pdf->SetXY(110, 55);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงนาม..............................................................................'));
    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(x: 130, y: 63);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $chairpersonName));
    $pdf->SetXY(118, 62);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(............................................................................)'));
    $pdf->SetXY(125, 69);
    $pdf->SetFont('THSarabunNew', '', 16);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }
    /////////////////////////////////////////////
    $pdf->SetXY(20, 80);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะ ฯ'));
    $pdf->SetXY(20, 85);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'เรียน  คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(30, 92);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ได้รับเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ พร้อม Flash drive (file .doc และ .pdf) จำนวน 1 อัน'));
    foreach ($graduateOfficerSignatures as $signature) {
        $thai_day_ThesisDocDate = $signature['thai_day_ThesisDocDate'];
        $thai_month_ThesisDocDate = $signature['thai_month_ThesisDocDate'];
        $thai_year_ThesisDocDate = $signature['thai_year_ThesisDocDate'];

        // แสดงข้อมูลวันที่ใน PDF
        $pdf->SetXY(x: 30, y: 99);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_day_ThesisDocDate ?? ''));

        $pdf->SetXY(x: 50, y: 99);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_month_ThesisDocDate ?? ''));

        $pdf->SetXY(x: 81, y: 99);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_year_ThesisDocDate ?? ''));
    }

    $pdf->SetXY(20, 98);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'วันที่..............เดือน........................พ.ศ.................... และได้ตรวจสอบคุณสมบัติของนักศึกษาแล้วดังนี้'));
    $GradOfficecumulativeGPAStudent = isset($graduateOfficerSignatures[0]['gradofficersignGs23_cumulativeGPAStudent'])
        ? $graduateOfficerSignatures[0]['gradofficersignGs23_cumulativeGPAStudent'] : '';
    $pdf->SetXY(x: 140, y: 105);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $GradOfficecumulativeGPAStudent));
    $pdf->SetXY(33, 104);
    $pdf->checkboxMark(
        !empty($GradOfficecumulativeGPAStudent),
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ศึกษารายวิชาครบตามที่กำหนดในหลักสูตร มีคะแนนเฉลี่ยสะสม......................'));

    $pdf->SetXY(33, 111);
    $GradOfficeKnowledgeExamPass = isset($graduateOfficerSignatures[0]['gradofficersignGs23_knowledgeExamPass'])
        ? $graduateOfficerSignatures[0]['gradofficersignGs23_knowledgeExamPass'] : '';
    $pdf->checkboxMark($GradOfficeKnowledgeExamPass == 1, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  สอบผ่านการสอบประมวลความรู้'));

    $pdf->SetXY(33, 118);
    $pdf->checkboxMark(
        isset($graduateOfficerSignatures[0]['gradofficersignGs23_status']) &&
            $graduateOfficerSignatures[0]['gradofficersignGs23_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติผลการสำเร็จการศึกษา'));

    if (
        isset($graduateOfficerSignatures[0]['gradofficersignGs23_status']) &&
        $graduateOfficerSignatures[0]['gradofficersignGs23_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ) {
        $DescriptiontGs23 = isset($graduateOfficerSignatures[0]['gradofficersignGs23_description']) ?
            $graduateOfficerSignatures[0]['gradofficersignGs23_description'] : '';

        $pdf->SetXY(x: 125, y: 126);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $DescriptiontGs23));
        $pdf->SetXY(33, 125);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ยังไม่สามารถอนุมัติผลการสำเร็จการศึกษาได้ เนื่องจาก..............................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(33, 125);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ยังไม่สามารถอนุมัติผลการสำเร็จการศึกษาได้ เนื่องจาก..............................................................................'), 0, 1, 'L');
    }


    $pdf->SetXY(39, 132);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '.....................................................................................................................................................................'));

    $pdf->SetXY(39, 139);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรด'));

    $pdf->SetXY(75, 139);
    $pdf->checkboxMark(
        isset($graduateOfficerSignatures[0]['gradofficersignGs23_status']) &&
            (
                $graduateOfficerSignatures[0]['gradofficersignGs23_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว' ||
                $graduateOfficerSignatures[0]['gradofficersignGs23_status'] == "ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว"
            ),
        4,
        'THSarabunNew',
        16
    );

    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ทราบ'));

    $pdf->SetXY(95, 139);
    $pdf->checkboxMark(
        isset($graduateOfficerSignatures[0]['gradofficersignGs23_status']) &&
            $graduateOfficerSignatures[0]['gradofficersignGs23_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติผลการสำเร็จการศึกษา'));

    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 126, 148, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }
    $pdf->SetXY(110, 155);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงนาม..............................................................................'));
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersignGs23_nameGradoffice'])
        ? $graduateOfficerSignatures[0]['gradofficersignGs23_nameGradoffice'] : '';
    $pdf->SetXY(x: 130, y: 163);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $GradOfficeName));
    $pdf->SetXY(118, 162);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(............................................................................)'));
    $pdf->SetXY(123, 170);
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
    $pdf->SetXY(20, 175);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(25, 179);
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
        $pdf->SetXY(x: 45, y: 186);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $AcademicResearchAssociateDeanDescription));
        $pdf->SetXY(25, 185);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ...................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(25, 185);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ...................................................................................................................'), 0, 1, 'L');
    }

    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 125, 193, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
    }
    $pdf->SetXY(110, 198);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(117, 204);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'));
    $pdf->SetXY(124, 210);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(123, 218);
    $pdf->SetFont('THSarabunNew', '', 16);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
    }

    $pdf->SetXY(20, 223);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(33, 227);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
            (
                $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว' ||
                $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'
            ),
        4,
        'THSarabunNew',
        16
    );

    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ทราบ'), 0, 1, 'L');
    $pdf->SetXY(33, 233);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
            $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติผลการสำเร็จการศึกษา'), 0, 1, 'L');

    if (
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'
    ) {
        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ?
            $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';
        $pdf->SetXY(x: 55, y: 240);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription));
        $pdf->SetXY(33, 239);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(33, 239);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................'), 0, 1, 'L');
    }

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
    $pdf->SetXY(110, 252);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(118, 258);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์ ยาวระ)'));
    $pdf->SetXY(120, 265);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(122, 274);
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
