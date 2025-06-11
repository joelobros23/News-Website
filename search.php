<?php
// search.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/admin/includes/functions.php'; // Provides helper functions

$searchQuery = trim($_GET['query'] ?? '');
$pageTitle = "Search Results for \"" . htmlspecialchars($searchQuery) . "\" - News Week";

// Fetch Categories for Menu
$menuCategories = getAllCategories($pdo);

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$articlesPerPage = 9; // Number of articles per page
$offset = ($page - 1) * $articlesPerPage;

$matchingTags = [];
$searchedArticles = [];
$totalSearchedArticles = 0;
$totalPages = 0;

if (!empty($searchQuery)) {
    // Fetch tags that match the search query
    $matchingTags = searchMatchingTags($pdo, $searchQuery, 10); // Limit to 10 matching tags displayed

    // Fetch articles that match the search query (title, content excerpt, or associated tag name)
    $searchedArticles = searchArticlesComprehensive($pdo, $searchQuery, $articlesPerPage, $offset);
    $totalSearchedArticles = countSearchedArticlesComprehensive($pdo, $searchQuery);
    $totalPages = ($articlesPerPage > 0 && $totalSearchedArticles > 0) ? ceil($totalSearchedArticles / $articlesPerPage) : 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
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
        .tag-search-result { @apply inline-block bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm mr-2 mb-2 hover:bg-indigo-200 transition; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Header -->
<?php require_once __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8">
        <section class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-1">
                Search Results for: "<?php echo htmlspecialchars($searchQuery); ?>"
            </h1>
            <p class="text-gray-600">
                <?php echo $totalSearchedArticles; ?> article(s) found.
                <?php if (!empty($matchingTags)) echo count($matchingTags) . " related tag(s) also found."; ?>
            </p>
        </section>

        <!-- Search Bar (repeated for convenience) -->
        <section class="mb-8">
            <form action="search.php" method="GET" class="flex">
                <input 
                    type="text" 
                    name="query" 
                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                    placeholder="Search again..." 
                    class="w-full p-3 rounded-l-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button 
                    type="submit"
                    class="bg-blue-500 text-white p-3 rounded-r-md hover:bg-blue-600 transition duration-200"
                >
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </section>

        <!-- Display Matching Tags -->
        <?php if (!empty($matchingTags)): ?>
            <section class="mb-10 p-4 bg-blue-50 rounded-lg">
                <h2 class="text-xl font-semibold text-blue-700 mb-3">Related Tags:</h2>
                <div>
                    <?php foreach($matchingTags as $tag): ?>
                        <a href="tag.php?slug=<?php echo htmlspecialchars($tag['slug']); ?>" class="tag-search-result">
                            <i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($tag['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>


        <!-- Searched Articles Grid -->
        <?php if (!empty($searchedArticles)): ?>
        <section>
            <h2 class="text-2xl font-semibold mb-6 text-gray-800">Matching Articles:</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($searchedArticles as $article): ?>
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
                           <?php if (!empty($article['category_name'])): ?>
                            <span class="mx-1">|</span> <i class="fas fa-folder-open mr-1"></i> <a href="category.php?slug=<?php echo htmlspecialchars($article['category_slug']); ?>" class="hover:text-blue-500"><?php echo htmlspecialchars($article['category_name']); ?></a>
                           <?php endif; ?>
                        </div>
                        <p class="text-gray-700 text-sm mb-3 leading-relaxed line-clamp-3 flex-grow">
                            <?php echo htmlspecialchars(createExcerpt($article['content'], 120)); ?>
                        </p>
                        <?php if (!empty($article['tags'])): ?>
                        <div class="mb-3">
                            <span class="text-xs font-semibold mr-1">Tags:</span>
                            <?php foreach ($article['tags'] as $index => $tag_item): // Renamed to avoid conflict ?>
                                <?php if($index < 3): ?>
                                <a href="tag.php?slug=<?php echo htmlspecialchars($tag_item['slug']); ?>" 
                                   class="bg-gray-100 text-gray-600 hover:bg-gray-200 px-2 py-1 rounded-full text-xs transition duration-150 ease-in-out inline-block mr-1 mb-1">
                                   <?php echo htmlspecialchars($tag_item['name']); ?>
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
                        <span class="font-medium"><?php echo min($offset + $articlesPerPage, $totalSearchedArticles); ?></span>
                        of
                        <span class="font-medium"><?php echo $totalSearchedArticles; ?></span>
                        results
                    </p>
                </div>
                <div class="flex-1 flex justify-between sm:justify-end pagination space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?query=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page - 1; ?>" class="relative">Previous</a>
                    <?php else: ?>
                        <span class="relative disabled">Previous</span>
                    <?php endif; ?>
                    
                    <?php
                    $linksToShow = 5; 
                    $start = max(1, $page - floor($linksToShow / 2));
                    $end = min($totalPages, $page + floor($linksToShow / 2));

                    if ($start > 1) {
                        echo '<a href="?query='.urlencode($searchQuery).'&page=1">1</a>';
                        if ($start > 2) echo '<span class="disabled">...</span>';
                    }
                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?query=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'current' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor;
                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) echo '<span class="disabled">...</span>';
                        echo '<a href="?query='.urlencode($searchQuery).'&page='.$totalPages.'">'.$totalPages.'</a>';
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?query=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page + 1; ?>" class="relative">Next</a>
                    <?php else: ?>
                        <span class="relative disabled">Next</span>
                    <?php endif; ?>
                </div>
            </nav>
            <?php endif; ?>

        </section>
        <?php elseif (!empty($searchQuery) && empty($matchingTags)): // Searched but no articles or tags found ?>
            <p class="text-center text-gray-600 text-lg py-10">No articles or tags found matching your search criteria "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>".</p>
        <?php elseif (empty($searchQuery)): // Page loaded without a search query ?>
            <p class="text-center text-gray-600 text-lg py-10">Please enter a term in the search bar above to find articles.</p>
        <?php endif; ?>
    </main>

    <!-- Footer -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>
