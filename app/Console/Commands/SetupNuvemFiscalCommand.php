<?php

namespace App\Console\Commands;

use App\Services\NuvemFiscalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupNuvemFiscalCommand extends Command
{
    protected $signature = 'nuvemfiscal:setup';
    protected $description = 'Cadastra a empresa e configura NFS-e na Nuvem Fiscal';

    public function handle(NuvemFiscalService $service): int
    {
        $empresa  = config('empresa');
        $cnpj     = preg_replace('/\D/', '', $empresa['cnpj']);
        $apiUrl   = rtrim(config('nuvemfiscal.api_url'), '/');

        // Token com scopes para gerenciar empresas e NFS-e
        $tokenResponse = Http::asForm()->post(config('nuvemfiscal.token_url'), [
            'grant_type'    => 'client_credentials',
            'client_id'     => config('nuvemfiscal.client_id'),
            'client_secret' => config('nuvemfiscal.client_secret'),
            'scope'         => 'empresa nfse',
        ]);

        if (! $tokenResponse->successful()) {
            $this->error('Falha ao obter token: ' . $tokenResponse->body());
            return self::FAILURE;
        }

        $token = $tokenResponse->json('access_token');

        $this->info("Cadastrando empresa CNPJ {$cnpj} na Nuvem Fiscal...");

        // 1. Cadastrar empresa
        $response = Http::withToken($token)->post("{$apiUrl}/empresas", [
            'cpf_cnpj'         => $cnpj,
            'nome_razao_social' => $empresa['razao_social'],
            'nome_fantasia'    => $empresa['razao_social'],
            'fone'             => preg_replace('/\D/', '', $empresa['telefone'] ?? ''),
            'email'            => $empresa['email'] ?? '',
            'endereco' => [
                'logradouro'      => $empresa['endereco'] ?? '',
                'numero'          => $empresa['numero'] ?? 'S/N',
                'bairro'          => $empresa['bairro'] ?? 'Centro',
                'codigo_municipio'=> env('NUVEMFISCAL_CODIGO_MUNICIPIO'),
                'cidade'          => $empresa['cidade'] ?? '',
                'uf'              => $empresa['uf'] ?? '',
                'cep'             => preg_replace('/\D/', '', $empresa['cep'] ?? ''),
            ],
        ]);

        $empresaJaExiste = $response->status() === 409
            || $response->json('error.code') === 'EmpresaAlreadyExists';

        if ($response->successful() || $empresaJaExiste) {
            $this->info($empresaJaExiste ? 'Empresa já cadastrada.' : 'Empresa cadastrada com sucesso.');
        } else {
            $this->error('Erro ao cadastrar empresa: ' . $response->body());
            return self::FAILURE;
        }

        // 2. Configurar NFS-e
        $this->info('Configurando NFS-e...');

        $nfseResponse = Http::withToken($token)->put("{$apiUrl}/empresas/{$cnpj}/nfse", [
            'ambiente' => config('nuvemfiscal.ambiente'),
            'rps' => [
                'lote'   => 1,
                'serie'  => '1',
                'numero' => 1,
            ],
        ]);

        if ($nfseResponse->successful()) {
            $this->info('NFS-e configurada com sucesso!');
            return self::SUCCESS;
        }

        $this->error('Erro ao configurar NFS-e: ' . $nfseResponse->body());
        return self::FAILURE;
    }
}
