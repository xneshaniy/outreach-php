<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Outreach.click') }}</title>

    <meta name="description" content="Outreach.click is an email outreach automation platform designed for B2B sales teams and agencies to manage SMTP accounts, launch campaigns, and scale leads.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://outreach.click/">
    <meta property="og:title" content="Outreach.click — Email Outreach Automation Platform">
    <meta property="og:description" content="Manage SMTP accounts, launch campaigns, reach prospects, and scale your business with Outreach.click.">
    <meta property="og:image" content="https://outreach.click/images/logo-dark.svg">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://outreach.click/">
    <meta name="twitter:title" content="Outreach.click — Email Outreach Automation Platform">
    <meta name="twitter:description" content="Manage SMTP accounts, launch campaigns, reach prospects, and scale your business with Outreach.click.">
    <meta name="twitter:image" content="https://outreach.click/images/logo-dark.svg">

    <!-- Favicon & PWA Icons -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#0265D2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Outreach.click",
      "operatingSystem": "All",
      "applicationCategory": "BusinessApplication",
      "url": "https://outreach.click/",
      "description": "Powerful email outreach automation to manage SMTP accounts, launch campaigns, reach prospects, and grow your business.",
      "offers": {
        "@type": "Offer",
        "price": "29.00",
        "priceCurrency": "USD"
      }
    }
    </script>

    <!-- Scripts -->
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 h-full">
    @inertia
</body>
</html>
