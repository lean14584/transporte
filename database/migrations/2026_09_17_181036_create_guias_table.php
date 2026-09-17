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
        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporte_id')->constrained('transportes');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('transportista');
            $table->string('camion_marca')->nullable();
            $table->string('camion_modelo')->nullable();
            $table->string('camion_patente')->nullable();
            $table->string('camion_color')->nullable();
            $table->string('mercaderia');
            $table->decimal('cantidad', 12, 2);
            $table->enum('unidad', ['kilos', 'unidades']);
            $table->date('fecha_carga');
            $table->enum('estado', ['cargada', 'recibida'])->default('cargada');

            $table->decimal('pesaje_bruto', 12, 2)->nullable();
            $table->decimal('tara', 12, 2)->nullable();
            $table->decimal('neto', 12, 2)->nullable();
            $table->decimal('porcentaje_ganancia', 5, 2)->nullable();
            $table->decimal('monto_ganancia', 12, 2)->nullable();
            $table->date('fecha_recepcion')->nullable();

            $table->foreignId('usuario_carga_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_recepcion_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['estado', 'fecha_carga']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guias');
    }
};
