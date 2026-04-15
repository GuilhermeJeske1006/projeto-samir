<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao {{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f4f5;
            margin: 0;
            padding: 0;
            color: #18181b;
        }
        .wrapper {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #18181b;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 22px;
            margin: 0;
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .body {
            padding: 40px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.65;
            color: #3f3f46;
            margin: 0 0 16px;
        }
        .body p.greeting {
            font-size: 17px;
            color: #18181b;
            font-weight: 500;
        }
        .info-box {
            background: #f4f4f5;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 24px 0;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #52525b;
        }
        .info-box p strong {
            color: #18181b;
        }
        .btn-wrapper {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background-color: #18181b;
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 32px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: -0.1px;
        }
        .link-fallback {
            font-size: 13px;
            color: #a1a1aa;
            word-break: break-all;
        }
        .link-fallback a {
            color: #71717a;
        }
        .footer {
            border-top: 1px solid #e4e4e7;
            padding: 24px 40px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #a1a1aa;
            margin: 0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>
        <div class="body">
            <p class="greeting">Olá, {{ $user->name }}!</p>
            <p>
                Uma conta foi criada para você no <strong>{{ config('app.name') }}</strong>.
                Para acessar o sistema, você precisa configurar sua senha clicando no botão abaixo.
            </p>

            <div class="info-box">
                <p><strong>Seu e-mail de acesso:</strong> {{ $user->email }}</p>
            </div>

            <div class="btn-wrapper">
                <a href="{{ $resetUrl }}" class="btn">Configurar minha senha</a>
            </div>

            <p>
                Este link expira em <strong>60 minutos</strong>. Caso expire, entre em contato com o administrador para gerar um novo acesso.
            </p>

            <p class="link-fallback">
                Se o botão não funcionar, copie e cole o link abaixo no seu navegador:<br>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </p>
        </div>
        <div class="footer">
            <p>
                Você está recebendo este e-mail porque uma conta foi criada para você.<br>
                Se não reconhece este cadastro, ignore este e-mail.
            </p>
        </div>
    </div>
</body>
</html>
