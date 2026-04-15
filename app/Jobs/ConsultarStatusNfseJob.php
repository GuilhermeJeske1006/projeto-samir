<?php

namespace App\Jobs;

use App\Models\NotaFiscal;
use App\Services\NuvemFiscalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsultarStatusNfseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    private const MAX_TENTATIVAS = 10;

    public function __construct(
        public int $notaFiscalId,
        public int $tentativa = 1
    ) {}

    public function handle(NuvemFiscalService $service): void
    {
        $nota = NotaFiscal::findOrFail($this->notaFiscalId);

        if (! $nota->nfse_id || $nota->nfse_status === 'autorizado') {
            return;
        }

        try {
            $result = $service->consultarStatus($nota->nfse_id);
            $status = $result['status'] ?? 'processando';

            if ($status === 'autorizado') {
                $nota->update([
                    'nfse_status'             => 'autorizado',
                    'nfse_numero'             => $result['numero'] ?? null,
                    'nfse_codigo_verificacao' => $result['codigo_verificacao'] ?? null,
                    'nfse_url_pdf'            => $service->urlPdf($nota->nfse_id),
                    'nfse_emitida_em'         => $result['data_emissao'] ?? now(),
                    'nfse_erro'               => null,
                ]);
                return;
            }

            if (in_array($status, ['cancelado', 'erro'])) {
                $nota->update([
                    'nfse_status' => $status,
                    'nfse_erro'   => $result['mensagem_sefaz'] ?? $result['mensagem'] ?? 'Erro desconhecido',
                ]);
                return;
            }

            // Ainda processando
            if ($this->tentativa < self::MAX_TENTATIVAS) {
                ConsultarStatusNfseJob::dispatch($this->notaFiscalId, $this->tentativa + 1)
                    ->delay(now()->addSeconds(15));
            } else {
                Log::warning('ConsultarStatusNfseJob: máximo de tentativas atingido', [
                    'nota_id' => $this->notaFiscalId,
                    'nfse_id' => $nota->nfse_id,
                ]);
                $nota->update([
                    'nfse_status' => 'erro',
                    'nfse_erro'   => 'Timeout: status não resolvido após ' . self::MAX_TENTATIVAS . ' tentativas.',
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('ConsultarStatusNfseJob falhou', [
                'nota_id'  => $this->notaFiscalId,
                'tentativa' => $this->tentativa,
                'error'    => $e->getMessage(),
            ]);

            if ($this->tentativa < self::MAX_TENTATIVAS) {
                ConsultarStatusNfseJob::dispatch($this->notaFiscalId, $this->tentativa + 1)
                    ->delay(now()->addSeconds(15));
            }
        }
    }
}
