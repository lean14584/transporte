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
        Schema::create('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fletero_id')->constrained('fleteros')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['cargo', 'pago']);
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo', 12, 2);
            $table->foreignId('guia_id')->nullable()->unique()->constrained('guias')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fletero_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fletero_cuenta_corriente_movimientos');
    }
};
