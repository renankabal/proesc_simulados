<?php

use App\Http\Controllers\Cartao\CartaoRespostaController;
use App\Http\Controllers\Leitura\LeituraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Prova\GabaritoController;
use App\Http\Controllers\Prova\ProvaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('provas.index'));

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Breeze profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Provas
    Route::resource('provas', ProvaController::class);

    // Gabaritos (nested under provas)
    Route::prefix('provas/{prova}')->name('provas.')->group(function () {
        Route::get('gabarito', [GabaritoController::class, 'edit'])->name('gabarito.edit');
        Route::put('gabarito', [GabaritoController::class, 'update'])->name('gabarito.update');
    });

    // Cartões-Resposta
    Route::prefix('provas/{prova}/cartoes')->name('cartoes.')->group(function () {
        Route::get('/', [CartaoRespostaController::class, 'index'])->name('index');
        Route::post('/', [CartaoRespostaController::class, 'store'])->name('store');
        Route::get('{cartao}/pdf', [CartaoRespostaController::class, 'pdf'])->name('pdf');
    });

    // Leitura e Resultados
    Route::get('leitura', [LeituraController::class, 'index'])->name('leitura.index')
        ->middleware('role:admin,professor,operador');
    Route::get('resultados', [LeituraController::class, 'resultados'])->name('resultados.index');
    Route::get('resultados/export', [LeituraController::class, 'exportCsv'])->name('resultados.export');
});

require __DIR__.'/auth.php';
