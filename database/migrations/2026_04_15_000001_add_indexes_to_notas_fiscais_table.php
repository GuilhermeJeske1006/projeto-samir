<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_fiscais', function (Blueprint $table) {
            $table->unique('numero');
            $table->index('status');
            $table->index('data_emissao');
            $table->index('tipo');
            $table->index('nfse_status');
        });
    }

    public function down(): void
    {
        Schema::table('notas_fiscais', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->dropIndex(['status']);
            $table->dropIndex(['data_emissao']);
            $table->dropIndex(['tipo']);
            $table->dropIndex(['nfse_status']);
        });
    }
};
