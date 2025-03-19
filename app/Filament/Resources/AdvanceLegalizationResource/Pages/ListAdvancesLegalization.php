<?php

namespace App\Filament\Resources\AdvanceLegalizationResource\Pages;

use App\Filament\Resources\AdvanceLegalizationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAdvancesLegalization extends ListRecords
{
    protected static string $resource = AdvanceLegalizationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
