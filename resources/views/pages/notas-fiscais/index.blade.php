<?php

use App\Models\NotaFiscal;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $tipo = '';
    public string $status = '';
    public string $data_inicio = '';
    public string $data_fim = '';

    public function mount()
    {
        $this->data_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->data_fim = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedTipo()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedDataInicio()
    {
        $this->resetPage();
    }

    public function updatedDataFim()
    {
        $this->resetPage();
    }

    public function cancelar(int $id)
    {
        $nota = NotaFiscal::findOrFail($id);
        $nota->update(['status' => 'cancelada']);
        session()->flash('message', 'Nota fiscal cancelada com sucesso!');
    }

    public function toggleStatus(int $id)
    {
        $nota = NotaFiscal::findOrFail($id);

        if ($nota->status === 'cancelada') {
            return;
        }

        $nota->status = $nota->status === 'rascunho' ? 'emitida' : 'rascunho';
        $nota->save();
    }

    public function with(): array
    {
        $notas = NotaFiscal::query()
            ->with(['local', 'funcionario'])
            ->when($this->tipo, fn($q) => $q->where('tipo', $this->tipo))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->data_inicio, fn($q) => $q->whereDate('data_emissao', '>=', $this->data_inicio))
            ->when($this->data_fim, fn($q) => $q->whereDate('data_emissao', '<=', $this->data_fim))
            ->orderBy('numero', 'desc')
            ->paginate(15);

        return [
            'notas' => $notas,
        ];
    }
}; ?>

<x-layouts::app :title="'Notas Fiscais'">
    @volt('notas-fiscais.index')
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">Notas Fiscais</flux:heading>
            <flux:button href="{{ route('notas-fiscais.create') }}" wire:navigate icon="plus">
                Nova Nota
            </flux:button>
        </div>

        @if (session('message'))
            <flux:callout variant="success" class="mb-4">
                {{ session('message') }}
            </flux:callout>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <flux:select wire:model.live="tipo" label="Tipo" placeholder="Todos">
                <flux:select.option value="">Todos</flux:select.option>
                <flux:select.option value="servico">Nota de Serviço</flux:select.option>
                <flux:select.option value="recibo">Recibo</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="status" label="Status" placeholder="Todos">
                <flux:select.option value="">Todos</flux:select.option>
                <flux:select.option value="rascunho">Rascunho</flux:select.option>
                <flux:select.option value="emitida">Emitida</flux:select.option>
                <flux:select.option value="cancelada">Cancelada</flux:select.option>
            </flux:select>

            <flux:input wire:model.live="data_inicio" type="date" label="Data Início" />
            <flux:input wire:model.live="data_fim" type="date" label="Data Fim" />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>N°</flux:table.column>
                <flux:table.column>Tipo</flux:table.column>
                <flux:table.column>Destinatário</flux:table.column>
                <flux:table.column>Período</flux:table.column>
                <flux:table.column>Horas</flux:table.column>
                <flux:table.column>Valor</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Ações</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($notas as $nota)
                    <flux:table.row>
                        <flux:table.cell class="font-mono">{{ str_pad($nota->numero, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($nota->tipo === 'servico')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Serviço</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Recibo</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $nota->destinatario_nome }}</flux:table.cell>
                        <flux:table.cell>{{ $nota->periodo_inicio->format('d/m/Y') }} - {{ $nota->periodo_fim->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($nota->total_horas, 2, ',', '.') }}h</flux:table.cell>
                        <flux:table.cell class="font-medium">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($nota->status === 'emitida')
                                <span wire:click="toggleStatus({{ $nota->id }})" class="cursor-pointer inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Emitida</span>
                            @elseif ($nota->status === 'rascunho')
                                <span wire:click="toggleStatus({{ $nota->id }})" class="cursor-pointer inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Rascunho</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Cancelada</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button size="sm" href="{{ route('notas-fiscais.show', $nota) }}" wire:navigate icon="eye" />
                            @if ($nota->status !== 'cancelada')
                                <flux:button size="sm" variant="danger" wire:click="cancelar({{ $nota->id }})" wire:confirm="Tem certeza que deseja cancelar esta nota?" icon="x-mark" />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-8">
                            Nenhuma nota fiscal encontrada.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $notas->links() }}
        </div>
    </div>
    @endvolt
</x-layouts::app>
