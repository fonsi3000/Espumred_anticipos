<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceResource\Pages;
use App\Models\Advance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\View\View;
use Barryvdh\DomPDF\Facade\Pdf;




class AdvanceResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'Anticipo';

    protected static ?string $pluralModelLabel = 'Anticipos';

    protected static ?string $navigationLabel = 'Todos los Anticipos';

    protected static ?int $navigationSort = 0;

    // Implementación del método requerido por HasShieldPermissions
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
        ];
    }
    // Deshabilitar la creación de nuevos anticipos
    public static function canCreate(): bool
    {
        return false;
    }

    // Deshabilitar la edición de anticipos
    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    // Deshabilitar la eliminación de anticipos
    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    // Mantener el formulario para visualización en modo solo lectura
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('provider_id')
                    ->relationship('provider', 'name')
                    ->label('Proveedor')
                    ->disabled(),
                Forms\Components\Textarea::make('concept')
                    ->label('Concepto')
                    ->disabled()
                    ->columnSpan(2),
                Forms\Components\Select::make('currency')
                    ->label('Moneda')
                    ->options(Advance::CURRENCIES)
                    ->disabled(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->disabled()
                    ->numeric(),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Valor Unitario')
                    ->disabled()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Toggle::make('has_iva')
                    ->label('¿Aplica IVA?')
                    ->disabled(),
                Forms\Components\TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->disabled()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('iva_value')
                    ->label('Valor IVA')
                    ->disabled()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('total_amount')
                    ->label('Valor Total')
                    ->disabled()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Textarea::make('amount_in_words')
                    ->label('Valor en Palabras')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('advance_percentage')
                    ->label('Porcentaje de Anticipo')
                    ->disabled()
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\TextInput::make('advance_amount')
                    ->label('Valor del Anticipo')
                    ->disabled()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('pending_balance')
                    ->label('Saldo Pendiente')
                    ->disabled()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('purchase_order')
                    ->label('Orden de Compra')
                    ->disabled(),
                Forms\Components\TextInput::make('legalization_term')
                    ->label('Plazo de Legalización')
                    ->disabled()
                    ->suffix(' días'),

                Forms\Components\Section::make('Estado y Trazabilidad')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(Advance::STATUS)
                            ->disabled(),
                        Forms\Components\TextInput::make('sap_code')
                            ->label('Código SAP')
                            ->disabled()
                            ->visible(fn($record) => $record?->sap_code),
                        Forms\Components\TextInput::make('egress_number')
                            ->label('Número de Egreso')
                            ->disabled()
                            ->visible(fn($record) => $record?->egress_number),
                        Forms\Components\TextInput::make('legalization_number')
                            ->label('Número de Legalización')
                            ->disabled()
                            ->visible(fn($record) => $record?->legalization_number),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo del Rechazo')
                            ->disabled()
                            ->visible(fn($record) => $record?->status === 'REJECTED'),
                    ])->columnSpan(2),

                Forms\Components\Section::make('Usuarios y Fechas')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('creator.name')
                                    ->label('Creado por')
                                    ->disabled()
                                    ->formatStateUsing(function ($state, $record) {
                                        return $record->creator?->name ?? 'No especificado';
                                    }),
                                Forms\Components\TextInput::make('created_at')
                                    ->label('Fecha de Creación')
                                    ->disabled(),
                                Forms\Components\TextInput::make('approver.name')
                                    ->label('Aprobado por')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->approved_by)
                                    ->formatStateUsing(function ($state, $record) {
                                        return $record->approver?->name ?? 'No especificado';
                                    }),
                                Forms\Components\TextInput::make('approved_at')
                                    ->label('Fecha de Aprobación')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->approved_at),
                                Forms\Components\TextInput::make('accountant.name')
                                    ->label('Contabilizado por')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->accounted_by)
                                    ->formatStateUsing(function ($state, $record) {
                                        return $record->accountant?->name ?? 'No especificado';
                                    }),
                                Forms\Components\TextInput::make('accounted_at')
                                    ->label('Fecha de Contabilización')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->accounted_at),
                                Forms\Components\TextInput::make('treasurer.name')
                                    ->label('Procesado por Tesorería')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->treasury_by)
                                    ->formatStateUsing(function ($state, $record) {
                                        return $record->treasurer?->name ?? 'No especificado';
                                    }),
                                Forms\Components\TextInput::make('treasury_at')
                                    ->label('Fecha de Proceso Tesorería')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->treasury_at),
                                Forms\Components\TextInput::make('legalizer.name')
                                    ->label('Legalizado por')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->legalized_by)
                                    ->formatStateUsing(function ($state, $record) {
                                        return $record->legalizer?->name ?? 'No especificado';
                                    }),
                                Forms\Components\TextInput::make('legalized_at')
                                    ->label('Fecha de Legalización')
                                    ->disabled()
                                    ->visible(fn($record) => $record?->legalized_at),
                            ])
                    ])->columnSpan(1),
            ])->columns(3);
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
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Aprobado por')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->defaultSort('created_at', 'desc')
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
                Tables\Filters\SelectFilter::make('status')
                    ->options(Advance::STATUS)
                    ->label('Estado'),
                Tables\Filters\TernaryFilter::make('has_iva')
                    ->label('IVA')
                    ->boolean(),
            ])
            ->actions([
                // Solo permitir la acción de ver con la vista personalizada existente
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
                        Tables\Actions\Action::make('descargar')
                            ->label('Descargar')
                            ->icon('heroicon-o-arrow-down')  // Cambiado a un icono más seguro
                            ->color('gray')
                            ->action(function (Advance $record) {

                                return response()->streamDownload(function () use ($record) {
                                    echo Pdf::loadView('filament.resources.advance-resource.pages.download-advance', [
                                        'advance' => $record,
                                        'statuses' => Advance::STATUS,
                                        'isPdfDownload' => true,
                                    ])->output();
                                }, "anticipo-{$record->id}.pdf");
                            }),
                        Tables\Actions\Action::make('cerrar')
                            ->label('Cerrar')
                            ->color('secondary')
                            ->action(fn() => null),
                    ]),
            ])
            ->bulkActions([
                // Eliminar todas las acciones en masa
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvances::route('/'),
        ];
    }

    // Mantener este método por si se utiliza en otras partes
    public static function numberToWords($number, $currency): string
    {
        $currencies = [
            'COP' => 'PESOS COLOMBIANOS',
            'USD' => 'DÓLARES ESTADOUNIDENSES',
            'EURO' => 'EUROS'
        ];

        return number_format($number, 2) . ' ' . ($currencies[$currency] ?? '');
    }
    public static function canAccess(): bool
    {
        return auth()->user()->can('view_advance-resource');
    }
}
