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
        Schema::table('locais', function (Blueprint $table) {
            $table->string('cnpj')->nullable()->after('nome');
            $table->string('razao_social')->nullable()->after('cnpj');
            $table->string('email')->nullable()->after('endereco');
            $table->string('telefone')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->dropColumn(['cnpj', 'razao_social', 'email', 'telefone']);
        });
    }
};
