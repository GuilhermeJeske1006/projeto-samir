<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_fiscais', function (Blueprint $table) {
            if (! Schema::hasColumn('notas_fiscais', 'nfse_id')) {
                $table->string('nfse_id')->nullable()->after('observacao');
            }
            if (! Schema::hasColumn('notas_fiscais', 'nfse_numero')) {
                $table->string('nfse_numero')->nullable()->after('nfse_id');
            }
            if (! Schema::hasColumn('notas_fiscais', 'nfse_codigo_verificacao')) {
                $table->string('nfse_codigo_verificacao')->nullable()->after('nfse_numero');
            }
            if (! Schema::hasColumn('notas_fiscais', 'nfse_status')) {
                $table->string('nfse_status')->nullable()->after('nfse_codigo_verificacao');
            }
            if (! Schema::hasColumn('notas_fiscais', 'nfse_url_pdf')) {
                $table->string('nfse_url_pdf')->nullable()->after('nfse_status');
            }
            if (! Schema::hasColumn('notas_fiscais', 'nfse_emitida_em')) {
                $table->datetime('nfse_emitida_em')->nullable()->after('nfse_url_pdf');
            }
            if (! Schema::hasColumn('notas_fiscais', 'nfse_erro')) {
                $table->text('nfse_erro')->nullable()->after('nfse_emitida_em');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notas_fiscais', function (Blueprint $table) {
            $table->dropColumn([
                'nfse_id', 'nfse_numero', 'nfse_codigo_verificacao',
                'nfse_status', 'nfse_url_pdf', 'nfse_emitida_em', 'nfse_erro',
            ]);
        });
    }
};
