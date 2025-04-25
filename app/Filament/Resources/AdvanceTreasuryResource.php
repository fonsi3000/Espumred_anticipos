<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceTreasuryResource\Pages;
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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class AdvanceTreasuryResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'Anticipo por Egreso';

    protected static ?string $pluralModelLabel = 'Anticipos por Egresos';

    protected static ?string $navigationLabel = 'Anticipos por Egresos';

    protected static ?int $navigationSort = 4;

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

    public static function canCreate(): bool
    {
        return false;
    }

    protected static function getUserFactory(): string
    {
        $user = Auth::user();
        return Advance::determineFactoryFromEmail($user->email);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID anticipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Valor Total')
                    ->money(fn(Advance $record): string => match ($record->currency) {
                        'USD' => 'usd',
                        'EURO' => 'eur',
                        default => 'cop',
                    })
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
                Tables\Columns\TextColumn::make('advance_amount')
                    ->label('Valor Anticipo')
                    ->money(fn(Advance $record): string => match ($record->currency) {
                        'USD' => 'usd',
                        'EURO' => 'eur',
                        default => 'cop',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('Saldo Pendiente')
                    ->money('cop')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_iva')
                    ->label('IVA')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sap_code')
                    ->label('Código SAP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_order')
                    ->label('Orden de Compra')
                    ->searchable(),
                // Columnas adicionales con toggleable para reducir carga inicial
                Tables\Columns\TextColumn::make('accountant.name')
                    ->label('Contabilizado por')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('accounted_at')
                    ->label('Fecha de Contabilización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->relationship('provider', 'name')
                    ->searchable() // Activar búsqueda
                    ->optionsLimit(15) // Limitar a mostrar solo 15 opciones a la vez
                    ->label('Proveedor'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn(Advance $record): string => "Anticipo: {$record->provider->name}")
                    ->modalWidth('5xl')
                    // La clave está en usar una función de retorno diferida que se ejecuta solo cuando se abre el modal
                    ->modalContent(function (Advance $record) {
                        // En este punto, el modal ya está abierto, así que cargamos los datos necesarios
                        $record->load(['provider', 'creator', 'approver', 'accountant', 'treasurer', 'legalizer']);

                        return view('filament.resources.advance-resource.pages.advance-view', [
                            'advance' => $record,
                            'statuses' => Advance::STATUS,
                        ]);
                    })
                    ->modalFooterActions([
                        Tables\Actions\Action::make('descargar')
                            ->label('Descargar')
                            ->icon('heroicon-o-arrow-down')
                            ->color('gray')
                            ->action(function (Advance $record) {
                                return response()->streamDownload(function () use ($record) {
                                    // Cargamos los datos sólo cuando se solicita la descarga
                                    $record->load(['provider', 'creator', 'approver', 'accountant', 'treasurer', 'legalizer']);

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
                Tables\Actions\Action::make('addEgressNumber')
                    ->label('Agregar N° Egreso')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->form([
                        TextInput::make('egress_number')
                            ->label('Número de Egreso')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Advance $record, array $data): void {
                        $record->addEgressNumber($data['egress_number']);
                    })
                    ->modalHeading('Agregar Número de Egreso')
                    ->modalDescription('Al agregar el número de egreso, el anticipo pasará a Legalización'),
            ])
            ->bulkActions([])
            // Optimización de consulta con eager loading selectivo
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('status', 'TREASURY')
                    ->with([
                        'provider:id,name,document_number,SAP_code,address,phone,city',
                        'accountant:id,name'
                    ]);
            })
            ->defaultSort('accounted_at', 'desc')
            // Paginación para mejorar rendimiento
            ->paginated([10, 25, 50, 100])
            // Persistir filtros en sesión
            ->persistFiltersInSession()
            // Mejorar visualización
            ->striped()
            // Optimizar opciones de paginación
            ->paginationPageOptions([10, 25, 50, 100])
            // Simplificar interfaz de filtros
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvancesTreasury::route('/'),
        ];
    }

    // Optimización de la consulta principal con eager loading selectivo
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Mantenemos la condición específica de estado 'TREASURY'
        $query = parent::getEloquentQuery()->where('status', 'TREASURY');

        // Solo aplicar filtro de fábrica si NO es super_admin
        if (!$user->hasRole('super_admin')) {
            $query = $query->where('factory', self::getUserFactory());
        }

        // Mantenemos el eager loading existente
        return $query->with([
            'provider:id,name,document_number,SAP_code,address,phone,city',
            'accountant:id,name'
        ]);
    }


    public static function canAccess(): bool
    {
        return auth()->user()->can('view_advance-treasury-resource');
    }
}
