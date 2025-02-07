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
        s.id_studyplan,
        sp.name_studyplan,
        s.abbreviate_student,
        s.address_student,
        s.email_student,
        s.tel_student,
        s.idstd_student,
        gs11.id_gs11report,
        gs11.name_gs11report,
        gs11.projectType_gs11report,
        gs11.projectThai_gs11report,
        gs11.projectEng_gs11report,
        gs11.advisorMain_gs11report,
        gs11.advisorSecond_gs11report,
        gs11.subjects_gs11report,
        gs11.gpa_gs11report,
        gs11.subjectsProject_gs11report,
        gs11.status_gs11report,
        gs11.docGs10rp_gs11report,
        gs11.docProjectdetails_gs11report,
        gs11.at_gs11report,
        gs11.signature_gs11report,
        gs11.date_gs11report,
        gs11.signName_gs11report
    FROM 
        gs11report gs11
    LEFT JOIN 
        student s ON gs11.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs11.id_gs11report = ?
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
        teachersign_sign
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
        $teacherSignatures[] = $row;  // เก็บข้อมูลลายเซ็นครูในอาร์เรย์
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
    // Assuming your date is fetched as $document['date_gs10report'] (e.g., '2024-12-14')
    $original_date = $document['date_gs11report']; // Format: YYYY-MM-DD
    $timestamp = strtotime($original_date); // Convert to timestamp

    // Thai month names
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

    // Extract day, month, and year
    $thai_day = date('d', $timestamp);
    $thai_month = $thai_months[date('m', $timestamp)];
    $thai_year = date('Y', $timestamp) + 543;

    // Combine into a readable format
    $thai_date_formatted = "วันที่ $thai_day เดือน $thai_month พ.ศ. $thai_year";

    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs11report'];
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
    $pdf->SetXY(170, 15);
    $pdf->SetFont('THSarabunNew', 'B', 14);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'คคอ. บว. 11'));
    $pdf->SetXY(150, 8);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 37, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(66, 42);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'));
    // ตำแหน่ง วัน เดืแน ปี
    $pdf->SetXY(190, 51.5);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    //$pdf->Cell(2, 0, iconv('UTF-8', 'cp874', 'วันที่.........เดือน.......................พ.ศ................'));
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formatted), 0, 1, 'R');
    $pdf->SetXY(20, 60);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เรื่อง'));
    $pdf->SetXY(35, 55);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ขอเสนอโครงการ'));

    $pdf->SetXY(65, 58);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(95, 58);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ '), 0, 1, 'L');
    $pdf->SetXY(20, 62);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(15, 10, iconv('UTF-8', 'cp874', 'เรียน'));
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(x: 80, y: 77.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(x: 159, y: 77.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(35, 73);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', "ข้าพเจ้า  (" . $document['prefix_student'] . ")............................................................................รหัสประจำตัว........................................."));

    $pdf->SetXY(20, 79);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท      แผน 1 แบบวิชาการ'));

    $pdf->SetXY(100, 82.5);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 1 แบบวิชาการ ภาคปกติ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

    $pdf->SetXY(125, 82.5);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 1 แบบวิชาการ ภาคสมทบ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(65, 85.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'แผน 2 แบบวิชาชีพ'));

    $pdf->SetXY(100, 88.5);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 2 แบบวิชาชีพ ภาคปกติ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

    $pdf->SetXY(125, 88.5);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 2 แบบวิชาชีพ ภาคสมทบ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(x: 38, y: 97);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(x: 94, y: 97);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(x: 174, y: 97);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['abbreviate_student']));
    $pdf->SetXY(20, 92.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'สาขาวิชา......................................................สาขา...........................................................อักษรย่อสาขาวิชา...................'));

    $pdf->SetXY(x: 60, y: 103.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20, 99);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก................................................................................................................................................'));

    $pdf->SetXY(x: 38, y: 109.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['email_student']));
    $pdf->SetXY(20, 105);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'E-mail: .............................................................................................'));

    $pdf->SetXY(x: 160, y: 109.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(117, 105);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'หมายเลขโทรศัพท์ มือถือ.......................................'));


    $pdf->SetXY(20, 115);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'ขอเสนอโครงการ  '));

    $pdf->SetXY(50, 118);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(80, 118);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(125, 115);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'เรื่อง'));

    $pdf->SetXY(20, 122);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', '(ภาษาไทย)'));

    $pdf->SetXY(x: 43, y: 127);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['projectThai_gs11report']));
    $pdf->SetXY(36, 122.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '......................................................................................................................................................................'));
    $pdf->SetXY(x: 28, y: 133.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(20, 129);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(20, 135);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', '(ภาษาอังกฤษ)'));

    $pdf->SetXY(x: 45, y: 140);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['projectEng_gs11report']));
    $pdf->SetXY(41, 135.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.................................................................................................................................................................'));

    $pdf->SetXY(20, 142);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(x: 80, y: 153);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['subjects_gs11report']));
    $pdf->SetXY(20, 148.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'ได้ลงทะเบียนรายวิชามาแล้ว จำนวน.................หน่วยกิต'));

    $pdf->SetXY(x: 140, y: 153);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['gpa_gs11report']));
    $pdf->SetXY(104, 148.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'มีคะแนนเฉลี่ยสะสม.............................'));

    $pdf->SetXY(x: 135, y: 159.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['subjectsProject_gs11report']));
    $pdf->SetXY(20, 155);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'ในภาคการศึกษานี้ได้ลงทะเบียนวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ จำนวน.................หน่วยกิต'));

    $pdf->SetXY(35, 164);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษา'));

    $pdf->SetXY(65, 167);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(93, 167);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(137, 164);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'ลงชื่อรับรอง'));

    // กำหนดลายเซ็นอาจารย์ในตัวแปรแยกกัน
    foreach ($teacherSignatures as $index => $teacherSignature) {
        $teacherSignatureData = $teacherSignature['teachersign_sign'];
        if (strpos($teacherSignatureData, 'data:image/png;base64,') === 0) {
            $teacherSignatureData = str_replace('data:image/png;base64,', '', $teacherSignatureData);
        }
        $teacherImage = 'signature_temp_teacher' . ($index + 1) . '.png';
        file_put_contents($teacherImage, base64_decode($teacherSignatureData));

        // ตรวจสอบว่าไฟล์ PNG ของอาจารย์ถูกต้อง
        if (getimagesize($teacherImage) === false) {
            unlink($teacherImage); // ลบไฟล์ถ้าพบว่าไม่ใช่ PNG ที่ถูกต้อง
            die("Error: Not a valid PNG file for teacher " . $teacherSignature['teachersign_nameTeacher']);
        }

        // แสดงลายเซ็นใน PDF ตามลำดับอาจารย์
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorMain_gs11report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 58, 169, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 30
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }
    $pdf->SetXY(48, 170.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '1............................................................................อาจารย์ที่ปรึกษาหลัก'));
    $pdf->SetXY(x: 58, y: 181);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['advisorMain_gs11report']));
    $pdf->SetXY(49, 176.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(............................................................................)'));
    // กำหนดลายเซ็นอาจารย์ในตัวแปรแยกกัน
    foreach ($teacherSignatures as $index => $teacherSignature) {
        $teacherSignatureData = $teacherSignature['teachersign_sign'];
        if (strpos($teacherSignatureData, 'data:image/png;base64,') === 0) {
            $teacherSignatureData = str_replace('data:image/png;base64,', '', $teacherSignatureData);
        }
        $teacherImage = 'signature_temp_teacher' . ($index + 1) . '.png';
        file_put_contents($teacherImage, base64_decode($teacherSignatureData));

        // ตรวจสอบว่าไฟล์ PNG ของอาจารย์ถูกต้อง
        if (getimagesize($teacherImage) === false) {
            unlink($teacherImage); // ลบไฟล์ถ้าพบว่าไม่ใช่ PNG ที่ถูกต้อง
            die("Error: Not a valid PNG file for teacher " . $teacherSignature['teachersign_nameTeacher']);
        }

        // แสดงลายเซ็นใน PDF ตามลำดับอาจารย์
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorSecond_gs11report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 58, 183, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 30
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }

    $pdf->SetXY(48, 182.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '2............................................................................อาจารย์ที่ปรึกษาร่วม'));

    $pdf->SetXY(x: 58, y: 193);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['advisorSecond_gs11report']));
    $pdf->SetXY(49, 188.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(............................................................................)'));


    $pdf->SetXY(20, 196);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'พร้อมนี้ได้แนบ  1. โครงการ                                                              (คคอ. บว.12)  จำนวน 1 ชุด'));
    $pdf->SetXY(60, 199);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(85, 199);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(41.5, 203);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '2. คำร้องขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษา                                                              (คคอ. บว.10)  จำนวน 1 ชุด'));
    $pdf->SetXY(98, 206);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(123, 206);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(35, 211);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));

    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 130, 213, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 50
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    }
    $pdf->SetXY(110, 223);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
    $pdf->SetXY(x: 130, y: 232.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(118, 228);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(.............................................................)'));

    $pdf->SetXY(20, 219);
    $pdf->SetFont('THSarabunNew', 'b', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', 'หมายเหตุ'));

    $pdf->SetXY(35, 219);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', '1. นักศึกษาต้องได้คะแนนเฉลี่ยสะสมไม่ต่ำกว่า 3.00 และต้องลงทะเบียนวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ไม่ต่ำกว่า 3 หน่วยกิต'));

    $pdf->SetXY(35, 223.5);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', '2. นักศึกษาต้องยื่นคำร้องเพื่อขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ (คคอ. บว.12) ภายใน 30 วัน นับจากวันที่ได้รับอนุมัติ'));

    $pdf->SetXY(39, 227.5);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', 'โครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'));
    $pdf->SetXY(165, 231.5);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', '/...ความเห็นประธาน...'));

    //----------- Page 2 ----------------------//
    $pdf->AddPage();
    $pdf->SetXY(20, 30);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'));
    $pdf->SetXY(25, 38);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ได้ตรวจสอบขอบเขตของโครงการ                                                               แล้ว'));
    $pdf->SetXY(80, 36);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    $pdf->SetXY(109, 36);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');


    $pdf->SetXY(25, 46);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เห็นสมควร'));
    $pdf->SetXY(48, 44);
    $pdf->checkboxMark(
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
        $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติ'), 0, 1, 'L');
    if (isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) && 
    $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว') {
    
    // ทำเครื่องหมาย Checkbox
    $pdf->SetXY(71, 44);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  พิจารณา'), 0, 1, 'L');
    
    // แสดงข้อความใน $description
    $description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';
    $pdf->SetXY(120, 45.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $description));
} else {
    // กรณีไม่ถูกปฏิเสธ
    $pdf->SetXY(71, 44);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  พิจารณา'), 0, 1, 'L');
}

    $pdf->SetXY(95, 44);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ...................................................................................'), 0, 1, 'L');

    foreach ($chairpersoncurriculumSignatures as $signature) {
        // แสดงรูปภาพลายเซ็น
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png'; // ไฟล์ PNG ที่แปลงจาก Base64
        $pdf->Image($signatureImagechairpersoncurriculum, 130, 50, 40);  // ตำแหน่งและขนาดของภาพ
    }
    $pdf->SetXY(110, 54);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));

    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(x: 130, y: 65.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $chairpersonName));
    $pdf->SetXY(118, 61);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(............................................................................)'));

    $pdf->SetXY(x: 124, y: 73);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }
    $pdf->SetXY(20, 80);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(20, 88);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เรียน คณบดีคณะครุศาสตร์อุตสาหกรรมตามเสนอ'));
    $pdf->SetXY(25, 95);
    $pdf->checkboxMark(
        isset($graduateOfficerSignatures[0]['gradofficersign_status']) &&
        $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติโครงการ'), 0, 1, 'L');
    $pdf->SetXY(75, 95);
    if (
        isset($graduateOfficerSignatures[0]['gradofficersign_status']) &&
        $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ) {
        // Checkbox: วิทยานิพนธ์
        if (isset($document['projectType_gs11report']) && $document['projectType_gs11report'] == 'วิทยานิพนธ์') {
            $pdf->SetXY(75, 95);
            $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
            $pdf->SetXY(105, 95);
            $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');
        }

        // Checkbox: การศึกษาค้นคว้าอิสระ
        if (isset($document['projectType_gs11report']) && $document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ') {
            $pdf->SetXY(75, 95);
            $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
            $pdf->SetXY(105, 95);
            $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');
        }
    } else {
        // กรณีที่ยังไม่ได้รับการอนุมัติ
        $pdf->SetXY(75, 95);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

        $pdf->SetXY(105, 95);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');
    }
    $pdf->SetXY(147, 97);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ตามเสนอ'));

    if (isset($graduateOfficerSignatures[0]['gradofficersign_status']) && 
    $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว') {
    
    // ทำเครื่องหมาย Checkbox
    $pdf->SetXY(25, 105);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    
    // แสดงข้อความใน $GradOfficeaDescription
    $GradOfficeaDescription = isset($graduateOfficerSignatures[0]['gradofficersign_description']) ? $graduateOfficerSignatures[0]['gradofficersign_description'] : '';
    $pdf->SetXY(45, 106.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $GradOfficeaDescription));

    $pdf->SetXY(29, 105);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ......................................................................................................................................................................'), 0, 1, 'L');
    $pdf->SetXY(22, 113);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'));
} else {
    // กรณีไม่ถูกปฏิเสธ
    $pdf->SetXY(25, 105);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    
    // แสดงข้อความ "อื่น ๆ" แต่ไม่ใส่ $GradOfficeaDescription
    $pdf->SetXY(29, 105);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ......................................................................................................................................................................'), 0, 1, 'L');
    $pdf->SetXY(22, 113);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'));
}


    $pdf->SetXY(130, 124.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    // $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', 'ยังไม่ขอแสดงความคิดเห็น'));
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 130, 115, 40);

                // ลบไฟล์หลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }
    $pdf->SetXY(110, 120);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersign_nameGradofficer']) ? $graduateOfficerSignatures[0]['gradofficersign_nameGradofficer'] : '';
    $pdf->SetXY(130, 131.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $GradOfficeName));
    $pdf->SetXY(118, 127);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(............................................................................)'));
    $pdf->SetXY(127, 139);
    $pdf->SetFont('THSarabunNew', '', 16);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];


            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer));
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
    }

    $pdf->SetXY(20, 151);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(25, 156);
    // $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->checkboxMark(
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
        $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นควรอนุมัติ'), 0, 1, 'L');
    if (isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) && 
    $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว') {
    
    // ทำเครื่องหมาย Checkbox
    $pdf->SetXY(25, 162);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    
    // แสดงข้อความใน $AcademicResearchAssociateDeanDescription
    $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';
    $pdf->SetXY(45, 163.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription));

    // แสดงข้อความ "อื่น ๆ" พร้อมเส้น ..................................................
    $pdf->SetXY(29, 162);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.....................................................................................................................................................................'), 0, 1, 'L');
} else {
    // กรณีไม่ถูกปฏิเสธ
    $pdf->SetXY(25, 162);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    
    // แสดงช่อง "อื่น ๆ" เปล่า ๆ
    $pdf->SetXY(29, 162);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.....................................................................................................................................................................'), 0, 1, 'L');
}

    $pdf->SetXY(20, 170);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'));

    //$pdf->SetXY(130, 176.5); 
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 130, 168, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
    }

    $pdf->SetXY(110, 172);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(119, 178);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'));
    $pdf->SetXY(124, 185);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(120, 197);
    $pdf->SetFont('THSarabunNew', '', 16);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
    }


    $pdf->SetXY(20, 206);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(25, 211);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติ'), 0, 1, 'L');

    if(isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
    $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'){
        $pdf->SetXY(25, 217);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);

        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ? $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';

        $pdf->SetXY(x: 45, y: 218.5);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription), 0, 1, 'L');

    }else{
        $pdf->SetXY(25, 217);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    }
    $pdf->SetXY(x: 29, y: 217);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.....................................................................................................................................................................'), 0, 1, 'L');
    $pdf->SetXY(20, 225);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'));

    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 130, 222, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is not an array or object.');
    }

    // $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'ยังไม่ขอแสดงความคิดเห็น'));
    $pdf->SetXY(110, 227);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(119, 233);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์  ยาวระ)'));
    $pdf->SetXY(122, 240);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(120, 252);
    $pdf->SetFont('THSarabunNew', '', 16);
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            $thai_date_formattedIndustrialEducationDean = $signature['thai_date_formattedIndustrialEducationDean'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedIndustrialEducationDean), 0, 1, 'L');
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is null or not valid. Please check the data source.');
    }

    //----------------------------------page 3 -------------------------------------------------------------------///
    $pdf->AddPage();

    $pdf->SetFillColor(192);
    $pdf->Image('img/logo.png', 15, 5, 15, 0);
    $pdf->SetXY(170, 15);
    $pdf->SetFont('THSarabunNew', 'B', 14);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'คคอ. บว. 20'));
    $pdf->SetXY(149, 8);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 37, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(85, 50);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'แบบฟอร์มเสนอโครงการ'));
    $pdf->SetXY(60, 57);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 18);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    $pdf->SetXY(95, 57);
    $pdf->checkboxMark($document['projectType_gs11report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 18);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');
    $pdf->SetXY(40, 68);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน วิทยาเขตขอนแก่น'));

    $pdf->SetXY(20, 80);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ชื่อเรื่อง (ภาษาไทย)'));
    $pdf->SetXY(60, 75.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(50, 80.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.......................................................................................................................................................'));
    $pdf->SetXY(60, 84);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['projectThai_gs11report']));
    $pdf->SetXY(20, 89);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));
    $pdf->SetXY(20, 97);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(20, 112);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ชื่อเรื่อง (ภาษาอังกฤษ)'));

    $pdf->SetXY(60, 116);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['projectEng_gs11report']));
    $pdf->SetXY(55, 112.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.................................................................................................................................................'));
    $pdf->SetXY(20, 121);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));
    $pdf->SetXY(20, 129);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(x: 90, y: 179.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(105, 167);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ผู้เสนอ'));
    $pdf->SetXY(80, 175);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '..................................................................................'));

    $pdf->SetXY(x: 110, y: 187.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(80, 183);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'รหัสประจำตัว'));
    $pdf->SetXY(101, 183);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '  ........................................................'));

    $pdf->SetXY(x: 110, y: 195.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(80, 191);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'สาขาวิชา'));
    $pdf->SetXY(103, 191);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................'));


    $pdf->SetXY(x: 120, y: 244.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['advisorMain_gs11report']));
    $pdf->SetXY(x: 120, y: 252.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['advisorSecond_gs11report']));
    $pdf->SetXY(80, 240);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาหลัก....................................................................................'));
    $pdf->SetXY(80, 248);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาร่วม......................................................................................'));
    $pdf->SetXY(155, 266);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '/ ...รายละเอียด...'));


    $pdf->Output();
    unlink($signatureImage);
    unlink($signatureImagechairpersoncurriculum);
    unlink($signatureImageAcademicResearchAssociateDean);
} else {
    echo json_encode(["error" => "Document not found."]);
}
?>