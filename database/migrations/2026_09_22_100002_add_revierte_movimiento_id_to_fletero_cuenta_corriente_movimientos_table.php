<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mismo enlace estructurado que en cuenta_corriente_movimientos (ver esa
     * migración): 'orden_pago_id' es unique, así que la reversión de un
     * cheque rechazado no puede usarla.
     */
    public function up(): void
    {
        Schema::table('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->foreignId('revierte_movimiento_id')
                ->nullable()
                ->after('orden_pago_id')
                ->constrained('fletero_cuenta_corriente_movimientos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revierte_movimiento_id');
        });
    }
};
