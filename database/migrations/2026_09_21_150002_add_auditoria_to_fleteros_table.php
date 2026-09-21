<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleteros', function (Blueprint $table) {
            $table->foreignId('creado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fleteros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por_id');
            $table->dropConstrainedForeignId('actualizado_por_id');
        });
    }
};
