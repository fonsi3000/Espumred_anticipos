<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Campo para el nombre del proveedor
            $table->string('document_number')->unique(); // Para NIT o cédula
            $table->string('address');
            $table->string('phone');
            $table->string('country');
            $table->string('city');
            $table->string('SAP_code')->nullable(); // Código SAP del proveedor
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
