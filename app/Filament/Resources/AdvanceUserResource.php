<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceUserResource\Pages;
use App\Models\Advance;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Card;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;


class AdvanceUserResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'Mi Anticipo';

    protected static ?string $pluralModelLabel = 'Mis Anticipos';

    protected static ?string $navigationLabel = 'Mis Anticipos';

    protected static ?int $navigationSort = 1;

    // Método para obtener la fábrica del usuario actual
    protected static function getUserFactory(): string
    {
        $user = Auth::user();
        return Advance::determineFactoryFromEmail($user->email);
    }

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                    ->description('Detalles básicos del anticipo')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Select::make('provider_id')
                            ->relationship('provider', 'name')
                            ->label('Proveedor')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('concept')
                            ->label('Concepto')
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('purchase_order')
                            ->label('Orden de Compra')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->lazy(), // Lazy loading para mejorar rendimiento

                Section::make('Detalles Financieros')
                    ->description('Información de montos y cálculos')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options(Advance::CURRENCIES)
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('Valor Unitario')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),
                        Forms\Components\Toggle::make('has_iva')
                            ->label('¿Aplica IVA?')
                            ->default(false)
                            ->onColor('success')
                            ->offColor('danger'),
                        Forms\Components\TextInput::make('advance_percentage')
                            ->label('Porcentaje de Anticipo')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('legalization_term')
                            ->label('Plazo de Legalización')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix(' días'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->lazy(), // Lazy loading para mejorar rendimiento

                // Campo oculto para la fábrica
                Forms\Components\Hidden::make('factory')
                    ->default(function () {
                        return self::getUserFactory();
                    }),

                Forms\Components\Hidden::make('subtotal'),
                Forms\Components\Hidden::make('iva_value'),
                Forms\Components\Hidden::make('total_amount'),
                Forms\Components\Hidden::make('amount_in_words'),
                Forms\Components\Hidden::make('advance_amount'),
                Forms\Components\Hidden::make('pending_balance'),
            ]);
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
                Tables\Columns\TextColumn::make('factory')
                    ->label('Fábrica')
                    ->formatStateUsing(fn(string $state): string => Advance::FACTORIES[$state])
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'medellin' => 'warning',
                        'litoral' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Columns\TextColumn::make('advance_percentage')
                    ->label('% Anticipo')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('advance_amount')
                    ->label('Valor Anticipo')
                    ->money(fn(Advance $record): string => match ($record->currency) {
                        'USD' => 'usd',
                        'EURO' => 'eur',
                        default => 'cop',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_order')
                    ->label('Orden de Compra')
                    ->searchable(),
                // Columna comentada pero disponible con toggleable 
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->relationship('provider', 'name')
                    ->label('Proveedor')
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(Advance::STATUS)
                    ->label('Estado'),
            ])
            ->actions([
                // Solo permitir la acción de ver con la vista personalizada existente
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
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Anticipo')
                    ->modalWidth('4xl')
                    ->visible(fn(Advance $record): bool => $record->status === 'PENDING')
                    ->using(function (Advance $record, array $data): Advance {
                        // Asegurar que la fábrica no cambie al editar
                        $data['factory'] = $record->factory;

                        // Calcular los valores antes de guardar
                        $subtotal = $data['quantity'] * $data['unit_price'];
                        $iva = $data['has_iva'] ? $subtotal * 0.19 : 0;
                        $total = $subtotal + $iva;
                        $advanceAmount = $total * ($data['advance_percentage'] / 100);

                        $data['subtotal'] = $subtotal;
                        $data['iva_value'] = $iva;
                        $data['total_amount'] = $total;
                        $data['advance_amount'] = $advanceAmount;
                        $data['pending_balance'] = $total - $advanceAmount;
                        $data['amount_in_words'] = self::numberToWords($total, $data['currency']);

                        $record->update($data);

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(Advance $record): bool => $record->status === 'PENDING'),
            ])
            // Optimización: reducir las bulk actions innecesarias  
            ->bulkActions([])
            ->emptyStateHeading('No hay anticipos')
            ->emptyStateDescription('Crea tu primer anticipo haciendo clic en el botón "Crear"')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear Anticipo')
                    ->modalHeading('Crear Nuevo Anticipo')
                    ->modalWidth('4xl')
                    ->modalSubmitActionLabel('Crear Anticipo')
                    ->using(function (array $data): Advance {
                        // Calcular los valores antes de guardar
                        $subtotal = $data['quantity'] * $data['unit_price'];
                        $iva = $data['has_iva'] ? $subtotal * 0.19 : 0;
                        $total = $subtotal + $iva;
                        $advanceAmount = $total * ($data['advance_percentage'] / 100);

                        $data['subtotal'] = $subtotal;
                        $data['iva_value'] = $iva;
                        $data['total_amount'] = $total;
                        $data['advance_amount'] = $advanceAmount;
                        $data['pending_balance'] = $total - $advanceAmount;
                        $data['amount_in_words'] = self::numberToWords($total, $data['currency']);
                        $data['created_by'] = Auth::id();

                        // Asegurar que la fábrica esté establecida
                        if (empty($data['factory'])) {
                            $data['factory'] = self::getUserFactory();
                        }

                        return Advance::create($data);
                    }),
            ])
            // Optimización de consulta con eager loading selectivo y filtrado por fábrica
            ->modifyQueryUsing(function (Builder $query) {
                // Primero, obtener el usuario actual
                $user = Auth::user();

                // Iniciar con la condición básica: solo anticipos creados por el usuario actual
                $query = $query->where('created_by', Auth::id());

                // Si NO es super_admin, entonces añadir el filtro de fábrica
                if (!$user->hasRole('super_admin')) {
                    $query = $query->where('factory', self::getUserFactory());
                }

                // Añadir eager loading para mejorar rendimiento
                return $query->with([
                    'provider:id,name,document_number,SAP_code,address,phone,city'
                ]);
            })
            ->defaultSort('created_at', 'desc')
            // Implementar paginación para mejorar rendimiento
            ->paginated([10, 25, 50, 100])
            // Persistir filtros en sesión para mejor UX
            ->persistFiltersInSession()
            // Mejorar presentación visual y rendimiento
            ->striped()
            // Optimizador de carga de tabla
            ->paginationPageOptions([10, 25, 50, 100])
            // Simplificar la interfaz de filtros
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvancesUser::route('/'),
        ];
    }

    // Optimización de la consulta principal con eager loading selectivo y filtrado por fábrica
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->where('created_by', Auth::id());

        // Solo aplicar filtro de fábrica si NO es super_admin
        if (!$user->hasRole('super_admin')) {
            $query = $query->where('factory', self::getUserFactory());
        }

        return $query->with([
            'provider:id,name,document_number,SAP_code,address,phone,city'
        ]);
    }

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
        return auth()->user()->can('view_advance-user-resource');
    }
}
