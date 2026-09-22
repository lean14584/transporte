<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El % de ganancia por defecto queda obsoleto: tanto guías "por
     * kilos" (siempre) como "por unidades" (desde 20260922) calculan el
     * total con precio_cliente/precio_fletero x cantidad, cargados al
     * crear la guía — no con un % aplicado en la recepción.
     */
    public function up(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->dropColumn(['porcentaje_ganancia_kilo', 'porcentaje_ganancia_unidad']);
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->decimal('porcentaje_ganancia_kilo', 5, 2)->default(10);
            $table->decimal('porcentaje_ganancia_unidad', 5, 2)->default(10);
        });
    }
};
