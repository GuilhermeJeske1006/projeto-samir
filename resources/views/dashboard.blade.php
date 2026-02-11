<?php

use App\Models\RegistroHora;
use App\Models\Local;
use App\Models\Funcionario;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $local_id = '';
    public string $funcionario_id = '';
    public string $data_inicio = '';
    public string $data_fim = '';

    public function mount()
    {
        $this->data_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->data_fim = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = RegistroHora::query()
            ->when($this->local_id, fn($q) => $q->where('local_id', $this->local_id))
            ->when($this->funcionario_id, fn($q) => $q->where('funcionario_id', $this->funcionario_id))
            ->when($this->data_inicio, fn($q) => $q->whereDate('data', '>=', $this->data_inicio))
            ->when($this->data_fim, fn($q) => $q->whereDate('data', '<=', $this->data_fim));

        $totais = (clone $query)->selectRaw('
            SUM(horas) as total_horas,
            SUM(horas * valor_hora_funcionario) as total_pagar,
            SUM(horas * valor_hora_local) as total_receber,
            SUM(CASE WHEN pago_funcionario = 1 THEN horas * valor_hora_funcionario ELSE 0 END) as total_pago_funcionario,
            SUM(CASE WHEN pago_funcionario = 0 THEN horas * valor_hora_funcionario ELSE 0 END) as total_pendente_funcionario,
            SUM(CASE WHEN pago_local = 1 THEN horas * valor_hora_local ELSE 0 END) as total_pago_local,
            SUM(CASE WHEN pago_local = 0 THEN horas * valor_hora_local ELSE 0 END) as total_pendente_local
        ')->first();

        $porFuncionario = (clone $query)
            ->select('funcionario_id')
            ->selectRaw('SUM(horas) as total_horas')
            ->selectRaw('SUM(horas * valor_hora_funcionario) as total_pagar')
            ->selectRaw('SUM(CASE WHEN pago_funcionario = 1 THEN horas * valor_hora_funcionario ELSE 0 END) as total_pago')
            ->selectRaw('SUM(CASE WHEN pago_funcionario = 0 THEN horas * valor_hora_funcionario ELSE 0 END) as total_pendente')
            ->groupBy('funcionario_id')
            ->with('funcionario')
            ->get();

        $porLocal = (clone $query)
            ->select('local_id')
            ->selectRaw('SUM(horas) as total_horas')
            ->selectRaw('SUM(horas * valor_hora_funcionario) as total_pagar')
            ->selectRaw('SUM(horas * valor_hora_local) as total_receber')
            ->selectRaw('SUM(CASE WHEN pago_local = 1 THEN horas * valor_hora_local ELSE 0 END) as total_pago')
            ->selectRaw('SUM(CASE WHEN pago_local = 0 THEN horas * valor_hora_local ELSE 0 END) as total_pendente')
            ->groupBy('local_id')
            ->with('local')
            ->get();

        return [
            'totais' => $totais,
            'porFuncionario' => $porFuncionario,
            'porLocal' => $porLocal,
            'locais' => Local::orderBy('nome')->get(),
            'funcionarios' => Funcionario::orderBy('nome')->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Dashboard'">
    @volt('relatorios.index')
    <div class="p-6">
        <flux:heading size="xl" class="mb-6">Dashboard</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <flux:select wire:model.live="local_id" label="locais"  placeholder="Todos os locais">
                <flux:select.option value="">Todos os locais</flux:select.option>
                @foreach ($locais as $local)
                    <flux:select.option value="{{ $local->id }}">{{ $local->nome }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="funcionario_id" label="Funcionários" placeholder="Todos os funcionários">
                <flux:select.option value="">Todos os funcionários</flux:select.option>
                @foreach ($funcionarios as $funcionario)
                    <flux:select.option value="{{ $funcionario->id }}">{{ $funcionario->nome }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="data_inicio" type="date" label="Data Início" />
            <flux:input wire:model.live="data_fim" type="date" label="Data Fim" />
        </div>

        <!-- Cards de Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Total de Horas</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ number_format($totais->total_horas ?? 0, 2, ',', '.') }}h
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Total a Pagar</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    R$ {{ number_format($totais->total_pagar ?? 0, 2, ',', '.') }}
                </div>
                <div class="mt-2 text-xs space-y-1">
                    <div class="text-green-600 dark:text-green-400">Pago: R$ {{ number_format($totais->total_pago_funcionario ?? 0, 2, ',', '.') }}</div>
                    <div class="text-amber-600 dark:text-amber-400">Pendente: R$ {{ number_format($totais->total_pendente_funcionario ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Total a Receber</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    R$ {{ number_format($totais->total_receber ?? 0, 2, ',', '.') }}
                </div>
                <div class="mt-2 text-xs space-y-1">
                    <div class="text-green-600 dark:text-green-400">Recebido: R$ {{ number_format($totais->total_pago_local ?? 0, 2, ',', '.') }}</div>
                    <div class="text-amber-600 dark:text-amber-400">Pendente: R$ {{ number_format($totais->total_pendente_local ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Lucro</div>
                @php
                    $lucro = ($totais->total_receber ?? 0) - ($totais->total_pagar ?? 0);
                @endphp
                <div class="text-2xl font-bold {{ $lucro >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    R$ {{ number_format($lucro, 2, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Por Funcionário -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-4">Por Funcionário</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Funcionário</flux:table.column>
                        <flux:table.column>Horas</flux:table.column>
                        <flux:table.column>A Pagar</flux:table.column>
                        <flux:table.column>Pago</flux:table.column>
                        <flux:table.column>Pendente</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($porFuncionario as $item)
                            <flux:table.row>
                                <flux:table.cell>{{ $item->funcionario->nome }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($item->total_horas, 2, ',', '.') }}h</flux:table.cell>
                                <flux:table.cell class="text-red-600 dark:text-red-400">R$ {{ number_format($item->total_pagar, 2, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-green-600 dark:text-green-400">R$ {{ number_format($item->total_pago, 2, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-amber-600 dark:text-amber-400">R$ {{ number_format($item->total_pendente, 2, ',', '.') }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center py-4">Nenhum registro encontrado.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <!-- Por Local -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-4">Por Local / Obra</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Local</flux:table.column>
                        <flux:table.column>Horas</flux:table.column>
                        <flux:table.column>A Receber</flux:table.column>
                        <flux:table.column>Recebido</flux:table.column>
                        <flux:table.column>Pendente</flux:table.column>
                        <flux:table.column>Lucro</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($porLocal as $item)
                            @php
                                $lucroLocal = $item->total_receber - $item->total_pagar;
                            @endphp
                            <flux:table.row>
                                <flux:table.cell>{{ $item->local->nome }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($item->total_horas, 2, ',', '.') }}h</flux:table.cell>
                                <flux:table.cell class="text-green-600 dark:text-green-400">R$ {{ number_format($item->total_receber, 2, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-green-600 dark:text-green-400">R$ {{ number_format($item->total_pago, 2, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-amber-600 dark:text-amber-400">R$ {{ number_format($item->total_pendente, 2, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="{{ $lucroLocal >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    R$ {{ number_format($lucroLocal, 2, ',', '.') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center py-4">Nenhum registro encontrado.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>
    @endvolt
</x-layouts::app>
