<?php

namespace App\Services\Import;

use App\Models\Cliente;
use App\Models\Operacion;
use App\Models\Cuota;
use App\Models\CuotaImportacion;
use App\Models\DetalleImportacion;
use Illuminate\Support\Facades\DB;

class ImportProcessor
{
    /**
     * Process a single mapped row.
     * Assumes row contains already validated, formatted values.
     *
     * @param array $row Mapped row data
     * @param int $importationId Current importation ID
     * @param int $lineNumber Excel row number
     * @return array Array with keys: 'status' (new, updated, unchanged, error) and 'cuota_id'
     */
    public function processRow(array $row, int $importationId, int $lineNumber): array
    {
        // 1. Identify/Create Cliente
        $cliente = $this->findOrCreateCliente($row);

        // 2. Identify/Create Operacion
        $operacion = $this->findOrCreateOperacion($row, $cliente->id);

        // 3. Process Cuota
        $numeroCuota = intval($row['numero_cuota']);
        $importeOriginal = floatval($row['importe_original']);
        $punitorios = isset($row['punitorios']) && $row['punitorios'] !== '' ? floatval($row['punitorios']) : 0.00;
        $totalActualizado = $importeOriginal + $punitorios;
        $diaCobro = isset($row['dia_cobro']) && $row['dia_cobro'] !== '' ? intval($row['dia_cobro']) : 1;

        $cuota = Cuota::where('operacion_id', $operacion->id)
            ->where('numero_cuota', $numeroCuota)
            ->first();

        $status = 'unchanged';
        $action = 'sin_cambios';

        if ($cuota) {
            // Check if values actually changed
            $changedImporte = abs(floatval($cuota->importe_original) - $importeOriginal) > 0.001;
            $changedPunitorios = abs(floatval($cuota->punitorios) - $punitorios) > 0.001;
            $changedDiaCobro = intval($cuota->dia_cobro) !== $diaCobro;
            $wasAbsent = $cuota->estado_financiero === 'ausente_en_ultima_importacion';

            if ($changedImporte || $changedPunitorios || $changedDiaCobro || $wasAbsent) {
                $status = 'updated';

                if ($changedImporte && $changedPunitorios) {
                    $action = 'actualizado_importe_y_punitorios';
                } elseif ($changedImporte) {
                    $action = 'actualizado_importe';
                } elseif ($changedPunitorios) {
                    $action = 'actualizado_punitorios';
                } else {
                    $action = 'actualizado';
                }

                // Update official financial data
                $cuota->importe_original = $importeOriginal;
                $cuota->punitorios = $punitorios;
                $cuota->total_actualizado = $totalActualizado;
                $cuota->dia_cobro = $diaCobro;

                // Recalculate saldo_pendiente based on total_actualizado minus payments
                $paymentsSum = floatval($cuota->pagos()->sum('monto_cobrado'));
                $cuota->saldo_pendiente = max(0.00, $totalActualizado - $paymentsSum);

                // If it was absent, bring it back to pendiente
                if ($wasAbsent) {
                    $cuota->estado_financiero = 'pendiente';
                }

                $cuota->save();
            } else {
                $status = 'unchanged';
                $action = 'sin_cambios';
            }
        } else {
            // Create a new Cuota
            $cuota = Cuota::create([
                'operacion_id' => $operacion->id,
                'numero_cuota' => $numeroCuota,
                'dia_cobro' => $diaCobro,
                'importe_original' => $importeOriginal,
                'punitorios' => $punitorios,
                'total_actualizado' => $totalActualizado,
                'saldo_pendiente' => $totalActualizado,
                'estado_financiero' => 'pendiente',
                'estado_gestion' => 'sin_contactar',
                'prioridad' => 'media',
            ]);

            $status = 'new';
            $action = 'creado';
        }

        // 4. Create CuotaImportacion record (historical photographic snapshot)
        CuotaImportacion::create([
            'cuota_id' => $cuota->id,
            'importacion_id' => $importationId,
            'importe_original_observado' => $importeOriginal,
            'punitorios_observados' => $punitorios,
            'total_actualizado_observado' => $totalActualizado,
            'dia_cobro_observado' => $diaCobro,
            'estado_presencia' => 'presente',
        ]);

        // 5. Create DetalleImportacion record (auditing log)
        DetalleImportacion::create([
            'importacion_id' => $importationId,
            'numero_linea' => $lineNumber,
            'accion' => $action,
            'datos_crudos' => json_encode($row),
        ]);

        return [
            'status' => $status,
            'cuota_id' => $cuota->id,
        ];
    }

    /**
     * Find or create Cliente following the exact priority rules.
     */
    protected function findOrCreateCliente(array $row): Cliente
    {
        $cliente = null;

        // Priority 1: Official Client Code
        if (!empty($row['codigo_cliente_oficial'])) {
            $cliente = Cliente::where('codigo_cliente_oficial', $row['codigo_cliente_oficial'])->first();
        }

        // Priority 2: Document (DNI/CUIL)
        if (!$cliente && !empty($row['documento'])) {
            $cliente = Cliente::where('documento', $row['documento'])->first();
        }

        if ($cliente) {
            // Check empty fields and populate them, do not overwrite manually managed fields!
            $updated = false;
            if (empty($cliente->telefono) && !empty($row['telefono'])) {
                $cliente->telefono = $row['telefono'];
                $updated = true;
            }
            if (empty($cliente->domicilio) && !empty($row['domicilio'])) {
                $cliente->domicilio = $row['domicilio'];
                $updated = true;
            }
            if (empty($cliente->email) && !empty($row['email'])) {
                $cliente->email = $row['email'];
                $updated = true;
            }
            if (empty($cliente->codigo_cliente_oficial) && !empty($row['codigo_cliente_oficial'])) {
                $cliente->codigo_cliente_oficial = $row['codigo_cliente_oficial'];
                $updated = true;
            }
            if ($updated) {
                $cliente->save();
            }
            return $cliente;
        }

        // Create new Cliente if not found
        return Cliente::create([
            'nombre' => $row['nombre'],
            'apellido' => $row['apellido'],
            'documento' => $row['documento'] ?? null,
            'telefono' => $row['telefono'] ?? null,
            'domicilio' => $row['domicilio'] ?? null,
            'email' => $row['email'] ?? null,
            'codigo_cliente_oficial' => $row['codigo_cliente_oficial'] ?? null,
        ]);
    }

    /**
     * Find or create Operacion. Reuse if numero_solicitud exists.
     */
    protected function findOrCreateOperacion(array $row, int $clienteId): Operacion
    {
        $solicitud = strval($row['numero_solicitud']);

        $operacion = Operacion::where('numero_solicitud', $solicitud)->first();

        if ($operacion) {
            return $operacion;
        }

        return Operacion::create([
            'cliente_id' => $clienteId,
            'numero_solicitud' => $solicitud,
        ]);
    }
}
