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
        Schema::create('cuenta_bancarias', function (Blueprint $table) {
            $table->id();
            $table->string('banco');
            $table->string('numero_cuenta')->nullable();
            $table->string('alias_cbu')->nullable();
            $table->decimal('saldo', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_bancarias');
    }
};
