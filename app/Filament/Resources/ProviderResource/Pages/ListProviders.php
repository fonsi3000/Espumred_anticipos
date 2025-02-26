<?php

namespace App\Filament\Resources\ProviderResource\Pages;

use App\Filament\Resources\ProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use App\Imports\ProviderImport;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->slideOver()
                ->color("primary")
                ->use(ProviderImport::class)
                ->label('Importar Proveedores'),
            Actions\CreateAction::make()
                ->modalHeading('Crear Proveedor')
                ->modalWidth('4xl'),
        ];
    }
}
