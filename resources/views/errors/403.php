<?php
/**
 * 403 Forbidden Error Page
 * 
 * Displayed when user doesn't have required permissions
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
    <title>403 - Access Forbidden</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <i class="fas fa-lock text-6xl text-red-500"></i>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Access Forbidden</h1>
            
            <p class="text-gray-600 mb-6">
                You don't have permission to access this resource.
            </p>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-800">
                    <i class="fas fa-exclamation-circle"></i>
                    If you believe this is an error, please contact your administrator.
                </p>
            </div>
            
            <div class="flex gap-3">
                <a href="javascript:history.back()" class="flex-1 btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
                <a href="?page=dashboard" class="flex-1 btn btn-primary">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
