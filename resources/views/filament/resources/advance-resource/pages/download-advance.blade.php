<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Anticipo: {{ $advance->provider->name }}</title>
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        /* Los estilos del encabezado ya no son necesarios ya que usamos una tabla */
        
        /* Secciones y tablas */
        .section {
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 14px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .concepto {
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        table th, table td {
            border: 1px solid #ddd;
            padding: 5px;
        }
        
        table th {
            background-color: #e6f0ff;
            text-align: center;
            font-weight: bold;
        }
        
        .bg-blue-100 {
            background-color: #e6f0ff;
        }
        
        .bg-gray-100 {
            background-color: #f5f5f5;
        }
        
        .bg-green-100 {
            background-color: #e6ffec;
        }
        
        .bg-blue-50 {
            background-color: #f0f5ff;
        }
        
        .bg-green-50 {
            background-color: #f0fff5;
        }
        
        .bg-yellow-50 {
            background-color: #fffbeb;
        }
        
        .bg-yellow-100 {
            background-color: #fef9c3;
        }
        
        .bg-purple-100 {
            background-color: #f3e8ff;
        }
        
        .bg-red-50 {
            background-color: #fef2f2;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: bold;
        }
        
        .uppercase {
            text-transform: uppercase;
        }
        
        .codigos-container {
            margin-bottom: 15px;
        }
        
        .codigo-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 8px;
        }
        
        .codigo-title {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .codigo-value {
            padding: 5px;
            background-color: white;
            border: 1px solid #eee;
            border-radius: 3px;
        }
        
        .codigo-fecha {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }
        
        .trazabilidad-user {
            font-weight: bold;
        }
        
        .trazabilidad-fecha {
            font-size: 9px;
            color: #666;
        }
        
        .rechazo {
            background-color: #fef2f2;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .rechazo-titulo {
            font-size: 11px;
            color: #666;
        }
        
        .rechazo-motivo {
            font-weight: bold;
        }
        
        .rechazo-fecha {
            font-size: 9px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Encabezado mejorado con tabla para mejor alineación -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 1px solid #ddd;">
        <tr>
            <td style="width: 30%; text-align: left; vertical-align: middle; padding: 10px;">
                <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="height: 60px;">
            </td>
            <td style="width: 40%; text-align: center; vertical-align: middle; padding: 10px;">
                <div style="font-size: 18px; font-weight: bold; color: #333;">SOLICITUD DE ANTICIPO #{{ $advance->id }}</div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle; padding: 10px;">
                <div style="font-weight: bold;">Fecha: {{ $advance->created_at->format('d/m/Y') }}</div>
                <div style="font-weight: bold;">OC: {{ $advance->purchase_order }}</div>
            </td>
        </tr>
    </table>

    <!-- Información del proveedor -->
    <div class="section">
        <div class="section-title">Datos del Proveedor</div>
        <table>
            <thead>
                <tr class="bg-blue-100">
                    <th width="17%">Nombre</th>
                    <th width="17%">NIT/Cédula</th>
                    <th width="17%">Código SAP</th>
                    <th width="17%">Dirección</th>
                    <th width="17%">Teléfono</th>
                    <th width="17%">Ciudad</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $advance->provider->name }}</td>
                    <td class="text-center">{{ $advance->provider->document_number ?? 'No especificado' }}</td>
                    <td class="text-center">{{ $advance->provider->SAP_code ?? 'No especificado' }}</td>
                    <td>{{ $advance->provider->address ?? 'No especificada' }}</td>
                    <td class="text-center">{{ $advance->provider->phone ?? 'No especificado' }}</td>
                    <td class="text-center">{{ $advance->provider->city ?? 'No especificada' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Tabla de valores -->
    <div class="section">
        <!-- Concepto como encabezado separado -->
        <div class="concepto">
            <span class="font-bold">Concepto:</span> {{ $advance->concept }}
        </div>
        
        <table>
            <thead>
                <tr class="bg-blue-100">
                    <th>Cantidad</th>
                    <th>Valor Unitario</th>
                    <th>Subtotal</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Anticipo</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">{{ $advance->quantity }}</td>
                    <td class="text-right">${{ number_format($advance->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($advance->subtotal, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($advance->iva_value, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">${{ number_format($advance->total_amount, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($advance->advance_amount, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($advance->pending_balance, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        
        <div style="background-color: #f5f5f5; padding: 8px; border-radius: 5px;">
            <span class="font-bold">SON:</span> {{ strtoupper($advance->amount_in_words) }}
        </div>
    </div>

    <!-- Códigos de seguimiento -->
    <div class="section">
        <div class="section-title">Códigos de Referencia</div>
        
        <div class="codigos-container">
            <table>
                <tr>
                    <td width="33%" style="vertical-align: top; padding: 5px;">
                        <div class="codigo-box bg-blue-50">
                            <div class="codigo-title">Código SAP</div>
                            <div class="codigo-value">
                                {{ $advance->sap_code ?? 'Pendiente' }}
                            </div>
                            @if($advance->accounted_at && $advance->accounted_by)
                            <div class="codigo-fecha">
                                Registrado: {{ $advance->accounted_at->format('d/m/Y') }} por {{ $advance->accountant?->name }}
                            </div>
                            @endif
                        </div>
                    </td>
                    <td width="33%" style="vertical-align: top; padding: 5px;">
                        <div class="codigo-box bg-green-50">
                            <div class="codigo-title">Número de Egreso</div>
                            <div class="codigo-value">
                                {{ $advance->egress_number ?? 'Pendiente' }}
                            </div>
                            @if($advance->treasury_at && $advance->treasury_by)
                            <div class="codigo-fecha">
                                Registrado: {{ $advance->treasury_at->format('d/m/Y') }} por {{ $advance->treasurer?->name }}
                            </div>
                            @endif
                        </div>
                    </td>
                    <td width="33%" style="vertical-align: top; padding: 5px;">
                        <div class="codigo-box bg-yellow-50">
                            <div class="codigo-title">Número de Legalización</div>
                            <div class="codigo-value">
                                {{ $advance->legalization_number ?? 'Pendiente' }}
                            </div>
                            @if($advance->legalized_at && $advance->legalized_by)
                            <div class="codigo-fecha">
                                Registrado: {{ $advance->legalized_at->format('d/m/Y') }} por {{ $advance->legalizer?->name }}
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Trazabilidad -->
    <div class="section" style="border-bottom: none;">
        <div class="section-title">Trazabilidad del Anticipo</div>
        
        <table>
            <thead>
                <tr>
                    <th class="bg-gray-100">Creado por</th>
                    @if($advance->approved_by)
                    <th class="bg-green-100">Aprobado por</th>
                    @endif
                    @if($advance->accounted_by)
                    <th class="bg-blue-100">Contabilizado por</th>
                    @endif
                    @if($advance->treasury_by)
                    <th class="bg-purple-100">Registro de egreso por</th>
                    @endif
                    @if($advance->legalized_by)
                    <th class="bg-yellow-100">Legalizado por</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="trazabilidad-user">{{ $advance->creator?->name ?? 'No especificado' }}</div>
                        <div class="trazabilidad-fecha">{{ $advance->created_at ? $advance->created_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                    </td>
                    @if($advance->approved_by)
                    <td>
                        <div class="trazabilidad-user">{{ $advance->approver?->name ?? 'No especificado' }}</div>
                        <div class="trazabilidad-fecha">{{ $advance->approved_at ? $advance->approved_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                    </td>
                    @endif
                    @if($advance->accounted_by)
                    <td>
                        <div class="trazabilidad-user">{{ $advance->accountant?->name ?? 'No especificado' }}</div>
                        <div class="trazabilidad-fecha">{{ $advance->accounted_at ? $advance->accounted_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                    </td>
                    @endif
                    @if($advance->treasury_by)
                    <td>
                        <div class="trazabilidad-user">{{ $advance->treasurer?->name ?? 'No especificado' }}</div>
                        <div class="trazabilidad-fecha">{{ $advance->treasury_at ? $advance->treasury_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                    </td>
                    @endif
                    @if($advance->legalized_by)
                    <td>
                        <div class="trazabilidad-user">{{ $advance->legalizer?->name ?? 'No especificado' }}</div>
                        <div class="trazabilidad-fecha">{{ $advance->legalized_at ? $advance->legalized_at->format('d/m/Y H:i') : 'No especificada' }}</div>
                    </td>
                    @endif
                </tr>
            </tbody>
        </table>
        
        @if($advance->status === 'REJECTED')
        <div class="rechazo">
            <div class="rechazo-titulo">Motivo de Rechazo</div>
            <div class="rechazo-motivo">{{ $advance->rejection_reason }}</div>
            <div class="rechazo-fecha">{{ $advance->rejection_date ? $advance->rejection_date->format('d/m/Y H:i') : 'No especificada' }}</div>
        </div>
        @endif
    </div>
    
    <!-- Pie de página -->
    <div style="position: fixed; bottom: 20px; width: 100%; text-align: center; font-size: 9px; color: #666;">
        Documento generado el {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>