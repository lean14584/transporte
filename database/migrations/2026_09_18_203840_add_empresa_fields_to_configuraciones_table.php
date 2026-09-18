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
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->string('empresa_nombre')->nullable()->after('id');
            $table->string('empresa_propietario')->nullable()->after('empresa_nombre');
            $table->string('empresa_direccion')->nullable()->after('empresa_propietario');
            $table->string('logo_path')->nullable()->after('empresa_direccion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->dropColumn(['empresa_nombre', 'empresa_propietario', 'empresa_direccion', 'logo_path']);
        });
    }
};
