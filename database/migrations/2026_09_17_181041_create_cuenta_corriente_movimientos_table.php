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
        Schema::create('cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['cargo', 'pago']);
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo', 12, 2);
            $table->foreignId('guia_id')->nullable()->constrained('guias')->nullOnDelete();
            $table->foreignId('recibo_id')->nullable()->constrained('recibos')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cliente_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_corriente_movimientos');
    }
};
