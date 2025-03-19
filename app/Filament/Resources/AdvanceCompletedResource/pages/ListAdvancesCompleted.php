<?php

namespace App\Filament\Resources\AdvanceResource\Pages;

use App\Filament\Resources\AdvanceCompletedResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAdvancesCompleted extends ListRecords
{
    protected static string $resource = AdvanceCompletedResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
