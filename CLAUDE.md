# CLAUDE.md — Sistema de Correção de Provas com Cartão-Resposta

> **Para o agente de IA:** Este arquivo é o guia de implementação central do projeto. Leia-o completamente antes de escrever qualquer linha de código. Toda decisão arquitetural, de modelagem e de implementação deve estar alinhada com este documento. Sempre retorne a este arquivo quando precisar de orientação sobre comportamento esperado, regras de negócio ou prioridades.

---

## 1. Visão Geral do Projeto

### 1.1 Contexto

Este sistema nasceu da necessidade de automatizar a correção de provas objetivas em escolas e instituições de ensino. Hoje esse processo é feito manualmente ou com leitoras ópticas de alto custo. A proposta é criar uma solução acessível, baseada em navegador web, que utiliza a câmera do dispositivo do usuário para ler cartões-resposta impressos e corrigi-los automaticamente contra um gabarito cadastrado.

A inspiração de fluxo e UX vem do sistema disponível em `https://www.sigaf.net.br/hzs/leitor/jsqr/`, que demonstra leitura de cartão-resposta via webcam, detecção de QR Code como âncora de posicionamento e leitura de até 90 questões distribuídas em blocos. **O novo sistema não é uma cópia desse sistema**, mas absorve sua lógica de captura por câmera, uso de QR Code como âncora e organização em blocos de questões.

### 1.2 Objetivo Principal

Desenvolver um sistema web completo que permita:

1. Cadastrar provas com N questões (até 90) e seus gabaritos;
2. Gerar cartões-resposta imprimíveis com QR Code de identificação;
3. Ler o cartão preenchido pelo aluno via webcam no navegador;
4. Processar automaticamente as marcações e compará-las ao gabarito;
5. Calcular acertos, erros, questões em branco e nota final;
6. Armazenar resultados com auditoria completa;
7. Exibir histórico e relatórios por prova, turma ou aluno.

### 1.3 Fluxo Macro de Uso

```
[Admin] Cadastra Prova + Questões + Gabarito
        ↓
[Admin/Professor] Gera Cartões-Resposta (PDF imprimível, com QR Code)
        ↓
[Aluno] Preenche o cartão com caneta
        ↓
[Corretor] Acessa tela de leitura → posiciona cartão frente à câmera
        ↓
[Browser] Detecta QR Code → ancora posição → lê marcações → exibe pré-visualização
        ↓
[Corretor] Revisa e confirma (ou corrige manualmente) a leitura
        ↓
[Sistema] Salva respostas → compara gabarito → calcula resultado → exibe relatório
```

### 1.4 Perfis de Usuário

| Perfil         | Responsabilidades                                                                 |
|----------------|-----------------------------------------------------------------------------------|
| `admin`        | Gerencia usuários, instituições, configurações gerais do sistema                  |
| `professor`    | Cadastra provas, gabaritos, turmas, gera cartões, visualiza relatórios            |
| `corretor`     | Realiza a leitura dos cartões via webcam, confirma ou edita leituras              |
| `visualizador` | Consulta resultados e relatórios, sem permissão de edição                         |

Um usuário pode acumular mais de um perfil. A autorização é baseada em **roles + policies do Laravel**.

---

## 2. Stack Tecnológica

### 2.1 Backend

| Componente           | Tecnologia                        | Justificativa                                                                                        |
|----------------------|-----------------------------------|------------------------------------------------------------------------------------------------------|
| Framework            | **Laravel 13**                    | Framework maduro, excelente suporte a filas, eventos, autorização, ORM e ecossistema robusto         |
| Linguagem            | **PHP 8.4**                       | Compatível com Laravel 13; tipagem forte, Fibers, enums nativos, performance melhorada               |
| Banco de dados       | **PostgreSQL 16+**                | Suporte a JSON/JSONB, arrays, transações robustas, `uuid_generate_v4()`, melhor para dados complexos |
| ORM                  | **Eloquent**                      | Nativo do Laravel, fluente, suporta escopos, casts, relacionamentos polimórficos                     |
| Filas                | **Laravel Queue + Redis**         | Processamento assíncrono de imagens pesadas e recálculo de resultados                                |
| Armazenamento        | **Laravel Storage (S3/local)**    | Armazenar imagens dos cartões capturados, evidências de leitura                                      |
| PDF                  | **barryvdh/laravel-dompdf**       | Geração de cartões-resposta em PDF com layout CSS; simples de integrar com Blade                     |
| QR Code (geração)    | **simplesoftwareio/simple-qrcode**| Integração nativa com Laravel, gera SVG/PNG, usa BaconQrCode internamente                            |

### 2.2 Frontend

**Abordagem escolhida: Blade + Livewire 3 + Alpine.js**

**Justificativa:** O sistema tem telas com estado reativo (leitura em tempo real da câmera, atualização de campos de questão conforme leitura avança) mas não é uma SPA completa. Livewire 3 oferece reatividade server-side com mínimo de JavaScript customizado. Alpine.js complementa com comportamentos client-side leves (toggle, modais, transições). Essa stack mantém o projeto homogêneo em PHP/Blade, sem necessidade de um framework JavaScript separado, o que simplifica manutenção e testes.

A tela de leitura via webcam, por ser intensiva em JavaScript (acesso à câmera, processamento de imagem, canvas), será implementada como um componente JavaScript puro embutido na view Blade, sem Livewire nessa parte crítica — para evitar latência de roundtrips.

### 2.3 Bibliotecas de Leitura via Webcam

| Biblioteca       | Uso                                                   |
|------------------|-------------------------------------------------------|
| **jsQR**         | Detecção e decodificação de QR Code em tempo real via canvas do navegador |
| **WebcamJS** ou `getUserMedia` nativo | Acesso à câmera; preferir `getUserMedia` nativo para evitar dependência desnecessária |
| **Canvas API**   | Processamento de pixels, análise de regiões de bolhas |

### 2.4 Processamento de Imagem

O processamento primário (detecção de QR, leitura de bolhas) ocorre **no navegador** usando Canvas API e jsQR. Isso evita tráfego de imagens grandes para o servidor em tempo real. Apenas o resultado interpretado (JSON com respostas lidas) + a imagem capturada (para auditoria) são enviados ao backend.

O backend pode **reprocessar** imagens armazenadas usando uma fila, caso seja necessário recalibrar a leitura.

---

## 3. Arquitetura Recomendada

### 3.1 Camadas da Aplicação

```
app/
├── Http/                         # Interface HTTP (Controllers, Requests, Middleware)
├── Livewire/                     # Componentes Livewire (telas reativas)
├── Domain/                       # Domínio central do negócio
│   ├── Prova/                    # Agregado Prova
│   ├── Gabarito/                 # Agregado Gabarito
│   ├── Cartao/                   # Agregado Cartão-Resposta
│   ├── Leitura/                  # Agregado Leitura/OMR
│   └── Resultado/                # Agregado Resultado
├── Application/                  # Casos de uso / Actions
│   ├── Actions/
│   └── DTOs/
├── Infrastructure/               # Repositórios, integrações externas
│   ├── Repositories/
│   └── Services/
└── Support/                      # Helpers, Traits, Enums transversais
```

### 3.2 Responsabilidades por Camada

**Domain:** Contém Models Eloquent, Enums de domínio (`StatusLeitura`, `TipoMarcacao`), Value Objects e regras de negócio puras. Não depende de HTTP nem de filas.

**Application (Actions):** Orquestra o domínio. Cada Action executa um caso de uso (ex.: `ProcessarLeituraAction`, `CalcularResultadoAction`). Recebe DTOs, coordena Services/Repositories, despacha Events.

**Infrastructure:** Implementações concretas de repositórios, clientes de APIs externas, serviços de storage. Pode ser substituída sem alterar o domínio.

**Http:** Controllers finos — recebem request, delegam para Actions, retornam response. Nunca contêm lógica de negócio.

### 3.3 Serviços, Actions e Repositories

```php
// Exemplo de Action
class ProcessarLeituraAction
{
    public function __construct(
        private readonly LeituraRepository $leituras,
        private readonly ResultadoCalculator $calculator,
        private readonly LeituraOMRParser $parser,
    ) {}

    public function execute(ProcessarLeituraDTO $dto): Leitura
    {
        $dadosOMR = $this->parser->parse($dto->imagemBase64, $dto->qrData);
        $leitura  = $this->leituras->criar($dadosOMR, $dto);
        $resultado = $this->calculator->calcular($leitura);
        $leitura->resultado()->create($resultado->toArray());
        LeituraProcessada::dispatch($leitura);
        return $leitura;
    }
}
```

### 3.4 Eventos e Filas

| Evento/Job                     | Quando ocorre                                              |
|--------------------------------|------------------------------------------------------------|
| `LeituraProcessada`            | Após salvar leitura confirmada; dispara cálculo de resultado |
| `ResultadoCalculado`           | Após cálculo; notifica professor se configurado            |
| `RecalcularResultadoJob`       | Job para reprocessar resultado quando gabarito muda        |
| `ArmazenarImagemCartaoJob`     | Persiste imagem capturada em S3/disco de forma assíncrona  |

### 3.5 Decisões para Testabilidade

- Actions recebem dependências via construtor (injeção de dependência); fáceis de mockar;
- Repositories abstraem acesso ao banco; testes de integração usam banco real (PostgreSQL);
- Nenhuma lógica de negócio nos Controllers;
- DTOs são classes tipadas (`readonly class`), sem side effects;
- Frontend JS de OMR é separado em módulos testáveis com Jest.

---

## 4. Modelagem de Banco de Dados

> **Regra geral:** Usar `uuid` como PK em todas as tabelas públicas. Usar `bigint` incremental apenas em tabelas de log/auditoria de alta volumetria. Sempre `timestamptz` (com timezone) para datas.

### 4.1 `users`

**Finalidade:** Usuários do sistema com autenticação e perfis de acesso.

```sql
CREATE TABLE users (
    id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          VARCHAR(50) NOT NULL DEFAULT 'professor'
                  CHECK (role IN ('admin','professor','corretor','visualizador')),
    ativo         BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token VARCHAR(100),
    created_at    TIMESTAMPTZ DEFAULT NOW(),
    updated_at    TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
```

### 4.2 `provas`

**Finalidade:** Cadastro de provas. Cada prova tem N questões, pertence a um professor criador.

```sql
CREATE TABLE provas (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    titulo          VARCHAR(255) NOT NULL,
    descricao       TEXT,
    disciplina      VARCHAR(100),
    turma           VARCHAR(100),
    ano_letivo      SMALLINT,
    total_questoes  SMALLINT NOT NULL DEFAULT 10
                    CHECK (total_questoes BETWEEN 1 AND 90),
    status          VARCHAR(30) NOT NULL DEFAULT 'rascunho'
                    CHECK (status IN ('rascunho','publicada','encerrada','arquivada')),
    data_aplicacao  DATE,
    nota_maxima     NUMERIC(5,2) NOT NULL DEFAULT 10.00,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_provas_user ON provas(user_id);
CREATE INDEX idx_provas_status ON provas(status);
CREATE INDEX idx_provas_data ON provas(data_aplicacao);
```

### 4.3 `questoes`

**Finalidade:** Cada questão de uma prova. Pode ser anulada individualmente.

```sql
CREATE TABLE questoes (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    prova_id     UUID NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    numero       SMALLINT NOT NULL CHECK (numero BETWEEN 1 AND 90),
    enunciado    TEXT,
    anulada      BOOLEAN NOT NULL DEFAULT FALSE,
    peso         NUMERIC(4,2) NOT NULL DEFAULT 1.00,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (prova_id, numero)
);
CREATE INDEX idx_questoes_prova ON questoes(prova_id);
```

### 4.4 `alternativas`

**Finalidade:** Alternativas A-E para cada questão (apenas para armazenar texto descritivo, se necessário).

```sql
CREATE TABLE alternativas (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    questao_id  UUID NOT NULL REFERENCES questoes(id) ON DELETE CASCADE,
    letra       CHAR(1) NOT NULL CHECK (letra IN ('A','B','C','D','E')),
    texto       TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (questao_id, letra)
);
CREATE INDEX idx_alternativas_questao ON alternativas(questao_id);
```

### 4.5 `gabaritos`

**Finalidade:** Gabarito oficial de uma prova. Uma prova pode ter múltiplas versões de gabarito (histórico), mas apenas um `ativo`.

```sql
CREATE TABLE gabaritos (
    id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    prova_id     UUID NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    versao       SMALLINT NOT NULL DEFAULT 1,
    ativo        BOOLEAN NOT NULL DEFAULT TRUE,
    criado_por   UUID REFERENCES users(id),
    respostas    JSONB NOT NULL DEFAULT '{}',
    -- Formato: {"1":"A","2":"C","3":"B",...}
    observacoes  TEXT,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW()
);
CREATE UNIQUE INDEX idx_gabaritos_prova_ativo ON gabaritos(prova_id) WHERE ativo = TRUE;
CREATE INDEX idx_gabaritos_prova ON gabaritos(prova_id);
```

**Regra:** Ao ativar um novo gabarito, desativar automaticamente o anterior via trigger ou via Action. O campo `respostas` JSONB permite consultas rápidas sem joins.

### 4.6 `cartoes_resposta`

**Finalidade:** Representa cada cartão impresso e entregue a um aluno. É gerado pelo sistema antes da prova.

```sql
CREATE TABLE cartoes_resposta (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    prova_id        UUID NOT NULL REFERENCES provas(id) ON DELETE RESTRICT,
    codigo_aluno    VARCHAR(100) NOT NULL,
    nome_aluno      VARCHAR(255),
    turma           VARCHAR(100),
    tentativa       SMALLINT NOT NULL DEFAULT 1,
    qr_data         VARCHAR(255) NOT NULL UNIQUE,
    -- Formato: "{id_cartao}" — UUID do próprio cartão como QR data
    pdf_path        VARCHAR(500),
    gerado_em       TIMESTAMPTZ DEFAULT NOW(),
    gerado_por      UUID REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_cartoes_prova ON cartoes_resposta(prova_id);
CREATE INDEX idx_cartoes_codigo_aluno ON cartoes_resposta(codigo_aluno);
CREATE INDEX idx_cartoes_qr ON cartoes_resposta(qr_data);
```

**Nota:** `qr_data` contém o UUID do cartão. Ao escanear o QR Code, o sistema busca o cartão, recupera `prova_id`, `codigo_aluno` e `tentativa`.

### 4.7 `leituras`

**Finalidade:** Registro de cada tentativa de leitura de um cartão. Uma leitura pode ser confirmada, descartada ou reprocessada.

```sql
CREATE TABLE leituras (
    id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cartao_id         UUID NOT NULL REFERENCES cartoes_resposta(id) ON DELETE RESTRICT,
    lido_por          UUID REFERENCES users(id),
    status            VARCHAR(30) NOT NULL DEFAULT 'pendente'
                      CHECK (status IN ('pendente','confirmada','descartada','reprocessando','erro')),
    imagem_path       VARCHAR(500),
    imagem_thumbnail  VARCHAR(500),
    metadados_omr     JSONB,
    -- Contém: angulo_rotacao, confianca_leitura, qr_detectado, warnings[]
    origem            VARCHAR(30) DEFAULT 'webcam'
                      CHECK (origem IN ('webcam','upload','manual')),
    confirmada_em     TIMESTAMPTZ,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_at        TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_leituras_cartao ON leituras(cartao_id);
CREATE INDEX idx_leituras_status ON leituras(status);
CREATE INDEX idx_leituras_lido_por ON leituras(lido_por);
```

### 4.8 `respostas_aluno`

**Finalidade:** Respostas individuais por questão, vinculadas a uma leitura específica.

```sql
CREATE TABLE respostas_aluno (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    leitura_id      UUID NOT NULL REFERENCES leituras(id) ON DELETE CASCADE,
    questao_numero  SMALLINT NOT NULL CHECK (questao_numero BETWEEN 1 AND 90),
    marcacao        CHAR(1) CHECK (marcacao IN ('A','B','C','D','E') OR marcacao IS NULL),
    dupla_marcacao  BOOLEAN NOT NULL DEFAULT FALSE,
    em_branco       BOOLEAN NOT NULL DEFAULT FALSE,
    confianca       NUMERIC(4,3),
    -- 0.0 a 1.0; gerada pelo algoritmo OMR
    corrigida_manual BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (leitura_id, questao_numero)
);
CREATE INDEX idx_respostas_leitura ON respostas_aluno(leitura_id);
```

**Regra:** Exatamente um dos três estados é verdadeiro por linha: `marcacao IS NOT NULL` (resposta válida), `dupla_marcacao = TRUE` (anulada por excesso), ou `em_branco = TRUE` (não marcou).

### 4.9 `resultados`

**Finalidade:** Resultado calculado de uma leitura confirmada. Pode ser recalculado sem perder o histórico.

```sql
CREATE TABLE resultados (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    leitura_id          UUID NOT NULL REFERENCES leituras(id) ON DELETE CASCADE,
    gabarito_id         UUID NOT NULL REFERENCES gabaritos(id) ON DELETE RESTRICT,
    total_questoes      SMALLINT NOT NULL,
    total_acertos       SMALLINT NOT NULL DEFAULT 0,
    total_erros         SMALLINT NOT NULL DEFAULT 0,
    total_brancos       SMALLINT NOT NULL DEFAULT 0,
    total_anuladas      SMALLINT NOT NULL DEFAULT 0,
    nota_bruta          NUMERIC(5,2),
    nota_final          NUMERIC(5,2),
    percentual_acerto   NUMERIC(5,2),
    detalhe_questoes    JSONB,
    -- [{"numero":1,"gabarito":"A","resposta":"B","resultado":"erro"},...]
    calculado_em        TIMESTAMPTZ DEFAULT NOW(),
    recalculado_em      TIMESTAMPTZ,
    versao_calculo      SMALLINT NOT NULL DEFAULT 1,
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);
CREATE UNIQUE INDEX idx_resultados_leitura ON resultados(leitura_id);
CREATE INDEX idx_resultados_gabarito ON resultados(gabarito_id);
```

### 4.10 `logs_processamento`

**Finalidade:** Auditoria completa de todas as operações relevantes do sistema.

```sql
CREATE TABLE logs_processamento (
    id          BIGSERIAL PRIMARY KEY,
    evento      VARCHAR(100) NOT NULL,
    nivel       VARCHAR(20) NOT NULL DEFAULT 'info'
                CHECK (nivel IN ('debug','info','aviso','erro','critico')),
    entidade    VARCHAR(100),
    entidade_id UUID,
    user_id     UUID REFERENCES users(id),
    payload     JSONB,
    ip          INET,
    user_agent  TEXT,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_logs_evento ON logs_processamento(evento);
CREATE INDEX idx_logs_entidade ON logs_processamento(entidade, entidade_id);
CREATE INDEX idx_logs_user ON logs_processamento(user_id);
CREATE INDEX idx_logs_created ON logs_processamento(created_at DESC);
```

---

## 5. Regras de Negócio

### 5.1 Estrutura de Prova e Questões

- Uma prova tem entre 1 e 90 questões;
- Cada questão tem exatamente 5 alternativas: A, B, C, D, E;
- O gabarito define a alternativa correta de cada questão;
- Uma questão pode ser marcada como `anulada` no gabarito: nesse caso, todos os alunos recebem ponto nela independente da resposta;
- O peso de cada questão é configurável (`peso`); a nota padrão é calculada como `(acertos / total_questoes_validas) * nota_maxima`.

### 5.2 Tipos de Marcação

| Estado          | Condição                                            | Pontuação     |
|-----------------|-----------------------------------------------------|---------------|
| `correta`       | Marcação = gabarito, questão não anulada            | +1 ponto      |
| `incorreta`     | Marcação ≠ gabarito, questão não anulada            | 0 pontos      |
| `em_branco`     | Nenhuma bolinha marcada                             | 0 pontos      |
| `dupla_marcacao`| Mais de uma bolinha marcada na mesma questão        | 0 pontos (anula) |
| `anulada`       | Questão marcada como anulada pelo professor         | +1 ponto para todos |

### 5.3 Validação do Cartão via QR Code

- O QR Code contém **apenas o UUID do cartão** (não expõe dados do aluno ou gabarito);
- Ao escanear, o backend valida: cartão existe? prova está ativa? já foi confirmada leitura?
- Se uma leitura `confirmada` já existe para o cartão, o sistema impede duplicata e alerta o corretor;
- Permite reprocessamento explícito mediante justificativa (registrada em `logs_processamento`).

### 5.4 Cálculo de Nota

```
nota_final = (total_acertos + total_anuladas) / total_questoes_nao_nulas * nota_maxima
```

Onde `total_questoes_nao_nulas` exclui questões anuladas do denominador se configurado assim.

- O cálculo usa o gabarito `ativo` no momento da confirmação da leitura;
- Se o gabarito for alterado após confirmação, um `RecalcularResultadoJob` pode ser disparado manualmente pelo admin;
- O resultado anterior é preservado; um novo `resultado` é criado com `versao_calculo` incrementado.

### 5.5 Auditoria

- Toda operação de criar/editar/confirmar/descartar leitura deve registrar entrada em `logs_processamento`;
- Mudanças no gabarito geram log com payload JSONB do estado anterior e novo;
- Recálculo de resultado preserva versão anterior e registra log com motivo.

---

## 6. Geração do Cartão-Resposta

### 6.1 Estratégia de Layout

O cartão é gerado como PDF via `barryvdh/laravel-dompdf`, renderizando uma view Blade com CSS para impressão. O layout deve ser **robusto para leitura por câmera**, o que impõe as seguintes restrições:

- **QR Code no canto superior esquerdo**, com margem de 5mm de borda;
- QR Code mínimo 25mm × 25mm impresso;
- **Marcadores de referência** (quadrados sólidos pretos de 6mm) nos 4 cantos do campo de respostas, para permitir correção de perspectiva no algoritmo OMR;
- Bolinhas de marcação com **diâmetro mínimo de 6mm**, bordas claras sobre fundo branco;
- Espaçamento entre bolinhas mínimo de 2mm;
- Contraste máximo: bolinhas em branco com borda preta forte (`#000`) sobre fundo branco;
- Fonte sans-serif para números de questão, tamanho mínimo 8pt;
- Página A4 portrait ou landscape dependendo do número de questões.

### 6.2 Posicionamento das Bolinhas

```
Cartão com 90 questões → 6 colunas × 15 questões
Cartão com 60 questões → 4 colunas × 15 questões
Cartão com 30 questões → 2 colunas × 15 questões
Cartão com ≤ 15 questões → 1 coluna
```

Cada coluna contém o número da questão e as 5 bolinhas (A B C D E) em linha horizontal.

### 6.3 QR Code e Dados de Identificação

O QR Code contém **somente o UUID do cartão-resposta** (36 caracteres). O nível de correção de erro deve ser `H` (máximo = 30%), para sobreviver a eventuais dobras ou manchas no papel.

Além do QR Code, o cabeçalho do cartão exibe: nome do aluno, matrícula, turma, disciplina e data — impressos em texto legível para conferência manual.

### 6.4 Exportação em PDF

```php
// Controller
public function gerarPDF(CartaoResposta $cartao): Response
{
    $pdf = Pdf::loadView('cartoes.template', ['cartao' => $cartao])
              ->setPaper('a4', 'portrait')
              ->setOption('dpi', 150)
              ->setOption('isHtml5ParserEnabled', true);

    return $pdf->download("cartao_{$cartao->id}.pdf");
}
```

O caminho do PDF gerado é salvo em `cartoes_resposta.pdf_path` para reimpressão.

---

## 7. Leitura via Webcam

### 7.1 Fluxo no Navegador

```
1. Usuário abre a tela de leitura
2. Browser pede permissão de câmera (getUserMedia)
3. Stream de vídeo exibido em <video> em loop
4. requestAnimationFrame() chama tick() ~30x/segundo
5. Cada tick: canvas.drawImage(video) → jsQR() detecta QR
6. QR detectado → desenha bounding box vermelho no canvas
7. Após N frames estáveis com mesmo QR (debounce = 3 frames):
   → Captura frame completo
   → Corrige rotação usando ângulo do QR
   → Recorta região de interesse (ROI) abaixo do QR
   → Analisa pixels para cada bolinha
8. Resultado exibido em pré-visualização lateral
9. Usuário clica "Confirmar" → POST para /api/leituras
```

### 7.2 Detecção do QR Code

Usar **jsQR** (mesma lib do projeto de referência):

```javascript
const code = jsQR(imageData.data, imageData.width, imageData.height, {
    inversionAttempts: 'dontInvert'
});
if (code) {
    const { topLeftCorner, topRightCorner, bottomLeftCorner } = code.location;
    // Calcula ângulo de rotação:
    const dx = topRightCorner.x - topLeftCorner.x;
    const dy = topRightCorner.y - topLeftCorner.y;
    const angulo = Math.atan2(dy, dx);
}
```

### 7.3 Correção de Perspectiva

```javascript
function capturarROI(video, qrLocation, canvasDestino) {
    const ctx = canvasDestino.getContext('2d');
    const { topLeftCorner: tl } = qrLocation;
    // Recorte: a partir do canto superior esquerdo do QR,
    // expande para cobrir toda a área de respostas
    canvasDestino.width  = ROI_WIDTH;   // px configurável
    canvasDestino.height = ROI_HEIGHT;  // px configurável
    ctx.save();
    ctx.rotate(-angulo);
    ctx.drawImage(video,
        tl.x - MARGEM, tl.y - MARGEM,  // sx, sy
        SOURCE_WIDTH, SOURCE_HEIGHT,     // sw, sh
        0, 0,                            // dx, dy
        ROI_WIDTH, ROI_HEIGHT            // dw, dh
    );
    ctx.restore();
}
```

### 7.4 Leitura das Bolinhas (OMR)

Para cada questão e cada alternativa, definir uma **região de 16×16 pixels** na imagem normalizada. Contar pixels escuros (R < 80, G < 80, B < 160) dentro da região. Se a contagem superar o limiar (`> 30` pixels escuros), a bolinha está marcada.

```javascript
function lerBolinha(imageData, x, y, tamanho = 16) {
    let pixelsEscuros = 0;
    for (let dy = 0; dy < tamanho; dy++) {
        for (let dx = 0; dx < tamanho; dx++) {
            const idx = ((y + dy) * imageData.width + (x + dx)) * 4;
            const r = imageData.data[idx];
            const g = imageData.data[idx + 1];
            const b = imageData.data[idx + 2];
            if (r < 80 && g < 80 && b < 160) pixelsEscuros++;
        }
    }
    return { marcada: pixelsEscuros > 30, confianca: pixelsEscuros / (tamanho * tamanho) };
}
```

**Detecção de dupla marcação:** Se mais de uma alternativa da mesma questão retornar `marcada = true`, registrar `dupla_marcacao = true` para aquela questão.

### 7.5 Pré-visualização e Confirmação Manual

- Exibir tabela lateral com questões e alternativas detectadas, colorida por status (verde = marcada, cinza = em branco, vermelho = dupla);
- Permitir que o corretor clique em qualquer célula para alterar manualmente a resposta antes de confirmar;
- Ao alterar manualmente, registrar `corrigida_manual = true` na `respostas_aluno`;
- Botão "Confirmar Leitura" envia o JSON final ao backend; botão "Descartar" cancela sem salvar.

---

## 8. Pipeline de Processamento

### 8.1 Fluxo Técnico Completo

```
[Browser]
  1. Captura frame do vídeo (canvas.drawImage)
  2. Aplica pré-processamento leve: nível de cinza, threshold adaptativo (via canvas)
  3. jsQR detecta QR Code → extrai UUID do cartão e ângulo de rotação
  4. Envia UUID ao backend (GET /api/cartoes/{qrData}) → valida cartão
  5. Recorta e normaliza ROI usando ângulo do QR
  6. Para cada questão/alternativa: executa lerBolinha()
  7. Classifica: válida | dupla | branco
  8. Monta payload: { cartao_id, respostas: [{numero, marcacao, confianca, dupla, branco}], metadados }
  9. Exibe pré-visualização para revisão

[Usuário confirma]
  10. POST /api/leituras com payload + imagem (base64 ou multipart)

[Backend]
  11. LeituraController → LeituraStoreRequest (valida payload)
  12. ProcessarLeituraAction:
      a. Cria registro em `leituras` com status 'pendente'
      b. Armazena imagem via ArmazenarImagemCartaoJob (async)
      c. Cria registros em `respostas_aluno`
      d. Atualiza leitura.status = 'confirmada'
      e. Dispara LeituraProcessada event
  13. CalcularResultadoListener:
      a. Busca gabarito ativo da prova
      b. Para cada questão: compara resposta com gabarito
      c. Soma acertos/erros/brancos/anuladas
      d. Calcula nota_final
      e. Cria registro em `resultados`
      f. Registra em `logs_processamento`
  14. Retorna JSON: { leitura_id, resultado_id, redirect_url }

[Browser]
  15. Redireciona para tela de resultado
```

### 8.2 Validações no Backend

- `cartao_id` existe e pertence ao usuário autenticado (ou à prova à qual ele tem acesso);
- Número de respostas = `prova.total_questoes`;
- Cada `marcacao` é `null` ou uma letra válida (`A-E`);
- Não existe leitura `confirmada` anterior para o mesmo cartão (ou usuário tem permissão de reprocessar);
- Imagem, se enviada, tem tipo MIME `image/jpeg` ou `image/png` e tamanho < 10MB.

---

## 9. Estratégia de Implementação Incremental

### Fase 1 — CRUD de Provas e Gabaritos

**Entregáveis:**
- Autenticação Laravel Breeze/Fortify com roles;
- CRUD de `provas` com Livewire (listar, criar, editar, arquivar);
- CRUD de `questoes` vinculado à prova;
- CRUD de `gabaritos` com validação de completude (todas questões preenchidas);
- Migrations, Models, Factories, Seeders, Feature Tests.

**Critério de aceite:**
- Professor cria uma prova com 30 questões e cadastra o gabarito completo;
- Admin consegue arquivar a prova; professor não consegue excluir prova com gabarito.

### Fase 2 — Geração do Cartão-Resposta

**Entregáveis:**
- Geração de `cartoes_resposta` em lote para uma prova;
- Template Blade do cartão com QR Code, marcadores de referência e bolinhas;
- Exportação em PDF via dompdf;
- Endpoint de download do PDF e reimpressão.

**Critério de aceite:**
- Cartão impresso A4 com QR Code legível por qualquer leitor; bolinhas circulares com contraste adequado; cabeçalho com dados do aluno.

### Fase 3 — Leitura via Webcam

**Entregáveis:**
- Tela de leitura com vídeo ao vivo, detecção de QR Code e pré-visualização;
- Algoritmo OMR no browser (lerBolinha por questão/alternativa);
- Confirmação manual e envio ao backend;
- Criação de `leituras` e `respostas_aluno`;
- TODO: calibração das coordenadas de bolinha por cartão impresso real.

**Critério de aceite:**
- Corretor aponta câmera para o cartão preenchido; sistema detecta QR; exibe respostas em tempo real; confirma e salva no banco.

### Fase 4 — Correção e Relatórios

**Entregáveis:**
- `CalcularResultadoAction` completa;
- Tela de resultado por aluno (acertos, erros, nota, detalhe por questão);
- Relatório por prova: média, mínimo, máximo, histograma de notas;
- Exportação de resultados em CSV;
- Suporte a questões anuladas e recálculo de gabarito.

**Critério de aceite:**
- Professor visualiza relatório de turma com média, distribuição e lista de resultados; exporta CSV.

### Fase 5 — Robustez, UX e Auditoria

**Entregáveis:**
- Reprocessamento de leituras via fila;
- Painel de auditoria (logs de processamento);
- Melhoria do algoritmo OMR com calibração por DPI e distância;
- Suporte a upload de imagem como alternativa à webcam;
- Notificações (e-mail/banco) ao professor quando todas as leituras de uma prova forem concluídas;
- Testes de ponta a ponta com imagens de amostra.

---

## 10. Estrutura de Pastas e Organização do Código

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── ProvaController.php
│   │   ├── GabaritoController.php
│   │   ├── CartaoRespostaController.php
│   │   ├── LeituraController.php
│   │   └── ResultadoController.php
│   ├── Requests/
│   │   ├── Prova/
│   │   │   ├── StoreProvaRequest.php
│   │   │   └── UpdateProvaRequest.php
│   │   ├── Gabarito/
│   │   │   └── StoreGabaritoRequest.php
│   │   └── Leitura/
│   │       └── StoreLeituraRequest.php
│   └── Middleware/
│       └── EnsureRole.php
│
├── Livewire/
│   ├── Prova/
│   │   ├── ProvaList.php
│   │   ├── ProvaForm.php
│   │   └── QuestaoManager.php
│   ├── Gabarito/
│   │   └── GabaritoEditor.php
│   ├── Cartao/
│   │   └── CartaoList.php
│   └── Resultado/
│       ├── ResultadoView.php
│       └── RelatorioProva.php
│
├── Domain/
│   ├── Prova/
│   │   ├── Models/
│   │   │   ├── Prova.php
│   │   │   ├── Questao.php
│   │   │   └── Alternativa.php
│   │   └── Enums/
│   │       └── StatusProva.php
│   ├── Gabarito/
│   │   └── Models/
│   │       └── Gabarito.php
│   ├── Cartao/
│   │   └── Models/
│   │       └── CartaoResposta.php
│   ├── Leitura/
│   │   ├── Models/
│   │   │   ├── Leitura.php
│   │   │   └── RespostaAluno.php
│   │   └── Enums/
│   │       ├── StatusLeitura.php
│   │       └── TipoMarcacao.php
│   └── Resultado/
│       └── Models/
│           └── Resultado.php
│
├── Application/
│   ├── Actions/
│   │   ├── Prova/
│   │   │   └── CreateProvaAction.php
│   │   ├── Gabarito/
│   │   │   ├── AtivarGabaritoAction.php
│   │   │   └── RecalcularResultadosAction.php
│   │   ├── Cartao/
│   │   │   └── GerarCartoesEmLoteAction.php
│   │   └── Leitura/
│   │       ├── ProcessarLeituraAction.php
│   │       └── CalcularResultadoAction.php
│   └── DTOs/
│       ├── StoreLeituraDTO.php
│       ├── RespostaOMRDTO.php
│       └── ResultadoCalculadoDTO.php
│
├── Infrastructure/
│   ├── Repositories/
│   │   ├── LeituraRepository.php
│   │   └── ResultadoRepository.php
│   └── Services/
│       ├── PDFCartaoService.php
│       ├── QRCodeService.php
│       └── ImagemStorageService.php
│
├── Jobs/
│   ├── ArmazenarImagemCartaoJob.php
│   └── RecalcularResultadoJob.php
│
├── Events/
│   ├── LeituraProcessada.php
│   └── ResultadoCalculado.php
│
├── Listeners/
│   ├── CalcularResultadoListener.php
│   └── RegistrarLogProcessamentoListener.php
│
├── Policies/
│   ├── ProvaPolicy.php
│   ├── GabaritoPolicy.php
│   └── LeituraPolicy.php
│
└── Support/
    ├── Enums/
    │   └── RoleUsuario.php
    └── Traits/
        └── HasAuditLog.php

resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php
│   ├── provas/
│   ├── gabaritos/
│   ├── cartoes/
│   │   └── template.blade.php      ← Layout do cartão imprimível
│   ├── leituras/
│   │   └── webcam.blade.php        ← Tela principal de leitura
│   └── resultados/
│
└── js/
    └── omr/
        ├── webcam.js               ← Acesso à câmera
        ├── qrDetector.js           ← jsQR wrapper
        ├── omrReader.js            ← Leitura das bolinhas
        ├── roiExtractor.js         ← Recorte e normalização da imagem
        └── previewRenderer.js      ← Pré-visualização da leitura
```

---

## 11. Rotas e Telas

```php
// routes/web.php

Route::middleware(['auth'])->group(function () {

    Route::get('/', DashboardController::class)->name('dashboard');

    // Provas
    Route::resource('provas', ProvaController::class);
    Route::get('provas/{prova}/questoes', [QuestaoController::class, 'index'])->name('provas.questoes');
    Route::post('provas/{prova}/arquivar', [ProvaController::class, 'arquivar'])->name('provas.arquivar');

    // Gabaritos
    Route::resource('provas.gabaritos', GabaritoController::class)->shallow();
    Route::post('gabaritos/{gabarito}/ativar', [GabaritoController::class, 'ativar'])->name('gabaritos.ativar');

    // Cartões
    Route::get('provas/{prova}/cartoes', [CartaoRespostaController::class, 'index'])->name('cartoes.index');
    Route::post('provas/{prova}/cartoes/gerar', [CartaoRespostaController::class, 'gerar'])->name('cartoes.gerar');
    Route::get('cartoes/{cartao}/pdf', [CartaoRespostaController::class, 'pdf'])->name('cartoes.pdf');

    // Leitura via webcam
    Route::get('leituras/webcam', [LeituraController::class, 'webcam'])->name('leituras.webcam');
    Route::get('leituras/{leitura}', [LeituraController::class, 'show'])->name('leituras.show');
    Route::delete('leituras/{leitura}', [LeituraController::class, 'descartar'])->name('leituras.descartar');

    // Resultados
    Route::get('resultados/{resultado}', [ResultadoController::class, 'show'])->name('resultados.show');
    Route::get('provas/{prova}/relatorio', [ResultadoController::class, 'relatorio'])->name('provas.relatorio');
    Route::get('provas/{prova}/relatorio/csv', [ResultadoController::class, 'exportarCSV'])->name('provas.relatorio.csv');
});

// API (usada pelo JavaScript da tela de leitura)
Route::middleware(['auth:sanctum'])->prefix('api')->group(function () {
    Route::get('cartoes/{qrData}', [API\CartaoController::class, 'buscarPorQR']);
    Route::post('leituras', [API\LeituraController::class, 'store']);
    Route::post('leituras/{leitura}/recalcular', [API\LeituraController::class, 'recalcular']);
});
```

---

## 12. APIs e Contratos Internos

### `GET /api/cartoes/{qrData}`

Valida o QR Code escaneado e retorna dados do cartão.

**Response 200:**
```json
{
  "cartao_id": "uuid",
  "prova_id": "uuid",
  "prova_titulo": "Simulado Bimestral 1",
  "codigo_aluno": "12345",
  "nome_aluno": "João Silva",
  "turma": "3A",
  "total_questoes": 30,
  "leitura_anterior": null
}
```

**Response 422:** Cartão não encontrado ou prova encerrada.

---

### `POST /api/leituras`

Salva uma leitura confirmada.

**Request:**
```json
{
  "cartao_id": "uuid",
  "respostas": [
    {"numero": 1, "marcacao": "A", "confianca": 0.92, "dupla": false, "branco": false},
    {"numero": 2, "marcacao": null, "confianca": 0.0, "dupla": false, "branco": true},
    {"numero": 3, "marcacao": null, "confianca": 0.0, "dupla": true, "branco": false}
  ],
  "metadados_omr": {
    "angulo_rotacao": 0.023,
    "qr_detectado": true,
    "warnings": []
  },
  "imagem": "data:image/jpeg;base64,..."
}
```

**Response 201:**
```json
{
  "leitura_id": "uuid",
  "resultado_id": "uuid",
  "redirect_url": "/resultados/uuid"
}
```

---

### `POST /api/leituras/{leitura}/recalcular`

Recalcula o resultado de uma leitura usando o gabarito ativo atual.

**Request:**
```json
{ "motivo": "Gabarito corrigido — questão 5 anulada" }
```

**Response 200:**
```json
{
  "resultado_id": "uuid",
  "versao_calculo": 2,
  "nota_final": 8.50
}
```

---

## 13. Segurança e Validações

### 13.1 Autenticação e Autorização

- Laravel Sanctum para autenticação de API (tela de webcam usa token de sessão);
- Policies do Laravel para cada recurso (`ProvaPolicy`, `GabaritoPolicy`, `LeituraPolicy`);
- Middleware `EnsureRole` verifica a role do usuário antes de acessar rotas sensíveis;
- Professor só acessa provas que criou; admin acessa tudo.

### 13.2 Validação de Entradas

- Toda entrada de usuário validada com `FormRequest` do Laravel, com mensagens em pt-BR;
- QR Code é apenas UUID v4; o backend nunca executa ou interpreta o conteúdo como código;
- Imagem recebida via API: validar MIME type com `finfo` (não confiar apenas na extensão), tamanho máximo configurável no `.env`.

### 13.3 Proteção contra Manipulação

- O QR Code é apenas uma chave de lookup; não contém gabarito nem nota;
- `cartao_id` na leitura é verificado via Policy: o usuário deve ter acesso à prova associada;
- Respostas manuais de `corrigida_manual = true` são logadas com user_id em `logs_processamento`.

### 13.4 Upload e Processamento de Imagem

```php
// Nunca processar imagem sem validação
$request->validate([
    'imagem' => ['required', 'string', 'max:10000000'], // base64
]);

$decoded = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $request->imagem));
$finfo   = new \finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->buffer($decoded);
abort_unless(in_array($mime, ['image/jpeg', 'image/png']), 422, 'Tipo de imagem inválido.');
```

### 13.5 Auditoria e Logs

- Usar o trait `HasAuditLog` em Models críticos (Prova, Gabarito, Leitura);
- O Listener `RegistrarLogProcessamentoListener` registra todos os eventos do sistema;
- Logs de erro com `Log::channel('stack')->error()` + `logs_processamento`.

---

## 14. Testes

### 14.1 Unitários

Testar isoladamente:
- `CalcularResultadoAction`: cenários de 0, 5, 10 acertos; questão anulada; dupla marcação;
- `ProcessarLeituraAction`: comportamento com DTO válido e inválido;
- Value Objects de domínio e Enums.

### 14.2 Integração

- Repositories com banco PostgreSQL real (usar `RefreshDatabase`);
- Criar prova + gabarito + leitura + resultado via factories e verificar cálculo final.

### 14.3 Feature Tests (HTTP)

```php
it('corretor pode confirmar leitura de cartão válido', function () {
    $corretor = User::factory()->create(['role' => 'corretor']);
    $cartao   = CartaoResposta::factory()->create();
    $payload  = StoreLeituraDTO::fake($cartao);

    actingAs($corretor)
        ->postJson('/api/leituras', $payload)
        ->assertCreated()
        ->assertJsonStructure(['leitura_id', 'resultado_id']);
});
```

### 14.4 Testes do Pipeline OMR

- Criar conjunto de imagens de teste (cartões preenchidos manualmente em condições variadas);
- Testar `omrReader.js` com Jest usando imagens sintéticas;
- Registrar precision/recall por limiar de pixels;
- TODO: criar dataset de imagens de calibração em `tests/fixtures/cartoes/`.

### 14.5 Cenários de Falha

| Cenário                         | Comportamento esperado                                   |
|---------------------------------|----------------------------------------------------------|
| QR Code não detectado           | `outputMessage` visível; sem envio ao backend            |
| Cartão já lido                  | Backend retorna 422 com mensagem; frontend alerta        |
| Imagem corrompida               | Backend retorna 422; log de erro                         |
| Gabarito inativo                | Backend busca gabarito ativo; se não há, retorna 422     |
| Dupla marcação em todas as questões | Resultado salvo com todas as questões como 'dupla'  |

### 14.6 Factories

```php
ProvaFactory: total_questoes entre 10 e 90, status aleatório
GabaritoFactory: respostas JSONB com N letras aleatórias (A-E)
CartaoRespostaFactory: qr_data = UUID válido, prova_id relacionado
LeituraFactory: status, imagem_path opcional
RespostaAlunoFactory: states para correta, errada, branco, dupla
```

---

## 15. Critérios de Qualidade

### 15.1 Código Limpo

- Métodos com no máximo 30 linhas; classes com responsabilidade única;
- Nomes em português para domínio de negócio (`calcularNota`, `confirmarLeitura`);
- Nomes em inglês para infraestrutura e padrões Laravel (`store`, `update`, `index`);
- Sem comentários óbvios; código autodescritivo.

### 15.2 Tipagem

- PHP: tipagem estrita em todos os arquivos (`declare(strict_types=1)`);
- DTOs como `readonly class` com propriedades tipadas;
- JavaScript: usar JSDoc ou migrar para TypeScript nos módulos OMR.

### 15.3 Migrations

- Sempre reversíveis (`up` e `down` completos);
- Nunca alterar migration já commitada em produção; criar nova migration;
- Usar `Blueprint` completo; nunca SQL raw na migration (exceto para triggers PostgreSQL específicos).

### 15.4 Seeders e Factories

- `DatabaseSeeder` para ambiente de desenvolvimento: cria admin + professor + prova de 30 questões com gabarito + 5 cartões;
- `ProductionSeeder`: apenas usuário admin padrão.

### 15.5 Performance

- Índices em todos os campos de busca e filtro (já definidos no modelo);
- Paginação obrigatória em listagens (mínimo `paginate(20)`);
- Eager loading sempre que houver N+1 (usar `with()`);
- Jobs para operações lentas (PDF em lote, recálculo massivo).

### 15.6 Observabilidade

- Todas as Actions registram início e fim em log (com duração em ms para actions críticas);
- Métricas básicas: total de leituras/dia, taxa de sucesso OMR, tempo médio de processamento;
- `TELESCOPE_ENABLED=true` em ambiente de desenvolvimento.

---

## 16. Riscos Técnicos e Mitigação

| Risco                                | Impacto | Mitigação                                                                                      |
|--------------------------------------|---------|-----------------------------------------------------------------------------------------------|
| Variação de iluminação               | Alto    | Threshold adaptativo por bloco; orientar usuário a usar luz uniforme; indicador de qualidade   |
| Rotação/perspectiva do cartão        | Alto    | Usar ângulo do QR Code para corrigir rotação antes de ler bolhas; marcadores de canto no cartão|
| Baixa qualidade da webcam            | Médio   | Exigir resolução mínima (720p); alertar se `videoWidth < 1280`                                 |
| Marcações fracas (lápis, caneta fina)| Alto    | Limiar ajustável; orientar uso de caneta esferográfica preta/azul                              |
| Marcações excessivas/rabiscos        | Médio   | Dupla marcação → anula; limiar mínimo para considerar "marcada" evita ruído                   |
| QR Code ilegível (dobra, mancha)     | Alto    | QR Code com error correction `H` (30%); área de QR sem bolinhas ao redor                      |
| Processamento pesado no navegador    | Médio   | requestAnimationFrame com skip de frames; só processar OMR quando QR estiver estável          |
| Divergência preview/leitura final    | Médio   | Usar exatamente o mesmo algoritmo no preview e no payload enviado; não recalcular no backend  |
| Cartão impresso em resolução baixa   | Alto    | Exportar PDF com DPI ≥ 150; recomendar impressora a laser ou jato de tinta qualidade normal    |
| Mudança de gabarito após leituras    | Médio   | Sistema preserva histórico; `RecalcularResultadoJob` com confirmação explícita do admin        |

---

## 17. Entregáveis Esperados do Agente

O agente de IA que implementar este sistema deve:

1. **Implementar por fases** conforme a Seção 9; não saltar etapas;
2. **Explicar decisões arquiteturais** ao implementar cada componente;
3. **Criar migrations, models, controllers, requests, policies e testes** para cada feature;
4. **Manter consistência com Laravel 13**: usar `php artisan make:` para gerar classes, evitar código improvisado;
5. **Criar factories e seeders** junto com cada migration;
6. **Documentar TODO** quando algo depender de calibração prática (ex.: offsets das bolinhas no OMR);
7. **Nunca usar `DB::statement` raw** onde o ORM resolve; reservar SQL raw para otimizações justificadas;
8. **Testar antes de considerar pronto**: cada feature deve ter ao menos um Feature Test verde;
9. **Documentar comandos** de instalação, migração e execução ao final de cada fase;
10. **Preparar para expansão**: não hardcodar limites de questões na UI; usar configuração do model.

---

## Primeiros Passos de Implementação

Execute esta sequência **exatamente nesta ordem** após ler este arquivo:

### Passo 1 — Verificar pré-requisitos

```bash
php -v          # Deve ser 8.4+
composer -V     # Deve ser 2.x
laravel --version
node -v
npm -v
psql --version  # PostgreSQL 14+
```

Se PHP, Composer ou Laravel CLI estiverem ausentes (macOS):
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.4)"
```

### Passo 2 — Criar o projeto Laravel

```bash
cd /Users/renankabal/www
laravel new proesc_simulados \
    --database=pgsql \
    --livewire \
    --npm \
    --no-interaction
cd proesc_simulados
```

### Passo 3 — Configurar o banco PostgreSQL

Criar banco e usuário no PostgreSQL:
```sql
CREATE DATABASE proesc_simulados;
CREATE USER proesc_app WITH PASSWORD 'senha_segura';
GRANT ALL PRIVILEGES ON DATABASE proesc_simulados TO proesc_app;
```

Editar `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=proesc_simulados
DB_USERNAME=proesc_app
DB_PASSWORD=senha_segura
```

### Passo 4 — Instalar dependências

```bash
composer require barryvdh/laravel-dompdf
composer require simplesoftwareio/simple-qrcode
composer require laravel/telescope --dev

npm install jsqr
npm install
npm run build
```

### Passo 5 — Configurar filas e cache

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Passo 6 — Executar as migrations iniciais

```bash
php artisan migrate
php artisan telescope:install
php artisan migrate
```

### Passo 7 — Implementar Fase 1

Começar pela autenticação com roles:
```bash
php artisan make:migration add_role_to_users_table
php artisan make:enum RoleUsuario
php artisan make:policy ProvaPolicy --model=Prova
```

Implementar na ordem: `users` (role) → `provas` → `questoes` → `gabaritos`, com CRUD completo via Livewire, policies e Feature Tests para cada recurso.

### Passo 8 — Verificação de saúde antes de cada commit

```bash
php artisan test                 # Todos os testes devem estar verdes
php artisan migrate:fresh --seed # Banco deve sedar sem erros
npm run build                    # Assets devem compilar sem erros
```

---

> **Lembre-se:** Precisão e auditabilidade são mais importantes que velocidade. O sistema lida com notas de alunos — cada leitura confirmada deve ser rastreável, reversível e confiável.
