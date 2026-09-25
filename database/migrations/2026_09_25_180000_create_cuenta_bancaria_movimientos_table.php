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
        Schema::create('cuenta_bancaria_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuenta_bancarias')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo', 12, 2);
            $table->foreignId('cheque_id')->nullable()->constrained('cheques')->nullOnDelete();
            $table->foreignId('revierte_movimiento_id')->nullable()->constrained('cuenta_bancaria_movimientos')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cuenta_bancaria_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_bancaria_movimientos');
    }
};
