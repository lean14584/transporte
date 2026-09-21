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
            $table->foreignId('pago_fletero_id')->nullable()->unique()->after('guia_id')->constrained('pago_fleteros')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fletero_cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pago_fletero_id');
        });
    }
};
