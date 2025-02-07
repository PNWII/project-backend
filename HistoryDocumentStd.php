<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

include('db_connection.php');

// ฟังก์ชันสำหรับเชื่อมต่อกับฐานข้อมูล
function connectDB()
{
    include('db_connection.php');
    return $conn;
}

// ฟังก์ชันดึงข้อมูลเอกสารของนักเรียน
function getDocumentsByStudentId($idstd_student)
{
    $conn = connectDB();

    $stmt = $conn->prepare("
        SELECT 
            f.formsubmit_id AS documentId, 
            f.formsubmit_type AS documentType,
            f.idstd_student AS studentId,
            f.formsubmit_dataform AS dataFormId,
            f.formsubmit_status AS documentStatus,
            f.formsubmit_at AS submissionDate,
            s.name_student AS studentName,
            gs10.id_gs10report AS gs10ReportId,
            gs10.name_gs10report AS gs10ReportName,
            gs10.projectType_gs10report AS gs10projectType,
            gs10.projectThai_gs10report AS gs10ProjectThai,
            gs10.projectEng_gs10report AS gs10ProjectEng,
            gs10.advisorType_gs10report AS gs10advisorType,
            gs10.date_gs10report AS gs10ReportDate,
            gs10.advisorMainNew_gs10report AS gs10advisorMainNew,
            gs10.advisorSecondNew_gs10report AS gs10advisorSecondNew,
            gs10.advisorMainOld_gs10report AS gs10advisorMainOld,
            gs10.advisorSecondOld_gs10report AS gs10advisorSecondOld,
            gs10.document_gs10report AS gs10document,
            gs10.at_gs10report AS gs10At,
            gs11.id_gs11report AS gs11ReportId,
            gs11.name_gs11report AS gs11ReportName,
            gs11.projectType_gs11report AS gs11ProjectType,
            gs11.projectThai_gs11report AS gs11ProjectThai,
            gs11.projectEng_gs11report AS gs11ProjectEng,
            gs11.advisorMain_gs11report AS gs11advisorMain,
            gs11.advisorSecond_gs11report AS gs11advisorSecond,
            gs11.subjects_gs11report AS gs11subjects,
            gs11.gpa_gs11report AS gs11gpa,
            gs11.subjectsProject_gs11report AS gs11subjectsProject,
            gs11.docGs10rp_gs11report AS gs11docGs10rp,
            gs11.docProjectdetails_gs11report AS gs11docProjectdetails,
            gs11.at_gs11report AS gs11At,
            gs11.date_gs11report AS gs11ReportDate,
            gs12.date_gs12report AS gs12ReportDate,
            gs12.id_gs12report AS gs12ReportId,
            gs12.name_gs12report AS gs12ReportName,
            gs12.projectType_gs12report AS gs12ProjectType,
            gs12.projectThai_gs12report AS gs12ProjectThai,
            gs12.projectEng_gs12report AS gs12ProjectEng,
            gs12.advisorMain_gs12report AS gs12advisorMain,
            gs12.advisorSecond_gs12report AS gs12advisorSecond,
            gs12.examRequestDate_gs12report AS gs12examRequestDate,
            gs12.examRequestTime_gs12report AS gs12examRequestTime,
            gs12.examRequestRoom_gs12report AS gs12examRequestRoom,
            gs12.examRequestFloor_gs12report AS gs12examRequestFloor,
            gs12.examRequestBuilding_gs12report AS gs12examRequestBuilding,
            gs12.docProjectdetailsGs20rp_gs12report AS gs12docProjectdetailsGs20rp,
            gs13.id_gs13report AS gs13reportId,
            gs13.name_gs13report AS gs13reportName,
            gs13.projectType_gs13report AS gs13ProjectThai,
            gs13.projectThai_gs13report AS gs13ProjectThai,
            gs13.projectEng_gs13report AS gs13ProjectEng,
            gs13.advisorMain_gs13report AS gs13advisorMain,
            gs13.advisorSecond_gs13report AS gs13advisorSecond,
            gs13.revisionDateAdvisor_gs13report AS gs13revisionDateAdvisor,
            gs13.docProjectdetailsGs21rp_gs13report AS gs13docProjectdetailsGs21rp,
            gs13.date_gs13report AS gs13ReportDate,
            gs14.id_gs14report AS gs14reportId,
            gs14.name_gs14report AS gs14reportName,
            gs14.projectType_gs14report AS gs14ProjectType,
            gs14.projectThai_gs14report AS gs14ProjectThai,
            gs14.projectEng_gs14report AS gs14ProjectEng,
            gs14.advisorMain_gs14report AS gs14advisorMain,
            gs14.advisorSecond_gs14report AS gs14advisorSecond,
            gs14.projectApprovalDate_gs14report AS gs14projectApprovalDate,
            gs14.progressExamRequestDate_gs14report AS gs14progressExamRequestDate,
            gs14.progressExamRequestTime_gs14report AS gs14progressExamRequestTime,
            gs14.progressExamRequestRoom_gs14report AS gs14progressExamRequestRoom,
            gs14.progressExamRequestFloor_gs14report AS gs14progressExamRequestFloor,
            gs14.progressExamRequestBuilding_gs14report AS gs14progressExamRequestBuilding,
            gs14.docProjectdetailsGs22rp_gs14report AS gs14docProjectdetailsGs22rp,
            gs14.date_gs14report AS gs14ReportDate,
            gs15.id_gs15report AS gs15reportId,
            gs15.name_gs15report AS gs15reportName,
            gs15.projectType_gs15report AS gs15ProjectType,
            gs15.projectThai_gs15report AS gs15ProjectThai,
            gs15.projectEng_gs15report AS gs15ProjectEng,
            gs15.advisorMain_gs15report AS gs15advisorMain,
            gs15.advisorSecond_gs15report AS gs15advisorSecond,
            gs15.projectApprovalDate_gs15report AS gs15projectApprovalDate,
            gs15.projectProgressDate_gs15report AS gs15projectProgressDate,
            gs15.defenseRequestDate_gs15report AS gs15defenseRequestDate,
            gs15.defenseRequestTime_gs15report AS gs15defenseRequestTime,
            gs15.defenseRequestRoom_gs15report AS gs15defenseRequestRoom,
            gs15.defenseRequestFloor_gs15report AS gs15defenseRequestFloor,
            gs15.defenseRequestBuilding_gs15report AS gs15defenseRequestBuilding,
            gs15.courseCredits_gs15report AS gs15courseCredits,
            gs15.cumulativeGPA_gs15report AS gs15cumulativeGPA,
            gs15.thesisCredits_gs15report AS gs15thesisCredits,
            gs15.thesisDefenseDoc_gs15report AS gs15thesisDefenseDoc,
            gs15.docGs40rpGs41rp_gs15report AS gs15docGs40rpGs41rp,
            gs15.docGs50rp_gs15report AS gs15docGs50rp,
            gs15.docThesisExamCopy_gs15report AS gs15docThesisExamCopy,
            gs15.date_gs15report AS gs15ReportDate,
            gs16.id_gs16report AS gs16reportId,
            gs16.name_gs16report AS gs16reportName,
            gs16.projectType_gs16report AS gs16ProjectType,
            gs16.projectThai_gs16report AS gs16ProjectThai,
            gs16.projectEng_gs16report AS gs16ProjectEng,
            gs16.projectDefenseDate_gs16report AS gs16ProjectDefenseDate,
            gs16.projectDefenseResult_gs16report AS gs16ProjectDefenseResult,
            gs16.thesisAdvisor_gs16report AS gs16ThesisAdvisor,
            gs16.thesisDoc_gs16report AS gs16ThesisDoc,
            gs16.thesisPDF_gs16report AS gs16ThesisPDF,
            gs16.date_gs16report AS gs16ReportDate,
            gs17.id_gs17report AS gs17reportId,
            gs17.name_gs17report AS gs17reportName,
            gs17.thesisAdvisor_gs17report AS gs17ThesisAdvisor,
            gs17.semesterAt_gs17report AS gs17SemesterAt,
            gs17.academicYear_gs17report AS gs17AcademicYear,
            gs17.courseCredits_gs17report AS gs17CourseCredits,
            gs17.cumulativeGPA_gs17report AS gs17CumulativeGPA,
            gs17.projectDefenseDate_gs17report AS gs17ProjectDefenseDate,
            gs17.additionalDetails_gs17report AS gs17AdditionalDetails,
            gs17.docCheck15_gs17report AS gs17DocCheck15,
            gs17.date_gs17report AS gs17ReportDate,
            gs18.id_gs18report AS gs18reportId,
            gs18.name_gs18report AS gs18reportName,
            gs18.thesisAdvisor_gs18report AS gs18ThesisAdvisor,
            gs18.semesterAt_gs18report AS gs18SemesterAt,
            gs18.academicYear_gs18report AS gs18AcademicYear,
            gs18.examRoundProject_gs18report AS gs18ExamRoundProject,
            gs18.courseCredits_gs18report AS gs18CourseCredits,
            gs18.cumulativeGPA_gs18report AS gs18CumulativeGPA,
            gs18.docGs41rp_gs18report AS gs18DocGs41rp,
            gs18.date_gs18report AS gs18ReportDate,
            gs19.id_gs19report AS gs19reportId,
            gs19.name_gs19report AS gs19reportName,
            gs19.thesisAdvisor_gs19report AS gs19ThesisAdvisor,
            gs19.semesterAt_gs19report AS gs19SemesterAt,
            gs19.academicYear_gs19report AS gs19AcademicYear,
            gs19.courseCredits_gs19report AS gs19CourseCredits,
            gs19.cumulativeGPA_gs19report AS gs19CumulativeGPA,
            gs19.projectKnowledgeExamDate_gs19report AS gs19ProjectKnowledgeExamDate,
            gs19.projectDefenseDate_gs19report AS gs19ProjectDefenseDate,
            gs19.additionalDetails_gs19report AS gs19AdditionalDetails,
            gs19.date_gs19report AS gs19ReportDate,
            gs23.id_gs23report AS gs23reportId,
            gs23.name_gs23report AS gs23reportName,
            gs23.projectThai_gs23report AS gs23ProjectThai,
            gs23.projectEng_gs23report AS gs23ProjectEng,
            gs23.projectDefenseDate_gs23report AS gs23ProjectDefenseDate,
            gs23.projectDefenseResult_gs23report AS gs23ProjectDefenseResult,
            gs23.IndependentStudyDoc_gs23report AS gs23IndependentStudyDoc,
            gs23.IndependentStudyPDF_gs23report AS gs23IndependentStudyPDF,
            gs23.IndependentStudyAdvisor_gs23report AS gs23IndependentStudyAdvisor,
            gs23.date_gs23report AS gs23ReportDate,
            ts.teachersign_description AS teacherSignDescription,
            ts.teachersign_nameTeacher AS teacherSignName,
            ts.teachersign_status AS teacherSignStatus,
            ts.teachersign_IdDocs AS teacherSignId,
            cs.ccurrsigna_description AS currSignDescription,
            cs.ccurrsigna_status AS ccurrsignaStatus,
            cs.ccurrsigna_nameChairpersonCurriculum AS currSignName,
            cs15.ccurrsignags15_description AS currSignGs15Description,
            cs15.ccurrsigna15_status AS currSignGs15Status,
            cs15.ccurrsignags15_nameChairpersonCurriculum AS currSignGs15Name,
            gf.gradofficersign_description AS gradOfficerSignDescription,
            gf.gradofficersign_nameGradofficer AS gradOfficerSignName,
            gf.gradofficersign_status AS gradOfficerSignStatus,
            gf10.gradofficersignGs10_nameGradOffice AS gradOfficerSignGs10Name,
            gf10.gradofficersignGs10_status AS gradOfficerSignGs10Status,
            gf10.gradofficersignGs10_description AS gradOfficerSignGs10Description,
            gf12.gradofficersignGs12_nameGradOffice AS gradOfficerSignGs12Name,
            gf12.gradofficersignGs12_status AS gradOfficerSignGs12Status,
            gf12.gradofficersignGs12_description AS gradOfficerSignGs12Description,
            gf13.gradofficersignGs13_nameGradOffice AS gradOfficerSignGs13Name,
            gf13.gradofficersignGs13_status AS gradOfficerSignGs13Status,
            gf13.gradofficersignGs13_description AS gradOfficerSignGs13Description,
            gf16.gradofficersignGs16_nameGradOffice AS gradOfficerSignGs16Name,
            gf16.gradofficersignGs16_status AS gradOfficerSignGs16Status,
            gf16.gradofficersignGs16_description AS gradOfficerSignGs16Description,
            gf17.gradofficersignGs17_nameGradOffice AS gradOfficerSignGs17Name,
            gf17.gradofficersignGs17_status AS gradOfficerSignGs17Status,
            gf17.gradofficersignGs17_description AS gradOfficerSignGs17Description,
            gf19.gradofficersignGs19_nameGradOffice AS gradOfficerSignGs19Name,
            gf19.gradofficersignGs19_status AS gradOfficerSignGs19Status,
            gf19.gradofficersignGs19_description AS gradOfficerSignGs19Description,
            gf23.gradofficersignGs23_nameGradOffice AS gradOfficerSignGs23Name,
            gf23.gradofficersignGs23_status AS gradOfficerSignGs23Status,
            gf23.gradofficersignGs23_description AS gradOfficerSignGs23Description,
            v.vdAcrsign_nameViceDeanAcademicResearch AS vdAcrsignName,
            v.vdAcrsign_status AS vdAcrsignStatus,
            v.vdAcrsign_description AS vdAcrsignDescription,
            d.deanfiesign_nameDeanIndEdu AS deanfiesignName,
            d.deanfiesign_status AS deanfiesignStatus,
            d.deanfiesign_description AS deanfiesignDescription
        FROM 
            formsubmit f
        LEFT JOIN 
            gs10report gs10 ON f.formsubmit_dataform = gs10.id_gs10report 
            AND f.formsubmit_type = 'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 
            gs11report gs11 ON f.formsubmit_dataform = gs11.id_gs11report 
            AND f.formsubmit_type = 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
         LEFT JOIN 
            gs12report gs12 ON f.formsubmit_dataform = gs12.id_gs12report 
            AND f.formsubmit_type = 'คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 
            gs13report gs13 ON f.formsubmit_dataform = gs13.id_gs13report 
            AND f.formsubmit_type = 'คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข'
        LEFT JOIN 
            gs14report gs14 ON f.formsubmit_dataform = gs14.id_gs14report 
            AND f.formsubmit_type = 'คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 
            gs15report gs15 ON f.formsubmit_dataform = gs15.id_gs15report 
            AND f.formsubmit_type = 'คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ'
        LEFT JOIN 
            gs16report gs16 ON f.formsubmit_dataform = gs16.id_gs16report 
            AND f.formsubmit_type = 'คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์'
        LEFT JOIN 
            gs17report gs17 ON f.formsubmit_dataform = gs17.id_gs17report 
            AND f.formsubmit_type = 'คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ'
        LEFT JOIN 
            gs18report gs18 ON f.formsubmit_dataform = gs18.id_gs18report 
            AND f.formsubmit_type = 'คคอ. บว. 18 แบบขอสอบประมวลความรู้'
        LEFT JOIN 
            gs19report gs19 ON f.formsubmit_dataform = gs19.id_gs19report 
            AND f.formsubmit_type = 'คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ'
        LEFT JOIN 
            gs23report gs23 ON f.formsubmit_dataform = gs23.id_gs23report 
            AND f.formsubmit_type = 'คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์'
        LEFT JOIN 
            student s ON f.idstd_student = s.idstd_student
        LEFT JOIN 
            teachersigna ts ON f.formsubmit_dataform = ts.teachersign_IdDocs
        LEFT JOIN 
            ccurrsigna cs ON f.formsubmit_dataform = cs.ccurrsigna_IdDocs 
        LEFT JOIN 
            ccurrsignags15 cs15 ON f.formsubmit_dataform = cs15.ccurrsignags15_IdDocs
        LEFT JOIN 
            gradofficersign gf ON f.formsubmit_dataform = gf.gradofficersign_IdDocs
        LEFT JOIN 
            gradofficersigngs10 gf10 ON f.formsubmit_dataform = gf10.gradofficersignGs10_IdDocs	
        LEFT JOIN 
            gradofficersigngs12 gf12 ON f.formsubmit_dataform = gf12.gradofficersignGs12_IdDocs
        LEFT JOIN 
            gradofficersigngs13 gf13 ON f.formsubmit_dataform = gf13.gradofficersignGs13_IdDocs
        LEFT JOIN 
            gradofficersigngs16 gf16 ON f.formsubmit_dataform = gf16.gradofficersignGs16_IdDocs
        LEFT JOIN 
            gradofficersigngs17 gf17 ON f.formsubmit_dataform = gf17.gradofficersignGs17_IdDocs
        LEFT JOIN 
            gradofficersigngs19 gf19 ON f.formsubmit_dataform = gf19.gradofficersignGs19_IdDocs
        LEFT JOIN 
            gradofficersigngs23 gf23 ON f.formsubmit_dataform = gf23.gradofficersignGs23_IdDocs
        LEFT JOIN 
            vdacrsign v ON f.formsubmit_dataform = v.vdAcrsign_IdDocs
        LEFT JOIN 
            deanfiesign d ON f.formsubmit_dataform = d.deanfiesign_IdDocs
        WHERE
            f.idstd_student = ?
    ");

    // Bind idstd_student
    $stmt->bind_param("s", $idstd_student);

    // Execute SQL
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    // Fetch results
    $result = $stmt->get_result();
    $documents = [];

    // Iterate through the result set and collect documents
    while ($row = $result->fetch_assoc()) {
        // ตรวจสอบสถานะเอกสารและการปฏิเสธ
        if ($row['documentStatus'] === "ถูกปฏิเสธจากอาจารย์ที่ปรึกษา") {
            $row['rejectionDescription'] = $row['teacherSignDescription'];
            $row['rejectionDescriptionName'] = $row['teacherSignName'];
        } else {
            $row['rejectionDescription'] = null;
            $row['rejectionDescriptionName'] = null;
        }

        // Store document by documentId to avoid duplicates
        $documents[$row['dataFormId']] = $row;
    }

    // Convert the associative array to an indexed array (removes duplicates)
    $documents = array_values($documents);

    return $documents;
}

// รับค่า idstd_student จาก URL
$idstd_student = isset($_GET['idstd_student']) ? $_GET['idstd_student'] : '';

// Validate input
if (empty($idstd_student)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing idstd_student parameter."]);
    exit;
}

try {
    // เรียกใช้ฟังก์ชันเพื่อดึงข้อมูลเอกสาร
    $documents = getDocumentsByStudentId($idstd_student);

    // Return response
    if (count($documents) > 0) {
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $documents]);
    } else {
        http_response_code(404);
        echo json_encode([
            "status" => "success",
            "message" => "No documents found for the provided student ID.",
            "data" => []
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
}

// Close the database connection
$conn->close();
