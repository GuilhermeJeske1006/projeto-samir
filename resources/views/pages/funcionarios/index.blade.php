<?php

use App\Models\Funcionario;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function delete(int $id)
    {
        $funcionario = Funcionario::findOrFail($id);
        $funcionario->delete();

        session()->flash('message', 'Funcionário excluído com sucesso!');
    }

    public function toggleStatus(int $id)
    {
        $funcionario = Funcionario::findOrFail($id);
        $funcionario->ativo = !$funcionario->ativo;
        $funcionario->save();
    }

    public function with(): array
    {
        return [
            'funcionarios' => Funcionario::query()->when($this->search, fn($q) => $q->where('nome', 'like', "%{$this->search}%"))->orderBy('nome')->paginate(10),
        ];
    }
}; ?>

<x-layouts::app :title="'Funcionários'">
    @volt('funcionarios.index')
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <flux:heading size="xl">Funcionários</flux:heading>
                <flux:button href="{{ route('funcionarios.create') }}" wire:navigate icon="plus">
                    Novo Funcionário
                </flux:button>
            </div>

            @if (session('message'))
                <flux:callout variant="success" class="mb-4">
                    {{ session('message') }}
                </flux:callout>
            @endif

            <div class="mb-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome..."
                    icon="magnifying-glass" />
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>CPF</flux:table.column>
                    <flux:table.column>Telefone</flux:table.column>
                    <flux:table.column>Valor/Hora</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Ações</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($funcionarios as $funcionario)
                        <flux:table.row>
                            <flux:table.cell>{{ $funcionario->nome }}</flux:table.cell>
                            <flux:table.cell>{{ $funcionario->cpf ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $funcionario->telefone ?? '-' }}</flux:table.cell>
                            <flux:table.cell>R$ {{ number_format($funcionario->valor_hora, 2, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :variant="$funcionario->ativo ? 'success' : 'danger'"
                                    wire:click="toggleStatus({{ $funcionario->id }})" class="cursor-pointer">
                                    {{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}
                                </flux:badge>

                            </flux:table.cell>
                            <flux:table.cell class="flex gap-2">
                                <flux:button size="sm" href="{{ route('funcionarios.edit', $funcionario) }}"
                                    wire:navigate icon="pencil" />
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $funcionario->id }})"
                                    wire:confirm="Tem certeza que deseja excluir este funcionário?" icon="trash" />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center py-8">
                                Nenhum funcionário encontrado.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $funcionarios->links() }}
            </div>
        </div>
    @endvolt
</x-layouts::app>
