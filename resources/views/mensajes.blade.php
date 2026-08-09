<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Centro de Comunicación - Gestor de Cobranzas</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col justify-between">

        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 py-4 px-6">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">💼</span>
                    <a href="/dashboard" class="text-xl font-bold tracking-tight text-gray-900 dark:text-white hover:underline">
                        Gestor de Cobranzas
                    </a>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium flex items-center gap-4">
                    <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:underline">Dashboard</a>
                    <a href="/agenda" class="text-gray-600 dark:text-gray-300 hover:underline">Agenda</a>
                    <a href="/mensajes" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">Mensajes</a>
                    <a href="/importar" class="text-gray-600 dark:text-gray-300 hover:underline">Importar Planilla</a>
                    <span class="border-l border-gray-300 dark:border-gray-600 h-4 inline-block mx-2"></span>
                    <span>v{{ app()->version() }}</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-6 py-8">
            <livewire:centro-comunicacion-component />
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6 text-center text-xs text-gray-500 dark:text-gray-400 mt-12">
            <div class="max-w-7xl mx-auto px-6 space-y-2">
                <p>&copy; {{ date('Y') }} Gestor de Cobranzas. Todos los derechos reservados.</p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-mono">PHP 8.3 / Laravel 11 / Livewire / Tailwind CSS</p>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
