<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class NotaFiscal extends Model
{
    use HasFactory;

    protected $table = 'notas_fiscais';

    protected $fillable = [
        'tipo',
        'local_id',
        'funcionario_id',
        'numero',
        'data_emissao',
        'periodo_inicio',
        'periodo_fim',
        'total_horas',
        'valor_total',
        'descricao',
        'status',
        'observacao',
        'nfse_id',
        'nfse_numero',
        'nfse_codigo_verificacao',
        'nfse_status',
        'nfse_url_pdf',
        'nfse_emitida_em',
        'nfse_erro',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'periodo_inicio' => 'date',
            'periodo_fim' => 'date',
            'total_horas' => 'decimal:2',
            'valor_total' => 'decimal:2',
        ];
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function registrosHoras(): BelongsToMany
    {
        return $this->belongsToMany(RegistroHora::class, 'nota_fiscal_registro_hora');
    }

    public function scopeServico($query)
    {
        return $query->where('tipo', 'servico');
    }

    public function scopeRecibo($query)
    {
        return $query->where('tipo', 'recibo');
    }

    public function getDestinatarioNomeAttribute(): string
    {
        if ($this->tipo === 'servico') {
            return $this->local?->razao_social ?? $this->local?->nome ?? '-';
        }

        return $this->funcionario?->nome ?? '-';
    }

    public static function proximoNumero(): int
    {
        return DB::transaction(function () {
            $max = static::lockForUpdate()->max('numero') ?? 0;
            return $max + 1;
        });
    }

    public function precisaEmitirNfse(): bool
    {
        return $this->tipo === 'servico'
            && $this->status === 'emitida'
            && is_null($this->nfse_id);
    }

    public function isNfseEmitida(): bool
    {
        return $this->nfse_status === 'autorizado';
    }

    public function isNfseProcessando(): bool
    {
        return $this->nfse_status === 'processando';
    }
}
