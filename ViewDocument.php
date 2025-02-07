<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('db_connection.php');

// Get the docId and docName from the POST request
$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Error decoding JSON: " . json_last_error_msg());
    echo json_encode(["status" => "error", "message" => "Error decoding JSON"]);
    exit();
}

$docId = isset($data['id']) ? $data['id'] : null;
$docName = isset($data['name']) ? $data['name'] : null;

if (!$docId || !$docName) {
    echo json_encode(["status" => "error", "message" => "Missing document ID or name."]);
    exit();
}

// Determine the appropriate table and query based on docName
$sql = "";
if (strpos($docName, 'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') !== false) {
    $sql = "
        SELECT 
            g.id_gs10report AS id,
            g.name_gs10report AS name,
            g.idstd_student AS idstd_student,
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.projectType_gs10report AS gs10projectType,
            g.projectThai_gs10report AS gs10ProjectThai,
            g.projectEng_gs10report AS gs10ProjectEng,
            g.advisorType_gs10report AS gs10advisorType,
            g.date_gs10report AS gs10ReportDate,
            g.advisorMainNew_gs10report AS gs10advisorMainNew,
            g.advisorSecondNew_gs10report AS gs10advisorSecondNew,
            g.advisorMainOld_gs10report AS gs10advisorMainOld,
            g.advisorSecondOld_gs10report AS gs10advisorSecondOld,
            g.document_gs10report AS gs10document,
            g.signature_gs10report AS gs10signature,
            g.signName_gs10report AS gs10signName,
            g.at_gs10report AS gs10timeSubmit,
            g.status_gs10report AS gs10status
        FROM gs10report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs10report = ? AND g.name_gs10report = ?";
} else if (strpos($docName, 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs11report AS id,
            g.name_gs11report AS name,
            g.idstd_student AS idstd_student,
            g.projectType_gs11report AS gs11projectType,
            g.projectThai_gs11report AS gs11ProjectThai,
            g.projectEng_gs11report AS gs11ProjectEng,
            g.advisorMain_gs11report AS gs11advisorMainNew,
            g.advisorSecond_gs11report AS gs11advisorSecondNew,
            g.subjects_gs11report AS gs11subjects,
            g.gpa_gs11report AS gs11gpa,
            g.subjectsProject_gs11report AS gs11subjectsProject,
            g.docGs10rp_gs11report AS gs11docGs10rp,
            g.docProjectdetails_gs11report AS gs11docProjectdetails,
            g.signature_gs11report AS gs11signature,
            g.signName_gs11report AS gs11signName,
            g.status_gs11report AS gs11status,
            g.date_gs11report AS gs11ReportDate,
            g.at_gs11report AS gs11timeSubmit
        FROM gs11report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs11report = ? AND g.name_gs11report = ?";
} else if (strpos($docName, 'คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') !== false) {
    $sql = "
        SELECT            
    s.name_student AS name_student,
    s.name_studentEng AS name_studentEng,
    s.prefix_studentEng AS prefix_studentEng,
    s.major_student AS major_student,
    s.branch_student AS branch_student,
    s.abbreviate_student AS abbreviate_student,
    s.address_student AS address_student,
    s.email_student AS email_student,
    s.tel_student AS tel_student,
    s.id_studyplan AS id_studyplan,
    sp.name_studyplan AS Namestudyplan,
    g.id_gs12report AS id,
    g.name_gs12report AS name,
    g.idstd_student AS idstd_student,
    g.projectType_gs12report AS gs12projectType,
    g.projectThai_gs12report AS gs12ProjectThai,
    g.projectEng_gs12report AS gs12ProjectEng,
    g.advisorMain_gs12report AS gs12advisorMainNew,
    g.advisorSecond_gs12report AS gs12advisorSecondNew,
    g.examRequestDate_gs12report AS gs12examRequestDate,
    g.examRequestTime_gs12report AS gs12examRequestTime,
    g.examRequestRoom_gs12report AS gs12examRequestRoom,
    g.examRequestFloor_gs12report AS gs12examRequestFloor,
    g.examRequestBuilding_gs12report AS gs12examRequestBuilding,
    g.docProjectdetailsGs20rp_gs12report AS gs12docProjectDetails,
    g.signature_gs12report AS gs12signature,
    g.signName_gs12report AS gs12signName,
    g.status_gs12report AS gs12status,
    g.date_gs12report AS gs12ReportDate,
    g.at_gs12report AS gs12timeSubmit,
    gf12.gradofficersignGs12_nameDocs AS gs12officeDocs,
    gf12.gradofficersignGs12_IdDocs AS gs12officeIdDocs,
    gf12.gradofficersignGs12_nameGradOffice AS gs12officenameGradOffice,
    gf12.gradofficersignGs12_description AS gs12officeDescription,
    gf12.gradofficersignGs12_projectApprovalDate AS gs12officeProjectApprovalDate,
    gf12.gradofficersignGs12_sign AS gs12officeSign

FROM gs12report g
LEFT JOIN student s ON g.idstd_student = s.idstd_student
LEFT JOIN gradofficersigngs12 gf12 ON g.id_gs12report = gf12.gradofficersignGs12_IdDocs
LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
WHERE g.id_gs12report = ? AND g.name_gs12report = ?;
";

} else if (strpos($docName, 'คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs13report AS id,
            g.name_gs13report AS name,
            g.idstd_student AS idstd_student,
            g.projectType_gs13report AS gs13projectType,
            g.projectThai_gs13report AS gs13ProjectThai,
            g.projectEng_gs13report AS gs13ProjectEng,
            g.advisorMain_gs13report AS gs13advisorMainNew,
            g.advisorSecond_gs13report AS gs13advisorSecondNew,
            g.revisionDateAdvisor_gs13report AS gs13revisionDateAdvisor,
            g.docProjectdetailsGs21rp_gs13report AS docProjectdetailsGs21rp,
            g.signature_gs13report AS gs13signature,
            g.signName_gs13report AS gs13signName,
            g.status_gs13report AS gs13status,
            g.date_gs13report AS gs13ReportDate,
            g.at_gs13report AS gs13timeSubmit,
            gf13.gradofficersignGs13_nameDocs AS gs13officeDocs,
            gf13.gradofficersignGs13_IdDocs AS gs13officeIdDocs,
            gf13.gradofficersignGs13_nameGradOffice AS gs13officenameGradOffice,
            gf13.gradofficersignGs13_description AS gs13officeDescription,
            gf13.gradofficersignGs13_projectApprovalDocument AS gs13officeProjectApprovalDocument,
            gf13.gradofficersignGs13_sign AS gs13officeSign
        FROM gs13report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN gradofficersigngs13 gf13 ON g.id_gs13report = gf13.gradofficersignGs13_IdDocs
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs13report = ? AND g.name_gs13report = ?";
} else if (strpos($docName, 'คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs14report AS id,
            g.name_gs14report AS name,
            g.idstd_student AS idstd_student,
            g.projectType_gs14report AS gs14projectType,
            g.projectThai_gs14report AS gs14ProjectThai,
            g.projectEng_gs14report AS gs14ProjectEng,
            g.advisorMain_gs14report AS gs14advisorMainNew,
            g.advisorSecond_gs14report AS gs14advisorSecondNew,
            g.projectApprovalDate_gs14report AS gs14projectApprovalDate,
            g.progressExamRequestDate_gs14report AS gs14progressExamRequestDate,
            g.progressExamRequestTime_gs14report AS gs14progressExamRequestTime,
            g.progressExamRequestRoom_gs14report AS gs14progressExamRequestRoom,
            g.progressExamRequestFloor_gs14report AS gs14progressExamRequestFloor,
            g.progressExamRequestBuilding_gs14report AS gs14progressExamRequestBuilding,
            g.docProjectdetailsGs22rp_gs14report AS gs22rpGs14report,      
            g.signature_gs14report AS gs14signature,
            g.signName_gs14report AS gs14signName,
            g.status_gs14report AS gs14status,
            g.date_gs14report AS gs14reportDate,
            g.at_gs14report AS gs14timeSubmit
        FROM gs14report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs14report = ? AND g.name_gs14report = ?";

} else if (strpos($docName, 'คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs15report AS id,
            g.name_gs15report AS name,
            g.idstd_student AS idstd_student,
            g.projectType_gs15report AS gs15projectType,
            g.projectThai_gs15report AS gs15ProjectThai,
            g.projectEng_gs15report AS gs15ProjectEng,
            g.advisorMain_gs15report AS gs15advisorMainNew,
            g.advisorSecond_gs15report AS gs15advisorSecondNew,
            g.projectApprovalDate_gs15report AS gs15projectApprovalDate,
            g.projectProgressDate_gs15report AS gs15projectProgressDate,
            g.defenseRequestDate_gs15report AS gs15defenseRequestDate,
            g.defenseRequestTime_gs15report AS gs15defenseRequestTime,
            g.defenseRequestRoom_gs15report AS gs15defenseRequestRoom,
            g.defenseRequestFloor_gs15report AS gs15defenseRequestFloor,
            g.defenseRequestBuilding_gs15report AS gs15defenseRequestBuilding,
            g.courseCredits_gs15report AS gs15courseCredits,
            g.cumulativeGPA_gs15report AS gs15cumulativeGPA,
            g.thesisCredits_gs15report AS gs15thesisCredits,
            g.thesisDefenseDoc_gs15report AS gs15thesisDefenseDoc,
            g.docGs40rpGs41rp_gs15report AS gs15docGs40rpGs41rp,
            g.docGs50rp_gs15report AS gs15docGs50rp,
            g.docThesisExamCopy_gs15report AS gs15docThesisExamCopy,
            g.signature_gs15report AS gs15signature,
            g.signName_gs15report AS gs15signName,
            g.status_gs15report AS gs15status,
            g.date_gs15report AS gs15ReportDate,
            g.at_gs15report AS gs15timeSubmit,
            cc15.ccurrsignags15_nameChairpersonCurriculum AS gs15ccnameChairpersonCurriculum,
            cc15.ccurrsignags15_description AS gs15ccdescription,
            cc15.ccurrsignags15_sign AS gs15ccsign,
            cc15.ccurrsignags15_examChair AS gs15ccexamChair,
            cc15.ccurrsignags15_examChairPosition AS gs15ccexamChairPosition,
            cc15.ccurrsignags15_examChairWorkplace AS gs15ccexamChairWorkplace,
            cc15.ccurrsignags15_examChairTel AS gs15ccexamChairTel,
            cc15.ccurrsignags15_examAdvisorMain AS gs15ccexamAdvisorMain,
            cc15.ccurrsignags15_examAdvisorMainPosition AS gs15ccexamAdvisorMainPosition,
            cc15.ccurrsignags15_examAdvisorMainWorkplace AS gs15ccexamAdvisorMainWorkplace,
            cc15.ccurrsignags15_examAdvisorMainTel AS gs15ccexamAdvisorMainTel,
            cc15.ccurrsignags15_examAdvisorSecond AS gs15ccexamAdvisorSecond,
            cc15.ccurrsignags15_examAdvisorSecondPosition AS gs15ccexamAdvisorSecondPosition,
            cc15.ccurrsignags15_examAdvisorSecondWorkplace AS gs15ccexamAdvisorSecondWorkplace,
            cc15.ccurrsignags15_examAdvisorSecondTel AS gs15ccexamAdvisorSecondTel,
            cc15.ccurrsignags15_examCurriculum AS gs15ccexamCurriculum,
            cc15.ccurrsignags15_examCurriculumPosition AS gs15ccexamCurriculumPosition,
            cc15.ccurrsignags15_examCurriculumWorkplace AS gs15ccexamCurriculumWorkplace,
            cc15.ccurrsignags15_examCurriculumTel AS gs15ccexamCurriculumTel
        FROM gs15report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN ccurrsignags15 cc15 ON g.id_gs15report = cc15.ccurrsignags15_IdDocs
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs15report = ? AND g.name_gs15report = ?";
} else if (strpos($docName, 'คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs16report AS id,
            g.name_gs16report AS name,
            g.idstd_student AS idstd_student,
            g.projectType_gs16report AS gs16projectType,
            g.projectThai_gs16report AS gs16ProjectThai,
            g.projectEng_gs16report AS gs16ProjectEng,
            g.projectDefenseDate_gs16report AS gs16ProjectDefenseDate,
            g.projectDefenseResult_gs16report AS gs16ProjectDefenseResult,
            g.thesisAdvisor_gs16report AS gs16ThesisAdvisor,
            g.thesisDoc_gs16report AS gs16ThesisDoc,
            g.thesisPDF_gs16report AS gs16ThesisPDF,
            g.signature_gs16report AS gs16signature,
            g.signName_gs16report AS gs16signName,
            g.status_gs16report AS gs16status,
            g.date_gs16report AS gs16ReportDate,
            g.at_gs16report AS gs16timeSubmit,
            gf16.gradofficersignGs16_nameGradOffice AS gf16officeNameGradOffice,
            gf16.gradofficersignGs16_description AS gf16officeDescription,
            gf16.gradofficersignGs16_sign AS gf16officeSign,
            gf16.gradofficersignGs16_ThesisCertificateDoc AS gf16officeThesisCertificateDoc,
            gf16.gradofficersignGs16_GraduationApprovalReport AS gf16officeGraduationApprovalReport
        FROM gs16report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN gradofficersigngs16 gf16 ON g.id_gs16report = gf16.gradofficersignGs16_IdDocs
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs16report = ? AND g.name_gs16report = ?";
}else if (strpos($docName, 'คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs17report AS id,
            g.name_gs17report AS name,
            g.idstd_student AS idstd_student,
            g.thesisAdvisor_gs17report AS gs17ThesisAdvisor,
            g.semesterAt_gs17report AS gs17SemesterAt,
            g.academicYear_gs17report AS gs17AcademicYear,
            g.courseCredits_gs17report AS gs17CourseCredits,
            g.cumulativeGPA_gs17report AS gs17CumulativeGPA,
            g.projectDefenseDate_gs17report AS gs17ProjectDefenseDate,
            g.additionalDetails_gs17report AS gs17AdditionalDetails,
            g.docCheck15_gs17report AS gs17DocCheck15,
            g.signature_gs17report AS gs17signature,
            g.signName_gs17report AS gs17signName,
            g.status_gs17report AS gs17status,
            g.date_gs17report AS gs17ReportDate,
            g.at_gs17report AS gs17timeSubmit,
            gf17.gradofficersignGs17_nameGradOffice AS gf17officeNameGradOffice,
            gf17.gradofficersignGs17_description AS gf17officeDescription,
            gf17.gradofficersignGs17_sign AS gf17officeSign,
            gf17.gradofficersignGs17_masterPlanOneApprovalDoc AS gf17officeMasterPlanOneApprovalDoc
        FROM gs17report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN gradofficersigngs17 gf17 ON g.id_gs17report = gf17.gradofficersignGs17_IdDocs
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs17report = ? AND g.name_gs17report = ?";
}else if (strpos($docName, 'คคอ. บว. 18 แบบขอสอบประมวลความรู้') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs18report AS id,
            g.name_gs18report AS name,
            g.idstd_student AS idstd_student,
            g.thesisAdvisor_gs18report AS gs18ThesisAdvisor,
            g.semesterAt_gs18report AS gs18SemesterAt,
            g.academicYear_gs18report AS gs18AcademicYear,
            g.examRoundProject_gs18report AS gs18ExamRoundProject,
            g.courseCredits_gs18report AS gs18CourseCredits,
            g.cumulativeGPA_gs18report AS gs18CumulativeGPA,
            g.docGs41rp_gs18report AS gs18DocGs41rp,
            g.signature_gs18report AS gs18signature,
            g.signName_gs18report AS gs18signName,
            g.status_gs18report AS gs18status,
            g.date_gs18report AS gs18ReportDate,
            g.at_gs18report AS gs18timeSubmit
        FROM gs18report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs18report = ? AND g.name_gs18report = ?";
}else if (strpos($docName, 'คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs19report AS id,
            g.name_gs19report AS name,
            g.idstd_student AS idstd_student,
            g.thesisAdvisor_gs19report AS gs19ThesisAdvisor,
            g.semesterAt_gs19report AS gs19SemesterAt,
            g.academicYear_gs19report AS gs19AcademicYear,
            g.courseCredits_gs19report AS gs19CourseCredits,
            g.cumulativeGPA_gs19report AS gs19CumulativeGPA,
            g.projectKnowledgeExamDate_gs19report AS gs19ProjectKnowledgeExamDate,
            g.projectDefenseDate_gs19report AS gs19ProjectDefenseDate,
            g.additionalDetails_gs19report AS gs19AdditionalDetails,
            g.signature_gs19report AS gs19signature,
            g.signName_gs19report AS gs19signName,
            g.status_gs19report AS gs19status,
            g.date_gs19report AS gs19ReportDate,
            g.at_gs19report AS gs19timeSubmit,
            gf19.gradofficersignGs19_nameGradOffice AS gf19officeNameGradOffice,
            gf19.gradofficersignGs19_description AS gf19officeDescription,
            gf19.gradofficersignGs19_sign AS gf19officeSign,
            gf19.gradofficersignGs19_masterPlanTwoApprovalDoc AS gf19officeMasterPlanTwoApprovalDoc
        FROM gs19report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN gradofficersigngs19 gf19 ON g.id_gs19report = gf19.gradofficersignGs19_IdDocs
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs19report = ? AND g.name_gs19report = ?";
}else if (strpos($docName, 'คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์') !== false) {
    $sql = "
        SELECT            
            s.name_student AS name_student,
            s.name_studentEng AS name_studentEng,
            s.prefix_studentEng AS prefix_studentEng,
            s.major_student AS major_student,
	        s.branch_student AS branch_student,
	        s.abbreviate_student AS abbreviate_student,
            s.address_student AS address_student,
	        s.email_student AS email_student,
	        s.tel_student AS tel_student,
            s.id_studyplan AS id_studyplan,
            sp.name_studyplan AS Namestudyplan,
            g.id_gs23report AS id,
            g.name_gs23report AS name,
            g.idstd_student AS idstd_student,
            g.projectType_gs23report AS gs23ProjectType,
            g.projectThai_gs23report AS gs23ProjectThai,
            g.projectEng_gs23report AS gs23ProjectEng,
            g.projectDefenseDate_gs23report AS gs23ProjectDefenseDate,
            g.projectDefenseResult_gs23report AS gs23ProjectDefenseResult,
            g.IndependentStudyDoc_gs23report AS gs23IndependentStudyDoc,
            g.IndependentStudyPDF_gs23report AS gs23IndependentStudyPDF,
            g.IndependentStudyAdvisor_gs23report AS gs23IndependentStudyAdvisor,
            g.signature_gs23report AS gs23signature,
            g.signName_gs23report AS gs23signName,
            g.status_gs23report AS gs23status,
            g.date_gs23report AS gs23ReportDate,
            g.at_gs23report AS gs23timeSubmit,
            gf23.gradofficersignGs23_nameGradOffice AS gf23officeNameGradOffice,
            gf23.gradofficersignGs23_description AS gf23officeDescription,
            gf23.gradofficersignGs23_sign AS gf23officeSign,
            gf23.gradofficersigngs23_ThesisDocDate AS gf23officeThesisDocDate,
            gf23.gradofficersignGs23_cumulativeGPAStudent AS gf23officeCumulativeGPAStudent,
            gf23.gradofficersignGs23_knowledgeExamPass AS gf23officeKnowledgeExamPass,
            gf23.gradofficersignGs23_status AS gf23officeStatus
        FROM gs23report g
        LEFT JOIN student s ON g.idstd_student = s.idstd_student
        LEFT JOIN gradofficersigngs23 gf23 ON g.id_gs23report = gf23.gradofficersignGs23_IdDocs
        LEFT JOIN studyplan sp ON s.id_studyplan  = sp.id_studyplan 
        WHERE g.id_gs23report = ? AND g.name_gs23report = ?";
} else {
    echo json_encode(["status" => "error", "message" => "Unknown document type."]);
    exit();
}

// Prepare and execute SQL statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Failed to prepare SQL statement."]);
    exit();
}

$stmt->bind_param('is', $docId, $docName);
if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => "Failed to execute SQL statement. Error: " . $stmt->error]);
    exit();
}

$result = $stmt->get_result();
$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

if (count($reports) === 0) {
    echo json_encode(["status" => "error", "message" => "No document found."]);
    exit();
}

// Return the fetched reports
echo json_encode([
    "status" => "success",
    "reports" => $reports
]);

$stmt->close();
$conn->close();
?>