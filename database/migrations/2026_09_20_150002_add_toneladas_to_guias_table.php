<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->decimal('tn_cargadas', 12, 2)->nullable()->after('cantidad');
            $table->decimal('tn_cliente', 12, 2)->nullable()->after('tn_cargadas');
            $table->decimal('tn_fletero', 12, 2)->nullable()->after('tn_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->dropColumn(['tn_cargadas', 'tn_cliente', 'tn_fletero']);
        });
    }
};
