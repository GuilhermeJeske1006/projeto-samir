<?php

use App\Models\Local;
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
        $local = Local::findOrFail($id);
        $local->delete();

        session()->flash('message', 'Local excluído com sucesso!');
    }

    public function with(): array
    {
        return [
            'locais' => Local::query()
                ->when($this->search, fn($q) => $q->where('nome', 'like', "%{$this->search}%"))
                ->orderBy('nome')
                ->paginate(10),
        ];
    }
}; ?>

<x-layouts::app :title="'Locais / Obras'">
    @volt('locais.index')
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">Locais / Obras</flux:heading>
            <flux:button href="{{ route('locais.create') }}" wire:navigate icon="plus">
                Novo Local
            </flux:button>
        </div>

        @if (session('message'))
            <flux:callout variant="success" class="mb-4">
                {{ session('message') }}
            </flux:callout>
        @endif

        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome..." icon="magnifying-glass" />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nome</flux:table.column>
                <flux:table.column>Endereço</flux:table.column>
                <flux:table.column>Valor/Hora</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Ações</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($locais as $local)
                    <flux:table.row>
                        <flux:table.cell>{{ $local->nome }}</flux:table.cell>
                        <flux:table.cell>{{ $local->endereco ?? '-' }}</flux:table.cell>
                        <flux:table.cell>R$ {{ number_format($local->valor_hora, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :variant="$local->ativo ? 'success' : 'danger'">
                                {{ $local->ativo ? 'Ativo' : 'Inativo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button size="sm" href="{{ route('locais.edit', $local) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $local->id }})" wire:confirm="Tem certeza que deseja excluir este local?" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8">
                            Nenhum local encontrado.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $locais->links() }}
        </div>
    </div>
    @endvolt
</x-layouts::app>
