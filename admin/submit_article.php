<?php
// admin/submit_article.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$action = $_GET['action'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? null; // Get admin ID from session

if (!$admin_id) {
    // Should not happen if requireAdminLogin() works, but as a safeguard
    $error_query_string = "?status=error&msg=" . urlencode("Admin user not identified. Please login again.");
    header("location: article_form.php" . $error_query_string);
    exit;
}

// --- CREATE OR UPDATE ARTICLE ---
if (($_SERVER["REQUEST_METHOD"] == "POST") && ($action === 'create' || $action === 'update')) {
    
    // --- Sanitize and retrieve form data ---
    $title = sanitizeString($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? ''); // Allow HTML, so sanitize output later
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $tags_string = sanitizeString($_POST['tags'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'draft');
    $existing_image_url = sanitizeString($_POST['existing_image_url'] ?? '');
    
    $article_id = null;
    if ($action === 'update') {
        $article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$article_id) {
            header("location: manage_articles.php?status=error&msg=" . urlencode("Invalid article ID for update."));
            exit;
        }
    }

    // --- Validate form data ---
    $errors = [];
    if (empty($title)) {
        $errors[] = "Article title is required.";
    }
    if (empty($content)) {
        $errors[] = "Article content is required.";
    }
    if ($category_id === false || $category_id <= 0) { // false if not int, or not positive
        $errors[] = "Please select a valid category.";
    }
    if (!in_array($status, ['draft', 'published', 'archived'])) {
        $errors[] = "Invalid status selected.";
    }

    if (!empty($errors)) {
        $errorMessage = implode("<br>", $errors);
        $redirectUrlBase = $action === 'create' ? "article_form.php" : "article_form.php?id=" . $article_id;
        $separator = (strpos($redirectUrlBase, '?') === false) ? '?' : '&';
        header("location: " . $redirectUrlBase . $separator . "status=error&msg=" . urlencode($errorMessage));
        exit;
    }

    // --- Handle Image Upload ---
    $image_url = $existing_image_url; // Default to existing image if updating
    $uploadDir = __DIR__ . '/../uploads/images/articles/'; // Define your upload directory relative to this script
    
    // Ensure the upload directory exists, create if not
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) { // Create directory recursively with appropriate permissions
            $errorMessage = "Failed to create image upload directory. Check permissions.";
            $redirectUrlBase = $action === 'create' ? "article_form.php" : "article_form.php?id=" . $article_id;
            $separator = (strpos($redirectUrlBase, '?') === false) ? '?' : '&';
            header("location: " . $redirectUrlBase . $separator . "status=error&msg=" . urlencode($errorMessage));
            exit;
        }
    }


    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] == UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['image_upload'], $uploadDir);
        if (is_array($uploadResult) && isset($uploadResult['error'])) {
            // Error during upload
            $errorMessage = "Image Upload Error: " . $uploadResult['message'];
            $redirectUrlBase = $action === 'create' ? "article_form.php" : "article_form.php?id=" . $article_id;
            $separator = (strpos($redirectUrlBase, '?') === false) ? '?' : '&';
            header("location: " . $redirectUrlBase . $separator . "status=error&msg=" . urlencode($errorMessage));
            exit;
        } else if (is_string($uploadResult)) {
            // Success, get the relative path from the webroot
            $image_url = '../uploads/images/articles/' . basename($uploadResult);
            
            if ($action === 'update' && !empty($existing_image_url) && $existing_image_url !== $image_url) {
                $oldImagePath = __DIR__ . '/../' . ltrim(str_replace('../', '', $existing_image_url), '/');
                if (file_exists($oldImagePath) && is_writable($oldImagePath)) {
                    unlink($oldImagePath); 
                }
            }
        }
    } elseif (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] != UPLOAD_ERR_NO_FILE) {
        $errorMessage = "Image Upload Error: Code " . $_FILES['image_upload']['error'];
        $redirectUrlBase = $action === 'create' ? "article_form.php" : "article_form.php?id=" . $article_id;
        $separator = (strpos($redirectUrlBase, '?') === false) ? '?' : '&';
        header("location: " . $redirectUrlBase . $separator . "status=error&msg=" . urlencode($errorMessage));
        exit;
    }


    // --- Generate Slug ---
    $slug = generateSlug($title);
    $originalSlug = $slug;
    $counter = 1;
    $slugIsUnique = false;
    while (!$slugIsUnique) {
        $sqlCheckSlug = "SELECT id FROM articles WHERE slug = :slug";
        if ($action === 'update' && $article_id) {
            $sqlCheckSlug .= " AND id != :article_id";
        }
        $stmtCheckSlug = $pdo->prepare($sqlCheckSlug);
        $stmtCheckSlug->bindParam(':slug', $slug);
        if ($action === 'update' && $article_id) {
            $stmtCheckSlug->bindParam(':article_id', $article_id, PDO::PARAM_INT);
        }
        $stmtCheckSlug->execute();
        if ($stmtCheckSlug->rowCount() == 0) {
            $slugIsUnique = true;
        } else {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }


    // --- Process Tags ---
    $tag_ids = processTags($pdo, $tags_string); 

    // --- Database Operation (Create or Update) ---
    try {
        $pdo->beginTransaction(); 

        $publish_date = ($status === 'published') ? date('Y-m-d H:i:s') : null;

        if ($action === 'create') {
            $sql = "INSERT INTO articles (title, slug, content, category_id, admin_id, image_url, status, publish_date, created_at, updated_at) 
                    VALUES (:title, :slug, :content, :category_id, :admin_id, :image_url, :status, :publish_date, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
        } else { 
            $sql = "UPDATE articles SET 
                    title = :title, 
                    slug = :slug, 
                    content = :content, 
                    category_id = :category_id, 
                    admin_id = :admin_id, 
                    image_url = :image_url, 
                    status = :status, 
                    publish_date = :publish_date, 
                    updated_at = NOW() 
                    WHERE id = :article_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':article_id', $article_id, PDO::PARAM_INT);
        }

        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $stmt->bindParam(':image_url', $image_url, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        if ($publish_date === null) {
            $stmt->bindValue(':publish_date', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':publish_date', $publish_date);
        }
        
        $stmt->execute();

        $current_article_id = ($action === 'create') ? $pdo->lastInsertId() : $article_id;

        if (!empty($tag_ids)) {
            associateTagsWithArticle($pdo, $current_article_id, $tag_ids); 
        } else {
            associateTagsWithArticle($pdo, $current_article_id, []); 
        }

        $pdo->commit(); 

        $successMessage = ($action === 'create') ? "Article created successfully!" : "Article updated successfully!";
        header("location: article_form.php?id=" . $current_article_id . "&status=success&msg=" . urlencode($successMessage));
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack(); 
        error_log("Article Submit PDOException: " . $e->getMessage());
        $errorMessage = "Database error. Could not save article. Details: " . $e->getCode();
        $redirectUrlBase = $action === 'create' ? "article_form.php" : "article_form.php?id=" . $article_id;
        $separator = (strpos($redirectUrlBase, '?') === false) ? '?' : '&';
        header("location: " . $redirectUrlBase . $separator . "status=error&msg=" . urlencode($errorMessage));
        exit;
    }

} 
// --- DELETE ARTICLE ACTION ---
elseif ($action === 'delete' && isset($_GET['id'])) {
    $article_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$article_id_to_delete) {
        header("location: manage_articles.php?status=error&msg=" . urlencode("Invalid article ID for deletion."));
        exit;
    }

    try {
        $article_to_delete = getArticleById($pdo, $article_id_to_delete);
        $pdo->beginTransaction();
        $stmt_tags = $pdo->prepare("DELETE FROM article_tags WHERE article_id = :article_id");
        $stmt_tags->bindParam(':article_id', $article_id_to_delete, PDO::PARAM_INT);
        $stmt_tags->execute();
        $stmt_article = $pdo->prepare("DELETE FROM articles WHERE id = :article_id");
        $stmt_article->bindParam(':article_id', $article_id_to_delete, PDO::PARAM_INT);
        $stmt_article->execute();
        $rowCount = $stmt_article->rowCount();
        $pdo->commit();

        if ($rowCount > 0) {
            if ($article_to_delete && !empty($article_to_delete['image_url'])) {
                 $imagePathToDelete = __DIR__ . '/../' . ltrim(str_replace('../', '', $article_to_delete['image_url']), '/');
                 if (file_exists($imagePathToDelete) && is_writable($imagePathToDelete)) {
                    unlink($imagePathToDelete);
                }
            }
            header("location: manage_articles.php?status=success&msg=" . urlencode("Article (ID: ".$article_id_to_delete.") deleted successfully."));
        } else {
            header("location: manage_articles.php?status=error&msg=" . urlencode("Article not found or could not be deleted."));
        }
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Article Delete PDOException: " . $e->getMessage());
        header("location: manage_articles.php?status=error&msg=" . urlencode("Database error. Could not delete article."));
        exit;
    }

} else {
    header("location: manage_articles.php?status=error&msg=" . urlencode("Invalid operation."));
    exit;
}

if (isset($pdo)) $pdo = null;
?>
