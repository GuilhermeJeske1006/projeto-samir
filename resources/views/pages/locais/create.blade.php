<?php

use App\Models\Local;
use App\Models\Funcionario;
use Livewire\Volt\Component;

new class extends Component {
    public string $nome = '';
    public string $cnpj = '';
    public string $razao_social = '';
    public string $endereco = '';
    public string $email = '';
    public string $telefone = '';
    public string $descricao = '';
    public string $valor_hora = '';
    public bool $ativo = true;
    public array $funcionarios_selecionados = [];

    public function save()
    {
        $validated = $this->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'razao_social' => 'nullable|string|max:255',
            'endereco' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'descricao' => 'nullable|string',
            'valor_hora' => 'required|numeric|min:0',
            'ativo' => 'boolean',
            'funcionarios_selecionados' => 'array',
        ]);

        $local = Local::create([
            'nome' => $validated['nome'],
            'cnpj' => $validated['cnpj'],
            'razao_social' => $validated['razao_social'],
            'endereco' => $validated['endereco'],
            'email' => $validated['email'],
            'telefone' => $validated['telefone'],
            'descricao' => $validated['descricao'],
            'valor_hora' => $validated['valor_hora'],
            'ativo' => $validated['ativo'],
        ]);

        if (!empty($validated['funcionarios_selecionados'])) {
            $local->funcionarios()->attach($validated['funcionarios_selecionados']);
        }

        session()->flash('message', 'Local criado com sucesso!');
        $this->redirect(route('locais.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'funcionarios' => Funcionario::ativos()->orderBy('nome')->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Novo Local'">
    @volt('locais.create')
    <div class="p-6 max-w-2xl">
        <flux:heading size="xl" class="mb-6">Novo Local</flux:heading>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="nome" label="Nome" placeholder="Ex: Obra do Zé" required />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="cnpj" label="CNPJ" placeholder="00.000.000/0000-00" />
                <flux:input wire:model="razao_social" label="Razão Social" placeholder="Razão social do cliente" />
            </div>

            <flux:input wire:model="endereco" label="Endereço" placeholder="Ex: Rua das Flores, 123" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="email" label="Email" type="email" placeholder="email@exemplo.com" />
                <flux:input wire:model="telefone" label="Telefone" placeholder="(00) 00000-0000" />
            </div>

            <flux:textarea wire:model="descricao" label="Descrição" placeholder="Descrição do local..." rows="3" />

            <flux:input wire:model="valor_hora" label="Valor por Hora (R$)" type="number" step="0.01" min="0" placeholder="0,00" required />

            <flux:switch wire:model="ativo" label="Ativo" />

            <flux:select wire:model="funcionarios_selecionados" label="Funcionários Vinculados" multiple>
                @foreach ($funcionarios as $funcionario)
                    <flux:select.option value="{{ $funcionario->id }}">{{ $funcionario->nome }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary">Salvar</flux:button>
                <flux:button href="{{ route('locais.index') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts::app>
