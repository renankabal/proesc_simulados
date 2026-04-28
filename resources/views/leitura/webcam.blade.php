@extends('layouts.main')

@section('title', 'Leitura de Cartão-Resposta')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Leitura de Cartão-Resposta</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Upload --}}
        <div class="bg-white rounded-lg shadow p-4">
            <div id="upload-area"
                class="relative bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg overflow-hidden flex items-center justify-center cursor-pointer hover:border-green-400 transition-colors"
                style="aspect-ratio:4/3"
                onclick="document.getElementById('fileInput').click()"
                ondragover="event.preventDefault(); this.classList.add('border-green-400','bg-green-50')"
                ondragleave="this.classList.remove('border-green-400','bg-green-50')"
                ondrop="event.preventDefault(); this.classList.remove('border-green-400','bg-green-50'); processarArquivo(event.dataTransfer.files[0])">

                <div id="upload-placeholder" class="text-center p-6 pointer-events-none">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-600 font-medium">Clique ou arraste a foto do cartão</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG ou PDF — até 10MB</p>
                </div>

                <img id="preview-img" class="hidden max-w-full max-h-full object-contain pointer-events-none" alt="Preview">
                <canvas id="canvas" class="hidden"></canvas>
            </div>

            <input type="file" id="fileInput" accept="image/jpeg,image/png,image/*,application/pdf" class="hidden"
                onchange="processarArquivo(this.files[0])">

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button onclick="document.getElementById('fileInput').click()"
                    class="bg-green-600 text-white px-5 py-2 rounded-full text-sm hover:bg-green-700 font-semibold">
                    Selecionar Arquivo
                </button>
                <button onclick="executarLeitura()" id="btnLer" disabled
                    class="bg-green-600 text-white px-5 py-2 rounded-full text-sm hover:bg-green-700 disabled:opacity-40 font-semibold">
                    Ler Cartão
                </button>
                <button onclick="resetar()"
                    class="border px-4 py-2 rounded-full text-sm text-gray-600 hover:bg-gray-50">
                    Resetar
                </button>
                <span id="status-text" class="text-xs text-gray-400">Aguardando imagem...</span>
            </div>

            <div id="qr-info" class="hidden mt-3 bg-green-50 rounded p-3 text-sm">
                <p class="font-semibold text-green-800" id="qr-aluno"></p>
                <p class="text-green-600 text-xs mt-0.5" id="qr-turma"></p>
                <p class="text-green-600 text-xs mt-0.5" id="qr-prova"></p>
            </div>


            <div id="error-box" class="hidden mt-3 bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700"></div>
        </div>

        {{-- Respostas --}}
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

{{-- Modal edição manual --}}
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
            <button onclick="selecionarLetra(null)" class="flex-1 py-2 rounded-full border text-sm text-gray-500 hover:bg-gray-50">Em branco</button>
            <button onclick="fecharModal()" class="flex-1 py-2 rounded-full bg-gray-100 text-sm text-gray-600 hover:bg-gray-200">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.2.67/build/pdf.min.mjs" type="module"></script>
<script type="module">
import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.2.67/build/pdf.min.mjs';
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.2.67/build/pdf.worker.min.mjs';
window._pdfjsLib = pdfjsLib;
</script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let capturedB64 = null, qrData = null, qrLocation = null, fetchQrInfoPromise = null;
let respostas = [], editandoQuestao = null, imagemOriginal = null, omrMarcadores = 0;

const canvas = document.getElementById('canvas');
const ctx    = canvas.getContext('2d');

// ─── Constantes do layout do PDF (dompdf 96 DPI, A4: 794×1122 px) ─────────────
// Tabela de questões com coluna de marcadores (14px) + 12 colunas auto + spacer(10px).
// Cada coluna auto ≈ (754-24)/12 = 60.8 ≈ 61px.
//
// Marcadores negros (10×18 px) na 1ª coluna da tabela:
//   MARKER_X = body_pad(20) + marker_col/2(7) = 27px
//
// Colunas de letras:
//   LCOL_X = body_pad(20) + marker(14) + Nº(61) = 95px
//   RCOL_X = LCOL_X + 5*61 + spacer(10) + Nº(61) = 471px

const PDF_W = 794, PDF_H = 1122;

// Posição e tamanho do QR no PDF (em px de saída)
const QR_SIZE  = 110;
const QR_LEFT  = 28;
const QR_TOP   = 130;

// Coluna de marcadores de linha (blocos negros)
const MARKER_X = 27;   // x do centro da coluna de marcadores no PDF normalizado

// Coordenadas do grid OMR no PDF (em px de saída)
const GRID_LCOL_X = 95;   // x do início das letras (coluna esquerda)
const GRID_RCOL_X = 471;  // x do início das letras (coluna direita)
const GRID_ROW1_Y = 296;  // y do topo da 1ª linha de dados (fallback)
const GRID_ROW_H  = 28;   // altura de cada linha — fallback sem marcadores
const GRID_CELL_W = 61;   // largura de cada coluna de letra (px)
const BUBBLE_R    = 10;   // raio da bolinha (px)

// Marcas de canto (quadrados 14×14px fixos nos cantos da página no canvas normalizado)
// PDF: position:fixed top/bottom:8px left/right:8px → centro em (8+7, 8+7) = (15, 15)
const CANTO_TL = { x: 15,  y: 15   };
const CANTO_TR = { x: 779, y: 15   };  // 794 - 8 - 14 + 7 = 779
const CANTO_BL = { x: 15,  y: 1107 };  // 1122 - 8 - 14 + 7 = 1107
const CANTO_BR = { x: 779, y: 1107 };

// ─── Correção de perspectiva (homografia) ────────────────────────────────────

function solveLinear(A, b) {
    const n = b.length;
    const M = A.map((r, i) => [...r, b[i]]);
    for (let c = 0; c < n; c++) {
        let mx = c;
        for (let r = c + 1; r < n; r++) if (Math.abs(M[r][c]) > Math.abs(M[mx][c])) mx = r;
        [M[c], M[mx]] = [M[mx], M[c]];
        for (let r = c + 1; r < n; r++) {
            if (Math.abs(M[c][c]) < 1e-12) continue;
            const f = M[r][c] / M[c][c];
            for (let j = c; j <= n; j++) M[r][j] -= f * M[c][j];
        }
    }
    const x = new Array(n);
    for (let i = n - 1; i >= 0; i--) {
        x[i] = M[i][n];
        for (let j = i + 1; j < n; j++) x[i] -= M[i][j] * x[j];
        x[i] /= M[i][i];
    }
    return x;
}

// Calcula homografia 3×3 via DLT a partir de 4 correspondências src→dst
function calcHomografia(src, dst) {
    const rows = [], rhs = [];
    for (let i = 0; i < 4; i++) {
        const [sx, sy, dx, dy] = [src[i].x, src[i].y, dst[i].x, dst[i].y];
        rows.push([sx, sy, 1, 0, 0, 0, -dx * sx, -dx * sy]); rhs.push(dx);
        rows.push([0, 0, 0, sx, sy, 1, -dy * sx, -dy * sy]); rhs.push(dy);
    }
    const h = solveLinear(rows, rhs);
    return [[h[0], h[1], h[2]], [h[3], h[4], h[5]], [h[6], h[7], 1]];
}

function invertM3(m) {
    const [[a,b,c],[d,e,f],[g,h,k]] = m;
    const det = a*(e*k-f*h) - b*(d*k-f*g) + c*(d*h-e*g);
    if (Math.abs(det) < 1e-10) return null;
    return [
        [(e*k-f*h)/det, (c*h-b*k)/det, (b*f-c*e)/det],
        [(f*g-d*k)/det, (a*k-c*g)/det, (c*d-a*f)/det],
        [(d*h-e*g)/det, (b*g-a*h)/det, (a*e-b*d)/det],
    ];
}

// Aplica homografia H ao canvas global por mapeamento inverso pixel a pixel
function warpPerspectiva(H) {
    const W = canvas.width, Hh = canvas.height;
    const Hi = invertM3(H);
    if (!Hi) return;
    const src = ctx.getImageData(0, 0, W, Hh);
    const dst = ctx.createImageData(W, Hh);
    const sd = src.data, dd = dst.data;
    for (let y = 0; y < Hh; y++) {
        for (let x = 0; x < W; x++) {
            const w  = Hi[2][0]*x + Hi[2][1]*y + Hi[2][2];
            const sx = Math.round((Hi[0][0]*x + Hi[0][1]*y + Hi[0][2]) / w);
            const sy = Math.round((Hi[1][0]*x + Hi[1][1]*y + Hi[1][2]) / w);
            if (sx >= 0 && sx < W && sy >= 0 && sy < Hh) {
                const si = (sy*W+sx)*4, di = (y*W+x)*4;
                dd[di]=sd[si]; dd[di+1]=sd[si+1]; dd[di+2]=sd[si+2]; dd[di+3]=255;
            }
        }
    }
    ctx.putImageData(dst, 0, 0);
}

// Encontra centróide de pixels escuros num raio ao redor de (cx, cy) na imagem cinza
function detectarCanto(gray, W, H, cx, cy, raio = 32) {
    let sx = 0, sy = 0, cnt = 0;
    for (let dy = -raio; dy <= raio; dy++) {
        for (let dx = -raio; dx <= raio; dx++) {
            const px = Math.round(cx + dx), py = Math.round(cy + dy);
            if (px < 0 || py < 0 || px >= W || py >= H) continue;
            if (gray[py * W + px] < 70) { sx += px; sy += py; cnt++; }
        }
    }
    return cnt > 15 ? { x: Math.round(sx / cnt), y: Math.round(sy / cnt) } : null;
}

// Detecta as 4 marcas de canto; retorna objeto com nulls para as não encontradas
function detectarCantosPagina(gray, W, H) {
    return {
        tl: detectarCanto(gray, W, H, CANTO_TL.x, CANTO_TL.y),
        tr: detectarCanto(gray, W, H, CANTO_TR.x, CANTO_TR.y),
        bl: detectarCanto(gray, W, H, CANTO_BL.x, CANTO_BL.y),
        br: detectarCanto(gray, W, H, CANTO_BR.x, CANTO_BR.y),
    };
}

// Aplica correção de perspectiva; para cantos não detectados usa posição esperada
function corrigirPerspectiva(cantos) {
    const dets = [cantos.tl, cantos.tr, cantos.bl, cantos.br];
    const exps = [CANTO_TL, CANTO_TR, CANTO_BL, CANTO_BR];
    const found = dets.filter(Boolean).length;
    if (found < 2) return 0;  // poucos cantos → não corrige
    const src = dets.map((d, i) => d ?? exps[i]);
    const H = calcHomografia(src, exps);
    warpPerspectiva(H);
    return found;
}

// ─── Upload ───────────────────────────────────────────────────────────────────

function processarArquivo(arquivo) {
    if (!arquivo) return;
    if (arquivo.size > 10 * 1024 * 1024) { showError('Arquivo muito grande. Máximo 10MB.'); return; }

    if (arquivo.type === 'application/pdf' || arquivo.name.toLowerCase().endsWith('.pdf')) {
        renderizarPDF(arquivo).catch(e => showError('Erro ao processar PDF: ' + e.message));
        return;
    }

    if (!arquivo.type.startsWith('image/')) { showError('Selecione JPG, PNG ou PDF.'); return; }

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            _aplicarImagem(img, e.target.result, 'Imagem carregada. Detectando QR Code...');
        };
        img.onerror = () => showError('Não foi possível carregar a imagem.');
        img.src = e.target.result;
    };
    reader.readAsDataURL(arquivo);
}

async function renderizarPDF(arquivo) {
    setStatus('Carregando PDF...');
    const pdfjs = window._pdfjsLib;
    if (!pdfjs) { showError('PDF.js ainda não carregou. Aguarde e tente novamente.'); return; }

    const arrayBuffer = await arquivo.arrayBuffer();
    const pdf  = await pdfjs.getDocument({ data: arrayBuffer }).promise;
    const page = await pdf.getPage(1);

    // Renderiza em escala 2× para boa qualidade de OMR
    const viewport = page.getViewport({ scale: 2.0 });
    const offCanvas = document.createElement('canvas');
    offCanvas.width  = viewport.width;
    offCanvas.height = viewport.height;
    await page.render({ canvasContext: offCanvas.getContext('2d'), viewport }).promise;

    const dataUrl = offCanvas.toDataURL('image/jpeg', 0.92);
    const img = new Image();
    await new Promise((res, rej) => { img.onload = res; img.onerror = rej; img.src = dataUrl; });
    _aplicarImagem(img, dataUrl, 'PDF carregado. Detectando QR Code...');
}

function _aplicarImagem(img, dataUrl, statusMsg) {
    imagemOriginal = img;
    capturedB64    = dataUrl;
    document.getElementById('preview-img').src = dataUrl;
    document.getElementById('preview-img').classList.remove('hidden');
    document.getElementById('upload-placeholder').classList.add('hidden');
    document.getElementById('btnLer').disabled = false;
    document.getElementById('error-box').classList.add('hidden');
    setStatus(statusMsg);
    detectarQRAutomatico();
}

// ─── Detecção de QR: multi-escala + binarização ───────────────────────────────

function detectarQRAutomatico() {
    const qr = tentarDetectarQR(imagemOriginal);
    if (qr) {
        qrData = qr;
        setStatus('QR detectado! Buscando informações do aluno...');
        const p = fetchQrInfo(qrData);
        fetchQrInfoPromise = p;
        // Exibe erro no auto-detect sem suprimir rejeição de fetchQrInfoPromise
        p.catch(e => showError(e.message));
    } else {
        setStatus('QR não detectado — tente uma foto com melhor iluminação ou mais próxima.');
    }
}

function tentarDetectarQR(img) {
    // Tenta em várias resoluções. 800px é o ponto ideal para jsQR.
    for (const maxW of [800, 1200, 500, img.width]) {
        const s = Math.min(1, maxW / img.width);
        const w = Math.round(img.width * s), h = Math.round(img.height * s);
        canvas.width = w; canvas.height = h;
        ctx.drawImage(img, 0, 0, w, h);

        const raw = ctx.getImageData(0, 0, w, h);
        let code = jsQR(raw.data, w, h, { inversionAttempts: 'attemptBoth' });
        if (!code) code = jsQR(binarizar(raw, w, h).data, w, h, { inversionAttempts: 'attemptBoth' });

        if (code) {
            // Escala os cantos de volta para coordenadas da imagem original
            const inv = 1 / s;
            qrLocation = {
                tl: { x: code.location.topLeftCorner.x     * inv, y: code.location.topLeftCorner.y     * inv },
                tr: { x: code.location.topRightCorner.x    * inv, y: code.location.topRightCorner.y    * inv },
                bl: { x: code.location.bottomLeftCorner.x  * inv, y: code.location.bottomLeftCorner.y  * inv },
                br: { x: code.location.bottomRightCorner.x * inv, y: code.location.bottomRightCorner.y * inv },
            };
            return code.data;
        }
    }
    qrLocation = null;
    return null;
}

function binarizar(imgData, W, H) {
    const src = imgData.data, out = new Uint8ClampedArray(src.length);
    let soma = 0;
    for (let i = 0; i < W * H; i++)
        soma += 0.299 * src[i*4] + 0.587 * src[i*4+1] + 0.114 * src[i*4+2];
    const thresh = soma / (W * H);
    for (let i = 0; i < W * H; i++) {
        const l = 0.299 * src[i*4] + 0.587 * src[i*4+1] + 0.114 * src[i*4+2];
        out[i*4] = out[i*4+1] = out[i*4+2] = (l < thresh ? 0 : 255);
        out[i*4+3] = 255;
    }
    return new ImageData(out, W, H);
}

// ─── Normalização usando o QR como âncora ─────────────────────────────────────
// Se o QR foi detectado: estima a posição e tamanho do cartão na foto usando as
// proporções conhecidas do PDF, e normaliza para um canvas de 794×1122 (A4 96 DPI).
// Com a imagem normalizada, as coordenadas do OMR são sempre as mesmas.

function normalizarCartao(img) {
    if (!qrLocation) {
        // Sem QR: usa imagem completa e coordenadas percentuais de fallback
        canvas.width  = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        return false;
    }

    // Tamanho do QR em pixels da imagem original
    const qrW = Math.abs(qrLocation.tr.x - qrLocation.tl.x);
    const qrH = Math.abs(qrLocation.bl.y - qrLocation.tl.y);
    const qrPx = (qrW + qrH) / 2; // média para suavizar distorções

    // Quantos pixels da foto correspondem a 1px do PDF
    const pxPerPdf = qrPx / QR_SIZE;

    // Limites estimados do cartão na foto
    const cardW    = PDF_W * pxPerPdf;
    const cardH    = PDF_H * pxPerPdf;
    const cardLeft = qrLocation.tl.x - QR_LEFT * pxPerPdf;
    const cardTop  = qrLocation.tl.y - QR_TOP  * pxPerPdf;

    // Desenha o cartão normalizado em 794×1122
    canvas.width  = PDF_W;
    canvas.height = PDF_H;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, PDF_W, PDF_H);
    ctx.drawImage(img, cardLeft, cardTop, cardW, cardH, 0, 0, PDF_W, PDF_H);

    return true;
}

// ─── Busca de info do cartão ──────────────────────────────────────────────────

async function fetchQrInfo(qr) {
    console.log('[QR detectado]', qr);
    const res = await fetch('/api/leituras/qr-info', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ qr_data: qr })
    });
    if (!res.ok) {
        const body = await res.text().catch(() => '');
        let msg;
        try { const j = JSON.parse(body); msg = j.error || j.message; } catch (_) {}
        console.error('[QR API erro]', res.status, body);
        throw new Error(msg || `Erro ${res.status}: verifique o console para detalhes.`);
    }
    exibirInfoCartao(await res.json());
}

function exibirInfoCartao(d) {
    document.getElementById('qr-aluno').textContent = d.nome_aluno || d.codigo_aluno;
    document.getElementById('qr-turma').textContent = d.turma ? 'Turma: ' + d.turma : '';
    document.getElementById('qr-prova').textContent = d.prova.titulo + ' — ' + d.prova.total_questoes + ' questões';
    document.getElementById('qr-info').classList.remove('hidden');
    window._totalQuestoes = d.prova.total_questoes;
    setStatus('Aluno identificado. Clique em "Ler Cartão" para processar as respostas.');
}

// ─── Execução da leitura OMR ──────────────────────────────────────────────────

async function executarLeitura() {
    if (!capturedB64) return;

    if (!qrData) {
        const qr = tentarDetectarQR(imagemOriginal);
        if (qr) {
            qrData = qr;
            setStatus('QR detectado! Buscando dados do aluno...');
            fetchQrInfoPromise = fetchQrInfo(qrData);
            // O await logo abaixo vai capturar erros — não precisa de .catch aqui
        } else {
            showError('QR Code não encontrado. Tente uma foto com melhor iluminação ou mais próxima do cartão.');
            return;
        }
    }

    // Aguarda fetchQrInfo terminar antes de prosseguir — evita race condition
    if (fetchQrInfoPromise && !window._totalQuestoes) {
        setStatus('Aguardando dados da prova...');
        try {
            await fetchQrInfoPromise;
        } catch (e) {
            showError(e.message);
            return;
        }
    }

    if (!window._totalQuestoes) {
        return;  // erro já exibido pela chamada anterior de fetchQrInfo
    }

    setStatus('Normalizando imagem e lendo marcações...');
    const normalizado = normalizarCartao(imagemOriginal);
    omrMarcadores = 0;

    // Correção de perspectiva pelas marcas de canto (após normalização por QR)
    let cantosCorrigidos = 0;
    if (normalizado) {
        const W = canvas.width, Hh = canvas.height;
        const imgData = ctx.getImageData(0, 0, W, Hh).data;
        const grayTemp = new Float32Array(W * Hh);
        for (let i = 0; i < W * Hh; i++)
            grayTemp[i] = 0.299 * imgData[i*4] + 0.587 * imgData[i*4+1] + 0.114 * imgData[i*4+2];
        const cantos = detectarCantosPagina(grayTemp, W, Hh);
        cantosCorrigidos = corrigirPerspectiva(cantos);
    }

    respostas = lerRespostasOMR(canvas, normalizado);
    renderRespostas();
    document.getElementById('btnEnviar').disabled = false;

    const total = window._totalQuestoes || 30;
    const half  = Math.ceil(total / 2);
    const msgCantos   = cantosCorrigidos > 0 ? ` | perspectiva corrigida (${cantosCorrigidos}/4 cantos)` : '';
    const msgMarcador = normalizado && omrMarcadores > 0
        ? `${omrMarcadores} de ${half} marcadores detectados — leitura linha a linha ✓${msgCantos}`
        : normalizado
            ? `Marcadores não detectados — usando posições estimadas.${msgCantos}`
            : 'Imagem não normalizada — posições percentuais (menos preciso).';
    setStatus('Leitura concluída. ' + msgMarcador);
}

// ─── Detecção de linhas pelos marcadores negros ───────────────────────────────
// Varre a coluna X=MARKER_X na imagem normalizada procurando retângulos pretos.
// Retorna array com o Y central de cada marcador encontrado (um por linha da grade).

function detectarLinhasPorMarcadores(gray, W, H, nLinhas) {
    const SCAN_R  = 5;   // varre ±5px em torno de MARKER_X
    const THRESH  = 80;  // limiar de escuridão (0-255)
    const MIN_RUN = 8;   // altura mínima do bloco em pixels

    const runs = [];
    let inRun = false, runStart = 0;

    for (let y = 0; y < H; y++) {
        let darkCnt = 0;
        for (let dx = -SCAN_R; dx <= SCAN_R; dx++) {
            const x = Math.min(W - 1, Math.max(0, MARKER_X + dx));
            if (gray[y * W + x] < THRESH) darkCnt++;
        }
        const isDark = darkCnt >= SCAN_R;
        if (isDark && !inRun) { inRun = true; runStart = y; }
        else if (!isDark && inRun) {
            if (y - runStart >= MIN_RUN)
                runs.push(Math.round((runStart + y - 1) / 2));
            inRun = false;
        }
    }
    if (inRun && H - runStart >= MIN_RUN)
        runs.push(Math.round((runStart + H - 1) / 2));

    omrMarcadores = runs.length;

    // Detectou todos: usa diretamente
    if (runs.length >= nLinhas) return runs.slice(0, nLinhas);

    // Detectou ao menos 2: interpola as linhas faltantes pelo espaçamento médio
    if (runs.length >= 2) {
        const spacing = (runs[runs.length - 1] - runs[0]) / (runs.length - 1);
        const base    = runs[0];
        return Array.from({ length: nLinhas }, (_, i) => Math.round(base + i * spacing));
    }

    // Detectou só 1: extrapola usando GRID_ROW_H conhecido
    if (runs.length === 1) {
        return Array.from({ length: nLinhas }, (_, i) => Math.round(runs[0] + i * GRID_ROW_H));
    }

    return null; // nenhum marcador → usa fallback calculado
}

// ─── Algoritmo OMR ────────────────────────────────────────────────────────────
// Quando normalizado=true: usa marcadores para detectar Y exato de cada linha e
// coordenadas absolutas do PDF para X. Quando false: usa percentuais de fallback.

function lerRespostasOMR(c, normalizado) {
    const W = c.width, H = c.height;
    const imgData = ctx.getImageData(0, 0, W, H);

    const gray = new Float32Array(W * H);
    for (let i = 0; i < W * H; i++) {
        const d = imgData.data;
        gray[i] = 0.299 * d[i*4] + 0.587 * d[i*4+1] + 0.114 * d[i*4+2];
    }

    // Total de questões vem do QR info; fallback 30 se ainda não carregado
    const totalQ   = window._totalQuestoes || 30;
    const halfRows = Math.ceil(totalQ / 2);

    let cols, cellW, R_BUBBLE, FILL_PCT;
    let rowYsFn; // função (row) → cy

    if (normalizado) {
        cols     = [
            { startX: GRID_LCOL_X, offset: 0,         rows: halfRows },
            { startX: GRID_RCOL_X, offset: halfRows,   rows: totalQ - halfRows },
        ];
        cellW    = GRID_CELL_W;
        R_BUBBLE = BUBBLE_R;
        FILL_PCT = 0.15;

        // Tenta localizar marcadores para Y preciso; fallback calculado
        const detected = detectarLinhasPorMarcadores(gray, W, H, halfRows);
        rowYsFn = detected
            ? (row) => detected[row]
            : (row) => GRID_ROW1_Y + row * GRID_ROW_H + Math.round(GRID_ROW_H / 2);
    } else {
        cols     = [
            { startX: Math.round(W * 0.08), offset: 0,         rows: halfRows },
            { startX: Math.round(W * 0.55), offset: halfRows,   rows: totalQ - halfRows },
        ];
        cellW    = Math.round(W * 0.072);
        R_BUBBLE = Math.round(Math.min(W, H) * 0.011);
        FILL_PCT = 0.22;
        const rowH0 = Math.round(H * 0.042);
        const startY0 = Math.round(H * 0.30);
        rowYsFn = (row) => startY0 + row * rowH0 + Math.round(rowH0 / 2);
    }

    const LETRAS = ['A','B','C','D','E'];
    const result = [];

    for (const col of cols) {
        for (let row = 0; row < col.rows; row++) {
            const cy  = rowYsFn(row);
            const num = col.offset + row + 1;
            const marcados = [], confiancas = [];

            for (let alt = 0; alt < 5; alt++) {
                const cx   = col.startX + alt * cellW + Math.round(cellW / 2);
                const R_BG = R_BUBBLE * 2;

                // Threshold local: média do anel externo
                let sumBg = 0, cntBg = 0;
                for (let dy = -R_BG; dy <= R_BG; dy++) {
                    for (let dx = -R_BG; dx <= R_BG; dx++) {
                        const dist = Math.sqrt(dx*dx + dy*dy);
                        if (dist < R_BUBBLE || dist > R_BG) continue;
                        const px = Math.round(cx+dx), py = Math.round(cy+dy);
                        if (px < 0 || py < 0 || px >= W || py >= H) continue;
                        sumBg += gray[py * W + px]; cntBg++;
                    }
                }
                const thresh = (cntBg > 0 ? sumBg / cntBg : 220) * 0.65;

                // Pixels escuros dentro da bolinha
                let dark = 0, total = 0;
                for (let dy = -R_BUBBLE; dy <= R_BUBBLE; dy++) {
                    for (let dx = -R_BUBBLE; dx <= R_BUBBLE; dx++) {
                        if (dx*dx + dy*dy > R_BUBBLE*R_BUBBLE) continue;
                        const px = Math.round(cx+dx), py = Math.round(cy+dy);
                        if (px < 0 || py < 0 || px >= W || py >= H) continue;
                        if (gray[py * W + px] < thresh) dark++;
                        total++;
                    }
                }

                const pct = total > 0 ? dark / total : 0;
                if (pct >= FILL_PCT) { marcados.push(LETRAS[alt]); confiancas.push(Math.min(1, pct / 0.70)); }
            }

            result.push({
                questao_numero:   num,
                marcacao:         marcados.length === 1 ? marcados[0] : null,
                dupla_marcacao:   marcados.length > 1,
                em_branco:        marcados.length === 0,
                confianca:        confiancas.length === 1 ? confiancas[0] : null,
                corrigida_manual: false,
            });
        }
    }
    return result;
}

// ─── Grid de respostas ────────────────────────────────────────────────────────

function renderRespostas() {
    const grid = document.getElementById('respostas-grid');
    grid.innerHTML = '';
    respostas.forEach(r => {
        const cor = r.dupla_marcacao   ? 'bg-yellow-100 border-yellow-400 text-yellow-800'
                  : r.em_branco        ? 'bg-gray-100 border-gray-300 text-gray-400'
                  : r.corrigida_manual ? 'bg-blue-100 border-blue-400 text-blue-800'
                  : 'bg-green-50 border-green-200 text-green-800';
        const div = document.createElement('div');
        div.className = `border rounded p-1 text-center cursor-pointer hover:opacity-80 transition ${cor}`;
        div.innerHTML = `<p class="text-gray-400 text-xs">${r.questao_numero}</p>
                         <p class="font-bold text-sm">${r.marcacao || (r.dupla_marcacao ? '!!' : '—')}</p>`;
        div.addEventListener('click', () => abrirModal(r.questao_numero));
        grid.appendChild(div);
    });
}

// ─── Modal ────────────────────────────────────────────────────────────────────

function abrirModal(num) {
    editandoQuestao = num;
    document.getElementById('modal-q-num').textContent = num;
    const atual = respostas.find(r => r.questao_numero === num)?.marcacao;
    document.querySelectorAll('.letra-btn').forEach(btn => {
        btn.classList.toggle('border-green-600', btn.dataset.letra === atual);
        btn.classList.toggle('bg-green-100',    btn.dataset.letra === atual);
    });
    document.getElementById('modal-edicao').classList.remove('hidden');
}
function fecharModal() { document.getElementById('modal-edicao').classList.add('hidden'); editandoQuestao = null; }
function selecionarLetra(letra) {
    if (!editandoQuestao) return;
    const r = respostas.find(r => r.questao_numero === editandoQuestao);
    if (r) { r.marcacao = letra; r.em_branco = letra === null; r.dupla_marcacao = false; r.corrigida_manual = true; }
    fecharModal(); renderRespostas();
}

// ─── Envio ────────────────────────────────────────────────────────────────────

async function enviarLeitura() {
    if (!qrData || !capturedB64) return;
    document.getElementById('btnEnviar').disabled = true;
    document.getElementById('error-box').classList.add('hidden');
    setStatus('Enviando...');
    try {
        const res  = await fetch('/api/leituras', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ qr_data: qrData, imagem: capturedB64, respostas, origem: 'upload' })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erro ao processar.');
        const r = data.resultado;
        document.getElementById('nota-final').textContent     = parseFloat(r.nota_final).toFixed(1);
        document.getElementById('acertos-info').textContent   = r.total_acertos + ' acertos de ' + r.total_questoes;
        document.getElementById('percentual-info').textContent = parseFloat(r.percentual_acerto).toFixed(1) + '% de aproveitamento';
        if (data.resultado_url) document.getElementById('link-resultado').href = data.resultado_url;
        document.getElementById('resultado-box').classList.remove('hidden');
        setStatus('Leitura confirmada com sucesso!');
    } catch(e) {
        showError(e.message);
        document.getElementById('btnEnviar').disabled = false;
    }
}

// ─── Reset / utils ────────────────────────────────────────────────────────────

function resetar() {
    qrData = null; capturedB64 = null; respostas = []; imagemOriginal = null; qrLocation = null; omrMarcadores = 0;
    fetchQrInfoPromise = null; window._totalQuestoes = null;
    document.getElementById('respostas-grid').innerHTML = '';
    ['qr-info','resultado-box','error-box'].forEach(id => document.getElementById(id).classList.add('hidden'));
    document.getElementById('btnEnviar').disabled = true;
    document.getElementById('btnLer').disabled    = true;
    document.getElementById('preview-img').classList.add('hidden');
    document.getElementById('preview-img').src    = '';
    document.getElementById('upload-placeholder').classList.remove('hidden');
    document.getElementById('fileInput').value    = '';
    setStatus('Resetado — selecione uma nova imagem.');
}

function setStatus(msg, err = false) {
    const el = document.getElementById('status-text');
    el.textContent = msg;
    el.className   = 'text-xs ' + (err ? 'text-red-500' : 'text-gray-400');
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
