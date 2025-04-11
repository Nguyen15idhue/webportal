<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { /* Tailwind config from user HTML */
             theme: { extend: { colors: { primary: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d' } } } }
        }
    </script>
    <style>
        /* Add minimal styles if needed for login/register */
        body { font-family: sans-serif; }
         input[type="text"], input[type="email"], input[type="password"] {
            @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-300 focus:border-primary-500 transition duration-150 ease-in-out shadow-sm text-sm;
         }
          button[type="submit"] {
             @apply w-full py-2.5 px-5 rounded-lg font-medium text-sm inline-flex items-center justify-center gap-1 transition duration-150 ease-in-out bg-primary-500 hover:bg-primary-600 text-white shadow;
         }
         .alert { @apply p-4 mb-4 text-sm rounded-lg; }
         .alert-success { @apply bg-green-100 text-green-700; }
         .alert-error { @apply bg-red-100 text-red-700; }
         .alert-info { @apply bg-blue-100 text-blue-700; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md">
         <!-- Flash Messages -->
        <?php if ($flash_success = App\Core\Auth::getFlash('success')): ?>
            <div class="alert alert-success" role="alert"><?= e($flash_success) ?></div>
        <?php endif; ?>
        <?php if ($flash_error = App\Core\Auth::getFlash('error')): ?>
            <div class="alert alert-error" role="alert"><?= e($flash_error) ?></div>
        <?php endif; ?>
         <?php if ($flash_info = App\Core\Auth::getFlash('info')): ?>
            <div class="alert alert-info" role="alert"><?= e($flash_info) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </div>

    <!-- Toast Container (Optional, JS will create it if needed) -->
    <div id="toast-container"></div>
    <script>
       // Simple Toast Function (copy from functions.js or user layout if needed)
       function showToast(message, type = 'success') { /* ... toast implementation ... */ }
    </script>
</body>
</html>