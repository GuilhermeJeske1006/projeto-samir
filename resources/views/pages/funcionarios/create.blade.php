<?php

use App\Mail\BemVindoFuncionario;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component {
    public string $nome = '';
    public string $email = '';
    public string $cpf = '';
    public string $telefone = '';
    public string $valor_hora = '';
    public bool $ativo = true;

    public function save()
    {
        $validated = $this->validate([
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'cpf' => 'nullable|string|max:14',
            'telefone' => 'nullable|string|max:20',
            'valor_hora' => 'required|numeric|min:0',
            'ativo' => 'boolean',
        ]);

        $userId = null;

        if (!empty($validated['email'])) {
            $user = User::create([
                'name' => $validated['nome'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(32)),
                'is_admin' => false,
            ]);

            $token = Password::createToken($user);
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);

            Mail::to($user->email)->send(new BemVindoFuncionario($user, $resetUrl));

            $userId = $user->id;
        }

        Funcionario::create([
            'user_id' => $userId,
            'nome' => $validated['nome'],
            'cpf' => $validated['cpf'],
            'telefone' => $validated['telefone'],
            'valor_hora' => $validated['valor_hora'],
            'ativo' => $validated['ativo'],
        ]);

        $message = !empty($validated['email'])
            ? 'Funcionário criado e e-mail de acesso enviado!'
            : 'Funcionário criado com sucesso!';

        session()->flash('message', $message);
        $this->redirect(route('funcionarios.index'), navigate: true);
    }
}; ?>

<x-layouts::app :title="'Novo Funcionário'">
    @volt('funcionarios.create')
    <div class="p-6 max-w-2xl">
        <flux:heading size="xl" class="mb-6">Novo Funcionário</flux:heading>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="nome" label="Nome" placeholder="Nome completo" required />

            <flux:input
                wire:model="email"
                label="E-mail"
                type="email"
                placeholder="funcionario@exemplo.com"
                description="Ao informar o e-mail, será criado um login no sistema e enviado um convite."
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="cpf" label="CPF" placeholder="000.000.000-00" />
                <flux:input wire:model="telefone" label="Telefone" placeholder="(00) 00000-0000" />
            </div>

            <flux:input wire:model="valor_hora" label="Valor por Hora (R$)" type="number" step="0.01" min="0" placeholder="0,00" required />

            <flux:switch wire:model="ativo" label="Ativo" />

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary">Salvar</flux:button>
                <flux:button href="{{ route('funcionarios.index') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts::app>
