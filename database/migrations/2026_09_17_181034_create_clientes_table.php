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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('dni')->nullable();
            $table->enum('condicion_iva', [
                'responsable_inscripto',
                'monotributista',
                'exento',
                'consumidor_final',
            ])->default('consumidor_final');
            $table->decimal('saldo_cta_cte', 12, 2)->default(0);
            $table->timestamps();

            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
