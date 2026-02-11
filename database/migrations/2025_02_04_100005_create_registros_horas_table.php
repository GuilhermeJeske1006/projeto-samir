<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_horas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('locais')->onDelete('cascade');
            $table->foreignId('funcionario_id')->constrained('funcionarios')->onDelete('cascade');
            $table->date('data');
            $table->decimal('horas', 5, 2);
            $table->decimal('valor_hora_funcionario', 10, 2);
            $table->decimal('valor_hora_local', 10, 2);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['local_id', 'funcionario_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_horas');
    }
};
