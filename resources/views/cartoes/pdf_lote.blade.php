<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 11pt; margin: 0; padding: 0; }
  .pagina { page-break-after: always; padding: 20px; }
  .pagina:last-child { page-break-after: avoid; }
  .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 14px; }
  .header h2 { margin: 0; font-size: 14pt; }
  .header p  { margin: 2px 0; font-size: 10pt; color: #333; }
  .info-row  { display: flex; gap: 20px; margin-bottom: 12px; font-size: 10pt; }
  .info-row span { border-bottom: 1px solid #000; flex: 1; padding-bottom: 2px; }
  .qr { text-align: center; margin: 10px 0 14px; }
  table.grid { border-collapse: collapse; width: 100%; margin-top: 10px; }
  table.grid th { font-size: 9pt; background: #eee; text-align: center; padding: 3px 2px; border: 1px solid #999; }
  table.grid td { text-align: center; padding: 3px 2px; border: 1px solid #ccc; font-size: 10pt; }
  .circle { display: inline-block; width: 18px; height: 18px; border: 1.5px solid #333; border-radius: 50%; line-height: 18px; font-size: 9pt; }
  .num { font-size: 9pt; font-weight: bold; color: #555; }
  .instructions { font-size: 8pt; color: #555; margin-top: 12px; border-top: 1px solid #ccc; padding-top: 6px; }
</style>
</head>
<body>
@foreach ($cartoes as $cartao)
<div class="pagina">
  <div class="header">
      <h2>{{ $prova->titulo }}</h2>
      <p>{{ $prova->disciplina }} | {{ $prova->turma }} | Data: {{ $prova->data_aplicacao?->format('d/m/Y') ?? '___/___/____' }}</p>
      <p>{{ $prova->total_questoes }} questões &bull; Nota máxima: {{ number_format($prova->nota_maxima, 1) }}</p>
  </div>
  <div class="info-row">
      <span><strong>Aluno:</strong> {{ $cartao->nome_aluno ?? $cartao->codigo_aluno }}</span>
      <span><strong>Código:</strong> {{ $cartao->codigo_aluno }}</span>
      <span><strong>Turma:</strong> {{ $cartao->turma ?? '' }}</span>
  </div>
  <div class="qr">
      {!! QrCode::size(110)->generate($cartao->qr_data) !!}
      <p style="font-size:8pt;color:#777;margin-top:2px">{{ $cartao->qr_data }}</p>
  </div>
  <p style="font-size:9pt;color:#555;margin-bottom:6px">Preencha completamente a bolinha correspondente:</p>
  <table class="grid">
      <thead>
          <tr>
              <th>Nº</th><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th>
              <th>&nbsp;&nbsp;</th>
              <th>Nº</th><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th>
          </tr>
      </thead>
      <tbody>
          @php $half = (int) ceil($prova->total_questoes / 2); @endphp
          @for ($i = 1; $i <= $half; $i++)
          @php $j = $i + $half; @endphp
          <tr>
              <td class="num">{{ $i }}</td>
              @foreach (['A','B','C','D','E'] as $l)<td><span class="circle">{{ $l }}</span></td>@endforeach
              <td></td>
              @if ($j <= $prova->total_questoes)
              <td class="num">{{ $j }}</td>
              @foreach (['A','B','C','D','E'] as $l)<td><span class="circle">{{ $l }}</span></td>@endforeach
              @else<td colspan="6"></td>@endif
          </tr>
          @endfor
      </tbody>
  </table>
  <p class="instructions">Não rasgue, dobre ou amasse este cartão &bull; Use caneta azul ou preta &bull; Tentativa {{ $cartao->tentativa }}</p>
</div>
@endforeach
</body>
</html>
