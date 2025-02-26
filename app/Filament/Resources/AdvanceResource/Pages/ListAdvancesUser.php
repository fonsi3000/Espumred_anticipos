<?php

namespace App\Filament\Resources\AdvanceUserResource\Pages;

use App\Filament\Resources\AdvanceUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvancesUser extends ListRecords
{
    protected static string $resource = AdvanceUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Crear Anticipo')
                ->modalWidth('4xl'),
        ];
    }
}
