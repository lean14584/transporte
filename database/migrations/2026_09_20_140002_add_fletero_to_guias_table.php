<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->foreignId('fletero_id')->nullable()->after('cliente_id')->constrained('fleteros');
            $table->string('transportista_documento')->nullable()->after('transportista');
        });
    }

    public function down(): void
    {
        Schema::table('guias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fletero_id');
            $table->dropColumn('transportista_documento');
        });
    }
};
