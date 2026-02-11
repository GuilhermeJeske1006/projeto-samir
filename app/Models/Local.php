<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Local extends Model
{
    use HasFactory;

    protected $table = 'locais';

    protected $fillable = [
        'nome',
        'cnpj',
        'razao_social',
        'endereco',
        'email',
        'telefone',
        'descricao',
        'valor_hora',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'valor_hora' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }

    public function funcionarios(): BelongsToMany
    {
        return $this->belongsToMany(Funcionario::class, 'local_funcionario')
            ->withTimestamps();
    }

    public function registrosHoras(): HasMany
    {
        return $this->hasMany(RegistroHora::class);
    }

    public function notasFiscais(): HasMany
    {
        return $this->hasMany(NotaFiscal::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
