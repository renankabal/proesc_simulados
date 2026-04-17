<?php

use App\Http\Controllers\Cartao\CartaoRespostaController;
use App\Http\Controllers\Leitura\LeituraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Prova\GabaritoController;
use App\Http\Controllers\Prova\ProvaController;
use App\Http\Controllers\Prova\QuestaoController;
use App\Http\Controllers\Resultado\ResultadoController;
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

    // Questões (nested under provas)
    Route::prefix('provas/{prova}/questoes')->name('questoes.')->group(function () {
        Route::post('/',          [QuestaoController::class, 'store'])->name('store');
        Route::post('/bulk',      [QuestaoController::class, 'bulkStore'])->name('bulk');
        Route::delete('{questao}',[QuestaoController::class, 'destroy'])->name('destroy');
    });

    // Gabaritos (nested under provas)
    Route::prefix('provas/{prova}')->name('provas.')->group(function () {
        Route::get('gabarito',  [GabaritoController::class, 'edit'])->name('gabarito.edit');
        Route::put('gabarito',  [GabaritoController::class, 'update'])->name('gabarito.update');
        Route::get('relatorio', [ProvaController::class, 'relatorio'])->name('relatorio');
    });

    // Cartões-Resposta
    Route::prefix('provas/{prova}/cartoes')->name('cartoes.')->group(function () {
        Route::get('/',               [CartaoRespostaController::class, 'index'])->name('index');
        Route::post('/',              [CartaoRespostaController::class, 'store'])->name('store');
        Route::post('/lote',          [CartaoRespostaController::class, 'storeLote'])->name('lote');
        Route::get('{cartao}/pdf',           [CartaoRespostaController::class, 'pdf'])->name('pdf');
        Route::get('lote/{filename}',        [CartaoRespostaController::class, 'downloadLote'])->name('downloadLote')
            ->where('filename', '.*');
    });

    // Leitura e Resultados
    Route::get('leitura', [LeituraController::class, 'index'])->name('leitura.index')
        ->middleware('role:admin,professor,operador');

    Route::get('resultados',        [LeituraController::class, 'resultados'])->name('resultados.index');
    Route::get('resultados/export', [LeituraController::class, 'exportCsv'])->name('resultados.export');
    Route::get('resultados/{resultado}', [ResultadoController::class, 'show'])->name('resultados.show');
});

require __DIR__.'/auth.php';
