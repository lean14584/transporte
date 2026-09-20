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
        Schema::table('transportes', function (Blueprint $table) {
            $table->string('numero_transporte')->nullable()->after('id');
            $table->string('cuit')->nullable()->after('telefono');
            $table->string('nacionalidad')->nullable()->after('cuit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transportes', function (Blueprint $table) {
            $table->dropColumn(['numero_transporte', 'cuit', 'nacionalidad']);
        });
    }
};
