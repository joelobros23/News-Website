<?php
// admin/article_form.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and function files
require_once __DIR__ . '/includes/db_connect.php'; // Provides $pdo
require_once __DIR__ . '/includes/functions.php'; // Provides helper functions, including getAllCategories

// Check if the user is logged in
requireAdminLogin();

$adminUsername = $_SESSION["admin_username"] ?? 'Admin';
// This variable will be used by sidebar.php
$currentAdminUsername = $adminUsername;

// Initialize variables for the form
$pageTitle = "Add New Article";
$submitButtonText = "Create Article";
$formAction = "submit_article.php?action=create";

$article = [
    'id' => '',
    'title' => '',
    'content' => '', // Content will be HTML
    'category_id' => '',
    'tags' => '', 
    'image_url' => '',
    'status' => 'draft'
];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $articleId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $fetchedArticle = getArticleById($pdo, $articleId); 

    if ($fetchedArticle) {
        $article = $fetchedArticle; // This includes HTML content
        if (isset($article['id'])) { 
            $tagsArray = getTagsForArticle($pdo, $article['id']);
            $article['tags'] = implode(', ', array_column($tagsArray, 'name'));
        } else {
            $article['tags'] = '';
        }
        $pageTitle = "Edit Article: " . htmlspecialchars($article['title']);
        $submitButtonText = "Update Article";
        $formAction = "submit_article.php?action=update&id=" . $article['id'];
    } else {
        $pageTitle = "Add New Article (Error: Article ID " . htmlspecialchars($articleId) . " not found)";
    }
}

$categories = getAllCategories($pdo);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - News Week Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <!-- TinyMCE CDN -->
    <script src="https://cdn.tiny.cloud/1/x4o0c2g5r32w4vt6mbeaevkmhfhdaqn3upn54hz5rx0isjh2/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <!-- Replace no-api-key with your actual TinyMCE API key if you have one for production -->
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-link { @apply flex items-center p-3 text-gray-700 hover:bg-blue-500 hover:text-white rounded-lg transition-colors duration-200; }
        .sidebar-link .icon { @apply w-5 h-5 mr-3; }
        .sidebar-link.active { @apply bg-blue-600 text-white; }
        /* Ensure TinyMCE toolbar is not overly compressed on small screens */
        .tox-tinymce { max-width: 100%; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <header class="mb-8 flex justify-between items-center">
                <h2 class="text-3xl font-semibold text-gray-800"><?php echo htmlspecialchars($pageTitle); ?></h2>
                <a href="manage_articles.php" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Articles
                </a>
            </header>

            <form id="articleForm" action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-lg shadow-md">
                <div id="formMessage" class="hidden mb-4 p-3 rounded-md text-sm"></div>

                <div class="mb-6">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Article Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($article['title']); ?>" required
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-6">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Content <span class="text-red-500">*</span></label>
                    <textarea name="content" id="content" rows="15" 
                              class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $article['content']; // Output raw HTML content for TinyMCE ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category_id" id="category_id" required
                            class="w-full p-3 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a Category</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($article['category_id'] == $category['id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No categories found. Please add categories first.</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label for="tags-input-field" class="block text-sm font-medium text-gray-700 mb-1">Tags (comma-separated)</label>
                    <input type="text" name="tags" id="tags-input-field" value="<?php echo htmlspecialchars($article['tags']); ?>"
                           placeholder="e.g., tech, news, update"
                           class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="tags-display-container" class="mt-2 flex flex-wrap gap-2"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 items-center">
                    <div>
                        <label for="image_upload" class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                        <input type="file" name="image_upload" id="image_upload" accept="image/jpeg, image/png, image/gif"
                               class="w-full p-2 border border-gray-300 rounded-md file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">Max file size: 2MB. JPG, PNG, GIF. If editing and you don't choose a new file, the existing image will be kept (if any).</p>
                         <?php if (!empty($article['image_url']) && isset($_GET['id'])): ?>
                            <input type="hidden" name="existing_image_url" value="<?php echo htmlspecialchars($article['image_url']); ?>">
                            <p class="text-xs text-gray-500 mt-1">Current: <?php echo htmlspecialchars(basename($article['image_url'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div id="imagePreviewContainer" class="<?php echo empty($article['image_url']) ? 'hidden' : ''; ?>">
                         <label class="block text-sm font-medium text-gray-700 mb-1">Image Preview</label>
                         <img id="imagePreview" src="<?php echo !empty($article['image_url']) ? htmlspecialchars($article['image_url']) : 'https://placehold.co/300x200/E2E8F0/4A5568?text=No+Image'; ?>" alt="Image Preview" class="max-h-40 h-auto w-auto rounded-md border border-gray-300 object-contain">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status"
                            class="w-full p-3 border border-gray-300 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="draft" <?php echo ($article['status'] == 'draft' ? 'selected' : ''); ?>>Draft</option>
                        <option value="published" <?php echo ($article['status'] == 'published' ? 'selected' : ''); ?>>Published</option>
                        <option value="archived" <?php echo ($article['status'] == 'archived' ? 'selected' : ''); ?>>Archived</option>
                    </select>
                </div>

                <div class="mt-8 border-t pt-6 flex items-center space-x-4">
                    <button type="submit" id="submitArticleBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out">
                        <i class="fas fa-save mr-2"></i> <?php echo $submitButtonText; ?>
                    </button>
                    <a href="manage_articles.php" class="text-gray-600 hover:text-gray-800 py-3 px-6 rounded-md border border-gray-300 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <?php if (isset($_GET['id']) && !empty($article['id'])): ?>
                    <button type="button" onclick="confirmDeleteArticle('<?php echo $article['id']; ?>', '<?php echo htmlspecialchars(addslashes($article['title']), ENT_QUOTES); ?>'); return false;"
                            class="ml-auto bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 transition duration-150 ease-in-out">
                        <i class="fas fa-trash mr-2"></i> Delete Article
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </main>
    </div>
    
    <div id="deleteConfirmationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50 px-4">
        <div class="relative p-6 sm:p-8 bg-white w-full max-w-md m-auto flex-col flex rounded-lg shadow-xl">
             <button onclick="closeDeleteModal()" class="absolute top-0 right-0 mt-4 mr-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times fa-lg"></i>
            </button>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-red-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalDeleteTitle">Confirm Deletion</h3>
                <div class="mt-2 px-2 sm:px-7 py-3">
                    <p class="text-sm text-gray-500" id="modalDeleteMessage">Are you sure you want to delete this article? This action cannot be undone.</p>
                </div>
                <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                    <button id="confirmDeleteArticleBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Yes, Delete
                    </button>
                    <button onclick="closeDeleteModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // TinyMCE Initialization
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: 'textarea#content',
                    plugins: [ 
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'textcolor', 'emoticons'
                    ],
                    toolbar: 'undo redo | blocks | ' + 
                             'bold italic underline strikethrough | forecolor backcolor | ' +
                             'alignleft aligncenter alignright alignjustify | ' +
                             'bullist numlist outdent indent | link image media | ' +
                             'removeformat | code | fullscreen | help',
                    content_style: 'body { font-family:Inter,sans-serif; font-size:16px }',
                    height: 500,
                    menubar: 'file edit view insert format tools table help', 
                    font_family_formats: 'Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Inter=Inter,sans-serif; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats',
                    font_size_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
                    forced_root_block: 'p',
                    setup: function (editor) {
                        editor.on('change', function () {
                            tinymce.triggerSave(); 
                        });
                         editor.on('init', function(args) {
                            // editor.focus();
                        });
                    }
                });
            } else {
                console.warn('TinyMCE script not loaded. Rich text editor will not be available.');
            }

            const articleForm = document.getElementById('articleForm');
            const formMessageContainer = document.getElementById('formMessage');
            const submitBtn = document.getElementById('submitArticleBtn');

            // Handle form messages from URL
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const msg = urlParams.get('msg');

            if (status && msg && formMessageContainer) {
                formMessageContainer.innerHTML = `<i class="fas ${status === 'success' ? 'fa-check-circle text-green-700' : 'fa-exclamation-triangle text-red-700'} mr-2"></i> ${decodeURIComponent(msg)}`;
                formMessageContainer.className = `mb-4 p-3 rounded-md text-sm ${status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
                formMessageContainer.classList.remove('hidden');
                
                const cleanUrl = window.location.pathname + (urlParams.has('id') ? '?id=' + urlParams.get('id') : '');
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }


            if (articleForm) {
                articleForm.addEventListener('submit', function(event) {
                    // Ensure TinyMCE content is saved to the underlying textarea
                    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                        tinymce.get('content').save(); 
                    }

                    const titleInput = document.getElementById('title');
                    const contentTextarea = document.getElementById('content'); // The actual textarea
                    let actualContent = '';

                    if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                        // Get plain text content from TinyMCE for validation
                        actualContent = tinymce.get('content').getContent({ format: 'text' }).trim();
                    } else {
                        // Fallback if TinyMCE isn't loaded, use textarea value directly
                        actualContent = contentTextarea.value.trim();
                    }
                    
                    console.log("Validating Title:", titleInput.value.trim());
                    console.log("Validating Content (plain text from TinyMCE/textarea):", actualContent);


                    // Clear previous messages
                    if(formMessageContainer) {
                        formMessageContainer.classList.add('hidden');
                        formMessageContainer.innerHTML = '';
                    }

                    if (titleInput.value.trim() === '' || actualContent === '') {
                        event.preventDefault(); // Stop form submission
                        
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> ' + '<?php echo $submitButtonText; ?>';
                        }
                        
                        let errorMessage = "Title and Content fields cannot be empty.";
                        if (titleInput.value.trim() === '' && actualContent !== '') {
                            errorMessage = "Title field cannot be empty.";
                        } else if (titleInput.value.trim() !== '' && actualContent === '') {
                            errorMessage = "Content field cannot be empty.";
                        }
                        
                        if (formMessageContainer) {
                            formMessageContainer.innerHTML = `<i class="fas fa-exclamation-triangle text-red-700 mr-2"></i> ${errorMessage}`;
                            formMessageContainer.className = 'mb-4 p-3 rounded-md text-sm bg-red-100 text-red-700';
                            formMessageContainer.classList.remove('hidden');
                        }
                        console.error("Client-side validation failed:", errorMessage);
                        return false;
                    }

                    // If validation passes, disable button and show processing
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                    }
                });
            }
            
            // --- The rest of your existing JavaScript for tags, image preview, delete modal etc. ---
            const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            let isEditing = window.location.search.includes('id=');
            sidebarLinks.forEach(link => { 
                const linkHref = link.getAttribute('href');
                if (!linkHref) return;
                const linkPath = linkHref.split('/').pop().split('?')[0] || 'dashboard.php';
                link.classList.remove('active');

                if (currentPath === 'article_form.php') {
                    if (isEditing && (linkPath === 'manage_articles.php' || linkHref.includes('manage_articles.php'))) {
                        link.classList.add('active');
                    } else if (!isEditing && (linkPath === 'article_form.php' || linkHref.includes('article_form.php'))) {
                        link.classList.add('active');
                    }
                } else if (linkPath === currentPath) {
                    link.classList.add('active');
                }
            });
            if (currentPath === 'index.php' && document.querySelector('.sidebar-link[href="dashboard.php"]')) { 
                 document.querySelector('.sidebar-link[href="dashboard.php"]').classList.add('active');
            }

            const tagsInputField = document.getElementById('tags-input-field');
            const tagsDisplayContainer = document.getElementById('tags-display-container');
            function updateTagsDisplay() { 
                if (!tagsInputField || !tagsDisplayContainer) return;
                tagsDisplayContainer.innerHTML = '';
                const tags = tagsInputField.value.split(',')
                    .map(tag => tag.trim())
                    .filter(tag => tag !== '');
                tags.forEach(tagText => {
                    const tagElement = document.createElement('span');
                    tagElement.className = 'bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs items-center inline-flex';
                    tagElement.textContent = tagText;
                    tagsDisplayContainer.appendChild(tagElement);
                });
            }
            if (tagsInputField) { 
                tagsInputField.addEventListener('input', updateTagsDisplay);
                updateTagsDisplay();
            }
            
            const imageUploadInput = document.getElementById('image_upload');
            const imagePreview = document.getElementById('imagePreview');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            const defaultImageSrc = 'https://placehold.co/300x200/E2E8F0/4A5568?text=No+Image';
            const existingImageUrl = <?php echo json_encode(!empty($article['image_url']) ? $article['image_url'] : ''); ?>;

            if (imageUploadInput && imagePreview && imagePreviewContainer) {
                imageUploadInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreviewContainer.classList.remove('hidden');
                        }
                        reader.readAsDataURL(file);
                    } else {
                        imagePreview.src = existingImageUrl || defaultImageSrc;
                        if (!existingImageUrl) {
                           imagePreviewContainer.classList.add('hidden');
                        } else {
                           imagePreviewContainer.classList.remove('hidden');
                        }
                    }
                });
                if (existingImageUrl) {
                    imagePreview.src = existingImageUrl;
                    imagePreviewContainer.classList.remove('hidden');
                } else {
                    imagePreview.src = defaultImageSrc;
                    imagePreviewContainer.classList.add('hidden');
                }
            }
        });

        const deleteModal = document.getElementById('deleteConfirmationModal');
        const modalDeleteTitle = document.getElementById('modalDeleteTitle');
        const modalDeleteMessage = document.getElementById('modalDeleteMessage');
        const confirmDeleteBtn = document.getElementById('confirmDeleteArticleBtn');
        let articleIdToDelete = null;
        let articleActionUrl = '';

        function confirmDeleteArticle(id, title) { 
            articleIdToDelete = id;
            articleActionUrl = `submit_article.php?action=delete&id=${id}`; 
            if (deleteModal && modalDeleteTitle && modalDeleteMessage) {
                modalDeleteTitle.textContent = 'Confirm Article Deletion';
                modalDeleteMessage.innerHTML = `Are you sure you want to delete the article: <br><strong>"${title}"</strong>? <br><br>This action cannot be undone.`;
                deleteModal.classList.remove('hidden');
            }
        }
        function closeDeleteModal() { 
            if (deleteModal) deleteModal.classList.add('hidden');
            articleIdToDelete = null;
            articleActionUrl = '';
        }
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                if (articleActionUrl) {
                    window.location.href = articleActionUrl;
                }
            });
        }
    </script>
</body>
</html>
