<?php
// admin/auth.php

// Start session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

$action = $_GET['action'] ?? '';

// --- LOGIN ACTION ---
if ($action === 'login') {
    // ... (your existing login code remains here) ...
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        if (empty(trim($username)) || empty(trim($password))) {
            header("location: index.php?error=empty_fields");
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (verifyAdminPassword($password, $admin['password'])) {
                        session_regenerate_id(true);
                        $_SESSION["admin_loggedin"] = true;
                        $_SESSION["admin_id"] = $admin['id'];
                        $_SESSION["admin_username"] = $admin['username'];

                        if ($remember_me) {
                            $cookie_name = "admin_remember_me";
                            $cookie_value = base64_encode($admin['id'] . '::' . hash('sha256', $admin['username'] . DB_SERVER));
                            setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/", "", isset($_SERVER["HTTPS"]), true);
                        } else {
                            if (isset($_COOKIE['admin_remember_me'])) {
                                setcookie('admin_remember_me', '', time() - 3600, "/");
                            }
                        }
                        
                        $redirectUrl = $_GET['redirect'] ?? 'dashboard.php';
                        // Basic validation for redirect URL
                        if (filter_var($redirectUrl, FILTER_VALIDATE_URL) && strpos($redirectUrl, $_SERVER['HTTP_HOST']) === false) {
                            $redirectUrl = 'dashboard.php';
                        } else if (!filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
                             $redirectUrl = ltrim($redirectUrl, '/');
                        }
                        header("location: " . (empty($redirectUrl) ? 'dashboard.php' : $redirectUrl));
                        exit;
                    } else {
                        header("location: index.php?error=invalid_credentials");
                        exit;
                    }
                } else {
                    header("location: index.php?error=invalid_credentials");
                    exit;
                }
            } else {
                error_log("Admin login SQL execution error.");
                header("location: index.php?error=server_error");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Admin login PDOException: " . $e->getMessage());
            header("location: index.php?error=server_error");
            exit;
        }
    } else {
        header("location: index.php?error=invalid_request_method");
        exit;
    }
}
// --- REGISTRATION ACTION ---
elseif ($action === 'register') {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $reg_username = trim($_POST['reg_username'] ?? '');
        $reg_email = trim($_POST['reg_email'] ?? '');
        $reg_password = $_POST['reg_password'] ?? '';
        $reg_confirm_password = $_POST['reg_confirm_password'] ?? '';

        // --- Validation ---
        $errors = [];
        if (empty($reg_username) || strlen($reg_username) < 4) {
            $errors['username'] = "Username must be at least 4 characters long.";
        }
        if (empty($reg_email) || !filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Please enter a valid email address.";
        }
        if (empty($reg_password) || strlen($reg_password) < 8) {
            $errors['password'] = "Password must be at least 8 characters long.";
        }
        if ($reg_password !== $reg_confirm_password) {
            $errors['confirm_password'] = "Passwords do not match.";
        }

        // Check if username or email already exists
        if (empty($errors)) {
            try {
                // Check username
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username LIMIT 1");
                $stmt->bindParam(':username', $reg_username, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors['username_exists'] = "Username already taken. Please choose another.";
                }

                // Check email
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = :email LIMIT 1");
                $stmt->bindParam(':email', $reg_email, PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors['email_exists'] = "Email address already registered. Please use another or login.";
                }
            } catch (PDOException $e) {
                error_log("Registration check PDOException: " . $e->getMessage());
                $errors['db_error'] = "A database error occurred. Please try again.";
            }
        }
        
        // --- Process Registration or Redirect with Errors ---
        if (!empty($errors)) {
            // For simplicity, redirect with the first error.
            // A more robust solution would pass all errors back or use session flash messages.
            $first_error_key = array_key_first($errors);
            $error_message = $errors[$first_error_key];
            $field_param = '';
            if ($first_error_key === 'username' || $first_error_key === 'username_exists') $field_param = '&field=username';
            if ($first_error_key === 'email' || $first_error_key === 'email_exists') $field_param = '&field=email';
            
            header("location: register.php?status=error&msg=" . urlencode($error_message) . $field_param);
            exit;
        } else {
            // Hash the password
            $hashed_password = hashAdminPassword($reg_password); // from functions.php

            // Insert new admin into the database
            try {
                $insert_stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (:username, :email, :password)");
                $insert_stmt->bindParam(':username', $reg_username, PDO::PARAM_STR);
                $insert_stmt->bindParam(':email', $reg_email, PDO::PARAM_STR);
                $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);

                if ($insert_stmt->execute()) {
                    // Registration successful
                    header("location: index.php?status=registration_success&msg=" . urlencode("Admin account created successfully! Please login."));
                    exit;
                } else {
                    error_log("Admin registration SQL execution error.");
                    header("location: register.php?status=error&msg=" . urlencode("Could not create account due to a server error."));
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Admin registration PDOException: " . $e->getMessage());
                // Check for unique constraint violation (though we checked above, race conditions are possible)
                if ($e->errorInfo[1] == 1062) { // MySQL error code for duplicate entry
                     header("location: register.php?status=error&msg=" . urlencode("Username or Email already exists."));
                } else {
                     header("location: register.php?status=error&msg=" . urlencode("A database error occurred during registration."));
                }
                exit;
            }
        }
    } else {
        // If not a POST request for registration, redirect
        header("location: register.php?error=invalid_request_method");
        exit;
    }
}
// --- LOGOUT ACTION ---
elseif ($action === 'logout') {
    // ... (your existing logout code remains here) ...
    $_SESSION = array();
    if (isset($_COOKIE['admin_remember_me'])) {
        unset($_COOKIE['admin_remember_me']);
        setcookie('admin_remember_me', '', time() - 3600, '/');
    }
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("location: index.php?status=logged_out");
    exit;
}
// --- INVALID ACTION ---
else {
    // ... (your existing invalid action code remains here) ...
    if (isAdminLoggedIn()) {
        header("location: dashboard.php?error=invalid_action");
    } else {
        header("location: index.php?error=invalid_action");
    }
    exit;
}

if (isset($pdo)) $pdo = null;
?>
