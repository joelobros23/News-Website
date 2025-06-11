<?php
// admin/submit_category.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$action = $_GET['action'] ?? '';

// --- CREATE CATEGORY ---
if ($action === 'create_category' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = sanitizeString($_POST['category_name'] ?? '');
    $category_description = sanitizeString($_POST['category_description'] ?? '');

    if (empty($category_name)) {
        header("location: manage_categories.php?status=error&msg=" . urlencode("Category name cannot be empty."));
        exit;
    }

    // Generate slug
    $slug = generateSlug($category_name);

    // Check if category name or slug already exists
    try {
        $stmt_check = $pdo->prepare("SELECT id FROM categories WHERE name = :name OR slug = :slug LIMIT 1");
        $stmt_check->bindParam(':name', $category_name, PDO::PARAM_STR);
        $stmt_check->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt_check->execute();
        if ($stmt_check->rowCount() > 0) {
            header("location: manage_categories.php?status=error&msg=" . urlencode("Category name or slug already exists."));
            exit;
        }

        // Insert new category
        $stmt_insert = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)");
        $stmt_insert->bindParam(':name', $category_name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt_insert->bindParam(':description', $category_description, PDO::PARAM_STR);

        if ($stmt_insert->execute()) {
            header("location: manage_categories.php?status=success&msg=" . urlencode("Category created successfully!"));
        } else {
            header("location: manage_categories.php?status=error&msg=" . urlencode("Could not create category. Database error."));
        }
        exit;

    } catch (PDOException $e) {
        error_log("Category Create PDOException: " . $e->getMessage());
        header("location: manage_categories.php?status=error&msg=" . urlencode("Database error creating category: " . $e->getCode()));
        exit;
    }
}

// --- UPDATE CATEGORY ---
elseif ($action === 'update_category' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $category_name = sanitizeString($_POST['category_name'] ?? '');
    $category_description = sanitizeString($_POST['category_description'] ?? '');

    if (!$category_id) {
        header("location: manage_categories.php?status=error&msg=" . urlencode("Invalid category ID for update."));
        exit;
    }
    if (empty($category_name)) {
        header("location: manage_categories.php?status=error&msg=" . urlencode("Category name cannot be empty.") . "&edit_id=" . $category_id);
        exit;
    }

    // Generate new slug
    $slug = generateSlug($category_name);

    try {
        // Check if new category name or slug conflicts with another existing category
        $stmt_check = $pdo->prepare("SELECT id FROM categories WHERE (name = :name OR slug = :slug) AND id != :id LIMIT 1");
        $stmt_check->bindParam(':name', $category_name, PDO::PARAM_STR);
        $stmt_check->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt_check->bindParam(':id', $category_id, PDO::PARAM_INT);
        $stmt_check->execute();
        if ($stmt_check->rowCount() > 0) {
            header("location: manage_categories.php?status=error&msg=" . urlencode("Another category with this name or slug already exists.") . "&edit_id=" . $category_id);
            exit;
        }

        // Update category
        $stmt_update = $pdo->prepare("UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id");
        $stmt_update->bindParam(':name', $category_name, PDO::PARAM_STR);
        $stmt_update->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt_update->bindParam(':description', $category_description, PDO::PARAM_STR);
        $stmt_update->bindParam(':id', $category_id, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            header("location: manage_categories.php?status=success&msg=" . urlencode("Category updated successfully!"));
        } else {
            header("location: manage_categories.php?status=error&msg=" . urlencode("Could not update category. Database error.") . "&edit_id=" . $category_id);
        }
        exit;

    } catch (PDOException $e) {
        error_log("Category Update PDOException: " . $e->getMessage());
        header("location: manage_categories.php?status=error&msg=" . urlencode("Database error updating category: " . $e->getCode()) . "&edit_id=" . $category_id);
        exit;
    }
}

// --- DELETE CATEGORY ---
elseif ($action === 'delete_category' && isset($_GET['id'])) {
    $category_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$category_id_to_delete) {
        header("location: manage_categories.php?status=error&msg=" . urlencode("Invalid category ID for deletion."));
        exit;
    }

    // Important: Decide how to handle articles associated with this category.
    // Option 1: Set category_id to NULL for associated articles (requires articles.category_id to allow NULL and ON DELETE SET NULL constraint).
    // Option 2: Prevent deletion if articles are associated.
    // Option 3: Delete associated articles (dangerous, usually not recommended without explicit confirmation).
    // Option 4: Assign articles to a default 'Uncategorized' category.

    // For this example, we'll use Option 1 (SET NULL), assuming your DB is set up for it.
    // If not, adjust the SQL or logic.
    // You added `ON DELETE SET NULL` to the `articles` table constraint for `category_id`.

    try {
        $pdo->beginTransaction();

        // Step 1: (Optional but good practice) Update articles to set category_id to NULL
        // This is automatically handled by `ON DELETE SET NULL` in your SQL schema.
        // If you didn't have that constraint, you would do:
        // $stmt_update_articles = $pdo->prepare("UPDATE articles SET category_id = NULL WHERE category_id = :category_id");
        // $stmt_update_articles->bindParam(':category_id', $category_id_to_delete, PDO::PARAM_INT);
        // $stmt_update_articles->execute();
        
        // Step 2: Delete the category
        $stmt_delete = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt_delete->bindParam(':id', $category_id_to_delete, PDO::PARAM_INT);
        $stmt_delete->execute();

        $rowCount = $stmt_delete->rowCount();
        $pdo->commit();

        if ($rowCount > 0) {
            header("location: manage_categories.php?status=success&msg=" . urlencode("Category (ID: ".$category_id_to_delete.") deleted successfully. Associated articles are now uncategorized."));
        } else {
            header("location: manage_categories.php?status=error&msg=" . urlencode("Category not found or could not be deleted."));
        }
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Category Delete PDOException: " . $e->getMessage());
        // Check for foreign key constraint violation if articles are NOT set to ON DELETE SET NULL
        // and you try to delete a category that has articles.
        // MySQL error code for foreign key constraint violation is often 1451.
        if ($e->errorInfo[1] == 1451) {
             header("location: manage_categories.php?status=error&msg=" . urlencode("Cannot delete category: It is currently assigned to one or more articles. Please reassign articles first."));
        } else {
             header("location: manage_categories.php?status=error&msg=" . urlencode("Database error. Could not delete category."));
        }
        exit;
    }
}

// --- INVALID ACTION ---
else {
    header("location: manage_categories.php?status=error&msg=" . urlencode("Invalid operation for categories."));
    exit;
}

if (isset($pdo)) $pdo = null;
?>
