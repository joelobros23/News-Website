<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - News Week</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-200 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 md:p-12 rounded-lg shadow-xl w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600">News Weekly</h1>
            <p class="text-gray-600">Admin Panel Login</p>
        </div>

        <!-- Login Form -->
        <!-- The action attribute will point to a PHP script that handles authentication -->
        <!-- For example: action="auth.php?action=login" method="POST" -->
        <form id="adminLoginForm" action="auth.php?action=login" method="POST">
            

            <?php 
            if (isset($_GET['error'])) {
                echo '<div class="mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">';
                if ($_GET['error'] == 'invalid_credentials') {
                    echo '<i class="fas fa-exclamation-triangle mr-2"></i> Invalid username or password.';
                } elseif ($_GET['error'] == 'empty_fields') {
                    echo '<i class="fas fa-exclamation-triangle mr-2"></i> Please fill in all fields.';
                } else {
                    echo '<i class="fas fa-exclamation-triangle mr-2"></i> An unknown error occurred.';
                }
                echo '</div>';
            }
            ?>

            <div id="loginErrorMessage" class="hidden mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i> <span id="errorMessageText"></span>
            </div>


            <div class="mb-6">
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-user text-gray-400"></i>
                    </span>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        required
                        class="w-full p-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        placeholder="Enter your username"
                    >
                </div>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                 <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-lock text-gray-400"></i>
                    </span>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required
                        class="w-full p-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        placeholder="Enter your password"
                    >
                </div>
            </div>

            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                </div>
                <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-500">Forgot password?</a>
            </div>

            <div>
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </div>
        </form>

        <p class="mt-8 text-center text-sm text-gray-500">
            Back to <a href="../index.php" class="font-medium text-blue-600 hover:text-blue-500">Main Site</a>
        </p>
    </div>

    <!-- Admin Specific JS -->
    <script src="js/admin_script.js"></script>
    <script>
        // Basic client-side validation (optional, PHP handles server-side)
        const loginForm = document.getElementById('adminLoginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const loginErrorMessage = document.getElementById('loginErrorMessage');
        const errorMessageText = document.getElementById('errorMessageText');

        if (loginForm) {
            loginForm.addEventListener('submit', function(event) {
                let isValid = true;
                let message = '';

                if (usernameInput.value.trim() === '' || passwordInput.value.trim() === '') {
                    isValid = false;
                    message = 'Please fill in both username and password.';
                }
                // Add more complex validation if needed (e.g., password strength)

                if (!isValid) {
                    event.preventDefault(); // Stop form submission
                    errorMessageText.textContent = message;
                    loginErrorMessage.classList.remove('hidden');
                } else {
                    loginErrorMessage.classList.add('hidden');
                }
            });
        }
         // Check for error messages from URL (passed by PHP redirect)
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        if (error && loginErrorMessage && errorMessageText) {
            let message = 'An unknown error occurred.';
            if (error === 'invalid_credentials') {
                message = 'Invalid username or password.';
            } else if (error === 'empty_fields') {
                message = 'Please fill in all fields.';
            } else if (error === 'not_logged_in') {
                message = 'You need to login to access the admin panel.';
            }
             else if (error === 'session_expired') {
                message = 'Your session has expired. Please login again.';
            }
            errorMessageText.textContent = message;
            loginErrorMessage.classList.remove('hidden');

            // Clean the URL to remove error query param
            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        }
    </script>
</body>
</html>
