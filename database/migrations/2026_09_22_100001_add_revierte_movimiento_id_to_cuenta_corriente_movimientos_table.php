<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enlace estructurado entre un movimiento de reversión (ej. cheque
     * rechazado) y el movimiento original que revierte. No puede ser
     * 'recibo_id'/'guia_id' porque esas columnas son unique (protegen el
     * pago/cargo original contra doble-submit) y un movimiento de reversión
     * es un segundo registro sobre el mismo recibo/guía.
     */
    public function up(): void
    {
        Schema::table('cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->foreignId('revierte_movimiento_id')
                ->nullable()
                ->after('recibo_id')
                ->constrained('cuenta_corriente_movimientos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revierte_movimiento_id');
        });
    }
};
