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

class EmitirNfseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $notaFiscalId) {}

    public function handle(NuvemFiscalService $service): void
    {
        // lockForUpdate() evita race condition se o job for despachado duas vezes em paralelo
        $nota = NotaFiscal::lockForUpdate()->findOrFail($this->notaFiscalId);

        if ($nota->nfse_id && $nota->nfse_status !== 'erro') {
            return; // já emitida com sucesso, evita dupla emissão
        }

        try {
            $result = $service->emitir($nota);

            $nfseId = $result['id'] ?? null;
            $status = $result['status'] ?? 'processando';

            $campos = [
                'nfse_id'     => $nfseId,
                'nfse_status' => $status,
                'nfse_erro'   => null,
            ];

            if ($status === 'autorizado') {
                $campos['nfse_numero']             = $result['numero'] ?? null;
                $campos['nfse_codigo_verificacao'] = $result['codigo_verificacao'] ?? null;
                $campos['nfse_url_pdf']            = $nfseId ? $service->urlPdf($nfseId) : null;
                $campos['nfse_emitida_em']         = $result['data_emissao'] ?? now();
            }

            $nota->update($campos);

            if ($status === 'processando' && $nfseId) {
                ConsultarStatusNfseJob::dispatch($this->notaFiscalId)
                    ->delay(now()->addSeconds(10));
            }

        } catch (\Throwable $e) {
            Log::error('EmitirNfseJob falhou', [
                'nota_id'  => $this->notaFiscalId,
                'tentativa' => $this->attempts(),
                'error'    => $e->getMessage(),
            ]);

            // Só marca como erro definitivo após esgotar todas as tentativas
            if ($this->attempts() >= $this->tries) {
                $nota->update([
                    'nfse_status' => 'erro',
                    'nfse_erro'   => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}
