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
        gs10.date_gs10report,
        gs10.signName_gs10report
    FROM 
        gs10report gs10
    LEFT JOIN 
        student s ON gs10.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs10.id_gs10report = ?
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

    $stmt = $conn->prepare("
    SELECT 
        gradofficersignGs10_IdDocs,
        gradofficersignGs10_nameDocs,
        gradofficersignGs10_nameGradOffice,
        gradofficersignGs10_description,
        gradofficersignGs10_sign,
        gradofficersignGs10_status,
        gradofficersignGs10_at,
        gradofficersignGs10_advisorMain,
        gradofficersignGs10_advisorMainCriteria,
        gradofficersignGs10_advisorSecond,
        gradofficersignGs10_advisorSecondCriteria
    FROM 
        gradofficersigngs10
    WHERE 
        gradofficersignGs10_IdDocs = ?
    ");

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $GraduateOfficerSignatures = [];

    while ($row = $result->fetch_assoc()) {
        // แปลงวันที่ timestamp เป็นรูปแบบวันที่ไทย
        $timestamp = strtotime($row['gradofficersignGs10_at']);
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

        // แปลง Base64 เป็นไฟล์ PNG และเก็บชื่อไฟล์แบบไดนามิก
        $signatureDataGraduateOfficer = $row['gradofficersignGs10_sign'];
        if (strpos($signatureDataGraduateOfficer, 'data:image/png;base64,') === 0) {
            $signatureData = str_replace('data:image/png;base64,', '', $signatureDataGraduateOfficer);
            $uniqueFileName = 'signature_GraduateOfficer_' . uniqid() . '.png';
            file_put_contents($uniqueFileName, base64_decode($signatureData));
            $row['signature_file_path'] = $uniqueFileName; // เก็บชื่อไฟล์ในข้อมูล
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
    $original_date = $document['date_gs10report']; // Format: YYYY-MM-DD
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
    $signatureData = $document['signature_gs10report'];
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

    $pdf->SetFillColor(192);
    $pdf->Image('img/logo.png', 15, 5, 15, 0);

    // $pdf->Cell(40, 10, iconv('UTF-8', 'cp874', 'สวัสดี'));
    $pdf->SetFont('THSarabunNew', 'B', 14);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คคอ. บว. 10'), 0, 1, 'R');
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'), 0, 1, 'R');

    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'), 0, 1, 'C');
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $thai_date_formatted), 0, 1, 'R');


    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรื่อง'), 0, 0);
    $pdf->Ln(0);
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(90, 48);
    $pdf->checkboxMark($document['projectType_gs10report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(120, 48);
    $pdf->checkboxMark($document['projectType_gs10report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    // ข้อความ "ขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษา" อยู่ที่ตำแหน่ง x=30, y=51.5
    $pdf->SetXY(30, 48);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษา'), 0, 1, 'L');

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรียน'), 0, 0, '');
    $pdf->SetXY(30, 55.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');
    $pdf->Ln(10);
    $pdf->Cell(135, 0, iconv('UTF-8', 'cp874', $document['name_student']), 0, 1, 'C');
    $pdf->Cell(278, 0, iconv('UTF-8', 'cp874', $document['idstd_student']), 0, 1, 'C');
    $pdf->Cell(206.5, 2, iconv('UTF-8', 'cp874', "ข้าพเจ้า (" . $document['prefix_student'] . ")............................................................รหัสประจำตัว......................................................................."), 0, 1, 'C');

    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท'), 0, 1, 'L');

    $pdf->SetXY(58, 72);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'แผน 1 แบบวิชาการ'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(92, 75);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 1 แบบวิชาการ ภาคปกติ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(120, 75);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 1 แบบวิชาการ ภาคสมทบ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(58, 81);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'แผน 2 แบบวิชาชีพ'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(92, 84);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 2 แบบวิชาชีพ ภาคปกติ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(120, 84);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 2 แบบวิชาชีพ ภาคสมทบ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(30, 83);
    $pdf->Cell(0, 18, iconv('UTF-8', 'cp874', $document['major_student']), 0, 1, 'L');
    $pdf->SetXY(10.5, 83);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขาวิชา.......................................................'), 0, 1, 'L');
    $pdf->SetXY(85, 83);
    $pdf->Cell(0, 18, iconv('UTF-8', 'cp874', $document['branch_student']), 0, 1, 'L');
    $pdf->SetXY(75, 83);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขา.....................................................................'), 0, 1, 'L');
    $pdf->SetXY(168, 83);
    $pdf->Cell(0, 18, iconv('UTF-8', 'cp874', $document['abbreviate_student']), 0, 1, 'L');
    $pdf->SetXY(146, 83);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'อักษรย่อสาขา...................................'), 0, 1, 'L');
    $pdf->SetXY(55, 90);
    $pdf->Cell(0, 18, iconv('UTF-8', 'cp874', $document['address_student']), 0, 1, 'L');
    $pdf->SetXY(10.5, 90);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก.....................................................................................................................................................................'), 0, 1, 'L');
    $pdf->SetXY(30, 96);
    $pdf->Cell(0, 18, iconv('UTF-8', 'cp874', string: $document['email_student']), 0, 1, 'L');
    $pdf->SetXY(10.5, 96);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', string: 'E-mail:...................................................................................'), 0, 1, 'L');
    $pdf->SetXY(140, 96);
    $pdf->Cell(0, 18, iconv('UTF-8', 'cp874', $document['tel_student']), 0, 1, 'L');
    $pdf->SetXY(98, 96);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'หมายเลขโทรศัพท์ มือถือ.......................................................................'), 0, 1, 'L');

    $pdf->SetXY(10.5, 106);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'จัดทำโครงงาน'), 0, 1, 'L');

    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(40, 110);
    $pdf->checkboxMark($document['projectType_gs10report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(75, 110);
    $pdf->checkboxMark($document['projectType_gs10report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->SetXY(30, 112);
    $pdf->Cell(0, 10.5, iconv('UTF-8', 'cp874', $document['projectThai_gs10report']), 0, 1, 'L');
    $pdf->SetXY(10.5, 112);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '(ภาษาไทย)..........................................................................................................................................................................................................................'), 0, 1, 'L');
    $pdf->SetXY(10.5, 118);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '............................................................................................................................................................................................................................................'), 0, 1, 'L');

    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->SetXY(35, 124);
    $pdf->Cell(0, 10.5, iconv('UTF-8', 'cp874', $document['projectEng_gs10report']), 0, 1, 'L');
    $pdf->SetXY(10.5, 124);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '(ภาษาอังกฤษ).....................................................................................................................................................................................................................'), 0, 1, 'L');
    $pdf->SetXY(10.5, 130);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '............................................................................................................................................................................................................................................'), 0, 1, 'L');

    $pdf->SetXY(10.5, 140);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'มีความประสงค์ขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(10.5, 148);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ซึ่งเป็นการ'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(40, 148);
    $pdf->checkboxMark($document['advisorType_gs10report'] == 'แต่งตั้งใหม่', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  แต่งตั้งใหม่'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(70, 148);
    $pdf->checkboxMark($document['advisorType_gs10report'] == 'แต่งตั้งเพิ่ม', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  แต่งตั้งเพิ่ม'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(100, 148);
    $pdf->checkboxMark($document['advisorType_gs10report'] == 'แต่งตั้งแทนอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  แต่งตั้งแทนอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(10.5, 156);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ชุดเก่าที่ขอยกเลิก ดังมีรายชื่อต่อไปนี้'), 0, 1, 'L');


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
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorMainOld_gs10report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 130, 160, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 30
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }


    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(20, 163);
    $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', $document['advisorMainOld_gs10report']), 0, 1, 'L');
    $pdf->SetXY(10.5, 163);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '1. ....................................................................................'), 0, 1, 'L');
    $pdf->SetXY(93, 163);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาหลัก ลงชื่อ...................................................................'), 0, 1, 'L');


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
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorSecondOld_gs10report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 130, 167, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 30
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }
    $pdf->SetXY(20, 170);
    $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', $document['advisorSecondOld_gs10report']), 0, 1, 'L');
    $pdf->SetXY(10.5, 170);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '2. ....................................................................................'), 0, 1, 'L');
    $pdf->SetXY(93, 170);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาร่วม ลงชื่อ.....................................................................'), 0, 1, 'L');

    $pdf->SetXY(0, 178);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาวิทยานิพนธ์ชุดเก่าลงชื่อรับทราบ'), 0, 1, 'C');



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
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorMainNew_gs10report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 130, 180, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 30
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }

    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(20, 185);
    $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', $document['advisorMainNew_gs10report']), 0, 1, 'L');
    $pdf->SetXY(10.5, 185);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '1. ....................................................................................'), 0, 1, 'L');
    $pdf->SetXY(93, 185);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาหลัก ลงชื่อ...................................................................'), 0, 1, 'L');


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
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorSecondNew_gs10report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 130, 190, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 30
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }

    $pdf->SetXY(20, 192);
    $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', $document['advisorSecondNew_gs10report']), 0, 1, 'L');
    $pdf->SetXY(10.5, 192);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '2. ....................................................................................'), 0, 1, 'L');
    $pdf->SetXY(93, 192);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาร่วม ลงชื่อ.....................................................................'), 0, 1, 'L');

    $pdf->SetXY(10.5, 215);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'), 0, 1, 'L');


    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 120, 210, 30, 0, 'PNG'); // พิกัด X, Y, ขนาดกว้าง 50
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    }
    $pdf->SetXY(80, 220);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'ลงชื่อ..................................................'), 0, 1, 'C');
    $pdf->SetXY(120, 228);
    $pdf->Cell(0, 18, iconv('UTF-8', 'cp874', $document['name_student']), 0, 1, 'L');
    $pdf->SetXY(82, 228);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', '(....................................................)'), 0, 1, 'C');

    $pdf->UnderlineText(10.5, 255, 'หมายเหตุ', 'THSarabunNew', 'B', 12);
    $pdf->SetXY(28, 251.5);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '1. นักศึกษาแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระได้ 1-2 คน'), 0, 1, 'L');
    $pdf->SetXY(28, 257);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '2. กรณีอาจารย์บัณฑิตศึกษายังไม่เคยได้รับการแต่งตั้งหรือได้รับการแต่งตั้งแต่ครบวาระ 3 ปีแล้วให้แนบประวัติอาจารย์ (คคอ.บว. 31) มาด้วย'), 0, 1, 'L');

    $pdf->SetXY(28, 270);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '/..ความเห็นของประธาน...'), 0, 1, 'R');

    //------------------------------- หน้า 2 ------------------------------------
    $pdf->SetMargins(50, 30, 10);
    $pdf->AddPage();

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->SetXY(10.5, 20);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'), 0, 1, 'L');

    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(10.5, 28);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ได้พิจารณาคุณสมบัติแล้ว'), 0, 1, 'L');

    $pdf->SetXY(15, 36);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เห็นสมควร'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(50, 38.5);
    $pdf->checkboxMark(
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
            $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อนุมัติ'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ

    if (
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
        $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว'
    ) {
        $description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';
        $pdf->SetXY(90, 38.5);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->SetXY(110, 37);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $description), 0, 1, 'L');
        $pdf->SetXY(93, 38);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ..............................................................................................'), 0, 1, 'L');
        $pdf->SetXY(10, 44);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', '.....................................................................................................................................................................................................'), 0, 1, 'L');
    } else {
        // กรณีไม่เข้าเงื่อนไข ใช้ Default
        $pdf->SetXY(90, 38.5);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->SetXY(93, 38);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ..............................................................................................'), 0, 1, 'L');
        $pdf->SetXY(10, 44);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', '.....................................................................................................................................................................................................'), 0, 1, 'L');
    }

    foreach ($chairpersoncurriculumSignatures as $signature) {
        // แสดงรูปภาพลายเซ็น
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png'; // ไฟล์ PNG ที่แปลงจาก Base64
        $pdf->Image($signatureImagechairpersoncurriculum, 110, 52, 40);  // ตำแหน่งและขนาดของภาพ
    }
    $pdf->SetXY(70, 52);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ.........................................................'), 0, 1, 'C');

    $pdf->SetXY(120, 64);
    // ตรวจสอบว่าค่ามีข้อมูลหรือไม่ ถ้าไม่มีข้อมูลให้เป็นค่าว่าง
    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';

    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $chairpersonName), 0, 1, 'L');
    $pdf->SetXY(75, 60);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(.........................................................)'), 0, 1, 'C');
    $pdf->SetXY(108, 68);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];

        // ตอนนี้คุณสามารถใช้ตัวแปรนี้ได้ในที่นี้
        $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->SetXY(10.5, 76);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะฯ'), 0, 1, 'L');

    $pdf->SetXY(10.5, 84);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรียน   คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');

    $pdf->SetXY(21.5, 92);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ข้อมูลประกอบการพิจารณามีดังนี้'), 0, 1, 'L');

    $pdf->SetXY(50, 100);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'จำนวนภาระงานรวม'), 0, 1, 'L');
    $pdf->SetXY(140, 100);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'คุณสมบัติ'), 0, 1, 'L');



    $pdf->SetXY(10, 108);
    $pdf->SetFont('THSarabunNew', '', 14);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาหลัก'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    if (
        isset($graduateOfficerSignatures[0]['gradofficersignGs10_status']) &&
        (
            $graduateOfficerSignatures[0]['gradofficersignGs10_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว' ||
            $graduateOfficerSignatures[0]['gradofficersignGs10_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
        )
    ) {
        $pdf->SetXY(40, 110.5);
        $pdf->checkboxMark($document['projectType_gs10report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์ '), 0, 1, 'L');
        // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
        $pdf->SetXY(40, 115);
        $GradOfficeAadvisorMain = isset($graduateOfficerSignatures[0]['gradofficersignGs10_advisorMain']) ? $graduateOfficerSignatures[0]['gradofficersignGs10_advisorMain'] : '';
        $pdf->SetXY(110, 109);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $GradOfficeAadvisorMain), 0, 1, 'L');
        $pdf->SetXY(115, 110.5);
        $pdf->SetFont('THSarabunNew', '', 14);
        $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', 'คน'), 0, 1, 'L');

        $pdf->SetXY(65, 110.5);
        $pdf->checkboxMark($document['projectType_gs10report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ.....................'), 0, 1, 'L');
        // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
        $pdf->SetXY(126, 110.5);
        $pdf->checkboxMark(
            isset($graduateOfficerSignatures[0]['gradofficersignGs10_advisorMainCriteria']) &&
                $graduateOfficerSignatures[0]['gradofficersignGs10_advisorMainCriteria'] == 'ตรงตามเกณฑ์',
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', ' ตรงตามเกณฑ์'), 0, 1, 'L');
        // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
        $pdf->SetXY(155, 110.5);
        $pdf->checkboxMark(
            isset($graduateOfficerSignatures[0]['gradofficersignGs10_advisorMainCriteria']) &&
                $graduateOfficerSignatures[0]['gradofficersignGs10_advisorMainCriteria'] == 'ไม่ตรงตามเกณฑ์',
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ไม่ตรงตามเกณฑ์'), 0, 1, 'L');

        $pdf->SetXY(10, 115);
        $pdf->SetFont('THSarabunNew', '', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาร่วม'), 0, 1, 'L');
        // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
        $pdf->SetXY(40, 117.5);
        $pdf->checkboxMark($document['projectType_gs10report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

        $GradOfficeaAvisorSecond = isset($graduateOfficerSignatures[0]['gradofficersignGs10_advisorSecond']) ? $graduateOfficerSignatures[0]['gradofficersignGs10_advisorSecond'] : '';
        $pdf->SetXY(110, 116);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $GradOfficeaAvisorSecond), 0, 1, 'L');
        $pdf->SetXY(115, 117.5);
        $pdf->SetFont('THSarabunNew', '', 14);
        $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', 'คน'), 0, 1, 'L');

        $pdf->SetXY(65, 117.5);
        $pdf->checkboxMark($document['projectType_gs10report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ.....................'), 0, 1, 'L');
        // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
        $pdf->SetXY(126, 117.5);
        $pdf->checkboxMark(
            isset($graduateOfficerSignatures[0]['gradofficersignGs10_advisorSecondCriteria']) &&
                $graduateOfficerSignatures[0]['gradofficersignGs10_advisorSecondCriteria'] == 'ตรงตามเกณฑ์',
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', ' ตรงตามเกณฑ์'), 0, 1, 'L');
        // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
        $pdf->SetXY(155, 117.5);
        $pdf->checkboxMark(
            isset($graduateOfficerSignatures[0]['gradofficersignGs10_advisorSecondCriteria']) &&
                $graduateOfficerSignatures[0]['gradofficersignGs10_advisorSecondCriteria'] == 'ไม่ตรงตามเกณฑ์',
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ไม่ตรงตามเกณฑ์'), 0, 1, 'L');
    } else {
        $pdf->SetXY(40, 110.5);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์ '), 0, 1, 'L');

        $pdf->SetXY(115, 110.5);
        $pdf->SetFont('THSarabunNew', '', 14);
        $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', 'คน'), 0, 1, 'L');

        $pdf->SetXY(65, 110.5);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ.....................'), 0, 1, 'L');
        $pdf->SetXY(126, 110.5);
        $pdf->checkboxMark(
            false,
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', ' ตรงตามเกณฑ์'), 0, 1, 'L');
        $pdf->SetXY(155, 110.5);
        $pdf->checkboxMark(
            false,
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ไม่ตรงตามเกณฑ์'), 0, 1, 'L');

        $pdf->SetXY(10, 115);
        $pdf->SetFont('THSarabunNew', '', 14);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาร่วม'), 0, 1, 'L');
        // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
        $pdf->SetXY(40, 117.5);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');


        $pdf->SetXY(115, 117.5);
        $pdf->SetFont('THSarabunNew', '', 14);
        $pdf->Cell(0, 3, iconv('UTF-8', 'cp874', 'คน'), 0, 1, 'L');

        $pdf->SetXY(65, 117.5);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ.....................'), 0, 1, 'L');
        $pdf->SetXY(126, 117.5);
        $pdf->checkboxMark(
            false,
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', ' ตรงตามเกณฑ์'), 0, 1, 'L');
        $pdf->SetXY(155, 117.5);
        $pdf->checkboxMark(
            false,
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  ไม่ตรงตามเกณฑ์'), 0, 1, 'L');
    }
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 110, 123, 40);

                // ลบไฟล์หลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }

    $pdf->SetXY(70, 123);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ.........................................................'), 0, 1, 'C');

    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersignGs10_nameGradOffice']) ? $graduateOfficerSignatures[0]['gradofficersignGs10_nameGradOffice'] : '';
    $pdf->SetXY(116, 135);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $GradOfficeName), 0, 1, 'L');
    $pdf->SetXY(75, 131);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(.........................................................)'), 0, 1, 'C');
    $pdf->SetXY(108, 139);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];

            // ตอนนี้คุณสามารถใช้ตัวแปรนี้ได้ในที่นี้
            $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer), 0, 1, 'L');
        }
    } else {
        // แจ้งเตือนว่าไม่มีข้อมูลใน $graduateOfficerSignatures
        error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
    }

    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->SetXY(10.5, 147);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'), 0, 1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(20, 160);

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

        // ทำเครื่องหมาย Checkbox
        $pdf->SetXY(20, 168);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);

        // แสดงข้อความใน $description
        $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';
        $pdf->SetXY(45, 167);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription), 0, 1, 'L');
    } else {
        // กรณีไม่ถูกปฏิเสธ
        $pdf->SetXY(20, 168);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    }

    // แสดงข้อความ "อื่น ๆ" (คงอยู่เสมอ)
    $pdf->SetXY(25, 168);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ .........................................................................................................................................................................'), 0, 1, 'L');

    // เส้นบรรทัดเพิ่มเติม (คงอยู่เสมอ)
    $pdf->SetXY(10, 176);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '.....................................................................................................................................................................................................'), 0, 1, 'L');

    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 110, 178, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
    }

    $pdf->SetXY(70, 179);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ.........................................................'), 0, 1, 'C');
    $pdf->SetXY(75, 187);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตร์ตราจารย์ ดร.เฉลิมพล บุญทศ)'), 0, 1, 'C');
    $pdf->SetXY(75, 193);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'), 0, 1, 'C');

    $pdf->SetXY(108, 200);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];

            // ตอนนี้คุณสามารถใช้ตัวแปรนี้ได้ในที่นี้
            $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
        }
    } else {
        // แจ้งเตือนว่าไม่มีข้อมูลใน $AcademicResearchAssociateDeanSignatures
        error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
    }

    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(10.5, 208);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');

    $pdf->SetXY(20, 220);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
            $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เห็นควรอนุมัติ'), 0, 1, 'L');

    $pdf->SetXY(20, 228);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
            $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );
    if (
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'
    ) {

        // ทำเครื่องหมาย Checkbox
        $pdf->SetXY(20, 228);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);

        // แสดงข้อความใน $description
        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ? $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';
        $pdf->SetXY(45, 227);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription), 0, 1, 'L');
    } else {
        // กรณีไม่ถูกปฏิเสธ
        $pdf->SetXY(20, 228);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    }

    // แสดงข้อความ "อื่น ๆ" (คงอยู่เสมอ)
    $pdf->SetXY(24, 228);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ .........................................................................................................................................................................'), 0, 1, 'L');

    $pdf->SetXY(10, 236);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '.....................................................................................................................................................................................................'), 0, 1, 'L');


    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 115, 239, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is not an array or object.');
    }

    $pdf->SetXY(70, 239);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'ลงชื่อ.........................................................'), 0, 1, 'C');
    $pdf->SetXY(75, 246);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตร์ตราจารย์ประพันธ์ ยาวระ)'), 0, 1, 'C');
    $pdf->SetXY(75, 253);
    $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'C');


    $pdf->SetXY(108, 260);
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            $thai_date_formattedIndustrialEducationDean = $signature['thai_date_formattedIndustrialEducationDean'];

            // ตอนนี้คุณสามารถใช้ตัวแปรนี้ได้ในที่นี้
            $pdf->Cell(0, 15, iconv('UTF-8', 'cp874', $thai_date_formattedIndustrialEducationDean), 0, 1, 'L');
        }
    } else {
        // แจ้งเตือนว่าไม่มีข้อมูลใน $IndustrialEducationDeanSignatures
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
