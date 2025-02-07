<?php
// backend/EditDocument.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");


include('db_connection.php');

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// ตรวจสอบการส่งข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $documentId = intval($data['documentId'] ?? 0);
    $dataFormId = intval($data['dataFormId'] ?? 0);
    $documentType = $data['documentType'] ?? null;
    $studentId = intval($data['studentId'] ?? 0);

    // ตรวจสอบข้อมูลที่ได้รับ
    if (empty($documentId) || empty($studentId) || empty($documentType)) {
        echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"]);
        exit;
    }

    // ตรวจสอบประเภทเอกสาร
    $validDocumentTypes = [
        'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ',
        'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ',
        'คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ',
        'คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข',
        'คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ',
        'คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ',
        'คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์',
        'คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ',
        'คคอ. บว. 18 แบบขอสอบประมวลความรู้',
        'คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ',
        'คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์'
    ];
    if (!in_array($documentType, $validDocumentTypes)) {
        echo json_encode(["success" => false, "message" => "ประเภทเอกสารไม่ถูกต้อง"]);
        exit;
    }

    // ตรวจสอบว่ามีเอกสารนี้ในตาราง formsubmit หรือไม่
    $formsubmitQuery = "SELECT * FROM formsubmit WHERE formsubmit_dataform = ? AND idstd_student = ?";
    $stmt = $conn->prepare($formsubmitQuery);
    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Error in SQL query preparation: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ii", $documentId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลเอกสารในระบบ"]);
        exit;
    }

    // ตรวจสอบประเภทเอกสารและสร้าง SQL
    $sql = "";
    $params = [];
    if ($documentType === 'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
        // ใช้ null coalescing operator (??) เพื่อกำหนดค่าเริ่มต้นหากไม่มีคีย์
        $gs10projectType = $data['gs10projectType'] ?? '';
        $gs10ProjectThai = $data['gs10ProjectThai'] ?? '';
        $gs10ProjectEng = $data['gs10ProjectEng'] ?? '';
        $gs10advisorType = $data['gs10advisorType'] ?? '';
        $gs10ReportDate = $data['gs10ReportDate'] ?? '';
        $gs10advisorMainNew = $data['gs10advisorMainNew'] ?? '';
        $gs10advisorSecondNew = $data['gs10advisorSecondNew'] ?? '';
        $gs10advisorMainOld = $data['gs10advisorMainOld'] ?? '';
        $gs10advisorSecondOld = $data['gs10advisorSecondOld'] ?? '';
        $gs10document = $data['gs10document'] ?? null;
        $gs10gs10At = $data['gs10gs10At'] ?? '';

        // สร้าง SQL สำหรับการอัพเดตข้อมูล
        $sql = "UPDATE gs10report 
                SET projectType_gs10report = ?, 
                    projectThai_gs10report = ?, 
                    projectEng_gs10report = ?,
                    advisorType_gs10report = ?,
                    date_gs10report = ?,
                    advisorMainNew_gs10report = ?,
                    advisorSecondNew_gs10report = ?,
                    advisorMainOld_gs10report = ?,
                    advisorSecondOld_gs10report = ?,
                    document_gs10report = ?
                WHERE id_gs10report = ? 
                  AND idstd_student = ?";
        $params = [$gs10projectType, $gs10ProjectThai, $gs10ProjectEng, $gs10advisorType, $gs10ReportDate, $gs10advisorMainNew, $gs10advisorSecondNew, $gs10advisorMainOld, $gs10advisorSecondOld, $gs10document, $documentId, $studentId];



    } elseif ($documentType === 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
        // สำหรับ gs11report ให้ใช้การตรวจสอบคล้ายๆ กัน
        $gs11ProjectType = $data['gs11ProjectType'] ?? '';
        $gs11ProjectThai = $data['gs11ProjectThai'] ?? '';
        $gs11ProjectEng = $data['gs11ProjectEng'] ?? '';
        $gs11advisorMain = $data['gs11advisorMain'] ?? '';
        $gs11advisorSecond = $data['gs11advisorSecond'] ?? '';
        $gs11subjects = $data['gs11subjects'] ?? '';
        $gs11gpa = $data['gs11gpa'] ?? '';
        $gs11subjectsProject = $data['gs11subjectsProject'] ?? '';
        $gs11docGs10rp = $data['gs11docGs10rp'] ?? '';
        $gs11docProjectdetails = $data['gs11docProjectdetails'] ?? '';
        $gs11gs11At = $data['gs11gs11At'] ?? '';
        $gs11ReportDate = $data['gs11ReportDate'] ?? '';
        $studentId = $data['studentId'];

        // สร้าง SQL สำหรับการอัพเดตข้อมูล
        $sql = "UPDATE gs11report 
                SET projectType_gs11report = ?, 
                    projectThai_gs11report = ?, 
                    projectEng_gs11report = ?,
                    advisorMain_gs11report = ?, 
                    advisorSecond_gs11report = ?, 
                    subjects_gs11report = ?, 
                    gpa_gs11report = ?, 
                    subjectsProject_gs11report = ?, 
                    docGs10rp_gs11report = ?, 
                    docProjectdetails_gs11report = ?, 
                    date_gs11report = ?
                WHERE id_gs11report = ? 
                  AND idstd_student = ?";
        $params = [$gs11ProjectType, $gs11ProjectThai, $gs11ProjectEng, $gs11advisorMain, $gs11advisorSecond, $gs11subjects, $gs11gpa, $gs11subjectsProject, $gs11docGs10rp, $gs11docProjectdetails, $gs11ReportDate, $documentId, $studentId];

    } elseif ($documentType === 'คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
        // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 12"
        $gs12ProjectType = $data['gs12ProjectType'] ?? '';
        $gs12ProjectThai = $data['gs12ProjectThai'] ?? '';
        $gs12ProjectEng = $data['gs12ProjectEng'] ?? '';
        $gs12advisorMain = $data['gs12advisorMain'] ?? '';
        $gs12advisorSecond = $data['gs12advisorSecond'] ?? '';
        $gs12examRequestDate = $data['gs12examRequestDate'] ?? '';
        $gs12examRequestTime = $data['gs12examRequestTime'] ?? '';
        $gs12examRequestRoom = $data['gs12examRequestRoom'] ?? '';
        $gs12examRequestFloor = $data['gs12examRequestFloor'] ?? '';
        $gs12examRequestBuilding = $data['gs12examRequestBuilding'] ?? '';
        $gs12docProjectdetailsGs20rp = $data['gs12docProjectdetailsGs20rp'] ?? '';
        $gs12ReportDate = $data['gs12ReportDate'] ?? '';
        $studentId = $data['studentId'] ?? '';
        $documentId = $data['documentId'] ?? '';
        // สร้าง SQL สำหรับการอัพเดตข้อมูล
        $sql = "UPDATE gs12report  
                SET projectType_gs12report = ?, 
                    projectThai_gs12report = ?, 
                    projectEng_gs12report = ?,
                    advisorMain_gs12report = ?, 
                    advisorSecond_gs12report = ?,
                    examRequestDate_gs12report = ?,
                    examRequestTime_gs12report = ?,
                    examRequestRoom_gs12report = ?,
                    examRequestFloor_gs12report = ?,
                    examRequestBuilding_gs12report = ?,
                    docProjectdetailsGs20rp_gs12report = ?,
                    date_gs12report = ?
                WHERE id_gs12report = ? 
                  AND idstd_student = ?";
        $params = [
            $gs12ProjectType,
            $gs12ProjectThai,
            $gs12ProjectEng,
            $gs12advisorMain,
            $gs12advisorSecond,
            $gs12examRequestDate,
            $gs12examRequestTime,
            $gs12examRequestRoom,
            $gs12examRequestFloor,
            $gs12examRequestBuilding,
            $gs12docProjectdetailsGs20rp,
            $gs12ReportDate,
            $documentId,
            $studentId
        ];

    } elseif ($documentType === 'คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข') {
        // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 13"
        $gs13ProjectType = $data['gs13ProjectType'] ?? '';
        $gs13ProjectThai = $data['gs13ProjectThai'] ?? '';
        $gs13ProjectEng = $data['gs13ProjectEng'] ?? '';
        $gs13advisorMain = $data['gs13advisorMain'] ?? '';
        $gs13advisorSecond = $data['gs13advisorSecond'] ?? '';
        $gs13revisionDateAdvisor = $data['gs13revisionDateAdvisor'] ?? '';
        $gs13docProjectdetailsGs21rp = $data['gs13docProjectdetailsGs21rp'] ?? '';
        $gs13ReportDate = $data['gs13ReportDate'] ?? '';
        $studentId = $data['studentId'] ?? '';
        $documentId = $data['documentId'] ?? '';
        // สร้าง SQL สำหรับการอัพเดตข้อมูล
        $sql = "UPDATE gs13report  
                SET projectType_gs13report = ?, 
                    projectThai_gs13report = ?, 
                    projectEng_gs13report = ?,
                    advisorMain_gs13report = ?, 
                    advisorSecond_gs13report = ?,
                    revisionDateAdvisor_gs13report = ?,
                    docProjectdetailsGs21rp_gs13report = ?,
                    date_gs13report = ?
                WHERE id_gs13report = ? 
                  AND idstd_student = ?";
        $params = [
            $gs13ProjectType,
            $gs13ProjectThai,
            $gs13ProjectEng,
            $gs13advisorMain,
            $gs13advisorSecond,
            $gs13revisionDateAdvisor,
            $gs13docProjectdetailsGs21rp,
            $gs13ReportDate,
            $documentId,
            $studentId
        ];
    } elseif ($documentType === 'คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
        // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 14"
        $gs14ProjectType = $data['gs14ProjectType'] ?? '';
        $gs14ProjectThai = $data['gs14ProjectThai'] ?? '';
        $gs14ProjectEng = $data['gs14ProjectEng'] ?? '';
        $gs14advisorMain = $data['gs14advisorMain'] ?? '';
        $gs14advisorSecond = $data['gs14advisorSecond'] ?? '';
        $gs14projectApprovalDate = $data['gs14projectApprovalDate'] ?? '';
        $gs14progressExamRequestDate = $data['gs14progressExamRequestDate'] ?? '';
        $gs14progressExamRequestTime = $data['gs14progressExamRequestTime'] ?? '';
        $gs14progressExamRequestRoom = $data['gs14progressExamRequestRoom'] ?? '';
        $gs14progressExamRequestFloor = $data['gs14progressExamRequestFloor'] ?? '';
        $gs14progressExamRequestBuilding = $data['gs14progressExamRequestBuilding'] ?? '';
        $gs14docProjectdetailsGs22rp = $data['gs14docProjectdetailsGs22rp'] ?? '';
        $gs14ReportDate = $data['gs14ReportDate'] ?? '';
        $studentId = $data['studentId'] ?? '';
        $documentId = $data['documentId'] ?? '';
        // สร้าง SQL สำหรับการอัพเดตข้อมูล
        $sql = "UPDATE gs14report  
                SET projectType_gs14report = ?, 
                    projectThai_gs14report = ?, 
                    projectEng_gs14report = ?,
                    advisorMain_gs14report = ?, 
                    advisorSecond_gs14report = ?,
                    projectApprovalDate_gs14report = ?,
                    progressExamRequestDate_gs14report = ?,
                    progressExamRequestTime_gs14report = ?,
                    progressExamRequestRoom_gs14report = ?,
                    progressExamRequestFloor_gs14report = ?,
                    progressExamRequestBuilding_gs14report = ?,
                    docProjectdetailsGs22rp_gs14report = ?,                  
                    date_gs14report = ?
                WHERE id_gs14report = ? 
                  AND idstd_student = ?";
        $params = [
            $gs14ProjectType,
            $gs14ProjectThai,
            $gs14ProjectEng,
            $gs14advisorMain,
            $gs14advisorSecond,
            $gs14projectApprovalDate,
            $gs14progressExamRequestDate,
            $gs14progressExamRequestTime,
            $gs14progressExamRequestRoom,
            $gs14progressExamRequestFloor,
            $gs14progressExamRequestBuilding,
            $gs14docProjectdetailsGs22rp,
            $gs14ReportDate,
            $documentId,
            $studentId
        ];
    } elseif ($documentType === 'คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
        // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 15"
        $gs15ProjectType = $data['gs15ProjectType'] ?? '';
        $gs15ProjectThai = $data['gs15ProjectThai'] ?? '';
        $gs15ProjectEng = $data['gs15ProjectEng'] ?? '';
        $gs15advisorMain = $data['gs15advisorMain'] ?? '';
        $gs15advisorSecond = $data['gs15advisorSecond'] ?? '';
        $gs15projectApprovalDate = $data['gs15projectApprovalDate'] ?? '';
        $gs15projectProgressDate = $data['gs15projectProgressDate'] ?? '';
        $gs15defenseRequestDate = $data['gs15defenseRequestDate'] ?? '';
        $gs15defenseRequestTime = $data['gs15defenseRequestTime'] ?? '';
        $gs15defenseRequestRoom = $data['gs15defenseRequestRoom'] ?? '';
        $gs15defenseRequestFloor = $data['gs15defenseRequestFloor'] ?? '';
        $gs15defenseRequestBuilding = $data['gs15defenseRequestBuilding'] ?? '';
        $gs15courseCredits = $data['gs15courseCredits'] ?? '';
        $gs15cumulativeGPA = $data['gs15cumulativeGPA'] ?? '';
        $gs15thesisCredits = $data['gs15thesisCredits'] ?? '';
        $gs15thesisDefenseDoc = $data['gs15thesisDefenseDoc'] ?? '';
        $gs15docGs40rpGs41rp = $data['gs15docGs40rpGs41rp'] ?? '';
        $gs15docGs50rp = $data['gs15docGs50rp'] ?? '';
        $gs15docThesisExamCopy = $data['gs15docThesisExamCopy'] ?? '';
        $gs15ReportDate = $data['gs15ReportDate'] ?? '';
        $studentId = $data['studentId'] ?? '';
        $documentId = $data['documentId'] ?? '';
        // สร้าง SQL สำหรับการอัพเดตข้อมูล
        $sql = "UPDATE gs15report  
                SET projectType_gs15report = ?, 
                    projectThai_gs15report = ?, 
                    projectEng_gs15report = ?,
                    advisorMain_gs15report = ?, 
                    advisorSecond_gs15report = ?,
                    projectApprovalDate_gs15report = ?,
                    projectProgressDate_gs15report = ?,
                    defenseRequestDate_gs15report = ?,
                    defenseRequestTime_gs15report = ?,
                    defenseRequestRoom_gs15report = ?,
                    defenseRequestFloor_gs15report = ?,
                    defenseRequestBuilding_gs15report = ?,
                    courseCredits_gs15report = ?,
                    cumulativeGPA_gs15report = ?,
                    thesisCredits_gs15report = ?,
                    thesisDefenseDoc_gs15report = ?,
                    docGs40rpGs41rp_gs15report = ?,
                    docGs50rp_gs15report = ?,
                    docThesisExamCopy_gs15report = ?,
                    date_gs15report = ?
                WHERE id_gs15report = ? 
                  AND idstd_student = ?";
        $params = [
            $gs15ProjectType,
            $gs15ProjectThai,
            $gs15ProjectEng,
            $gs15advisorMain,
            $gs15advisorSecond,
            $gs15projectApprovalDate,
            $gs15projectProgressDate,
            $gs15defenseRequestDate,
            $gs15defenseRequestTime,
            $gs15defenseRequestRoom,
            $gs15defenseRequestFloor,
            $gs15defenseRequestBuilding,
            $gs15courseCredits,
            $gs15cumulativeGPA,
            $gs15thesisCredits,
            $gs15thesisDefenseDoc,
            $gs15docGs40rpGs41rp,
            $gs15docGs50rp,
            $gs15docThesisExamCopy,
            $gs15ReportDate,
            $documentId,
            $studentId
        ];
    }elseif ($documentType === 'คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์') {
        // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 16"
        $gs16ProjectThai = $data['gs16ProjectThai'] ?? '';
        $gs16ProjectEng = $data['gs16ProjectEng'] ?? '';
        $gs16ProjectDefenseDate = $data['gs16ProjectDefenseDate'] ?? '';
        $gs16ProjectDefenseResult = $data['gs16ProjectDefenseResult'] ?? '';
        $gs16ThesisAdvisor = $data['gs16ThesisAdvisor'] ?? '';
        $gs16ThesisDoc = $data['gs16ThesisDoc'] ?? '';
        $gs16ThesisPDF = $data['gs16ThesisPDF'] ?? '';
        $gs16ReportDate = $data['gs16ReportDate'] ?? '';
        $studentId = $data['studentId'] ?? '';
        $documentId = $data['documentId'] ?? '';
        // สร้าง SQL สำหรับการอัพเดตข้อมูล
        $sql = "UPDATE gs16report  
                SET projectThai_gs16report = ?, 
                    projectEng_gs16report = ?,
                    projectDefenseDate_gs16report = ?,
                    projectDefenseResult_gs16report = ?,
                    thesisAdvisor_gs16report = ?,
                    thesisDoc_gs16report = ?,
                    thesisPDF_gs16report = ?,
                    date_gs16report = ?
                WHERE id_gs16report = ? 
                  AND idstd_student = ?";
        $params = [
            $gs16ProjectThai,
            $gs16ProjectEng,
            $gs16ProjectDefenseDate,
            $gs16ProjectDefenseResult,
            $gs16ThesisAdvisor,
            $gs16ThesisDoc,
            $gs16ThesisPDF,
            $gs16ReportDate,
            $documentId,
            $studentId
        ];
}elseif ($documentType === 'คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ') {
    // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 17"
    
    $gs17ThesisAdvisor = $data['gs17ThesisAdvisor'] ?? '';
    $gs17SemesterAt = $data['gs17SemesterAt'] ?? '';
    $gs17AcademicYear = $data['gs17AcademicYear'] ?? '';
    $gs17CourseCredits = $data['gs17CourseCredits'] ?? '';
    $gs17CumulativeGPA = $data['gs17CumulativeGPA'] ?? '';
    $gs17ProjectDefenseDate = $data['gs17ProjectDefenseDate'] ?? '';
    $gs17AdditionalDetails = $data['gs17AdditionalDetails'] ?? '';
    $gs17DocCheck15 = $data['gs17DocCheck15'] ?? '';
    $gs17ReportDate = $data['gs17ReportDate'] ?? '';
    $studentId = $data['studentId'] ?? '';
    $documentId = $data['documentId'] ?? '';
    // สร้าง SQL สำหรับการอัพเดตข้อมูล
    $sql = "UPDATE gs17report  
            SET thesisAdvisor_gs17report = ?,
                semesterAt_gs17report = ?,
                academicYear_gs17report = ?,
                courseCredits_gs17report = ?,
                cumulativeGPA_gs17report = ?,
                projectDefenseDate_gs17report = ?,
                additionalDetails_gs17report = ?,
                docCheck15_gs17report = ?,
                date_gs17report = ?
            WHERE id_gs17report = ? 
              AND idstd_student = ?";
    $params = [
        $gs17ThesisAdvisor,
        $gs17SemesterAt,
        $gs17AcademicYear,
        $gs17CourseCredits,
        $gs17CumulativeGPA,
        $gs17ProjectDefenseDate,
        $gs17AdditionalDetails,
        $gs17DocCheck15,
        $gs17ReportDate,
        $documentId,
        $studentId
    ];
}elseif ($documentType === 'คคอ. บว. 18 แบบขอสอบประมวลความรู้') {
    // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 18"
    
    $gs18ThesisAdvisor = $data['gs18ThesisAdvisor'] ?? '';
    $gs18SemesterAt = $data['gs18SemesterAt'] ?? '';
    $gs18AcademicYear = $data['gs18AcademicYear'] ?? '';
    $gs18ExamRoundProject = $data['gs18ExamRoundProject'] ?? '';
    $gs18CourseCredits = $data['gs18CourseCredits'] ?? '';
    $gs18CumulativeGPA = $data['gs18CumulativeGPA'] ?? '';
    $gs18DocGs41rp = $data['gs18DocGs41rp'] ?? '';
    $gs18ReportDate = $data['gs18ReportDate'] ?? '';
    $studentId = $data['studentId'] ?? '';
    $documentId = $data['documentId'] ?? '';
    // สร้าง SQL สำหรับการอัพเดตข้อมูล
    $sql = "UPDATE gs18report  
            SET thesisAdvisor_gs18report = ?,
                semesterAt_gs18report = ?,
                academicYear_gs18report = ?,
                examRoundProject_gs18report = ?,
                courseCredits_gs18report = ?,
                cumulativeGPA_gs18report = ?,
                docGs41rp_gs18report = ?,
                date_gs18report = ?
            WHERE id_gs18report = ? 
              AND idstd_student = ?";
    $params = [
        $gs18ThesisAdvisor,
        $gs18SemesterAt,
        $gs18AcademicYear,
        $gs18ExamRoundProject,
        $gs18CourseCredits,
        $gs18CumulativeGPA,
        $gs18DocGs41rp,
        $gs18ReportDate,
        $documentId,
        $studentId
    ];
}elseif ($documentType === 'คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ') {
    // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 19"
    
    $gs19ThesisAdvisor = $data['gs19ThesisAdvisor'] ?? '';
    $gs19SemesterAt = $data['gs19SemesterAt'] ?? '';
    $gs19AcademicYear = $data['gs19AcademicYear'] ?? '';
    $gs19CourseCredits = $data['gs19CourseCredits'] ?? '';
    $gs19CumulativeGPA = $data['gs19CumulativeGPA'] ?? '';
    $gs19ProjectKnowledgeExamDate = $data['gs19ProjectKnowledgeExamDate'] ?? '';
    $gs19ProjectDefenseDate = $data['gs19ProjectDefenseDate'] ?? '';
    $gs19AdditionalDetails = $data['gs19AdditionalDetails'] ?? '';
    $gs19ReportDate = $data['gs19ReportDate'] ?? '';
    $studentId = $data['studentId'] ?? '';
    $documentId = $data['documentId'] ?? '';
    // สร้าง SQL สำหรับการอัพเดตข้อมูล
    $sql = "UPDATE gs19report  
            SET thesisAdvisor_gs19report = ?,
                semesterAt_gs19report = ?,
                academicYear_gs19report = ?,
                courseCredits_gs19report = ?,
                cumulativeGPA_gs19report = ?,
                projectKnowledgeExamDate_gs19report = ?,
                projectDefenseDate_gs19report = ?,
                additionalDetails_gs19report = ?,
                date_gs19report = ?
            WHERE id_gs19report = ? 
              AND idstd_student = ?";
    $params = [
        $gs19ThesisAdvisor,
        $gs19SemesterAt,
        $gs19AcademicYear,
        $gs19CourseCredits,
        $gs19CumulativeGPA,
        $gs19ProjectKnowledgeExamDate,
        $gs19ProjectDefenseDate,
        $gs19AdditionalDetails,
        $gs19ReportDate,
        $documentId,
        $studentId
    ];
}elseif ($documentType === 'คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์') {
    // โค้ดสำหรับจัดการข้อมูลประเภท "คคอ. บว. 23"
    
    $gs23ProjectThai = $data['gs23ProjectThai'] ?? '';
    $gs23ProjectEng = $data['gs23ProjectEng'] ?? '';
    $gs23ProjectDefenseDate = $data['gs23ProjectDefenseDate'] ?? '';
    $gs23ProjectDefenseResult = $data['gs23ProjectDefenseResult'] ?? '';
    $gs23IndependentStudyDoc = $data['gs23IndependentStudyDoc'] ?? '';
    $gs23IndependentStudyPDF = $data['gs23IndependentStudyPDF'] ?? '';
    $gs23IndependentStudyAdvisor = $data['gs23IndependentStudyAdvisor'] ?? '';
    $gs23ReportDate = $data['gs23ReportDate'] ?? '';
    $studentId = $data['studentId'] ?? '';
    $documentId = $data['documentId'] ?? '';
    // สร้าง SQL สำหรับการอัพเดตข้อมูล
    $sql = "UPDATE gs23report  
            SET projectThai_gs23report = ?,
                projectEng_gs23report = ?,
                projectDefenseDate_gs23report = ?,
                projectDefenseResult_gs23report = ?,
                IndependentStudyDoc_gs23report = ?,
                IndependentStudyPDF_gs23report = ?,
                IndependentStudyAdvisor_gs23report = ?,
                date_gs23report = ?
            WHERE id_gs23report = ? 
              AND idstd_student = ?";
    $params = [
        $gs23ProjectThai,
        $gs23ProjectEng,
        $gs23ProjectDefenseDate,
        $gs23ProjectDefenseResult,
        $gs23IndependentStudyDoc,
        $gs23IndependentStudyPDF,
        $gs23IndependentStudyAdvisor,
        $gs23ReportDate,
        $documentId,
        $studentId
    ];
}


    // เตรียมและรันคำสั่ง SQL สำหรับการอัพเดตข้อมูลหลัก
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Error in SQL query preparation: " . $conn->error]);
        exit;
    }

    // Bind parameters
    $types = str_repeat('s', count($params) - 2) . 'ii'; // สร้าง string สำหรับ bind_param ตามจำนวนตัวแปร
    $stmt->bind_param($types, ...$params);

    // Execute query
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "ไม่สามารถอัปเดตข้อมูลเอกสารได้: " . $stmt->error]);
    }

    // ปิดการเชื่อมต่อ
    $stmt->close();
    $conn->close();
}
?>