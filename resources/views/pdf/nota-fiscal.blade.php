<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nota de Serviço #{{ str_pad($nota->numero, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 30px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; margin-bottom: 5px; }
        .header .numero { font-size: 14px; color: #666; }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 15px; }
        .info-box { border: 1px solid #ddd; border-radius: 4px; padding: 12px; margin-bottom: 15px; }
        .info-box h3 { font-size: 13px; color: #555; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .info-box p { margin-bottom: 3px; font-size: 11px; }
        .info-box p strong { color: #555; }
        .dados-nota { margin-bottom: 20px; }
        .dados-nota .item { display: inline-block; margin-right: 30px; margin-bottom: 8px; }
        .dados-nota .item .label { font-size: 10px; color: #888; text-transform: uppercase; }
        .dados-nota .item .valor { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f5f5f5; padding: 8px 10px; text-align: left; font-size: 11px; border-bottom: 2px solid #ddd; }
        td { padding: 6px 10px; border-bottom: 1px solid #eee; font-size: 11px; }
        .text-right { text-align: right; }
        .total-row { border-top: 2px solid #333; }
        .total-row td { padding-top: 10px; font-weight: bold; font-size: 13px; }
        .descricao { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 12px; margin-bottom: 20px; }
        .descricao h3 { font-size: 12px; color: #555; margin-bottom: 5px; }
        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 15px; font-size: 10px; color: #888; text-align: center; }
        .valor-total { font-size: 18px; color: #16a34a; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>NOTA DE SERVIÇO</h1>
        <span class="numero">N° {{ str_pad($nota->numero, 5, '0', STR_PAD_LEFT) }} | Emissão: {{ $nota->data_emissao->format('d/m/Y') }}</span>
    </div>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-box">
                <h3>PRESTADOR DE SERVIÇO</h3>
                <p><strong>Razão Social:</strong> {{ $empresa['razao_social'] ?: '-' }}</p>
                <p><strong>CNPJ:</strong> {{ $empresa['cnpj'] ?: '-' }}</p>
                <p><strong>Insc. Municipal:</strong> {{ $empresa['inscricao_municipal'] ?: '-' }}</p>
                <p><strong>Endereço:</strong> {{ $empresa['endereco'] ?: '-' }}</p>
                <p><strong>Cidade/UF:</strong> {{ $empresa['cidade'] ?: '-' }}/{{ $empresa['uf'] ?: '-' }} - CEP: {{ $empresa['cep'] ?: '-' }}</p>
                <p><strong>Telefone:</strong> {{ $empresa['telefone'] ?: '-' }}</p>
                <p><strong>Email:</strong> {{ $empresa['email'] ?: '-' }}</p>
            </div>
        </div>
        <div class="info-col">
            <div class="info-box">
                <h3>TOMADOR DO SERVIÇO</h3>
                <p><strong>Nome:</strong> {{ $nota->local->nome ?? '-' }}</p>
                <p><strong>Razão Social:</strong> {{ $nota->local->razao_social ?? '-' }}</p>
                <p><strong>CNPJ:</strong> {{ $nota->local->cnpj ?? '-' }}</p>
                <p><strong>Endereço:</strong> {{ $nota->local->endereco ?? '-' }}</p>
                <p><strong>Email:</strong> {{ $nota->local->email ?? '-' }}</p>
                <p><strong>Telefone:</strong> {{ $nota->local->telefone ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div class="dados-nota">
        <div class="item">
            <div class="label">Período</div>
            <div class="valor">{{ $nota->periodo_inicio->format('d/m/Y') }} a {{ $nota->periodo_fim->format('d/m/Y') }}</div>
        </div>
        <div class="item">
            <div class="label">Total de Horas</div>
            <div class="valor">{{ number_format($nota->total_horas, 2, ',', '.') }}h</div>
        </div>
        <div class="item">
            <div class="label">Valor Total</div>
            <div class="valor valor-total">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="descricao">
        <h3>DESCRIÇÃO DO SERVIÇO</h3>
        <p>{{ $nota->descricao }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Funcionário</th>
                <th>Horas</th>
                <th class="text-right">Valor/h</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($nota->registrosHoras->sortBy('data') as $reg)
                <tr>
                    <td>{{ $reg->data->format('d/m/Y') }}</td>
                    <td>{{ $reg->funcionario->nome }}</td>
                    <td>{{ number_format($reg->horas, 2, ',', '.') }}h</td>
                    <td class="text-right">R$ {{ number_format($reg->valor_hora_local, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($reg->valor_receber, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td>{{ number_format($nota->total_horas, 2, ',', '.') }}h</td>
                <td></td>
                <td class="text-right">R$ {{ number_format($nota->valor_total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if ($nota->observacao)
        <div class="descricao">
            <h3>OBSERVAÇÕES</h3>
            <p>{{ $nota->observacao }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
