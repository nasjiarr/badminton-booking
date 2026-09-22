<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">

        <title inertia>{{ config('app.name', 'Smash Arena') }}</title>

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#0A0F1D">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Smash Arena">
        <meta name="application-name" content="Smash Arena">
        <meta name="description" content="Sistem Reservasi & Booking Lapangan Badminton Modern Smash Arena">

        <!-- Favicon & PWA Icons -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="alternate icon" href="/favicon.ico">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
        <link rel="manifest" href="/build/manifest.webmanifest">

        <!-- Fonts: Plus Jakarta Sans (Clean Body/UI) & Barlow Condensed (Bold Athletic Display & Numbers) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|barlow-condensed:600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-100 selection:bg-volt selection:text-arena-base">
        @inertia
    </body>
</html>
