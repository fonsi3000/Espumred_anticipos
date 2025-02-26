<?php

namespace App\Filament\Resources\AdvanceUserResource\Pages;

use App\Filament\Resources\AdvanceUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvanceUser extends CreateRecord
{
    protected static string $resource = AdvanceUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
