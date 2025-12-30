<?php
/**
 * 401 Unauthorized Error Page
 * 
 * Displayed when user is not authenticated
 * 
 * @package iACC
 * @author Development Team
 * @since v1.9
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-user-slash text-6xl text-yellow-500"></i>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Unauthorized</h1>
            
            <p class="text-gray-600 mb-6">
                Your session has expired or you are not authenticated.
            </p>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle"></i>
                    Please login again to continue.
                </p>
            </div>
            
            <a href="?page=login" class="btn btn-primary w-full">
                <i class="fas fa-sign-in-alt"></i> Go to Login
            </a>
        </div>
    </div>
</body>
</html>
