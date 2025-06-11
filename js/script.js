// js/script.js

document.addEventListener('DOMContentLoaded', () => {
    // Update footer year (already in HTML, but can be centralized here)
    const currentYearElement = document.getElementById('currentYear');
    if (currentYearElement) {
        currentYearElement.textContent = new Date().getFullYear();
    }

    // --- Placeholder for Search Functionality ---
    // In a real PHP application, the form submission would handle the search.
    // This JS might be used for AJAX search suggestions or client-side filtering if desired.
    const searchForm = document.querySelector('form[action="search.php"]');
    if (searchForm) {
        searchForm.addEventListener('submit', (event) => {
            const queryInput = searchForm.querySelector('input[name="query"]');
            if (queryInput && queryInput.value.trim() === '') {
                // Basic validation: prevent empty search
                alert('Please enter a search term.'); // Avoid alerts, use a custom message display if needed
                console.warn('Search query is empty. Form submission will proceed for server-side handling.');
                // Or, prevent submission and show a custom message:
                event.preventDefault();
                // displaySearchError('Please enter a search term.');
            }
            // The form will submit to search.php for server-side processing.
        });
    }

    // --- Smooth Scrolling for Anchor Links (Optional) ---
    // Example: If you have links like <a href="#sectionId">Scroll to Section</a>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const hrefAttribute = this.getAttribute('href');
            // Ensure it's not just a hash for non-scrolling purposes
            if (hrefAttribute && hrefAttribute !== '#' && document.querySelector(hrefAttribute)) {
                e.preventDefault();
                document.querySelector(hrefAttribute).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // --- Mobile Menu Toggle (Example if you add a hamburger menu later) ---
     const mobileMenuButton = document.getElementById('mobile-menu-button');
     const mobileMenu = document.getElementById('mobile-menu');
     if (mobileMenuButton && mobileMenu) {
         mobileMenuButton.addEventListener('click', () => {
             mobileMenu.classList.toggle('hidden'); // Assuming Tailwind's 'hidden' class for visibility
         });
     }

    console.log('News Week public script loaded.');

    // You can add more interactive features here:
    // - Lazy loading for images
    // - Client-side filtering of articles (if a small set is loaded)
    // - Handling user interactions for light/dark mode toggle
});

// Function to display a custom search error (example)
 function displaySearchError(message) {
     let errorElement = document.getElementById('searchError');
     if (!errorElement) {
         errorElement = document.createElement('p');
         errorElement.id = 'searchError';
         errorElement.className = 'text-red-500 text-sm mt-2'; // Tailwind classes
         const searchForm = document.querySelector('form[action="search.php"]');
         if (searchForm) {
             searchForm.parentNode.insertBefore(errorElement, searchForm.nextSibling);
         }
     }
     errorElement.textContent = message;
     // Optionally, remove the message after a few seconds
     setTimeout(() => {
         if (errorElement) errorElement.remove();
     }, 3000);
 }
