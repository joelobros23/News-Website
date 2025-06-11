<?php
// contact.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/includes/db_connect.php'; 
require_once __DIR__ . '/admin/includes/functions.php';

// --- Variables needed by header.php ---
$pageTitle = "Contact Us - News Week";
$menuCategories = getAllCategories($pdo); 

// --- Fetch all contact settings ---
$contact_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_name, setting_value FROM site_settings WHERE setting_name LIKE 'contact_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contact_settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Contact Page - Error fetching content: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php'; 
?>

    <!-- Main Content Area -->
    <main class="container mx-auto px-4 py-8">
        <div class="bg-white p-6 md:p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-6 border-b pb-4">
                Get In Touch
            </h1>
            
            <div class="space-y-6 text-lg text-gray-700">
                <?php if (!empty($contact_settings['contact_telephone'])): ?>
                <div class="flex items-center">
                    <i class="fas fa-phone-alt fa-fw w-6 text-blue-500 mr-4"></i>
                    <div>
                        <h2 class="font-semibold">Telephone</h2>
                        <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_settings['contact_telephone'])); ?>" class="hover:text-blue-600">
                            <?php echo htmlspecialchars($contact_settings['contact_telephone']); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($contact_settings['contact_mobile'])): ?>
                <div class="flex items-center">
                    <i class="fas fa-mobile-alt fa-fw w-6 text-blue-500 mr-4"></i>
                    <div>
                        <h2 class="font-semibold">Mobile</h2>
                        <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_settings['contact_mobile'])); ?>" class="hover:text-blue-600">
                            <?php echo htmlspecialchars($contact_settings['contact_mobile']); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($contact_settings['contact_email'])): ?>
                <div class="flex items-center">
                    <i class="fas fa-envelope fa-fw w-6 text-blue-500 mr-4"></i>
                    <div>
                        <h2 class="font-semibold">Email</h2>
                        <a href="mailto:<?php echo htmlspecialchars($contact_settings['contact_email']); ?>" class="hover:text-blue-600">
                            <?php echo htmlspecialchars($contact_settings['contact_email']); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($contact_settings['contact_location'])): ?>
                <div class="flex items-start">
                    <i class="fas fa-map-marker-alt fa-fw w-6 text-blue-500 mr-4 mt-1"></i>
                    <div>
                        <h2 class="font-semibold">Location</h2>
                        <p><?php echo nl2br(htmlspecialchars($contact_settings['contact_location'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Optional: Add a contact form or map embed here -->

        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
