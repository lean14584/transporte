<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->string('estado')->default('confirmado')->after('medio_pago');
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
