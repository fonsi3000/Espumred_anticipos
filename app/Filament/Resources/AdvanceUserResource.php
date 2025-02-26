<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceUserResource\Pages;
use App\Models\Advance;
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

class AdvanceUserResource extends Resource
{
    protected static ?string $model = Advance::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'Mi Anticipo';

    protected static ?string $pluralModelLabel = 'Mis Anticipos';

    protected static ?string $navigationLabel = 'Mis Anticipos';

    protected static ?int $navigationSort = 1;

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
                    ->collapsible(),

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
                    ->collapsible(),

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
                Tables\Columns\TextColumn::make('purchase_order')
                    ->label('Orden de Compra')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Fecha de Creación')
                //     ->dateTime('d/m/Y H:i')
                //     ->sortable(),
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
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
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
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Anticipo')
                    ->modalWidth('4xl')
                    ->visible(fn(Advance $record): bool => $record->status === 'PENDING')
                    ->using(function (Advance $record, array $data): Advance {
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(Advance $record): bool => $record->status === 'PENDING'),
                ]),
            ])
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

                        return Advance::create($data);
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('created_by', Auth::id());
            })
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvancesUser::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('created_by', Auth::id());
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
}
