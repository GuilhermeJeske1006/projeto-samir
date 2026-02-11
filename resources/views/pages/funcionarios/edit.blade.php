<?php

use App\Models\Funcionario;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public Funcionario $funcionario;
    public string $nome = '';
    public string $cpf = '';
    public string $telefone = '';
    public string $valor_hora = '';
    public bool $ativo = true;
    public string $user_id = '';

    public function mount(Funcionario $funcionario)
    {
        $this->funcionario = $funcionario;
        $this->nome = $funcionario->nome;
        $this->cpf = $funcionario->cpf ?? '';
        $this->telefone = $funcionario->telefone ?? '';
        $this->valor_hora = $funcionario->valor_hora;
        $this->ativo = $funcionario->ativo;
        $this->user_id = $funcionario->user_id ?? '';
    }

    public function save()
    {
        $validated = $this->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'telefone' => 'nullable|string|max:20',
            'valor_hora' => 'required|numeric|min:0',
            'ativo' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $this->funcionario->update([
            'nome' => $validated['nome'],
            'cpf' => $validated['cpf'],
            'telefone' => $validated['telefone'],
            'valor_hora' => $validated['valor_hora'],
            'ativo' => $validated['ativo'],
            'user_id' => $validated['user_id'] ?: null,
        ]);

        session()->flash('message', 'Funcionário atualizado com sucesso!');
        $this->redirect(route('funcionarios.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'users' => User::whereDoesntHave('funcionario')
                ->orWhere('id', $this->funcionario->user_id)
                ->orderBy('name')
                ->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Editar Funcionário'">
    @volt('funcionarios.edit')
    <div class="p-6 max-w-2xl">
        <flux:heading size="xl" class="mb-6">Editar Funcionário</flux:heading>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="nome" label="Nome" placeholder="Nome completo" required />

            <flux:input wire:model="cpf" label="CPF" placeholder="000.000.000-00" />

            <flux:input wire:model="telefone" label="Telefone" placeholder="(00) 00000-0000" />

            <flux:input wire:model="valor_hora" label="Valor por Hora (R$)" type="number" step="0.01" min="0" placeholder="0,00" required />

            <flux:switch wire:model="ativo" label="Ativo" />

            <flux:select wire:model="user_id" label="Vincular a Usuário (para acesso mobile)">
                <flux:select.option value="">Sem vínculo</flux:select.option>
                @foreach ($users as $user)
                    <flux:select.option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary">Salvar</flux:button>
                <flux:button href="{{ route('funcionarios.index') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts::app>
