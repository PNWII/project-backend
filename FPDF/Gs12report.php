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
        gs12.id_gs12report,
        gs12.name_gs12report,
        gs12.projectType_gs12report,
        gs12.projectThai_gs12report,
        gs12.projectEng_gs12report,
        gs12.advisorMain_gs12report,
        gs12.advisorSecond_gs12report,
        gs12.examRequestDate_gs12report,
        gs12.examRequestTime_gs12report,
        gs12.examRequestRoom_gs12report,
        gs12.examRequestFloor_gs12report,
        gs12.examRequestBuilding_gs12report,
        gs12.status_gs12report,
        gs12.at_gs12report,
        gs12.signature_gs12report,
        gs12.date_gs12report,
        gs12.signName_gs12report
    FROM 
        gs12report gs12
    LEFT JOIN 
        student s ON gs12.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs12.id_gs12report = ?
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
        gradofficersignGs12_IdDocs,
        gradofficersignGs12_nameDocs,
        gradofficersignGs12_nameGradOffice,
        gradofficersignGs12_description,
        gradofficersignGs12_sign,
        gradofficersignGs12_status,
        gradofficersignGs12_projectApprovalDate,
        gradofficersignGs12_at
    FROM 
        gradofficersigngs12
    WHERE 
        gradofficersignGs12_IdDocs = ?
    ");

    if (!$stmt) {
        die("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $GraduateOfficerSignatures = [];

    while ($row = $result->fetch_assoc()) {
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

        // แปลงวันที่ใน gradofficersignGs12_at
        $timestamp_at = strtotime($row['gradofficersignGs12_at']);
        $thai_day_at = date('d', $timestamp_at);
        $thai_month_at = $thai_months[date('m', $timestamp_at)];
        $thai_year_at = date('Y', $timestamp_at) + 543;

        $thai_date_formattedGraduateOfficer = "วันที่ $thai_day_at เดือน $thai_month_at พ.ศ. $thai_year_at";
        $row['thai_date_formattedGraduateOfficer'] = $thai_date_formattedGraduateOfficer;

        // แปลงวันที่ใน gradofficersignGs12_projectApprovalDate
        if (!empty($row['gradofficersignGs12_projectApprovalDate'])) {
            $timestamp_approval = strtotime($row['gradofficersignGs12_projectApprovalDate']);
            $thai_day_approval = date('d', $timestamp_approval);
            $thai_month_approval = $thai_months[date('m', $timestamp_approval)];
            $thai_year_approval = date('Y', $timestamp_approval) + 543;

            $thai_date_projectApproval = "วันที่ $thai_day_approval เดือน $thai_month_approval พ.ศ. $thai_year_approval";
            $row['thai_date_projectApproval'] = $thai_date_projectApproval;
            $row['thai_day_approval'] = $thai_day_approval;
             $row['thai_month_approval'] = $thai_month_approval;
             $row['thai_year_approval'] = $thai_year_approval;


        } else {
            $row['thai_date_projectApproval'] = null;
            $row['thai_day_approval'] = null;
            $row['thai_month_approval'] = null;
            $row['thai_year_approval'] = null;
        }

        // จัดการข้อมูลลายเซ็น
        $signatureDataGraduateOfficer = $row['gradofficersignGs12_sign'];
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
    $date1_thai = convertToThaiDate($document['date_gs12report'] ?? '');
    $date2_thai = convertToThaiDate($document['examRequestDate_gs12report'] ?? '');

    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs12report'];
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
    $pdf->AddPage('P', 'A4');

    $pdf->SetFillColor(192);
    $pdf->Image('img/logo.png', 15, 5, 15, 0);

    $pdf->SetXY(170, 15);
    $pdf->SetFont('THSarabunNew', 'B', 14);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'คคอ. บว. 12'));
    $pdf->SetXY(150, 8);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 37, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(66, 42);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'));

    $pdf->SetXY(190, 51.5);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date1_thai), 0, 1, 'R');
    $pdf->SetXY(20, 62);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เรื่อง'));
    $pdf->SetXY(35, 57);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ขอสอบหัวข้อ'));

    $pdf->SetXY(60, 60);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(90, 60);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ '), 0, 1, 'L');
    $pdf->SetXY(20, 65);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(15, 10, iconv('UTF-8', 'cp874', 'เรียน'));
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));

    $pdf->SetXY(80, 77.5); $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));

    $pdf->SetXY(159, 77.5); 
    $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(35, 73);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', "ข้าพเจ้า  (" . $document['prefix_student'] . ")............................................................................รหัสประจำตัว........................................."));

    $pdf->SetXY(20, 80);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท      แผน 1 แบบวิชาการ'));

    $pdf->SetXY(100, 83);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 1 แบบวิชาการ ภาคปกติ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

    $pdf->SetXY(125, 83);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 1 แบบวิชาการ ภาคสมทบ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(65, 88);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'แผน 2 แบบวิชาชีพ'));

    $pdf->SetXY(100, 91);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 2 แบบวิชาชีพ ภาคปกติ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

    $pdf->SetXY(125, 91);
    $pdf->checkboxMark($document['name_studyplan'] == 'แผนที่ 2 แบบวิชาชีพ ภาคสมทบ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

    $pdf->SetXY(36, 98.5); $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(92, 98.5); $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(174, 98.5); $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['abbreviate_student']));
    $pdf->SetXY(20, 94);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'สาขาวิชา................................................สาขา................................................................อักษรย่อสาขาวิชา...................'));

    $pdf->SetXY(60, 105.5); 
    $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20, 101);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก...............................................................................................................................................'));

    $pdf->SetXY(38, 112.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['email_student']));
    $pdf->SetXY(155, 112.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(20, 108);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'E-mail:..................................................................................หมายเลขโทรศัพท์ มือถือ..................................................'));

    $pdf->SetXY(20, 120);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'จัดทำโครงการ  '));

    $pdf->SetXY(50, 123);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(80, 123);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(125, 120);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'เรื่อง..............................................................'));

    $pdf->SetXY(30, 131.5); 
    $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', ' ' . $document['projectThai_gs12report']));
    $pdf->SetXY(20, 127);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(30, 137.5); 
    $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', ' ' . $document['projectEng_gs12report']));
    $pdf->SetXY(20, 133);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(20, 140);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'มีความประสงค์ขอสอบหัวข้อ'));

    $pdf->SetXY(68, 143);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(97, 143);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(x:138, y:144.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $date2_thai));
    $pdf->SetXY(140, 140);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '......................................................'));

    $pdf->SetXY(x:30, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['examRequestTime_gs12report']));
    $pdf->SetXY(x:65, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['examRequestRoom_gs12report']));
    $pdf->SetXY(x:95, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $document['examRequestFloor_gs12report']));
    $pdf->SetXY(x:118, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['examRequestBuilding_gs12report']));
    $pdf->SetXY(20, 147);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'เวลา.........................ณ ห้อง.........................ชั้น...................อาคาร.............................'));

    $pdf->SetXY(45, 160);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษา'));

    $pdf->SetXY(75, 163);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');

    $pdf->SetXY(103, 163);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(145, 160);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'ลงชื่อรับทราบ'));

    $pdf->SetXY(50, 171.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorMain_gs12report']));
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
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorMain_gs12report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 148, 166, 30, 0, 'PNG'); 
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }

        // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }
    $pdf->SetXY(30, 167);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '1.1..........................................................................อาจารย์ที่ปรึกษาหลัก ลงชื่อ...................................................'));

    $pdf->SetXY(x:50, y:178.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorSecond_gs12report']));
    //$pdf->SetXY(x:148, y:178.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'นิรัตน์ชา เนียมชาวนาม'));
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
    if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorSecond_gs12report']) {
        if (file_exists($teacherImage)) {
            $pdf->Image($teacherImage, 148, 175, 30, 0, 'PNG'); 
        } else {
            $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
        }
    }

    // ลบไฟล์ PNG ชั่วคราวหลังจากใช้งานเสร็จ
    if (file_exists($teacherImage)) {
        unlink($teacherImage);
    }
}
    $pdf->SetXY(30, 174);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '1.2..........................................................................อาจารย์ที่ปรึกษาร่วม  ลงชื่อ...................................................'));

    $pdf->SetXY(20, 186);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'พร้อมนี้ได้แนบโครงการย่อ (คคอ. บว.20) ฉบับจริง จำนวน 1 ชุด และฉบับสำเนา จำนวน 5 ชุด'));
    $pdf->SetXY(30, 196);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));

   if (file_exists($signatureImage)) {
    $pdf->Image($signatureImage, 130, 200, 30, 0, 'PNG'); 
} else {
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
}
    $pdf->SetXY(x:125, y:227.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(110, 217);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
    $pdf->SetXY(118, 223);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(.............................................................)'));

    $pdf->SetXY(20, 215);
    $pdf->SetFont('THSarabunNew', 'b', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', 'หมายเหตุ'));

    $pdf->SetXY(35, 215);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', 'การสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระต้องสอบภายใน 30 วัน นับจากวันที่ได้รับอนุมัติโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'));

    $pdf->SetXY(35, 220);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', 'มิฉะนั้นจะต้องเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระใหม่'));
    $pdf->SetXY(165, 231);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(-85, 45, iconv('UTF-8', 'cp874', '/...ความเห็นประธาน...'));

    ///////////---------  Page 2
    $pdf->AddPage();
    $pdf->SetXY(20, 30);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'));
    $pdf->SetXY(25, 38);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ได้พิจารณาคุณสมบัติแล้ว'));
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
    );    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติ'), 0, 1, 'L');
    // ตรวจสอบเงื่อนไขและแสดงข้อมูล
if (isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) && 
$chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว') {

// ทำเครื่องหมาย Checkbox
$pdf->SetXY(78, 44);
$pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
$pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ..........................................................................................................'), 0, 1, 'L');

// แสดงข้อความจาก $description
$description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';
$pdf->SetXY(100, 45.5);
$pdf->SetFont('THSarabunNew', '', 16);
$pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $description));
} else {
// กรณีไม่เข้าเงื่อนไข ใช้ Default
$pdf->SetXY(78, 44);
$pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
$pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ..........................................................................................................'), 0, 1, 'L');
}

    //$pdf->SetXY(x:127, y:58.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'นิรัตน์ชา เนียมชาวนาม'));
    foreach ($chairpersoncurriculumSignatures as $signature) {
        // แสดงรูปภาพลายเซ็น
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png'; // ไฟล์ PNG ที่แปลงจาก Base64
        $pdf->Image($signatureImagechairpersoncurriculum, 126, 46, 40);  // ตำแหน่งและขนาดของภาพ
    }
    $pdf->SetXY(110, 54);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(x:127, y:65.5); 
    $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $chairpersonName));
    $pdf->SetXY(118, 61);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(............................................................................)'));

    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->SetXY(123, 73);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }
    $pdf->SetXY(20, 80);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(20, 88);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เรียน คณบดีคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(25, 96);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'นักศึกษาได้รับอนุมัติโครงการ'));

    if (isset($graduateOfficerSignatures[0]['gradofficersignGs12_status']) && 
    $graduateOfficerSignatures[0]['gradofficersignGs12_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว') {

    $pdf->SetXY(78, 94);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    $pdf->SetXY(108, 94);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');

    $pdf->SetXY(x:170, y:95.5); 
    foreach ($graduateOfficerSignatures as $signature) {
        if (isset($signature['thai_day_approval'])) {
            $thai_day_approval = $signature['thai_day_approval'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_day_approval));  // Print day
        }
        
        if (isset($signature['thai_month_approval'])) {
            $thai_month_approval = $signature['thai_month_approval'];
            $pdf->SetXY(x:40, y:102.5); 
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_month_approval));  // Print month
        }
        if (isset($signature['thai_year_approval'])) {
            $thai_year_approval = $signature['thai_year_approval'];
            $pdf->SetXY(x:90, y:102.5); 
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_year_approval));  // Print month
        }
    }

    $pdf->SetXY(153, 96);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เมื่อวันที่..........................'));
    $pdf->SetXY(20, 103);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เดือน...................................................พ.ศ. ...................................'));
    $pdf->SetXY(25, 108);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติให้ดำเนินการสอบหัวข้อ'), 0, 1, 'L');
    $pdf->SetXY(97, 108);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    $pdf->SetXY(125, 108);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ    ตามเสนอ'), 0, 1, 'L');
    $pdf->SetXY(25, 115);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ..........................................................................................................'), 0, 1, 'L');
    
}else {
    $pdf->SetXY(78, 94);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    $pdf->SetXY(108, 94);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');
    $pdf->SetXY(153, 96);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เมื่อวันที่..........................'));
    $pdf->SetXY(20, 103);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เดือน...................................................พ.ศ. ...................................'));
    $pdf->SetXY(25, 108);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติให้ดำเนินการสอบหัวข้อ'), 0, 1, 'L');
    $pdf->SetXY(97, 108);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    $pdf->SetXY(125, 108);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ    ตามเสนอ'), 0, 1, 'L');
}
    if (isset($graduateOfficerSignatures[0]['gradofficersignGs12_status']) && 
    $graduateOfficerSignatures[0]['gradofficersignGs12_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว') {
    $pdf->SetXY(x:50, y:116.5); 
    $pdf->SetFont('THSarabunNew','',16);
    $gradofficersignGs12Description = isset($graduateOfficerSignatures[0]['gradofficersignGs12_description']) ? $graduateOfficerSignatures[0]['gradofficersignGs12_description'] : '';

    $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $gradofficersignGs12Description));
    $pdf->SetXY(25, 115);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ..........................................................................................................'), 0, 1, 'L');

    }
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 127, 115, 40);

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
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersignGs12_nameGradOffice']) 
    ? $graduateOfficerSignatures[0]['gradofficersignGs12_nameGradOffice'] : '';
    $pdf->SetXY(x:127, y:131.5); 
    $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $GradOfficeName ));
    $pdf->SetXY(118, 127);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(............................................................................)'));

    
    $pdf->SetXY(121, 139);
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
    
    $pdf->SetXY(25, 162);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    
    $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';
    $pdf->SetXY(50, 163.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription));

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
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'));
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

$IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ?
$IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';

$pdf->SetXY(x: 50, y: 218.5);
$pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription), 0, 1, 'L');
$pdf->SetXY(x: 29, y: 217);
$pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.....................................................................................................................................................................'), 0, 1, 'L');


}else{
$pdf->SetXY(25, 218.5);
$pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
$pdf->SetXY(x: 29, y: 217);
$pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.....................................................................................................................................................................'), 0, 1, 'L');

}
    //$pdf->SetXY(x:50, y:224.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '่ไม่ขอออกความเห็น'));
    $pdf->SetXY(20, 225);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'));

    //$pdf->SetXY(x:127, y:231.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'นิรัตน์ชา เนียมชาวนาม'));
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 128, 224, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is not an array or object.');
    }
    $pdf->SetXY(110, 227);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(121, 233);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์  ยาวระ)'));
    $pdf->SetXY(124, 240);
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
    $pdf->SetXY(150, 8);
    $pdf->SetFont('THSarabunNew', '', 12);
    $pdf->Cell(0, 37, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(85, 50);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'แบบฟอร์มเสนอโครงการ'));
    $pdf->SetXY(65, 57);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 18);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    $pdf->SetXY(100, 57);
    $pdf->checkboxMark($document['projectType_gs12report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 18);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'), 0, 1, 'L');
    $pdf->SetXY(30, 68);
    $pdf->SetFont('THSarabunNew', 'B', 18);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน วิทยาเขตขอนแก่น'));

    $pdf->SetXY(20, 80);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ชื่อเรื่อง (ภาษาไทย)'));
    $pdf->SetXY(60, 85); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectThai_gs12report']));
    $pdf->SetXY(50, 80.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.......................................................................................................................................................'));
    //$pdf->SetXY(x:30, y:83.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '่ไม่ขอออกความเห็น'));
    $pdf->SetXY(20, 89);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));
    //$pdf->SetXY(x:30, y:91.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '่ไม่ขอออกความเห็น'));
    $pdf->SetXY(20, 97);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(20, 112);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ชื่อเรื่อง (ภาษาอังกฤษ)'));

    $pdf->SetXY(x:60, y:117); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectEng_gs12report']));
    $pdf->SetXY(55, 112.5);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.................................................................................................................................................'));
    //$pdf->SetXY(x:30, y:115.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '่ไม่ขอออกความเห็น'));
    $pdf->SetXY(20, 121);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    //$pdf->SetXY(x:30, y:123.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '่ไม่ขอออกความเห็น'));
    $pdf->SetXY(20, 129);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(x:90, y:179.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(105, 167);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ผู้เสนอ'));
    $pdf->SetXY(80, 175);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '..................................................................................'));

    $pdf->SetXY(x:110, y:187.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(80, 183);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'รหัสประจำตัว'));
    $pdf->SetXY(101, 183);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '  ........................................................'));

    $pdf->SetXY(x:110, y:195.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(80, 191);
    $pdf->SetFont('THSarabunNew', 'B', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'สาขาวิชา'));
    $pdf->SetXY(103, 191);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', '.........................................................'));


    $pdf->SetXY(x:120, y:244.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorMain_gs12report']));
    $pdf->SetXY(x:120, y:252.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorSecond_gs12report']));
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