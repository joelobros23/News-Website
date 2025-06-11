<?php
// admin/submit_tag.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions

requireAdminLogin(); // Ensure only logged-in admins can access

$action = $_GET['action'] ?? '';

// --- CREATE TAG ---
if ($action === 'create_tag' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $tag_name = sanitizeString($_POST['tag_name'] ?? '');

    if (empty($tag_name)) {
        header("location: manage_tags.php?status=error&msg=" . urlencode("Tag name cannot be empty."));
        exit;
    }

    // Generate slug
    $slug = generateSlug($tag_name);

    // Check if tag name or slug already exists
    try {
        $stmt_check = $pdo->prepare("SELECT id FROM tags WHERE name = :name OR slug = :slug LIMIT 1");
        $stmt_check->bindParam(':name', $tag_name, PDO::PARAM_STR);
        $stmt_check->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt_check->execute();
        if ($stmt_check->rowCount() > 0) {
            header("location: manage_tags.php?status=error&msg=" . urlencode("Tag name or slug already exists."));
            exit;
        }

        // Insert new tag
        $stmt_insert = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
        $stmt_insert->bindParam(':name', $tag_name, PDO::PARAM_STR);
        $stmt_insert->bindParam(':slug', $slug, PDO::PARAM_STR);

        if ($stmt_insert->execute()) {
            header("location: manage_tags.php?status=success&msg=" . urlencode("Tag created successfully!"));
        } else {
            header("location: manage_tags.php?status=error&msg=" . urlencode("Could not create tag. Database error."));
        }
        exit;

    } catch (PDOException $e) {
        error_log("Tag Create PDOException: " . $e->getMessage());
        header("location: manage_tags.php?status=error&msg=" . urlencode("Database error creating tag: " . $e->getCode()));
        exit;
    }
}

// --- UPDATE TAG ---
elseif ($action === 'update_tag' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $tag_id = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);
    $tag_name = sanitizeString($_POST['tag_name'] ?? '');

    if (!$tag_id) {
        header("location: manage_tags.php?status=error&msg=" . urlencode("Invalid tag ID for update."));
        exit;
    }
    if (empty($tag_name)) {
        header("location: manage_tags.php?status=error&msg=" . urlencode("Tag name cannot be empty.") . "&edit_id=" . $tag_id);
        exit;
    }

    // Generate new slug
    $slug = generateSlug($tag_name);

    try {
        // Check if new tag name or slug conflicts with another existing tag
        $stmt_check = $pdo->prepare("SELECT id FROM tags WHERE (name = :name OR slug = :slug) AND id != :id LIMIT 1");
        $stmt_check->bindParam(':name', $tag_name, PDO::PARAM_STR);
        $stmt_check->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt_check->bindParam(':id', $tag_id, PDO::PARAM_INT);
        $stmt_check->execute();
        if ($stmt_check->rowCount() > 0) {
            header("location: manage_tags.php?status=error&msg=" . urlencode("Another tag with this name or slug already exists.") . "&edit_id=" . $tag_id);
            exit;
        }

        // Update tag
        $stmt_update = $pdo->prepare("UPDATE tags SET name = :name, slug = :slug WHERE id = :id");
        $stmt_update->bindParam(':name', $tag_name, PDO::PARAM_STR);
        $stmt_update->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt_update->bindParam(':id', $tag_id, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            header("location: manage_tags.php?status=success&msg=" . urlencode("Tag updated successfully!"));
        } else {
            header("location: manage_tags.php?status=error&msg=" . urlencode("Could not update tag. Database error.") . "&edit_id=" . $tag_id);
        }
        exit;

    } catch (PDOException $e) {
        error_log("Tag Update PDOException: " . $e->getMessage());
        header("location: manage_tags.php?status=error&msg=" . urlencode("Database error updating tag: " . $e->getCode()) . "&edit_id=" . $tag_id);
        exit;
    }
}

// --- DELETE TAG ---
elseif ($action === 'delete_tag' && isset($_GET['id'])) {
    $tag_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$tag_id_to_delete) {
        header("location: manage_tags.php?status=error&msg=" . urlencode("Invalid tag ID for deletion."));
        exit;
    }

    // When a tag is deleted, the `ON DELETE CASCADE` constraint on the `article_tags` table's
    // `tag_id` foreign key should automatically delete all associations of this tag with articles.

    try {
        $pdo->beginTransaction();
        
        // Delete the tag
        $stmt_delete = $pdo->prepare("DELETE FROM tags WHERE id = :id");
        $stmt_delete->bindParam(':id', $tag_id_to_delete, PDO::PARAM_INT);
        $stmt_delete->execute();

        $rowCount = $stmt_delete->rowCount();
        $pdo->commit();

        if ($rowCount > 0) {
            header("location: manage_tags.php?status=success&msg=" . urlencode("Tag (ID: ".$tag_id_to_delete.") deleted successfully. Its associations with articles have been removed."));
        } else {
            header("location: manage_tags.php?status=error&msg=" . urlencode("Tag not found or could not be deleted."));
        }
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Tag Delete PDOException: " . $e->getMessage());
        // MySQL error code for foreign key constraint violation is often 1451.
        // This shouldn't happen for tags if article_tags has ON DELETE CASCADE.
        // However, if other tables reference tags without ON DELETE CASCADE, it could.
        if ($e->errorInfo[1] == 1451) { 
             header("location: manage_tags.php?status=error&msg=" . urlencode("Cannot delete tag: It is referenced elsewhere (unexpected error)."));
        } else {
             header("location: manage_tags.php?status=error&msg=" . urlencode("Database error. Could not delete tag."));
        }
        exit;
    }
}

// --- INVALID ACTION ---
else {
    header("location: manage_tags.php?status=error&msg=" . urlencode("Invalid operation for tags."));
    exit;
}

if (isset($pdo)) $pdo = null;
?>
