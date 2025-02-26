<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceApprovedResource\Pages;
use App\Models\Advance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;

class AdvanceApprovedResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $modelLabel = 'Anticipo por Código SAP';

    protected static ?string $pluralModelLabel = 'Anticipos por Código SAP';

    protected static ?string $navigationLabel = 'Anticipos por Código SAP';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        // Reutilizamos el formulario del AdvanceResource
        return AdvanceResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Valor Total')
                    ->money('cop')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Advance::STATUS[$state])
                    ->color(fn(string $state): string => match ($state) {
                        'PENDING' => 'gray',
                        'APPROVED' => 'success',
                        'TREASURY' => 'info',
                        'LEGALIZATION' => 'warning',
                        'COMPLETED' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('advance_percentage')
                    ->label('% Anticipo')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('advance_amount')
                    ->label('Valor Anticipo')
                    ->money('cop')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('Saldo Pendiente')
                    ->money('cop')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_iva')
                    ->label('IVA')
                    ->boolean(),
                Tables\Columns\TextColumn::make('purchase_order')
                    ->label('Orden de Compra')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('approver.name')
                //     ->label('Aprobado por')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('approved_at')
                //     ->label('Fecha de Aprobación')
                //     ->dateTime()
                //     ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->relationship('provider', 'name')
                    ->label('Proveedor')
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options(Advance::CURRENCIES)
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('has_iva')
                    ->label('IVA')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn(Advance $record): string => "Anticipo: {$record->provider->name}")
                    ->modalWidth('5xl')
                    ->modalContent(function (Advance $record): View {
                        return view('filament.resources.advance-resource.pages.advance-view', [
                            'advance' => $record,
                            'statuses' => Advance::STATUS,
                        ]);
                    })
                    ->modalFooterActions([
                        Action::make('imprimir')
                            ->label('Imprimir')
                            ->icon('heroicon-o-printer')
                            ->color('gray')
                            ->action(fn() => null),
                        Action::make('cerrar')
                            ->label('Cerrar')
                            ->color('secondary')
                            ->action(fn() => null),
                    ]),
                Tables\Actions\Action::make('addSapCode')
                    ->label('Agregar SAP')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('sap_code')
                            ->label('Código SAP')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Advance $record, array $data): void {
                        $record->addSapCode($data['sap_code']);
                    })
                    ->modalHeading('Agregar Código SAP')
                    ->modalDescription('Al agregar el código SAP, el anticipo pasará automáticamente a Tesorería'),
            ])
            ->bulkActions([])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('status', 'APPROVED');
            })
            ->defaultSort('approved_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvancesApproved::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'APPROVED');
    }

    // En app/Filament/Resources/AdvanceApprovedResource.php

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_AdvanceApprovedResource');
    }
}
