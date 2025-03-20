<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->enum('factory', ['medellin', 'litoral']);
            $table->string('concept');
            $table->enum('currency', ['COP', 'USD', 'EURO']);
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 20, 2);
            $table->boolean('has_iva')->default(false);
            $table->decimal('subtotal', 20, 2)->nullable();
            $table->decimal('iva_value', 20, 2)->nullable();
            $table->decimal('total_amount', 20, 2)->nullable();
            $table->text('amount_in_words')->nullable();
            $table->decimal('advance_percentage', 5, 2);
            $table->decimal('advance_amount', 20, 2)->nullable();
            $table->decimal('pending_balance', 20, 2)->nullable();
            $table->string('purchase_order');
            $table->integer('legalization_term');

            // Estados y documentos relacionados
            $table->enum('status', [
                'PENDING',
                'APPROVED',
                'TREASURY',
                'LEGALIZATION',
                'COMPLETED',
                'REJECTED'
            ])->default('PENDING');
            $table->string('sap_code')->nullable();
            $table->string('egress_number')->nullable();
            $table->string('legalization_number')->nullable();
            $table->text('rejection_reason')->nullable();

            // Usuarios que realizaron acciones
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('accounted_by')->nullable()->constrained('users');
            $table->foreignId('treasury_by')->nullable()->constrained('users');
            $table->foreignId('legalized_by')->nullable()->constrained('users');

            // Fechas de las acciones
            $table->timestamp('rejection_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('accounted_at')->nullable();
            $table->timestamp('treasury_at')->nullable();
            $table->timestamp('legalized_at')->nullable();
            $table->timestamp('status_updated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advances');
    }
};
