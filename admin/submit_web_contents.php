<?php
// admin/submit_web_contents.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; 
require_once __DIR__ . '/includes/functions.php';

requireAdminLogin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // An array of all possible setting names from the form
    // This must perfectly match the `name` attributes in your web_contents.php form
    $allowed_settings = [
        'about_us', 
        'privacy_policy', 
        'contact_telephone', 
        'contact_mobile', 
        'contact_email', 
        'contact_location',
        'social_facebook', 
        'social_twitter', 
        'social_instagram', 
        'social_linkedin'
    ];

    try {
        $pdo->beginTransaction();

        // This query inserts a new setting or updates it if the name (the primary key) already exists.
        $sql = "INSERT INTO site_settings (setting_name, setting_value) VALUES (:setting_name, :setting_value)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        
        $stmt = $pdo->prepare($sql);

        // Loop through every allowed setting
        foreach ($allowed_settings as $setting_name) {
            // Check if a value for this setting was submitted in the POST data.
            // This is important because unchecked checkboxes, for example, might not be sent.
            if (isset($_POST[$setting_name])) {
                // Get the value from the POST array. 
                // For Quill editors, this comes from the hidden input that JS populates.
                // For text fields, it's their direct value.
                $setting_value = $_POST[$setting_name];

                // Execute the prepared statement for each setting with its specific values.
                $stmt->execute([
                    ':setting_name' => $setting_name,
                    ':setting_value' => $setting_value
                ]);
            }
        }

        $pdo->commit();
        
        // Redirect back with a success message
        header("location: web_contents.php?status=success&msg=" . urlencode("Web contents updated successfully!"));
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Log the detailed error for your own debugging
        error_log("Error saving web contents: " . $e->getMessage());
        // Redirect with a user-friendly error message
        header("location: web_contents.php?status=error&msg=" . urlencode("A database error occurred. Could not save changes."));
        exit;
    }

} else {
    // If someone accesses this page directly without POSTing, redirect them.
    header("location: web_contents.php");
    exit;
}
?>
