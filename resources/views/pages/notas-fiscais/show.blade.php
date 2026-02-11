<?php

use App\Models\NotaFiscal;
use Livewire\Volt\Component;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component {
    public NotaFiscal $nota;

    public function mount(NotaFiscal $nota)
    {
        $this->nota = $nota->load(['local', 'funcionario', 'registrosHoras.local', 'registrosHoras.funcionario']);
    }

    public function gerarPdf()
    {
        $nota = $this->nota->load(['local', 'funcionario', 'registrosHoras.local', 'registrosHoras.funcionario']);
        $empresa = config('empresa');

        $view = $nota->tipo === 'servico' ? 'pdf.nota-fiscal' : 'pdf.recibo';

        $pdf = Pdf::loadView($view, [
            'nota' => $nota,
            'empresa' => $empresa,
        ]);

        $filename = $nota->tipo === 'servico'
            ? 'nota-servico-' . str_pad($nota->numero, 5, '0', STR_PAD_LEFT) . '.pdf'
            : 'recibo-' . str_pad($nota->numero, 5, '0', STR_PAD_LEFT) . '.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    public function cancelar()
    {
        $this->nota->update(['status' => 'cancelada']);
        session()->flash('message', 'Nota fiscal cancelada.');
    }
}; ?>

<x-layouts::app :title="'Nota Fiscal #' . str_pad($nota->numero, 5, '0', STR_PAD_LEFT)">
    @volt('notas-fiscais.show')
    <div class="p-6 max-w-4xl">
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">
                {{ $nota->tipo === 'servico' ? 'Nota de Serviço' : 'Recibo' }} #{{ str_pad($nota->numero, 5, '0', STR_PAD_LEFT) }}
            </flux:heading>
            <div class="flex gap-2">
                <flux:button wire:click="gerarPdf" icon="arrow-down-tray" variant="primary">
                    Baixar PDF
                </flux:button>
                <flux:button href="{{ route('notas-fiscais.index') }}" wire:navigate variant="ghost">
                    Voltar
                </flux:button>
            </div>
        </div>

        @if (session('message'))
            <flux:callout variant="success" class="mb-4">
                {{ session('message') }}
            </flux:callout>
        @endif

        <!-- Status -->
        <div class="mb-6">
            @if ($nota->status === 'emitida')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Emitida</span>
            @elseif ($nota->status === 'rascunho')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Rascunho</span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Cancelada</span>
            @endif
        </div>

        <!-- Dados gerais -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-4">Prestador</flux:heading>
                <div class="space-y-2 text-sm">
                    <div><span class="text-zinc-500 dark:text-zinc-400">Razão Social:</span> {{ config('empresa.razao_social') ?: 'Não configurado' }}</div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">CNPJ:</span> {{ config('empresa.cnpj') ?: 'Não configurado' }}</div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">Endereço:</span> {{ config('empresa.endereco') ?: '-' }}</div>
                    <div><span class="text-zinc-500 dark:text-zinc-400">Telefone:</span> {{ config('empresa.telefone') ?: '-' }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-4">
                    {{ $nota->tipo === 'servico' ? 'Tomador do Serviço' : 'Funcionário' }}
                </flux:heading>
                <div class="space-y-2 text-sm">
                    @if ($nota->tipo === 'servico' && $nota->local)
                        <div><span class="text-zinc-500 dark:text-zinc-400">Nome:</span> {{ $nota->local->nome }}</div>
                        <div><span class="text-zinc-500 dark:text-zinc-400">Razão Social:</span> {{ $nota->local->razao_social ?: '-' }}</div>
                        <div><span class="text-zinc-500 dark:text-zinc-400">CNPJ:</span> {{ $nota->local->cnpj ?: '-' }}</div>
                        <div><span class="text-zinc-500 dark:text-zinc-400">Endereço:</span> {{ $nota->local->endereco ?: '-' }}</div>
                    @elseif ($nota->funcionario)
                        <div><span class="text-zinc-500 dark:text-zinc-400">Nome:</span> {{ $nota->funcionario->nome }}</div>
                        <div><span class="text-zinc-500 dark:text-zinc-400">CPF:</span> {{ $nota->funcionario->cpf ?: '-' }}</div>
                        <div><span class="text-zinc-500 dark:text-zinc-400">Telefone:</span> {{ $nota->funcionario->telefone ?: '-' }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Info da nota -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Data de Emissão</div>
                    <div class="font-medium">{{ $nota->data_emissao->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Período</div>
                    <div class="font-medium">{{ $nota->periodo_inicio->format('d/m/Y') }} - {{ $nota->periodo_fim->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total de Horas</div>
                    <div class="font-medium">{{ number_format($nota->total_horas, 2, ',', '.') }}h</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Valor Total</div>
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="mb-2">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Descrição</div>
                <div>{{ $nota->descricao }}</div>
            </div>

            @if ($nota->observacao)
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Observação</div>
                    <div>{{ $nota->observacao }}</div>
                </div>
            @endif
        </div>

        <!-- Registros de horas vinculados -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
            <flux:heading size="lg" class="mb-4">Registros de Horas ({{ $nota->registrosHoras->count() }})</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Data</flux:table.column>
                    @if ($nota->tipo === 'servico')
                        <flux:table.column>Funcionário</flux:table.column>
                    @else
                        <flux:table.column>Local</flux:table.column>
                    @endif
                    <flux:table.column>Horas</flux:table.column>
                    <flux:table.column>Valor/h</flux:table.column>
                    <flux:table.column>Subtotal</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($nota->registrosHoras->sortBy('data') as $reg)
                        <flux:table.row>
                            <flux:table.cell>{{ $reg->data->format('d/m/Y') }}</flux:table.cell>
                            @if ($nota->tipo === 'servico')
                                <flux:table.cell>{{ $reg->funcionario->nome }}</flux:table.cell>
                            @else
                                <flux:table.cell>{{ $reg->local->nome }}</flux:table.cell>
                            @endif
                            <flux:table.cell>{{ number_format($reg->horas, 2, ',', '.') }}h</flux:table.cell>
                            <flux:table.cell>
                                R$ {{ number_format($nota->tipo === 'servico' ? $reg->valor_hora_local : $reg->valor_hora_funcionario, 2, ',', '.') }}
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">
                                R$ {{ number_format($nota->tipo === 'servico' ? $reg->valor_receber : $reg->valor_pagar, 2, ',', '.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>

        @if ($nota->status !== 'cancelada')
            <flux:button variant="danger" wire:click="cancelar" wire:confirm="Tem certeza que deseja cancelar esta nota fiscal?">
                Cancelar Nota
            </flux:button>
        @endif
    </div>
    @endvolt
</x-layouts::app>
