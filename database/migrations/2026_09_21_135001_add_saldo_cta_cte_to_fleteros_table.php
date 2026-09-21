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
        Schema::table('fleteros', function (Blueprint $table) {
            $table->decimal('saldo_cta_cte', 12, 2)->default(0)->after('nacionalidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleteros', function (Blueprint $table) {
            $table->dropColumn('saldo_cta_cte');
        });
    }
};
