<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceResource\Pages\ListAdvancesApproved;
use App\Models\Advance;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Illuminate\Support\Facades\Cache;

class AdvanceApprovedResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $modelLabel = 'Anticipo por Código SAP';

    protected static ?string $pluralModelLabel = 'Anticipos por Código SAP';

    protected static ?string $navigationLabel = 'Anticipos por Código SAP';

    protected static ?int $navigationSort = 3;

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
                // Tables\Columns\TextColumn::make('approver.name')
                //     ->label('Aprobado por')
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('approved_at')
                //     ->label('Fecha de Aprobación')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
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
                        // No cachear la vista ya que podría contener objetos no serializables
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
            // Optimización de la consulta base con eager loading
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('status', 'APPROVED')
                    ->with(['provider:id,name', 'approver:id,name']);
            })
            ->defaultSort('approved_at', 'desc')
            // Implementar paginación más eficiente
            ->paginated([10, 25, 50, 100])
            // Mejora para reducir el tiempo de respuesta del servidor 
            ->persistFiltersInSession()
            // Ocultar actividad de filtro para reducir renderizado
            ->filtersTriggerAction(
                fn(\Filament\Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdvancesApproved::route('/'),
        ];
    }

    // Optimización de la consulta principal
    public static function getEloquentQuery(): Builder
    {
        // No podemos cachear la consulta directamente porque contiene objetos PDO no serializables
        // En su lugar, optimizamos con eager loading
        return parent::getEloquentQuery()
            ->where('status', 'APPROVED')
            ->with(['provider:id,name', 'creator:id,name', 'approver:id,name']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_advance-approved-resource');
    }

    // Simplificamos el método de navegación para evitar problemas de serialización
    public static function getNavigation(): array
    {
        return parent::getNavigation();
    }
}
