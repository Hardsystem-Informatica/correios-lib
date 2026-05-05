# Logística Reversa — Guia de Uso

Contrato público da extensão `Hardsystem\Correios\LogisticaReversa`. Este documento é a referência de quem **integra** a lib: lista cada operação, todos os tipos de entrada e saída, e exemplos prontos pra copiar.

A lib mantém o WSDL SOAP dos Correios encapsulado — o consumidor só lida com PHP tipado.

## Sumário

1. [Setup](#setup)
2. [Instanciando a fachada](#instanciando-a-fachada)
3. [Operações](#operações)
   - [solicitarPostagemReversa](#solicitarpostagemreversa)
   - [cancelarPedido](#cancelarpedido)
   - [acompanharPedido](#acompanharpedido)
   - [acompanharPedidoPorData](#acompanharpedidoporData)
   - [revalidarPrazoAutorizacaoPostagem](#revalidarprazoautorizacaopostagem)
   - [solicitarRange](#solicitarrange)
   - [calcularDigitoVerificador](#calculardigitoverificador)
   - [dvLocal (estático)](#dvlocal-estático)
   - [solicitarPostagemSimultanea](#solicitarpostagemsimultanea)
4. [DTOs de entrada](#dtos-de-entrada)
5. [DTOs de saída](#dtos-de-saída)
6. [Enums](#enums)
7. [Erros e exceções](#erros-e-exceções)

## Setup

Requisitos: PHP 8.1+, extensão `ext-soap` habilitada.

```bash
composer require hardsystem/correios-lib
```

```php
use Hardsystem\Correios\LogisticaReversa\Ambiente;
use Hardsystem\Correios\LogisticaReversa\Credenciais;
use Hardsystem\Correios\LogisticaReversa\LogisticaReversa;
```

## Instanciando a fachada

Três formas, em ordem de preferência:

### `new LogisticaReversa(Ambiente, Credenciais)` — uso normal

| Parâmetro     | Tipo          | Descrição                                                    |
| ------------- | ------------- | ------------------------------------------------------------ |
| `$ambiente`   | `Ambiente`    | Enum: `Ambiente::Producao` ou `Ambiente::Homologacao`        |
| `$credenciais`| `Credenciais` | Credenciais do contrato — ver [Credenciais](#credenciais)     |

```php
$logisticaReversa = new LogisticaReversa(
    Ambiente::Producao,
    new Credenciais(
        usuario: 'meu-usuario',
        senha: 'minha-senha',
        codigoAdministrativo: '17000190',
        contrato: '9992157880',
        cartaoPostagem: '0011111111',
    ),
);
```

### `LogisticaReversa::comWsdl(string, Credenciais)` — escape hatch

Use quando precisar apontar pra um WSDL não-padrão (ex.: ambiente de testes interno, mock).

```php
$logisticaReversa = LogisticaReversa::comWsdl(
    'https://meu-mock.local/lr.wsdl',
    $credenciais,
);
```

### `LogisticaReversa::comCliente(\SoapClient, Credenciais)` — para testes/debug

Permite injetar um `SoapClient` configurado externamente (com `trace`, mocks, timeouts customizados).

```php
$cliente = new SoapClient($wsdl, ['login' => '...', 'password' => '...', 'trace' => true]);
$logisticaReversa = LogisticaReversa::comCliente($cliente, $credenciais);
```

## Operações

### `solicitarPostagemReversa`

Cria autorizações de postagem (e-tickets) ou solicitações de coleta domiciliar. Aceita até **50 solicitações por chamada**, cada uma com até **10 objetos**.

**Assinatura:**

```php
public function solicitarPostagemReversa(SolicitacaoReversa $solicitacao): array
```

**Parâmetro:**

| Nome           | Tipo                  | Obrigatório |
| -------------- | --------------------- | ----------- |
| `$solicitacao` | `SolicitacaoReversa`  | sim         |

**Retorno:** `ResultadoSolicitacao[]` — um item por coleta enviada. Itens podem ter erro individual sem afetar os outros (consultar `temErro()`).

**Lança:**
- `LogisticaReversaException` — quando o servidor recusa o batch inteiro (ex.: contrato inválido, cartão inválido)

**Exemplo:**

```php
$resultados = $logisticaReversa->solicitarPostagemReversa(
    new SolicitacaoReversa(
        codigoServico: '04677',
        destinatario: new EnderecoDestinatario(
            nome: 'Loja XPTO',
            logradouro: 'SBN',
            numero: '10',
            bairro: 'Plano Piloto',
            cidade: 'Brasília',
            uf: 'DF',
            cep: '70002900',
            cienciaConteudoProibido: true,
        ),
        coletas: [
            new Coleta(
                tipo: TipoColeta::AutorizacaoPostagem,
                remetente: new EnderecoRemetente(
                    nome: 'Cliente Final',
                    logradouro: 'Rua 35',
                    numero: '10',
                    bairro: 'Centro',
                    cidade: 'Brasília',
                    uf: 'DF',
                    cep: '71931180',
                    ddd: '61',
                    telefone: '34262222',
                    email: 'cliente@mail.com',
                    restricaoAnac: true,
                ),
                objetos: [new ObjetoColeta(item: 1, descricao: 'Livro devolvido')],
                idCliente: 'NF-12345',
                diasValidade: 10,
            ),
        ],
    ),
);

foreach ($resultados as $resultado) {
    if ($resultado->temErro()) {
        echo "Erro [{$resultado->codigoErro}]: {$resultado->descricaoErro}\n";
        continue;
    }
    echo "E-ticket: {$resultado->numeroColeta}, prazo: {$resultado->prazo->format('d/m/Y')}\n";
}
```

### `cancelarPedido`

Cancela uma autorização de postagem ou coleta reversa. Só funciona enquanto o pedido estiver em status cancelável.

**Assinatura:**

```php
public function cancelarPedido(string $numeroPedido, TipoPedido $tipo): ResultadoCancelamento
```

**Parâmetros:**

| Nome             | Tipo         | Obrigatório | Descrição                                                |
| ---------------- | ------------ | ----------- | -------------------------------------------------------- |
| `$numeroPedido`  | `string`     | sim         | Número devolvido em `ResultadoSolicitacao::numeroColeta` |
| `$tipo`          | `TipoPedido` | sim         | `TipoPedido::AutorizacaoPostagem` ou `TipoPedido::Coleta` |

**Retorno:** `ResultadoCancelamento`

**Lança:**
- `PedidoNaoCancelavelException` — pedido fora de status que aceita cancelamento (cod_erro -9)
- `LogisticaReversaException` — outros erros (-5 número não encontrado, etc.)

**Exemplo:**

```php
try {
    $cancelamento = $logisticaReversa->cancelarPedido(
        $resultado->numeroColeta,
        TipoPedido::AutorizacaoPostagem,
    );
    echo "Cancelado em {$cancelamento->dataHoraCancelamento->format('d/m/Y H:i')}\n";
} catch (PedidoNaoCancelavelException $erro) {
    echo "Não pode cancelar: {$erro->getMessage()}\n";
}
```

### `acompanharPedido`

Consulta um pedido pelo número, retornando histórico ou só o último evento.

**Assinatura:**

```php
public function acompanharPedido(string $numeroPedido, TipoPedido $tipo, TipoBusca $busca): Pedido
```

**Parâmetros:**

| Nome             | Tipo         | Obrigatório |
| ---------------- | ------------ | ----------- |
| `$numeroPedido`  | `string`     | sim         |
| `$tipo`          | `TipoPedido` | sim         |
| `$busca`         | `TipoBusca`  | sim — `Historico` (todos eventos) ou `UltimoEvento` (só o último) |

**Retorno:** `Pedido`

**Lança:**
- `LogisticaReversaException` — pedido não encontrado (cod_erro -5)

**Exemplo:**

```php
$pedido = $logisticaReversa->acompanharPedido(
    '194848820',
    TipoPedido::AutorizacaoPostagem,
    TipoBusca::Historico,
);

echo "Status atual: " . ($pedido->ultimoStatus?->descricao() ?? 'desconhecido') . "\n";
foreach ($pedido->historico as $evento) {
    echo "{$evento->dataAtualizacao->format('d/m/Y')} {$evento->horaAtualizacao} — {$evento->descricaoStatus}\n";
}
```

### `acompanharPedidoPorData`

Lista todos os pedidos com atualização de status numa data específica.

**Assinatura:**

```php
public function acompanharPedidoPorData(\DateTimeImmutable $data, TipoPedido $tipo): array
```

**Parâmetros:**

| Nome     | Tipo                 | Obrigatório |
| -------- | -------------------- | ----------- |
| `$data`  | `\DateTimeImmutable` | sim         |
| `$tipo`  | `TipoPedido`         | sim         |

**Retorno:** `Pedido[]` — pode vir vazio quando não há movimento.

**Lança:**
- `LogisticaReversaException` — em alguns casos o servidor retorna -13 (sem informação) em vez de lista vazia.

**Exemplo:**

```php
$pedidos = $logisticaReversa->acompanharPedidoPorData(
    new DateTimeImmutable('today'),
    TipoPedido::AutorizacaoPostagem,
);

echo "Hoje: " . count($pedidos) . " pedidos com movimento\n";
```

### `revalidarPrazoAutorizacaoPostagem`

Estende o prazo de uma autorização de postagem cuja validade já tenha expirado. Aceita entre **5 e 30 dias corridos**.

**Assinatura:**

```php
public function revalidarPrazoAutorizacaoPostagem(string $numeroPedido, int $dias): \DateTimeImmutable
```

**Parâmetros:**

| Nome             | Tipo     | Obrigatório | Restrições               |
| ---------------- | -------- | ----------- | ------------------------ |
| `$numeroPedido`  | `string` | sim         |                          |
| `$dias`          | `int`    | sim         | entre 5 e 30 (inclusive) |

**Retorno:** `\DateTimeImmutable` — novo prazo de validade.

**Lança:**
- `LogisticaReversaException` com `codigoCorreios`:
  - `-15` — `dias` fora do intervalo 5–30
  - `-16` — e-ticket ainda dentro do prazo (não expirou)
  - `-17` — e-ticket já utilizado em postagem
  - `-13` — pedido inexistente
  - `-18` — novo prazo ultrapassa vigência do contrato

**Exemplo:**

```php
$novoPrazo = $logisticaReversa->revalidarPrazoAutorizacaoPostagem('194848820', 10);
echo "Novo prazo: {$novoPrazo->format('d/m/Y')}\n";
```

### `solicitarRange`

Reserva uma faixa numérica de e-tickets para uso de contingência. Limite máximo de **50.000** etiquetas por chamada. **Importante:** só pode solicitar nova faixa após consumir 80% da anterior (cod_erro 247).

**Assinatura:**

```php
public function solicitarRange(TipoRange $tipo, int $quantidade, ?ServicoLR $servico = null): FaixaEtiquetas
```

**Parâmetros:**

| Nome           | Tipo          | Obrigatório | Descrição                                          |
| -------------- | ------------- | ----------- | -------------------------------------------------- |
| `$tipo`        | `TipoRange`   | sim         | Atualmente só `TipoRange::AutorizacaoPostagem`     |
| `$quantidade`  | `int`         | sim         | 1 a 50.000                                         |
| `$servico`     | `?ServicoLR`  | não         | Quando `null`, range é puro AP                     |

**Retorno:** `FaixaEtiquetas`

**Lança:**
- `LogisticaReversaException`:
  - `226` — quantidade inválida
  - `247` — range anterior não consumido em 80%

**Exemplo:**

```php
$faixa = $logisticaReversa->solicitarRange(TipoRange::AutorizacaoPostagem, 100);
echo "Range reservado: {$faixa->faixaInicial} até {$faixa->faixaFinal}\n";
```

### `calcularDigitoVerificador`

Calcula o DV de um e-ticket reservado via `solicitarRange`. Faz uma chamada SOAP. **Para evitar latência, prefira `LogisticaReversa::dvLocal()`** — usa o mesmo algoritmo (Anexo 03 do manual) sem rede.

**Assinatura:**

```php
public function calcularDigitoVerificador(string $numero): string
```

**Parâmetros:**

| Nome      | Tipo     | Obrigatório | Restrições                  |
| --------- | -------- | ----------- | --------------------------- |
| `$numero` | `string` | sim         | 8 ou 9 dígitos numéricos    |

**Retorno:** `string` — número original concatenado com o DV.

**Exemplo:**

```php
$comDigito = $logisticaReversa->calcularDigitoVerificador('15653829');
// '156538297'
```

### `dvLocal` (estático)

Cálculo do DV sem chamada de rede. Mesmo algoritmo do `calcularDigitoVerificador`.

**Assinatura:**

```php
public static function dvLocal(string $numero): string
```

**Parâmetros e retorno:** idênticos a `calcularDigitoVerificador`.

**Lança:**
- `\InvalidArgumentException` — quando `$numero` não tem 8 ou 9 dígitos ou contém não-numéricos.

**Exemplo:**

```php
$comDigito = LogisticaReversa::dvLocal('15653829'); // '156538297'
```

### `solicitarPostagemSimultanea`

Solicita uma coleta no mesmo endereço onde será entregue uma encomenda substituta (Logística Reversa Simultânea). Requer um número de etiqueta de **registro de ida** previamente reservado pelo representante comercial.

**Assinatura:**

```php
public function solicitarPostagemSimultanea(SolicitacaoSimultanea $solicitacao): ResultadoSolicitacao
```

**Parâmetro:**

| Nome           | Tipo                    | Obrigatório |
| -------------- | ----------------------- | ----------- |
| `$solicitacao` | `SolicitacaoSimultanea` | sim         |

**Retorno:** `ResultadoSolicitacao`

**Exemplo:**

```php
$resultado = $logisticaReversa->solicitarPostagemSimultanea(
    new SolicitacaoSimultanea(
        codigoServico: '04677',
        destinatario: $destinatario,
        coleta: new ColetaSimultanea(
            remetente: $remetente,
            numeroEtiquetaIda: 'DL123456789BR',
            idCliente: 'NF-99999',
        ),
    ),
);
```

## DTOs de entrada

### `Credenciais`

Imutável. Construa uma vez e reutilize entre chamadas.

| Campo                    | Tipo     | Obrigatório |
| ------------------------ | -------- | ----------- |
| `usuario`                | `string` | sim         |
| `senha`                  | `string` | sim         |
| `codigoAdministrativo`   | `string` | sim         |
| `contrato`               | `string` | sim         |
| `cartaoPostagem`         | `string` | sim         |

### `EnderecoDestinatario`

| Campo                       | Tipo      | Obrigatório | Default |
| --------------------------- | --------- | ----------- | ------- |
| `nome`                      | `string`  | sim         |         |
| `logradouro`                | `string`  | sim         |         |
| `numero`                    | `string`  | sim         |         |
| `bairro`                    | `string`  | sim         |         |
| `cidade`                    | `string`  | sim         |         |
| `uf`                        | `string`  | sim         |         |
| `cep`                       | `string`  | sim         | (8 dígitos sem hífen) |
| `cienciaConteudoProibido`   | `bool`    | sim         |         |
| `complemento`               | `?string` | não         | `null`  |
| `referencia`                | `?string` | não         | `null`  |
| `ddd`                       | `?string` | não         | `null`  |
| `telefone`                  | `?string` | não         | `null`  |
| `celular`                   | `?string` | não         | `null`  |
| `dddCelular`                | `?string` | não         | `null`  |
| `email`                     | `?string` | não         | `null`  |
| `identificacao`             | `?string` | não         | `null`  |

### `EnderecoRemetente`

Diferente do destinatário — `ddd`, `telefone`, `email` e `restricaoAnac` são obrigatórios.

| Campo                  | Tipo      | Obrigatório | Default |
| ---------------------- | --------- | ----------- | ------- |
| `nome`                 | `string`  | sim         |         |
| `logradouro`           | `string`  | sim         |         |
| `numero`               | `string`  | sim         |         |
| `bairro`               | `string`  | sim         |         |
| `cidade`               | `string`  | sim         |         |
| `uf`                   | `string`  | sim         |         |
| `cep`                  | `string`  | sim         |         |
| `ddd`                  | `string`  | sim         |         |
| `telefone`             | `string`  | sim         |         |
| `email`                | `string`  | sim         |         |
| `restricaoAnac`        | `bool`    | sim         |         |
| `complemento`          | `?string` | não         | `null`  |
| `referencia`           | `?string` | não         | `null`  |
| `celular`              | `?string` | não         | `null`  |
| `dddCelular`           | `?string` | não         | `null`  |
| `sms`                  | `bool`    | não         | `false` |
| `identificacao`        | `?string` | não         | `null`  |
| `documentoEstrangeiro` | `?string` | não         | `null`  |

### `Produto`

| Campo        | Tipo  | Obrigatório |
| ------------ | ----- | ----------- |
| `codigo`     | `int` | sim         |
| `tipo`       | `int` | sim         |
| `quantidade` | `int` | sim (1–10)  |

### `ObjetoColeta`

| Campo       | Tipo      | Obrigatório | Default |
| ----------- | --------- | ----------- | ------- |
| `item`      | `int`     | sim (1–10)  |         |
| `id`        | `?string` | não         | `null`  |
| `descricao` | `?string` | não         | `null`  |
| `entrega`   | `?string` | não         | `null`  |
| `numero`    | `?string` | não         | `null`  |

### `Coleta`

| Campo                       | Tipo                       | Obrigatório | Default | Observação                                                            |
| --------------------------- | -------------------------- | ----------- | ------- | --------------------------------------------------------------------- |
| `tipo`                      | `TipoColeta`               | sim         |         | `CA`, `C` ou `A`                                                      |
| `remetente`                 | `EnderecoRemetente`        | sim         |         |                                                                       |
| `objetos`                   | `ObjetoColeta[]`           | sim         |         | 1–10 itens                                                            |
| `numeroEticket`             | `?string`                  | não         | `null`  | E-ticket reservado de range                                           |
| `idCliente`                 | `?string`                  | não         | `null`  | NF, OS, etc. — não pode repetir entre solicitações                    |
| `dataAgendamento`           | `?\DateTimeImmutable`      | não         | `null`  | Para tipo `C`/`CA`. Mín. 5 dias corridos da data de processamento     |
| `diasValidade`              | `?int`                     | não         | `null`  | Para tipo `A`. 1–90 dias                                              |
| `cartaoPostagem`            | `?string`                  | não         | `null`  | Sobrescreve o cartão das credenciais para essa coleta                 |
| `valorDeclarado`            | `?float`                   | não         | `null`  | R$ 24,50 ≤ valor ≤ R$ 10.000,00                                       |
| `descricao`                 | `?string`                  | não         | `null`  |                                                                       |
| `solicitarAvisoRecebimento` | `bool`                     | não         | `false` | Só para tipo `A`                                                      |
| `checklist`                 | `?ChecklistTipo`           | não         | `null`  |                                                                       |
| `documentos`                | `DocumentoChecklist[]`     | não         | `[]`    | Obrigatório com `checklist = Documento`. Máx. 8                       |
| `produtos`                  | `Produto[]`                | não         | `[]`    | Embalagens junto à coleta                                             |

**Regra:** ou passa `dataAgendamento` (coleta), ou `diasValidade` (autorização), ou nenhum (autorização com default 10 dias). Não passe os dois.

### `ColetaSimultanea`

Variante para LRS. Tipo é sempre `C` implicitamente; campo `numeroEtiquetaIda` é obrigatório.

| Campo                | Tipo                       | Obrigatório | Default |
| -------------------- | -------------------------- | ----------- | ------- |
| `remetente`          | `EnderecoRemetente`        | sim         |         |
| `numeroEtiquetaIda`  | `string`                   | sim         |         |
| `idCliente`          | `?string`                  | não         | `null`  |
| `valorDeclarado`     | `?float`                   | não         | `null`  |
| `descricao`          | `?string`                  | não         | `null`  |
| `checklist`          | `?ChecklistTipo`           | não         | `null`  |
| `documentos`         | `DocumentoChecklist[]`     | não         | `[]`    |
| `produtos`           | `Produto[]`                | não         | `[]`    |
| `observacao`         | `?string`                  | não         | `null`  |

### `SolicitacaoReversa`

| Campo            | Tipo                    | Obrigatório | Default |
| ---------------- | ----------------------- | ----------- | ------- |
| `codigoServico`  | `string`                | sim         |         |
| `destinatario`   | `EnderecoDestinatario`  | sim         |         |
| `coletas`        | `Coleta[]`              | sim         |         |
| `cartaoPostagem` | `?string`               | não         | `null`  |

### `SolicitacaoSimultanea`

| Campo            | Tipo                    | Obrigatório | Default |
| ---------------- | ----------------------- | ----------- | ------- |
| `codigoServico`  | `string`                | sim         |         |
| `destinatario`   | `EnderecoDestinatario`  | sim         |         |
| `coleta`         | `ColetaSimultanea`      | sim         |         |
| `cartaoPostagem` | `?string`               | não         | `null`  |

## DTOs de saída

Todos `readonly` — propriedades de leitura direta.

### `ResultadoSolicitacao`

Retornado por `solicitarPostagemReversa` (lista) e `solicitarPostagemSimultanea` (único).

| Campo             | Tipo                    | Pode ser null? |
| ----------------- | ----------------------- | -------------- |
| `tipo`            | `TipoColeta`            | não            |
| `numeroColeta`    | `string`                | não (vazio se houve erro no item) |
| `idCliente`       | `?string`               | sim            |
| `numeroEtiqueta`  | `?string`               | sim            |
| `idObjeto`        | `?string`               | sim            |
| `statusObjeto`    | `?StatusPedido`         | sim            |
| `prazo`           | `?\DateTimeImmutable`   | sim            |
| `dataSolicitacao` | `?\DateTimeImmutable`   | sim            |
| `horaSolicitacao` | `?string`               | sim            |
| `codigoErro`      | `?int`                  | sim — `0`/`null` quando OK |
| `descricaoErro`   | `?string`               | sim            |

**Método:** `temErro(): bool` — `true` se `codigoErro` não for `null` nem `0`.

### `ResultadoCancelamento`

Retornado por `cancelarPedido`.

| Campo                  | Tipo                  |
| ---------------------- | --------------------- |
| `codigoAdministrativo` | `string`              |
| `numeroPedido`         | `string`              |
| `statusPedido`         | `string` (texto)      |
| `dataHoraCancelamento` | `\DateTimeImmutable`  |

### `EventoPedido`

Item de `Pedido::historico`.

| Campo              | Tipo                  | Pode ser null? |
| ------------------ | --------------------- | -------------- |
| `status`           | `StatusPedido`        | não            |
| `descricaoStatus`  | `string`              | não            |
| `dataAtualizacao`  | `\DateTimeImmutable`  | não            |
| `horaAtualizacao`  | `string` (HH:MM:SS)   | não            |
| `observacao`       | `?string`             | sim            |

### `Pedido`

Retornado por `acompanharPedido` e `acompanharPedidoPorData`.

| Campo                    | Tipo                  | Pode ser null? |
| ------------------------ | --------------------- | -------------- |
| `codigoAdministrativo`   | `string`              | não            |
| `tipoSolicitacao`        | `TipoPedido`          | não            |
| `numeroPedido`           | `string`              | não            |
| `historico`              | `EventoPedido[]`      | não — pode ser `[]` |
| `controleCliente`        | `?string`             | sim            |
| `numeroEtiqueta`         | `?string`             | sim            |
| `controleObjetoCliente`  | `?string`             | sim            |
| `ultimoStatus`           | `?StatusPedido`       | sim            |
| `descricaoUltimoStatus`  | `?string`             | sim            |
| `dataUltimaAtualizacao`  | `?\DateTimeImmutable` | sim            |
| `horaUltimaAtualizacao`  | `?string`             | sim            |

### `FaixaEtiquetas`

Retornado por `solicitarRange`.

| Campo            | Tipo                  |
| ---------------- | --------------------- |
| `emitidoEm`      | `\DateTimeImmutable`  |
| `faixaInicial`   | `string`              |
| `faixaFinal`     | `string`              |

## Enums

### `Ambiente: string`

| Caso          | Valor             | Endpoint                                                                                            |
| ------------- | ----------------- | --------------------------------------------------------------------------------------------------- |
| `Producao`    | `'producao'`      | `apps.correios.com.br/logisticaReversaWS/...`                                                       |
| `Homologacao` | `'homologacao'`   | `apphom.correios.com.br/logisticaReversaWS/...`                                                     |

Método: `wsdl(): string` — retorna a URL completa do WSDL.

### `TipoColeta: string`

| Caso                          | Valor  | Significado                                                                                          |
| ----------------------------- | ------ | ---------------------------------------------------------------------------------------------------- |
| `ColetaDomiciliarComFallback` | `'CA'` | Coleta domiciliar; se não disponível na localidade, vira autorização de postagem automaticamente    |
| `ColetaDomiciliar`            | `'C'`  | Coleta domiciliar estrita; rejeita se localidade não tem coleta                                     |
| `AutorizacaoPostagem`         | `'A'`  | Cliente posta em agência usando o e-ticket                                                          |

### `TipoPedido: string`

Para `cancelarPedido` / `acompanharPedido` / `acompanharPedidoPorData`.

| Caso                  | Valor |
| --------------------- | ----- |
| `Coleta`              | `'C'` |
| `AutorizacaoPostagem` | `'A'` |

### `TipoBusca: string`

| Caso          | Valor | Comportamento             |
| ------------- | ----- | ------------------------- |
| `Historico`   | `'H'` | Todos os eventos do pedido |
| `UltimoEvento`| `'U'` | Apenas o último evento     |

### `TipoRange: string`

| Caso                  | Valor   |
| --------------------- | ------- |
| `AutorizacaoPostagem` | `'AP'`  |

### `ServicoLR: string`

Opcional em `solicitarRange`.

| Caso     | Valor  | Descrição                |
| -------- | ------ | ------------------------ |
| `PAC`    | `'LE'` | Logística Reversa - PAC  |
| `Sedex`  | `'LS'` | Logística Reversa - SEDEX |
| `eSedex` | `'LV'` | Logística Reversa - e-SEDEX |

### `ChecklistTipo: int`

| Caso         | Valor |
| ------------ | ----- |
| `Celular`    | `2`   |
| `Eletronico` | `4`   |
| `Documento`  | `5`   |
| `Conteudo`   | `7`   |

### `DocumentoChecklist: int`

38 valores conforme tabela do manual (item 5.4). Casos relevantes incluem `CPF` (2), `CNPJ` (3), `RG` (4), `CNH` (8), `Passaporte` (33), `TituloEleitor` (38), etc. Use sempre os cases do enum em vez do número cru.

### `StatusPedido: int`

33 cases cobrindo o Anexo 06 do manual. Cases mais comuns:

| Caso                              | Valor | Descrição                            |
| --------------------------------- | ----- | ------------------------------------ |
| `AColetar`                        | `1`   | A Coletar                            |
| `Coletado`                        | `6`   | Coletado                             |
| `Entregue`                        | `7`   | Entregue                             |
| `DesistenciaCliente`              | `9`   | Desistência do Cliente ECT           |
| `AguardandoObjetoNaAgencia`       | `55`  | Aguardando Objeto na Agência         |
| `PrazoUtilizacaoExpirado`         | `57`  | Prazo de Utilização Expirado         |
| `AutorizacaoPostagemCancelada`    | `68`  | Autorização de Postagem Cancelada    |

Método: `descricao(): string` — texto humano correspondente.

### `CodigoErroLR`

Não é enum — é classe utilitária com lookup estático dos ~100 códigos do Anexo 05.

```php
CodigoErroLR::descricaoDe(228);    // 'Quantidade de objetos superior ao permitido'
CodigoErroLR::descricaoDe(99999);  // null
CodigoErroLR::conhecido(228);      // true
```

## Erros e exceções

Hierarquia:

```
\Exception
└── LogisticaReversaException
    └── PedidoNaoCancelavelException
```

### `LogisticaReversaException`

Base de tudo. Carrega `codigoCorreios: ?int` quando o erro veio do servidor (vem `null` em falhas de transporte).

| Propriedade       | Tipo    |
| ----------------- | ------- |
| `getMessage()`    | `string` |
| `codigoCorreios`  | `?int`  |

**Factories:**
- `LogisticaReversaException::deCodigoCorreios(int $codigo, ?string $mensagemServidor): self`
- `LogisticaReversaException::falhaTransporte(string $mensagem, ?\Throwable $anterior): self` — usado quando o `SoapClient` lança `SoapFault` (DNS, TLS, timeout).

### `PedidoNaoCancelavelException`

Específico para `cod_erro = -9` ao tentar cancelar pedido fora de status. `codigoCorreios` sempre `-9`.

```php
try {
    $logisticaReversa->cancelarPedido($numero, TipoPedido::AutorizacaoPostagem);
} catch (PedidoNaoCancelavelException $e) {
    // status do pedido não permite cancelamento
} catch (LogisticaReversaException $e) {
    // outros erros: -5 não encontrado, falha de rede, etc.
}
```

### Erros por item (não são exceções)

**Importante:** `solicitarPostagemReversa` aceita lote de até 50 e o servidor processa **parcialmente** — itens válidos viram e-ticket, itens inválidos voltam com `codigoErro` preenchido no `ResultadoSolicitacao`. Não estouram exception. Sempre verifique `temErro()` antes de usar `numeroColeta`.

```php
foreach ($logisticaReversa->solicitarPostagemReversa($solicitacao) as $resultado) {
    if ($resultado->temErro()) {
        $descricao = CodigoErroLR::descricaoDe($resultado->codigoErro)
            ?? $resultado->descricaoErro
            ?? 'erro desconhecido';
        echo "Falhou [{$resultado->codigoErro}]: {$descricao}\n";
        continue;
    }
    // processa o sucesso
}
```
