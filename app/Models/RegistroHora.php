<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RegistroHora extends Model
{
    use HasFactory;

    protected $table = 'registros_horas';

    protected $fillable = [
        'local_id',
        'funcionario_id',
        'data',
        'horas',
        'valor_hora_funcionario',
        'valor_hora_local',
        'observacao',
        'pago_funcionario',
        'pago_local',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'horas' => 'decimal:2',
            'valor_hora_funcionario' => 'decimal:2',
            'valor_hora_local' => 'decimal:2',
            'pago_funcionario' => 'boolean',
            'pago_local' => 'boolean',
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

    public function getValorPagarAttribute(): float
    {
        return $this->horas * $this->valor_hora_funcionario;
    }

    public function getValorReceberAttribute(): float
    {
        return $this->horas * $this->valor_hora_local;
    }

    public function getLucroAttribute(): float
    {
        return $this->valor_receber - $this->valor_pagar;
    }

    public function notasFiscais(): BelongsToMany
    {
        return $this->belongsToMany(NotaFiscal::class, 'nota_fiscal_registro_hora');
    }
}
