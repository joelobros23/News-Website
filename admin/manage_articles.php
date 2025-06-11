<?php
// admin/manage_articles.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$adminUsername = $_SESSION["admin_username"] ?? 'Admin';
$currentAdminUsername = $adminUsername;
// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$articlesPerPage = 10; // Number of articles to display per page
$offset = ($page - 1) * $articlesPerPage;

// Fetch articles for the current page
// We'll need a function like getAllArticlesWithCount or modify getAllArticles
// For now, let's assume getAllArticles can take limit and offset
$articles = getAllArticles($pdo, $articlesPerPage, $offset);
$totalArticles = countTotalArticles($pdo); // Get total for pagination
$totalPages = ceil($totalArticles / $articlesPerPage);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Articles - News Week Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { @apply flex items-center p-3 text-gray-700 hover:bg-blue-500 hover:text-white rounded-lg transition-colors duration-200; }
        .sidebar-link .icon { @apply w-5 h-5 mr-3; }
        .sidebar-link.active { @apply bg-blue-600 text-white; }
        .table th { @apply px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50; }
        .table td { @apply px-4 py-3 whitespace-nowrap text-sm text-gray-700 bg-white; }
        .pagination a, .pagination span { @apply inline-flex items-center justify-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50; }
        .pagination span.current { @apply z-10 bg-blue-50 border-blue-500 text-blue-600; }
        .pagination a.disabled, .pagination span.disabled { @apply opacity-50 cursor-not-allowed; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="mb-8 flex justify-between items-center">
                <h2 class="text-3xl font-semibold text-gray-800">Manage Articles</h2>
                <a href="article_form.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    <i class="fas fa-plus mr-2"></i> Add New Article
                </a>
            </header>

            <!-- Status Messages (e.g., after deletion) -->
            <div id="statusMessageContainer" class="mb-4">
                <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                    <div class="p-4 rounded-md text-sm <?php echo ($_GET['status'] == 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <i class="fas <?php echo ($_GET['status'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
                    </div>
                <?php endif; ?>
            </div>


            <!-- Articles Table -->
            <section class="bg-white p-6 rounded-lg shadow-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Title</th>
                                <th scope="col">Category</th>
                                <th scope="col">Author</th>
                                <th scope="col">Status</th>
                                <th scope="col">Publish Date</th>
                                <th scope="col">Last Updated</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (!empty($articles)): ?>
                                <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td class="text-gray-500"><?php echo $article['id']; ?></td>
                                    <td class="font-medium text-gray-900 hover:text-blue-600 max-w-xs truncate">
                                        <a href="article_form.php?id=<?php echo $article['id']; ?>" title="Edit: <?php echo htmlspecialchars($article['title']); ?>">
                                            <?php echo htmlspecialchars(createExcerpt($article['title'], 60)); ?>
                                        </a>
                                        <a href="../article.php?slug=<?php echo $article['slug']; ?>" target="_blank" class="text-xs text-blue-400 hover:text-blue-600 ml-1" title="View Public Page">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($article['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($article['author_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo ($article['status'] == 'published') ? 'bg-green-100 text-green-800' : 
                                                       (($article['status'] == 'draft') ? 'bg-yellow-100 text-yellow-800' : 
                                                       'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($article['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $article['publish_date'] ? date('M d, Y H:i', strtotime($article['publish_date'])) : 'Not Published'; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($article['updated_at'])); ?></td>
                                    <td class="space-x-1 whitespace-nowrap">
                                        <a href="article_form.php?id=<?php echo $article['id']; ?>" class="text-blue-600 hover:text-blue-900 p-1" title="Edit Article">
                                            <i class="fas fa-edit fa-fw"></i>
                                        </a>
                                        <a href="#" onclick="confirmDeleteArticle('<?php echo $article['id']; ?>', '<?php echo htmlspecialchars(addslashes($article['title']), ENT_QUOTES); ?>'); return false;" class="text-red-600 hover:text-red-900 p-1" title="Delete Article">
                                            <i class="fas fa-trash fa-fw"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-gray-500 py-4">
                                        No articles found. <a href="article_form.php" class="text-blue-500 hover:underline">Add a new article!</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-6 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6" aria-label="Pagination">
                    <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium"><?php echo $offset + 1; ?></span>
                            to
                            <span class="font-medium"><?php echo min($offset + $articlesPerPage, $totalArticles); ?></span>
                            of
                            <span class="font-medium"><?php echo $totalArticles; ?></span>
                            results
                        </p>
                    </div>
                    <div class="flex-1 flex justify-between sm:justify-end pagination space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="relative">Previous</a>
                        <?php else: ?>
                            <span class="relative disabled">Previous</span>
                        <?php endif; ?>
                        
                        <?php
                        // Pagination links logic (simplified for brevity)
                        // You might want a more complex logic for many pages (e.g., with ellipses)
                        $linksToShow = 5; // Number of page links to show around current page
                        $start = max(1, $page - floor($linksToShow / 2));
                        $end = min($totalPages, $page + floor($linksToShow / 2));

                        if ($start > 1) {
                            echo '<a href="?page=1">1</a>';
                            if ($start > 2) echo '<span class="disabled">...</span>';
                        }

                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'current' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor;

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) echo '<span class="disabled">...</span>';
                            echo '<a href="?page='.$totalPages.'">'.$totalPages.'</a>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="relative">Next</a>
                        <?php else: ?>
                            <span class="relative disabled">Next</span>
                        <?php endif; ?>
                    </div>
                </nav>
                <?php endif; ?>
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
                    <p class="text-sm text-gray-500" id="modalDeleteMessage">Are you sure you want to delete this article? This action cannot be undone.</p>
                </div>
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                    <button id="confirmDeleteArticleBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
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
                const cleanUrl = window.location.pathname + (urlParams.has('page') ? '?page=' + urlParams.get('page') : ''); // Keep page param if exists
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        });

        // Delete Confirmation Modal
        const deleteModal = document.getElementById('deleteConfirmationModal');
        const modalDeleteTitle = document.getElementById('modalDeleteTitle');
        const modalDeleteMessage = document.getElementById('modalDeleteMessage');
        const confirmDeleteBtn = document.getElementById('confirmDeleteArticleBtn');
        let articleActionUrl = '';

        function confirmDeleteArticle(id, title) {
            articleActionUrl = `submit_article.php?action=delete&id=${id}`; // This should point to your delete handler
            if (deleteModal && modalDeleteTitle && modalDeleteMessage) {
                modalDeleteTitle.textContent = 'Confirm Article Deletion';
                modalDeleteMessage.innerHTML = `Are you sure you want to delete the article: <br><strong>"${title}"</strong>? <br><br>This action cannot be undone.`;
                deleteModal.classList.remove('hidden');
            }
        }

        function closeDeleteModal() {
            if (deleteModal) deleteModal.classList.add('hidden');
            articleActionUrl = '';
        }

        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                if (articleActionUrl) {
                    window.location.href = articleActionUrl; // Redirect to the delete handler
                }
            });
        }
    </script>
</body>
</html>
