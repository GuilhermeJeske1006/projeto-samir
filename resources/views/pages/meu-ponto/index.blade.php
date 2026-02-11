<?php

use App\Models\Local;
use App\Models\Funcionario;
use App\Models\RegistroHora;
use Livewire\Volt\Component;

new class extends Component {
    public ?Funcionario $funcionario = null;
    public string $local_id = '';
    public string $horas = '8';
    public string $data = '';
    public string $observacao = '';

    public bool $showForm = false;
    public bool $showSuccess = false;

    public bool $criandoNovoLocal = false;
    public string $novoLocalNome = '';

    public function mount()
    {
        $this->funcionario = auth()->user()->funcionario;
        $this->data = now()->format('Y-m-d');
    }

    public function iniciarRegistro()
    {
        $this->showForm = true;
        $this->showSuccess = false;
    }

    public function toggleNovoLocal()
    {
        $this->criandoNovoLocal = !$this->criandoNovoLocal;
        $this->local_id = '';
        $this->novoLocalNome = '';
    }

    public function decrementarHoras()
    {
        $valor = floatval($this->horas) - 0.5;
        $this->horas = strval(max(0.5, $valor));
    }

    public function incrementarHoras()
    {
        $valor = floatval($this->horas) + 0.5;
        $this->horas = strval(min(24, $valor));
    }

    public function cancelar()
    {
        $this->reset(['local_id', 'horas', 'observacao', 'showForm', 'criandoNovoLocal', 'novoLocalNome']);
        $this->data = now()->format('Y-m-d');
        $this->horas = '8';
    }

    public function registrar()
    {
        if ($this->criandoNovoLocal) {
            $this->validate([
                'novoLocalNome' => 'required|string|max:255',
                'horas' => 'required|numeric|min:0.5|max:24',
                'data' => 'required|date',
                'observacao' => 'nullable|string|max:500',
            ], [
                'novoLocalNome.required' => 'Informe o nome do local',
                'horas.required' => 'Informe as horas trabalhadas',
                'horas.min' => 'Mínimo de 0.5 hora',
                'horas.max' => 'Máximo de 24 horas',
            ]);

            $local = Local::create([
                'nome' => $this->novoLocalNome,
                'valor_hora' => 0,
            ]);

            $local->funcionarios()->attach($this->funcionario->id);
        } else {
            $this->validate([
                'local_id' => 'required|exists:locais,id',
                'horas' => 'required|numeric|min:0.5|max:24',
                'data' => 'required|date',
                'observacao' => 'nullable|string|max:500',
            ], [
                'local_id.required' => 'Selecione o local de trabalho',
                'horas.required' => 'Informe as horas trabalhadas',
                'horas.min' => 'Mínimo de 0.5 hora',
                'horas.max' => 'Máximo de 24 horas',
            ]);

            $local = Local::find($this->local_id);
        }

        RegistroHora::create([
            'local_id' => $local->id,
            'funcionario_id' => $this->funcionario->id,
            'data' => $this->data,
            'horas' => $this->horas,
            'valor_hora_funcionario' => $this->funcionario->valor_hora,
            'valor_hora_local' => $local->valor_hora,
            'observacao' => $this->observacao,
        ]);

        $this->reset(['local_id', 'horas', 'observacao', 'showForm', 'criandoNovoLocal', 'novoLocalNome']);
        $this->data = now()->format('Y-m-d');
        $this->horas = '8';
        $this->showSuccess = true;
    }

    public function with(): array
    {
        $locais = [];
        $registrosRecentes = collect();

        if ($this->funcionario) {
            $locais = $this->funcionario->locais()->ativos()->orderBy('nome')->get();
            $registrosRecentes = $this->funcionario->registrosHoras()
                ->with('local')
                ->orderBy('data', 'desc')
                ->limit(5)
                ->get();
        }

        return [
            'locais' => $locais,
            'registrosRecentes' => $registrosRecentes,
        ];
    }
}; ?>

<x-layouts::app :title="'Meu Ponto'">
    @volt('meu-ponto.index')
    <div class="min-h-screen bg-zinc-100 dark:bg-zinc-900">
        {{-- Header Mobile --}}
        <div class="bg-white dark:bg-zinc-800 shadow-sm px-4 py-6 mb-4">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white text-center">
                Meu Ponto
            </h1>
            @if ($funcionario)
                <p class="text-center text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ $funcionario->nome }}
                </p>
            @endif
        </div>

        <div class="px-4 pb-8">
            @if (!$funcionario)
                {{-- Usuário não vinculado a funcionário --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-6 text-center">
                    <div class="text-yellow-600 dark:text-yellow-400 mb-2">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                        Conta não vinculada
                    </h3>
                    <p class="text-yellow-700 dark:text-yellow-300 mt-2">
                        Sua conta ainda não está vinculada a um funcionário. Entre em contato com o administrador.
                    </p>
                </div>
            @else
                {{-- Mensagem de sucesso --}}
                @if ($showSuccess)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 mb-4 text-center" wire:click="$set('showSuccess', false)">
                        <div class="text-green-600 dark:text-green-400 mb-2">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-green-800 dark:text-green-200">
                            Registrado!
                        </h3>
                        <p class="text-green-700 dark:text-green-300 mt-1">
                            Horas registradas com sucesso
                        </p>
                        <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                            Toque para fechar
                        </p>
                    </div>
                @endif

                @if (!$showForm)
                    {{-- Botão principal para registrar --}}
                    <button
                        wire:click="iniciarRegistro"
                        class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-2xl p-8 shadow-lg transition-all mb-6"
                    >
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-2xl font-bold block">Registrar Horas</span>
                            <span class="text-blue-200 text-sm mt-1 block">Toque para iniciar</span>
                        </div>
                    </button>
                @else
                    {{-- Formulário de registro --}}
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 mb-6">
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6 text-center">
                            Registrar Horas
                        </h2>

                        <form wire:submit="registrar" class="space-y-5">
                            {{-- Local --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Local de Trabalho
                                </label>

                                @if (!$criandoNovoLocal)
                                    <select
                                        wire:model="local_id"
                                        class="w-full rounded-xl border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-lg py-4 px-4 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Selecione o local...</option>
                                        @foreach ($locais as $local)
                                            <option value="{{ $local->id }}">{{ $local->nome }}</option>
                                        @endforeach
                                    </select>
                                    @error('local_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    <button
                                        type="button"
                                        wire:click="toggleNovoLocal"
                                        class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                    >
                                        + Cadastrar novo local
                                    </button>
                                @else
                                    <input
                                        type="text"
                                        wire:model="novoLocalNome"
                                        placeholder="Nome do novo local..."
                                        class="w-full rounded-xl border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-lg py-4 px-4 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    @error('novoLocalNome')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    <button
                                        type="button"
                                        wire:click="toggleNovoLocal"
                                        class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                    >
                                        Selecionar local existente
                                    </button>
                                @endif
                            </div>

                            {{-- Data --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Data
                                </label>
                                <input
                                    type="date"
                                    wire:model="data"
                                    class="w-full rounded-xl border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-lg py-4 px-4 focus:ring-blue-500 focus:border-blue-500"
                                />
                                @error('data')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Horas --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Horas Trabalhadas
                                </label>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        wire:click="decrementarHoras"
                                        class="w-14 h-14 rounded-xl bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-2xl font-bold flex items-center justify-center active:bg-zinc-300 dark:active:bg-zinc-600"
                                    >
                                        -
                                    </button>
                                    <input
                                        type="number"
                                        wire:model="horas"
                                        step="0.5"
                                        min="0.5"
                                        max="24"
                                        class="flex-1 rounded-xl border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white text-2xl py-4 px-4 text-center font-bold focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    <button
                                        type="button"
                                        wire:click="incrementarHoras"
                                        class="w-14 h-14 rounded-xl bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-2xl font-bold flex items-center justify-center active:bg-zinc-300 dark:active:bg-zinc-600"
                                    >
                                        +
                                    </button>
                                </div>
                                @error('horas')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Observação --}}
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    Observação (opcional)
                                </label>
                                <textarea
                                    wire:model="observacao"
                                    rows="2"
                                    placeholder="Ex: Instalação elétrica"
                                    class="w-full rounded-xl border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white py-3 px-4 focus:ring-blue-500 focus:border-blue-500"
                                ></textarea>
                            </div>

                            {{-- Botões --}}
                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    wire:click="cancelar"
                                    class="flex-1 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-xl py-4 text-lg font-semibold active:bg-zinc-300 dark:active:bg-zinc-600"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="flex-1 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white rounded-xl py-4 text-lg font-semibold"
                                >
                                    Confirmar
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Registros Recentes --}}
                @if ($registrosRecentes->count() > 0)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-4">
                            Últimos Registros
                        </h3>
                        <div class="space-y-3">
                            @foreach ($registrosRecentes as $registro)
                                <div class="flex items-center justify-between py-3 border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">
                                            {{ $registro->local->nome }}
                                        </p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $registro->data->format('d/m/Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-blue-600 dark:text-blue-400">
                                            {{ number_format($registro->horas, 1, ',', '.') }}h
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
    @endvolt
</x-layouts::app>
