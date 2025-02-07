<?php
//ไฟล์ fetchstudent.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Include database connection
include('db_connection.php');

// Prepare SQL query to fetch students
$stmt = $conn->prepare("
    SELECT 
        s.id_student, 
        s.id_studyplan, 
        s.prefix_student, 
        s.name_student,
        s.prefix_studentEng, 
        s.name_studentEng,  
        s.idstd_student, 
        s.major_student, 
        s.branch_student, 
        s.abbreviate_student, 
        s.address_student, 
        s.email_student, 
        s.tel_student,
        s.password_student,
        status_student,
        sp.name_studyplan
    FROM 
        student s 
    LEFT JOIN 
        studyplan sp 
    ON 
        s.id_studyplan = sp.id_studyplan
");

$stmt->execute();
$result = $stmt->get_result();

// Check if there are any results
if ($result->num_rows > 0) {
    $student = array();

    // Fetch all rows and store them in an array
    while ($row = $result->fetch_assoc()) {
        $student[] = $row;
    }

    // Return the students data as a JSON response
    echo json_encode($student);
} else {
    echo json_encode([]); // Return an empty array if no students are found
}

// Close the connection
$conn->close();
