<?php
// includes/footer.php

// This file is included by pages that should already have a $pdo connection.
// We'll fetch the social media links here. For better performance on a high-traffic site,
// you might fetch all site settings once in the header and pass the array down.
$social_links = [];
if (isset($pdo)) {
    try {
        // Fetch only the settings that start with 'social_'
        $stmt = $pdo->query("SELECT setting_name, setting_value FROM site_settings 
                             WHERE setting_name LIKE 'social_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Store them in an array, e.g., $social_links['social_facebook'] = 'http://...'
            $social_links[$row['setting_name']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log("Footer - Error fetching social links: " . $e->getMessage());
        // If there's an error, the $social_links array will just be empty.
    }
}
?>
    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12 py-8">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <span id="currentYear"></span> News Week. All Rights Reserved.</p>
            <p class="text-sm">
                <a href="about.php" class="hover:text-blue-400">About Us</a> | 
                <a href="contact.php" class="hover:text-blue-400">Contact</a> | 
                <a href="privacy.php" class="hover:text-blue-400">Privacy Policy</a>
            </p>
            
            <!-- Dynamic Social Media Links -->
            <div class="mt-4">
                <?php if (!empty($social_links['social_facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($social_links['social_facebook']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="text-xl hover:text-blue-400 mx-2"><i class="fab fa-facebook-f"></i></a>
                <?php endif; ?>

                <?php if (!empty($social_links['social_twitter'])): ?>
                    <a href="<?php echo htmlspecialchars($social_links['social_twitter']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter" class="text-xl hover:text-blue-400 mx-2"><i class="fab fa-twitter"></i></a>
                <?php endif; ?>

                <?php if (!empty($social_links['social_instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($social_links['social_instagram']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="text-xl hover:text-blue-400 mx-2"><i class="fab fa-instagram"></i></a>
                <?php endif; ?>

                <?php if (!empty($social_links['social_linkedin'])): ?>
                    <a href="<?php echo htmlspecialchars($social_links['social_linkedin']); ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="text-xl hover:text-blue-400 mx-2"><i class="fab fa-linkedin-in"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Custom JS -->
    <script src="js/script.js"></script>
    <script>
        // Simple script to update the year in the footer
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>