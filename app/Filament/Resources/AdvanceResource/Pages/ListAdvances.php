<?php

namespace App\Filament\Resources\AdvanceResource\Pages;

use App\Filament\Resources\AdvanceResource;
use Filament\Resources\Pages\ListRecords;

class ListAdvances extends ListRecords
{
    protected static string $resource = AdvanceResource::class;

    // Deshabilitar el botón de crear anticipo
    protected function getHeaderActions(): array
    {
        return [
            // Se eliminan todas las acciones del encabezado
        ];
    }
}
