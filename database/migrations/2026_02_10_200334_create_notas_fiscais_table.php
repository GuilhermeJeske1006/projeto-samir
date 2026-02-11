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
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['servico', 'recibo']);
            $table->foreignId('local_id')->nullable()->constrained('locais')->nullOnDelete();
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->unsignedInteger('numero');
            $table->date('data_emissao');
            $table->date('periodo_inicio');
            $table->date('periodo_fim');
            $table->decimal('total_horas', 8, 2);
            $table->decimal('valor_total', 12, 2);
            $table->text('descricao');
            $table->enum('status', ['rascunho', 'emitida', 'cancelada'])->default('rascunho');
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');
    }
};
