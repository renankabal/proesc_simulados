@extends('layouts.main')

@section('title', 'Leitura de Cartão-Resposta')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Leitura via Webcam</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Camera --}}
        <div class="bg-white rounded-lg shadow p-4">
            <div class="relative bg-black rounded overflow-hidden" style="aspect-ratio:4/3">
                <video id="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <canvas id="canvas" class="hidden"></canvas>
                <div id="qr-border" class="absolute inset-0 border-4 border-yellow-400 pointer-events-none opacity-0 rounded transition-all"></div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button onclick="startCamera()" id="btnStart"
                    class="bg-green-600 text-white px-5 py-2 rounded-full text-sm hover:bg-green-700">
                    Iniciar Câmera
                </button>
                <button onclick="captureFrame()" id="btnCapture" disabled
                    class="bg-green-600 text-white px-5 py-2 rounded-full text-sm hover:bg-green-700 disabled:opacity-40 font-semibold">
                    Capturar
                </button>
                <button onclick="resetar()"
                    class="border px-4 py-2 rounded-full text-sm text-gray-600 hover:bg-gray-50">
                    Resetar
                </button>
                <span id="status-text" class="text-xs text-gray-400">Aguardando câmera...</span>
            </div>

            <div id="qr-info" class="hidden mt-3 bg-green-50 rounded p-3 text-sm">
                <p class="font-semibold text-green-800" id="qr-aluno"></p>
                <p class="text-green-600 text-xs mt-0.5" id="qr-prova"></p>
            </div>

            <div id="error-box" class="hidden mt-3 bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700"></div>
        </div>

        {{-- Respostas + Confirmar --}}
        <div class="bg-white rounded-lg shadow p-4 flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-gray-700">Respostas Detectadas</h2>
                <span class="text-xs text-gray-400">Clique em uma célula para corrigir</span>
            </div>

            <div id="respostas-grid" class="grid grid-cols-5 gap-1 text-xs flex-1 content-start"></div>

            <div id="resultado-box" class="hidden mt-4 bg-green-50 rounded p-4 text-center border border-green-200">
                <p class="text-gray-500 text-sm">Nota Final</p>
                <p id="nota-final" class="text-4xl font-bold text-green-700 my-1"></p>
                <p id="acertos-info" class="text-gray-600 text-sm"></p>
                <p id="percentual-info" class="text-gray-500 text-xs mt-1"></p>
                <a id="link-resultado" href="#" class="text-green-500 text-xs hover:underline mt-2 inline-block">Ver detalhe completo →</a>
            </div>

            <div class="mt-4 flex gap-2 pt-2 border-t">
                <button onclick="enviarLeitura()" id="btnEnviar" disabled
                    class="flex-1 bg-green-600 text-white py-2.5 rounded-full text-sm hover:bg-green-700 disabled:opacity-40 font-semibold">
                    Confirmar Leitura
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de edição manual --}}
<div id="modal-edicao" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-72">
        <h3 class="font-semibold text-gray-800 mb-1">Corrigir Questão <span id="modal-q-num" class="text-green-600"></span></h3>
        <p class="text-xs text-gray-400 mb-4">Escolha a resposta correta ou marque como branco</p>
        <div class="grid grid-cols-5 gap-2 mb-4">
            @foreach (['A','B','C','D','E'] as $l)
            <button onclick="selecionarLetra('{{ $l }}')"
                class="letra-btn py-2 rounded border-2 border-gray-200 font-bold text-gray-700 hover:border-green-500 hover:bg-green-50 transition"
                data-letra="{{ $l }}">{{ $l }}</button>
            @endforeach
        </div>
        <div class="flex gap-2">
            <button onclick="selecionarLetra(null)"
                class="flex-1 py-2 rounded-full border text-sm text-gray-500 hover:bg-gray-50">Em branco</button>
            <button onclick="fecharModal()"
                class="flex-1 py-2 rounded-full bg-gray-100 text-sm text-gray-600 hover:bg-gray-200">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let stream = null, capturedB64 = null, qrData = null, scanInterval = null;
let respostas = [];   // [{questao_numero, marcacao, dupla_marcacao, em_branco, confianca, corrigida_manual}]
let editandoQuestao = null;

const video  = document.getElementById('video');
const canvas = document.getElementById('canvas');
const ctx    = canvas.getContext('2d');

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
        });
        video.srcObject = stream;
        document.getElementById('btnCapture').disabled = false;
        setStatus('Câmera ativa — posicione o cartão na frente da câmera');
        scanInterval = setInterval(scanQR, 250);
    } catch(e) {
        setStatus('Erro: ' + e.message, true);
    }
}

function scanQR() {
    if (!video.readyState || video.readyState < 2) return;
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    const img  = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
    if (code && code.data !== qrData) {
        qrData = code.data;
        document.getElementById('qr-border').classList.remove('opacity-0');
        document.getElementById('qr-border').classList.replace('border-yellow-400', 'border-green-400');
        setStatus('QR detectado: ' + qrData);
        fetchQrInfo(qrData);
    }
}

async function fetchQrInfo(qr) {
    try {
        const res  = await fetch('/api/leituras/qr-info', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ qr_data: qr })
        });
        if (!res.ok) throw new Error((await res.json()).error || 'Cartão não encontrado.');
        const d = await res.json();
        document.getElementById('qr-aluno').textContent = d.nome_aluno || d.codigo_aluno;
        document.getElementById('qr-prova').textContent = d.prova.titulo + ' — ' + d.prova.total_questoes + ' questões';
        document.getElementById('qr-info').classList.remove('hidden');
        window._totalQuestoes = d.prova.total_questoes;
    } catch(e) { showError(e.message); }
}

function captureFrame() {
    if (!stream) return;
    clearInterval(scanInterval);
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    capturedB64 = canvas.toDataURL('image/jpeg', 0.85);
    respostas   = lerRespostasOMR(canvas);
    renderRespostas();
    document.getElementById('btnEnviar').disabled = !qrData;
    setStatus('Imagem capturada. Revise as respostas e clique em Confirmar Leitura.');
}

function lerRespostasOMR(c) {
    const W = c.width, H = c.height;
    const imgData = ctx.getImageData(0, 0, W, H);
    const result  = [];
    const LETRAS  = ['A','B','C','D','E'];
    const CSIZE   = 14, THRESH = 30;
    const cols    = [
        { startX: Math.round(W * 0.08), offset: 0 },
        { startX: Math.round(W * 0.55), offset: 15 },
    ];
    const startY = Math.round(H * 0.30);
    const rowH   = Math.round(H * 0.042);
    const cellW  = Math.round(W * 0.072);

    for (const col of cols) {
        for (let row = 0; row < 15; row++) {
            const cy  = startY + row * rowH + Math.round(rowH / 2);
            const num = col.offset + row + 1;
            const marcados = [];
            for (let alt = 0; alt < 5; alt++) {
                const cx = col.startX + alt * cellW + Math.round(cellW / 2);
                let dark = 0;
                for (let dy = -CSIZE/2; dy < CSIZE/2; dy++) {
                    for (let dx = -CSIZE/2; dx < CSIZE/2; dx++) {
                        const px = Math.round(cx+dx), py = Math.round(cy+dy);
                        if (px < 0 || py < 0 || px >= W || py >= H) continue;
                        const i = (py * W + px) * 4;
                        if (imgData.data[i] < 100 && imgData.data[i+1] < 100 && imgData.data[i+2] < 100) dark++;
                    }
                }
                if (dark > THRESH) marcados.push(LETRAS[alt]);
            }
            result.push({
                questao_numero:  num,
                marcacao:        marcados.length === 1 ? marcados[0] : null,
                dupla_marcacao:  marcados.length > 1,
                em_branco:       marcados.length === 0,
                confianca:       marcados.length === 1 ? 0.85 : null,
                corrigida_manual: false,
            });
        }
    }
    return result;
}

function renderRespostas() {
    const grid = document.getElementById('respostas-grid');
    grid.innerHTML = '';
    respostas.forEach(r => {
        const div  = document.createElement('div');
        const cor  = r.dupla_marcacao ? 'bg-yellow-100 border-yellow-400 text-yellow-800'
                   : r.em_branco     ? 'bg-gray-100 border-gray-300 text-gray-400'
                   : r.corrigida_manual ? 'bg-blue-100 border-blue-400 text-blue-800'
                   : 'bg-green-50 border-green-200 text-green-800';
        div.className = `border rounded p-1 text-center cursor-pointer hover:opacity-80 transition ${cor}`;
        div.innerHTML = `<p class="text-gray-400 text-xs">${r.questao_numero}</p>
                         <p class="font-bold text-sm">${r.marcacao || (r.dupla_marcacao ? '!!' : '—')}</p>`;
        div.addEventListener('click', () => abrirModal(r.questao_numero));
        grid.appendChild(div);
    });
}

function abrirModal(num) {
    editandoQuestao = num;
    document.getElementById('modal-q-num').textContent = num;
    const atual = respostas.find(r => r.questao_numero === num)?.marcacao;
    document.querySelectorAll('.letra-btn').forEach(btn => {
        btn.classList.toggle('border-green-600', btn.dataset.letra === atual);
        btn.classList.toggle('bg-green-100', btn.dataset.letra === atual);
    });
    document.getElementById('modal-edicao').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modal-edicao').classList.add('hidden');
    editandoQuestao = null;
}

function selecionarLetra(letra) {
    if (!editandoQuestao) return;
    const r = respostas.find(r => r.questao_numero === editandoQuestao);
    if (r) {
        r.marcacao         = letra;
        r.em_branco        = letra === null;
        r.dupla_marcacao   = false;
        r.corrigida_manual = true;
    }
    fecharModal();
    renderRespostas();
}

async function enviarLeitura() {
    if (!qrData || !capturedB64) return;
    document.getElementById('btnEnviar').disabled = true;
    document.getElementById('error-box').classList.add('hidden');
    setStatus('Enviando...');
    try {
        const res  = await fetch('/api/leituras', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ qr_data: qrData, imagem: capturedB64, respostas, origem: 'webcam' })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erro ao processar.');
        const r = data.resultado;
        document.getElementById('nota-final').textContent  = parseFloat(r.nota_final).toFixed(1);
        document.getElementById('acertos-info').textContent = r.total_acertos + ' acertos de ' + r.total_questoes;
        document.getElementById('percentual-info').textContent = parseFloat(r.percentual_acerto).toFixed(1) + '% de aproveitamento';
        if (data.resultado_url) document.getElementById('link-resultado').href = data.resultado_url;
        document.getElementById('resultado-box').classList.remove('hidden');
        setStatus('Leitura confirmada com sucesso!');
    } catch(e) {
        showError(e.message);
        document.getElementById('btnEnviar').disabled = false;
    }
}

function resetar() {
    qrData = null; capturedB64 = null; respostas = [];
    document.getElementById('respostas-grid').innerHTML = '';
    document.getElementById('qr-info').classList.add('hidden');
    document.getElementById('resultado-box').classList.add('hidden');
    document.getElementById('error-box').classList.add('hidden');
    document.getElementById('btnEnviar').disabled = true;
    document.getElementById('qr-border').classList.add('opacity-0');
    if (stream) scanInterval = setInterval(scanQR, 250);
    setStatus('Resetado — posicione novo cartão.');
}

function setStatus(msg, error = false) {
    const el = document.getElementById('status-text');
    el.textContent = msg;
    el.className = 'text-xs ' + (error ? 'text-red-500' : 'text-gray-400');
}
function showError(msg) {
    const el = document.getElementById('error-box');
    el.textContent = 'Erro: ' + msg;
    el.classList.remove('hidden');
}
document.getElementById('modal-edicao').addEventListener('click', e => {
    if (e.target === document.getElementById('modal-edicao')) fecharModal();
});
</script>
@endsection
