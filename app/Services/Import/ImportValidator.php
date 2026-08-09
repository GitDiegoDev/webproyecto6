<?php

namespace App\Services\Import;

class ImportValidator
{
    /**
     * Validate the file extension.
     */
    public function validateExtension(string $extension): bool
    {
        return in_array(strtolower($extension), ['csv', 'xlsx']);
    }

    /**
     * Validate the mapping structure.
     * Ensures all mandatory fields are mapped.
     */
    public function validateHeaders(array $mapping): array
    {
        $errors = [];
        $mandatory = ['numero_solicitud', 'numero_cuota', 'nombre', 'apellido', 'importe_original'];

        foreach ($mandatory as $field) {
            if (empty($mapping[$field])) {
                $errors[] = "El campo obligatorio '" . $field . "' no está mapeado.";
            }
        }

        return $errors;
    }

    /**
     * Validate a single row's values.
     * Returns an array of error messages, or empty array if valid.
     */
    public function validateRow(array $row): array
    {
        $errors = [];

        // Solicitud
        if (empty($row['numero_solicitud'])) {
            $errors[] = "Número de solicitud vacío.";
        }

        // Cuota
        if (!isset($row['numero_cuota']) || $row['numero_cuota'] === '') {
            $errors[] = "Número de cuota vacío o cero.";
        } elseif (!is_numeric($row['numero_cuota']) || intval($row['numero_cuota']) <= 0) {
            $errors[] = "Número de cuota inválido: " . $row['numero_cuota'];
        }

        // Importe original
        if (!isset($row['importe_original']) || $row['importe_original'] === '') {
            $errors[] = "Importe original vacío.";
        } elseif (!is_numeric($row['importe_original']) || floatval($row['importe_original']) < 0) {
            $errors[] = "Importe original inválido (debe ser numérico no negativo): " . $row['importe_original'];
        }

        // Punitorios (optional, but must be numeric if present)
        if (isset($row['punitorios']) && $row['punitorios'] !== '') {
            if (!is_numeric($row['punitorios']) || floatval($row['punitorios']) < 0) {
                $errors[] = "Punitorios inválidos (debe ser numérico no negativo): " . $row['punitorios'];
            }
        }

        // Client details
        if (empty($row['nombre']) || empty($row['apellido'])) {
            $errors[] = "Nombre y apellido del cliente son obligatorios.";
        }

        if (empty($row['documento']) && empty($row['codigo_cliente_oficial'])) {
            $errors[] = "Se requiere al menos un identificador oficial de cliente (documento o código oficial).";
        }

        // Dia cobro (optional, but if present must be 1-31)
        if (isset($row['dia_cobro']) && $row['dia_cobro'] !== '') {
            if (!is_numeric($row['dia_cobro']) || intval($row['dia_cobro']) < 1 || intval($row['dia_cobro']) > 31) {
                $errors[] = "Día de cobro inválido (debe ser entre 1 y 31): " . $row['dia_cobro'];
            }
        }

        return $errors;
    }
}
