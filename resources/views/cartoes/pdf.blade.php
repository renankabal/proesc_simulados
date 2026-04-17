<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 11pt; margin: 0; padding: 20px; color: #000; }

  /* Cabeçalho da prova */
  .header { text-align: center; border-bottom: 2px solid #1a7a3c; padding-bottom: 10px; margin-bottom: 14px; }
  .header h2 { margin: 0; font-size: 14pt; color: #1a7a3c; }
  .header p  { margin: 2px 0; font-size: 10pt; color: #444; }

  /* Bloco de identificação: QR à esquerda + dados do aluno à direita */
  .identificacao {
    display: table;
    width: 100%;
    margin-bottom: 14px;
    border: 1.5px solid #1a7a3c;
    border-radius: 6px;
    overflow: hidden;
  }
  .identificacao .qr-col {
    display: table-cell;
    width: 130px;
    vertical-align: middle;
    text-align: center;
    padding: 10px 8px;
    background: #f0faf4;
    border-right: 1px solid #c3e6cf;
  }
  .identificacao .qr-col img,
  .identificacao .qr-col svg { width: 110px; height: 110px; }
  .identificacao .qr-col p {
    font-size: 7pt;
    color: #777;
    margin: 3px 0 0;
    word-break: break-all;
    max-width: 120px;
  }
  .identificacao .dados-col {
    display: table-cell;
    vertical-align: middle;
    padding: 10px 14px;
  }
  .identificacao .dados-col .label {
    font-size: 7.5pt;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1px;
  }
  .identificacao .dados-col .valor {
    font-size: 11pt;
    font-weight: bold;
    color: #111;
    border-bottom: 1px solid #ccc;
    padding-bottom: 2px;
    margin-bottom: 8px;
  }
  .identificacao .dados-col .valor.destaque {
    font-size: 13pt;
    color: #1a7a3c;
  }
  .identificacao .dados-col .grid2 { display: table; width: 100%; }
  .identificacao .dados-col .grid2 .cell { display: table-cell; width: 50%; padding-right: 8px; }

  /* Grade de questões */
  table.grid { border-collapse: collapse; width: 100%; margin-top: 8px; }
  table.grid th { font-size: 9pt; background: #e8f5ec; text-align: center; padding: 3px 2px; border: 1px solid #6dba8a; color: #1a7a3c; }
  table.grid td { text-align: center; padding: 4px 2px; border: 1px solid #ccc; font-size: 10pt; }
  .circle { display: inline-block; width: 20px; height: 20px; border: 1.5px solid #333; border-radius: 50%; line-height: 20px; font-size: 9pt; cursor: default; }
  .num { font-size: 9pt; font-weight: bold; color: #555; }

  /* Rodapé */
  .instructions { font-size: 8pt; color: #666; margin-top: 10px; border-top: 1px solid #c3e6cf; padding-top: 6px; }
  .instructions strong { color: #1a7a3c; }

  p.instrucao-fill { font-size: 9pt; color: #555; margin: 8px 0 4px; }
</style>
</head>
<body>

{{-- Cabeçalho --}}
<div class="header">
    <h2>{{ $prova->titulo }}</h2>
    <p>{{ $prova->disciplina }}@if($prova->turma) &bull; Turma: {{ $prova->turma }}@endif @if($prova->data_aplicacao) &bull; Data: {{ $prova->data_aplicacao->format('d/m/Y') }}@endif</p>
    <p>{{ $prova->total_questoes }} questões &bull; Nota máxima: {{ number_format($prova->nota_maxima, 1) }}</p>
</div>

{{-- Bloco de identificação com QR --}}
<div class="identificacao">
    <div class="qr-col">
        {!! QrCode::size(110)->generate($cartao->qr_data) !!}
        <p>Leia com a câmera para identificar o aluno</p>
    </div>
    <div class="dados-col">
        <div class="label">Aluno</div>
        <div class="valor destaque">{{ $cartao->nome_aluno ?? $cartao->codigo_aluno }}</div>

        <div class="grid2">
            <div class="cell">
                <div class="label">Código</div>
                <div class="valor">{{ $cartao->codigo_aluno }}</div>
            </div>
            <div class="cell">
                <div class="label">Turma</div>
                <div class="valor">{{ $cartao->turma ?? '—' }}</div>
            </div>
        </div>

        <div class="grid2">
            <div class="cell">
                <div class="label">Tentativa</div>
                <div class="valor">{{ $cartao->tentativa }}</div>
            </div>
            <div class="cell">
                <div class="label">Cartão gerado em</div>
                <div class="valor" style="font-size:9pt">{{ $cartao->created_at?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Instruções de preenchimento --}}
<p class="instrucao-fill">
    Preencha <strong>completamente</strong> a bolinha da sua resposta com caneta azul ou preta:
</p>

{{-- Grade de questões --}}
<table class="grid">
    <thead>
        <tr>
            <th>Nº</th>
            <th>A</th><th>B</th><th>C</th><th>D</th><th>E</th>
            <th style="width:10px">&nbsp;</th>
            <th>Nº</th>
            <th>A</th><th>B</th><th>C</th><th>D</th><th>E</th>
        </tr>
    </thead>
    <tbody>
        @php $half = (int) ceil($prova->total_questoes / 2); @endphp
        @for ($i = 1; $i <= $half; $i++)
        @php $j = $i + $half; @endphp
        <tr>
            <td class="num">{{ $i }}</td>
            @foreach (['A','B','C','D','E'] as $l)
            <td><span class="circle">{{ $l }}</span></td>
            @endforeach
            <td></td>
            @if ($j <= $prova->total_questoes)
            <td class="num">{{ $j }}</td>
            @foreach (['A','B','C','D','E'] as $l)
            <td><span class="circle">{{ $l }}</span></td>
            @endforeach
            @else
            <td colspan="6"></td>
            @endif
        </tr>
        @endfor
    </tbody>
</table>

<p class="instructions">
    <strong>Atenção:</strong> Não rasgue, dobre ou amasse este cartão &bull;
    Use somente caneta azul ou preta &bull;
    Preencha os círculos completamente &bull;
    Em caso de erro, risque o círculo errado e preencha o correto &bull;
    Tentativa {{ $cartao->tentativa }}
</p>

</body>
</html>
