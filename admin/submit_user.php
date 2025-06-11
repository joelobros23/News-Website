<?php
// admin/submit_user.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$action = $_GET['action'] ?? '';
$currentAdminId = $_SESSION['admin_id'] ?? 0;

if (!$currentAdminId) {
    // Should not happen if requireAdminLogin works
    header("location: settings.php?form_status=error&form_msg=" . urlencode("User session not found. Please login again."));
    exit;
}

// --- UPDATE MY PROFILE ACTION ---
if ($action === 'update_my_profile' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_email = trim($_POST['new_email'] ?? '');
    
    $current_password_for_change = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    $update_fields = [];
    $params = [':id' => $currentAdminId];
    $success_messages = [];
    $error_messages = [];

    // Fetch current user details for comparison and password verification
    try {
        $stmt_current = $pdo->prepare("SELECT username, email, password FROM admins WHERE id = :id");
        $stmt_current->bindParam(':id', $currentAdminId, PDO::PARAM_INT);
        $stmt_current->execute();
        $currentUser = $stmt_current->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            header("location: settings.php?form_status=error&form_msg=" . urlencode("Could not retrieve current user data."));
            exit;
        }
    } catch (PDOException $e) {
        error_log("Update Profile - Error fetching current user: " . $e->getMessage());
        header("location: settings.php?form_status=error&form_msg=" . urlencode("Database error fetching user details."));
        exit;
    }

    // --- Process Username Change ---
    if (!empty($new_username) && $new_username !== $currentUser['username']) {
        if (strlen($new_username) < 4) {
            $error_messages[] = "New username must be at least 4 characters long.";
        } else {
            // Check if new username is already taken by another user
            $stmt_check_username = $pdo->prepare("SELECT id FROM admins WHERE username = :username AND id != :id");
            $stmt_check_username->bindParam(':username', $new_username, PDO::PARAM_STR);
            $stmt_check_username->bindParam(':id', $currentAdminId, PDO::PARAM_INT);
            $stmt_check_username->execute();
            if ($stmt_check_username->rowCount() > 0) {
                $error_messages[] = "New username '{$new_username}' is already taken.";
            } else {
                $update_fields[] = "username = :new_username";
                $params[':new_username'] = $new_username;
            }
        }
    }

    // --- Process Email Change ---
    if (!empty($new_email) && $new_email !== $currentUser['email']) {
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_messages[] = "New email address is not valid.";
        } else {
            // Check if new email is already taken by another user
            $stmt_check_email = $pdo->prepare("SELECT id FROM admins WHERE email = :email AND id != :id");
            $stmt_check_email->bindParam(':email', $new_email, PDO::PARAM_STR);
            $stmt_check_email->bindParam(':id', $currentAdminId, PDO::PARAM_INT);
            $stmt_check_email->execute();
            if ($stmt_check_email->rowCount() > 0) {
                $error_messages[] = "New email address '{$new_email}' is already registered by another user.";
            } else {
                $update_fields[] = "email = :new_email";
                $params[':new_email'] = $new_email;
            }
        }
    }

    // --- Process Password Change ---
    // Only attempt password change if new_password field is filled
    if (!empty($new_password)) {
        if (empty($current_password_for_change)) {
            $error_messages[] = "Current password is required to change your password.";
        } elseif (!verifyAdminPassword($current_password_for_change, $currentUser['password'])) {
            $error_messages[] = "Current password provided is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $error_messages[] = "New password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_new_password) {
            $error_messages[] = "New passwords do not match.";
        } else {
            // All checks passed for password change
            $update_fields[] = "password = :new_password_hashed";
            $params[':new_password_hashed'] = hashAdminPassword($new_password);
        }
    } elseif (!empty($current_password_for_change) && empty($new_password)) {
        // User typed current password but not new password, which is usually an error or oversight
        $error_messages[] = "Please provide a new password if you've entered your current password for a change.";
    }


    // --- Execute Update if there are changes and no errors ---
    if (empty($error_messages) && !empty($update_fields)) {
        try {
            $sql = "UPDATE admins SET " . implode(", ", $update_fields) . " WHERE id = :id";
            $stmt_update = $pdo->prepare($sql);
            
            if ($stmt_update->execute($params)) {
                $success_messages[] = "Profile updated successfully!";
                // If username was changed, update session
                if (isset($params[':new_username'])) {
                    $_SESSION['admin_username'] = $params[':new_username'];
                }
                header("location: settings.php?form_status=success&form_msg=" . urlencode(implode(" ", $success_messages)));
            } else {
                header("location: settings.php?form_status=error&form_msg=" . urlencode("Could not update profile due to a database error."));
            }
            exit;

        } catch (PDOException $e) {
            error_log("Update Profile PDOException: " . $e->getMessage());
            header("location: settings.php?form_status=error&form_msg=" . urlencode("Database error during profile update. Code: " . $e->getCode()));
            exit;
        }
    } elseif (!empty($error_messages)) {
        // Redirect with errors
        header("location: settings.php?form_status=error&form_msg=" . urlencode(implode("<br>", $error_messages)));
        exit;
    } else {
        // No changes were made or attempted
        header("location: settings.php?form_status=info&form_msg=" . urlencode("No changes were submitted."));
        exit;
    }
}


// --- DELETE USER ACTION (from manage_users.php) ---
elseif ($action === 'delete_user' && isset($_GET['id'])) {
    $user_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$user_id_to_delete) {
        header("location: manage_users.php?status=error&msg=" . urlencode("Invalid user ID for deletion."));
        exit;
    }

    if ($user_id_to_delete == $currentAdminId) {
        header("location: manage_users.php?status=error&msg=" . urlencode("You cannot delete your own account from the user list."));
        exit;
    }

    try {
        $stmt_count = $pdo->query("SELECT COUNT(*) FROM admins");
        $totalAdminUsers = (int)$stmt_count->fetchColumn();
        if ($totalAdminUsers <= 1 && $user_id_to_delete == $currentAdminId) { // Double check, though prior check should catch current user
             header("location: manage_users.php?status=error&msg=" . urlencode("Cannot delete the only admin user."));
             exit;
        }
         if ($totalAdminUsers <= 1) { // More general check for last user
            header("location: manage_users.php?status=error&msg=" . urlencode("Cannot delete the last admin user."));
            exit;
        }


        $stmt_delete = $pdo->prepare("DELETE FROM admins WHERE id = :id");
        $stmt_delete->bindParam(':id', $user_id_to_delete, PDO::PARAM_INT);
        
        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                header("location: manage_users.php?status=success&msg=" . urlencode("Admin user (ID: ".$user_id_to_delete.") deleted successfully."));
            } else {
                header("location: manage_users.php?status=error&msg=" . urlencode("User not found or could not be deleted."));
            }
        } else {
            header("location: manage_users.php?status=error&msg=" . urlencode("Database error during user deletion."));
        }
        exit;

    } catch (PDOException $e) {
        error_log("User Delete PDOException: " . $e->getMessage());
        header("location: manage_users.php?status=error&msg=" . urlencode("Database error. Could not delete user."));
        exit;
    }
}

// --- Fallback for other actions or direct access ---
else {
    header("location: dashboard.php?status=error&msg=" . urlencode("Invalid user operation or direct access."));
    exit;
}

if (isset($pdo)) $pdo = null;
?>
