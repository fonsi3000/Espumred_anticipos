<div class="bg-white rounded-lg">
    <!-- Encabezado -->
    <div class="flex items-center w-full border-b pb-4">
        <!-- Sección izquierda: Logo -->
        <div class="w-1/3 px-4">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16">
        </div>
        
        <!-- Sección central: Título -->
        <div class="w-1/3 px-4 text-center">
            <h1 class="text-3xl font-bold">SOLICITUD DE ANTICIPO #{{ $advance->id }}</h1>
            <!-- Badge para mostrar la fábrica -->
            <div class="mt-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $advance->factory == 'medellin' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    {{ $advance->factory == 'medellin' ? 'Espumas Medellín' : 'Espumados del Litoral' }}
                </span>
            </div>
        </div>
        
        <!-- Sección derecha: Fecha y OC -->
        <div class="w-1/3 px-4 text-right">
            <div class="font-semibold">Fecha: {{ $advance->created_at->format('d/m/Y') }}</div>
            <div class="font-semibold">OC: {{ $advance->purchase_order }}</div>
        </div>
    </div>

    <!-- Información del proveedor optimizada -->
    <div class="border-b pb-2">
        <h2 class="font-bold text-base border-b pb-1 mb-2">Datos del Proveedor</h2>
        <div class="overflow-x-auto">
            <table class="w-full bg-white table-fixed">
                <thead>
                    <tr class="bg-blue-100">
                        <th class="py-1 px-3 border text-center w-1/6">Nombre</th>
                        <th class="py-1 px-3 border text-center w-1/6">NIT/Cédula</th>
                        <th class="py-1 px-3 border text-center w-1/6">Código SAP</th>
                        <th class="py-1 px-3 border text-center w-1/6">Dirección</th>
                        <th class="py-1 px-3 border text-center w-1/6">Teléfono</th>
                        <th class="py-1 px-3 border text-center w-1/6">Ciudad</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-1 px-3 border">{{ $advance->provider->name }}</td>
                        <td class="py-1 px-3 border text-center">{{ $advance->provider->document_number ?? 'No especificado' }}</td>
                        <td class="py-1 px-3 border text-center">{{ $advance->provider->SAP_code ?? 'No especificado' }}</td>
                        <td class="py-1 px-3 border">{{ $advance->provider->address ?? 'No especificada' }}</td>
                        <td class="py-1 px-3 border text-center">{{ $advance->provider->phone ?? 'No especificado' }}</td>
                        <td class="py-1 px-3 border text-center">{{ $advance->provider->city ?? 'No especificada' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabla de valores -->
    <div class="p-4 border-b">
        <!-- Concepto como encabezado separado -->
        <div class="mb-3 p-2 bg-gray-50 border rounded">
            <span class="font-bold">Concepto:</span> {{ $advance->concept }}
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full bg-white">
                <thead>
                    <tr class="bg-blue-100">
                        <th class="py-2 px-4 border text-center">Cantidad</th>
                        <th class="py-2 px-4 border text-right">Valor Unitario</th>
                        <th class="py-2 px-4 border text-right">Subtotal</th>
                        <th class="py-2 px-4 border text-right">IVA</th>
                        <th class="py-2 px-4 border text-right">Total</th>
                        <th class="py-2 px-4 border text-right">Anticipo</th>
                        <th class="py-2 px-4 border text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-2 px-4 border text-center">{{ number_format($advance->quantity, 0, ',', '.') }}</td>
                        <td class="py-2 px-4 border text-right">${{ number_format($advance->unit_price, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 border text-right">${{ number_format($advance->subtotal, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 border text-right">${{ number_format($advance->iva_value, 0, ',', '.') }}</td>
                        <td class="py-2 px-4 border text-right font-bold">${{ number_format($advance->total_amount, 0, ',', '.') }}</td>
                        <td class="py-2 px-4 border text-right">${{ number_format($advance->advance_amount, 0, ',', '.') }}</td>
                        <td class="py-2 px-4 border text-right">${{ number_format($advance->pending_balance, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-2 p-2 bg-gray-100 rounded">
            <span class="font-bold">SON:</span> {{ strtoupper($advance->amount_in_words) }}
        </div>
    </div>

    <!-- NUEVA SECCIÓN: Códigos de seguimiento -->
    <div class="p-4 border-b">
        <h2 class="font-bold text-lg mb-3">Códigos de Referencia</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Código SAP -->
            <div class="p-3 bg-blue-50 rounded-lg shadow-sm {{ !$advance->sap_code ? 'opacity-50' : '' }}">
                <div class="text-sm text-gray-700 mb-1">Código SAP</div>
                <div class="flex items-center">
                    <div class="bg-blue-100 p-2 rounded-l">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 10h.01M7 13h.01M7 16h.01M11 7h6M11 10h6M11 13h6M11 16h6M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                        </svg>
                    </div>
                    <div class="font-medium p-2 bg-white flex-grow rounded-r">
                        {{ $advance->sap_code ?? 'Pendiente' }}
                    </div>
                </div>
                @if($advance->accounted_at && $advance->accounted_by)
                <div class="text-xs text-gray-500 mt-2">
                    Registrado: {{ $advance->accounted_at->format('d/m/Y') }} por {{ $advance->accountant?->name }}
                </div>
                @endif
            </div>

            <!-- Número de Egreso -->
            <div class="p-3 bg-green-50 rounded-lg shadow-sm {{ !$advance->egress_number ? 'opacity-50' : '' }}">
                <div class="text-sm text-gray-700 mb-1">Número de Egreso</div>
                <div class="flex items-center">
                    <div class="bg-green-100 p-2 rounded-l">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="font-medium p-2 bg-white flex-grow rounded-r">
                        {{ $advance->egress_number ?? 'Pendiente' }}
                    </div>
                </div>
                @if($advance->treasury_at && $advance->treasury_by)
                <div class="text-xs text-gray-500 mt-2">
                    Registrado: {{ $advance->treasury_at->format('d/m/Y') }} por {{ $advance->treasurer?->name }}
                </div>
                @endif
            </div>

            <!-- Número de Legalización -->
            <div class="p-3 bg-yellow-50 rounded-lg shadow-sm {{ !$advance->legalization_number ? 'opacity-50' : '' }}">
                <div class="text-sm text-gray-700 mb-1">Número de Legalización</div>
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-2 rounded-l">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="font-medium p-2 bg-white flex-grow rounded-r">
                        {{ $advance->legalization_number ?? 'Pendiente' }}
                    </div>
                </div>
                @if($advance->legalized_at && $advance->legalized_by)
                <div class="text-xs text-gray-500 mt-2">
                    Registrado: {{ $advance->legalized_at->format('d/m/Y') }} por {{ $advance->legalizer?->name }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Trazabilidad  -->
    <div class="p-4">
        <h2 class="font-bold text-lg mb-3">Trazabilidad del Anticipo</h2>
        <div class="overflow-x-auto">
            <table class="w-full bg-white">
                <thead>
                    <tr>
                        <th class="py-2 px-3 bg-gray-100 text-left text-sm">Creado por</th>
                        @if($advance->approved_by)
                        <th class="py-2 px-3 bg-green-100 text-left text-sm">Aprobado por</th>
                        @endif
                        @if($advance->accounted_by)
                        <th class="py-2 px-3 bg-blue-100 text-left text-sm">Contabilizado por</th>
                        @endif
                        @if($advance->treasury_by)
                        <th class="py-2 px-3 bg-purple-100 text-left text-sm">Registro de egreso por</th>
                        @endif
                        @if($advance->legalized_by)
                        <th class="py-2 px-3 bg-yellow-100 text-left text-sm">Legalizado por</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-2 px-3 border-t">
                            <div class="font-medium">{{ $advance->creator?->name ?? 'No especificado' }}</div>
                            <div class="text-xs text-gray-500">{{ $advance->created_at ? $advance->created_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                        </td>
                        @if($advance->approved_by)
                        <td class="py-2 px-3 border-t">
                            <div class="font-medium">{{ $advance->approver?->name ?? 'No especificado' }}</div>
                            <div class="text-xs text-gray-500">{{ $advance->approved_at ? $advance->approved_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                        </td>
                        @endif
                        @if($advance->accounted_by)
                        <td class="py-2 px-3 border-t">
                            <div class="font-medium">{{ $advance->accountant?->name ?? 'No especificado' }}</div>
                            <div class="text-xs text-gray-500">{{ $advance->accounted_at ? $advance->accounted_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                        </td>
                        @endif
                        @if($advance->treasury_by)
                        <td class="py-2 px-3 border-t">
                            <div class="font-medium">{{ $advance->treasurer?->name ?? 'No especificado' }}</div>
                            <div class="text-xs text-gray-500">{{ $advance->treasury_at ? $advance->treasury_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                        </td>
                        @endif
                        @if($advance->legalized_by)
                        <td class="py-2 px-3 border-t">
                            <div class="font-medium">{{ $advance->legalizer?->name ?? 'No especificado' }}</div>
                            <div class="text-xs text-gray-500">{{ $advance->legalized_at ? $advance->legalized_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                        </td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
        
        @if($advance->status === 'REJECTED')
        <div class="mt-4 bg-red-50 p-3 rounded shadow-sm">
            <div class="text-sm text-gray-500">Motivo de Rechazo</div>
            <div class="font-medium">{{ $advance->rejection_reason }}</div>
            <div class="text-xs">{{ $advance->rejection_date ? $advance->rejection_date->format('d/m/Y H:i') : 'No especificada' }}</div>
        </div>
        @endif
    </div>
</div>