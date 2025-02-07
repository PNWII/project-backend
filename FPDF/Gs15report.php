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
        gs15.id_gs15report,
        gs15.name_gs15report,
        gs15.projectType_gs15report,
        gs15.projectThai_gs15report,
        gs15.projectEng_gs15report,
        gs15.advisorMain_gs15report,
        gs15.advisorSecond_gs15report,
        gs15.projectApprovalDate_gs15report,
        gs15.projectProgressDate_gs15report,
        gs15.defenseRequestDate_gs15report,
        gs15.defenseRequestTime_gs15report,
        gs15.defenseRequestRoom_gs15report,
        gs15.defenseRequestFloor_gs15report,
        gs15.defenseRequestBuilding_gs15report,
        gs15.courseCredits_gs15report,
        gs15.cumulativeGPA_gs15report,
        gs15.thesisCredits_gs15report,
        gs15.thesisDefenseDoc_gs15report,
        gs15.status_gs15report,
        gs15.at_gs15report,
        gs15.signature_gs15report,
        gs15.date_gs15report,
        gs15.signName_gs15report
    FROM 
        gs15report gs15
    LEFT JOIN 
        student s ON gs15.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs15.id_gs15report = ?
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
    ccurrsignags15_IdDocs,
    ccurrsignags15_nameDocs,
    ccurrsignags15_nameChairpersonCurriculum,
    ccurrsignags15_description,
    ccurrsignags15_sign,
    ccurrsigna15_status,
    ccurrsignags15_at,
    ccurrsignags15_examChair,
    ccurrsignags15_examChairPosition,
    ccurrsignags15_examChairWorkplace,
    ccurrsignags15_examChairTel,
    ccurrsignags15_examAdvisorMain,
    ccurrsignags15_examAdvisorMainPosition,
    ccurrsignags15_examAdvisorMainWorkplace,
    ccurrsignags15_examAdvisorMainTel,
    ccurrsignags15_examAdvisorSecond,
    ccurrsignags15_examAdvisorSecondPosition,
    ccurrsignags15_examAdvisorSecondWorkplace,
    ccurrsignags15_examAdvisorSecondTel,
    ccurrsignags15_examCurriculum,
    ccurrsignags15_examCurriculumPosition,
    ccurrsignags15_examCurriculumWorkplace,
    ccurrsignags15_examCurriculumTel,
    ccurrsigna_status
FROM 
    ccurrsignags15
LEFT JOIN 
    ccurrsigna cc 
ON 
    ccurrsignags15_IdDocs = cc.ccurrsigna_IdDocs
WHERE 
    ccurrsignags15_IdDocs = ?
    ");

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $chairpersoncurriculumSignatures = [];
    while ($row = $result->fetch_assoc()) {
        // แปลงวันที่ timestamp เป็นรูปแบบวันที่ไทย
        $timestamp = strtotime($row['ccurrsignags15_at']); // แปลง timestamp
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
        $signatureDatachairpersoncurriculum = $row['ccurrsignags15_sign'];  // ถ้าคุณใช้ชื่อ 'ccurrsigna_sign' สำหรับลายเซ็น
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
    $date1_thai = convertToThaiDate($document['date_gs15report'] ?? '');
    $date2_thaiprojectApprovalDate = convertToThaiDate($document['projectApprovalDate_gs15report'] ?? '');
    $date3_thaiprojectProgressDate = convertToThaiDate($document['projectProgressDate_gs15report'] ?? '');
    $date4_thaidefenseRequestDate = convertToThaiDate($document['defenseRequestDate_gs15report'] ?? '');



    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs15report'];
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
    $pdf->AddFont('THSarabunNew','','THSarabunNew.php');
    $pdf->AddFont('THSarabunNew','B','THSarabunNew_b.php');
    $pdf->AddPage('P','A4');
    // $pdf->SetFont('THSarabunNew','',14);
    
    $pdf->SetFillColor(192);
    
    $pdf->Image('img/logo.png', 15, 5, 15, 0);    
    
    $pdf->SetFont('THSarabunNew','B',14);
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คคอ. บว. 15'),0,1,'R');
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'),0,1,'R');
    
    $pdf->SetFont('THSarabunNew','B',18);
    $pdf->Cell( 0  , 20 , iconv( 'UTF-8','cp874' , 'คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ' ) , 0 , 1 , 'C' );

    
    $pdf->SetXY(190, 43);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date1_thai), 0, 1, 'R');

    $pdf->SetXY(56, 48);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

    $pdf->SetXY(86, 48);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    
    $pdf->SetXY(20, 45);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรื่อง'), 0 , 0 );
   
    $pdf->SetXY(30, 48);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ขอสอบป้องกัน'),0,1, 'L');
    $pdf->SetXY(20, 55.5);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'เรียน'),0,0,'' );
    $pdf->SetXY(30, 55.5);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'L');
    
    $pdf->SetXY(x:70, y:72); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(x:152, y:72); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $document['idstd_student']));
    $pdf->SetXY(33, 68);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', "ข้าพเจ้า (". $document['prefix_student'] .")............................................................................รหัสประจำตัว................................................"));
    
    $pdf->SetXY(60, 80);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_studyplan']));
    $pdf->SetXY(20, 76);
    $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท...........................................................................................'));
    $pdf->SetXY(145, 78);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคปกติ') !== false, 
        4, 
        'THSarabunNew', 
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');
    
    $pdf->SetXY(170, 78);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคสมทบ') !== false, 
        4, 
        'THSarabunNew', 
        16
    );
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');



    $pdf->SetXY(x:40, y:87.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $document['major_student']));
    $pdf->SetXY(20,78);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขาวิชา........................................................'),0,1,'L' );
    $pdf->SetXY(85,83);
    $pdf->SetXY(x:100, y:87.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(85,78);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขา.....................................................................'),0,1,'L'  );
    
    $pdf->SetXY(x:180, y:87.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['abbreviate_student']));
    $pdf->SetXY(156,78);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'อักษรย่อสาขา........................'),0,1,'L'  );
    $pdf->SetXY(55,90);
    
    $pdf->SetXY(x:62, y:94.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20,85);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก...........................................................................................................................................................'),0,1,'L'  );
    
    $pdf->SetXY(x:35, y:100.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['email_student']));
    $pdf->SetXY(20,91);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'E-mail:...................................................................................'),0,1,'L'  );
    $pdf->SetXY(140,96);
    $pdf->SetXY(x:145, y:100.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(107,91);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'หมายเลขโทรศัพท์ มือถือ............................................................'),0,1,'L'  );
    
    $pdf->SetXY(20,102);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'ได้รับอนุมัติหัวข้อ'),0,1,'L' );
    $pdf->SetXY(51, 106);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    $pdf->SetXY(80, 106);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    $pdf->SetXY(127.8, 102);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,12.5, iconv('UTF-8', 'cp874', $date2_thaiprojectApprovalDate));
    $pdf->SetXY(122,102);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'เมื่อ'),0,1,'L' );

    $pdf->SetXY(20,109);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'สอบความก้าวหน้า'),0,1,'L' );
    $pdf->SetXY(51, 113);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    $pdf->SetXY(80, 113);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    
    $pdf->SetXY(127.8, 109); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(0,12.5, iconv('UTF-8', 'cp874', $date3_thaiprojectProgressDate));
    $pdf->SetXY(122,109);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'เมื่อ'),0,1,'L' );
    $pdf->SetXY(20,115.5);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'มีความประสงค์'),0,1,'L' );
    $pdf->SetXY(25,122);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'ขอสอบป้องกัน'),0,1,'L' );
    $pdf->SetXY(52, 126);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    $pdf->SetXY(81, 126);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    
    $pdf->SetXY(127.8, 122); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(0,12.5, iconv('UTF-8', 'cp874', $date4_thaidefenseRequestDate));
    $pdf->SetXY(122,122);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'เมื่อ'),0,1,'L' );

    $pdf->SetXY(x:30, y:128); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(0,12.5, iconv('UTF-8', 'cp874', $document['defenseRequestTime_gs15report']));
    $pdf->SetXY(x:63, y:128); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(0,12.5, iconv('UTF-8', 'cp874', $document['defenseRequestRoom_gs15report']));
    $pdf->SetXY(x:93, y:128); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(0,12.5, iconv('UTF-8', 'cp874', $document['defenseRequestFloor_gs15report']));
    $pdf->SetXY(x:121, y:128); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(0,12.5, iconv('UTF-8', 'cp874', $document['defenseRequestBuilding_gs15report']));
    $pdf->SetXY(20,129);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'เวลา.........................ณ ห้อง............................ชั้น....................อาคาร........................'),0,1,'L' );

    $pdf->SetXY(57,136);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษา'),0,1,'L' );
    $pdf->SetXY(87, 140.5);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    $pdf->SetXY(115, 140.5);
    $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    $pdf->SetXY(157,136);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'ลงชื่อรับทราบ'),0,1,'L' );
    
    $pdf->SetXY(x:42, y:148.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorMain_gs15report']));
    // $pdf->SetXY(x:150, y:148.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '23'));
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
    
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorMain_gs15report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 145, 145, 30, 0, 'PNG'); 
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }
    
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }    
    $pdf->SetXY(33,143);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '1............................................................................................อาจารย์ที่ปรึกษาหลัก ลงชื่อ.....................................................................'),0,1,'L' );
    $pdf->SetXY(x:42, y:154.8); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorSecond_gs15report']));
    // $pdf->SetXY(x:150, y:154.8); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', '23'));
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
    
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorSecond_gs15report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 145, 150, 30, 0, 'PNG'); 
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }
    
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }    
    $pdf->SetXY(33,149.3);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '2............................................................................................อาจารย์ที่ปรึกษาร่วม  ลงชื่อ......................................................................'),0,1,'L' );

    $pdf->SetXY(20,156);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'ข้าพเจ้าได้ดำเนินการตามข้อบังคับ ฯ มหาวิทยาลัย ว่าด้วยการศึกษาระดับบัณฑิตศึกษา หมวดที่ 9 การสำเร็จการศึกษาฯ แล้วคือ'),0,1,'L' );

    $pdf->SetXY(x:107, y:166.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['courseCredits_gs15report']));
    $pdf->SetXY(x:160, y:166.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['cumulativeGPA_gs15report']));
    $pdf->SetXY(28,161);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '1.  ศึกษารายวิชาครบตามที่กำหนดในหลักสูตรแล้ว จำนวน..............หน่วยกิต ได้คะแนนเฉลี่ยสะสม....................และได้รับการประเมิน'),0,1,'L' );
    $pdf->SetXY(x:96, y:172.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $document['thesisCredits_gs15report']));
    $pdf->SetXY(33,167);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'วิทยานิพนธ์/การศึกษาค้นคว้าอิสระแล้ว จำนวน.............หน่วยกิต'),0,1,'L' );
    $pdf->SetXY(28,173);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '2.  เรียบเรียงวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ตามข้อกำหนดในคู่มือจัดทำวิทยานิพนธ์/การศึกษาค้นคว้าอิสระของมหาวิทยาลัย/'),0,1,'L' );
    $pdf->SetXY(33,179);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม เสร็จเรียบร้อยแล้ว'),0,1,'L' );
    $pdf->SetXY(28,184);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '3.  แผนการเรียน (คคอ. บว. 40/41) จำนวน 1 ชุด แบบขอส่งผลงานที่ได้นำเสนอ/ตีพิมพ์ (คคอ. บว. 50) ฉบับจริง จำนวน 1 ชุด'),0,1,'L' );
    $pdf->SetXY(x:176, y:194.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['thesisDefenseDoc_gs15report']));
    $pdf->SetXY(33,189);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'ฉบับสำเนา จำนวน 3 ชุด และวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับสอบสำหรับคณะกรรมการสอบ จำนวน.............ชุด'),0,1,'L' );


    $pdf->SetXY(30,209);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));
    
    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 130, 220, 30, 0, 'PNG'); 
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    }    
    $pdf->SetXY(110, 233);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ลงชื่อ .................................................................'));
    
    $pdf->SetXY(x:122, y:241); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(117, 240);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '(.................................................................)'));

    $pdf->SetXY(20, 254);
    $pdf->SetFont('THSarabunNew','b',12);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'หมายเหตุ'));

    $pdf->SetXY(37, 254);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'การสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ต้องห่างจากวันสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระไม่น้อยกว่า 30 วัน'));

    $pdf->SetXY(37, 260);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ได้รับอนุมัติหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ไม่น้อยกว่า 120 วัน'));
    $pdf->SetXY(165, 272);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '/...ความเห็นของประธาน...'));


    //------------------------------- หน้า 2 ------------------------------------
    $pdf->SetMargins( 50,30,10 );
    $pdf->AddPage();
    
    $pdf->SetFont('THSarabunNew','B',14);
    $pdf->SetXY(20, 21.5);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'),0,1, 'L');
    
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->SetXY(20, 26.5);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ได้พิจารณาคุณสมบัติแล้ว'),0,1, 'L');
    if (isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) && 
    $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว') {
        $pdf->SetXY(30, 32);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติให้สอบป้องกัน'),0,1, 'L');
        $pdf->SetXY(85, 33);
        $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

        $pdf->SetXY(114, 33);
        $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
        $pdf->SetXY(30, 39);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรพิจารณา เนื่องจาก............................................................................................................................................................'),0,1, 'L');
    
        $pdf->SetFont('THSarabunNew','',14);
        $pdf->SetXY(20, 45);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'พร้อมนี้ขอเสนอชื่อคณะกรรมการสอบป้องกัน'),0,1, 'L');
        $pdf->SetXY(85, 45);
        $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

        $pdf->SetXY(114, 45);
        $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
//------------------- 1
    $examChairName = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examChair']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examChair'] : '';
    $pdf->SetXY(x:53, y:51.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examChairName));
    $examChairPosition = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examChairPosition']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examChairPosition'] : '';    
    $pdf->SetXY(x:143, y:51.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examChairPosition));    
    $pdf->SetXY(20, 50);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '1. ประธานกรรมการสอบ.........................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 55);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ต้องไม่ใช่อาจารย์ที่ปรึกษาหลักหรืออาจารย์ที่ปรึกษาร่วม)'),0,1, 'L');
    $examChairWorkplace = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examChairWorkplace']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examChairWorkplace'] : '';  
    $pdf->SetXY(x:58, y:61); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examChairWorkplace));
    $pdf->SetXY(20, 59.6);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
    $examChairTel = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examChairTel']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examChairTel'] : '';   
    $pdf->SetXY(x:146, y:65); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $examChairTel));    
    $pdf->SetXY(20, 64);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
//-------------- 2
    $examAdvisorMain = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMain']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMain'] : ''; 
    $pdf->SetXY(x:42, y:69.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examAdvisorMain));
    $examAdvisorMainPosition = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMainPosition']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMainPosition'] : '';  
    $pdf->SetXY(x:143, y:69.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examAdvisorMainPosition));    
    $pdf->SetXY(20, 68.2);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '2. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 73);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์ที่ปรึกษาหลัก)'),0,1, 'L');
    $examAdvisorMainWorkplace = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMainWorkplace']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMainWorkplace'] : '';
    $pdf->SetXY(x:58, y:79); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examAdvisorMainWorkplace));
    $pdf->SetXY(20, 77.5);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
  
    $examAdvisorMainTel = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMainTel']) ? 
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorMainTel'] : ''; 
    $pdf->SetXY(x:146, y:83); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $examAdvisorMainTel));    
    $pdf->SetXY(20, 82);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');

// 3 -------------------- 3
    $examAdvisorSecond = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecond']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecond'] : '';
    $pdf->SetXY(x:42, y:87); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $examAdvisorSecond));
    $examAdvisorSecondPosition = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecondPosition']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecondPosition'] : '';    
    $pdf->SetXY(x:143, y:87); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $examAdvisorSecondPosition));    
    $pdf->SetXY(20,86);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '3. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 91);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์ที่ปรึกษาร่วม)'),0,1, 'L');
    $examAdvisorSecondWorkplace = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecondWorkplace']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecondWorkplace'] : '';
    $pdf->SetXY(x:58.5, y:97.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examAdvisorSecondWorkplace));    
    $pdf->SetXY(20, 96);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
    $examAdvisorSecondTel = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecondTel']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examAdvisorSecondTel'] : '';
    $pdf->SetXY(x:146, y:102); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examAdvisorSecondTel));
    $pdf->SetXY(20, 101);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
 // 4 ------------------------------- 4    
    $examCurriculum = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculum']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculum'] : '';
    $pdf->SetXY(x:41, y:107); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $examCurriculum));
    $examCurriculumPosition = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculumPosition']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculumPosition'] : '';
    $pdf->SetXY(x:143, y:107); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examCurriculumPosition));    
    $pdf->SetXY(20,106);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '4. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 111);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์กรรมการประจำหลักสูตร)'),0,1, 'L');
    $examCurriculumWorkplace = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculumWorkplace']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculumWorkplace'] : '';
    $pdf->SetXY(x:58.5, y:117); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $examCurriculumWorkplace));    
    $pdf->SetXY(20, 116);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
    $examCurriculumTel = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculumTel']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_examCurriculumTel'] : '';
    $pdf->SetXY(x:146, y:122); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $examCurriculumTel));
    $pdf->SetXY(20, 121);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');

    } else  if (isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) && 
    $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว') {
        $pdf->SetXY(30, 32);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติให้สอบป้องกัน'),0,1, 'L');
        $pdf->SetXY(85, 33);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

        $pdf->SetXY(114, 33);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
        $description = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_description']) ?
        $chairpersoncurriculumSignatures[0]['ccurrsignags15_description'] : '';
        $pdf->SetXY(x:80, y:40.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $description)); 
        
        $pdf->SetXY(20, 45);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'พร้อมนี้ขอเสนอชื่อคณะกรรมการสอบป้องกัน'),0,1, 'L');
        $pdf->SetXY(85, 45);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    
        $pdf->SetXY(114, 45);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    //------------------- 1
        $pdf->SetXY(20, 50);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '1. ประธานกรรมการสอบ.........................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 55);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ต้องไม่ใช่อาจารย์ที่ปรึกษาหลักหรืออาจารย์ที่ปรึกษาร่วม)'),0,1, 'L');
    
        $pdf->SetXY(20, 59.6);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 64);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
    //-------------- 2
        $pdf->SetXY(20, 68.2);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '2. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 73);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์ที่ปรึกษาหลัก)'),0,1, 'L');
        $pdf->SetXY(20, 77.5);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 82);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
    
    // 3 -------------------- 3
        $pdf->SetXY(20,86);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '3. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 91);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์ที่ปรึกษาร่วม)'),0,1, 'L');
        $pdf->SetXY(20, 96);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 101);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
     // 4 ------------------------------- 4    
        $pdf->SetXY(20,106);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '4. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 111);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์กรรมการประจำหลักสูตร)'),0,1, 'L');
        $pdf->SetXY(20, 116);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
        $pdf->SetXY(20, 121);
        $pdf->SetFont('THSarabunNew','',12);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
   
    $pdf->SetXY(30, 39);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรพิจารณา เนื่องจาก............................................................................................................................................................'),0,1, 'L');
    } else{
        $pdf->SetXY(30, 32);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติให้สอบป้องกัน'),0,1, 'L');
        $pdf->SetXY(85, 33);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

        $pdf->SetXY(114, 33);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
           
    $pdf->SetXY(30, 39);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรพิจารณา เนื่องจาก............................................................................................................................................................'),0,1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->SetXY(20, 45);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'พร้อมนี้ขอเสนอชื่อคณะกรรมการสอบป้องกัน'),0,1, 'L');
    $pdf->SetXY(85, 45);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

    $pdf->SetXY(114, 45);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
//------------------- 1
    $pdf->SetXY(20, 50);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '1. ประธานกรรมการสอบ.........................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 55);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ต้องไม่ใช่อาจารย์ที่ปรึกษาหลักหรืออาจารย์ที่ปรึกษาร่วม)'),0,1, 'L');

    $pdf->SetXY(20, 59.6);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 64);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
//-------------- 2
    $pdf->SetXY(20, 68.2);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '2. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 73);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์ที่ปรึกษาหลัก)'),0,1, 'L');
    $pdf->SetXY(20, 77.5);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 82);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');

// 3 -------------------- 3
    $pdf->SetXY(20,86);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '3. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 91);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์ที่ปรึกษาร่วม)'),0,1, 'L');
    $pdf->SetXY(20, 96);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 101);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
 // 4 ------------------------------- 4    
    $pdf->SetXY(20,106);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '4. กรรมการสอบ......................................................................................................ตำแหน่ง (บริหาร/วิชาการ)..........................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 111);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(อาจารย์กรรมการประจำหลักสูตร)'),0,1, 'L');
    $pdf->SetXY(20, 116);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'สถานที่ทำงาน(ระบุที่อยู่ให้ครบ)....................................................................................................................................................................................................................'),0,1, 'L');
    $pdf->SetXY(20, 121);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '......................................................................................................................................................................โทรศัพท์..................................................................................'),0,1, 'L');
    }
   
foreach ($chairpersoncurriculumSignatures as $signature) {
    $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png'; 
    $pdf->Image($signatureImagechairpersoncurriculum, 126, 123, 40);  
}    
    
    $pdf->SetXY(85, 131);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ.....................................................................'),0,1, 'C');
    $nameChairpersonCurriculum = isset($chairpersoncurriculumSignatures[0]['ccurrsignags15_nameChairpersonCurriculum']) ?
    $chairpersoncurriculumSignatures[0]['ccurrsignags15_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(x:129, y:136); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $nameChairpersonCurriculum));
    $pdf->SetXY(91, 135);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(......................................................................)'),0,1, 'C');
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->SetXY(123, 142);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }
 
    $pdf->SetFont('THSarabunNew','B',14);
    $pdf->SetXY(20, 143);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะฯ'),0,1, 'L');
    
    $pdf->SetXY(20, 148);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'เรียน   คณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'L');

    if (
        isset($graduateOfficerSignatures[0]['gradofficersign_status']) && 
        $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ) {
        $pdf->SetXY(30, 155);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  นักศึกษามีคุณสมบัติครบที่จะสอบป้องกัน'),0,1, 'L');
        
        $pdf->SetXY(95, 155);
        $pdf->checkboxMark($document['projectType_gs15report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    
        $pdf->SetXY(120, 155);
        $pdf->checkboxMark($document['projectType_gs15report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระได้ เห็นสมควรอนุมัติ'),0,1, 'L');
    
        $pdf->SetXY(30, 161);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  โปรดลงนามในคำสั่ง หนังสือเชิญคณะกรรมการสอบและหนังสือแจ้งต้นสังกัด'),0,1, 'L');
        $pdf->SetXY(30, 167);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่นๆ .......................................................................................................................................................................................'),0,1, 'L');

    } else  if (
        isset($graduateOfficerSignatures[0]['gradofficersign_status']) && 
        $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ) {
        $pdf->SetXY(30, 155);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  นักศึกษามีคุณสมบัติครบที่จะสอบป้องกัน'),0,1, 'L');
        
        $pdf->SetXY(95, 155);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    
        $pdf->SetXY(120, 155);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระได้ เห็นสมควรอนุมัติ'),0,1, 'L');
    
        $pdf->SetXY(30, 161);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  โปรดลงนามในคำสั่ง หนังสือเชิญคณะกรรมการสอบและหนังสือแจ้งต้นสังกัด'),0,1, 'L');
        $gradofficersignDescription = isset($graduateOfficerSignatures[0]['gradofficersign_description']) ? $graduateOfficerSignatures[0]['gradofficersign_description'] : '';
        $pdf->SetXY(x:50, y:168.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $gradofficersignDescription));
        $pdf->SetXY(30, 167);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่นๆ .......................................................................................................................................................................................'),0,1, 'L');

    } else{
    $pdf->SetXY(30, 155);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  นักศึกษามีคุณสมบัติครบที่จะสอบป้องกัน'),0,1, 'L');
    
    $pdf->SetXY(95, 155);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

    $pdf->SetXY(120, 155);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระได้ เห็นสมควรอนุมัติ'),0,1, 'L');

    $pdf->SetXY(30, 161);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  โปรดลงนามในคำสั่ง หนังสือเชิญคณะกรรมการสอบและหนังสือแจ้งต้นสังกัด'),0,1, 'L');
    $pdf->SetXY(30, 167);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่นๆ .......................................................................................................................................................................................'),0,1, 'L');

    }
    
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 120, 170, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }        
    $pdf->SetXY(85, 177);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ.....................................................................'),0,1, 'C');
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersign_nameGradofficer']) 
    ? $graduateOfficerSignatures[0]['gradofficersign_nameGradofficer'] : '';
    $pdf->SetXY(x:129, y:183.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $GradOfficeName));
    $pdf->SetXY(91, 182);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(......................................................................)'),0,1, 'C');
    $pdf->SetXY(123, 187);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];


            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer));
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
    }   
    $pdf->SetXY(20, 192);
    $pdf->SetFont('THSarabunNew','B',14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'),0,1, 'L');
    $pdf->SetXY(26, 199);
    $pdf->checkboxMark(
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
        $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว',
        4,
        'THSarabunNew',
        14
    );        
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นควรอนุมัติ'),0,1, 'L');

    if (isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) && 
    $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว') {
        $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';
        $pdf->SetXY(x:74, y:200.5); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription));
        $pdf->SetXY(55, 199);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ......................................................................................................................................................'),0,1, 'L');
       
    }else{
        $pdf->SetXY(55, 199);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ......................................................................................................................................................'),0,1, 'L');
       

    }
   if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
    foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
        if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
            $pdf->Image($signature['signature_file_path'], 115, 205, 40);
            unlink($signature['signature_file_path']);
        } else {
            error_log('Signature file does not exist: ' . $signature['signature_file_path']);
        }
    }
} else {
    error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
}
    $pdf->SetXY(85, 210);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'),0,1, 'C');
    $pdf->SetXY(92, 216);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'),0,1, 'C');
    $pdf->SetXY(90, 222);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'),0,1, 'C');
    $pdf->SetXY(123, 228);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
    }
    $pdf->SetXY(20, 234);
    $pdf->SetFont('THSarabunNew','B',14);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'L');

    $pdf->SetXY(26, 241);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        14
    );               
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติ'),0,1, 'L');
    if(isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
    $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'){

        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ?
        $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';
        $pdf->SetXY(x:74, y:242); $pdf->SetFont('THSarabunNew','',14); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $IndustrialEducationDeanDescription));
        $pdf->SetXY(55, 241);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ......................................................................................................................................................'),0,1, 'L');
       


    }else{
        $pdf->SetXY(55, 241);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 14);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ......................................................................................................................................................'),0,1, 'L');
       
    }
//    $pdf->SetXY(x:120, y:255); 
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 123, 246, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is not an array or object.');
    }      $pdf->SetXY(85, 254);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'),0,1, 'C');
    $pdf->SetXY(90, 260);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์ ยาวระ)'),0,1, 'C');
    $pdf->SetXY(90, 266);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'C');
    $pdf->SetXY(123, 274);
    $pdf->SetFont('THSarabunNew','',14);
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
    ?>