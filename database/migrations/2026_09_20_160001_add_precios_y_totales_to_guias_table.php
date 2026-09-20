<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->dropColumn(['tn_cliente', 'tn_fletero']);
        });

        Schema::table('guias', function (Blueprint $table) {
            $table->decimal('precio_cliente', 12, 2)->nullable()->after('tn_cargadas');
            $table->decimal('precio_fletero', 12, 2)->nullable()->after('precio_cliente');
            $table->decimal('total_cliente', 12, 2)->nullable()->after('monto_ganancia');
            $table->decimal('total_fletero', 12, 2)->nullable()->after('total_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->dropColumn(['precio_cliente', 'precio_fletero', 'total_cliente', 'total_fletero']);
        });

        Schema::table('guias', function (Blueprint $table) {
            $table->decimal('tn_cliente', 12, 2)->nullable()->after('tn_cargadas');
            $table->decimal('tn_fletero', 12, 2)->nullable()->after('tn_cliente');
        });
    }
};
