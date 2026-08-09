<?php

namespace App\Services\Import;

use App\Models\Importacion;
use App\Models\Cuota;
use App\Models\CuotaImportacion;
use App\Models\DetalleImportacion;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class ImportService
{
    protected ImportValidator $validator;
    protected ImportProcessor $processor;

    public function __construct(ImportValidator $validator, ImportProcessor $processor)
    {
        $this->validator = $validator;
        $this->processor = $processor;
    }

    /**
     * Execute the full portfolio import.
     *
     * @param string $filePath Absolute path to the uploaded file
     * @param string $originalName Original filename
     * @param int $periodoId PeriodoCobranza ID
     * @param int|null $userId User ID initiating the import
     * @param array $mapping Associative array of [system_field => header_name_in_file]
     * @return array Import summary statistics
     * @throws Exception
     */
    public function import(string $filePath, string $originalName, int $periodoId, ?int $userId, array $mapping): array
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        if (!$this->validator->validateExtension($extension)) {
            throw new Exception("Extensión de archivo inválida: .{$extension}. Sólo se permiten .csv y .xlsx");
        }

        $headerErrors = $this->validator->validateHeaders($mapping);
        if (!empty($headerErrors)) {
            throw new Exception("Estructura de mapeo inválida: " . implode(" ", $headerErrors));
        }

        // Initialize statistics
        $stats = [
            'cantidad_registros' => 0,
            'registros_nuevos' => 0,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'registros_sin_cambios' => 0,
            'benign_duplicates' => 0,
        ];

        // 1. Create the Importacion record in "procesando" state
        $importation = Importacion::create([
            'periodo_cobranza_id' => $periodoId,
            'user_id' => $userId,
            'fecha_hora' => now(),
            'nombre_archivo' => $originalName,
            'tipo_archivo' => strtolower($extension),
            'cantidad_registros' => 0,
            'registros_nuevos' => 0,
            'registros_actualizados' => 0,
            'registros_ausentes' => 0,
            'registros_errores' => 0,
            'estado' => 'procesando',
        ]);

        DB::beginTransaction();

        try {
            // Load spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                throw new Exception("El archivo de importación está vacío.");
            }

            // Extract headers (first row)
            $headers = array_map(function ($h) {
                return $h !== null ? trim($h) : '';
            }, $rows[0]);

            // Create index mapping: system_field => header_index in file
            $indexMapping = [];
            foreach ($mapping as $systemField => $headerName) {
                if ($headerName === '') {
                    continue;
                }
                $index = array_search($headerName, $headers);
                if ($index === false) {
                    throw new Exception("La columna mapeada '{$headerName}' para el campo '{$systemField}' no existe en el archivo.");
                }
                $indexMapping[$systemField] = $index;
            }

            $processedCuotaIds = [];
            $seenRows = []; // key => values for duplicate checking

            // Process data rows
            $rowCount = count($rows);
            for ($i = 1; $i < $rowCount; $i++) {
                $rawRow = $rows[$i];

                // Skip completely empty rows
                $isEmpty = true;
                foreach ($rawRow as $cell) {
                    if ($cell !== null && trim($cell) !== '') {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty) {
                    continue;
                }

                $lineNumber = $i + 1; // 1-based index for user visibility (Excel starts at row 1)

                // Map row columns to system fields
                $mappedRow = [];
                foreach ($indexMapping as $systemField => $colIndex) {
                    $val = $rawRow[$colIndex] ?? null;
                    $mappedRow[$systemField] = $val !== null ? trim($val) : null;
                }

                // Row-level validation
                $rowErrors = $this->validator->validateRow($mappedRow);
                if (!empty($rowErrors)) {
                    $stats['registros_errores']++;
                    DetalleImportacion::create([
                        'importacion_id' => $importation->id,
                        'numero_linea' => $lineNumber,
                        'accion' => 'error',
                        'detalles_error' => implode(" | ", $rowErrors),
                        'datos_crudos' => json_encode($rawRow),
                    ]);
                    continue;
                }

                // Check duplicates within the same file
                $solicitud = $mappedRow['numero_solicitud'];
                $cuotaNum = intval($mappedRow['numero_cuota']);
                $duplicateKey = $solicitud . '_' . $cuotaNum;

                if (isset($seenRows[$duplicateKey])) {
                    // Compare values to determine if benign or contradictory
                    $isIdentical = true;
                    foreach ($indexMapping as $systemField => $colIndex) {
                        if (strval($mappedRow[$systemField] ?? '') !== strval($seenRows[$duplicateKey][$systemField] ?? '')) {
                            $isIdentical = false;
                            break;
                        }
                    }

                    if ($isIdentical) {
                        // Benign duplicate: log it and skip further processing
                        $stats['benign_duplicates']++;
                        DetalleImportacion::create([
                            'importacion_id' => $importation->id,
                            'numero_linea' => $lineNumber,
                            'accion' => 'duplicado_benigno',
                            'detalles_error' => "Fila duplicada benigna (idéntica a línea " . $seenRows[$duplicateKey]['line_number'] . ")",
                            'datos_crudos' => json_encode($rawRow),
                        ]);
                    } else {
                        // Contradictory duplicate: log as error and skip
                        $stats['registros_errores']++;
                        DetalleImportacion::create([
                            'importacion_id' => $importation->id,
                            'numero_linea' => $lineNumber,
                            'accion' => 'error',
                            'detalles_error' => "Fila duplicada con datos contradictorios respecto a línea " . $seenRows[$duplicateKey]['line_number'],
                            'datos_crudos' => json_encode($rawRow),
                        ]);
                    }
                    continue;
                }

                // Mark as seen
                $mappedRow['line_number'] = $lineNumber;
                $seenRows[$duplicateKey] = $mappedRow;

                // Process the valid row
                $result = $this->processor->processRow($mappedRow, $importation->id, $lineNumber);

                $stats['cantidad_registros']++;
                $processedCuotaIds[] = $result['cuota_id'];

                if ($result['status'] === 'new') {
                    $stats['registros_nuevos']++;
                } elseif ($result['status'] === 'updated') {
                    $stats['registros_actualizados']++;
                } else {
                    $stats['registros_sin_cambios']++;
                }
            }

            // 2. Identify absent cuotas (only if there are previous completed importations for this period)
            $previousImportations = Importacion::where('periodo_cobranza_id', $periodoId)
                ->where('id', '!=', $importation->id)
                ->where('estado', 'completada')
                ->pluck('id');

            if ($previousImportations->isNotEmpty()) {
                $portfolioCuotaIds = CuotaImportacion::whereIn('importacion_id', $previousImportations)
                    ->where('estado_presencia', 'presente')
                    ->pluck('cuota_id')
                    ->unique();

                $absentCuotaIds = $portfolioCuotaIds->diff($processedCuotaIds);

                if ($absentCuotaIds->isNotEmpty()) {
                    $absentCuotas = Cuota::whereIn('id', $absentCuotaIds)->get();

                    foreach ($absentCuotas as $cuota) {
                        // Update financial state to "ausente_en_ultima_importacion"
                        $cuota->update([
                            'estado_financiero' => 'ausente_en_ultima_importacion',
                        ]);

                        // Record in cuota_importaciones
                        CuotaImportacion::create([
                            'cuota_id' => $cuota->id,
                            'importacion_id' => $importation->id,
                            'importe_original_observado' => $cuota->importe_original,
                            'punitorios_observados' => $cuota->punitorios,
                            'total_actualizado_observado' => $cuota->total_actualizado,
                            'dia_cobro_observado' => $cuota->dia_cobro,
                            'estado_presencia' => 'ausente_en_importacion',
                        ]);

                        // Record in detalle_importaciones
                        DetalleImportacion::create([
                            'importacion_id' => $importation->id,
                            'numero_linea' => 0, // 0 or null indicates background/absence processing
                            'accion' => 'marcado_ausente',
                            'datos_crudos' => json_encode(['cuota_id' => $cuota->id, 'numero_solicitud' => $cuota->operacion->numero_solicitud, 'numero_cuota' => $cuota->numero_cuota]),
                        ]);

                        $stats['registros_ausentes']++;
                    }
                }
            }

            // 3. Finalize Importacion counters and state
            $importation->update([
                'cantidad_registros' => $stats['cantidad_registros'],
                'registros_nuevos' => $stats['registros_nuevos'],
                'registros_actualizados' => $stats['registros_actualizados'],
                'registros_ausentes' => $stats['registros_ausentes'],
                'registros_errores' => $stats['registros_errores'],
                'estado' => 'completada',
            ]);

            DB::commit();

            return $stats;

        } catch (Exception $e) {
            DB::rollBack();

            // Mark import as failed
            $importation->update([
                'estado' => 'fallida',
            ]);

            // Log severe error in DetalleImportacion
            DetalleImportacion::create([
                'importacion_id' => $importation->id,
                'numero_linea' => 0,
                'accion' => 'error_grave',
                'detalles_error' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
