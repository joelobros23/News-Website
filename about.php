<?php
// about.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/includes/db_connect.php'; 
require_once __DIR__ . '/admin/includes/functions.php';

// --- Variables needed by header.php ---
$pageTitle = "About Us - News Week";
$menuCategories = getAllCategories($pdo); 
// $categorySlug is not needed here, so it's not defined.

// --- Fetch content for this page ---
$about_us_content = '';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'about_us'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $about_us_content = $result['setting_value'];
    }
} catch (PDOException $e) {
    error_log("About Us Page - Error fetching content: " . $e->getMessage());
    $about_us_content = "<p>Error: Could not load page content.</p>";
}

require_once __DIR__ . '/includes/header.php'; // Include the header
?>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8">
        <div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-6 border-b pb-4">
                About Us
            </h1>
            <!-- The `prose` class styles the HTML content from the editor -->
            <div class="article-content prose lg:prose-xl max-w-none">
                <?php echo $about_us_content; ?>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; // Include the footer ?>
