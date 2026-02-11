<?php

use App\Models\RegistroHora;
use App\Models\Local;
use App\Models\Funcionario;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $local_id = '';
    public string $funcionario_id = '';
    public string $data_inicio = '';
    public string $data_fim = '';
    public string $status_funcionario = '';
    public string $status_local = '';

    public function mount()
    {
        $this->data_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->data_fim = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedLocalId()
    {
        $this->resetPage();
    }

    public function updatedFuncionarioId()
    {
        $this->resetPage();
    }

    public function updatedDataInicio()
    {
        $this->resetPage();
    }

    public function updatedDataFim()
    {
        $this->resetPage();
    }

    public function updatedStatusFuncionario()
    {
        $this->resetPage();
    }

    public function updatedStatusLocal()
    {
        $this->resetPage();
    }

    public function togglePagoFuncionario(int $id)
    {
        $registro = RegistroHora::findOrFail($id);
        $registro->update(['pago_funcionario' => !$registro->pago_funcionario]);
    }

    public function togglePagoLocal(int $id)
    {
        $registro = RegistroHora::findOrFail($id);
        $registro->update(['pago_local' => !$registro->pago_local]);
    }

    public function delete(int $id)
    {
        $registro = RegistroHora::findOrFail($id);
        $registro->delete();

        session()->flash('message', 'Registro excluído com sucesso!');
    }

    public function with(): array
    {
        $registros = RegistroHora::query()
            ->with(['local', 'funcionario'])
            ->when($this->local_id, fn($q) => $q->where('local_id', $this->local_id))
            ->when($this->funcionario_id, fn($q) => $q->where('funcionario_id', $this->funcionario_id))
            ->when($this->data_inicio, fn($q) => $q->whereDate('data', '>=', $this->data_inicio))
            ->when($this->data_fim, fn($q) => $q->whereDate('data', '<=', $this->data_fim))
            ->when($this->status_funcionario !== '', fn($q) => $q->where('pago_funcionario', $this->status_funcionario === 'pago'))
            ->when($this->status_local !== '', fn($q) => $q->where('pago_local', $this->status_local === 'pago'))
            ->orderBy('data', 'desc')
            ->paginate(15);

        return [
            'registros' => $registros,
            'locais' => Local::ativos()->orderBy('nome')->get(),
            'funcionarios' => Funcionario::ativos()->orderBy('nome')->get(),
        ];
    }
}; ?>

<x-layouts::app :title="'Registro de Horas'">
    @volt('horas.index')
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">Registro de Horas</flux:heading>
            <flux:button href="{{ route('horas.create') }}" wire:navigate icon="plus">
                Novo Registro
            </flux:button>
        </div>

        @if (session('message'))
            <flux:callout variant="success" class="mb-4">
                {{ session('message') }}
            </flux:callout>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <flux:select wire:model.live="local_id" label="Locais" placeholder="Todos os locais">
                <flux:select.option value="">Todos os locais</flux:select.option>
                @foreach ($locais as $local)
                    <flux:select.option value="{{ $local->id }}">{{ $local->nome }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="funcionario_id" label="Funcionários" placeholder="Todos os funcionários">
                <flux:select.option value="">Todos os funcionários</flux:select.option>
                @foreach ($funcionarios as $funcionario)
                    <flux:select.option value="{{ $funcionario->id }}">{{ $funcionario->nome }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="data_inicio" type="date" label="Data Início" />
            <flux:input wire:model.live="data_fim" type="date" label="Data Fim" />

            <flux:select wire:model.live="status_funcionario" label="Pgto Funcionário" placeholder="Todos">
                <flux:select.option value="">Todos</flux:select.option>
                <flux:select.option value="pago">Pago</flux:select.option>
                <flux:select.option value="pendente">Pendente</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="status_local" label="Pgto Local" placeholder="Todos">
                <flux:select.option value="">Todos</flux:select.option>
                <flux:select.option value="pago">Pago</flux:select.option>
                <flux:select.option value="pendente">Pendente</flux:select.option>
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Data</flux:table.column>
                <flux:table.column>Local</flux:table.column>
                <flux:table.column>Funcionário</flux:table.column>
                <flux:table.column>Horas</flux:table.column>
                <flux:table.column>Valor Pagar</flux:table.column>
                <flux:table.column>Valor Receber</flux:table.column>
                <flux:table.column>Lucro</flux:table.column>
                <flux:table.column>Pgto Func.</flux:table.column>
                <flux:table.column>Pgto Local</flux:table.column>
                <flux:table.column>Ações</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($registros as $registro)
                    <flux:table.row>
                        <flux:table.cell>{{ $registro->data->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $registro->local->nome }}</flux:table.cell>
                        <flux:table.cell>{{ $registro->funcionario->nome }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($registro->horas, 2, ',', '.') }}h</flux:table.cell>
                        <flux:table.cell class="text-red-600 dark:text-red-400">R$ {{ number_format($registro->valor_pagar, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell class="text-green-600 dark:text-green-400">R$ {{ number_format($registro->valor_receber, 2, ',', '.') }}</flux:table.cell>
                        <flux:table.cell class="{{ $registro->lucro >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($registro->lucro, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" wire:click="togglePagoFuncionario({{ $registro->id }})" variant="{{ $registro->pago_funcionario ? 'primary' : 'ghost' }}">
                                {{ $registro->pago_funcionario ? 'Pago' : 'Pendente' }}
                            </flux:button>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" wire:click="togglePagoLocal({{ $registro->id }})" variant="{{ $registro->pago_local ? 'primary' : 'ghost' }}">
                                {{ $registro->pago_local ? 'Pago' : 'Pendente' }}
                            </flux:button>
                        </flux:table.cell>
                        <flux:table.cell class="flex gap-2">
                            <flux:button size="sm" href="{{ route('horas.edit', $registro) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $registro->id }})" wire:confirm="Tem certeza que deseja excluir este registro?" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="10" class="text-center py-8">
                            Nenhum registro encontrado.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $registros->links() }}
        </div>
    </div>
    @endvolt
</x-layouts::app>
