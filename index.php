<?php
// index.php (Homepage)
if (session_status() == PHP_SESSION_NONE) {
    session_start(); 
}

require_once __DIR__ . '/admin/includes/db_connect.php'; 
require_once __DIR__ . '/admin/includes/functions.php'; 

$menuCategories = getAllCategories($pdo); 

$featuredArticle = null;
try {
    $stmt_featured = $pdo->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name 
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN admins adm ON a.admin_id = adm.id
        WHERE a.status = 'published'
        ORDER BY a.publish_date DESC, a.created_at DESC
        LIMIT 1
    ");
    $stmt_featured->execute();
    $featuredArticle = $stmt_featured->fetch(PDO::FETCH_ASSOC);
    if ($featuredArticle) {
        $featuredArticle['tags'] = getTagsForArticle($pdo, $featuredArticle['id']);
    }
} catch (PDOException $e) {
    error_log("Error fetching featured article: " . $e->getMessage());
}

$latestArticlesLimit = 6; 
$latestArticles = [];
try {
    $sql_latest = "
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name 
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN admins adm ON a.admin_id = adm.id
        WHERE a.status = 'published'";
    
    if ($featuredArticle) {
        $sql_latest .= " AND a.id != :featured_article_id";
    }
    
    $sql_latest .= " ORDER BY a.publish_date DESC, a.created_at DESC LIMIT :limit";
    
    $stmt_latest = $pdo->prepare($sql_latest);
    if ($featuredArticle) {
        $stmt_latest->bindParam(':featured_article_id', $featuredArticle['id'], PDO::PARAM_INT);
    }
    $stmt_latest->bindParam(':limit', $latestArticlesLimit, PDO::PARAM_INT);
    $stmt_latest->execute();
    $latestArticlesResults = $stmt_latest->fetchAll(PDO::FETCH_ASSOC);

    foreach ($latestArticlesResults as $article_item) { // Renamed to avoid conflict with $article in loops
        $article_item['tags'] = getTagsForArticle($pdo, $article_item['id']);
        $latestArticles[] = $article_item;
    }

} catch (PDOException $e) {
    error_log("Error fetching latest articles: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Week - Your Daily Digest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css"> 
    <style>
        body { font-family: 'Inter', sans-serif; }
        .line-clamp-3 {
            overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3;
            /* max-height: calc(1.5em * 3); Consider line-height if using this */
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Header -->
<?php require_once __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8">

        <section class="mb-8">
            <form action="search.php" method="GET" class="flex">
                <input 
                    type="text" 
                    name="query" 
                    placeholder="Search articles by keyword or tag..." 
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

        <?php if ($featuredArticle): ?>
        <section class="mb-12 p-6 bg-white rounded-lg shadow-lg">
            <!-- Ensure title is a block element and explicitly bold -->
            <h2 class="block text-3xl md:text-4xl font-bold mb-4 text-gray-900">
                <a href="article.php?slug=<?php echo htmlspecialchars($featuredArticle['slug']); ?>" class="hover:text-blue-600 font-bold"> 
                    <?php echo htmlspecialchars($featuredArticle['title']); ?>
                </a>
            </h2>
            
            <?php if (!empty($featuredArticle['image_url'])): ?>
                <a href="article.php?slug=<?php echo htmlspecialchars($featuredArticle['slug']); ?>" class="block mb-4">
                    <img src="<?php echo htmlspecialchars($featuredArticle['image_url']); ?>" alt="<?php echo htmlspecialchars($featuredArticle['title']); ?>" 
                         onerror="this.onerror=null;this.src='https://placehold.co/1200x600/E2E8F0/4A5568?text=Image+Not+Available';"
                         class="w-full h-auto max-h-[500px] object-cover rounded-md">
                </a>
            <?php elseif ($featuredArticle): // Show placeholder only if featured article exists but image doesn't ?>
                <a href="article.php?slug=<?php echo htmlspecialchars($featuredArticle['slug']); ?>" class="block mb-4">
                    <img src="https://placehold.co/1200x600/E2E8F0/4A5568?text=<?php echo urlencode(htmlspecialchars($featuredArticle['title'])); ?>" 
                         alt="<?php echo htmlspecialchars($featuredArticle['title']); ?>" 
                         class="w-full h-auto max-h-[500px] object-cover rounded-md">
                </a>
            <?php endif; ?>
            
            <!-- Ensure excerpt is a block element and styled distinctly -->
            <p class="block text-gray-700 mb-4 leading-relaxed line-clamp-3">
                <?php echo htmlspecialchars(createExcerpt($featuredArticle['content'], 250)); ?>
            </p>
            
            <div class="flex flex-wrap items-center text-sm text-gray-500 mb-2 gap-x-2">
                <span><i class="fas fa-calendar-alt mr-1"></i> Published on <?php echo date('F j, Y', strtotime($featuredArticle['publish_date'])); ?></span>
                <?php if (!empty($featuredArticle['author_name'])): ?>
                <span>| <i class="fas fa-user mr-1"></i> By <?php echo htmlspecialchars($featuredArticle['author_name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($featuredArticle['category_name'])): ?>
                <span>| <i class="fas fa-folder-open mr-1"></i> <a href="category.php?slug=<?php echo htmlspecialchars($featuredArticle['category_slug']); ?>" class="hover:text-blue-500"><?php echo htmlspecialchars($featuredArticle['category_name']); ?></a></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($featuredArticle['tags'])): ?>
            <div class="my-4">
                <span class="text-sm font-semibold mr-2">Tags:</span>
                <?php foreach ($featuredArticle['tags'] as $tag): ?>
                    <a href="tag.php?slug=<?php echo htmlspecialchars($tag['slug']); ?>" 
                       class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-2 py-1 rounded-full text-xs transition duration-150 ease-in-out inline-block mr-1 mb-1">
                       <?php echo htmlspecialchars($tag['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <a href="article.php?slug=<?php echo htmlspecialchars($featuredArticle['slug']); ?>" class="text-blue-500 hover:text-blue-700 font-semibold inline-block mt-2">Read More &rarr;</a> {/* Added inline-block and mt-2 */}
        </section>
        <?php else: ?>
            <p class="text-center text-gray-600 text-lg mb-12">No featured articles available at the moment. Check back soon!</p>
        <?php endif; ?>

        <section>
            <h2 class="text-3xl font-semibold mb-6 text-gray-800 border-b-2 border-blue-500 pb-2">Latest News</h2>
            <?php if (!empty($latestArticles)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($latestArticles as $article): ?>
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
                            <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="hover:text-blue-600 font-bold"> {/* Added font-bold to link */}
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
                            <?php foreach ($article['tags'] as $index => $tag): ?>
                                <?php if($index < 3): ?>
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
            <?php else: ?>
                <p class="text-center text-gray-600 text-lg">No more articles to display right now. Please check back later!</p>
            <?php endif; ?>
        </section>

    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script src="js/script.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>