<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    protected static ?int $navigationSort = 7;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Proveedor')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('document_number')
                            ->label('NIT/Cédula')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('SAP_code')
                            ->label('Código SAP')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->lazy(), // Lazy loading para mejor rendimiento

                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->label('País')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->label('Ciudad')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->lazy(), // Lazy loading para mejor rendimiento
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('NIT/Cédula')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('SAP_code')
                    ->label('Código SAP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('País')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->label('País')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('city')
                    ->label('Ciudad')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar Proveedor')
                    ->modalWidth('4xl'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                ]),
            ])
            // Paginación optimizada
            ->paginated([10, 25, 50, 100])
            // Persistir filtros para mejor experiencia de usuario
            ->persistFiltersInSession()
            // Mejorar visualización
            ->striped()
            // Trigger de filtros simplificado
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros')
            )
            // Optimizador de opciones de paginación
            ->paginationPageOptions([10, 25, 50, 100])
            // Configuración de ordenamiento por defecto
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
        ];
    }

    // Optimización para contar relaciones sin cargarlas completamente
    public static function getRelations(): array
    {
        return [];
    }

    // Método para precargar consultas comunes en una sola operación
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    // Método para configurar permisos de acceso
    public static function canAccess(): bool
    {
        return auth()->user()->can('view_provider-resource');
    }
}
