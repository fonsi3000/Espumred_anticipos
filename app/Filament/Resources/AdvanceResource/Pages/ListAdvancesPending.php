<?php

namespace App\Filament\Resources\AdvancePendingResource\Pages;

use App\Filament\Resources\AdvancePendingResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAdvancesPending extends ListRecords
{
    protected static string $resource = AdvancePendingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
