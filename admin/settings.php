<?php
// admin/settings.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$adminUsername = $_SESSION["admin_username"] ?? 'Admin';
$currentAdminId = $_SESSION["admin_id"] ?? 0;
$currentAdminUsername = $adminUsername;
// Fetch current admin's details to pre-fill forms
$currentAdminDetails = null;
if ($currentAdminId) {
    try {
        $stmt = $pdo->prepare("SELECT username, email FROM admins WHERE id = :id");
        $stmt->bindParam(':id', $currentAdminId, PDO::PARAM_INT);
        $stmt->execute();
        $currentAdminDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Settings page - Error fetching admin details: " . $e->getMessage());
        // Handle error appropriately
    }
}

if (!$currentAdminDetails) {
    // Fallback or error if details couldn't be fetched
    $currentAdminDetails = ['username' => $adminUsername, 'email' => 'N/A'];
    // Potentially redirect
    // header("location: dashboard.php?status=error&msg=Could not load user settings.");
    // exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - News Week</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { @apply flex items-center p-3 text-gray-700 hover:bg-blue-500 hover:text-white rounded-lg transition-colors duration-200; }
        .sidebar-link .icon { @apply w-5 h-5 mr-3; }
        .sidebar-link.active { @apply bg-blue-600 text-white; }
        .form-section { @apply bg-white p-6 rounded-lg shadow-md mb-8; }
        .form-section h3 { @apply text-xl font-semibold text-gray-700 mb-4 border-b pb-2; }
        .input-group label { @apply block text-sm font-medium text-gray-700 mb-1; }
        .input-group input { @apply w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="mb-8">
                <h2 class="text-3xl font-semibold text-gray-800">Account Settings</h2>
            </header>

            <!-- Status Messages Container -->
            <div id="statusMessageGlobal" class="mb-6">
                 <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                    <div class="p-4 rounded-md text-sm <?php echo ($_GET['status'] == 'success') ? 'bg-green-100 text-green-700' : (($_GET['status'] == 'error') ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'); ?>">
                        <i class="fas <?php echo ($_GET['status'] == 'success') ? 'fa-check-circle' : (($_GET['status'] == 'error') ? 'fa-exclamation-triangle' : 'fa-info-circle'); ?> mr-2"></i>
                        <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Unified Profile Settings Form -->
            <section class="form-section">
                <h3>Update Your Profile</h3>
                <form id="updateProfileForm" action="submit_user.php?action=update_my_profile" method="POST">
                    <div id="profileFormMessage" class="form-message hidden mb-4 p-3 rounded-md text-sm"></div>

                    <fieldset class="mb-6 border-b pb-6">
                        <legend class="text-lg font-medium text-gray-900 mb-2">Account Details</legend>
                        <div class="input-group mb-4">
                            <label for="new_username">Username</label>
                            <input type="text" name="new_username" id="new_username" minlength="4"
                                   value="<?php echo htmlspecialchars($currentAdminDetails['username']); ?>"
                                   placeholder="Enter new username if changing">
                            <p class="text-xs text-gray-500 mt-1">Leave blank if you don't want to change your username. Min 4 characters.</p>
                        </div>
                        <div class="input-group">
                            <label for="new_email">Email Address</label>
                            <input type="email" name="new_email" id="new_email"
                                   value="<?php echo htmlspecialchars($currentAdminDetails['email']); ?>"
                                   placeholder="Enter new email if changing">
                            <p class="text-xs text-gray-500 mt-1">Leave blank if you don't want to change your email.</p>
                        </div>
                    </fieldset>
                    
                    <fieldset>
                        <legend class="text-lg font-medium text-gray-900 mb-2">Change Password (Optional)</legend>
                        <p class="text-sm text-gray-600 mb-3">To change your password, fill in all three password fields below. Otherwise, leave them blank.</p>
                        <div class="input-group mb-4">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password"
                                   placeholder="Required to change password">
                        </div>
                        <div class="input-group mb-4">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" minlength="8"
                                   placeholder="Enter new password">
                            <p class="text-xs text-gray-500 mt-1">Min 8 characters.</p>
                        </div>
                        <div class="input-group mb-6">
                            <label for="confirm_new_password">Confirm New Password</label>
                            <input type="password" name="confirm_new_password" id="confirm_new_password"
                                   placeholder="Confirm new password">
                        </div>
                    </fieldset>
                    
                    <button type="submit" id="saveProfileButton"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </form>
            </section>

        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Sidebar active link
            const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
            document.querySelectorAll('.sidebar-link').forEach(link => {
                const linkHref = link.getAttribute('href');
                if (!linkHref) return;
                const linkPath = linkHref.split('/').pop().split('?')[0] || 'dashboard.php';
                link.classList.remove('active');
                if (linkPath === currentPath) {
                    link.classList.add('active');
                }
            });
             if (currentPath === 'index.php' && document.querySelector('.sidebar-link[href="dashboard.php"]')) {
                document.querySelector('.sidebar-link[href="dashboard.php"]').classList.add('active');
            }

            // Clear general status messages from URL after display
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') || urlParams.has('msg')) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
            
            // Form submission message specific to this page
            const profileFormMessageContainer = document.getElementById('profileFormMessage');
            if (urlParams.has('form_status') && urlParams.has('form_msg')) {
                const formStatus = urlParams.get('form_status');
                const formMsg = decodeURIComponent(urlParams.get('form_msg'));
                if (profileFormMessageContainer) {
                    profileFormMessageContainer.innerHTML = `<i class="fas ${formStatus === 'success' ? 'fa-check-circle text-green-700' : 'fa-exclamation-triangle text-red-700'} mr-2"></i> ${formMsg}`;
                    profileFormMessageContainer.className = `form-message mb-4 p-3 rounded-md text-sm ${formStatus === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
                    profileFormMessageContainer.classList.remove('hidden');
                }
            }


            // Client-side validation for password change part
            const profileForm = document.getElementById('updateProfileForm');
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_new_password');
            const currentPasswordInput = document.getElementById('current_password');

            if (profileForm) {
                profileForm.addEventListener('submit', function(event) {
                    let errorMsg = '';
                    profileFormMessageContainer.classList.add('hidden');
                    profileFormMessageContainer.textContent = '';

                    // Check password fields only if new_password is not empty
                    if (newPasswordInput.value.trim() !== '') {
                        if (currentPasswordInput.value.trim() === '') {
                            errorMsg = 'Current password is required to change your password.';
                        } else if (newPasswordInput.value.length < 8) {
                            errorMsg = 'New password must be at least 8 characters long.';
                        } else if (newPasswordInput.value !== confirmPasswordInput.value) {
                            errorMsg = 'New passwords do not match.';
                        }
                    } else {
                        // If new_password is blank, confirm_new_password and current_password (for password change) should also be blank ideally, or ignored by server
                        if (confirmPasswordInput.value.trim() !== '' || (currentPasswordInput.value.trim() !== '' && newPasswordInput.value.trim() === '' && confirmPasswordInput.value.trim() === '')) {
                           // This case is a bit ambiguous for client-side. Server should primarily handle.
                           // If new_password is empty, but confirm or current is filled for password change context.
                        }
                    }
                    
                    if (errorMsg) {
                        event.preventDefault();
                        if (profileFormMessageContainer) {
                            profileFormMessageContainer.innerHTML = `<i class="fas fa-exclamation-triangle text-red-700 mr-2"></i> ${errorMsg}`;
                            profileFormMessageContainer.className = 'form-message mb-4 p-3 rounded-md text-sm bg-red-100 text-red-700';
                            profileFormMessageContainer.classList.remove('hidden');
                        }
                        return;
                    }

                    const button = document.getElementById('saveProfileButton');
                    if (button) {
                        button.disabled = true;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving Changes...';
                    }
                });
            }
        });
    </script>
</body>
</html>
