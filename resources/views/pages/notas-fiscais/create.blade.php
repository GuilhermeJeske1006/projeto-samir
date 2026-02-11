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

    public function updatedLocalId()
    {
        $this->buscarRegistros();
    }

    public function updatedFuncionarioId()
    {
        $this->buscarRegistros();
    }

    public function updatedPeriodoInicio()
    {
        $this->buscarRegistros();
    }

    public function updatedPeriodoFim()
    {
        $this->buscarRegistros();
    }

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
            'tipo' => 'required|in:servico,recibo',
            'local_id' => $this->tipo === 'servico' ? 'required|exists:locais,id' : 'nullable',
            'funcionario_id' => $this->tipo === 'recibo' ? 'required|exists:funcionarios,id' : 'nullable',
            'periodo_inicio' => 'required|date',
            'periodo_fim' => 'required|date|after_or_equal:periodo_inicio',
            'descricao' => 'required|string',
            'observacao' => 'nullable|string',
        ]);

        if (empty($this->registrosPreview)) {
            session()->flash('error', 'Nenhum registro de horas encontrado para o período selecionado.');
            return;
        }

        $nota = NotaFiscal::create([
            'tipo' => $this->tipo,
            'local_id' => $this->tipo === 'servico' ? $this->local_id : null,
            'funcionario_id' => $this->tipo === 'recibo' ? $this->funcionario_id : null,
            'numero' => NotaFiscal::proximoNumero(),
            'data_emissao' => now(),
            'periodo_inicio' => $this->periodo_inicio,
            'periodo_fim' => $this->periodo_fim,
            'total_horas' => $this->totalHoras,
            'valor_total' => $this->valorTotal,
            'descricao' => $this->descricao,
            'status' => 'emitida',
            'observacao' => $this->observacao,
        ]);

        $registroIds = collect($this->registrosPreview)->pluck('id');
        $nota->registrosHoras()->attach($registroIds);

        session()->flash('message', 'Nota fiscal #' . str_pad($nota->numero, 5, '0', STR_PAD_LEFT) . ' emitida com sucesso!');
        $this->redirect(route('notas-fiscais.show', $nota), navigate: true);
    }

    public function with(): array
    {
        return [
            'locais' => Local::orderBy('nome')->get(),
            'funcionarios' => Funcionario::orderBy('nome')->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Nova Nota Fiscal'">
    @volt('notas-fiscais.create')
    <div class="p-6 max-w-4xl">
        <flux:heading size="xl" class="mb-6">Nova Nota Fiscal</flux:heading>

        @if (session('error'))
            <flux:callout variant="danger" class="mb-4">
                {{ session('error') }}
            </flux:callout>
        @endif

        <form wire:submit="emitir" class="space-y-6">
            <flux:select wire:model.live="tipo" label="Tipo de Documento" required>
                <flux:select.option value="servico">Nota de Serviço (para Cliente/Local)</flux:select.option>
                <flux:select.option value="recibo">Recibo de Pagamento (para Funcionário)</flux:select.option>
            </flux:select>

            @if ($tipo === 'servico')
                <flux:select wire:model.live="local_id" label="Local / Cliente" required>
                    <flux:select.option value="">Selecione um local</flux:select.option>
                    @foreach ($locais as $local)
                        <flux:select.option value="{{ $local->id }}">{{ $local->nome }}{{ $local->cnpj ? ' - ' . $local->cnpj : '' }}</flux:select.option>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model.live="periodo_inicio" type="date" label="Período Início" required />
                <flux:input wire:model.live="periodo_fim" type="date" label="Período Fim" required />
            </div>

            <flux:textarea wire:model="descricao" label="Descrição do Serviço" rows="2" required />

            <flux:textarea wire:model="observacao" label="Observação" placeholder="Observações adicionais..." rows="2" />

            <!-- Preview dos registros -->
            @if (!empty($registrosPreview))
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <flux:heading size="lg" class="mb-4">Registros Incluídos ({{ count($registrosPreview) }})</flux:heading>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Data</flux:table.column>
                            @if ($tipo === 'servico')
                                <flux:table.column>Funcionário</flux:table.column>
                            @else
                                <flux:table.column>Local</flux:table.column>
                            @endif
                            <flux:table.column>Horas</flux:table.column>
                            <flux:table.column>Valor</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($registrosPreview as $reg)
                                <flux:table.row>
                                    <flux:table.cell>{{ \Carbon\Carbon::parse($reg['data'])->format('d/m/Y') }}</flux:table.cell>
                                    @if ($tipo === 'servico')
                                        <flux:table.cell>{{ $reg['funcionario']['nome'] ?? '-' }}</flux:table.cell>
                                    @else
                                        <flux:table.cell>{{ $reg['local']['nome'] ?? '-' }}</flux:table.cell>
                                    @endif
                                    <flux:table.cell>{{ number_format($reg['horas'], 2, ',', '.') }}h</flux:table.cell>
                                    <flux:table.cell class="font-medium">
                                        R$ {{ number_format($reg['horas'] * ($tipo === 'servico' ? $reg['valor_hora_local'] : $reg['valor_hora_funcionario']), 2, ',', '.') }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="mt-4 flex justify-end gap-6 text-lg font-bold">
                        <span>Total: {{ number_format($totalHoras, 2, ',', '.') }}h</span>
                        <span class="text-green-600 dark:text-green-400">R$ {{ number_format($valorTotal, 2, ',', '.') }}</span>
                    </div>
                </div>
            @elseif (($tipo === 'servico' && $local_id) || ($tipo === 'recibo' && $funcionario_id))
                <flux:callout variant="warning">
                    Nenhum registro de horas pendente encontrado para o período selecionado.
                </flux:callout>
            @endif

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary" :disabled="empty($registrosPreview)">Emitir Nota</flux:button>
                <flux:button href="{{ route('notas-fiscais.index') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts::app>
