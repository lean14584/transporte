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
        Schema::table('cuenta_corriente_movimientos', function (Blueprint $table) {
            // Guia/Recibo son hasOne desde el modelo (1:1) pero nada lo forzaba
            // en la base; un doble-submit podía crear dos movimientos para la
            // misma guía/recibo. NULL múltiples siguen permitidos (MySQL no
            // los considera duplicados en un índice único).
            $table->unique('guia_id');
            $table->unique('recibo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->dropUnique(['guia_id']);
            $table->dropUnique(['recibo_id']);
        });
    }
};
