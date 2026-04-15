<?php

namespace App\Services;

use App\Models\NotaFiscal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NuvemFiscalService
{
    private string $apiUrl;
    private string $ambiente;

    public function __construct()
    {
        $this->apiUrl  = rtrim(config('nuvemfiscal.api_url'), '/');
        $this->ambiente = config('nuvemfiscal.ambiente', 'homologacao');
    }

    public function getAccessToken(): string
    {
        return Cache::remember('nuvemfiscal_token', 50 * 60, function () {
            $response = Http::asForm()->post(config('nuvemfiscal.token_url'), [
                'grant_type'    => 'client_credentials',
                'client_id'     => config('nuvemfiscal.client_id'),
                'client_secret' => config('nuvemfiscal.client_secret'),
                'scope'         => 'nfse',
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Falha ao obter token Nuvem Fiscal: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    public function emitir(NotaFiscal $nota): array
    {
        $nota->load('local');

        $payload = $this->buildPayload($nota);

        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->apiUrl}/nfse/dps", $payload);

        if (! $response->successful()) {
            Log::error('Nuvem Fiscal emitir erro', [
                'nota_id' => $nota->id,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            throw new \RuntimeException($response->json('message') ?? $response->body());
        }

        return $response->json();
    }

    public function consultarStatus(string $nfseId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->get("{$this->apiUrl}/nfse/{$nfseId}");

        if (! $response->successful()) {
            throw new \RuntimeException('Erro ao consultar NFS-e: ' . $response->body());
        }

        return $response->json();
    }

    public function cancelar(NotaFiscal $nota, string $motivo): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->apiUrl}/nfse/{$nota->nfse_id}/cancelamento", [
                'motivo' => $motivo,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Erro ao cancelar NFS-e: ' . $response->body());
        }

        return $response->json();
    }

    public function urlPdf(string $nfseId): string
    {
        return "{$this->apiUrl}/nfse/{$nfseId}/pdf";
    }

    private function buildPayload(NotaFiscal $nota): array
    {
        $local         = $nota->local;
        $empresa       = config('empresa');
        $cnpjPrestador = preg_replace('/\D/', '', $empresa['cnpj']);
        $aliquota      = (float) config('nuvemfiscal.aliquota_iss', 0);
        $valorServicos = (float) $nota->valor_total;
        $valorIss      = round($valorServicos * $aliquota / 100, 2);

        // c_trib_nac: código nacional (6 dígitos). Ex: 17.05 → "170500"
        $itemListaServico = config('nuvemfiscal.item_lista_servico', '17.05');
        $cTribNac = str_pad(str_replace('.', '', $itemListaServico), 6, '0');

        return [
            'ambiente'   => $this->ambiente,
            'referencia' => "NF-{$nota->numero}",
            'infDPS' => [
                'dhEmi'   => $nota->data_emissao->toIso8601String(),
                'dCompet' => $nota->periodo_inicio->startOfMonth()->format('Y-m-d'),
                'prest'   => ['CNPJ' => $cnpjPrestador],
                'toma'    => $this->buildTomador($local),
                'serv' => [
                    'locPrest' => [
                        'cLocPrestacao' => config('nuvemfiscal.codigo_municipio'),
                    ],
                    'cServ' => [
                        'cTribNac'  => $cTribNac,
                        'xDescServ' => mb_substr($nota->descricao, 0, 2000),
                    ],
                ],
                'valores' => [
                    'vServPrest' => [
                        'vServ' => $valorServicos,
                    ],
                    'trib' => [
                        'tribMun' => [
                            'tribISSQN' => 1,
                            'pAliq'     => $aliquota,
                            'vBC'       => $valorServicos,
                            'vISSQN'    => $valorIss,
                            'vLiq'      => $valorServicos - $valorIss,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildTomador($local): array
    {
        if (! $local) {
            return [];
        }

        $tomador = [
            'xNome' => $local->razao_social ?? $local->nome,
        ];

        if (! empty($local->email)) {
            $tomador['email'] = $local->email;
        }

        $doc = preg_replace('/\D/', '', $local->cnpj ?? '');
        if (strlen($doc) === 14) {
            $tomador['CNPJ'] = $doc;
        } elseif (strlen($doc) === 11) {
            $tomador['CPF'] = $doc;
        }

        if ($local->endereco) {
            $tomador['end'] = ['xLgr' => $local->endereco];
        }

        return $tomador;
    }
}
