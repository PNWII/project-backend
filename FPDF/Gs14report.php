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
        gs14.id_gs14report,
        gs14.name_gs14report,
        gs14.projectType_gs14report,
        gs14.projectThai_gs14report,
        gs14.projectEng_gs14report,
        gs14.advisorMain_gs14report,
        gs14.advisorSecond_gs14report,
        gs14.projectApprovalDate_gs14report,
        gs14.progressExamRequestDate_gs14report,
        gs14.progressExamRequestTime_gs14report,
        gs14.progressExamRequestRoom_gs14report,
        gs14.progressExamRequestFloor_gs14report,
        gs14.progressExamRequestBuilding_gs14report,
        gs14.status_gs14report,
        gs14.at_gs14report,
        gs14.signature_gs14report,
        gs14.date_gs14report,
        gs14.signName_gs14report
    FROM 
        gs14report gs14
    LEFT JOIN 
        student s ON gs14.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs14.id_gs14report = ?
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
    $date1_thai = convertToThaiDate($document['date_gs14report'] ?? '');
    $date2_thai = convertToThaiDate($document['projectApprovalDate_gs14report'] ?? '');
    $date3_thai = convertToThaiDate($document['progressExamRequestDate_gs14report'] ?? '');


    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs14report'];
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
    $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คคอ. บว. 14'),0,1,'R');
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'),0,1,'R');
    
    $pdf->SetFont('THSarabunNew','B',18);
    $pdf->Cell( 0  , 20 , iconv( 'UTF-8','cp874' , 'แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ' ) , 0 , 1 , 'C' );
    
    $pdf->SetXY(190, 43);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date1_thai), 0, 1, 'R');
   
    $pdf->SetXY(68, 48);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

    $pdf->SetXY(98, 48);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    
    $pdf->SetXY(20, 45);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรื่อง'), 0 , 0 );
   
    $pdf->SetXY(33, 48);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ขอสอบความก้าวหน้า'),0,1, 'L');
    $pdf->SetXY(20, 55.5);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,5, iconv('UTF-8', 'cp874', 'เรียน'),0,0,'' );
    $pdf->SetXY(33, 55.5);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'L');
    $pdf->Ln(10);
    $pdf->SetXY(x:70, y:72); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(x:152, y:72); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $document['idstd_student']));
    $pdf->SetXY(33, 68);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', "ข้าพเจ้า (". $document['prefix_student'] .")............................................................................รหัสประจำตัว................................................"));
    
    $pdf->SetFont('THSarabunNew', '', 16);

// แสดงชื่อแผนการศึกษา
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


    $pdf->SetXY(x:40, y:89); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(20,80);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขาวิชา..............................................'),0,1,'L' );

    $pdf->SetXY(x:90, y:89); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(76,80);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขา.....................................................................'),0,1,'L'  );
    $pdf->SetXY(168, 80);
    $pdf->SetXY(x:170, y:89); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['abbreviate_student']));
    $pdf->SetXY(146,80);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'อักษรย่อสาขา...................................'),0,1,'L'  );
    $pdf->SetXY(55,90);
    $pdf->SetXY(x:62, y:97); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20,88);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก..........................................................................................................................................................'),0,1,'L'  );
    $pdf->SetXY(30,96);
    $pdf->SetXY(x:35, y:105.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['email_student']));
    $pdf->SetXY(20,96);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'E-mail:.........................................................................'),0,1,'L'  );
    $pdf->SetXY(140,96);
    $pdf->SetXY(x:140, y:105.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(98,96);
    $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'หมายเลขโทรศัพท์ มือถือ.......................................................................'),0,1,'L'  );
    
    $pdf->SetXY(20,112);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'จัดทำโครงงาน'),0,1,'L' );
    
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(45, 116);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(75, 116);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    
    $pdf->SetXY(120,113);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', 'เรื่อง..............................................................................'),0,1,'L' );

    $pdf->SetFont('THSarabunNew','',14);
    $pdf->SetXY(30,112);
    $pdf->SetXY(x:25, y:124.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectThai_gs14report']));
    $pdf->SetXY(20,119);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '...................................................................................................................................................................................................'),0,1,'L' );
    
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->SetXY(35,124);
    $pdf->SetXY(x:25, y:131); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectEng_gs14report']));
    $pdf->SetXY(20,125.5);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0, 12.5, iconv('UTF-8', 'cp874', '...............................................................................................................................................................................................................................'),0,1,'L' );

    
    $pdf->SetXY(x:109.5, y:136.4); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(0,5, iconv('UTF-8', 'cp874', $date2_thai));

    $pdf->SetXY(20, 136.4);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ได้รับอนุมัติหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ     เมื่อ'),0,1, 'L');
    
    $pdf->SetXY(20, 140);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'มีความประสงค์ขอสอบความก้าวหน้า'));

    $pdf->SetXY(78, 143);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

    $pdf->SetXY(105, 143);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');

  
    $pdf->SetXY(145.5, 140);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', $date3_thai));
    
    $pdf->SetXY(x:30, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['progressExamRequestTime_gs14report']));
    $pdf->SetXY(x:65, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['progressExamRequestRoom_gs14report']));
    $pdf->SetXY(x:95, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['progressExamRequestFloor_gs14report']));
    $pdf->SetXY(x:118, y:151.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['progressExamRequestBuilding_gs14report']));
    $pdf->SetXY(20, 147);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'เวลา.........................ณ ห้อง.........................ชั้น...................อาคาร.............................'));


    $pdf->SetXY(45, 160);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษา'));

    $pdf->SetXY(75, 163);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');

    $pdf->SetXY(103, 163);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');

    $pdf->SetXY(145, 160);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'ลงชื่อรับทราบ'));
    
    $pdf->SetXY(x:35, y:171.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorMain_gs14report']));
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
    
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorMain_gs14report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 145, 165, 30, 0, 'PNG'); 
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }
    
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }    
    $pdf->SetXY(30, 167);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '1............................................................................อาจารย์ที่ปรึกษาหลัก ลงชื่อ...........................................................'));
    $pdf->SetXY(x:35, y:178.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorSecond_gs14report']));
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
    
        if ($teacherSignature['teachersign_nameTeacher'] === $document['advisorSecond_gs14report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 145, 173, 30, 0, 'PNG'); 
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }
    
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }    
    $pdf->SetXY(30, 174);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '2............................................................................อาจารย์ที่ปรึกษาร่วม  ลงชื่อ...........................................................'));

    $pdf->SetXY(20, 186);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'พร้อมนี้ได้แนบโครงการย่อ (คคอ. บว.22) ฉบับจริง จำนวน 1 ชุด และฉบับสำเนา จำนวน 5 ชุด'));
    $pdf->SetXY(30, 196);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));
    
    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 130, 200, 30, 0, 'PNG'); 
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    }
       $pdf->SetXY(110, 225);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
    
    $pdf->SetXY(x:122, y:239.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(117, 235);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '(.................................................................)'));

    $pdf->SetXY(20, 253);
    $pdf->SetFont('THSarabunNew','b',14);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'หมายเหตุ'));

    $pdf->SetXY(38, 253);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'การสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ต้องห่างจากวันที่ได้รับอนุมัติหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้า'));

    $pdf->SetXY(38, 260);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'อิสระ ไม่น้อยกว่า 60 วัน'));
    $pdf->SetXY(165, 270);
    $pdf->SetFont('THSarabunNew','',14);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '/...ความเห็นของประธาน...'));


    //------------------------------- หน้า 2 ------------------------------------
    $pdf->SetMargins( 50,30,10 );
    $pdf->AddPage();
    
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->SetXY(20, 20);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'),0,1, 'L');
    
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->SetXY(26, 27);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ได้พิจารณาคุณสมบัติแล้ว'),0,1, 'L');
    
    $pdf->SetXY(26, 34);
    $pdf->SetFont('THSarabunNew','', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'เห็นสมควร'),0,1, 'L');
    // เพิ่ม checkbox พร้อมเครื่องหมายถูก ในตำแหน่งที่ต้องการ
    $pdf->SetXY(53, 34);
    $pdf->checkboxMark(
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
        $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว',
        4,
        'THSarabunNew',
        16
    );    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติ'),0,1, 'L');

    if (isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) && 
$chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว') {
    $pdf->SetXY(81, 34);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................'),0,1, 'L');

    $description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';
    $pdf->SetXY(x:110, y:35); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $description));

}else{
    $pdf->SetXY(81, 34);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................'),0,1, 'L');
}
    $pdf->SetXY(26, 40);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '...................................................................................................................................................................................'),0,1,'L' );
    
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png'; 
        $pdf->Image($signatureImagechairpersoncurriculum, 126, 52, 40);  
    }    
    $pdf->SetXY(95, 57);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ลงนาม....................................................................................'),0,1, 'C');
    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(x:128, y:67); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $chairpersonName));    

    $pdf->SetXY(105, 65);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '(..................................................................................)'),0,1, 'C');
    
    $pdf->SetXY(123, 73);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }

    
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->SetXY(20, 78);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะฯ'),0,1, 'L');
    
    $pdf->SetXY(20, 85);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'เรียน   คณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'L');
    
    $pdf->SetXY(26, 93);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'นักศึกษามีคุณสมบัติครบที่จะสอบความก้าวหน้า'),0,1, 'L');
    
    
    if (
        isset($graduateOfficerSignatures[0]['gradofficersign_status']) && 
        $graduateOfficerSignatures[0]['gradofficersign_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
    ) {
        // กรณีสถานะได้รับการอนุมัติ
        $pdf->SetXY(102, 93);
        $pdf->checkboxMark($document['projectType_gs14report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    
        $pdf->SetXY(132, 93);
        $pdf->checkboxMark($document['projectType_gs14report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ  ได้'), 0, 1, 'L');
    
        $pdf->SetXY(30, 100);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติ'), 0, 1, 'L');
    } else {
        // กรณีสถานะไม่ได้รับการอนุมัติ
        $pdf->SetXY(102, 93);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'), 0, 1, 'L');
    
        $pdf->SetXY(132, 93);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ  ได้'), 0, 1, 'L');
    
        $pdf->SetXY(30, 100);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติ'), 0, 1, 'L');
    }
    
    
    if (isset($graduateOfficerSignatures[0]['gradofficersign_status']) && 
$graduateOfficerSignatures[0]['gradofficersign_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว') {
    $pdf->SetXY(30, 107);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..............................................................................................................................................................'),0,1, 'L');

    $gradofficersignDescription = isset($graduateOfficerSignatures[0]['gradofficersign_description']) ? $graduateOfficerSignatures[0]['gradofficersign_description'] : '';
    $pdf->SetXY(x:48, y:109); 
    $pdf->SetFont('THSarabunNew','',16); 
    $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $gradofficersignDescription));

}else{
    $pdf->SetXY(30, 107);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..............................................................................................................................................................'), 0, 1, 'L');
}
    $pdf->SetXY(20, 113);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'),0,1,'L' );
    
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 127, 120, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }    
    $pdf->SetXY(95, 131);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ลงนาม....................................................................................'),0,1, 'C');
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersign_nameGradofficer']) 
    ? $graduateOfficerSignatures[0]['gradofficersign_nameGradofficer'] : '';
    $pdf->SetXY(127, 139); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $GradOfficeName));  
    $pdf->SetXY(105, 138);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '(..................................................................................)'),0,1, 'C');
    $pdf->SetXY(123, 146);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];


            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer));
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
    }
    
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->SetXY(20, 151);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'),0,1, 'L');
    $pdf->SetXY(26, 159);
    $pdf->checkboxMark(
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
        $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว',
        4,
        'THSarabunNew',
        16
    );    
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เห็นควรอนุมัติ'),0,1, 'L');
    if (isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) && 
    $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว') {

        $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';
        $pdf->SetXY(x:45, y:168); 
        $pdf->SetFont('THSarabunNew','',16); 
        $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription));
        $pdf->SetXY(26, 166);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ .................................................................................................................................................................'),0,1, 'L');

    }else{
        $pdf->SetXY(26, 166);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ .................................................................................................................................................................'),0,1, 'L');
    }
    
    $pdf->SetXY(20, 172);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '........................................................................................................................................................................................'),0,1,'L' );
    
if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
    foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
        if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
            $pdf->Image($signature['signature_file_path'], 115, 176, 40);
            unlink($signature['signature_file_path']);
        } else {
            error_log('Signature file does not exist: ' . $signature['signature_file_path']);
        }
    }
} else {
    error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
}    
    $pdf->SetXY(80, 189);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'),0,1, 'C');
    $pdf->SetXY(75, 196);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'),0,1, 'C');
    $pdf->SetXY(75, 203);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'),0,1, 'C');
    $pdf->SetXY(113, 211);
 if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
    foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
        $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
    }
} else {
    error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
}
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->SetXY(20, 215);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'L');
    
    $pdf->SetXY(26, 222);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );            
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อนุมัติ'),0,1, 'L');
    if(isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
    $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'){

        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ?
        $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';
        $pdf->SetXY(x:45, y:230); 
        $pdf->SetFont('THSarabunNew','',16); 
        $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription));
        $pdf->SetXY(26, 228);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................................................'),0,1, 'L');
    }else{
        $pdf->SetXY(26, 228);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................................................'),0,1, 'L');
    }
    $pdf->SetXY(20, 234);
    $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '..........................................................................................................................................................................................'),0,1,'L' );
    
    // $pdf->SetXY(x:115, y:252); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'dfdgdg'));
    if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
        foreach ($IndustrialEducationDeanSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                // แสดงรูปภาพลายเซ็นใน PDF
                $pdf->Image($signature['signature_file_path'], 115, 245, 40);

                // ลบไฟล์ทันทีหลังจากแสดงผล
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('IndustrialEducationDeanSignatures is not an array or object.');
    }  
    $pdf->SetXY(80, 251);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ลงชื่อ...................................................................................'),0,1, 'C');
    $pdf->SetXY(75, 258);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์ ยาวระ)'),0,1, 'C');
    $pdf->SetXY(75, 265);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'),0,1, 'C');
    $pdf->SetXY(112, 273);
    $pdf->SetFont('THSarabunNew','',16);
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
    $pdf->SetFont('THSarabunNew','B',14);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'คคอ. บว. 22'));
    $pdf->SetXY(145, 8);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0,40, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(67, 50);
    $pdf->SetFont('THSarabunNew','B',18);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'แบบฟอร์มเสนอความก้าวหน้าโครงการ'));
    $pdf->SetXY(60, 57);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'วิทยานิพนธ์', 4, 'THSarabunNew', 18);
    $pdf->SetFont('THSarabunNew','B',18);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  วิทยานิพนธ์'),0,1, 'L');
    $pdf->SetXY(95, 57);
    $pdf->checkboxMark($document['projectType_gs14report'] == 'การศึกษาค้นคว้าอิสระ', 4, 'THSarabunNew', 18);
    $pdf->SetFont('THSarabunNew','B',18);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  การศึกษาค้นคว้าอิสระ'),0,1, 'L');
    $pdf->SetXY(40, 68);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน วิทยาเขตขอนแก่น'));

    $pdf->SetXY(20, 80);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'ชื่อเรื่อง (ภาษาไทย)'));
    $pdf->SetXY(60, 85); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectThai_gs14report']));
    $pdf->SetXY(50, 80.5);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '........................................................................................................................................................'));
    // $pdf->SetXY(x:25, y:93.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'dfdgdg'));
    $pdf->SetXY(20, 89);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));
    // $pdf->SetXY(x:25, y:101.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'dfdgdg'));
    $pdf->SetXY(20, 97);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(20, 112);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'ชื่อเรื่อง (ภาษาอังกฤษ)'));
    
    $pdf->SetXY(x:60, y:117); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectEng_gs14report']));
    $pdf->SetXY(55, 112.5);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.................................................................................................................................................'));
    // $pdf->SetXY(x:25, y:125.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'dfdgdg'));
    $pdf->SetXY(20, 121);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    // $pdf->SetXY(x:25, y:133.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'dfdgdg'));
    $pdf->SetXY(20, 129);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.........................................................................................................................................................................................'));

    $pdf->SetXY(x:90, y:179.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(105, 167);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'ผู้เสนอ'));
    $pdf->SetXY(80, 175);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '..................................................................................'));
    
    $pdf->SetXY(x:110, y:187.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(80, 183);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'รหัสประจำตัว'));
    $pdf->SetXY(101, 183);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '  ........................................................'));
    
    $pdf->SetXY(x:110, y:195.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(80, 191);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'สาขาวิชา'));
    $pdf->SetXY(103, 191);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.........................................................'));


    $pdf->SetXY(x:120, y:244.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorMain_gs14report']));
    $pdf->SetXY(x:120, y:252.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['advisorSecond_gs14report']));
    $pdf->SetXY(80, 240);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาหลัก............................................................................'));
    $pdf->SetXY(80, 248);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'อาจารย์ที่ปรึกษาร่วม.............................................................................'));
    $pdf->SetXY(155, 266);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '/ ...รายละเอียด...'));



    $pdf->Output();

    unlink($signatureImage);
    unlink($signatureImagechairpersoncurriculum);
    unlink($signatureImageAcademicResearchAssociateDean);
} else {
    echo json_encode(["error" => "Document not found."]);
}
    ?>