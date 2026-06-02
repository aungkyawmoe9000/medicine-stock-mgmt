<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Medicine Stock Management</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen flex">
            <aside class="w-64 bg-blue-900 text-white hidden md:flex flex-col sticky top-0 h-screen">
                <div class="p-6">
                    <h1 class="text-xl font-bold tracking-wider text-blue-100">
                        MEDICINE STOCK
                    </h1>
                    <p class="text-xs text-blue-300 uppercase">Management System</p>
                </div>

                <nav class="flex-grow px-4 space-y-2">
                    @livewire('layout.navigation')
                </nav>

                <div class="p-6 border-t border-blue-800">
                    <livewire:welcome.navigation />
                </div>
            </aside>

            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                <header class="bg-white shadow-sm border-b z-10">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            {{ $header ?? 'Dashboard' }}
                        </h2>
                        <div class="md:hidden">
                            </div>
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
