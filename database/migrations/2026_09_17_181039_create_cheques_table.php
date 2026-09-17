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
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('cuenta_bancarias');
            $table->string('numero');
            $table->string('banco');
            $table->string('beneficiario');
            $table->date('fecha_emision');
            $table->date('fecha_pago');
            $table->decimal('monto', 12, 2);
            $table->enum('estado', ['emitido', 'cobrado', 'rechazado'])->default('emitido');
            $table->timestamps();

            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
