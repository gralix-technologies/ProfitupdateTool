<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            window.Laravel = {
                csrfToken: '{{ csrf_token() }}'
            };
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Gralix Favicon -->
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512x512.png">
        <link rel="apple-touch-icon" sizes="192x192" href="/favicon-192x192.png">
        <link rel="apple-touch-icon" sizes="512x512" href="/favicon-512x512.png">
        <meta name="theme-color" content="#222551">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        
        <!-- Full-Width Layout - No White Spaces -->
        <style>
            
                box-sizing: border-box;
            }
            
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background-color: #f8f9fa;
                min-height: 100vh;
                width: 100%;
                overflow-x: hidden;
            }
            
            
            .page {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                margin: 0 !important;
                padding: 0 !important;
                width: 100%;
            }
            
            
            .navbar {
                background: #222551;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                padding: 0.75rem 0;
                flex-shrink: 0;
                margin: 0 !important;
                width: 100%;
            }
            
            .navbar .container-xl {
                margin: 0 auto;
                padding-left: 1.5rem;
                padding-right: 1.5rem;
                max-width: none;
                width: 100%;
            }
            
            .navbar-brand {
                color: white !important;
                font-weight: 600;
                font-size: 1.125rem;
                margin: 0;
            }
            
            
            .navbar-nav .nav-link {
                color: rgba(255, 255, 255, 0.8) !important;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                margin: 0 0.125rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
            }
            
            .navbar-nav .nav-link:hover {
                color: white !important;
                background-color: rgba(255, 255, 255, 0.1);
            }
            
            .navbar-nav .nav-link.active {
                color: white !important;
                background-color: #E85C2C;
                box-shadow: 0 2px 4px rgba(232, 92, 44, 0.3);
            }
            
            .navbar-nav .nav-link-icon {
                margin-right: 0.5rem;
                opacity: 0.8;
            }
            
            .navbar-nav .nav-link.active .nav-link-icon {
                opacity: 1;
            }
            
            
            .navbar-nav.flex-row .nav-link {
                color: rgba(255, 255, 255, 0.8) !important;
                padding: 0.5rem;
            }
            
            .navbar-nav.flex-row .nav-link:hover {
                color: white !important;
            }
            
            
            .navbar .text-white {
                color: white !important;
            }
            
            
            .main-content {
                flex: 1;
                background-color: #f8f9fa;
                min-height: calc(100vh - 70px);
                width: 100%;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            
            .main-content .container-xl,
            .main-content .container-fluid {
                max-width: none !important;
                width: 100% !important;
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
                margin: 0 !important;
            }
            
            
            .page-header {
                background-color: white;
                border-bottom: 1px solid var(--tblr-border-color);
                padding: 1.5rem 0;
                margin: 0 0 1.5rem 0;
                width: 100%;
            }
            
            .page-body {
                padding: 0 !important;
                margin: 0 !important;
                width: 100%;
            }
            
            
            .avatar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
            }
            
            
            .dropdown-menu {
                border: 1px solid var(--tblr-border-color);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            
            
            .card {
                border: 1px solid var(--tblr-border-color);
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            
            .container, .container-fluid, .container-xl, .container-lg, .container-md, .container-sm {
                padding-left: 1.5rem !important;
                padding-right: 1.5rem !important;
            }
            
            
            @media (max-width: 767.98px) {
                .navbar-collapse {
                    background: rgba(0, 0, 0, 0.1);
                    margin-top: 1rem;
                    padding: 1rem;
                    border-radius: 0.5rem;
                }
                
                .navbar-nav .nav-link {
                    padding: 0.75rem 1rem;
                }
                
                .navbar .container-xl,
                .main-content .container-xl,
                .main-content .container-fluid {
                    padding-left: 1rem !important;
                    padding-right: 1rem !important;
                }
                
                .page-header {
                    padding: 1rem 0;
                    margin-bottom: 1rem;
                }
            }
            
            
            .page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            
            body {
                background-color: #f8f9fa !important;
            }
        </style>
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>


