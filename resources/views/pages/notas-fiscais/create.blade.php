<?php

use App\Models\NotaFiscal;
use App\Models\RegistroHora;
use App\Models\Local;
use App\Models\Funcionario;
use Livewire\Volt\Component;

new class extends Component {
    public string $tipo = 'servico';
    public string $local_id = '';
    public string $funcionario_id = '';
    public string $periodo_inicio = '';
    public string $periodo_fim = '';
    public string $descricao = 'Prestação de serviços conforme registros de horas.';
    public string $observacao = '';

    public $registrosPreview = [];
    public float $totalHoras = 0;
    public float $valorTotal = 0;

    public function mount()
    {
        $this->periodo_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->periodo_fim = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedTipo()
    {
        $this->local_id = '';
        $this->funcionario_id = '';
        $this->buscarRegistros();
    }

    public function updatedLocalId() { $this->buscarRegistros(); }
    public function updatedFuncionarioId() { $this->buscarRegistros(); }
    public function updatedPeriodoInicio() { $this->buscarRegistros(); }
    public function updatedPeriodoFim() { $this->buscarRegistros(); }

    public function buscarRegistros()
    {
        $this->registrosPreview = [];
        $this->totalHoras = 0;
        $this->valorTotal = 0;

        if ($this->tipo === 'servico' && !$this->local_id) return;
        if ($this->tipo === 'recibo' && !$this->funcionario_id) return;
        if (!$this->periodo_inicio || !$this->periodo_fim) return;

        $query = RegistroHora::query()
            ->with(['local', 'funcionario'])
            ->whereDate('data', '>=', $this->periodo_inicio)
            ->whereDate('data', '<=', $this->periodo_fim)
            ->whereDoesntHave('notasFiscais', function ($q) {
                $q->where('tipo', $this->tipo)->where('status', '!=', 'cancelada');
            });

        if ($this->tipo === 'servico') {
            $query->where('local_id', $this->local_id);
        } else {
            $query->where('funcionario_id', $this->funcionario_id);
        }

        $registros = $query->orderBy('data')->get();

        $this->registrosPreview = $registros->toArray();
        $this->totalHoras = $registros->sum('horas');

        if ($this->tipo === 'servico') {
            $this->valorTotal = $registros->sum(fn($r) => $r->horas * $r->valor_hora_local);
        } else {
            $this->valorTotal = $registros->sum(fn($r) => $r->horas * $r->valor_hora_funcionario);
        }
    }

    public function emitir()
    {
        $this->validate([
            'tipo'           => 'required|in:servico,recibo',
            'local_id'       => $this->tipo === 'servico' ? 'required|exists:locais,id' : 'nullable',
            'funcionario_id' => $this->tipo === 'recibo' ? 'required|exists:funcionarios,id' : 'nullable',
            'periodo_inicio' => 'required|date',
            'periodo_fim'    => 'required|date|after_or_equal:periodo_inicio',
            'descricao'      => 'required|string|max:2000',
            'observacao'     => 'nullable|string|max:1000',
        ]);

        if (empty($this->registrosPreview)) {
            session()->flash('error', 'Nenhum registro de horas encontrado para o período selecionado.');
            return;
        }

        $nota = NotaFiscal::create([
            'tipo'          => $this->tipo,
            'local_id'      => $this->tipo === 'servico' ? $this->local_id : null,
            'funcionario_id'=> $this->tipo === 'recibo' ? $this->funcionario_id : null,
            'numero'        => NotaFiscal::proximoNumero(),
            'data_emissao'  => now(),
            'periodo_inicio'=> $this->periodo_inicio,
            'periodo_fim'   => $this->periodo_fim,
            'total_horas'   => $this->totalHoras,
            'valor_total'   => $this->valorTotal,
            'descricao'     => $this->descricao,
            'status'        => 'emitida',
            'observacao'    => $this->observacao,
        ]);

        $registroIds = collect($this->registrosPreview)->pluck('id');
        $nota->registrosHoras()->attach($registroIds);

        session()->flash('message', 'Nota fiscal #' . str_pad($nota->numero, 5, '0', STR_PAD_LEFT) . ' emitida com sucesso!');
        $this->redirect(route('notas-fiscais.show', $nota), navigate: true);
    }

    public function with(): array
    {
        return [
            'locais'       => Local::ativos()->orderBy('nome')->get(),
            'funcionarios' => Funcionario::ativos()->orderBy('nome')->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Nova Nota Fiscal'">
    @volt('notas-fiscais.create')
    <div class="p-6 max-w-4xl">

        {{-- Cabeçalho --}}
        <div class="flex items-center gap-4 mb-6">
            <flux:button href="{{ route('notas-fiscais.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
            <div>
                <flux:heading size="xl">Nova Nota Fiscal</flux:heading>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Preencha os dados para emitir uma nota ou recibo</p>
            </div>
        </div>

        @if (session('error'))
            <flux:callout variant="danger" class="mb-5" icon="exclamation-circle">
                {{ session('error') }}
            </flux:callout>
        @endif

        <form wire:submit="emitir" class="space-y-5">

            {{-- Tipo de documento --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-4">Tipo de Documento</p>

                <div class="grid grid-cols-2 gap-3 mb-5">
                    <button type="button" wire:click="$set('tipo', 'servico')"
                        class="relative flex items-center gap-3 p-4 rounded-lg border-2 transition-all text-left
                            {{ $tipo === 'servico'
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0
                            {{ $tipo === 'servico' ? 'bg-blue-500' : 'bg-zinc-100 dark:bg-zinc-700' }}">
                            <svg class="w-5 h-5 {{ $tipo === 'servico' ? 'text-white' : 'text-zinc-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm {{ $tipo === 'servico' ? 'text-blue-700 dark:text-blue-300' : 'text-zinc-700 dark:text-zinc-300' }}">Nota de Serviço</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Para clientes / locais</p>
                        </div>
                    </button>

                    <button type="button" wire:click="$set('tipo', 'recibo')"
                        class="relative flex items-center gap-3 p-4 rounded-lg border-2 transition-all text-left
                            {{ $tipo === 'recibo'
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0
                            {{ $tipo === 'recibo' ? 'bg-purple-500' : 'bg-zinc-100 dark:bg-zinc-700' }}">
                            <svg class="w-5 h-5 {{ $tipo === 'recibo' ? 'text-white' : 'text-zinc-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm {{ $tipo === 'recibo' ? 'text-purple-700 dark:text-purple-300' : 'text-zinc-700 dark:text-zinc-300' }}">Recibo de Pagamento</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Para funcionários</p>
                        </div>
                    </button>
                </div>

                @if ($tipo === 'servico')
                    <flux:select wire:model.live="local_id" label="Local / Cliente" required>
                        <flux:select.option value="">Selecione um local</flux:select.option>
                        @foreach ($locais as $local)
                            <flux:select.option value="{{ $local->id }}">
                                {{ $local->nome }}{{ $local->cnpj ? ' — ' . $local->cnpj : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:select wire:model.live="funcionario_id" label="Funcionário" required>
                        <flux:select.option value="">Selecione um funcionário</flux:select.option>
                        @foreach ($funcionarios as $funcionario)
                            <flux:select.option value="{{ $funcionario->id }}">{{ $funcionario->nome }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            {{-- Período --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-4">Período de Referência</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model.live="periodo_inicio" type="date" label="Data Início" required />
                    <flux:input wire:model.live="periodo_fim" type="date" label="Data Fim" required />
                </div>
            </div>

            {{-- Descrição --}}
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-4">Detalhes</p>
                <div class="space-y-4">
                    <flux:textarea wire:model="descricao" label="Descrição do Serviço" rows="2" required maxlength="2000" />
                    <flux:textarea wire:model="observacao" label="Observação" placeholder="Observações adicionais (opcional)" rows="2" maxlength="1000" />
                </div>
            </div>

            {{-- Preview dos registros --}}
            @if (!empty($registrosPreview))
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                        <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide">
                            Registros Incluídos
                        </p>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($registrosPreview) }} registro(s)</span>
                    </div>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Data</flux:table.column>
                            @if ($tipo === 'servico')
                                <flux:table.column>Funcionário</flux:table.column>
                            @else
                                <flux:table.column>Local</flux:table.column>
                            @endif
                            <flux:table.column>Horas</flux:table.column>
                            <flux:table.column>Subtotal</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($registrosPreview as $reg)
                                <flux:table.row>
                                    <flux:table.cell class="text-sm">{{ \Carbon\Carbon::parse($reg['data'])->format('d/m/Y') }}</flux:table.cell>
                                    @if ($tipo === 'servico')
                                        <flux:table.cell class="text-sm">{{ $reg['funcionario']['nome'] ?? '—' }}</flux:table.cell>
                                    @else
                                        <flux:table.cell class="text-sm">{{ $reg['local']['nome'] ?? '—' }}</flux:table.cell>
                                    @endif
                                    <flux:table.cell class="text-sm">{{ number_format($reg['horas'], 2, ',', '.') }}h</flux:table.cell>
                                    <flux:table.cell class="font-semibold text-sm">
                                        R$ {{ number_format($reg['horas'] * ($tipo === 'servico' ? $reg['valor_hora_local'] : $reg['valor_hora_funcionario']), 2, ',', '.') }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-700 flex justify-end items-center gap-6">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            Total horas: <span class="font-bold text-zinc-700 dark:text-zinc-200">{{ number_format($totalHoras, 2, ',', '.') }}h</span>
                        </div>
                        <div class="text-base font-bold text-green-600 dark:text-green-400">
                            R$ {{ number_format($valorTotal, 2, ',', '.') }}
                        </div>
                    </div>
                </div>

            @elseif (($tipo === 'servico' && $local_id) || ($tipo === 'recibo' && $funcionario_id))
                <flux:callout variant="warning" icon="exclamation-triangle">
                    Nenhum registro de horas pendente encontrado para o período selecionado.
                </flux:callout>
            @endif

            {{-- Ações --}}
            <div class="flex items-center gap-3 pt-2">
                <flux:button type="submit" variant="primary" :disabled="empty($registrosPreview)"
                    wire:loading.attr="disabled" wire:target="emitir">
                    <span wire:loading.remove wire:target="emitir">Emitir Nota</span>
                    <span wire:loading wire:target="emitir">Emitindo...</span>
                </flux:button>
                <flux:button href="{{ route('notas-fiscais.index') }}" wire:navigate variant="ghost">
                    Cancelar
                </flux:button>
            </div>

        </form>
    </div>
    @endvolt
</x-layouts::app>
