// admin/js/admin_script.js

document.addEventListener('DOMContentLoaded', () => {
    console.log('Admin script loaded.');

    // --- Sidebar Active Link Handler ---
    // This logic is often duplicated or slightly varied per page if not using a templating engine.
    // The version in dashboard.php or article_form.php is more specific.
    // A generic version can be a fallback.
    const currentPath = window.location.pathname.split('/').pop() || 'index.php'; // Default to index.php if path is '/'
    const sidebarLinks = document.querySelectorAll('.sidebar-link'); // Assuming sidebar links have this class
    let isEditing = window.location.search.includes('id=');


    sidebarLinks.forEach(link => {
        const linkPath = link.getAttribute('href').split('/').pop();
        link.classList.remove('active'); // Reset all

        if (currentPath === 'article_form.php') {
            if (isEditing && linkPath === 'manage_articles.php') {
                link.classList.add('active'); // Highlight 'Manage Articles' when editing an article
            } else if (!isEditing && linkPath === 'article_form.php') {
                link.classList.add('active'); // Highlight 'Add New Article' when on the blank form
            }
        } else if (linkPath === currentPath) {
            link.classList.add('active');
        } else if ((currentPath === 'index.php' || currentPath === '') && (linkPath === 'dashboard.php' || linkPath === 'index.php')) {
             // If admin lands on admin/ or admin/index.php, highlight dashboard
            if (link.getAttribute('href').includes('dashboard.php') || link.getAttribute('href') === './' || link.getAttribute('href') === 'index.php') {
                 link.classList.add('active');
            }
        }
    });


    // --- Generic Modal Controls (example if you have multiple modals) ---
    // The specific modal controls are in dashboard.php and article_form.php
     window.openModal = function(modalId) {
         const modal = document.getElementById(modalId);
         if (modal) modal.classList.remove('hidden');
     }
     window.closeModal = function(modalId) {
         const modal = document.getElementById(modalId);
         if (modal) modal.classList.add('hidden');
     }


    // --- Flash Message Handling (from URL params) ---
    // This is also handled on individual pages like login and article_form.
    // Centralizing it here might be useful if you have many pages that need it.
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');
    const messageContainerId = urlParams.get('msg_container_id') || 'globalAdminMessage'; // Allow specifying container
    const globalMessageContainer = document.getElementById(messageContainerId);

    if (status && msg && globalMessageContainer) {
        globalMessageContainer.innerHTML = `<i class="fas ${status === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} mr-2"></i> ${decodeURIComponent(msg)}`;
        if (status === 'success') {
            globalMessageContainer.className = 'p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg';
        } else if (status === 'error') {
            globalMessageContainer.className = 'p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg';
        } else if (status === 'info') {
            globalMessageContainer.className = 'p-4 mb-4 text-sm text-blue-700 bg-blue-100 rounded-lg';
        }
        globalMessageContainer.classList.remove('hidden');

        // Clean the URL
        const paramsToRemove = ['status', 'msg', 'msg_container_id'];
        let currentUrl = new URL(window.location.href);
        paramsToRemove.forEach(param => currentUrl.searchParams.delete(param));
        window.history.replaceState({}, document.title, currentUrl.toString());
    }

    // --- Add more global admin scripts here ---
    // Example: Initializing a date picker, char counter for textareas, etc.

});

// You might want a function to show a more dynamic notification/toast
 function showAdminToast(message, type = 'info') { // types: info, success, warning, error
     const toastContainer = document.getElementById('toast-container') || createToastContainer();
     const toast = document.createElement('div');
     toast.className = `p-4 mb-3 rounded-lg shadow-lg text-white fixed bottom-5 right-5 transition-opacity duration-300 ease-in-out`;
     let iconClass = '';
     if (type === 'success') {
         toast.classList.add('bg-green-500');
         iconClass = 'fas fa-check-circle';
     } else if (type === 'error') {
         toast.classList.add('bg-red-500');
         iconClass = 'fas fa-exclamation-circle';
     } else if (type === 'warning') {
         toast.classList.add('bg-yellow-500');
         iconClass = 'fas fa-exclamation-triangle';
     } else { // info
         toast.classList.add('bg-blue-500');
         iconClass = 'fas fa-info-circle';
     }
     toast.innerHTML = `<i class="${iconClass} mr-2"></i> ${message}`;
     toastContainer.appendChild(toast);
     setTimeout(() => {
         toast.style.opacity = '0';
         setTimeout(() => toast.remove(), 300);
     }, 3000); // Hide after 3 seconds
 }

 function createToastContainer() {
     let container = document.createElement('div');
     container.id = 'toast-container';
     container.className = 'fixed bottom-5 right-5 z-[100]'; // Ensure high z-index
     document.body.appendChild(container);
     return container;
 }

 showAdminToast('Article saved successfully!', 'success');
