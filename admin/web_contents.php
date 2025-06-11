<?php
// admin/web_contents.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db_connect.php'; 
require_once __DIR__ . '/includes/functions.php';

requireAdminLogin();

$currentAdminUsername = $_SESSION["admin_username"] ?? 'Admin';

// Fetch all settings at once to populate the form
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_name, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Web Contents - Error fetching settings: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Contents - News Week Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    
    <!-- Quill.js - A stable version -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-button.active {
            border-color: #2563eb; /* blue-600 */
            color: #2563eb; /* blue-600 */
        }
        .ql-toolbar { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; border-color: #d1d5db; }
        .ql-container { border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; border-color: #d1d5db; height: 350px; font-size: 16px; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="mb-8">
                <h2 class="text-3xl font-semibold text-gray-800">Manage Web Contents</h2>
                <p class="text-gray-600 mt-1">Edit the content for your site's public pages like 'About Us' and manage contact & social media links.</p>
            </header>

            <div id="statusMessageGlobal" class="mb-6">
                 <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                    <div class="p-4 rounded-md text-sm <?php echo ($_GET['status'] == 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <i class="fas <?php echo ($_GET['status'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars(urldecode($_GET['msg'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <form id="webContentsForm" action="submit_web_contents.php" method="POST">
                <div class="border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                        <li class="mr-2" role="presentation">
                            <button class="tab-button px-4 py-2 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 active" data-target="about-content" type="button" role="tab">About Us</button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="tab-button px-4 py-2 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300" data-target="contact-content" type="button" role="tab">Contact Details</button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="tab-button px-4 py-2 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300" data-target="policy-content" type="button" role="tab">Privacy Policy</button>
                        </li>
                        <li role="presentation">
                            <button class="tab-button px-4 py-2 rounded-t-lg border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300" data-target="social-content" type="button" role="tab">Social Media</button>
                        </li>
                    </ul>
                </div>

                <div id="myTabContent">
                    <div class="tab-content p-6 bg-white rounded-lg rounded-tl-none shadow-md" id="about-content" role="tabpanel">
                        <label class="block text-lg font-medium text-gray-800 mb-3">About Us Page Content</label>
                        <input type="hidden" name="about_us" id="about_us_input">
                        <div id="about_us_editor"><?php echo $settings['about_us'] ?? ''; ?></div>
                    </div>
                    <div class="tab-content p-6 bg-white rounded-lg rounded-tl-none shadow-md hidden" id="contact-content" role="tabpanel">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Contact Information</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="contact_telephone" class="block text-sm font-medium text-gray-700">Telephone</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fas fa-phone-alt w-4 text-center"></i></span>
                                    <input type="text" name="contact_telephone" id="contact_telephone" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['contact_telephone'] ?? ''); ?>" placeholder="(012) 345-6789">
                                </div>
                            </div>
                            <div>
                                <label for="contact_mobile" class="block text-sm font-medium text-gray-700">Mobile</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fas fa-mobile-alt w-4 text-center"></i></span>
                                    <input type="text" name="contact_mobile" id="contact_mobile" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['contact_mobile'] ?? ''); ?>" placeholder="+63 912 345 6789">
                                </div>
                            </div>
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fas fa-envelope w-4 text-center"></i></span>
                                    <input type="email" name="contact_email" id="contact_email" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" placeholder="contact@example.com">
                                </div>
                            </div>
                            <div>
                                <label for="contact_location" class="block text-sm font-medium text-gray-700">Location / Address</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fas fa-map-marker-alt w-4 text-center"></i></span>
                                    <input type="text" name="contact_location" id="contact_location" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['contact_location'] ?? ''); ?>" placeholder="123 News Lane, Media City">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content p-6 bg-white rounded-lg rounded-tl-none shadow-md hidden" id="policy-content" role="tabpanel">
                        <label class="block text-lg font-medium text-gray-800 mb-3">Privacy Policy Page Content</label>
                        <input type="hidden" name="privacy_policy" id="privacy_policy_input">
                        <div id="privacy_policy_editor"><?php echo $settings['privacy_policy'] ?? ''; ?></div>
                    </div>
                    <div class="tab-content p-6 bg-white rounded-lg rounded-tl-none shadow-md hidden" id="social-content" role="tabpanel">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Social Media Links</h3>
                        <div class="space-y-4">
                             <div>
                                <label for="social_facebook" class="block text-sm font-medium text-gray-700">Facebook URL</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fab fa-facebook-f w-4 text-center"></i></span>
                                    <input type="url" name="social_facebook" id="social_facebook" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>" placeholder="https://facebook.com/yourpage">
                                </div>
                            </div>
                             <div>
                                <label for="social_twitter" class="block text-sm font-medium text-gray-700">Twitter (X) URL</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fab fa-twitter w-4 text-center"></i></span>
                                    <input type="url" name="social_twitter" id="social_twitter" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourhandle">
                                </div>
                            </div>
                             <div>
                                <label for="social_instagram" class="block text-sm font-medium text-gray-700">Instagram URL</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fab fa-instagram w-4 text-center"></i></span>
                                    <input type="url" name="social_instagram" id="social_instagram" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/yourhandle">
                                </div>
                            </div>
                             <div>
                                <label for="social_linkedin" class="block text-sm font-medium text-gray-700">LinkedIn URL</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"><i class="fab fa-linkedin-in w-4 text-center"></i></span>
                                    <input type="url" name="social_linkedin" id="social_linkedin" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 p-2" value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/company/yourpage">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 border-t pt-5">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-save mr-2"></i> Save All Changes
                    </button>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Quill Editor Setup ---
            const quillInstances = {}; 
            const toolbarOptions = [
                [{ 'header': [1, 2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'color': [] }, { 'background': [] }],
                ['clean']
            ];

            function initializeQuill(editorId, inputId) {
                if (!quillInstances[editorId]) {
                    const editor = new Quill(`#${editorId}`, {
                        modules: { toolbar: toolbarOptions },
                        theme: 'snow'
                    });
                    
                    quillInstances[editorId] = editor;

                    const hiddenInput = document.getElementById(inputId);
                    if (hiddenInput) {
                        editor.on('text-change', function() {
                            hiddenInput.value = editor.root.innerHTML;
                        });
                        hiddenInput.value = editor.root.innerHTML;
                    }
                }
            }
            
            // --- Tab Functionality ---
            const tabs = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', (event) => {
                    event.preventDefault(); 
                    
                    const targetId = tab.dataset.target;
                    const targetContent = document.getElementById(targetId);

                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.add('hidden'));

                    tab.classList.add('active');
                    if (targetContent) {
                        targetContent.classList.remove('hidden');
                        
                        const editorDiv = targetContent.querySelector('[id$="_editor"]');
                        if (editorDiv) {
                            const inputDivId = editorDiv.id.replace('_editor', '_input');
                            initializeQuill(editorDiv.id, inputDivId);
                        }
                    }
                });
            });

            // Initialize the first editor on page load
            initializeQuill('about_us_editor', 'about_us_input');
            
            // Sync content to hidden inputs before form submission
            const webContentsForm = document.getElementById('webContentsForm');
            if (webContentsForm) {
                webContentsForm.addEventListener('submit', function() {
                    for (const editorId in quillInstances) {
                        const inputId = editorId.replace('_editor', '_input');
                        const hiddenInput = document.getElementById(inputId);
                        if (hiddenInput && quillInstances[editorId]) {
                            hiddenInput.value = quillInstances[editorId].root.innerHTML;
                        }
                    }
                });
            }

            // Clear URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status') || urlParams.has('msg')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>
