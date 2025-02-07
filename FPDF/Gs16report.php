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
        gs16.id_gs16report,
        gs16.name_gs16report,
        gs16.projectType_gs16report,
        gs16.projectThai_gs16report,
        gs16.projectEng_gs16report,
        gs16.projectDefenseDate_gs16report,
        gs16.projectDefenseResult_gs16report,
        gs16.thesisAdvisor_gs16report,
        gs16.status_gs16report,
        gs16.at_gs16report,
        gs16.signature_gs16report,
        gs16.date_gs16report,
        gs16.signName_gs16report
    FROM 
        gs16report gs16
    LEFT JOIN 
        student s ON gs16.idstd_student = s.idstd_student
    LEFT JOIN 
        studyplan sp ON s.id_studyplan = sp.id_studyplan
    WHERE 
        gs16.id_gs16report = ?
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
        gradofficersignGs16_IdDocs,
        gradofficersignGs16_nameDocs,
        gradofficersignGs16_nameGradoffice,
        gradofficersignGs16_description,
        gradofficersignGs16_sign,
        gradofficersignGs16_status,
        gradofficersignGs16_ThesisCertificateDoc,
        gradofficersignGs16_GraduationApprovalReport,
        gradofficersignGs16_at
    FROM 
        gradofficersigngs16
    WHERE 
        gradofficersignGs16_IdDocs = ?
    ");

    if (!$stmt) {
        die("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $docId);
    $stmt->execute();
    $result = $stmt->get_result();

    $GraduateOfficerSignatures = [];

    while ($row = $result->fetch_assoc()) {
        $timestamp = strtotime($row['gradofficersignGs16_at']);
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

        $signatureDataGraduateOfficer = $row['gradofficersignGs16_sign'];
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
    $date1_thai = convertToThaiDate($document['date_gs16report'] ?? '');
    $date2_thai = convertToThaiDate($document['projectDefenseDate_gs16report'] ?? '');

    /// แปลง Base64 เป็นไฟล์ PNG
    $signatureData = $document['signature_gs16report'];
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

            $this->SetFont('ZapfDingbats','', $ori_font_size);
            $this->Cell($checkbox_size, $checkbox_size, $check, 1, 0);
            $this->SetFont($ori_font_family, $ori_font_style, $ori_font_size);
        }
    }
    $pdf = new FPDF(); 
    $pdf = new PDF(); 

    // Add Thai font 
    $pdf->AddFont('THSarabunNew','','THSarabunNew.php');
    $pdf->AddFont('THSarabunNew','B','THSarabunNew_b.php');
    $pdf->AddPage();
    $pdf->SetFillColor(192);
    $pdf->Image('img/logo.png', 15, 5, 15, 0);  

    $pdf->SetXY(170, 15);
    $pdf->SetFont('THSarabunNew','B',14);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'คคอ. บว. 16'));
    $pdf->SetXY(150, 6.5);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0,40, iconv('UTF-8', 'cp874', 'ครุศาสตร์อุตสาหกรรม มทร.อีสาน'));
    $pdf->SetXY(70, 42);
    $pdf->SetFont('THSarabunNew','B',18);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์'));


    
   
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->SetXY(189, 52);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date1_thai), 0, 1, 'R');

    $pdf->SetXY(20, 62);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'เรื่อง'));
    $pdf->SetXY(35, 57);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,10, iconv('UTF-8', 'cp874', 'ขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์'));

    $pdf->SetXY(20, 64);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(15,10, iconv('UTF-8', 'cp874', 'เรียน'));
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));
   

    $pdf->SetXY(x:82, y:84.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(36, 80);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', "ข้าพเจ้า (". $document['prefix_student'].").....................................................................................(พิมพ์หรือเขียนตัวบรรจง)"));
   
    $pdf->SetXY(x:79, y:91.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_studentEng']));
    $pdf->SetXY(48, 87);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', "(". $document['prefix_studentEng'].").............................................................................................(พิมพ์หรือเขียนตัวบรรจง)"));

    $pdf->SetXY(x:45, y:99.5); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['idstd_student']));
    $pdf->SetXY(20, 95.5);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'รหัสประจำตัว............................................................'));

    $pdf->SetXY(60, 106);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_studyplan']));
    $pdf->SetXY(20, 102);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท.................................................................'));

    $pdf->SetXY(120, 105);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคปกติ') !== false, 
        4, 
        'THSarabunNew', 
        16
    );    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'),0,1, 'L');

    $pdf->SetXY(145, 105);
    $pdf->checkboxMark(
        strpos($document['name_studyplan'], 'ภาคสมทบ') !== false, 
        4, 
        'THSarabunNew', 
        16
    );    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'),0,1, 'L');

    $pdf->SetXY(x:40, y:112); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['major_student']));
    $pdf->SetXY(x:100, y:112); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['branch_student']));
    $pdf->SetXY(x:178, y:112); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $document['abbreviate_student']));
    $pdf->SetXY(20, 108);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'สาขาวิชา......................................................สาขา................................................................อักษรย่อสาขาวิชา.................'));

    $pdf->SetXY(x:60, y:119); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['address_student']));
    $pdf->SetXY(20, 115);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก...................................................................................................................................................'));
    $pdf->SetXY(x:40, y:126); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',  $document['email_student']));
    $pdf->SetXY(x:152, y:126); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['tel_student']));
    $pdf->SetXY(20, 122);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'E-mail:......................................................................................หมายเลขโทรศัพท์ มือถือ..................................................'));

    $pdf->SetXY(20, 137);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'มีความประสงค์ขอสอบหัวข้อ  ขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์'));

    $pdf->SetXY(x:82, y:148); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874',$date2_thai));
    $pdf->SetXY(20, 144);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', 'ได้ดำเนินการสอบป้องกันวิทยานิพนธ์ เมื่อ.......................................................................'));

    $pdf->SetXY(20, 151);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(10,10, iconv('UTF-8', 'cp874', 'ผลการสอบ'));

    $pdf->SetXY(45, 154);
    $pdf->checkboxMark($document['projectDefenseResult_gs16report'] == 'ผ่าน (ส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์ไม่เกิน 15 วันนับจากวันสอบ)', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ผ่าน (ส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์ไม่เกิน 15 วันนับจากวันสอบ)'),0,1, 'L');

    $pdf->SetXY(45, 162);
    $pdf->checkboxMark($document['projectDefenseResult_gs16report'] == 'ผ่านโดยมีเงื่อนไข (ส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์ไม่เกิน 30 วันนับจากวันสอบ)', 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ผ่านโดยมีเงื่อนไข (ส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์ไม่เกิน 30 วันนับจากวันสอบ) '),0,1, 'L');
    $pdf->SetXY(20, 169.5);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'บัดนี้ได้ปรับปรุงแก้ไขเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ตามข้อเสนอแนะของคณะกรรมการสอบเรียบร้อยแล้ว '),0,1, 'L');
    $pdf->SetXY(20, 176.5);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'แลัวได้ส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์ พร้อม Flash drive (file .doc และ .pdf)'),0,1, 'L');
    $pdf->SetXY(20, 183.5);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', 'ชื่อวิทยานิพนธ์ (พิมพ์หรือเขียนตัวบรรจง)'),0,1, 'L');

    $pdf->SetXY(x:50, y:191); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectThai_gs16report']));
    $pdf->SetXY(26, 187);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '(ภาษาไทย)....................................................................................................................................................................'));
    $pdf->SetXY(20, 194);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.............................................................................................................................................................................................'));
    $pdf->SetXY(x:50, y:205); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['projectEng_gs16report']));
    $pdf->SetXY(26, 201);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '(ภาษาอังกฤษ)...............................................................................................................................................................'));
    $pdf->SetXY(20, 208);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(20,10, iconv('UTF-8', 'cp874', '.............................................................................................................................................................................................'));


    $pdf->SetXY(33, 225);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));
    // $pdf->SetXY(x:113, y:245);
    if (file_exists($signatureImage)) {
        $pdf->Image($signatureImage, 120, 230, 30, 0, 'PNG'); 
    } else {
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
    } 
    $pdf->SetXY(100, 244);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
    $pdf->SetXY(x:113, y:253); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $document['name_student']));
    $pdf->SetXY(108, 252);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '(.............................................................)'));

    $pdf->SetXY(165, 272);
    $pdf->SetFont('THSarabunNew','',12);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '/...ความเห็นประธาน...'));


    //------------------ Page 2 ----------------------------------------------------------------- 2 //
    $pdf->AddPage();
    $pdf->SetXY(20, 20);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'ความเห็นของอาจารย์ที่ปรึกษาวิทยานิพนธ์'));
    $pdf->SetXY(20,25);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ได้พิจารณารายละเอียดตามคำร้องของนักศึกษาแล้ว'));

    if (isset($teacherSignatures[0]['teachersign_status']) && 
    $teacherSignatures[0]['teachersign_status'] == 'ได้รับการอนุมัติจากครูอาจารย์ที่ปรึกษาแล้ว') {
        $pdf->SetXY(22, 32);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  เห็นสมควรให้ดำเนินการตามเสนอได้'));
    } else {
        $pdf->SetXY(22, 32);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  เห็นสมควรให้ดำเนินการตามเสนอได้'));
    }

    if (isset($teacherSignatures[0]['teachersign_status']) && 
    $teacherSignatures[0]['teachersign_status'] == 'ถูกปฏิเสธจากครูอาจารย์ที่ปรึกษาแล้ว') {
        $teacherSignaturesDescription = isset($teacherSignatures[0]['teachersign_description']) 
        ? $teacherSignatures[0]['teachersign_description'] 
        : '';
    
    $pdf->SetXY(45, 40); 
    $pdf->SetFont('THSarabunNew', '', 16); 
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $teacherSignaturesDescription));
        $pdf->SetXY(22, 39);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'),0,1, 'L');
    
    }else{
        $pdf->SetXY(22, 39);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'),0,1, 'L');
    
    }
   
    $pdf->SetXY(x:126, y:55); 
    //$pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'dfngkjgmmgkmg'));
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
    
        if ($teacherSignature['teachersign_nameTeacher'] === $document['thesisAdvisor_gs16report']) {
            if (file_exists($teacherImage)) {
                $pdf->Image($teacherImage, 126, 47, 30, 0, 'PNG'); 
            } else {
                $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
            }
        }
    
        if (file_exists($teacherImage)) {
            unlink($teacherImage);
        }
    }
    $pdf->SetXY(110, 54);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $teacherSignaturesName = isset($teacherSignatures[0]['teachersign_nameTeacher']) 
        ? $teacherSignatures[0]['teachersign_nameTeacher'] 
        : '';
    $pdf->SetXY(130, 62);
    $pdf->SetFont('THSarabunNew', '', 16);
    $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',   $teacherSignaturesName));
  
    $pdf->SetXY(118, 61);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '(............................................................................)'));
    $pdf->SetXY(127, 70);
    foreach ($teacherSignatures as $signature) {
        $thai_date_formattedteachersign = $signature['thai_date_formattedteachersign'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedteachersign), 0, 1, 'L');
    }
 

    $pdf->SetXY(20, 77);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'));
    $pdf->SetXY(26, 81.5);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ได้พิจารณารายละเอียดตามเสนอแล้ว'));
    $pdf->SetXY(27, 88);
    $pdf->checkboxMark(
        isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) &&
        $chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว',
        4,
        'THSarabunNew',
        16
    ); 
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นสมควรให้ดำเนินการตามเสนอได้'),0,1, 'L');

    if (isset($chairpersoncurriculumSignatures[0]['ccurrsigna_status']) && 
$chairpersoncurriculumSignatures[0]['ccurrsigna_status'] == 'ถูกปฏิเสธจากประธานคณะกรรมการบริหารหลักสูตรแล้ว') {
    $description = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_description']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_description'] : '';

    $pdf->SetXY(x:112, y:89); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $description));
    $pdf->SetXY(95, 88);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ..........................................................................................'),0,1, 'L');

}else{ 
    $pdf->SetXY(95, 88);
    $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ..........................................................................................'),0,1, 'L');
}
   

   // $pdf->SetXY(x:126, y:105); 
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png'; 
        $pdf->Image($signatureImagechairpersoncurriculum, 126, 95, 40);  
    }        $pdf->SetXY(110, 104);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
    $pdf->SetXY(x:130, y:111); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $chairpersonName));
    $pdf->SetXY(118, 110);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '(............................................................................)'));
   
    $pdf->SetXY(127, 119);
    $pdf->SetFont('THSarabunNew','',16);
    foreach ($chairpersoncurriculumSignatures as $signature) {
        $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
    }

    $pdf->SetXY(20, 126);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะ ฯ'));
    $pdf->SetXY(20, 129);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'เรียน  คณบดีคณะครุศาสตร์อุตสาหกรรม'));
    if (isset($graduateOfficerSignatures[0]['gradofficersignGs16_status']) && 
    $graduateOfficerSignatures[0]['gradofficersignGs16_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว') {
        $pdf->SetXY(26, 136);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  ได้ตรวจสอบแล้วเห็นสมควรอนุมัติ พร้อมนี้ได้แนบเอกสารมาเพื่อโปรดลงนาม'));

    $ThesisCertificateDocGs16 = isset($graduateOfficerSignatures[0]['gradofficersignGs16_ThesisCertificateDoc']) ? $graduateOfficerSignatures[0]['gradofficersignGs16_ThesisCertificateDoc'] : '';
    $checkboxMark1 = !empty($ThesisCertificateDocGs16);
    $pdf->SetXY(34, 142);
    $pdf->checkboxMark($checkboxMark1, 4, 'THSarabunNew', 16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  ใบรับรองวิทยานิพนธ์'));

    $GraduationApprovalReportGs16 = isset($graduateOfficerSignatures[0]['gradofficersignGs16_GraduationApprovalReport']) ? $graduateOfficerSignatures[0]['gradofficersignGs16_GraduationApprovalReport'] : '';
    $checkboxMark2 = !empty($GraduationApprovalReportGs16);
    $pdf->SetXY(34, 148);
    $pdf->checkboxMark($checkboxMark2, 4, 'THSarabunNew', 16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  แบบรายงานการอนุมัติผลการสำเร็จการศึกษา'));
    }else{
        $pdf->SetXY(26, 136);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  ได้ตรวจสอบแล้วเห็นสมควรอนุมัติ พร้อมนี้ได้แนบเอกสารมาเพื่อโปรดลงนาม'));
    
        $pdf->SetXY(34, 142);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  ใบรับรองวิทยานิพนธ์'));
    
        $pdf->SetXY(34, 148);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  แบบรายงานการอนุมัติผลการสำเร็จการศึกษา'));
    }

    if (isset($graduateOfficerSignatures[0]['gradofficersignGs16_status']) && 
    $graduateOfficerSignatures[0]['gradofficersignGs16_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว') {
        $DescriptiontGs16 = isset($graduateOfficerSignatures[0]['gradofficersignGs16_description']) ? $graduateOfficerSignatures[0]['gradofficersignGs16_description'] : '';
        $pdf->SetXY(x:45, y:156); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $DescriptiontGs16));
        $pdf->SetXY(26, 155);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'),0,1, 'L');
    
    }else{
        $pdf->SetXY(26, 154);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '  อื่น ๆ.......................................................................................................................................................................'));
    }
    
   // $pdf->SetXY(x:126, y:168); 
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                $pdf->Image($signature['signature_file_path'], 120, 160, 40);
                unlink($signature['signature_file_path']);
            } else {
                error_log('Signature file does not exist: ' . $signature['signature_file_path']);
            }
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid.');
    }    
    $pdf->SetXY(110, 167);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ลงชื่อ..............................................................................'));
    $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersignGs16_nameGradoffice']) 
    ? $graduateOfficerSignatures[0]['gradofficersignGs16_nameGradoffice'] : '';
    $pdf->SetXY(x:130, y:174); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $GradOfficeName));
    $pdf->SetXY(118, 173);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '(............................................................................)'));
    $pdf->SetXY(127, 181);
    $pdf->SetFont('THSarabunNew','',16);
    if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
        foreach ($graduateOfficerSignatures as $signature) {
            $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];


            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer));
        }
    } else {
        error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
    }

    $pdf->SetXY(20, 186);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(25, 192);
    $pdf->checkboxMark(
        isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) &&
        $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ได้รับการอนุมัติจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว',
        4,
        'THSarabunNew',
        16
    );     
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  เห็นควรอนุมัติ'),0,1, 'L');
    if (isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status']) && 
    $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_status'] == 'ถูกปฏิเสธจากรองคณบดีฝ่ายวิชาการและวิจัยแล้ว') {
        $AcademicResearchAssociateDeanDescription = isset($AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description']) ? $AcademicResearchAssociateDeanSignatures[0]['vdAcrsign_description'] : '';
        $pdf->SetXY(x:90, y:193); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $AcademicResearchAssociateDeanDescription));
        $pdf->SetXY(70, 192);
    $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ...................................................................................................................'),0,1, 'L');

    }else{
        $pdf->SetXY(70, 192);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ...................................................................................................................'),0,1, 'L');
    }
    
   // $pdf->SetXY(x:125, y:206);
   if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
    foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
        if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
            $pdf->Image($signature['signature_file_path'], 125, 195, 40);
            unlink($signature['signature_file_path']);
        } else {
            error_log('Signature file does not exist: ' . $signature['signature_file_path']);
        }
    }
} else {
    error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
}    
    $pdf->SetXY(110, 205);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(117, 212);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'));
    $pdf->SetXY(124, 219);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'));
    $pdf->SetXY(123, 227);
    $pdf->SetFont('THSarabunNew','',16);
    if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
        foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
            $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
        }
    } else {
        error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
    }


    $pdf->SetXY(20, 233);
    $pdf->SetFont('THSarabunNew','B',16);
    $pdf->Cell(0,0, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'));
    $pdf->SetXY(25, 237);
    $pdf->checkboxMark(
        isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
        $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ได้รับการอนุมัติจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว',
        4,
        'THSarabunNew',
        16
    );       
    $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อนุมัติ'),0,1, 'L');
    if(isset($IndustrialEducationDeanSignatures[0]['deanfiesign_status']) &&
    $IndustrialEducationDeanSignatures[0]['deanfiesign_status'] == 'ถูกปฏิเสธจากคณบดีคณะครุศาสตร์อุตสาหกรรมแล้ว'){
        $IndustrialEducationDeanDescription = isset($IndustrialEducationDeanSignatures[0]['deanfiesign_description']) ?
        $IndustrialEducationDeanSignatures[0]['deanfiesign_description'] : '';

        $pdf->SetXY(x:90, y:238); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription));
        $pdf->SetXY(70, 237);
        $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................'),0,1, 'L');
     
    }else{
      //  $pdf->SetXY(x:90, y:238); $pdf->SetFont('THSarabunNew','',16); $pdf->Cell(2,0, iconv('UTF-8', 'cp874', 'dfngkjgmmgkmg'));
        $pdf->SetXY(70, 237);
        $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ....................................................................................................................'),0,1, 'L');
     
    }
   
   // $pdf->SetXY(x:125, y:253);
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
    }      $pdf->SetXY(110, 252);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................'));
    $pdf->SetXY(118, 258);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์ ยาวระ)'));
    $pdf->SetXY(120, 265);
    $pdf->SetFont('THSarabunNew','',16);
    $pdf->Cell(0,4, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'));
   
    $pdf->SetXY(123, 274);
    $pdf->SetFont('THSarabunNew','',16);
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