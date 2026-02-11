<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Funcionario extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
        'cpf',
        'telefone',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locais(): BelongsToMany
    {
        return $this->belongsToMany(Local::class, 'local_funcionario')
            ->withTimestamps();
    }

    public function registrosHoras(): HasMany
    {
        return $this->hasMany(RegistroHora::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
