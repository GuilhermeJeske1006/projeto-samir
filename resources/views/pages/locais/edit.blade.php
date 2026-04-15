<?php

use App\Models\Local;
use App\Models\Funcionario;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Component;

new class extends Component {
    public Local $local;
    public string $nome = '';
    public string $cnpj = '';
    public string $razao_social = '';
    public string $endereco = '';
    public string $email = '';
    public string $telefone = '';
    public string $descricao = '';
    public string $valor_hora = '';
    public bool $ativo = true;
    public array $funcionarios_selecionados = [];

    public bool $buscandoCnpj = false;
    public string $cnpjStatus = '';

    public function mount(Local $local)
    {
        $this->local = $local;
        $this->nome = $local->nome;
        $this->cnpj = $local->cnpj ?? '';
        $this->razao_social = $local->razao_social ?? '';
        $this->endereco = $local->endereco ?? '';
        $this->email = $local->email ?? '';
        $this->telefone = $local->telefone ?? '';
        $this->descricao = $local->descricao ?? '';
        $this->valor_hora = $local->valor_hora;
        $this->ativo = $local->ativo;
        $this->funcionarios_selecionados = $local->funcionarios->pluck('id')->map(fn($id) => (string) $id)->toArray();
    }

    public function buscarDadosCnpj(): void
    {
        $digits = preg_replace('/\D/', '', $this->cnpj);
        if (strlen($digits) !== 14) {
            $this->cnpjStatus = '';
            return;
        }

        $this->buscandoCnpj = true;
        $this->cnpjStatus = '';

        try {
            $response = Http::timeout(6)->get("https://brasilapi.com.br/api/cnpj/v1/{$digits}");

            if ($response->successful()) {
                $d = $response->json();

                $situacao = strtoupper($d['descricao_situacao_cadastral'] ?? '');
                $this->cnpjStatus = $situacao === 'ATIVA' ? 'success' : 'inactive';

                $this->razao_social = $d['razao_social'] ?? '';
                if (empty($this->nome)) {
                    $this->nome = !empty($d['nome_fantasia']) ? $d['nome_fantasia'] : ($d['razao_social'] ?? '');
                }

                $partes = array_filter([
                    $d['logradouro'] ?? '',
                    $d['numero'] ?? '',
                    $d['complemento'] ?? '',
                    $d['bairro'] ?? '',
                    trim(($d['municipio'] ?? '') . (isset($d['uf']) ? '/' . $d['uf'] : '')),
                ]);
                $this->endereco = implode(', ', $partes);

                if (!empty($d['email'])) {
                    $this->email = strtolower($d['email']);
                }

                $tel = preg_replace('/\D/', '', $d['ddd_telefone_1'] ?? '');
                if (strlen($tel) === 10) {
                    $this->telefone = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                } elseif (strlen($tel) === 11) {
                    $this->telefone = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                }
            } elseif ($response->status() === 404) {
                $this->cnpjStatus = 'not_found';
            } else {
                $this->cnpjStatus = 'api_error';
            }
        } catch (\Exception) {
            $this->cnpjStatus = 'api_error';
        } finally {
            $this->buscandoCnpj = false;
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'razao_social' => 'nullable|string|max:255',
            'endereco' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'descricao' => 'nullable|string',
            'valor_hora' => 'required|numeric|min:0',
            'ativo' => 'boolean',
            'funcionarios_selecionados' => 'array',
        ]);

        $this->local->update([
            'nome' => $validated['nome'],
            'cnpj' => $validated['cnpj'],
            'razao_social' => $validated['razao_social'],
            'endereco' => $validated['endereco'],
            'email' => $validated['email'],
            'telefone' => $validated['telefone'],
            'descricao' => $validated['descricao'],
            'valor_hora' => $validated['valor_hora'],
            'ativo' => $validated['ativo'],
        ]);

        $this->local->funcionarios()->sync($validated['funcionarios_selecionados']);

        session()->flash('message', 'Local atualizado com sucesso!');
        $this->redirect(route('locais.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'funcionarios' => Funcionario::ativos()->orderBy('nome')->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Editar Local'">
    @volt('locais.edit')
    <div class="p-6 max-w-2xl">
        <flux:heading size="xl" class="mb-6">Editar Local</flux:heading>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="nome" label="Nome" placeholder="Ex: Obra do Zé" required />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:input
                        wire:model.defer="cnpj"
                        wire:blur="buscarDadosCnpj"
                        x-mask="99.999.999/9999-99"
                        label="CNPJ"
                        placeholder="00.000.000/0000-00"
                        :description="$buscandoCnpj ? 'Consultando Receita Federal...' : ''"
                    />
                    @if ($cnpjStatus === 'success')
                        <p class="mt-1.5 text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Empresa encontrada — dados preenchidos automaticamente
                        </p>
                    @elseif ($cnpjStatus === 'inactive')
                        <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Empresa encontrada mas situação cadastral não está ativa
                        </p>
                    @elseif ($cnpjStatus === 'not_found')
                        <p class="mt-1.5 text-xs text-red-500 dark:text-red-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            CNPJ não encontrado na Receita Federal
                        </p>
                    @elseif ($cnpjStatus === 'api_error')
                        <p class="mt-1.5 text-xs text-zinc-400 dark:text-zinc-500 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            Não foi possível consultar agora — preencha manualmente
                        </p>
                    @endif
                </div>
                <flux:input wire:model="razao_social" label="Razão Social" placeholder="Razão social do cliente" />
            </div>

            <flux:input wire:model="endereco" label="Endereço" placeholder="Ex: Rua das Flores, 123" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="email" label="Email" type="email" placeholder="email@exemplo.com" />
                <flux:input wire:model="telefone" x-mask:dynamic="$input.replace(/\D/g,'').length > 10 ? '(99) 99999-9999' : '(99) 9999-9999'" label="Telefone" placeholder="(00) 00000-0000" />
            </div>

            <flux:textarea wire:model="descricao" label="Descrição" placeholder="Descrição do local..." rows="3" />

            <flux:input wire:model="valor_hora" label="Valor por Hora (R$)" type="number" step="0.01" min="0" placeholder="0,00" required />

            <flux:switch wire:model="ativo" label="Ativo" />

            <div>
                <flux:label>
                    Funcionários Vinculados
                    @if (count($funcionarios_selecionados) > 0)
                        <flux:badge size="sm" class="ml-2">{{ count($funcionarios_selecionados) }} selecionado{{ count($funcionarios_selecionados) > 1 ? 's' : '' }}</flux:badge>
                    @endif
                </flux:label>
                <div class="mt-1.5 border border-zinc-200 dark:border-zinc-700 rounded-lg divide-y divide-zinc-100 dark:divide-zinc-800 max-h-52 overflow-y-auto">
                    @forelse ($funcionarios as $funcionario)
                        <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 cursor-pointer transition-colors">
                            <flux:checkbox wire:model="funcionarios_selecionados" value="{{ $funcionario->id }}" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $funcionario->nome }}</span>
                        </label>
                    @empty
                        <p class="px-3 py-4 text-sm text-zinc-400 dark:text-zinc-500 text-center">Nenhum funcionário ativo cadastrado</p>
                    @endforelse
                </div>
            </div>

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary">Salvar</flux:button>
                <flux:button href="{{ route('locais.index') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts::app>
