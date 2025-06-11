<?php
// category.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/admin/includes/functions.php'; // Provides helper functions

$categorySlug = $_GET['slug'] ?? null;

if (!$categorySlug) {
    header("Location: index.php?error=no_category_slug");
    exit;
}

// Fetch category details
$category = getCategoryBySlug($pdo, $categorySlug);

if (!$category) {
    http_response_code(404);
    $pageTitle = "Category Not Found";
    // You could include a 404.php page here
} else {
    $pageTitle = "Articles in " . htmlspecialchars($category['name']) . " - News Week";
}

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$articlesPerPage = 9; // Number of articles per page
$offset = ($page - 1) * $articlesPerPage;

$articlesInCategory = [];
$totalArticlesInCategory = 0;
if ($category) { // Only fetch articles if category was found
    $articlesInCategoryResults = getArticlesByCategorySlug($pdo, $categorySlug, $articlesPerPage, $offset);
    foreach ($articlesInCategoryResults as $article_item) {
        $article_item['tags'] = getTagsForArticle($pdo, $article_item['id']);
        $articlesInCategory[] = $article_item;
    }
    $totalArticlesInCategory = countArticlesByCategorySlug($pdo, $categorySlug);
}
$totalPages = ($articlesPerPage > 0 && $totalArticlesInCategory > 0) ? ceil($totalArticlesInCategory / $articlesPerPage) : 0;


// Fetch Categories for Menu (same as index.php)
$menuCategories = getAllCategories($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? "News Week"; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .line-clamp-3 {
            overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3;
        }
        .pagination a, .pagination span { @apply inline-flex items-center justify-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50; }
        .pagination span.current { @apply z-10 bg-blue-50 border-blue-500 text-blue-600; }
        .pagination a.disabled, .pagination span.disabled { @apply opacity-50 cursor-not-allowed; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Header -->
<?php require_once __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8">
        <?php if ($category): ?>
            <section class="mb-8">
                <h1 class="text-4xl font-bold text-gray-800 mb-2">
                    Category: <?php echo htmlspecialchars($category['name']); ?>
                </h1>
                <?php if (!empty($category['description'])): ?>
                    <p class="text-lg text-gray-600"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
            </section>

            <?php if (!empty($articlesInCategory)): ?>
            <section>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($articlesInCategory as $article): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col transform hover:scale-105 transition-transform duration-300">
                        <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="block">
                            <?php if (!empty($article['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                     onerror="this.onerror=null;this.src='https://placehold.co/600x400/CBD5E0/4A5568?text=Image+Missing';"
                                     class="w-full h-48 object-cover">
                            <?php else: ?>
                                 <img src="https://placehold.co/600x400/CBD5E0/4A5568?text=<?php echo urlencode(htmlspecialchars(createExcerpt($article['title'], 20))); ?>" 
                                      alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                      class="w-full h-48 object-cover">
                            <?php endif; ?>
                        </a>
                        <div class="p-6 flex flex-col flex-grow">
                            <h3 class="text-xl font-bold mb-2 text-gray-900">
                                <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="hover:text-blue-600 font-bold">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </h3>
                            <div class="flex items-center text-xs text-gray-500 mb-2">
                               <i class="fas fa-calendar-alt mr-1"></i> <?php echo date('M j, Y', strtotime($article['publish_date'])); ?>
                               <?php if (!empty($article['author_name'])): ?>
                                <span class="mx-1">|</span> <i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($article['author_name']); ?>
                               <?php endif; ?>
                            </div>
                            <p class="text-gray-700 text-sm mb-3 leading-relaxed line-clamp-3 flex-grow">
                                <?php echo htmlspecialchars(createExcerpt($article['content'], 120)); ?>
                            </p>
                            <?php if (!empty($article['tags'])): ?>
                            <div class="mb-3">
                                <span class="text-xs font-semibold mr-1">Tags:</span>
                                <?php foreach ($article['tags'] as $index => $tag): ?>
                                    <?php if($index < 3): // Show a few tags only ?>
                                    <a href="tag.php?slug=<?php echo htmlspecialchars($tag['slug']); ?>" 
                                       class="bg-gray-100 text-gray-600 hover:bg-gray-200 px-2 py-1 rounded-full text-xs transition duration-150 ease-in-out inline-block mr-1 mb-1">
                                       <?php echo htmlspecialchars($tag['name']); ?>
                                    </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="text-blue-500 hover:text-blue-700 text-sm font-semibold mt-auto">Read More &rarr;</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-10 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6" aria-label="Pagination">
                    <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium"><?php echo $offset + 1; ?></span>
                            to
                            <span class="font-medium"><?php echo min($offset + $articlesPerPage, $totalArticlesInCategory); ?></span>
                            of
                            <span class="font-medium"><?php echo $totalArticlesInCategory; ?></span>
                            results
                        </p>
                    </div>
                    <div class="flex-1 flex justify-between sm:justify-end pagination space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?slug=<?php echo htmlspecialchars($categorySlug); ?>&page=<?php echo $page - 1; ?>" class="relative">Previous</a>
                        <?php else: ?>
                            <span class="relative disabled">Previous</span>
                        <?php endif; ?>
                        
                        <?php
                        $linksToShow = 5; 
                        $start = max(1, $page - floor($linksToShow / 2));
                        $end = min($totalPages, $page + floor($linksToShow / 2));

                        if ($start > 1) {
                            echo '<a href="?slug='.htmlspecialchars($categorySlug).'&page=1">1</a>';
                            if ($start > 2) echo '<span class="disabled">...</span>';
                        }
                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?slug=<?php echo htmlspecialchars($categorySlug); ?>&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'current' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor;
                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) echo '<span class="disabled">...</span>';
                            echo '<a href="?slug='.htmlspecialchars($categorySlug).'&page='.$totalPages.'">'.$totalPages.'</a>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?slug=<?php echo htmlspecialchars($categorySlug); ?>&page=<?php echo $page + 1; ?>" class="relative">Next</a>
                        <?php else: ?>
                            <span class="relative disabled">Next</span>
                        <?php endif; ?>
                    </div>
                </nav>
                <?php endif; ?>

            </section>
            <?php elseif($category): // Category exists but no articles ?>
                <p class="text-center text-gray-600 text-lg">No articles found in the "<?php echo htmlspecialchars($category['name']); ?>" category yet.</p>
            <?php endif; // End check for !empty($articlesInCategory) ?>

        <?php else: // Category not found ?>
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle fa-4x text-yellow-500 mb-4"></i>
                <h1 class="text-4xl font-bold text-gray-700 mb-2">404 - Category Not Found</h1>
                <p class="text-gray-600 mb-6">Sorry, the category "<?php echo htmlspecialchars($categorySlug); ?>" you are looking for does not exist.</p>
                <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-md shadow-md transition duration-150">
                    <i class="fas fa-home mr-2"></i> Go to Homepage
                </a>
            </div>
        <?php endif; // End check for $category ?>
    </main>

    <!-- Footer -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>
