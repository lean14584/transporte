<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Las órdenes de pago se emiten al fletero que hizo el flete, no al
     * transporte (la empresa de transporte). Sin datos reales todavía
     * (0 filas en producción al momento de este cambio), así que se
     * reemplaza la columna en vez de mantener las dos.
     */
    public function up(): void
    {
        Schema::table('orden_pagos', function (Blueprint $table) {
            $table->dropForeign(['transporte_id']);
            $table->dropColumn('transporte_id');
        });

        Schema::table('orden_pagos', function (Blueprint $table) {
            $table->foreignId('fletero_id')->after('id')->constrained('fleteros');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_pagos', function (Blueprint $table) {
            $table->dropForeign(['fletero_id']);
            $table->dropColumn('fletero_id');
        });

        Schema::table('orden_pagos', function (Blueprint $table) {
            $table->foreignId('transporte_id')->after('id')->constrained('transportes');
        });
    }
};
