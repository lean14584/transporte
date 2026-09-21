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
        Schema::table('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->foreignId('orden_pago_id')->nullable()->unique()->after('guia_id')->constrained('orden_pagos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('orden_pago_id');
        });
    }
};
