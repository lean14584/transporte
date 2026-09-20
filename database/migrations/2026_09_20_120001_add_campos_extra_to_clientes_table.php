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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('numero_cliente')->nullable()->after('id');
            $table->string('razon_social')->nullable()->after('nombre');
            $table->string('cuit')->nullable()->after('dni');
            $table->string('contacto')->nullable()->after('telefono');
            $table->string('nacionalidad')->nullable()->after('cuit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['numero_cliente', 'razon_social', 'cuit', 'contacto', 'nacionalidad']);
        });
    }
};
