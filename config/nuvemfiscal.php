<?php

return [
    'client_id'          => env('NUVEMFISCAL_CLIENT_ID'),
    'client_secret'      => env('NUVEMFISCAL_CLIENT_SECRET'),
    'ambiente'           => env('NUVEMFISCAL_AMBIENTE', 'homologacao'), // homologacao|producao
    'token_url'          => 'https://auth.nuvemfiscal.com.br/oauth/token',
    'api_url'            => env('NUVEMFISCAL_API_URL', 'https://api.sandbox.nuvemfiscal.com.br'),
    'codigo_municipio'   => env('NUVEMFISCAL_CODIGO_MUNICIPIO'),
    'item_lista_servico' => env('NUVEMFISCAL_ITEM_LISTA_SERVICO', '17.05'),
    'aliquota_iss'       => env('NUVEMFISCAL_ALIQUOTA_ISS', 0),
];
