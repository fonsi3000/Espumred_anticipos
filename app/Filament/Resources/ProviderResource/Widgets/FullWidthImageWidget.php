<?php

namespace App\Filament\Resources\ProviderResource\Widgets;

use Filament\Widgets\Widget;

class FullWidthImageWidget extends Widget
{
    // Establecer una prioridad alta para que aparezca en la parte superior
    protected static ?int $sort = -10;

    // Deshabilitar la carga perezosa para que las imágenes se carguen inmediatamente
    protected static bool $isLazy = false;

    // Definir el ancho del widget
    public function getColumnSpan(): int|string
    {
        return 'full'; // También puedes usar valores como 1, 2, 3 según lo prefieras
    }

    // Definir el alto del widget
    protected int | string | array $heightOverride = '150px';

    // La vista ya está definida correctamente
    protected static string $view = 'filament.resources.provider-resource.widgets.full-width-image-widget';
}
