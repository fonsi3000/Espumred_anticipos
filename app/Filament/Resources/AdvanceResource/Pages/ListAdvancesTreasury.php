<?php

namespace App\Filament\Resources\AdvanceTreasuryResource\Pages;

use App\Filament\Resources\AdvanceTreasuryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAdvancesTreasury extends ListRecords
{
    protected static string $resource = AdvanceTreasuryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
