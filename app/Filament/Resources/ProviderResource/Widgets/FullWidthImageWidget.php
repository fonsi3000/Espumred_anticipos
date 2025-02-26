<?php

namespace App\Filament\Resources\ProviderResource\Widgets;

use Filament\Widgets\Widget;

class FullWidthImageWidget extends Widget
{
    // Establecer una prioridad alta para que aparezca en la parte superior
    protected static ?int $sort = -10;

    // Deshabilitar la carga perezosa para que la imagen se cargue inmediatamente
    protected static bool $isLazy = false;

    // Indicar a Filament que debe ocupar todo el ancho disponible
    public function getColumnSpan(): int|string
    {
        return 'full';
    }

    // Opcional: Definir el alto del widget
    protected int | string | array $heightOverride = '500px';

    // La vista ya está definida correctamente
    protected static string $view = 'filament.resources.provider-resource.widgets.full-width-image-widget';
}
