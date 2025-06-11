<?php
// admin/manage_users.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$adminUsername = $_SESSION["admin_username"] ?? 'Admin';
$currentAdminId = $_SESSION["admin_id"] ?? 0; // Get current admin's ID
$currentAdminUsername = $adminUsername;
// Fetch all admin users (excluding passwords)
// We'll need a function for this, let's call it getAllAdminUsers()
/**
 * Fetches all admin users from the database.
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of admin users.
 */
function getAllAdminUsers(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, username, email, created_at FROM admins ORDER BY username ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllAdminUsers: " . $e->getMessage());
        return [];
    }
}
$adminUsers = getAllAdminUsers($pdo);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admin Users - News Week Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { @apply flex items-center p-3 text-gray-700 hover:bg-blue-500 hover:text-white rounded-lg transition-colors duration-200; }
        .sidebar-link .icon { @apply w-5 h-5 mr-3; }
        .sidebar-link.active { @apply bg-blue-600 text-white; }
        .table th { @apply px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50; }
        .table td { @apply px-4 py-3 text-sm text-gray-700 bg-white whitespace-nowrap; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="mb-8 flex justify-between items-center">
                <h2 class="text-3xl font-semibold text-gray-800">Manage Admin Users</h2>
                <a href="register.php" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    <i class="fas fa-user-plus mr-2"></i> Add New Admin
                </a>
            </header>
            <p class="text-sm text-yellow-700 bg-yellow-100 p-3 rounded-md mb-6">
                <i class="fas fa-exclamation-triangle mr-1"></i> Note: The "Add New Admin" button links to the public registration page (<code>register.php</code>). For security, this registration page should be protected or removed in a production environment after initial setup.
            </p>

            <!-- Status Messages -->
            <div id="statusMessageContainer" class="mb-6">
                <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                    <div class="p-4 rounded-md text-sm <?php echo ($_GET['status'] == 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <i class="fas <?php echo ($_GET['status'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Admin Users Table -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Existing Admin Users</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Username</th>
                                <th scope="col">Email</th>
                                <th scope="col">Date Created</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (!empty($adminUsers)): ?>
                                <?php foreach ($adminUsers as $user): ?>
                                <tr>
                                    <td class="text-gray-500"><?php echo $user['id']; ?></td>
                                    <td class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td class="space-x-1">
                                        <a href="user_form.php?edit_id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 p-1" title="Edit User">
                                            <i class="fas fa-edit fa-fw"></i>
                                        </a>
                                        <?php if ($user['id'] != $currentAdminId && count($adminUsers) > 1): // Prevent deleting self or last admin ?>
                                            <a href="#" onclick="confirmDeleteUser('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars(addslashes($user['username']), ENT_QUOTES); ?>'); return false;" class="text-red-600 hover:text-red-900 p-1" title="Delete User">
                                                <i class="fas fa-trash fa-fw"></i>
                                            </a>
                                        <?php elseif ($user['id'] == $currentAdminId): ?>
                                            <span class="text-gray-400 p-1" title="Cannot delete yourself"><i class="fas fa-trash fa-fw"></i></span>
                                        <?php elseif (count($adminUsers) <= 1): ?>
                                            <span class="text-gray-400 p-1" title="Cannot delete the last admin user"><i class="fas fa-trash fa-fw"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500 py-4">
                                        No admin users found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50 px-4">
        <div class="relative p-6 sm:p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-xl">
             <button onclick="closeDeleteModal()" class="absolute top-0 right-0 mt-4 mr-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times fa-lg"></i>
            </button>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-red-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalDeleteTitle">Confirm Deletion</h3>
                <div class="mt-2 px-2 sm:px-7 py-3">
                    <p class="text-sm text-gray-500" id="modalDeleteMessage">Are you sure you want to delete this user? This action cannot be undone.</p>
                </div>
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                    <button id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Yes, Delete User
                    </button>
                    <button onclick="closeDeleteModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/admin_script.js"></script> <!-- General admin scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Sidebar active link handling
            const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
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

            // Clear URL parameters (status, msg)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') || urlParams.has('msg')) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        });

        // Delete Confirmation Modal
        const deleteModal = document.getElementById('deleteConfirmationModal');
        const modalDeleteTitle = document.getElementById('modalDeleteTitle');
        const modalDeleteMessage = document.getElementById('modalDeleteMessage');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        let userActionUrl = '';

        function confirmDeleteUser(id, username) {
            userActionUrl = `submit_user.php?action=delete_user&id=${id}`; // This will be the handler
            if (deleteModal && modalDeleteTitle && modalDeleteMessage) {
                modalDeleteTitle.textContent = 'Confirm User Deletion';
                modalDeleteMessage.innerHTML = `Are you sure you want to delete the admin user: <br><strong>"${username}" (ID: ${id})</strong>? <br><br>This action cannot be undone.`;
                deleteModal.classList.remove('hidden');
            }
        }

        function closeDeleteModal() {
            if (deleteModal) deleteModal.classList.add('hidden');
            userActionUrl = '';
        }

        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                if (userActionUrl) {
                    window.location.href = userActionUrl;
                }
            });
        }
    </script>
</body>
</html>