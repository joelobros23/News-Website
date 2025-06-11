<?php
// admin/manage_tags.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$adminUsername = $_SESSION["admin_username"] ?? 'Admin';
$currentAdminUsername = $adminUsername;
// Fetch all tags
$tags = getAllTags($pdo); // Assumes getAllTags() is in functions.php

// Variables for editing a tag (if an edit ID is passed)
$editingTag = null;
$editMode = false;
$formAction = "submit_tag.php?action=create_tag"; // This will be the handler script
$formButtonText = "Add Tag";
$formTagName = "";
$formTagId = "";


if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $editTagId = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
    if ($editTagId) {
        // We'll need a getTagById() function, or adapt logic
        // For now, let's manually find it in the $tags array for simplicity
        // In a real app, a specific DB query is better: $editingTag = getTagById($pdo, $editTagId);
        foreach ($tags as $tag) {
            if ($tag['id'] == $editTagId) {
                $editingTag = $tag;
                break;
            }
        }
        
        if ($editingTag) {
            $editMode = true;
            $formAction = "submit_tag.php?action=update_tag&id=" . $editingTag['id'];
            $formButtonText = "Update Tag";
            $formTagName = $editingTag['name'];
            $formTagId = $editingTag['id'];
        } else {
            header("location: manage_tags.php?status=error&msg=" . urlencode("Tag not found for editing."));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tags - News Week Admin</title>
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
            <header class="mb-8">
                <h2 class="text-3xl font-semibold text-gray-800">Manage Tags</h2>
            </header>

            <!-- Status Messages -->
            <div id="statusMessageContainer" class="mb-6">
                <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                    <div class="p-4 rounded-md text-sm <?php echo ($_GET['status'] == 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <i class="fas <?php echo ($_GET['status'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add/Edit Tag Form -->
            <section class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-xl font-semibold text-gray-700 mb-4"><?php echo $editMode ? 'Edit Tag' : 'Add New Tag'; ?></h3>
                <form id="tagForm" action="<?php echo $formAction; ?>" method="POST">
                    <input type="hidden" name="tag_id" value="<?php echo htmlspecialchars($formTagId); ?>">
                    
                    <div class="mb-6">
                        <label for="tag_name" class="block text-sm font-medium text-gray-700 mb-1">Tag Name <span class="text-red-500">*</span></label>
                        <input type="text" name="tag_name" id="tag_name" value="<?php echo htmlspecialchars($formTagName); ?>" required
                               class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-center">
                        <button type="submit" id="submitTagBtn"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                            <i class="fas <?php echo $editMode ? 'fa-save' : 'fa-plus'; ?> mr-2"></i> <?php echo $formButtonText; ?>
                        </button>
                        <?php if ($editMode): ?>
                            <a href="manage_tags.php" class="ml-4 text-gray-600 hover:text-gray-800 py-2 px-4 rounded-md border border-gray-300 hover:bg-gray-50 transition">
                                Cancel Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <!-- Tags Table -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Existing Tags</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Slug</th>
                                <th scope="col">Articles</th> <!-- Count of articles with this tag -->
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (!empty($tags)): ?>
                                <?php foreach ($tags as $tag): ?>
                                <tr>
                                    <td class="text-gray-500"><?php echo $tag['id']; ?></td>
                                    <td class="font-medium text-gray-900"><?php echo htmlspecialchars($tag['name']); ?></td>
                                    <td><?php echo htmlspecialchars($tag['slug']); ?></td>
                                    <td>
                                        <?php 
                                        // Placeholder for article count - requires a new function or DB query
                                        // echo countArticlesWithTag($pdo, $tag['id']); 
                                        echo rand(0,15); // Temporary placeholder
                                        ?>
                                    </td>
                                    <td class="space-x-1">
                                        <a href="manage_tags.php?edit_id=<?php echo $tag['id']; ?>" class="text-blue-600 hover:text-blue-900 p-1" title="Edit Tag">
                                            <i class="fas fa-edit fa-fw"></i>
                                        </a>
                                        <a href="#" onclick="confirmDeleteTag('<?php echo $tag['id']; ?>', '<?php echo htmlspecialchars(addslashes($tag['name']), ENT_QUOTES); ?>'); return false;" class="text-red-600 hover:text-red-900 p-1" title="Delete Tag">
                                            <i class="fas fa-trash fa-fw"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500 py-4">
                                        No tags found. Add one above!
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
                    <p class="text-sm text-gray-500" id="modalDeleteMessage">Are you sure you want to delete this item? Deleting a tag will remove it from all associated articles.</p>
                </div>
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                    <button id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Yes, Delete
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


            // Clear URL parameters (status, msg) after displaying them
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') || urlParams.has('msg')) {
                const editIdParam = urlParams.has('edit_id') ? '?edit_id=' + urlParams.get('edit_id') : '';
                const cleanUrl = window.location.pathname + editIdParam;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
            
            // If in edit mode, scroll to the form
            if (urlParams.has('edit_id')) {
                const formSection = document.getElementById('tagForm');
                if (formSection) {
                    formSection.scrollIntoView({ behavior: 'smooth' });
                    document.getElementById('tag_name').focus();
                }
            }
        });

        // Delete Confirmation Modal
        const deleteModal = document.getElementById('deleteConfirmationModal');
        const modalDeleteTitle = document.getElementById('modalDeleteTitle');
        const modalDeleteMessage = document.getElementById('modalDeleteMessage');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        let tagActionUrl = '';

        function confirmDeleteTag(id, name) {
            tagActionUrl = `submit_tag.php?action=delete_tag&id=${id}`;
            if (deleteModal && modalDeleteTitle && modalDeleteMessage) {
                modalDeleteTitle.textContent = 'Confirm Tag Deletion';
                modalDeleteMessage.innerHTML = `Are you sure you want to delete the tag: <br><strong>"${name}"</strong>? <br><br>This will remove the tag from all associated articles.`;
                deleteModal.classList.remove('hidden');
            }
        }

        function closeDeleteModal() {
            if (deleteModal) deleteModal.classList.add('hidden');
            tagActionUrl = '';
        }

        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                if (tagActionUrl) {
                    window.location.href = tagActionUrl;
                }
            });
        }
        
        // Form submission visual feedback
        const tagForm = document.getElementById('tagForm');
        if (tagForm) {
            tagForm.addEventListener('submit', function() {
                const submitBtn = document.getElementById('submitTagBtn');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                }
            });
        }
    </script>
</body>
</html>
