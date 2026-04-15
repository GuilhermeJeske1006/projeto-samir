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

    public function updatedTipo() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }
    public function updatedDataInicio() { $this->resetPage(); }
    public function updatedDataFim() { $this->resetPage(); }

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

    public function limparFiltros()
    {
        $this->tipo = '';
        $this->status = '';
        $this->data_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->data_fim = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function with(): array
    {
        $query = NotaFiscal::query()
            ->with(['local', 'funcionario'])
            ->when($this->tipo, fn($q) => $q->where('tipo', $this->tipo))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->data_inicio, fn($q) => $q->whereDate('data_emissao', '>=', $this->data_inicio))
            ->when($this->data_fim, fn($q) => $q->whereDate('data_emissao', '<=', $this->data_fim));

        $totais = (clone $query)->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status != "cancelada" THEN valor_total ELSE 0 END) as valor_ativo,
            SUM(CASE WHEN tipo = "servico" AND status != "cancelada" THEN 1 ELSE 0 END) as qtd_servico,
            SUM(CASE WHEN tipo = "recibo" AND status != "cancelada" THEN 1 ELSE 0 END) as qtd_recibo
        ')->first();

        $notas = $query->orderBy('numero', 'desc')->paginate(15);

        return [
            'notas'  => $notas,
            'totais' => $totais,
        ];
    }
}; ?>

<x-layouts::app :title="'Notas Fiscais'">
    @volt('notas-fiscais.index')
    <div class="p-6">

        {{-- Cabeçalho --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <flux:heading size="xl">Notas Fiscais</flux:heading>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Gerencie notas de serviço e recibos</p>
            </div>
            <flux:button href="{{ route('notas-fiscais.create') }}" wire:navigate icon="plus" variant="primary">
                Nova Nota
            </flux:button>
        </div>

        @if (session('message'))
            <flux:callout variant="success" class="mb-4" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        {{-- Cards de resumo --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Total Notas</p>
                <p class="text-2xl font-bold mt-1 text-zinc-900 dark:text-zinc-100">{{ number_format($totais->total ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Valor Ativo</p>
                <p class="text-2xl font-bold mt-1 text-green-600 dark:text-green-400">R$ {{ number_format($totais->valor_ativo ?? 0, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Serviços</p>
                <p class="text-2xl font-bold mt-1 text-blue-600 dark:text-blue-400">{{ number_format($totais->qtd_servico ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Recibos</p>
                <p class="text-2xl font-bold mt-1 text-purple-600 dark:text-purple-400">{{ number_format($totais->qtd_recibo ?? 0) }}</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
                <flux:select wire:model.live="tipo" label="Tipo" size="sm">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="servico">Nota de Serviço</flux:select.option>
                    <flux:select.option value="recibo">Recibo</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="status" label="Status" size="sm">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="rascunho">Rascunho</flux:select.option>
                    <flux:select.option value="emitida">Emitida</flux:select.option>
                    <flux:select.option value="cancelada">Cancelada</flux:select.option>
                </flux:select>

                <flux:input wire:model.live="data_inicio" type="date" label="Data Início" size="sm" />
                <flux:input wire:model.live="data_fim" type="date" label="Data Fim" size="sm" />

                <flux:button wire:click="limparFiltros" variant="ghost" size="sm" icon="x-mark">
                    Limpar
                </flux:button>
            </div>
        </div>

        {{-- Tabela --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-20">N°</flux:table.column>
                    <flux:table.column class="w-28">Tipo</flux:table.column>
                    <flux:table.column>Destinatário</flux:table.column>
                    <flux:table.column class="hidden md:table-cell">Período</flux:table.column>
                    <flux:table.column class="hidden md:table-cell w-20">Horas</flux:table.column>
                    <flux:table.column class="w-32">Valor</flux:table.column>
                    <flux:table.column class="w-28">Status</flux:table.column>
                    <flux:table.column class="hidden md:table-cell w-28">NFS-e</flux:table.column>
                    <flux:table.column class="w-24">Ações</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($notas as $nota)
                        <flux:table.row wire:key="{{ $nota->id }}">
                            <flux:table.cell>
                                <span class="font-mono text-sm text-zinc-500">{{ str_pad($nota->numero, 5, '0', STR_PAD_LEFT) }}</span>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($nota->tipo === 'servico')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Serviço
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        Recibo
                                    </span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="font-medium text-sm">{{ $nota->destinatario_nome }}</span>
                            </flux:table.cell>

                            <flux:table.cell class="hidden md:table-cell text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $nota->periodo_inicio->format('d/m/Y') }} – {{ $nota->periodo_fim->format('d/m/Y') }}
                            </flux:table.cell>

                            <flux:table.cell class="hidden md:table-cell text-sm text-zinc-600 dark:text-zinc-300">
                                {{ number_format($nota->total_horas, 1, ',', '.') }}h
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                    R$ {{ number_format($nota->valor_total, 2, ',', '.') }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($nota->status === 'emitida')
                                    <button wire:click="toggleStatus({{ $nota->id }})" title="Clique para voltar para rascunho"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900 transition-colors cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Emitida
                                    </button>
                                @elseif ($nota->status === 'rascunho')
                                    <button wire:click="toggleStatus({{ $nota->id }})" title="Clique para emitir"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900 transition-colors cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Rascunho
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Cancelada
                                    </span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="hidden md:table-cell">
                                @if ($nota->tipo === 'servico')
                                    @if ($nota->nfse_status === 'autorizado')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Autorizada
                                        </span>
                                    @elseif ($nota->nfse_status === 'processando')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                            <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                            Processando
                                        </span>
                                    @elseif ($nota->nfse_status === 'erro')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Erro
                                        </span>
                                    @elseif ($nota->nfse_status === 'cancelado')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                            Cancelada
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600 text-xs">—</span>
                                    @endif
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-600 text-xs">—</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-1.5">
                                    <flux:button size="sm" href="{{ route('notas-fiscais.show', $nota) }}" wire:navigate icon="eye" variant="ghost" />
                                    @if ($nota->status !== 'cancelada')
                                        <flux:button size="sm" variant="danger"
                                            wire:click="cancelar({{ $nota->id }})"
                                            wire:confirm="Tem certeza que deseja cancelar a nota #{{ str_pad($nota->numero, 5, '0', STR_PAD_LEFT) }}?"
                                            icon="x-mark" />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-zinc-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm font-medium">Nenhuma nota fiscal encontrada</p>
                                    <p class="text-xs">Tente ajustar os filtros ou crie uma nova nota</p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($notas->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $notas->links() }}
                </div>
            @endif
        </div>

    </div>
    @endvolt
</x-layouts::app>
