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
        gs18.id_gs18report,
        gs18.name_gs18report,
        gs18.semesterAt_gs18report,
        gs18.academicYear_gs18report,
        gs18.examRoundProject_gs18report,
        gs18.courseCredits_gs18report,
        gs18.cumulativeGPA_gs18report,
        gs18.docGs41rp_gs18report,
        gs18.thesisAdvisor_gs18report,
        gs18.status_gs18report,
        gs18.at_gs18report,
        gs18.signature_gs18report,
        gs18.date_gs18report,
        gs18.signName_gs18report
    FROM 
        gs18report gs18
    LEFT JOIN 
        student s ON gs18.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs18.id_gs18report = ?
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
       gradofficersign_IdDocs,
       gradofficersign_nameDocs,
       gradofficersign_nameGradofficer,
       gradofficersign_description,
       gradofficersign_sign,
       gradofficersign_status,
       gradofficersign_at
    FROM 
        gradofficersign
    WHERE 
       gradofficersign_IdDocs = ?
    ");

    if (!$stmt) {
        die("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $GraduateOfficerSignatures = [];

    while ($row = $result->fetch_assoc()) {
        $timestamp = strtotime($row['gradofficersign_at']);
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

        $signatureDataGraduateOfficer = $row['gradofficersign_sign'];
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
    $date1_thai = convertToThaiDate($document['date_gs18report'] ?? '');

    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs18report'];
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
        function UnderlineText($x, $y, $text, $font_family, $font_style, $font_size)
        {
            $this->SetFont($font_family, $font_style, $font_size);
            $this->Text($x, $y, iconv('UTF-8', 'cp874', $text));
            $text_width = $this->GetStringWidth(iconv('UTF-8', 'cp874', $text));
            $this->Line($x, $y + 0.5, $x + $text_width, $y + 0.5); // Adjust the y position for the underline
        }
    }

    $pdf = new FPDF();
    $pdf = new PDF();

    // Add Thai font 
    $pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
    $pdf->AddFont('THSarabunNew', 'B', 'THSarabunNew_b.php');
    $pdf->AddPage('P', 'A4');
    // $pdf->SetFont('THSarabunNew','',14);

    $pdf->SetFillColor(192);
    $pdf->Image('img/logo.png', 15, 5, 15, 0);

    // $pdf->Cell(40, 10, iconv('UTF-8', 'cp874', 'สวัสดี'));
    $pdf->SetFont('THSarabunNew', 'B', 14);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คคอ. บว. 18'), 0, 1, 'R');
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'), 0, 1, 'R');

    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', '      แบบขอสอบประมวลความรู้'), 0, 1, 'C');

    $pdf->SetXY(170, 38);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $date1_thai), 0, 1, 'R');
    $pdf->SetXY(20, 48);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'เรื่อง'), 0, 0);

    // ข้อความ "ขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษา" อยู่ที่ตำแหน่ง x=30, y=51.5
    $pdf->SetXY(35, 48);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ขอสอบประมวลความรู้'), 0, 1, 'L');
    $pdf->SetXY(20, 55.5);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'เรียน'), 0, 0, '');
    $pdf->SetXY(35, 55.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');

    $pdf->SetXY(70, 61.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(160, 61.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(35, 62);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', "ข้าพเจ้า (" . $document['prefix_student'] . ")..................................................................................รหัสประจำตัว............................................."));

    $pdf->SetXY(62, 69);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['name_studyplan']));
    $pdf->SetXY(20, 70);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท.............................................................'));

    $pdf->SetXY(115, 73);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคปกติ') !== false,
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

    $pdf->SetXY(140, 73);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคสมทบ') !== false,
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(30, 73);
    $pdf->SetXY(37, 77.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(20, 73);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขาวิชา.......................................................'), 0, 1, 'L');

    $pdf->SetXY(95, 77.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(84, 73);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขา.....................................................................'), 0, 1, 'L');

    $pdf->SetXY(179, 77.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['abbreviate_student']));
    $pdf->SetXY(155, 73);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'อักษรย่อสาขา..........................'), 0, 1, 'L');
    $pdf->SetXY(55, 90);
    $pdf->SetXY(62, 84.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20, 80);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก...........................................................................................................................................................'), 0, 1, 'L');
    $pdf->SetXY(30, 96);
    $pdf->SetXY(35, 91.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['email_student']));
    $pdf->SetXY(167, 91.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(20, 87);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'E-mail:.........................................................................................................'), 0, 1, 'L');

    $pdf->SetXY(127, 87);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'หมายเลขโทรศัพท์ มือถือ........................................'), 0, 1, 'L');

    $pdf->SetXY(107.5, 105);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['semesterAt_gs18report']));
    $pdf->SetXY(118, 105);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['academicYear_gs18report']));
    $pdf->SetXY(20, 108);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'มีความประสงค์ขอสอบประมวลความรู้ในภาคการศึกษาที่............/..............ซึ่งเป็นการสอบ  '), 0, 1, 'L');

    $pdf->SetXY(20, 113);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'ข้าพเจ้าได้ดำเนินการตามข้อบังคับฯ มหาวิทยาลัย ว่าด้วยการศึกษาระดับบัณฑิตศึกษา หมวดที่ 6 การวัดผลและประเมินผล'));
    $pdf->SetXY(20, 119);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'การศึกษา แล้วคือ'));

    $pdf->SetXY(155, 109);
    $pdf->checkboxMark($document['examRoundProject_gs18report'] == 'ครั้งที่ 1', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ครั้งที่ 1'), 0, 1, 'L');

    $pdf->SetXY(177, 109);
    $pdf->checkboxMark($document['examRoundProject_gs18report'] == 'ครั้งที่ 2', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ครั้งที่ 2'), 0, 1, 'L');

    $pdf->SetXY(121, 124.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['courseCredits_gs18report']));
    $pdf->SetXY(180, 124.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['cumulativeGPA_gs18report']));
    $pdf->SetXY(30, 125);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '1. ศึกษารายวิชาครบตามที่กำหนดในหลักสูตรแล้ว  จำนวน..............หน่วยกิต ได้คะแนนเฉลี่ยสะสม............................'));
    $pdf->SetXY(30, 131);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '2. แนบแผนการเรียน แผน 2 แบบวิชาชีพ (คคอ. บว.41) จำนวน 1 ชุด'));

    $pdf->SetXY(33, 139);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));
    //   $pdf->SetXY(120, 154.5); 
    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 120, 145, 30, 0, 'PNG');
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    }
    $pdf->SetXY(105, 155);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
    $pdf->SetXY(120, 162.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(113, 163);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(.............................................................)'));

    //----------------------------- เส้นกรอบสี่เหลี่ยม-------------------------------------------------------------------------------------//
    $pdf->SetXY(20, 165);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '___________________________________________________________________________________________'));

    $pdf->SetXY(105, 169);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 172.9);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 176.8);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 180.8);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 184.7);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 188.6);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 192.6);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 196.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 200.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 204.45);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 208.45);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 212.4);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 216.38);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 220.35);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 180.8);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 224.33);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));
    $pdf->SetXY(105, 228.2);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '|'));

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->SetXY(20, 172);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ความเห็นของอาจารย์ที่ปรึกษา'), 0, 1, 'L');

    $pdf->SetXY(26, 182);
    $pdf->checkboxMark(
        isset($teacherSignatures[0]['teachersign_status']) &&
            $teacherSignatures[0]['teachersign_status'] == 'ได้รับการอนุมัติจากครูอาจารย์ที่ปรึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เห็นสมควรให้สอบได้'), 0, 1, 'L');

    if (
        isset($teacherSignatures[0]['teachersign_status']) &&
        $teacherSignatures[0]['teachersign_status'] == 'ถูกปฏิเสธจากครูอาจารย์ที่ปรึกษาแล้ว'
    ) {

        $teacherSignaturesDescription = isset($teacherSignatures[0]['teachersign_description'])
            ? $teacherSignatures[0]['teachersign_description']
            : '';
        $pdf->SetXY(44, 187);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $teacherSignaturesDescription));
        $pdf->SetXY(26, 190);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(26, 190);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ................................................................'), 0, 1, 'L');
    }


    //    $pdf->SetXY(25, 194); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'นิรัตน์ชา เนียมชาวนา'));
    $pdf->SetXY(20, 197);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '........................................................................................'), 0, 1, 'L');

    //   $pdf->SetXY(40, 210); 
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

        if ($teacherSignature['teachersign_nameTeacher'] === $document['thesisAdvisor_gs18report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 40, 205, 30, 0, 'PNG');
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }
    $pdf->SetXY(1, 208);
    $pdf->Cell(122, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'), 0, 1, 'C');
    $teacherSignaturesName = isset($teacherSignatures[0]['teachersign_nameTeacher'])
        ? $teacherSignatures[0]['teachersign_nameTeacher']
        : '';
    $pdf->SetXY(45, 217);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874',  $teacherSignaturesName));
    $pdf->SetXY(5, 215);
    $pdf->Cell(123, 15, iconv('UTF-8', 'cp874', '(...........................................................................)'), 0, 1, 'C');

    $pdf->SetXY(5, 222);
    foreach ($teacherSignatures as $signature) {
        $thai_date_formattedteachersign = $signature['thai_date_formattedteachersign'];
        $pdf->Cell(120, 15, iconv('UTF-8', 'cp874', $thai_date_formattedteachersign), 0, 1, 'C');
    }

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->SetXY(109, 172);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'), 0, 1, 'L');

    $pdf->SetXY(114, 182);
    $pdf->checkboxMark(
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
            $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เพื่อโปรดพิจารณาอนุมัติ'), 0, 1, 'L');

    if (
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
        $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว'
    ) {
        $description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';
        $pdf->SetXY(132, 187);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $description));
        $pdf->SetXY(114, 190);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ .............................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(114, 190);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ .............................................................................'), 0, 1, 'L');
    }

    $pdf->SetXY(109, 197);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '...................................................................................................'), 0, 1, 'L');

    //    $pdf->SetXY(140, 210); 
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png';
        $pdf->Image($signatureImagechairpersoncurriculum, 140, 200, 40);
    }
    $pdf->SetXY(128, 208);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ..........................................................................'), 0, 1, 'C');
    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(140, 217);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $chairpersonName));
    $pdf->SetXY(133, 215);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(...........................................................................)'), 0, 1, 'C');
    $pdf->SetXY(135, 222);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }

    //-----------------------------End เส้นกรอบสี่เหลี่ยม-------------------------------------------------------------------------------------//

    //----------------------------- ส่วนท้าย ----------------------------------------------------------------------------------------------//
    $pdf->SetXY(20, 218);
    $pdf->SetFont('THSarabunNew', 'b', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', 'หมายเหตุ'));
    $pdf->SetXY(35, 218);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', '1. นักศึกษาต้องศึกษารายวิชาครบตามที่กำหนดในหลักสูตรและได้คะแนนเฉลี่ยนสะสมไม่ต่ำกว่า 3.00'));
    $pdf->SetXY(35, 223.5);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', '2. นักศึกษาผู้ที่สอบไม่ผ่านครั้งที่ 1 มีสิทธิ์สอบแก้ตัวได้อีก 1 ครั้ง ภายในเวลา 1 ปี แต่ไม่เร็วกว่า 60 วันนับจากวันสอบวันแรก '));
    $pdf->SetXY(165, 231);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', '/....บันทึกเจ้าหน้าที่.....................'));

    //------------------------------- หน้า 2 ------------------------------------
    $pdf->SetMargins(50, 30, 10);
    $pdf->AddPage();

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->SetXY(20, 20);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะฯ'), 0, 1, 'L');

    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(20, 27);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรียน  คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');

    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(25, 34);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ได้ตรวจสอบคุณสมบัติของนักศึกษาแล้ว'), 0, 1, 'L');


    $pdf->SetXY(26, 44);
    $pdf->checkboxMark(
        isset($graduateOfficerSignatures[0]['gradofficersign_status']) &&
            $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติให้สอบประมวลความรู้ได้'), 0, 1, 'L');

    if (
        isset($graduateOfficerSignatures[0]['gradofficersign_status']) &&
        $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ) {
        $DescriptiontGs18 = isset($graduateOfficerSignatures[0]['gradofficersign_description']) ?
            $graduateOfficerSignatures[0]['gradofficersign_description'] : '';

        $pdf->SetXY(45, 49);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $DescriptiontGs18));
        $pdf->SetXY(26, 52);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(26, 52);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................................................................'), 0, 1, 'L');
    }

    $pdf->SetXY(20, 56);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'), 0, 1, 'L');

    //$pdf->SetXY(120 , 72);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 125, 66, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }
    $pdf->SetXY(95, 70);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................................'), 0, 1, 'C');
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersign_nameGradofficer'])
        ? $graduateOfficerSignatures[0]['gradofficersign_nameGradofficer'] : '';
    $pdf->SetXY(130, 80);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $GradOfficeName));
    $pdf->SetXY(105, 78);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(..................................................................................)'), 0, 1, 'C');
    $pdf->SetXY(125, 84);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];


            $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer));
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
    }
    $pdf->SetXY(20, 100);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'), 0, 1, 'L');
    $pdf->SetXY(26, 114);
    $pdf->checkboxMark(
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
            $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เห็นควรอนุมัติ'), 0, 1, 'L');

    if (
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
        $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว'
    ) {
        $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';
        $pdf->SetXY(43, 120);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription));
        $pdf->SetXY(26, 123);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(26, 120);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................................................'), 0, 1, 'L');
    }
    $pdf->SetXY(20, 130);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '...........................................................................................................................................................................................'), 0, 1, 'L');

    //  $pdf->SetXY(120, 142);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 125, 138, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
    }
    $pdf->SetXY(95, 140);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................................'), 0, 1, 'C');
    $pdf->SetXY(100, 148);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'), 0, 1, 'C');
    $pdf->SetXY(100, 155);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'), 0, 1, 'C');
    $pdf->SetXY(123, 162);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
            $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
    }

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->SetXY(20, 177);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');

    $pdf->SetXY(26, 190);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
            $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อนุมัติ'), 0, 1, 'L');

    if (
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'
    ) {
        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ?
            $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';
        $pdf->SetXY(45, 195);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription));
        $pdf->SetXY(26, 198);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................................................'), 0, 1, 'L');
    } else {
        $pdf->SetXY(26, 198);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................................................'), 0, 1, 'L');
    }

    $pdf->SetXY(20, 205);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'), 0, 1, 'L');

    //    $pdf->SetXY(120, 217); 
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 125, 208, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is not an array or object.');
    }
    $pdf->SetXY(95, 215);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................................'), 0, 1, 'C');
    $pdf->SetXY(95, 222);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์ ยาวระ)'), 0, 1, 'C');
    $pdf->SetXY(95, 229);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'C');
    $pdf->SetXY(120, 235);
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            $thai_date_formattedIndustrialEducationDean = $signature['thai_date_formattedIndustrialEducationDean'];
            $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedIndustrialEducationDean), 0, 1, 'L');
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is null or not valid. Please check the data source.');
    }
    // $pdf->Output('D', "แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ-{$name_gs10rp}.pdf");

    $pdf->Output();

    unlink($signatureImage);
    unlink($signatureImagechairpersoncurriculum);
    unlink($signatureImageAcademicResearchAssociateDean);
} else {
    echo json_encode(["error" => "Document not found."]);
}
