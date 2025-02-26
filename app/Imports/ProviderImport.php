<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use App\Models\Provider;

class ProviderImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Saltar la primera fila (encabezado)
            if ($index === 0) {
                continue;
            }

            // Asignar valores según el índice de columna
            $documentNumber = trim($row[1] ?? ''); // Segunda columna (NIT/Cédula)

            if (!$documentNumber) {
                continue; // Si el NIT/Cédula está vacío, omitir la fila
            }

            // Verificar si el NIT/Cédula ya está registrado
            $exists = Provider::where('document_number', $documentNumber)->exists();

            if (!$exists) {
                Provider::create([
                    'document_number' => $documentNumber,
                    'name' => $row[0] ?? 'Sin nombre', // Primera columna
                    'SAP_code' => $row[2] ?? null, // Nueva posición: tercera columna para código SAP
                    'address' => $row[3] ?? 'Desconocido', // Cuarta columna (movida)
                    'phone' => $row[4] ?? 'No registrado', // Quinta columna (movida)
                    'country' => $row[5] ?? 'No especificado', // Sexta columna (movida)
                    'city' => $row[6] ?? 'No especificado', // Séptima columna (movida)
                ]);
            }
        }
    }
}
