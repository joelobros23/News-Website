<?php
// admin/includes/functions.php

// Ensure db_connect.php is included. If this file is always included after db_connect.php,
// you might not need this check, but it can prevent errors if used independently.
if (!isset($pdo) && !isset($mysqli)) {
    // Attempt to include it if not already. Adjust path if necessary.
    // require_once __DIR__ . '/db_connect.php'; 
    // If still not set, it's a critical error.
    // if (!isset($pdo) && !isset($mysqli)) {
    //     die("Database connection is not available in functions.php. Ensure db_connect.php is included correctly.");
    // }
}

// --- Session & Authentication Functions ---

/**
 * Checks if an admin user is currently logged in.
 * @return bool True if logged in, false otherwise.
 */
function isAdminLoggedIn(): bool {
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Ensure session is started
    }
    return isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true;
}

/**
 * Redirects to the login page if the admin is not logged in.
 * Optionally provide a redirect URL for after login.
 * @param string $redirectTo URL to redirect to after successful login (optional).
 */
function requireAdminLogin(string $redirectTo = ''): void {
    if (!isAdminLoggedIn()) {
        $queryString = '';
        if (!empty($redirectTo)) {
            $queryString = '?redirect=' . urlencode($redirectTo);
        } else {
            $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            if (strpos(basename($currentUrl), 'index.php') === false && strpos(basename($currentUrl), 'login.php') === false) {
                 $queryString = '?redirect=' . urlencode($currentUrl);
            }
        }
        header("location: index.php" . $queryString . (empty($queryString) ? '?' : '&') . "error=not_logged_in");
        exit;
    }
}

/**
 * Hashes a password using PHP's password_hash function.
 * @param string $password The plain text password.
 * @return string The hashed password.
 */
function hashAdminPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifies a password against a hash.
 * @param string $password The plain text password.
 * @param string $hashedPassword The stored hashed password.
 * @return bool True if the password matches, false otherwise.
 */
function verifyAdminPassword(string $password, string $hashedPassword): bool {
    return password_verify($password, $hashedPassword);
}


// --- Data Sanitization & Validation Functions ---

/**
 * Sanitizes string input to prevent XSS.
 * @param string $input The string to sanitize.
 * @return string The sanitized string.
 */
function sanitizeString(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generates a URL-friendly slug from a string.
 * @param string $text The text to slugify.
 * @return string The generated slug.
 */
function generateSlug(string $text): string {
    $text = strip_tags($text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a-' . substr(md5(time()), 0, 6);
    }
    return $text;
}

// --- Article Related Functions (Examples using PDO) ---

/**
 * Fetches an article by its ID.
 * @param PDO $pdo The PDO database connection object.
 * @param int $id The article ID.
 * @return array|false The article data as an associative array, or false if not found.
 */
function getArticleById(PDO $pdo, int $id): array|false {
    try {
        $stmt = $pdo->prepare("SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name 
                               FROM articles a
                               LEFT JOIN categories c ON a.category_id = c.id
                               LEFT JOIN admins adm ON a.admin_id = adm.id
                               WHERE a.id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getArticleById: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches an article by its slug.
 * @param PDO $pdo The PDO database connection object.
 * @param string $slug The article slug.
 * @return array|false The article data as an associative array, or false if not found.
 */
function getArticleBySlug(PDO $pdo, string $slug): array|false {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name 
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN admins adm ON a.admin_id = adm.id
            WHERE a.slug = :slug AND a.status = 'published' 
            LIMIT 1 
        "); // Ensure only published articles are fetched by slug directly
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        // If an article is found, you might want to increment its view count here (optional)
        // if ($article) {
        //     $updateViewsStmt = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = :id");
        //     $updateViewsStmt->bindParam(':id', $article['id'], PDO::PARAM_INT);
        //     $updateViewsStmt->execute();
        // }
        return $article;

    } catch (PDOException $e) {
        error_log("Error in getArticleBySlug (slug: $slug): " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches all articles, optionally with pagination.
 * @param PDO $pdo The PDO database connection object.
 * @param int $limit Number of articles per page.
 * @param int $offset Offset for pagination.
 * @return array An array of articles.
 */
function getAllArticles(PDO $pdo, int $limit = 10, int $offset = 0): array {
    try {
        $sql = "SELECT a.*, c.name as category_name, adm.username as author_name 
                FROM articles a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN admins adm ON a.admin_id = adm.id
                ORDER BY a.publish_date DESC, a.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllArticles: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches recent articles.
 * This is the function that was missing.
 * @param PDO $pdo The PDO database connection object.
 * @param int $count Number of recent articles to fetch.
 * @return array An array of recent articles.
 */
function getRecentArticles(PDO $pdo, int $count = 5): array {
    try {
        // Fetches only 'published' articles, ordered by publish_date
        $sql = "SELECT a.id, a.title, a.slug, a.status, a.publish_date, 
                       c.name as category_name, adm.username as author_name
                FROM articles a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN admins adm ON a.admin_id = adm.id
                WHERE a.status = 'published' 
                ORDER BY a.publish_date DESC, a.created_at DESC
                LIMIT :count";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':count', $count, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getRecentArticles: " . $e->getMessage());
        return []; // Return an empty array on error
    }
}


/**
 * Counts the total number of articles.
 * @param PDO $pdo The PDO database connection object.
 * @param string|null $status Filter by status (e.g., 'published', 'draft').
 * @return int Total count.
 */
function countTotalArticles(PDO $pdo, ?string $status = null): int {
    try {
        $sql = "SELECT COUNT(*) FROM articles";
        if ($status !== null) {
            $sql .= " WHERE status = :status";
        }
        $stmt = $pdo->prepare($sql);
        if ($status !== null) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in countTotalArticles: " . $e->getMessage());
        return 0;
    }
}


// --- Category Related Functions (Examples using PDO) ---

/**
 * Fetches all categories.
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of categories.
 */
function getAllCategories(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllCategories: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches a category by its ID.
 * @param PDO $pdo
 * @param int $id
 * @return array|false
 */
function getCategoryById(PDO $pdo, int $id): array|false {
    try {
        $stmt = $pdo->prepare("SELECT id, name, slug, description FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCategoryById: " . $e->getMessage());
        return false;
    }
}


// --- Tag Related Functions (Examples using PDO) ---

/**
 * Fetches all tags.
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of tags.
 */
function getAllTags(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, name, slug FROM tags ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllTags: " . $e->getMessage());
        return [];
    }
}

/**
 * Gets or creates tags from a comma-separated string.
 * Returns an array of tag IDs.
 * @param PDO $pdo
 * @param string $tagString Comma-separated list of tag names.
 * @return array Array of tag IDs.
 */
function processTags(PDO $pdo, string $tagString): array {
    $tagNames = array_map('trim', explode(',', $tagString));
    $tagNames = array_filter($tagNames); 
    $tagIds = [];

    if (empty($tagNames)) {
        return [];
    }

    foreach ($tagNames as $tagName) {
        if (empty($tagName)) continue;

        $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = :name");
        $stmt->bindParam(':name', $tagName, PDO::PARAM_STR);
        $stmt->execute();
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tag) {
            $tagIds[] = $tag['id'];
        } else {
            $slug = generateSlug($tagName);
            $insertStmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
            $insertStmt->bindParam(':name', $tagName, PDO::PARAM_STR);
            $insertStmt->bindParam(':slug', $slug, PDO::PARAM_STR);
            try {
                $insertStmt->execute();
                $tagIds[] = $pdo->lastInsertId();
            } catch (PDOException $e) {
                error_log("Error creating tag '$tagName': " . $e->getMessage());
                $stmt->execute(); 
                $tag = $stmt->fetch(PDO::FETCH_ASSOC);
                if($tag) $tagIds[] = $tag['id'];
            }
        }
    }
    return array_unique($tagIds);
}

/**
 * Associates tags with an article in the article_tags pivot table.
 * @param PDO $pdo
 * @param int $articleId
 * @param array $tagIds Array of tag IDs to associate.
 * @return bool True on success, false on failure.
 */
function associateTagsWithArticle(PDO $pdo, int $articleId, array $tagIds): bool {
    try {
        $deleteStmt = $pdo->prepare("DELETE FROM article_tags WHERE article_id = :article_id");
        $deleteStmt->bindParam(':article_id', $articleId, PDO::PARAM_INT);
        $deleteStmt->execute();

        if (empty($tagIds)) {
            return true; 
        }

        $insertSql = "INSERT INTO article_tags (article_id, tag_id) VALUES ";
        $placeholders = [];
        $values = [];
        foreach ($tagIds as $tagId) {
            $placeholders[] = "(:article_id_{$tagId}, :tag_id_{$tagId})";
            $values["article_id_{$tagId}"] = $articleId;
            $values["tag_id_{$tagId}"] = (int)$tagId;
        }
        $insertSql .= implode(", ", $placeholders);
        
        $stmt = $pdo->prepare($insertSql);
        return $stmt->execute($values);

    } catch (PDOException $e) {
        error_log("Error associating tags with article $articleId: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches tags for a specific article.
 * @param PDO $pdo
 * @param int $articleId
 * @return array Array of tag names or an array of tag objects.
 */
function getTagsForArticle(PDO $pdo, int $articleId): array {
    try {
        $stmt = $pdo->prepare("SELECT t.id, t.name, t.slug 
                               FROM tags t
                               JOIN article_tags at ON t.id = at.tag_id
                               WHERE at.article_id = :article_id
                               ORDER BY t.name ASC");
        $stmt->bindParam(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching tags for article $articleId: " . $e->getMessage());
        return [];
    }
}


// --- Utility Functions ---

/**
 * Creates a short excerpt from text.
 * @param string $text The input text.
 * @param int $length The desired length of the excerpt.
 * @param string $suffix Suffix to append if text is truncated.
 * @return string The excerpt.
 */
function createExcerpt(string $htmlContent, int $length = 150, string $suffix = '...'): string {
    // 1. Decode HTML entities first to get actual characters (e.g., &ldquo; -> “)
    $text = html_entity_decode($htmlContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // 2. Strip all HTML tags to get plain text
    $text = strip_tags($text);
    
    // 3. Normalize multiple spaces (including newlines that became spaces) into single spaces and trim
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (mb_strlen($text) > $length) {
        $excerptText = mb_substr($text, 0, $length);
        // Try to cut at the last space to avoid breaking words
        $lastSpace = mb_strrpos($excerptText, ' ');
        if ($lastSpace !== false) {
            $excerptText = mb_substr($excerptText, 0, $lastSpace);
        }
        return $excerptText . $suffix;
    }
    return $text;
}

/**
 * Handles file uploads.
 *
 * @param array $file The $_FILES['input_name'] array.
 * @param string $uploadDir The directory to upload the file to (e.g., 'uploads/images/').
 * @param array $allowedTypes Allowed MIME types (e.g., ['image/jpeg', 'image/png']).
 * @param int $maxSize Maximum file size in bytes.
 * @return string|array Returns the path to the uploaded file on success, or an error array on failure.
 */
function handleFileUpload(array $file, string $uploadDir, array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], int $maxSize = 2 * 1024 * 1024): string|array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['error' => true, 'message' => 'Invalid file upload parameters.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['error' => true, 'message' => 'No file sent.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['error' => true, 'message' => 'Exceeded filesize limit.'];
        default:
            return ['error' => true, 'message' => 'Unknown errors.'];
    }

    if ($file['size'] > $maxSize) {
        return ['error' => true, 'message' => 'Exceeded filesize limit (' . ($maxSize / 1024 / 1024) . 'MB).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $fileMimeType = $finfo->file($file['tmp_name']);
    if (!in_array($fileMimeType, $allowedTypes)) {
        return ['error' => true, 'message' => 'Invalid file format. Allowed types: ' . implode(', ', $allowedTypes)];
    }

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) { 
            return ['error' => true, 'message' => 'Failed to create upload directory.'];
        }
    }
    if (!is_writable($uploadDir)) {
         return ['error' => true, 'message' => 'Upload directory is not writable.'];
    }
    
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeFileName = generateSlug(pathinfo($file['name'], PATHINFO_FILENAME)); 
    $newFileName = $safeFileName . '_' . uniqid() . '.' . $fileExtension;
    $filePath = rtrim($uploadDir, '/') . '/' . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['error' => true, 'message' => 'Failed to move uploaded file.'];
    }

    return $filePath; 
}

if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    // http_response_code(403); 
    // die("Direct access not allowed.");
}


/**
 * Counts the total number of categories.
 * @param PDO $pdo The PDO database connection object.
 * @return int Total count.
 */
function countTotalCategories(PDO $pdo): int {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in countTotalCategories: " . $e->getMessage());
        return 0;
    }
}

/**
 * Counts the total number of tags.
 * @param PDO $pdo The PDO database connection object.
 * @return int Total count.
 */
function countTotalTags(PDO $pdo): int {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM tags");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in countTotalTags: " . $e->getMessage());
        return 0;
    }
}

/**
 * Counts the total number of admin users.
 * @param PDO $pdo The PDO database connection object.
 * @return int Total count.
 */
function countTotalAdminUsers(PDO $pdo): int {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in countTotalAdminUsers: " . $e->getMessage());
        return 0;
    }
}

function getTagById(PDO $pdo, int $id): array|false {
    try {
        $stmt = $pdo->prepare("SELECT id, name, slug FROM tags WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getTagById: " . $e->getMessage());
        return false;
    }
}

// Add to admin/includes/functions.php
function countArticlesWithTag(PDO $pdo, int $tagId): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM article_tags WHERE tag_id = :tag_id");
        $stmt->bindParam(':tag_id', $tagId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in countArticlesWithTag for tag ID $tagId: " . $e->getMessage());
        return 0;
    }
}

/**
 * Fetches a category by its slug.
 * @param PDO $pdo The PDO database connection object.
 * @param string $slug The category slug.
 * @return array|false The category data as an associative array, or false if not found.
 */
function getCategoryBySlug(PDO $pdo, string $slug): array|false {
    try {
        $stmt = $pdo->prepare("SELECT id, name, slug, description FROM categories WHERE slug = :slug LIMIT 1");
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCategoryBySlug (slug: $slug): " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches articles belonging to a specific category slug, with pagination.
 * @param PDO $pdo The PDO database connection object.
 * @param string $categorySlug The slug of the category.
 * @param int $limit Number of articles per page.
 * @param int $offset Offset for pagination.
 * @return array An array of articles.
 */
function getArticlesByCategorySlug(PDO $pdo, string $categorySlug, int $limit = 10, int $offset = 0): array {
    try {
        // Ensure you select all necessary fields, including category_slug for links if needed
        $sql = "SELECT a.*, c.name as category_name, c.slug as category_slug_from_join, adm.username as author_name 
                FROM articles a
                JOIN categories c ON a.category_id = c.id
                LEFT JOIN admins adm ON a.admin_id = adm.id
                WHERE c.slug = :category_slug AND a.status = 'published'
                ORDER BY a.publish_date DESC, a.created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':category_slug', $categorySlug, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getArticlesByCategorySlug (slug: $categorySlug): " . $e->getMessage());
        return [];
    }
}

/**
 * Counts the total number of published articles in a specific category by slug.
 * @param PDO $pdo The PDO database connection object.
 * @param string $categorySlug The slug of the category.
 * @return int Total count of articles.
 */
function countArticlesByCategorySlug(PDO $pdo, string $categorySlug): int {
    try {
        $sql = "SELECT COUNT(a.id) 
                FROM articles a
                JOIN categories c ON a.category_id = c.id
                WHERE c.slug = :category_slug AND a.status = 'published'";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':category_slug', $categorySlug, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in countArticlesByCategorySlug (slug: $categorySlug): " . $e->getMessage());
        return 0;
    }
}

/**
 * Searches for tags whose names match the query.
 * Case-insensitive search.
 * @param PDO $pdo The PDO database connection object.
 * @param string $query The search query.
 * @param int $limit Max number of tags to return.
 * @return array An array of matching tags.
 */
function searchMatchingTags(PDO $pdo, string $searchQuery, int $limit = 5): array {
    try {
        $searchTerm = '%' . strtolower($searchQuery) . '%'; // Prepare for LIKE and case-insensitivity
        $stmt = $pdo->prepare("SELECT id, name, slug FROM tags WHERE LOWER(name) LIKE :query ORDER BY name ASC LIMIT :limit");
        $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in searchMatchingTags (query: $searchQuery): " . $e->getMessage());
        return [];
    }
}

/**
 * Searches articles by title and content.
 * Returns distinct articles with pagination. Case-insensitive.
 * SIMPLIFIED VERSION FOR DEBUGGING.
 * @param PDO $pdo The PDO database connection object.
 * @param string $searchQuery The search query.
 * @param int $limit Number of articles per page.
 * @param int $offset Offset for pagination.
 * @return array An array of matching articles.
 */
function searchArticlesComprehensive(PDO $pdo, string $searchQuery, int $limit = 10, int $offset = 0): array {
    // --- Start Debugging ---
    error_log("--- searchArticlesComprehensive (SIMPLIFIED) ---");
    error_log("Original Search Query: " . $searchQuery);
    // --- End Debugging ---

    try {
        $searchTermWithWildcards = '%' . strtolower($searchQuery) . '%';

        // --- Debugging Parameters ---
        error_log("Search Term with Wildcards (for LIKE): " . $searchTermWithWildcards);
        // --- End Debugging Parameters ---

        $sql = "
            SELECT 
                a.*, 
                c.name as category_name, 
                c.slug as category_slug, 
                adm.username as author_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN admins adm ON a.admin_id = adm.id
            WHERE a.status = 'published' AND (
                LOWER(a.title) LIKE :search_term_wildcards 
                OR LOWER(a.content) LIKE :search_term_wildcards 
                /* OR EXISTS (
                    SELECT 1 
                    FROM article_tags at
                    JOIN tags t ON at.tag_id = t.id
                    WHERE at.article_id = a.id AND LOWER(t.name) LIKE :search_term_wildcards
                ) -- Tag searching temporarily removed for simplification */
            )
            ORDER BY a.publish_date DESC, a.created_at DESC -- Simplified ordering
            LIMIT :limit OFFSET :offset
        ";
        
        // --- Debugging SQL ---
        error_log("Simplified Search SQL: " . $sql);
        // --- End Debugging SQL ---
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':search_term_wildcards', $searchTermWithWildcards, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // --- Debugging Results ---
        error_log("Number of articles found by simplified query: " . count($articles));
        if (count($articles) > 0) {
            error_log("First article found (simplified): " . print_r($articles[0]['id'] . " - " . $articles[0]['title'], true));
        } else {
            error_log("No articles found by simplified query for term: " . $searchTermWithWildcards);
        }
        // --- End Debugging Results ---
        
        $enrichedArticles = [];
        foreach ($articles as $article) {
            $article['tags'] = getTagsForArticle($pdo, $article['id']); // Still get tags for display
            $enrichedArticles[] = $article;
        }
        return $enrichedArticles;

    } catch (PDOException $e) {
        error_log("Error in searchArticlesComprehensive (SIMPLIFIED) (query: $searchQuery): " . $e->getMessage());
        return [];
    }
}

/**
 * Counts total articles for a simplified search (title, content).
 * @param PDO $pdo The PDO database connection object.
 * @param string $searchQuery The search query.
 * @return int Total count of matching articles.
 */
function countSearchedArticlesComprehensive(PDO $pdo, string $searchQuery): int {
    try {
        $searchTermWithWildcards = '%' . strtolower($searchQuery) . '%';
        
        $sql = "
            SELECT COUNT(DISTINCT a.id)
            FROM articles a
            WHERE a.status = 'published' AND (
                LOWER(a.title) LIKE :search_term_wildcards 
                OR LOWER(a.content) LIKE :search_term_wildcards
                /* OR EXISTS (
                    SELECT 1 
                    FROM article_tags at
                    JOIN tags t ON at.tag_id = t.id
                    WHERE at.article_id = a.id AND LOWER(t.name) LIKE :search_term_wildcards
                ) -- Tag searching temporarily removed for simplification */
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':search_term_wildcards', $searchTermWithWildcards, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in countSearchedArticlesComprehensive (SIMPLIFIED) (query: $searchQuery): " . $e->getMessage());
        return 0;
    }
}

?>
