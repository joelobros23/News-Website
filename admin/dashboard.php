<?php
// admin/dashboard.php
// Initialize session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and function files
// It's crucial that db_connect.php (which defines $pdo) is included before functions.php
// if functions in functions.php might use $pdo globally (though passing $pdo as an argument is better).
require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions, including getRecentArticles and requireAdminLogin

// Check if the user is logged in, if not then redirect to login page
requireAdminLogin(); // This function will handle the session check and redirection.

$adminUsername = $_SESSION["admin_username"] ?? 'Admin';

// This variable will be used by sidebar.php
$currentAdminUsername = $adminUsername; 

// Fetch data for dashboard widgets - ensure $pdo is passed to these functions
$totalArticles = countTotalArticles($pdo);
$totalPublishedArticles = countTotalArticles($pdo, 'published'); // Example for published
$totalCategories = countTotalCategories($pdo); // We'll need to create this function
$totalTags = countTotalTags($pdo);             // We'll need to create this function
$totalAdminUsers = countTotalAdminUsers($pdo); // We'll need to create this function

// Fetch recent articles
$recentArticles = getRecentArticles($pdo, 5); // Pass $pdo and set limit to 5
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - News Week</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { @apply flex items-center p-3 text-gray-700 hover:bg-blue-500 hover:text-white rounded-lg transition-colors duration-200; }
        .sidebar-link .icon { @apply w-5 h-5 mr-3; }
        .sidebar-link.active { @apply bg-blue-600 text-white; }
        .table th { @apply px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50; }
        .table td { @apply px-4 py-3 whitespace-nowrap text-sm text-gray-700 bg-white; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="mb-8 flex justify-between items-center">
                <h2 class="text-3xl font-semibold text-gray-800">Dashboard Overview</h2>
                <a href="article_form.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    <i class="fas fa-plus mr-2"></i> Add New Article
                </a>
            </header>

            <!-- Stats Cards -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-full mr-4">
                            <i class="fas fa-newspaper fa-2x text-blue-500"></i>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $totalArticles; ?></p>
                            <p class="text-gray-500">Total Articles</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-full mr-4">
                            <i class="fas fa-tags fa-2x text-green-500"></i>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $totalCategories; ?></p>
                            <p class="text-gray-500">Total Categories</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                     <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-full mr-4">
                            <i class="fas fa-hashtag fa-2x text-yellow-500"></i>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $totalTags; ?></p>
                            <p class="text-gray-500">Total Tags</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                     <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-full mr-4">
                            <i class="fas fa-users fa-2x text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $totalAdminUsers; ?></p>
                            <p class="text-gray-500">Admin Users</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Articles Table -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Recent Published Articles</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table">
                        <thead>
                            <tr>
                                <th scope="col">Title</th>
                                <th scope="col">Category</th>
                                <th scope="col">Author</th>
                                <th scope="col">Date Published</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (!empty($recentArticles)): ?>
                                <?php foreach ($recentArticles as $article): ?>
                                <tr>
                                    <td class="font-medium text-gray-900 hover:text-blue-600">
                                        <a href="../article.php?id=<?php echo $article['id']; ?>" target="_blank" title="View Public Page: <?php echo htmlspecialchars($article['title']); ?>">
                                            <?php echo htmlspecialchars(createExcerpt($article['title'], 50)); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($article['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($article['author_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $article['publish_date'] ? date('M d, Y', strtotime($article['publish_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo ($article['status'] == 'published') ? 'bg-green-100 text-green-800' : 
                                                       (($article['status'] == 'draft') ? 'bg-yellow-100 text-yellow-800' : 
                                                       'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($article['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="space-x-1 whitespace-nowrap">
                                        <a href="article_form.php?id=<?php echo $article['id']; ?>" class="text-blue-600 hover:text-blue-900 p-1" title="Edit Article">
                                            <i class="fas fa-edit fa-fw"></i> <span class="hidden sm:inline">Edit</span>
                                        </a>
                                        <a href="#" onclick="confirmDelete('<?php echo $article['id']; ?>', '<?php echo htmlspecialchars(addslashes($article['title']), ENT_QUOTES); ?>'); return false;" class="text-red-600 hover:text-red-900 p-1" title="Delete Article">
                                            <i class="fas fa-trash fa-fw"></i> <span class="hidden sm:inline">Delete</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500 py-4">
                                        No recent published articles found. <a href="article_form.php" class="text-blue-500 hover:underline">Add a new article!</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <a href="manage_articles.php" class="text-blue-500 hover:text-blue-700 font-semibold">View All Articles &rarr;</a>
                </div>
            </section>
            
        </main>
    </div>

    <!-- Confirmation Modal (Generic) -->
    <div id="confirmationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50 px-4">
        <div class="relative p-6 sm:p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-xl">
            <button onclick="closeModal()" class="absolute top-0 right-0 mt-4 mr-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times fa-lg"></i>
            </button>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-red-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Confirm Action</h3>
                <div class="mt-2 px-2 sm:px-7 py-3">
                    <p class="text-sm text-gray-500" id="modalMessage">Are you sure you want to proceed? This cannot be undone.</p>
                </div>
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                    <button id="confirmActionButton" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm
                    </button>
                    <button onclick="closeModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/admin_script.js"></script>
    <script>
        // Admin username display
        const adminUser = <?php echo json_encode($adminUsername); ?>;
        const adminUsernameDisplay = document.getElementById('adminUsernameDisplay');
        if (adminUsernameDisplay && adminUser) {
            adminUsernameDisplay.textContent = adminUser;
        }

        // Sidebar active link
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.classList.remove('active');
                const linkPath = link.getAttribute('href').split('/').pop() || 'dashboard.php';
                if (linkPath === currentPath) {
                    link.classList.add('active');
                }
            });
             // Ensure dashboard.php is active if on admin root (index.php)
            if (currentPath === 'index.php') {
                document.querySelector('.sidebar-link[href="dashboard.php"]')?.classList.add('active');
            }
        });

        // Modal functions
        const modal = document.getElementById('confirmationModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const confirmActionButton = document.getElementById('confirmActionButton');
        let actionUrl = ''; // Stores the URL for the confirmed action (e.g., deletion)

        function confirmDelete(articleId, articleTitle) {
            actionUrl = `delete_article.php?id=${articleId}&confirm=true`; // Example: specific delete script
            modalTitle.textContent = 'Confirm Deletion';
            modalMessage.innerHTML = `Are you sure you want to delete the article: <br><strong>"${articleTitle}"</strong>? <br><br>This action cannot be undone.`;
            confirmActionButton.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm';
            confirmActionButton.textContent = 'Yes, Delete Article';
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            actionUrl = ''; // Reset action URL
        }

        if (confirmActionButton) {
            confirmActionButton.addEventListener('click', () => {
                if (actionUrl) {
                    // In a real application, you would likely redirect to the actionUrl
                    // or submit a form via AJAX to perform the deletion.
                    console.log('Confirmed action. Would navigate to or process:', actionUrl);
                    // For actual deletion, you'd redirect or use AJAX:
                    // window.location.href = actionUrl;
                    alert(`Simulating action for: ${actionUrl}. Implement actual PHP logic.`);
                    closeModal(); 
                    // Optionally, you might want to reload the page or remove the item from the UI.
                    // location.reload(); 
                }
            });
        }
    </script>
</body>
</html>
