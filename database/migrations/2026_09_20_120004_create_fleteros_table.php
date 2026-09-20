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
        Schema::create('fleteros', function (Blueprint $table) {
            $table->id();
            $table->string('numero_fletero')->nullable();
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('cuit')->nullable();
            $table->string('telefono')->nullable();
            $table->string('contacto')->nullable();
            $table->string('nacionalidad')->nullable();
            $table->timestamps();

            $table->index('razon_social');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleteros');
    }
};
