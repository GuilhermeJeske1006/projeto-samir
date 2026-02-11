<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', function () {
    if (! auth()->user()->isAdmin()) {
        return redirect()->route('meu-ponto.index');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Área do Funcionário (todos os usuários autenticados)
Route::middleware(['auth'])->group(function () {
    Route::get('meu-ponto', fn () => view('pages.meu-ponto.index'))->name('meu-ponto.index');
});

// Rotas protegidas por autenticação + admin
Route::middleware(['auth', 'admin'])->group(function () {
    // Locais
    Route::get('locais', fn () => view('pages.locais.index'))->name('locais.index');
    Route::get('locais/criar', fn () => view('pages.locais.create'))->name('locais.create');
    Route::get('locais/{local}/editar', fn ($local) => view('pages.locais.edit', ['local' => \App\Models\Local::findOrFail($local)]))->name('locais.edit');

    // Funcionários
    Route::get('funcionarios', fn () => view('pages.funcionarios.index'))->name('funcionarios.index');
    Route::get('funcionarios/criar', fn () => view('pages.funcionarios.create'))->name('funcionarios.create');
    Route::get('funcionarios/{funcionario}/editar', fn ($funcionario) => view('pages.funcionarios.edit', ['funcionario' => \App\Models\Funcionario::findOrFail($funcionario)]))->name('funcionarios.edit');

    // Registro de Horas
    Route::get('horas', fn () => view('pages.horas.index'))->name('horas.index');
    Route::get('horas/criar', fn () => view('pages.horas.create'))->name('horas.create');
    Route::get('horas/{registro}/editar', fn ($registro) => view('pages.horas.edit', ['registro' => \App\Models\RegistroHora::findOrFail($registro)]))->name('horas.edit');

    // Notas Fiscais
    Route::get('notas-fiscais', fn () => view('pages.notas-fiscais.index'))->name('notas-fiscais.index');
    Route::get('notas-fiscais/criar', fn () => view('pages.notas-fiscais.create'))->name('notas-fiscais.create');
    Route::get('notas-fiscais/{nota}', fn ($nota) => view('pages.notas-fiscais.show', ['nota' => \App\Models\NotaFiscal::findOrFail($nota)]))->name('notas-fiscais.show');
});

require __DIR__.'/settings.php';
