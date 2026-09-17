<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orden_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporte_id')->constrained('transportes');
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->string('concepto')->nullable();
            $table->enum('medio_pago', ['efectivo', 'transferencia', 'cheque']);
            $table->foreignId('cheque_id')->nullable()->constrained('cheques')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_pagos');
    }
};
