<?php

use App\Models\RegistroHora;
use App\Models\Local;
use App\Models\Funcionario;
use Livewire\Volt\Component;

new class extends Component {
    public string $local_id = '';
    public string $funcionario_id = '';
    public string $data = '';
    public string $horas = '';
    public string $observacao = '';

    public $funcionariosDoLocal = [];

    public function mount()
    {
        $this->data = now()->format('Y-m-d');
    }

    public function updatedLocalId($value)
    {
        $this->funcionario_id = '';
        $this->funcionariosDoLocal = [];

        if ($value) {
            $local = Local::with('funcionarios')->find($value);
            if ($local) {
                $this->funcionariosDoLocal = $local->funcionarios->toArray();
            }
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'local_id' => 'required|exists:locais,id',
            'funcionario_id' => 'required|exists:funcionarios,id',
            'data' => 'required|date',
            'horas' => 'required|numeric|min:0.01|max:24',
            'observacao' => 'nullable|string',
        ]);

        $local = Local::find($validated['local_id']);
        $funcionario = Funcionario::find($validated['funcionario_id']);

        RegistroHora::create([
            'local_id' => $validated['local_id'],
            'funcionario_id' => $validated['funcionario_id'],
            'data' => $validated['data'],
            'horas' => $validated['horas'],
            'valor_hora_funcionario' => $funcionario->valor_hora,
            'valor_hora_local' => $local->valor_hora,
            'observacao' => $validated['observacao'],
        ]);

        session()->flash('message', 'Registro de horas criado com sucesso!');
        $this->redirect(route('horas.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'locais' => Local::ativos()->orderBy('nome')->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Novo Registro de Horas'">
    @volt('horas.create')
    <div class="p-6 max-w-2xl">
        <flux:heading size="xl" class="mb-6">Novo Registro de Horas</flux:heading>

        <form wire:submit="save" class="space-y-6">
            <flux:select wire:model.live="local_id" label="Local / Obra" required>
                <flux:select.option value="">Selecione um local</flux:select.option>
                @foreach ($locais as $local)
                    <flux:select.option value="{{ $local->id }}">{{ $local->nome }} (R$ {{ number_format($local->valor_hora, 2, ',', '.') }}/h)</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="funcionario_id" label="Funcionário" required :disabled="empty($funcionariosDoLocal)">
                <flux:select.option value="">Selecione um funcionário</flux:select.option>
                @foreach ($funcionariosDoLocal as $funcionario)
                    <flux:select.option value="{{ $funcionario['id'] }}">{{ $funcionario['nome'] }} (R$ {{ number_format($funcionario['valor_hora'], 2, ',', '.') }}/h)</flux:select.option>
                @endforeach
            </flux:select>

            @if ($local_id && empty($funcionariosDoLocal))
                <flux:callout variant="warning">
                    Este local não possui funcionários vinculados. <a href="{{ route('locais.edit', $local_id) }}" class="underline" wire:navigate>Vincule funcionários ao local</a>.
                </flux:callout>
            @endif

            <flux:input wire:model="data" type="date" label="Data" required />

            <flux:input wire:model="horas" type="number" step="0.5" min="0.5" max="24" label="Horas Trabalhadas" placeholder="Ex: 8" required />

            <flux:textarea wire:model="observacao" label="Observação" placeholder="Observações adicionais..." rows="2" />

            <div class="flex gap-4">
                <flux:button type="submit" variant="primary">Salvar</flux:button>
                <flux:button href="{{ route('horas.index') }}" wire:navigate variant="ghost">Cancelar</flux:button>
            </div>
        </form>
    </div>
    @endvolt
</x-layouts::app>
