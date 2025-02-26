<?php

namespace App\Filament\Resources\AdvanceUserResource\Pages;

use App\Filament\Resources\AdvanceUserResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditAdvanceUser extends EditRecord
{
    protected static string $resource = AdvanceUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
