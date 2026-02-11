<?php

use App\Models\Funcionario;
use Livewire\Volt\Component;

new class extends Component {
    public string $nome = '';
    public string $cpf = '';
    public string $telefone = '';
    public string $valor_hora = '';
    public bool $ativo = true;

    public function save()
    {
        $validated = $this->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'telefone' => 'nullable|string|max:20',
            'valor_hora' => 'required|numeric|min:0',
            'ativo' => 'boolean',
        ]);

        Funcionario::create($validated);

        session()->flash('message', 'Funcionário criado com sucesso!');
        $this->redirect(route('funcionarios.index'), navigate: true);
    }
}; ?>

<x-layouts::app :title="'Novo Funcionário'">
    @volt('funcionarios.create')
    <div class="p-6 max-w-2xl">
        <flux:heading size="xl" class="mb-6">Novo Funcionário</flux:heading>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="nome" label="Nome" placeholder="Nome completo" required />

            <flux:input wire:model="cpf" label="CPF" placeholder="000.000.000-00" />

            <flux:input wire:model="telefone" label="Telefone" placeholder="(00) 00000-0000" />

            <flux:input wire:model="valor_hora" label="Valor por Hora (R$)" type="number" step="0.01" min="0" placeholder="0,00" required />

            <flux:switch wire:model="ativo" label="Ativo" />

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary">Salvar</flux:button>
                <flux:button href="{{ route('funcionarios.index') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts::app>
