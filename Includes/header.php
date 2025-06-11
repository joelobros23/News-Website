<?php
// includes/header.php

// This file expects the following variables to be defined in the page that includes it:
// $pageTitle (string) - For the <title> tag.
// $menuCategories (array) - An array of categories for the navigation menu.
// $categorySlug (string) - (Optional) The slug of the current category page, to highlight the active menu item.

// Determine the current page for active link highlighting
$current_page_name = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? "News Week - Your Daily Digest"; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css"> <!-- Your custom CSS -->
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        /* Additional base styles can go here if needed */
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-6 flex flex-col sm:flex-row justify-between items-center">
            <h1 class="text-3xl font-bold text-blue-600">
                <a href="index.php">News Week</a>
            </h1>
            <nav class="mt-4 sm:mt-0">
                <ul class="flex space-x-2 sm:space-x-4 text-sm sm:text-base flex-wrap justify-center">
                    <li>
                        <a href="index.php" 
                           class="text-gray-600 hover:text-blue-500 px-2 py-1 rounded <?php echo ($current_page_name === 'index.php') ? 'font-bold text-blue-600' : ''; ?>">
                           Home
                        </a>
                    </li>
                    <?php if (!empty($menuCategories)): ?>
                        <?php foreach ($menuCategories as $category_item): ?>
                            <li>
                                <a href="category.php?slug=<?php echo htmlspecialchars($category_item['slug']); ?>" 
                                   class="text-gray-600 hover:text-blue-500 px-2 py-1 rounded <?php echo (isset($categorySlug) && $categorySlug === $category_item['slug']) ? 'font-bold text-blue-600' : ''; ?>">
                                    <?php echo htmlspecialchars($category_item['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
