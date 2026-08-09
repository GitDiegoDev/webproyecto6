<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Gestor de Cobranzas - En Construcción</title>

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
                    <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Gestor de Cobranzas
                    </h1>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    v{{ app()->version() }}
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-4xl mx-auto px-6 py-12 flex flex-col items-center justify-center text-center gap-8">

            <!-- Hero section -->
            <div class="space-y-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400">
                    🚧 Sitio en Construcción
                </span>
                <h2 class="text-4xl lg:text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    Optimiza la cobranza de tu financiera
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                    Una herramienta complementaria y personal para organizar la cartera mensual, priorizar clientes, registrar gestiones, promesas de pago, visitas de cobradores y mucho más.
                </p>
            </div>

            <!-- Livewire Test Component -->
            <div class="w-full max-w-md py-4">
                <livewire:test-component />
            </div>

            <!-- Features Quick View (Draft) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-left w-full mt-4">
                <div class="p-5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-lg mb-2">📊</div>
                    <h3 class="font-bold text-gray-950 dark:text-white mb-1">Agenda Diaria</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Priorización inteligente de contactos y promesas de pago que vencen en el día actual.</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-lg mb-2">📥</div>
                    <h3 class="font-bold text-gray-950 dark:text-white mb-1">Importación Inteligente</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Carga archivos XLSX o CSV de la financiera para sincronizar el estado oficial sin perder tus gestiones.</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="text-lg mb-2">💬</div>
                    <h3 class="font-bold text-gray-950 dark:text-white mb-1">Plantillas de Mensajes</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Copiar y personalizar plantillas predefinidas para enviar recordatorios rápidos por WhatsApp.</p>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
            <div class="max-w-7xl mx-auto px-6 space-y-2">
                <p>&copy; {{ date('Y') }} Gestor de Cobranzas. Todos los derechos reservados.</p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500">Impulsado por PHP 8.3 / Laravel 11 / Livewire / Tailwind CSS</p>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
