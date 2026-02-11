<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_funcionario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('locais')->onDelete('cascade');
            $table->foreignId('funcionario_id')->constrained('funcionarios')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['local_id', 'funcionario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_funcionario');
    }
};
