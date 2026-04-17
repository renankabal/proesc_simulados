<?php

namespace App\Http\Controllers\Leitura;

use App\Domain\Leitura\Models\Leitura;
use App\Domain\Resultado\Models\Resultado;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeituraController extends Controller
{
    public function index(Request $request): View
    {
        return view('leitura.webcam');
    }

    public function resultados(Request $request): View
    {
        $query = Resultado::query()
            ->with(['leitura.cartao.prova', 'gabarito'])
            ->orderByDesc('created_at');

        if (!$request->user()->isAdmin()) {
            $query->whereHas('leitura.cartao.prova', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        $resultados = $query->paginate(20);

        return view('resultados.index', compact('resultados'));
    }
}
