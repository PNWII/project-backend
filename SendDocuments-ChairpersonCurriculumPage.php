<?php
// ไฟล์ backend/SendDocuments-ChairpersonCurriculumPage.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include 'db_connection.php';

// เปิดการแสดงข้อผิดพลาด
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // เรียกข้อมูลจาก fetch_documentFormSubmit.php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/TestPHP-API2/backend/fetch_documentFormSubmit.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);  // เก็บ header ด้วย
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode != 200) {
        throw new Exception("Failed to fetch data from fetch_documentFormSubmit.php. HTTP Code: $httpCode");
    }

    curl_close($ch);

    // ตัด header ออกและเก็บเฉพาะ body
    $responseBody = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));

    // ตรวจสอบว่าเป็น JSON หรือไม่
    $formSubmitData = json_decode($responseBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON response: " . json_last_error_msg());
    }

    if (!is_array($formSubmitData)) {
        throw new Exception("Invalid or empty data from fetch_documentFormSubmit.php");
    }

    // ดึงข้อมูล statusMapping จากฐานข้อมูล
    $statusMapping = [];
    $statusMappingQuery = "SELECT teachersign_IdDocs, teachersign_status, teachersign_nameDocs,teachersign_at FROM teachersigna";
    $result = $conn->query($statusMappingQuery);

    if (!$result) {
        throw new Exception("Error fetching data from teachersigna: " . $conn->error);
    }


    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusMapping[$row['teachersign_IdDocs']][] = [
                'status' => $row['teachersign_status'],
                'id' => $row['teachersign_IdDocs'],
                'docName' => $row['teachersign_nameDocs'],
                'timeSubmit' => $row['teachersign_at']
            ];
        }
    }

    // เริ่มประมวลผลข้อมูล
    $documentsToSend = [];
    foreach ($formSubmitData as $form) { // แก้ไขบรรทัดนี้จาก 'as$statusMapping' เป็น 'as $form'
        $formId = $form['formsubmit_id'];
        $formType = $form['formsubmit_type'];
        $formDataForm = $form['formsubmit_dataform'];
        $formStatus = $form['formsubmit_status'];

        if (
            isset($statusMapping[$formDataForm]) &&
            is_array($statusMapping[$formDataForm])


        ) {
            $gs10Approved = 0;
            $gs11Approved = 0;
            $gs12Approved = 0;
            $gs13Approved = 0;
            $gs14Approved = 0;
            $gs15Approved = 0;
            $gs16Approved = 0;
            $gs17Approved = 0;
            $gs18Approved = 0;
            $gs19Approved = 0;
            $gs23Approved = 0;

            foreach ($statusMapping[$formDataForm] as $data) {

                if ($data['status'] === 'ได้รับการอนุมัติจากครูอาจารย์ที่ปรึกษาแล้ว') {
                    if ($data['docName'] === 'คคอ. บว. 10 แบบขออนุมัติแต่งตั้งอาจารย์ที่ปรึกษาวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
                        $gs10Approved++;
                    } elseif ($data['docName'] === 'คคอ. บว. 11 แบบขอเสนอโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
                        $gs11Approved++;
                    } elseif ($data['docName'] === 'คคอ. บว. 12 แบบขอสอบหัวข้อวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
                        $gs12Approved++;
                    } elseif ($data['docName'] === 'คคอ. บว. 13 แบบขอส่งโครงการวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ ฉบับแก้ไข') {
                        $gs13Approved++;
                    } elseif ($data['docName'] === 'คคอ. บว. 14 แบบขอสอบความก้าวหน้าวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
                        $gs14Approved++;
                    } elseif ($data['docName'] === 'คคอ. บว. 15 คำร้องขอสอบป้องกันวิทยานิพนธ์/การศึกษาค้นคว้าอิสระ') {
                        $gs15Approved++;
                    }elseif ($data['docName'] === 'คคอ. บว. 16 แบบขอส่งเล่มวิทยานิพนธ์ฉบับสมบูรณ์') {
                        $gs16Approved++;
                    }elseif ($data['docName'] === 'คคอ. บว. 17 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 1 แบบวิชาการ') {
                        $gs17Approved++;
                    }elseif ($data['docName'] === 'คคอ. บว. 18 แบบขอสอบประมวลความรู้') {
                        $gs18Approved++;
                    }elseif ($data['docName'] === 'คคอ. บว. 19 แบบขออนุมัติผลการสำเร็จการศึกษา นักศึกษาระดับปริญญาโท แผน 2 แบบวิชาชีพ') {
                        $gs19Approved++;
                    }elseif ($data['docName'] === 'คคอ. บว. 23 แบบขอส่งเล่มการศึกษาค้นคว้าอิสระฉบับสมบูรณ์') {
                        $gs23Approved++;
                    }
                }
            }

            // ดึงสถานะการอนุมัติจาก ccurrsigna
            $statusSql = "
SELECT ccurrsigna_nameChairpersonCurriculum, ccurrsigna_status
FROM ccurrsigna
WHERE ccurrsigna_IdDocs = ?";
            $statusStmt = $conn->prepare($statusSql);
            if (!$statusStmt) {
                throw new Exception("Error preparing the ccurrsigna query: " . $conn->error);
            }

            $statusStmt->bind_param("s", $formDataForm);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();

            $ccurrsignaStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
            while ($statusRow = $statusResult->fetch_assoc()) {
                $ccurrsignaStatus = $statusRow['ccurrsigna_status'];
            }

            // ประมวลผลผลลัพธ์จาก reports และเพิ่มสถานะการอนุมัติ
            $status = ($ccurrsignaStatus !== '') ? $ccurrsignaStatus : 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';

            // ตรวจสอบสถานะ ccurrsigna และอัปเดตข้อความใน formsubmitStatus
            if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว') {
                $formsubmitStatus = 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตร กำลังรอการพิจารณาจากเจ้าหน้าที่บัณฑิตศึกษา';
                $formsubmitStatusDoc = 'ได้รับการอนุมัติจากประธานคณะกรรมการบริหารหลักสูตรแล้ว';

            } else {
                $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                $formsubmitStatusDoc = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา';
            }

            if ($gs10Approved === 4) {
                if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                    // อัปเดตสถานะ
                    $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                    // อัปเดตสถานะใน formsubmit
                    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                    if (!$stmtUpdateFormsubmit) {
                        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                    }

                    // อัปเดตสถานะใน Gs10report
                    $sqlUpdateGs10report = "UPDATE Gs10report SET status_gs10report = ? WHERE id_gs10report = ? AND name_gs10report = ?";
                    $stmtUpdateGs10report = $conn->prepare($sqlUpdateGs10report);
                    if (!$stmtUpdateGs10report) {
                        throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                    }

                    // Bind Parameters
                    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                    $stmtUpdateGs10report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                    // Execute Queries
                    $stmtUpdateFormsubmit->execute();
                    $stmtUpdateGs10report->execute();
                }

                // เก็บข้อมูลที่ต้องการส่ง
                $documentsToSend[] = [
                    'id' => $formDataForm,
                    'gs10Approved' => $gs10Approved,
                    'docName' => $formType,
                    'idStudent' => $form['idstd_student'],
                    'nameStudent' => $form['name_student'],
                    'timeSubmit' => $data['timeSubmit'],
                    'message' => 'มีเอกสาร คคอ. บว. 10 ที่ได้รับการอนุมัติครบ 4 รายการแล้ว',
                    'status' => $status
                ];
            }


            if ($gs11Approved === 2) {
                if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                    // อัปเดตสถานะ
                    $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                    // อัปเดตสถานะใน gs11report
                    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                    if (!$stmtUpdateFormsubmit) {
                        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                    }

                    // อัปเดตสถานะใน Gs10report
                    $sqlUpdateGs11report = "UPDATE Gs11report SET status_gs11report = ? WHERE id_gs11report = ? AND name_gs11report = ?";
                    $stmtUpdateGs11report = $conn->prepare($sqlUpdateGs11report);
                    if (!$stmtUpdateGs11report) {
                        throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                    }

                    // Bind Parameters
                    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                    $stmtUpdateGs11report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                    // Execute Queries
                    $stmtUpdateFormsubmit->execute();
                    $stmtUpdateGs11report->execute();
                }
                $documentsToSend[] = [
                    'id' => $formDataForm,
                    'gs11Approved' => $gs11Approved,
                    'idStudent' => $form['idstd_student'],
                    'nameStudent' => $form['name_student'],
                    'timeSubmit' => $data['timeSubmit'],
                    'docName' => $formType,
                    'message' => 'มีเอกสาร คคอ. บว. 11 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                    'status' => $status
                ];
            }
            if ($gs12Approved === 2) {
                if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                    // อัปเดตสถานะ
                    $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                    // อัปเดตสถานะใน gs12report
                    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                    if (!$stmtUpdateFormsubmit) {
                        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                    }

                    // อัปเดตสถานะใน Gs10report
                    $sqlUpdateGs12report = "UPDATE Gs12report SET status_gs12report = ? WHERE id_gs12report = ? AND name_gs12report = ?";
                    $stmtUpdateGs12report = $conn->prepare($sqlUpdateGs12report);
                    if (!$stmtUpdateGs12report) {
                        throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                    }

                    // Bind Parameters
                    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                    $stmtUpdateGs12report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                    // Execute Queries
                    $stmtUpdateFormsubmit->execute();
                    $stmtUpdateGs12report->execute();
                }
                $documentsToSend[] = [
                    'id' => $formDataForm,
                    'gs12Approved' => $gs12Approved,
                    'idStudent' => $form['idstd_student'],
                    'nameStudent' => $form['name_student'],
                    'timeSubmit' => $data['timeSubmit'],
                    'docName' => $formType,
                    'message' => 'มีเอกสาร คคอ. บว. 12 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                    'status' => $status
                ];
            }
            if ($gs13Approved === 2) {
                if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                    // อัปเดตสถานะ
                    $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                    // อัปเดตสถานะใน gs13report
                    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                    if (!$stmtUpdateFormsubmit) {
                        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                    }

                    // อัปเดตสถานะใน Gs10report
                    $sqlUpdateGs13report = "UPDATE Gs13report SET status_gs13report = ? WHERE id_gs13report = ? AND name_gs13report = ?";
                    $stmtUpdateGs13report = $conn->prepare($sqlUpdateGs13report);
                    if (!$stmtUpdateGs13report) {
                        throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                    }

                    // Bind Parameters
                    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                    $stmtUpdateGs13report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                    // Execute Queries
                    $stmtUpdateFormsubmit->execute();
                    $stmtUpdateGs13report->execute();
                }
                $documentsToSend[] = [
                    'id' => $formDataForm,
                    'gs13Approved' => $gs13Approved,
                    'idStudent' => $form['idstd_student'],
                    'nameStudent' => $form['name_student'],
                    'timeSubmit' => $data['timeSubmit'],
                    'docName' => $formType,
                    'message' => 'มีเอกสาร คคอ. บว. 13 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                    'status' => $status
                ];
            }
            if ($gs14Approved === 2) {
                if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                    // อัปเดตสถานะ
                    $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                    // อัปเดตสถานะใน gs14report
                    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                    if (!$stmtUpdateFormsubmit) {
                        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                    }

                    // อัปเดตสถานะใน Gs10report
                    $sqlUpdateGs14report = "UPDATE Gs14report SET status_gs14report = ? WHERE id_gs14report = ? AND name_gs14report = ?";
                    $stmtUpdateGs14report = $conn->prepare($sqlUpdateGs14report);
                    if (!$stmtUpdateGs14report) {
                        throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                    }

                    // Bind Parameters
                    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                    $stmtUpdateGs14report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                    // Execute Queries
                    $stmtUpdateFormsubmit->execute();
                    $stmtUpdateGs14report->execute();
                }
                $documentsToSend[] = [
                    'id' => $formDataForm,
                    'gs14Approved' => $gs14Approved,
                    'idStudent' => $form['idstd_student'],
                    'nameStudent' => $form['name_student'],
                    'timeSubmit' => $data['timeSubmit'],
                    'docName' => $formType,
                    'message' => 'มีเอกสาร คคอ. บว. 14 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                    'status' => $status
                ];
            }
            if ($gs15Approved === 2) {
                if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                    // อัปเดตสถานะ
                    $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                    // อัปเดตสถานะใน gs15report
                    $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                    $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                    if (!$stmtUpdateFormsubmit) {
                        throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                    }

                    // อัปเดตสถานะใน Gs10report
                    $sqlUpdateGs15report = "UPDATE Gs15report SET status_gs15report = ? WHERE id_gs15report = ? AND name_gs15report = ?";
                    $stmtUpdateGs15report = $conn->prepare($sqlUpdateGs15report);
                    if (!$stmtUpdateGs15report) {
                        throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                    }

                    // Bind Parameters
                    $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                    $stmtUpdateGs15report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                    // Execute Queries
                    $stmtUpdateFormsubmit->execute();
                    $stmtUpdateGs15report->execute();
                }
                $documentsToSend[] = [
                    'id' => $formDataForm,
                    'gs15Approved' => $gs15Approved,
                    'idStudent' => $form['idstd_student'],
                    'nameStudent' => $form['name_student'],
                    'timeSubmit' => $data['timeSubmit'],
                    'docName' => $formType,
                    'message' => 'มีเอกสาร คคอ. บว. 15 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                    'status' => $status
                ];
            }
             
        if ($gs16Approved === 1) {
            if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                // อัปเดตสถานะ
                $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                // อัปเดตสถานะใน gs16report
                $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                if (!$stmtUpdateFormsubmit) {
                    throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                }

                // อัปเดตสถานะใน Gs16report
                $sqlUpdateGs16report = "UPDATE gs16report SET status_gs16report = ? WHERE id_gs16report = ? AND name_gs16report = ?";
                $stmtUpdateGs16report = $conn->prepare($sqlUpdateGs16report);
                if (!$stmtUpdateGs16report) {
                    throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                }

                // Bind Parameters
                $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                $stmtUpdateGs16report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                // Execute Queries
                $stmtUpdateFormsubmit->execute();
                $stmtUpdateGs16report->execute();
            }
            $documentsToSend[] = [
                'id' => $formDataForm,
                'gs16Approved' => $gs16Approved,
                'idStudent' => $form['idstd_student'],
                'nameStudent' => $form['name_student'],
                'timeSubmit' => $data['timeSubmit'],
                'docName' => $formType,
                'message' => 'มีเอกสาร คคอ. บว. 16 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                'status' => $status
            ];
        }
         
        if ($gs17Approved === 1) {
            if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                // อัปเดตสถานะ
                $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                // อัปเดตสถานะใน gs17report
                $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                if (!$stmtUpdateFormsubmit) {
                    throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                }

                // อัปเดตสถานะใน Gs17report
                $sqlUpdateGs17report = "UPDATE gs17report SET status_gs17report = ? WHERE id_gs17report = ? AND name_gs17report = ?";
                $stmtUpdateGs17report = $conn->prepare($sqlUpdateGs17report);
                if (!$stmtUpdateGs17report) {
                    throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                }

                // Bind Parameters
                $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                $stmtUpdateGs17report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                // Execute Queries
                $stmtUpdateFormsubmit->execute();
                $stmtUpdateGs17report->execute();
            }
            $documentsToSend[] = [
                'id' => $formDataForm,
                'gs17Approved' => $gs17Approved,
                'idStudent' => $form['idstd_student'],
                'nameStudent' => $form['name_student'],
                'timeSubmit' => $data['timeSubmit'],
                'docName' => $formType,
                'message' => 'มีเอกสาร คคอ. บว. 17 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                'status' => $status
            ];
        }
        if ($gs18Approved === 1) {
            if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                // อัปเดตสถานะ
                $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                // อัปเดตสถานะใน gs18report
                $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                if (!$stmtUpdateFormsubmit) {
                    throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                }

                // อัปเดตสถานะใน Gs18report
                $sqlUpdateGs18report = "UPDATE gs18report SET status_gs18report = ? WHERE id_gs18report = ? AND name_gs18report = ?";
                $stmtUpdateGs18report = $conn->prepare($sqlUpdateGs18report);
                if (!$stmtUpdateGs18report) {
                    throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                }

                // Bind Parameters
                $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                $stmtUpdateGs18report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                // Execute Queries
                $stmtUpdateFormsubmit->execute();
                $stmtUpdateGs18report->execute();
            }
            $documentsToSend[] = [
                'id' => $formDataForm,
                'gs18Approved' => $gs18Approved,
                'idStudent' => $form['idstd_student'],
                'nameStudent' => $form['name_student'],
                'timeSubmit' => $data['timeSubmit'],
                'docName' => $formType,
                'message' => 'มีเอกสาร คคอ. บว. 18 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                'status' => $status
            ];
        } if ($gs19Approved === 1) {
            if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                // อัปเดตสถานะ
                $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                // อัปเดตสถานะใน gs19report
                $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                if (!$stmtUpdateFormsubmit) {
                    throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                }

                // อัปเดตสถานะใน Gs19report
                $sqlUpdateGs19report = "UPDATE gs19report SET status_gs19report = ? WHERE id_gs19report = ? AND name_gs19report = ?";
                $stmtUpdateGs19report = $conn->prepare($sqlUpdateGs19report);
                if (!$stmtUpdateGs19report) {
                    throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                }

                // Bind Parameters
                $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                $stmtUpdateGs19report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                // Execute Queries
                $stmtUpdateFormsubmit->execute();
                $stmtUpdateGs19report->execute();
            }
            $documentsToSend[] = [
                'id' => $formDataForm,
                'gs19Approved' => $gs19Approved,
                'idStudent' => $form['idstd_student'],
                'nameStudent' => $form['name_student'],
                'timeSubmit' => $data['timeSubmit'],
                'docName' => $formType,
                'message' => 'มีเอกสาร คคอ. บว. 19 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                'status' => $status
            ];
        } if ($gs23Approved === 1) {
            if ($ccurrsignaStatus === 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษาแล้ว กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร') {
                // อัปเดตสถานะ
                $formsubmitStatus = 'ได้รับการอนุมัติจากอาจารย์ที่ปรึกษา กำลังรอการพิจารณาจากประธานคณะกรรมการบริหารหลักสูตร';
                // อัปเดตสถานะใน gs23report
                $sqlUpdateFormsubmit = "UPDATE formsubmit SET formsubmit_status = ? WHERE formsubmit_dataform = ? AND formsubmit_type = ?";
                $stmtUpdateFormsubmit = $conn->prepare($sqlUpdateFormsubmit);
                if (!$stmtUpdateFormsubmit) {
                    throw new Exception("Error preparing UPDATE SQL for formsubmit: " . $conn->error);
                }

                // อัปเดตสถานะใน Gs23report
                $sqlUpdateGs23report = "UPDATE gs23report SET status_gs23report = ? WHERE id_gs23report = ? AND name_gs23report = ?";
                $stmtUpdateGs23report = $conn->prepare($sqlUpdateGs23report);
                if (!$stmtUpdateGs23report) {
                    throw new Exception("Error preparing UPDATE SQL for Gs10report: " . $conn->error);
                }

                // Bind Parameters
                $stmtUpdateFormsubmit->bind_param("sss", $formsubmitStatus, $formDataForm, $formType);
                $stmtUpdateGs23report->bind_param("sss", $formsubmitStatusDoc, $formDataForm, $formType);

                // Execute Queries
                $stmtUpdateFormsubmit->execute();
                $stmtUpdateGs23report->execute();
            }
            $documentsToSend[] = [
                'id' => $formDataForm,
                'gs23Approved' => $gs23Approved,
                'idStudent' => $form['idstd_student'],
                'nameStudent' => $form['name_student'],
                'timeSubmit' => $data['timeSubmit'],
                'docName' => $formType,
                'message' => 'มีเอกสาร คคอ. บว. 23 ที่ได้รับการอนุมัติครบ 2 รายการแล้ว',
                'status' => $status
            ];
        }
        } else {
            error_log("Status mapping not found for formDataForm: $formDataForm");
        }
    }

    // ส่งข้อมูลกลับเป็น JSON
    echo json_encode(['documentsToSend' => $documentsToSend], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้ส่งข้อความผิดพลาดในรูป JSON
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    $conn->close();
}
?>