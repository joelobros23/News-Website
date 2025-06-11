<?php
// privacy.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/includes/db_connect.php'; 
require_once __DIR__ . '/admin/includes/functions.php';

// --- Variables needed by header.php ---
$pageTitle = "Privacy Policy - News Week";
$menuCategories = getAllCategories($pdo); 

// --- Fetch content for this page ---
$privacy_policy_content = '';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'privacy_policy'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $privacy_policy_content = $result['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Privacy Policy Page - Error fetching content: " . $e->getMessage());
    $privacy_policy_content = "<p>Error: Could not load privacy policy.</p>";
}

require_once __DIR__ . '/includes/header.php';
?>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8">
        <div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
            <!-- Title is often included in the content itself from the editor, but we can add a fallback -->
            <!-- <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-6 border-b pb-4">
                Privacy Policy
            </h1> -->
            <!-- The `prose` class styles the HTML content from the editor -->
            <div class="article-content prose lg:prose-xl max-w-none">
                <?php echo $privacy_policy_content; ?>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
