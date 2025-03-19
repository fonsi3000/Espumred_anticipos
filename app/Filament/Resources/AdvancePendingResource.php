<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvancePendingResource\Pages;
use App\Models\Advance;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class AdvancePendingResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $modelLabel = 'Anticipo Pendiente';

    protected static ?string $pluralModelLabel = 'Anticipos Pendientes';

    protected static ?string $navigationLabel = 'Anticipos Pendientes';

    protected static ?int $navigationSort = 2;

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
                // Columnas comentadas pero optimizadas como toggleable para mostrar cuando sea necesario
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
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
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Advance $record): void {
                        $record->updateStatus('APPROVED');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar Anticipo')
                    ->modalDescription('¿Está seguro de aprobar este anticipo?'),
                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Motivo del Rechazo')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Advance $record, array $data): void {
                        $record->reject($data['rejection_reason']);
                    })
                    ->modalHeading('Rechazar Anticipo')
                    ->requiresConfirmation(),
            ])
            // Eliminar bulk actions para mejorar rendimiento
            ->bulkActions([])
            // Optimización de consulta con eager loading selectivo
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('status', 'PENDING')
                    ->with([
                        'provider:id,name,document_number,SAP_code,address,phone,city',
                        'creator:id,name'
                    ]);
            })
            ->defaultSort('created_at', 'desc')
            // Paginación para mejorar rendimiento
            ->paginated([10, 25, 50, 100])
            // Persistir filtros en sesión para mejor UX
            ->persistFiltersInSession()
            // Optimizaciones visuales que ayudan al rendimiento
            ->striped()
            // Simplificar carga de componentes
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
            'index' => Pages\ListAdvancesPending::route('/'),
        ];
    }

    // Optimización de la consulta principal con eager loading selectivo
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'PENDING')
            ->with([
                'provider:id,name,document_number,SAP_code,address,phone,city',
                'creator:id,name'
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_advance-pending-resource');
    }
}
