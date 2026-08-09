<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 max-w-md mx-auto">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $message }}</h3>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Este es un componente Livewire de prueba para verificar que la reactividad y la integración funcionan correctamente.
    </p>

    <div class="flex gap-4">
        <!-- Livewire toggle button -->
        <button wire:click="toggleExtra" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
            {{ $showExtra ? 'Ocultar info (Livewire)' : 'Ver info (Livewire)' }}
        </button>

        <!-- Alpine.js toggle button to verify Alpine integration -->
        <div x-data="{ open: false }">
            <button @click="open = !open" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md transition-colors">
                Toggle (Alpine.js)
            </button>
            <div x-show="open" class="mt-2 text-xs text-emerald-600 dark:text-emerald-400 font-medium" x-cloak>
                ¡Alpine.js está funcionando correctamente de forma interactiva!
            </div>
        </div>
    </div>

    @if($showExtra)
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/30 text-xs text-blue-800 dark:text-blue-300 rounded border border-blue-100 dark:border-blue-800">
            ¡Excelente! Livewire ha procesado la acción en el servidor y actualizado la vista de forma reactiva sin recargar la página.
        </div>
    @endif
</div>
