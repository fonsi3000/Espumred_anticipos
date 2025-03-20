<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Advance extends Model
{
    protected $fillable = [
        'provider_id',
        'factory',
        'concept',
        'currency',
        'quantity',
        'unit_price',
        'has_iva',
        'subtotal',
        'iva_value',
        'total_amount',
        'amount_in_words',
        'advance_percentage',
        'advance_amount',
        'pending_balance',
        'purchase_order',
        'legalization_term',
        'status',
        'sap_code',
        'egress_number',
        'legalization_number',
        'rejection_reason',
        'rejection_date',
        'status_updated_at',
        'created_by',
        'approved_by',
        'accounted_by',
        'treasury_by',
        'legalized_by',
        'approved_at',
        'accounted_at',
        'treasury_at',
        'legalized_at'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'has_iva' => 'boolean',
        'subtotal' => 'decimal:2',
        'iva_value' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'advance_percentage' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'rejection_date' => 'datetime',
        'status_updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'accounted_at' => 'datetime',
        'treasury_at' => 'datetime',
        'legalized_at' => 'datetime'
    ];

    // Constantes cacheadas para mejorar el rendimiento
    public const CURRENCIES = [
        'COP' => 'Pesos Colombianos',
        'USD' => 'Dólar Estadounidense',
        'EURO' => 'Euro Español'
    ];

    public const STATUS = [
        'PENDING' => 'Pendiente',
        'APPROVED' => 'Aprobado',
        'TREASURY' => 'Tesorería',
        'LEGALIZATION' => 'Legalización',
        'COMPLETED' => 'Terminado',
        'REJECTED' => 'Rechazado'
    ];

    public const STATUS_COLORS = [
        'PENDING' => 'gray',
        'APPROVED' => 'green',
        'TREASURY' => 'blue',
        'LEGALIZATION' => 'purple',
        'COMPLETED' => 'emerald',
        'REJECTED' => 'red'
    ];

    // Constantes para las fábricas
    public const FACTORIES = [
        'medellin' => 'Medellín',
        'litoral' => 'Litoral'
    ];

    // Nuevo scope para filtrar por fábrica
    public function scopeForFactory(Builder $query, ?string $factory = null): Builder
    {
        if ($factory) {
            return $query->where('factory', $factory);
        }

        return $query;
    }

    // Determinar la fábrica basada en el dominio de correo del usuario
    public static function determineFactoryFromEmail(string $email): string
    {
        if (str_contains($email, '@espumadosdellitoral.com.co')) {
            return 'litoral';
        }

        return 'medellin';
    }

    // Relaciones optimizadas con clave explícita
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounted_by');
    }

    public function treasurer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'treasury_by');
    }

    public function legalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legalized_by');
    }

    // Métodos de cálculo (se mantienen iguales para preservar funcionalidad)
    public function calculateSubtotal(): float
    {
        return (float)$this->quantity * (float)$this->unit_price;
    }

    public function calculateIva(): float
    {
        return $this->has_iva ? (float)$this->subtotal * 0.19 : 0;
    }

    public function calculateTotal(): float
    {
        return (float)$this->subtotal + (float)$this->iva_value;
    }

    public function calculateAdvanceAmount(): float
    {
        return (float)$this->total_amount * ((float)$this->advance_percentage / 100);
    }

    public function calculatePendingBalance(): float
    {
        return (float)$this->total_amount - (float)$this->advance_amount;
    }

    // Optimización de atributos con Cache
    public function getStatusLabelAttribute(): string
    {
        return Cache::remember("advance_status_label_{$this->id}_{$this->status}", now()->addDay(), function () {
            return self::STATUS[$this->status] ?? 'Desconocido';
        });
    }

    public function getStatusColorAttribute(): string
    {
        return Cache::remember("advance_status_color_{$this->id}_{$this->status}", now()->addDay(), function () {
            return self::STATUS_COLORS[$this->status] ?? 'gray';
        });
    }

    // Obtener etiqueta de fábrica
    public function getFactoryLabelAttribute(): string
    {
        return Cache::remember("advance_factory_label_{$this->id}_{$this->factory}", now()->addDay(), function () {
            return self::FACTORIES[$this->factory] ?? 'Desconocido';
        });
    }

    // Método optimizado para recalcular todos los valores a la vez
    public function recalculateAllValues(): void
    {
        $this->subtotal = $this->calculateSubtotal();
        $this->iva_value = $this->calculateIva();
        $this->total_amount = $this->calculateTotal();
        $this->advance_amount = $this->calculateAdvanceAmount();
        $this->pending_balance = $this->calculatePendingBalance();
        $this->amount_in_words = self::numberToWords($this->total_amount, $this->currency);
    }

    // Métodos de acción (mantienen funcionalidad)
    public function reject(string $reason): void
    {
        $this->status = 'REJECTED';
        $this->rejection_reason = $reason;
        $this->rejection_date = now();
        $this->status_updated_at = now();
        $this->save();

        // Limpia el caché relacionado
        $this->clearModelCache();
    }

    public function updateStatus(string $status): void
    {
        if (!array_key_exists($status, self::STATUS)) {
            throw new \InvalidArgumentException('Estado no válido');
        }

        $this->status = $status;
        $this->status_updated_at = now();

        if ($status === 'APPROVED') {
            $this->approved_by = Auth::id();
            $this->approved_at = now();
        }

        $this->save();

        // Limpia el caché relacionado
        $this->clearModelCache();
    }

    public function addSapCode(string $sapCode): void
    {
        $this->sap_code = $sapCode;
        $this->status = 'TREASURY';
        $this->status_updated_at = now();
        $this->accounted_by = Auth::id();
        $this->accounted_at = now();
        $this->save();

        // Limpia el caché relacionado
        $this->clearModelCache();
    }

    public function addEgressNumber(string $egressNumber): void
    {
        $this->egress_number = $egressNumber;
        $this->status = 'LEGALIZATION';
        $this->status_updated_at = now();
        $this->treasury_by = Auth::id();
        $this->treasury_at = now();
        $this->save();

        // Limpia el caché relacionado
        $this->clearModelCache();
    }

    public function addLegalizationNumber(string $legalizationNumber): void
    {
        $this->legalization_number = $legalizationNumber;
        $this->status = 'COMPLETED';
        $this->status_updated_at = now();
        $this->legalized_by = Auth::id();
        $this->legalized_at = now();
        $this->save();

        // Limpia el caché relacionado
        $this->clearModelCache();
    }

    // Método para limpiar caché asociado al modelo
    protected function clearModelCache(): void
    {
        Cache::forget("advance_status_label_{$this->id}_{$this->status}");
        Cache::forget("advance_status_color_{$this->id}_{$this->status}");
        Cache::forget("advance_factory_label_{$this->id}_{$this->factory}");
    }

    // Hook optimizado con verificación para evitar cálculos innecesarios
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($advance) {
            $advance->status = $advance->status ?? 'PENDING';
            $advance->status_updated_at = now();
            $advance->created_by = Auth::id();

            // Si no se ha especificado la fábrica, determinarla del correo del usuario
            if (empty($advance->factory)) {
                $user = Auth::user();
                $advance->factory = self::determineFactoryFromEmail($user->email);
            }

            // Realizamos todos los cálculos de una vez
            $advance->recalculateAllValues();
        });

        static::updating(function ($advance) {
            if ($advance->isDirty('status')) {
                $advance->status_updated_at = now();
            }

            // Solo recalculamos si han cambiado los campos relevantes
            if (
                $advance->isDirty(['quantity', 'unit_price', 'has_iva', 'advance_percentage']) ||
                $advance->isDirty(['subtotal', 'iva_value', 'total_amount'])
            ) {
                $advance->recalculateAllValues();
            }
        });
    }

    // Método mejorado para convertir números a palabras
    public static function numberToWords($number, $currency): string
    {
        // Cache de la conversión para evitar procesamiento repetido
        $cacheKey = "number_to_words_" . md5($number . $currency);

        return Cache::remember($cacheKey, now()->addMonth(), function () use ($number, $currency) {
            $currencies = [
                'COP' => 'PESOS COLOMBIANOS',
                'USD' => 'DÓLARES ESTADOUNIDENSES',
                'EURO' => 'EUROS'
            ];

            return number_format((float)$number, 2) . ' ' . ($currencies[$currency] ?? '');
        });
    }
}
