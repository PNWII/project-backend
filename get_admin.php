<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Include database connection
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    // Get idAdmin from the request
    $userIDAdmin = $input['idAdmin'] ?? null;

    if ($userIDAdmin) {
        // Check if idAdmin exists in the database
        $stmt = $conn->prepare("SELECT username_admin,name_admin,email_admin,tel_admin FROM admin WHERE email_admin = ?");
        $stmt->bind_param("s", $userIDAdmin);
        $stmt->execute();
        $stmt->bind_result($usernameAdmin, $nameAdmin, $emailAdmin, $telAdmin);
        $stmt->fetch();

        if ($usernameAdmin) {
            // Return the username of the admin
            echo json_encode([
                "status" => "success",
                "usernameAdmin" => $usernameAdmin,
                "name_admin" => $nameAdmin,
                "email_admin" => $emailAdmin,
                "tel_admin" => $telAdmin
            ]);
        } else {
            // No admin found with the provided ID
            echo json_encode(["status" => "error", "message" => "User not found"]);
        }

        $stmt->close();
    } else {
        // If idAdmin is not provided
        echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    }

} else {
    // If the request method is not POST
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

$conn->close();
?>