<?php

use App\Jobs\ConsultarStatusNfseJob;
use App\Jobs\EmitirNfseJob;
use App\Models\NotaFiscal;
use App\Services\NuvemFiscalService;
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

    public function emitirNfse()
    {
        if ($this->nota->tipo !== 'servico' || $this->nota->status !== 'emitida') {
            return;
        }

        // Permite re-tentativa em caso de erro
        $this->nota->update([
            'nfse_id'     => null,
            'nfse_status' => 'processando',
            'nfse_erro'   => null,
        ]);

        EmitirNfseJob::dispatch($this->nota->id);

        session()->flash('message', 'Emissão da NFS-e iniciada. Aguarde o processamento.');
        $this->nota->refresh();
    }

    public function verificarStatusNfse()
    {
        if (! $this->nota->nfse_id) {
            return;
        }

        try {
            $service = app(NuvemFiscalService::class);
            $result  = $service->consultarStatus($this->nota->nfse_id);
            $status  = $result['status'] ?? 'processando';

            $campos = ['nfse_status' => $status];

            if ($status === 'autorizado') {
                $campos['nfse_numero']             = $result['numero'] ?? null;
                $campos['nfse_codigo_verificacao'] = $result['codigo_verificacao'] ?? null;
                $campos['nfse_url_pdf']            = $service->urlPdf($this->nota->nfse_id);
                $campos['nfse_emitida_em']         = $result['data_emissao'] ?? now();
                $campos['nfse_erro']               = null;
            }

            $this->nota->update($campos);
            $this->nota->refresh();
        } catch (\Throwable $e) {
            session()->flash('error', 'Erro ao consultar status: ' . $e->getMessage());
        }
    }

    public function cancelar()
    {
        $this->nota->update(['status' => 'cancelada']);
        session()->flash('message', 'Nota fiscal cancelada.');
    }
}; ?>

<x-layouts::app :title="'Nota Fiscal #' . str_pad($nota->numero, 5, '0', STR_PAD_LEFT)">
    @volt('notas-fiscais.show')
    <div class="p-6 max-w-5xl">

        {{-- Cabeçalho --}}
        <div class="flex items-start justify-between mb-6 gap-4">
            <div class="flex items-center gap-4">
                <flux:button href="{{ route('notas-fiscais.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $nota->tipo === 'servico' ? 'Nota de Serviço' : 'Recibo' }}
                        <span class="font-mono text-zinc-400">#{{ str_pad($nota->numero, 5, '0', STR_PAD_LEFT) }}</span>
                    </h1>
                    <div class="flex items-center gap-2 mt-1">
                        @if ($nota->status === 'emitida')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Emitida
                            </span>
                        @elseif ($nota->status === 'rascunho')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Rascunho
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Cancelada
                            </span>
                        @endif
                        <span class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ $nota->tipo === 'servico' ? 'Nota de Serviço' : 'Recibo de Pagamento' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <flux:button wire:click="gerarPdf" icon="arrow-down-tray" variant="filled" size="sm">
                    Baixar PDF
                </flux:button>
            </div>
        </div>

        @if (session('message'))
            <flux:callout variant="success" class="mb-5" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        @if (session('error'))
            <flux:callout variant="danger" class="mb-5" icon="exclamation-circle">
                {{ session('error') }}
            </flux:callout>
        @endif

        {{-- NFS-e (apenas para notas de serviço) --}}
        @if ($nota->tipo === 'servico')
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-5 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <flux:heading size="sm">NFS-e — Nota Fiscal de Serviços Eletrônica</flux:heading>
                    </div>
                </div>
                <div class="px-6 py-5">
                    @if ($nota->precisaEmitirNfse())
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Não emitida</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">A nota está pronta para emissão eletrônica na prefeitura.</p>
                            </div>
                            <flux:button wire:click="emitirNfse" variant="primary" icon="paper-airplane" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="emitirNfse">Emitir NFS-e</span>
                                <span wire:loading wire:target="emitirNfse">Enviando...</span>
                            </flux:button>
                        </div>

                    @elseif ($nota->isNfseProcessando())
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                    <svg class="animate-spin h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Processando na prefeitura</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">A NFS-e está sendo analisada. Aguarde ou verifique o status manualmente.</p>
                                </div>
                            </div>
                            <flux:button wire:click="verificarStatusNfse" variant="ghost" icon="arrow-path" size="sm" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="verificarStatusNfse">Verificar</span>
                                <span wire:loading wire:target="verificarStatusNfse">Verificando...</span>
                            </flux:button>
                        </div>

                    @elseif ($nota->isNfseEmitida())
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-green-700 dark:text-green-300">Autorizada com sucesso</span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @if ($nota->nfse_numero)
                                    <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Número NFS-e</p>
                                        <p class="font-semibold text-sm mt-0.5">{{ $nota->nfse_numero }}</p>
                                    </div>
                                @endif
                                @if ($nota->nfse_codigo_verificacao)
                                    <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Cód. Verificação</p>
                                        <p class="font-semibold text-sm mt-0.5 font-mono">{{ $nota->nfse_codigo_verificacao }}</p>
                                    </div>
                                @endif
                                @if ($nota->nfse_emitida_em)
                                    <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Emitida em</p>
                                        <p class="font-semibold text-sm mt-0.5">{{ \Carbon\Carbon::parse($nota->nfse_emitida_em)->format('d/m/Y H:i') }}</p>
                                    </div>
                                @endif
                                @if ($nota->nfse_url_pdf)
                                    <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3 flex items-center">
                                        <a href="{{ $nota->nfse_url_pdf }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                            </svg>
                                            PDF da NFS-e
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                    @elseif ($nota->nfse_status === 'erro')
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-red-700 dark:text-red-300">Erro na emissão</p>
                                    @if ($nota->nfse_erro)
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1 bg-red-50 dark:bg-red-900/20 rounded px-3 py-2 font-mono">{{ $nota->nfse_erro }}</p>
                                    @endif
                                </div>
                                <flux:button wire:click="emitirNfse" variant="danger" icon="arrow-path" size="sm" wire:loading.attr="disabled">
                                    Tentar novamente
                                </flux:button>
                            </div>
                        </div>

                    @elseif ($nota->nfse_status === 'cancelado')
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                NFS-e Cancelada
                            </span>
                        </div>

                    @elseif ($nota->status !== 'emitida')
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            A nota precisa estar com status <strong>emitida</strong> para gerar a NFS-e.
                        </p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Grid: Prestador + Tomador --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-3">Prestador</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2">
                        <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Razão Social</span>
                        <span class="font-medium text-right">{{ config('empresa.razao_social') ?: 'Não configurado' }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-zinc-500 dark:text-zinc-400 shrink-0">CNPJ</span>
                        <span class="font-mono text-right">{{ config('empresa.cnpj') ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Endereço</span>
                        <span class="text-right">{{ config('empresa.endereco') ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Telefone</span>
                        <span class="text-right">{{ config('empresa.telefone') ?: '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-3">
                    {{ $nota->tipo === 'servico' ? 'Tomador do Serviço' : 'Funcionário' }}
                </p>
                <div class="space-y-2 text-sm">
                    @if ($nota->tipo === 'servico' && $nota->local)
                        <div class="flex justify-between gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Nome</span>
                            <span class="font-medium text-right">{{ $nota->local->nome }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Razão Social</span>
                            <span class="text-right">{{ $nota->local->razao_social ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400 shrink-0">CNPJ</span>
                            <span class="font-mono text-right">{{ $nota->local->cnpj ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Endereço</span>
                            <span class="text-right">{{ $nota->local->endereco ?: '—' }}</span>
                        </div>
                    @elseif ($nota->funcionario)
                        <div class="flex justify-between gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Nome</span>
                            <span class="font-medium text-right">{{ $nota->funcionario->nome }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400 shrink-0">CPF</span>
                            <span class="font-mono text-right">{{ $nota->funcionario->cpf ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-zinc-500 dark:text-zinc-400 shrink-0">Telefone</span>
                            <span class="text-right">{{ $nota->funcionario->telefone ?: '—' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Resumo financeiro --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-5">
            <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide mb-4">Detalhes da Nota</p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Data de Emissão</p>
                    <p class="font-semibold text-sm mt-0.5">{{ $nota->data_emissao->format('d/m/Y') }}</p>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Período</p>
                    <p class="font-semibold text-sm mt-0.5">{{ $nota->periodo_inicio->format('d/m/Y') }} – {{ $nota->periodo_fim->format('d/m/Y') }}</p>
                </div>
                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Total de Horas</p>
                    <p class="font-semibold text-sm mt-0.5">{{ number_format($nota->total_horas, 2, ',', '.') }}h</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg px-4 py-3 border border-green-100 dark:border-green-900/30">
                    <p class="text-xs text-green-600 dark:text-green-400">Valor Total</p>
                    <p class="font-bold text-lg text-green-700 dark:text-green-300 mt-0.5">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Descrição</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">{{ $nota->descricao }}</p>
                </div>
                @if ($nota->observacao)
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Observação</p>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg px-4 py-3">{{ $nota->observacao }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Registros de horas --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-5">
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wide">Registros de Horas</p>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $nota->registrosHoras->count() }} registro(s)</span>
            </div>
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
                    @forelse ($nota->registrosHoras->sortBy('data') as $reg)
                        <flux:table.row wire:key="reg-{{ $reg->id }}">
                            <flux:table.cell class="text-sm">{{ $reg->data->format('d/m/Y') }}</flux:table.cell>
                            @if ($nota->tipo === 'servico')
                                <flux:table.cell class="text-sm">{{ $reg->funcionario->nome }}</flux:table.cell>
                            @else
                                <flux:table.cell class="text-sm">{{ $reg->local->nome }}</flux:table.cell>
                            @endif
                            <flux:table.cell class="text-sm">{{ number_format($reg->horas, 2, ',', '.') }}h</flux:table.cell>
                            <flux:table.cell class="text-sm text-zinc-500">
                                R$ {{ number_format($nota->tipo === 'servico' ? $reg->valor_hora_local : $reg->valor_hora_funcionario, 2, ',', '.') }}
                            </flux:table.cell>
                            <flux:table.cell class="font-semibold text-sm">
                                R$ {{ number_format($nota->tipo === 'servico' ? $reg->valor_receber : $reg->valor_pagar, 2, ',', '.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-8 text-center text-sm text-zinc-400">
                                Nenhum registro de horas vinculado.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Ações destrutivas --}}
        @if ($nota->status !== 'cancelada')
            <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/10 rounded-xl border border-red-100 dark:border-red-900/30">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-700 dark:text-red-300">Zona de perigo</p>
                    <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">Cancelar a nota é uma ação irreversível.</p>
                </div>
                <flux:button variant="danger" size="sm" wire:click="cancelar"
                    wire:confirm="Tem certeza que deseja cancelar esta nota fiscal? Esta ação não pode ser desfeita.">
                    Cancelar Nota
                </flux:button>
            </div>
        @endif

    </div>
    @endvolt
</x-layouts::app>
