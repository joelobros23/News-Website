<?php
// admin/includes/sidebar.php

// This variable should be defined in the parent page (e.g., dashboard.php)
// before including this sidebar.
$currentAdminUsername = $_SESSION["admin_username"] ?? 'Admin';

// Determine active link based on the current script's name
$current_page = basename($_SERVER['PHP_SELF']);
$is_editing_article = ($current_page === 'article_form.php' && isset($_GET['id']));
$is_adding_article = ($current_page === 'article_form.php' && !isset($_GET['id']));

?>
<!-- Add a <style> block here to define the specific behaviors for the sidebar text, as @apply won't work with the CDN script. -->
<style>
    /* Styling for the text next to the icons */
    .sidebar-text {
        /* Start with opacity 0 and prevent interaction */
        opacity: 0;
        pointer-events: none;
        /* Add a transition for the fade-in effect */
        transition: opacity 0.2s ease-in-out;
        transition-delay: 0.1s; /* Delay the text appearing slightly */
        /* Ensure text doesn't wrap when sidebar is collapsed */
        white-space: nowrap;
    }
    /* When hovering over the parent <aside> with the 'group' class, make the text visible */
    .group:hover .sidebar-text {
        opacity: 1;
        pointer-events: auto;
    }
</style>

<!-- The 'group' class on the aside element enables using 'group-hover' on child elements -->
<aside class="group w-20 hover:w-64 bg-white shadow-lg flex-shrink-0 transition-all duration-300 ease-in-out overflow-x-hidden">
    <!-- Header section of the sidebar -->
    <div class="p-4 border-b border-gray-200 flex items-center h-[73px] overflow-hidden">
        <i class="fas fa-newspaper text-blue-600 text-3xl flex-shrink-0"></i>
        <!-- The site title uses the sidebar-text class to fade in on hover -->
        <h1 class="text-2xl font-bold text-blue-600 ml-3 sidebar-text">News Week</h1>
    </div>
    
    <!-- Navigation Links -->
    <nav class="mt-4 p-3 space-y-2">
        <a href="dashboard.php" title="Dashboard" class="sidebar-link <?php echo ($current_page === 'dashboard.php' || $current_page === 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Dashboard</span>
        </a>
        <a href="manage_articles.php" title="Manage Articles" class="sidebar-link <?php echo ($current_page === 'manage_articles.php' || $is_editing_article) ? 'active' : ''; ?>">
            <i class="fas fa-newspaper icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Manage Articles</span>
        </a>
        <a href="article_form.php" title="Add New Article" class="sidebar-link <?php echo $is_adding_article ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Add New Article</span>
        </a>
        <a href="manage_categories.php" title="Manage Categories" class="sidebar-link <?php echo ($current_page === 'manage_categories.php') ? 'active' : ''; ?>">
            <i class="fas fa-tags icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Manage Categories</span>
        </a>
        <a href="manage_tags.php" title="Manage Tags" class="sidebar-link <?php echo ($current_page === 'manage_tags.php') ? 'active' : ''; ?>">
            <i class="fas fa-hashtag icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Manage Tags</span>
        </a>
        <a href="manage_users.php" title="Manage Users" class="sidebar-link <?php echo ($current_page === 'manage_users.php' || $current_page === 'user_form.php') ? 'active' : ''; ?>">
            <i class="fas fa-users-cog icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Manage Users</span>
        </a>
        <a href="web_contents.php" title="Web Contents" class="sidebar-link <?php echo ($current_page === 'web_contents.php') ? 'active' : ''; ?>">
            <i class="fas fa-globe-asia icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Web Contents</span>
        </a>
        <a href="settings.php" title="Settings" class="sidebar-link <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Settings</span>
        </a>
        <a href="auth.php?action=logout" title="Logout" class="sidebar-link mt-8 !text-red-500 hover:!bg-red-100">
            <i class="fas fa-sign-out-alt icon w-6 h-6 flex-shrink-0 text-center text-lg"></i>
            <span class="sidebar-text ml-4">Logout</span>
        </a>
    </nav>

    <!-- User profile section at the bottom -->
    <div class="absolute bottom-0 w-full p-4 text-center text-xs text-gray-400 overflow-hidden">
        <!-- Collapsed state: just a user icon -->
        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xl group-hover:hidden transition-all">
            <i class="fas fa-user"></i>
        </div>
        <!-- Expanded state: show user info -->
        <div class="hidden group-hover:block transition-all duration-300">
            <p>Logged in as:</p>
            <strong class="whitespace-nowrap"><?php echo htmlspecialchars($currentAdminUsername); ?></strong>
        </div>
    </div>
</aside>
