@extends('layouts.main')

@section('title', 'Leitura de Cartão-Resposta')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Leitura via Webcam</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Camera Feed --}}
        <div class="bg-white rounded-lg shadow p-4">
            <div class="relative">
                <video id="video" autoplay playsinline muted
                    class="w-full rounded border border-gray-200 bg-black"
                    style="max-height:360px;object-fit:cover"></video>
                <canvas id="canvas" style="display:none"></canvas>

                <div id="qr-overlay" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div id="qr-box" class="border-4 border-yellow-400 opacity-60 rounded"
                        style="width:180px;height:180px"></div>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button id="btnStart" onclick="startCamera()"
                    class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">
                    Iniciar Câmera
                </button>
                <button id="btnCapture" onclick="captureFrame()" disabled
                    class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 disabled:opacity-40">
                    Capturar
                </button>
                <span id="status-text" class="text-xs text-gray-500">Aguardando câmera...</span>
            </div>

            {{-- QR info --}}
            <div id="qr-info" class="mt-3 hidden bg-indigo-50 rounded p-3 text-sm">
                <p class="font-medium text-indigo-800" id="qr-aluno"></p>
                <p class="text-indigo-600" id="qr-prova"></p>
            </div>
        </div>

        {{-- Result Panel --}}
        <div class="bg-white rounded-lg shadow p-4">
            <h2 class="font-semibold text-gray-700 mb-3">Respostas Detectadas</h2>

            <div id="respostas-grid" class="grid grid-cols-10 gap-1 text-xs"></div>

            <div id="resultado-box" class="hidden mt-4 bg-green-50 rounded p-4 text-center">
                <p class="text-gray-500 text-sm">Nota Final</p>
                <p id="nota-final" class="text-4xl font-bold text-green-700 my-1"></p>
                <p id="acertos-info" class="text-gray-600 text-sm"></p>
                <p id="percentual-info" class="text-gray-500 text-xs mt-1"></p>
            </div>

            <div id="error-box" class="hidden mt-4 bg-red-50 rounded p-4 text-sm text-red-700"></div>

            <div class="mt-4 flex gap-2">
                <button id="btnEnviar" onclick="enviarLeitura()" disabled
                    class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700 disabled:opacity-40">
                    Enviar Leitura
                </button>
                <button onclick="resetar()"
                    class="border px-4 py-2 rounded text-sm text-gray-600 hover:bg-gray-50">
                    Resetar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let stream = null;
let capturedImageB64 = null;
let qrData = null;
let respostasDetectadas = [];
let scanInterval = null;

const video    = document.getElementById('video');
const canvas   = document.getElementById('canvas');
const ctx      = canvas.getContext('2d');

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
        });
        video.srcObject = stream;
        document.getElementById('btnCapture').disabled = false;
        setStatus('Câmera ativa — posicione o cartão');
        scanInterval = setInterval(scanQR, 300);
    } catch (e) {
        setStatus('Erro ao acessar câmera: ' + e.message, true);
    }
}

function scanQR() {
    if (!video.readyState || video.readyState < 2) return;
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
    if (code && code.data !== qrData) {
        qrData = code.data;
        setStatus('QR detectado: ' + qrData);
        document.getElementById('qr-box').classList.replace('border-yellow-400', 'border-green-400');
        fetchQrInfo(qrData);
    }
}

async function fetchQrInfo(qr) {
    try {
        const res = await fetch('/api/leituras/qr-info', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ qr_data: qr })
        });
        if (!res.ok) throw new Error('Cartão não encontrado no sistema.');
        const data = await res.json();
        document.getElementById('qr-aluno').textContent = (data.nome_aluno || data.codigo_aluno);
        document.getElementById('qr-prova').textContent = data.prova.titulo + ' — ' + data.prova.total_questoes + ' questões';
        document.getElementById('qr-info').classList.remove('hidden');
        window._totalQuestoes = data.prova.total_questoes;
    } catch (e) {
        showError(e.message);
    }
}

function captureFrame() {
    if (!stream) return;
    clearInterval(scanInterval);

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    capturedImageB64 = canvas.toDataURL('image/jpeg', 0.85);

    setStatus('Imagem capturada. Lendo respostas...');
    respostasDetectadas = lerRespostasOMR(canvas);
    renderRespostas(respostasDetectadas);
    document.getElementById('btnEnviar').disabled = !qrData;
    setStatus('Pronto — verifique as respostas e clique em Enviar.');
}

function lerRespostasOMR(c) {
    // Layout: QR code no canto superior esquerdo; grade a partir de ~200px do topo
    // 2 colunas de 15 questões cada, alternativas A-E horizontalmente
    // Valores aproximados para cartão A4 capturado em 1280x720
    const imgData = ctx.getImageData(0, 0, c.width, c.height);
    const W = c.width, H = c.height;
    const respostas = [];

    const cols = [
        { startX: Math.round(W * 0.08), questaoOffset: 0  },
        { startX: Math.round(W * 0.55), questaoOffset: 15 },
    ];

    const startY   = Math.round(H * 0.30);
    const rowH     = Math.round(H * 0.042);
    const cellW    = Math.round(W * 0.072);
    const LETRAS   = ['A','B','C','D','E'];
    const THRESHOLD = 30;
    const CSIZE    = 14;

    for (const col of cols) {
        for (let row = 0; row < 15; row++) {
            const questaoNum = col.questaoOffset + row + 1;
            const cy = startY + row * rowH + Math.round(rowH / 2);
            let marcados = [];

            for (let alt = 0; alt < 5; alt++) {
                const cx = col.startX + alt * cellW + Math.round(cellW / 2);
                let dark = 0;
                for (let dy = -CSIZE/2; dy < CSIZE/2; dy++) {
                    for (let dx = -CSIZE/2; dx < CSIZE/2; dx++) {
                        const px = Math.round(cx+dx), py = Math.round(cy+dy);
                        if (px < 0 || py < 0 || px >= W || py >= H) continue;
                        const idx = (py * W + px) * 4;
                        const r = imgData.data[idx], g = imgData.data[idx+1], b = imgData.data[idx+2];
                        if (r < 100 && g < 100 && b < 100) dark++;
                    }
                }
                if (dark > THRESHOLD) marcados.push(LETRAS[alt]);
            }

            respostas.push({
                questao_numero: questaoNum,
                marcacao:       marcados.length === 1 ? marcados[0] : null,
                dupla_marcacao: marcados.length > 1,
                em_branco:      marcados.length === 0,
                confianca:      marcados.length === 1 ? 0.85 : null,
            });
        }
    }

    return respostas;
}

function renderRespostas(respostas) {
    const grid = document.getElementById('respostas-grid');
    grid.innerHTML = '';
    respostas.forEach(r => {
        const el = document.createElement('div');
        el.className = 'text-center';
        const color = r.dupla_marcacao ? 'bg-red-100 text-red-700'
                    : r.em_branco     ? 'bg-gray-100 text-gray-400'
                    : 'bg-indigo-50 text-indigo-700';
        el.innerHTML = `<div class="text-gray-400 text-xs">${r.questao_numero}</div>
                        <div class="rounded font-bold py-0.5 ${color}">${r.marcacao || (r.dupla_marcacao ? '!!' : '-')}</div>`;
        grid.appendChild(el);
    });
}

async function enviarLeitura() {
    if (!qrData || !capturedImageB64) return;

    document.getElementById('btnEnviar').disabled = true;
    setStatus('Enviando...');
    document.getElementById('error-box').classList.add('hidden');

    try {
        const res = await fetch('/api/leituras', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({
                qr_data:  qrData,
                imagem:   capturedImageB64,
                respostas: respostasDetectadas,
                origem:   'webcam',
            })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erro ao processar leitura.');

        const r = data.resultado;
        document.getElementById('nota-final').textContent  = parseFloat(r.nota_final).toFixed(1);
        document.getElementById('acertos-info').textContent = r.total_acertos + ' acertos de ' + r.total_questoes;
        document.getElementById('percentual-info').textContent = parseFloat(r.percentual_acerto).toFixed(1) + '% de aproveitamento';
        document.getElementById('resultado-box').classList.remove('hidden');
        setStatus('Leitura enviada com sucesso!');
    } catch (e) {
        showError(e.message);
        document.getElementById('btnEnviar').disabled = false;
    }
}

function resetar() {
    qrData = null;
    capturedImageB64 = null;
    respostasDetectadas = [];
    document.getElementById('respostas-grid').innerHTML = '';
    document.getElementById('qr-info').classList.add('hidden');
    document.getElementById('resultado-box').classList.add('hidden');
    document.getElementById('error-box').classList.add('hidden');
    document.getElementById('btnEnviar').disabled = true;
    document.getElementById('qr-box').classList.replace('border-green-400', 'border-yellow-400');
    if (stream) {
        scanInterval = setInterval(scanQR, 300);
    }
    setStatus('Resetado — posicione novo cartão.');
}

function setStatus(msg, error = false) {
    const el = document.getElementById('status-text');
    el.textContent = msg;
    el.className = 'text-xs ' + (error ? 'text-red-500' : 'text-gray-500');
}

function showError(msg) {
    const el = document.getElementById('error-box');
    el.textContent = 'Erro: ' + msg;
    el.classList.remove('hidden');
}
</script>
@endsection
