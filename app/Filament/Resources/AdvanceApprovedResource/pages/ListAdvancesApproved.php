<?php

namespace App\Filament\Resources\ListAdvancesApproved\Pages;

use App\Filament\Resources\AdvanceApprovedResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAdvancesApproved extends ListRecords
{
    protected static string $resource = AdvanceApprovedResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
