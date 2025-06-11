<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - News Week</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-200 flex items-center justify-center min-h-screen py-12">

    <div class="bg-white p-8 md:p-12 rounded-lg shadow-xl w-full max-w-lg">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600">News Week</h1>
            <p class="text-gray-600">Create New Admin Account</p>
            <p class="text-xs text-red-500 mt-2"><strong>Warning:</strong> This page should be protected or removed in a production environment.</p>
        </div>

        <form id="adminRegistrationForm" action="auth.php?action=register" method="POST">
            
            <div id="registrationMessage" class="hidden mb-4 p-3 rounded-md text-sm">
                </div>

            <div class="mb-4">
                <label for="reg_username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-user text-gray-400"></i>
                    </span>
                    <input 
                        type="text" 
                        name="reg_username" 
                        id="reg_username" 
                        required
                        class="w-full p-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        placeholder="Choose a username (e.g., new_admin)"
                        minlength="4"
                    >
                </div>
                <p id="usernameError" class="text-red-500 text-xs mt-1 hidden"></p>
            </div>

            <div class="mb-4">
                <label for="reg_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </span>
                    <input 
                        type="email" 
                        name="reg_email" 
                        id="reg_email" 
                        required
                        class="w-full p-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        placeholder="your.email@example.com"
                    >
                </div>
                <p id="emailError" class="text-red-500 text-xs mt-1 hidden"></p>
            </div>

            <div class="mb-4">
                <label for="reg_password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                 <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-lock text-gray-400"></i>
                    </span>
                    <input 
                        type="password" 
                        name="reg_password" 
                        id="reg_password" 
                        required
                        class="w-full p-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        placeholder="Create a strong password"
                        minlength="8"
                    >
                </div>
                <p id="passwordError" class="text-red-500 text-xs mt-1 hidden">Password must be at least 8 characters long.</p>
            </div>

            <div class="mb-6">
                <label for="reg_confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                 <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-check-circle text-gray-400"></i>
                    </span>
                    <input 
                        type="password" 
                        name="reg_confirm_password" 
                        id="reg_confirm_password" 
                        required
                        class="w-full p-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        placeholder="Re-enter your password"
                    >
                </div>
                <p id="confirmPasswordError" class="text-red-500 text-xs mt-1 hidden">Passwords do not match.</p>
            </div>

            <div>
                <button 
                    type="submit" 
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150 ease-in-out"
                >
                    <i class="fas fa-user-plus mr-2"></i> Register Admin Account
                </button>
            </div>
        </form>

        <p class="mt-8 text-center text-sm text-gray-500">
            Already have an account? <a href="index.php" class="font-medium text-blue-600 hover:text-blue-500">Login here</a>
        </p>
         <p class="mt-2 text-center text-sm text-gray-500">
            Back to <a href="../index.php" class="font-medium text-blue-600 hover:text-blue-500">Main Site</a>
        </p>
    </div>

    <script>
        // Client-side validation (basic)
        const form = document.getElementById('adminRegistrationForm');
        const usernameInput = document.getElementById('reg_username');
        const emailInput = document.getElementById('reg_email');
        const passwordInput = document.getElementById('reg_password');
        const confirmPasswordInput = document.getElementById('reg_confirm_password');
        
        const usernameError = document.getElementById('usernameError');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');
        const registrationMessage = document.getElementById('registrationMessage');

        // Function to show/hide error messages
        function showError(element, message) {
            element.textContent = message;
            element.classList.remove('hidden');
        }
        function hideError(element) {
            element.textContent = '';
            element.classList.add('hidden');
        }

        if(form) {
            form.addEventListener('submit', function(event) {
                let isValid = true;
                hideError(usernameError);
                hideError(emailError);
                hideError(passwordError);
                hideError(confirmPasswordError);
                registrationMessage.classList.add('hidden');

                // Username validation
                if (usernameInput.value.trim().length < 4) {
                    showError(usernameError, 'Username must be at least 4 characters long.');
                    isValid = false;
                }

                // Email validation (basic)
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailInput.value.trim())) {
                    showError(emailError, 'Please enter a valid email address.');
                    isValid = false;
                }

                // Password length validation
                if (passwordInput.value.length < 8) {
                    showError(passwordError, 'Password must be at least 8 characters long.');
                    isValid = false;
                } else {
                     hideError(passwordError); // Hide if it was previously shown and now valid
                }


                // Confirm password validation
                if (passwordInput.value !== confirmPasswordInput.value) {
                    showError(confirmPasswordError, 'Passwords do not match.');
                    isValid = false;
                } else if (confirmPasswordInput.value.length === 0 && passwordInput.value.length > 0) {
                    showError(confirmPasswordError, 'Please confirm your password.');
                    isValid = false;
                }


                if (!isValid) {
                    event.preventDefault(); // Stop form submission
                    registrationMessage.textContent = 'Please correct the errors above.';
                    registrationMessage.className = 'mb-4 p-3 rounded-md text-sm bg-red-100 text-red-700';
                    registrationMessage.classList.remove('hidden');
                } else {
                    // Optionally, disable button to prevent multiple submissions
                    form.querySelector('button[type="submit"]').disabled = true;
                    form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                }
            });
        }

        // Display messages from URL parameters (after PHP redirect)
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');
        const errorField = urlParams.get('field');

        if (status && msg && registrationMessage) {
            registrationMessage.innerHTML = decodeURIComponent(msg); // Use innerHTML if msg contains HTML (e.g. links)
            if (status === 'success') {
                registrationMessage.className = 'mb-4 p-3 rounded-md text-sm bg-green-100 text-green-700';
            } else if (status === 'error') {
                registrationMessage.className = 'mb-4 p-3 rounded-md text-sm bg-red-100 text-red-700';
                if(errorField) {
                    if(errorField === 'username' && usernameError) showError(usernameError, decodeURIComponent(msg));
                    else if(errorField === 'email' && emailError) showError(emailError, decodeURIComponent(msg));
                    // For general errors, registrationMessage will show it.
                }
            }
            registrationMessage.classList.remove('hidden');

            // Clean the URL
            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        }
    </script>
</body>
</html>
