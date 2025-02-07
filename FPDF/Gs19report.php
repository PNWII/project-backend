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
         gs19.id_gs19report,
         gs19.name_gs19report,
         gs19.semesterAt_gs19report,
         gs19.academicYear_gs19report,
         gs19.courseCredits_gs19report,
         gs19.cumulativeGPA_gs19report,
         gs19.projectKnowledgeExamDate_gs19report,
         gs19.projectDefenseDate_gs19report,
         gs19.additionalDetails_gs19report,
         gs19.thesisAdvisor_gs19report,
         gs19.status_gs19report,
         gs19.at_gs19report,
         gs19.signature_gs19report,
         gs19.date_gs19report,
         gs19.signName_gs19report
     FROM 
         gs19report gs19
     LEFT JOIN 
         student s ON gs19.idstd_student = s.idstd_student
     LEFT JOIN 
         studyplan sp ON s.id_studyplan = sp.id_studyplan
     WHERE 
         gs19.id_gs19report = ?
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
        gradofficersignGs19_IdDocs,
        gradofficersignGs19_nameDocs,
        gradofficersignGs19_nameGradoffice,
        gradofficersignGs19_description,
        gradofficersignGs19_sign,
        gradofficersignGs19_status,
        gradofficersignGs19_masterPlanTwoApprovalDoc,
        gradofficersignGs19_at
     FROM 
         gradofficersigngs19
     WHERE 
        gradofficersignGs19_IdDocs = ?
     ");

        if (!$stmt) {
            die("SQL prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $docId);
        $stmt->execute();
        $result = $stmt->get_result();

        $GraduateOfficerSignatures = [];

        while ($row = $result->fetch_assoc()) {
            $timestamp = strtotime($row['gradofficersignGs19_at']);
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

            $signatureDataGraduateOfficer = $row['gradofficersignGs19_sign'];
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
        $date1_thai = convertToThaiDate($document['date_gs19report'] ?? '');
        $date2_thai = convertToThaiDate($document['projectKnowledgeExamDate_gs19report'] ?? '');
        $date3_thai = convertToThaiDate($document['projectDefenseDate_gs19report'] ?? '');



        /// แปลง Base64 เป็นไฟล์ PNG
        $signatureData = $document['signature_gs19report'];
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
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คคอ. บว. 19'), 0, 1, 'R');
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'คณะครุศาสตร์อุตสาหกรรม มทร.อีสาน'), 0, 1, 'R');
        $pdf->SetXY(20, 20);
        $pdf->SetFont('THSarabunNew', 'B', 18);
        $pdf->Cell(0, 30, iconv('UTF-8', 'cp874', 'แบบขออนุมัติผลสำเร็จการศึกษา'), 0, 1, 'C');

        $pdf->SetXY(20, 42.5);
        $pdf->SetFont('THSarabunNew', 'B', 18);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ'), 0, 1, 'C');

        $pdf->SetXY(20, 43);
        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->Cell(0, 25, iconv('UTF-8', 'cp874', $date1_thai), 0, 1, 'R');

        $pdf->SetXY(20, 66);
        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรื่อง'), 0, 0);
        $pdf->Ln(0);

        $pdf->SetXY(33.5, 68);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(0, 6, iconv('UTF-8', 'cp874', 'ขอสอบประมวลความรู้'), 0, 1, 'L');

        $pdf->SetXY(20, 73);
        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'เรียน'), 0, 0, 'L');


        $pdf->SetXY(33.5, 75);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(0, 6, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 0, 'L');

        $pdf->SetXY(x: 70, y: 93);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
        $pdf->SetXY(x: 153, y: 93);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['idstd_student']));
        $pdf->SetXY(33.5, 89);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', "ข้าพเจ้า (" . $document['prefix_student'] . ").........................................................................รหัสประจำตัว........................................................"));

        $pdf->SetXY(20, 96.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(10, 10, iconv('UTF-8', 'cp874', 'นักศึกษาระดับปริญญาโท     แผน 2 แบบวิชาชีพ'));

        $pdf->SetXY(102, 99);
        $pdf->checkboxMark(
            strpos($document['name_studyplan'], 'ภาคปกติ') !== false,
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคปกติ'), 0, 1, 'L');

        $pdf->SetXY(128, 99);
        $pdf->checkboxMark(
            strpos($document['name_studyplan'], 'ภาคสมทบ') !== false,
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ภาคสมทบ'), 0, 1, 'L');

        $pdf->SetXY(x: 40, y: 107.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['major_student']));
        $pdf->SetXY(x: 170, y: 107.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['abbreviate_student']));
        $pdf->SetXY(20, 98);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'สาขาวิชา..........................................................................................................................อักษรย่อสาขา...................................'), 0, 1, 'L');

        $pdf->SetXY(x: 63, y: 115);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['address_student']));
        $pdf->SetXY(20, 105.5);
        $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'ที่อยู่ที่ติดต่อได้โดยสะดวก..........................................................................................................................................................'), 0, 1, 'L');

        $pdf->SetXY(x: 35, y: 122.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['email_student']));
        $pdf->SetXY(x: 155, y: 122.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $document['tel_student']));
        $pdf->SetXY(20, 113);
        $pdf->Cell(0, 20, iconv('UTF-8', 'cp874', 'E-mail:...................................................................................หมายเลขโทรศัพท์ มือถือ............................................................'), 0, 1, 'L');

        $pdf->SetXY(20, 136);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'มีความประสงค์ ขออนุมัติผลสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ'), 0, 1, 'L');

        $pdf->SetXY(x: 140, y: 146);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $document['semesterAt_gs19report']));
        $pdf->SetXY(x: 172.5, y: 146);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $document['academicYear_gs19report']));
        $pdf->SetXY(20, 144);
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', 'ทั้งนี้ได้ปฏิบัติตามเงื่อนไขเพื่อขออนุมัติผลการสำเร็จการศึกษาในภาคการศึกษาที่.................ปีการศึกษา.................ดังนี้'), 0, 1, 'L');

        $pdf->SetXY(x: 104, y: 153.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['courseCredits_gs19report']));
        $pdf->SetXY(x: 155, y: 153.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['cumulativeGPA_gs19report']));
        $pdf->SetXY(21, 152);
        $pdf->checkboxMark(
            !empty($document['courseCredits_gs19report']) && !empty($document['cumulativeGPA_gs19report']),
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ศึกษารายวิชาครบตามที่กำหนดในหลักสูตรจำนวน................หน่วยกิต มีคะแนนเฉลี่ย......................'), 0, 1, 'L');
        $pdf->SetXY(x: 85, y: 160.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date2_thai));
        $pdf->SetXY(21, 159);
        $pdf->checkboxMark(
            !empty($document['projectKnowledgeExamDate_gs19report']),
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  สอบผ่านการสอบประมวลความรู้ เมื่อ.................................................................................................'), 0, 1, 'L');

        $pdf->SetXY(x: 90, y: 168.5);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $date3_thai));
        $pdf->SetXY(21, 167);
        $pdf->checkboxMark(
            !empty($document['projectDefenseDate_gs19report']),
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  สอบป้องกันการค้นคว้าอิสระผ่านแล้วเมื่อ..........................................................................................'), 0, 1, 'L');

        $pdf->SetXY(x: 40, y: 175);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['additionalDetails_gs19report']));
        $pdf->SetXY(21, 174);
        $pdf->checkboxMark(
            !empty($document['additionalDetails_gs19report']),
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ................................................................................................................................................................................'), 0, 1, 'L');

        $pdf->SetXY(32, 187);
        $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'จึงเรียนมาเพื่อโปรดพิจารณา'));
        $pdf->SetXY(126, 200);
        $pdf->Cell(20, 10, iconv('UTF-8', 'cp874', 'ขอแสดงความนับถือ'));
        // $pdf->SetXY(x:120, y:231); 
        if (file_exists($signatureImage)) {
            $pdf->Image($signatureImage, 120, 217, 30, 0, 'PNG');
        } else {
            $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็น'), 0, 1, 'C');
        }
        $pdf->SetXY(105, 232);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ลงชื่อ.................................................................'));
        $pdf->SetXY(x: 120, y: 241);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $document['name_student']));
        $pdf->SetXY(113, 242);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '(.............................................................)'));

        $pdf->SetXY(165, 276);
        $pdf->SetFont('THSarabunNew', '', 12);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '/....ความเห็นอาจารย์..............'));

        ////////////////---------------------- หน้า 2 ------------------------------------
        $pdf->SetMargins(50, 30, 10);
        $pdf->AddPage();

        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->SetXY(20, 20);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของอาจารย์ที่ปรึกษาการศึกษาค้นคว้าอิสระหลัก'), 0, 1, 'L');

        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->SetXY(20, 27);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ได้พิจารณารายละเอียดตามคำร้องของนักศึกษาแล้ว'), 0, 1, 'L');

        $pdf->SetXY(21, 32);
        $pdf->checkboxMark(
            isset($teacherSignatures[0]['teachersign_status']) &&
                $teacherSignatures[0]['teachersign_status'] == 'ได้รับการอนุมัติจากครูอาจารย์ที่ปรึกษาแล้ว',
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  เห็นสมควรอนุมัติให้ดำเนินการตามเสนอได้'), 0, 1, 'L');

        if (
            isset($teacherSignatures[0]['teachersign_status']) &&
            $teacherSignatures[0]['teachersign_status'] == 'ถูกปฏิเสธจากครูอาจารย์ที่ปรึกษาแล้ว'
        ) {
            $teacherSignaturesDescription = isset($teacherSignatures[0]['teachersign_description'])
                ? $teacherSignatures[0]['teachersign_description']
                : '';
            $pdf->SetXY(x: 43, y: 41);
            $pdf->SetFont('THSarabunNew', '', 16);
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $teacherSignaturesDescription));
            $pdf->SetXY(21, 39);
            $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ.........................................................................................................................................................................'), 0, 1, 'L');
        } else {
            $pdf->SetXY(21, 39);
            $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 5, iconv('UTF-8', 'cp874', '  อื่น ๆ.........................................................................................................................................................................'), 0, 1, 'L');
        }

        // $pdf->SetXY(x:120, y:55); 
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

            if ($teacherSignature['teachersign_nameTeacher'] === $document['thesisAdvisor_gs19report']) {
                if (file_exists($teacherImage)) {
                    $pdf->Image($teacherImage, 125, 46, 30, 0, 'PNG');
                } else {
                    $pdf->Cell(0, 10, iconv('UTF-8', 'cp874', 'ไม่พบลายเซ็นของอาจารย์ ' . $teacherSignature['teachersign_nameTeacher']), 0, 1, 'C');
                }
            }

            if (file_exists($teacherImage)) {
                unlink($teacherImage);
            }
        }
        $pdf->SetXY(95, 56);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ลงชื่อ......................................................................................'), 0, 1, 'C');
        $teacherSignaturesName = isset($teacherSignatures[0]['teachersign_nameTeacher'])
            ? $teacherSignatures[0]['teachersign_nameTeacher']
            : '';
        $pdf->SetXY(x: 125, y: 61);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $teacherSignaturesName));
        $pdf->SetXY(102, 62);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '(..................................................................................)'), 0, 1, 'C');

        $pdf->SetXY(123, 69);
        foreach ($teacherSignatures as $signature) {
            $thai_date_formattedteachersign = $signature['thai_date_formattedteachersign'];
            $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', $thai_date_formattedteachersign), 0, 1, 'L');
        }
        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->SetXY(20, 75);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของประธานคณะกรรมการบริหารหลักสูตร'), 0, 1, 'L');
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->SetXY(20, 82);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ได้พิจารณารายละเอียดตามเสนอแล้ว'), 0, 1, 'L');

        $pdf->SetXY(21, 87);
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

            $pdf->SetXY(x: 44, y: 95);
            $pdf->SetFont('THSarabunNew', '', 16);
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $description));
            $pdf->SetXY(21, 94);
            $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ .........................................................................................................................................................................'), 0, 1, 'L');
        } else {

            $pdf->SetXY(21, 94);
            $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ .........................................................................................................................................................................'), 0, 1, 'L');
        }


        // $pdf->SetXY(x:120, y:112); 
        foreach ($chairpersoncurriculumSignatures as $signature) {
            $signatureImagechairpersoncurriculum = 'signature_chairpersoncurriculum.png';
            $pdf->Image($signatureImagechairpersoncurriculum, 115, 100, 40);
        }
        $pdf->SetXY(95, 113);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ลงชื่อ......................................................................................'), 0, 1, 'C');
        $chairpersonName = isset($chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum']) ? $chairpersoncurriculumSignatures[0]['ccurrsigna_nameChairpersonCurriculum'] : '';
        $pdf->SetXY(x: 125, y: 118);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $chairpersonName));
        $pdf->SetXY(102, 119);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '(..................................................................................)'), 0, 1, 'C');
        $pdf->SetXY(123, 126);
        foreach ($chairpersoncurriculumSignatures as $signature) {
            $thai_date_formattedchairpersoncurriculum = $signature['thai_date_formattedchairpersoncurriculum'];
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedchairpersoncurriculum), 0, 1, 'L');
        }
        $pdf->SetXY(20, 131);
        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'บันทึกเจ้าหน้าที่บัณฑิตศึกษาประจำคณะฯ'), 0, 1, 'L');

        $pdf->SetXY(20, 137);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'เรียน  คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');

        $pdf->SetXY(21, 142);
        $pdf->checkboxMark(
            isset($graduateOfficerSignatures[0]['gradofficersignGs19_status']) &&
                $graduateOfficerSignatures[0]['gradofficersignGs19_status'] == 'ได้รับการอนุมัติจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว',
            4,
            'THSarabunNew',
            16
        );
        $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  ได้ตรวจสอบแล้วเห็นสมควรอนุมัติ พร้อมนี้ได้แนบแบบรายงานการอนุมัติผลการสำเร็จการศึกษา'), 0, 1, 'L');

        if (
            isset($graduateOfficerSignatures[0]['gradofficersignGs19_status']) &&
            $graduateOfficerSignatures[0]['gradofficersignGs19_status'] == 'ถูกปฏิเสธจากเจ้าหน้าที่บัณฑิตศึกษาแล้ว'
        ) {
            $DescriptiontGs19 = isset($graduateOfficerSignatures[0]['gradofficersignGs19_description']) ?
                $graduateOfficerSignatures[0]['gradofficersignGs19_description'] : '';
            $pdf->SetXY(x: 43, y: 150);
            $pdf->SetFont('THSarabunNew', '', 16);
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $DescriptiontGs19));
            $pdf->SetXY(21, 149);
            $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ .........................................................................................................................................................................'), 0, 1, 'L');
        } else {
            $pdf->SetXY(21, 149);
            $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ .........................................................................................................................................................................'), 0, 1, 'L');
        }
        // $pdf->SetXY(x:120, y:164);
        if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
            foreach ($graduateOfficerSignatures as $signature) {
                if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                    $pdf->Image($signature['signature_file_path'], 120, 155, 40);
                    unlink($signature['signature_file_path']);
                } else {
                    error_log('Signature file does not exist: ' . $signature['signature_file_path']);
                }
            }
        } else {
            error_log('graduateOfficerSignatures is null or not valid.');
        }
        $pdf->SetXY(95, 165);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ลงชื่อ......................................................................................'), 0, 1, 'C');
        $GradOfficeName = isset($graduateOfficerSignatures[0]['gradofficersignGs19_nameGradoffice'])
            ? $graduateOfficerSignatures[0]['gradofficersignGs19_nameGradoffice'] : '';
        $pdf->SetXY(x: 125, y: 170);
        $pdf->SetFont('THSarabunNew', '', 16);
        $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $GradOfficeName));
        $pdf->SetXY(102, 171);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '(..................................................................................)'), 0, 1, 'C');
        $pdf->SetXY(123, 178);
        if (is_array($graduateOfficerSignatures) || is_object($graduateOfficerSignatures)) {
            foreach ($graduateOfficerSignatures as $signature) {
                $thai_date_formattedGraduateOfficer = $signature['thai_date_formattedGraduateOfficer'];


                $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $thai_date_formattedGraduateOfficer));
            }
        } else {
            error_log('graduateOfficerSignatures is null or not valid. Please check the data source.');
        }

        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->SetXY(20, 186);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของรองคณบดีฝ่ายวิชาการและวิจัย'), 0, 1, 'L');

        $pdf->SetXY(21, 192);
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
            $pdf->SetXY(x: 76, y: 193);
            $pdf->SetFont('THSarabunNew', '', 16);
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874',  $AcademicResearchAssociateDeanDescription));
            $pdf->SetXY(55, 192);
            $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................'), 0, 1, 'L');
        } else {
            $pdf->SetXY(55, 192);
            $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................'), 0, 1, 'L');
        }

        // $pdf->SetXY(x:120, y:206); 
        if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
            foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
                if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                    $pdf->Image($signature['signature_file_path'], 120, 197, 40);
                    unlink($signature['signature_file_path']);
                } else {
                    error_log('Signature file does not exist: ' . $signature['signature_file_path']);
                }
            }
        } else {
            error_log('AcademicResearchAssociateDeanSignatures is not an array or object.');
        }
        $pdf->SetXY(95, 207);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................................'), 0, 1, 'C');
        $pdf->SetXY(100, 214);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ ดร.เฉลิมพล บุญทศ)'), 0, 1, 'C');
        $pdf->SetXY(100, 220);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'รองคณบดีฝ่ายวิชาการและวิจัย'), 0, 1, 'C');
        $pdf->SetXY(123, 226);
        if (is_array($AcademicResearchAssociateDeanSignatures) || is_object($AcademicResearchAssociateDeanSignatures)) {
            foreach ($AcademicResearchAssociateDeanSignatures as $signature) {
                $thai_date_formattedAcademicResearchAssociateDean = $signature['thai_date_formattedAcademicResearchAssociateDean'];
                $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', $thai_date_formattedAcademicResearchAssociateDean), 0, 1, 'L');
            }
        } else {
            error_log('AcademicResearchAssociateDeanSignatures is null or not valid. Please check the data source.');
        }

        $pdf->SetFont('THSarabunNew', 'B', 16);
        $pdf->SetXY(20, 233);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ความเห็นของคณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'L');

        $pdf->SetXY(21, 238);
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
            $pdf->SetXY(x: 76, y: 240);
            $pdf->SetFont('THSarabunNew', '', 16);
            $pdf->Cell(2, 0, iconv('UTF-8', 'cp874', $IndustrialEducationDeanDescription));
            $pdf->SetXY(55, 238);
            $pdf->checkboxMark(true, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................'), 0, 1, 'L');
        } else {
            $pdf->SetXY(55, 238);
            $pdf->checkboxMark(false, 4, 'THSarabunNew', 16);
            $pdf->Cell(0, 4, iconv('UTF-8', 'cp874', '  อื่น ๆ ..................................................................................................................................'), 0, 1, 'L');
        }

        // $pdf->SetXY(x:120, y:254); 
        if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
            foreach ($IndustrialEducationDeanSignatures as $signature) {
                if (!empty($signature['signature_file_path']) && file_exists($signature['signature_file_path'])) {
                    // แสดงรูปภาพลายเซ็นใน PDF
                    $pdf->Image($signature['signature_file_path'], 118, 242, 40);

                    // ลบไฟล์ทันทีหลังจากแสดงผล
                    unlink($signature['signature_file_path']);
                } else {
                    error_log('Signature file does not exist: ' . $signature['signature_file_path']);
                }
            }
        }
        $pdf->SetXY(95, 255);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'ลงชื่อ....................................................................................'), 0, 1, 'C');
        $pdf->SetXY(95, 262);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', '(ผู้ช่วยศาสตราจารย์ประพันธ์  ยาวระ)'), 0, 1, 'C');
        $pdf->SetXY(95, 269);
        $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', 'คณบดีคณะครุศาสตร์อุตสาหกรรม'), 0, 1, 'C');
        $pdf->SetXY(120, 276);
        if (is_array($IndustrialEducationDeanSignatures) || is_object($IndustrialEducationDeanSignatures)) {
            foreach ($IndustrialEducationDeanSignatures as $signature) {
                $thai_date_formattedIndustrialEducationDean = $signature['thai_date_formattedIndustrialEducationDean'];
                $pdf->Cell(0, 0, iconv('UTF-8', 'cp874', $thai_date_formattedIndustrialEducationDean), 0, 1, 'L');
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

    ?>