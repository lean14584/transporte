<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleteros', function (Blueprint $table) {
            $table->string('dni')->nullable()->after('cuit');
        });
    }

    public function down(): void
    {
        Schema::table('fleteros', function (Blueprint $table) {
            $table->dropColumn('dni');
        });
    }
};
