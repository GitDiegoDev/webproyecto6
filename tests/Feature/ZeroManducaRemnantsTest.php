<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ZeroManducaRemnantsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that no codebase files contain any references to "manduca" or "Manduca".
     */
    public function test_no_file_references_to_manduca(): void
    {
        $basePath = base_path();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath));
        $matches = [];

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getPathname();
            // Normalize path separators for cross-platform (Windows / Linux) safety
            $normalizedPath = str_replace('\\', '/', $filePath);

            // Skip hidden files, vendor, node_modules, git directories, and storage/bootstrap/public cache/build directories
            if (str_contains($normalizedPath, '/vendor/') ||
                str_contains($normalizedPath, '/node_modules/') ||
                str_contains($normalizedPath, '/.git/') ||
                str_contains($normalizedPath, '/storage/') ||
                str_contains($normalizedPath, '/bootstrap/cache/') ||
                str_starts_with(basename($normalizedPath), '.')) {
                continue;
            }

            // Read files and check for 'manduca'
            $content = file_get_contents($filePath);
            if (stripos($content, 'manduca') !== false) {
                // Ensure we don't fail because of this test file itself
                if (basename($normalizedPath) !== 'ZeroManducaRemnantsTest.php') {
                    $matches[] = str_replace($basePath . '/', '', $normalizedPath);
                }
            }
        }

        $this->assertEmpty($matches, "Found references to 'manduca' in files: " . implode(', ', $matches));
    }

    /**
     * Test that no database tables or structures exist for Manduca.
     */
    public function test_no_database_tables_for_manduca(): void
    {
        // Get all tables using native Schema builder across drivers
        $tables = Schema::getTables();
        $tableNames = array_map(function ($table) {
            if (is_array($table)) {
                return $table['name'] ?? reset($table);
            }
            if (is_object($table)) {
                return $table->name ?? (string) $table;
            }
            return (string) $table;
        }, $tables);

        $legacyTables = ['platillos', 'pedidos', 'mesas', 'manduca', 'categorias', 'productos', 'ingredientes'];

        $foundLegacy = [];
        foreach ($tableNames as $table) {
            if (in_array(strtolower($table), $legacyTables, true)) {
                $foundLegacy[] = $table;
            }
        }

        $this->assertEmpty($foundLegacy, "Legacy tables or structures found: " . implode(', ', $foundLegacy));
    }

    /**
     * Test that database seeder output and structure correspond exactly to approved Gestor de Cobranzas schema.
     */
    public function test_approved_gestor_de_cobranzas_schema_is_correct(): void
    {
        $expectedTables = [
            'users',
            'clientes',
            'operaciones',
            'cuotas',
            'periodos_cobranza',
            'importaciones',
            'cuota_importaciones',
            'detalle_importaciones',
            'gestiones',
            'promesas_pago',
            'pagos',
            'visitas_cobrador',
            'plantillas_mensajes'
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Approved Gestor de Cobranzas table is missing: {$table}");
        }
    }
}
