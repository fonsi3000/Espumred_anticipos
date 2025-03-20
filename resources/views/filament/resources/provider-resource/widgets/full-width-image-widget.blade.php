<x-filament-widgets::widget>
    <div class="flex w-full p-2">
        <!-- Primera imagen (post.png) -->
        <a href="https://espumadoslitoral-my.sharepoint.com/:i:/g/personal/desarrollador1_espumasmedellin_com_co/EXIIK7BdzENGv0f3GN90DK4BmassScLWXyKL8TxwtpMLCw?e=fhaGQc" target="_blank" rel="noopener noreferrer" class="flex-1 mr-2">
            <div class="w-full h-36 overflow-hidden rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                <img 
                    src="{{ asset('images/post.png') }}"
                    alt="Imagen del widget"
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                >
            </div>
        </a>
        
        <!-- Segunda imagen (politica.png) -->
        <a href="https://espumadoslitoral-my.sharepoint.com/:b:/g/personal/desarrollador1_espumasmedellin_com_co/EZA3UOTmsZRGpQ6dARX12Q8BaxjZAD1Omkv1SzPFDhLgZQ?e=atTbpV" target="_blank" rel="noopener noreferrer" class="flex-1 ml-2">
            <div class="w-full h-36 overflow-hidden rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                <img 
                    src="{{ asset('images/politica.png') }}"
                    alt="Política de empresa"
                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                >
            </div>
        </a>
    </div>
</x-filament-widgets::widget>