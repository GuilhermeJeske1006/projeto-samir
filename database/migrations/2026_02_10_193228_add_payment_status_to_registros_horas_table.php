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
        Schema::table('registros_horas', function (Blueprint $table) {
            $table->boolean('pago_funcionario')->default(false)->after('observacao');
            $table->boolean('pago_local')->default(false)->after('pago_funcionario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registros_horas', function (Blueprint $table) {
            $table->dropColumn(['pago_funcionario', 'pago_local']);
        });
    }
};
