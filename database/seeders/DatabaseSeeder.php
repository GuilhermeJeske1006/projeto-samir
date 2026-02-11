<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Local;
use App\Models\Funcionario;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuário admin
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        // Usuário normal
        User::factory()->create([
            'name' => 'Usuário',
            'email' => 'user@example.com',
            'is_admin' => false,
        ]);

        // Funcionários de exemplo
        $joao = Funcionario::create([
            'nome' => 'João Silva',
            'cpf' => '123.456.789-00',
            'telefone' => '(11) 99999-1111',
            'valor_hora' => 25.00,
            'ativo' => true,
        ]);

        $maria = Funcionario::create([
            'nome' => 'Maria Santos',
            'cpf' => '987.654.321-00',
            'telefone' => '(11) 99999-2222',
            'valor_hora' => 30.00,
            'ativo' => true,
        ]);

        $pedro = Funcionario::create([
            'nome' => 'Pedro Oliveira',
            'cpf' => '456.789.123-00',
            'telefone' => '(11) 99999-3333',
            'valor_hora' => 28.00,
            'ativo' => true,
        ]);

        // Locais de exemplo
        $obraZe = Local::create([
            'nome' => 'Obra do Zé',
            'endereco' => 'Rua das Flores, 100',
            'descricao' => 'Construção de casa',
            'valor_hora' => 50.00,
            'ativo' => true,
        ]);

        $reformaMaria = Local::create([
            'nome' => 'Reforma da Maria',
            'endereco' => 'Av. Principal, 500',
            'descricao' => 'Reforma de apartamento',
            'valor_hora' => 45.00,
            'ativo' => true,
        ]);

        // Vincular funcionários aos locais
        $obraZe->funcionarios()->attach([$joao->id, $pedro->id]);
        $reformaMaria->funcionarios()->attach([$maria->id, $pedro->id]);
    }
}
