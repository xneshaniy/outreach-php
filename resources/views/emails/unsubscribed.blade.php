<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed | Outreach CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center px-4 max-w-md">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Successfully Unsubscribed</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            <strong>{{ $email }}</strong> has been removed from our mailing list. You will no longer receive emails from this campaign.
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-500">
            If this was a mistake, please contact the sender directly.
        </p>
    </div>
</body>
</html>