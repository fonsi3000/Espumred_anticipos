<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Advance extends Model
{
    protected $fillable = [
        'provider_id',
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

    // Relaciones
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
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

    // Métodos de cálculo
    public function calculateSubtotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function calculateIva(): float
    {
        return $this->has_iva ? $this->subtotal * 0.19 : 0;
    }

    public function calculateTotal(): float
    {
        return $this->subtotal + $this->iva_value;
    }

    public function calculateAdvanceAmount(): float
    {
        return $this->total_amount * ($this->advance_percentage / 100);
    }

    public function calculatePendingBalance(): float
    {
        return $this->total_amount - $this->advance_amount;
    }

    // Attributes
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? 'Desconocido';
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    // Métodos de acción
    public function reject(string $reason): void
    {
        $this->status = 'REJECTED';
        $this->rejection_reason = $reason;
        $this->rejection_date = now();
        $this->status_updated_at = now();
        $this->save();
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
    }

    public function addSapCode(string $sapCode): void
    {
        $this->sap_code = $sapCode;
        $this->status = 'TREASURY';
        $this->status_updated_at = now();
        $this->accounted_by = Auth::id();
        $this->accounted_at = now();
        $this->save();
    }

    public function addEgressNumber(string $egressNumber): void
    {
        $this->egress_number = $egressNumber;
        $this->status = 'LEGALIZATION';
        $this->status_updated_at = now();
        $this->treasury_by = Auth::id();
        $this->treasury_at = now();
        $this->save();
    }

    public function addLegalizationNumber(string $legalizationNumber): void
    {
        $this->legalization_number = $legalizationNumber;
        $this->status = 'COMPLETED';
        $this->status_updated_at = now();
        $this->legalized_by = Auth::id();
        $this->legalized_at = now();
        $this->save();
    }

    // Hook para calcular valores antes de guardar
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($advance) {
            $advance->status = $advance->status ?? 'PENDING';
            $advance->status_updated_at = now();
            $advance->created_by = Auth::id();
            $advance->subtotal = $advance->calculateSubtotal();
            $advance->iva_value = $advance->calculateIva();
            $advance->total_amount = $advance->calculateTotal();
            $advance->advance_amount = $advance->calculateAdvanceAmount();
            $advance->pending_balance = $advance->calculatePendingBalance();
            $advance->amount_in_words = self::numberToWords($advance->total_amount, $advance->currency);
        });

        static::updating(function ($advance) {
            if ($advance->isDirty('status')) {
                $advance->status_updated_at = now();
            }
            $advance->subtotal = $advance->calculateSubtotal();
            $advance->iva_value = $advance->calculateIva();
            $advance->total_amount = $advance->calculateTotal();
            $advance->advance_amount = $advance->calculateAdvanceAmount();
            $advance->pending_balance = $advance->calculatePendingBalance();
            $advance->amount_in_words = self::numberToWords($advance->total_amount, $advance->currency);
        });
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
