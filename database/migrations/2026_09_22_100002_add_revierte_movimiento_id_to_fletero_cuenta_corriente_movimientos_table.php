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
        // Nombre de constraint explícito y corto: el generado por defecto
        // (fletero_cuenta_corriente_movimientos_revierte_movimiento_id_foreign,
        // 68 caracteres) supera el límite de 64 de MySQL.
        Schema::table('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('revierte_movimiento_id')->nullable()->after('orden_pago_id');
            $table->foreign('revierte_movimiento_id', 'fccm_revierte_movimiento_fk')
                ->references('id')->on('fletero_cuenta_corriente_movimientos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->dropForeign('fccm_revierte_movimiento_fk');
            $table->dropColumn('revierte_movimiento_id');
        });
    }
};
