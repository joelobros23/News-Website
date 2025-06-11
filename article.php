<?php
// article.php (Single Article Page)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/admin/includes/functions.php'; // Provides helper functions

// Get the slug from the URL
$articleSlug = $_GET['slug'] ?? null;

if (!$articleSlug) {
    header("Location: index.php?error=no_article_slug");
    exit;
}

$article = getArticleBySlug($pdo, $articleSlug);

if (!$article) {
    http_response_code(404);
    $pageTitle = "Article Not Found";
} else {
    $pageTitle = htmlspecialchars($article['title']) . " - News Week";
    $article['tags'] = getTagsForArticle($pdo, $article['id']);
}

$menuCategories = getAllCategories($pdo);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? "News Week - Article"; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css"> <!-- Your custom CSS -->
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .tag-link { 
            @apply px-2 py-1 rounded-full text-xs transition duration-150 ease-in-out; 
        }
        /* The `@tailwindcss/typography` plugin (included with the Tailwind CDN) 
           is activated by the `prose` class on the parent `div.article-content`.
           It will automatically style HTML elements like h1, p, ul, strong, em, etc.
           If you need to further customize these styles beyond what `prose` offers,
           add standard CSS rules here or in your css/style.css file.
           For example:
           .article-content h1 {
               color: #1a202c; // Example: Change h1 color
           }
           .article-content p {
               line-height: 1.75; // Example: Adjust paragraph line height
           }
        */
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Header -->
<?php require_once __DIR__ . '/includes/header.php'; ?>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8">
        <?php if ($article): ?>
            <article class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
                <header class="mb-6 border-b pb-4">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-3"><?php echo htmlspecialchars($article['title']); ?></h1>
                    <div class="text-sm text-gray-500 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span>Published on <?php echo date('F j, Y \a\t g:i a', strtotime($article['publish_date'])); ?></span>
                        <?php if (!empty($article['author_name'])): ?>
                            <span>| By <span class="font-medium"><?php echo htmlspecialchars($article['author_name']); ?></span></span>
                        <?php endif; ?>
                        <?php if (!empty($article['category_name'])): ?>
                            <span>| In <a href="category.php?slug=<?php echo htmlspecialchars($article['category_slug']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($article['category_name']); ?></a></span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if (!empty($article['image_url'])): ?>
                    <figure class="mb-6">
                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>"
                             onerror="this.onerror=null;this.src='https://placehold.co/1200x600/E2E8F0/4A5568?text=Image+Not+Available';"
                             class="w-full h-auto max-h-[600px] object-contain rounded-md shadow-md">
                    </figure>
                <?php endif; ?>

                <!-- The `prose` class will style the HTML content from TinyMCE -->
<div class="article-content prose lg:prose-xl max-w-none">
    <?php echo $article['content']; ?>
</div>

                <?php if (!empty($article['tags'])): ?>
                <footer class="mt-8 pt-6 border-t">
                    <span class="text-md font-semibold mr-2 text-gray-700">Tags:</span>
                    <?php foreach ($article['tags'] as $tag): ?>
                        <a href="tag.php?slug=<?php echo htmlspecialchars($tag['slug']); ?>" 
                           class="tag-link bg-gray-200 text-gray-700 hover:bg-gray-300 mr-2 mb-2 inline-block">
                           <?php echo htmlspecialchars($tag['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </footer>
                <?php endif; ?>
            </article>
            
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle fa-4x text-yellow-500 mb-4"></i>
                <h1 class="text-4xl font-bold text-gray-700 mb-2">404 - Article Not Found</h1>
                <p class="text-gray-600 mb-6">Sorry, the article you are looking for does not exist or may have been moved.</p>
                <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-md shadow-md transition duration-150">
                    <i class="fas fa-home mr-2"></i> Go to Homepage
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>
