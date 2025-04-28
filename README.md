# Correios Lib

Biblioteca PHP para integração com os serviços dos Correios.

## Requisitos

- PHP 8.1 ou superior
- Composer

## Instalação

```bash
composer require hardsystem/correios-lib
```

## Funcionalidades

- Cálculo de frete
- Rastreamento de encomendas
- Consulta de CEP
- Geração de etiquetas

## Exemplos de Uso

### Consulta de CEP

```php
<?php

require 'vendor/autoload.php';

use Hardsystem\Correios\Correios;

$correios = new Correios();

try {
    $endereco = $correios->consultarCep('01001000');
    
    echo "CEP: " . $endereco->getCep() . "\n";
    echo "Logradouro: " . $endereco->getLogradouro() . "\n";
    echo "Bairro: " . $endereco->getBairro() . "\n";
    echo "Cidade: " . $endereco->getLocalidade() . "\n";
    echo "UF: " . $endereco->getUf() . "\n";
    echo "DDD: " . $endereco->getDdd() . "\n";
} catch (\Hardsystem\Correios\Exceptions\ConsultaCepException $e) {
    echo "Erro ao consultar CEP: " . $e->getMessage() . "\n";
}
```

## Documentação

A documentação completa está disponível em [docs/](docs/).

## Contribuição

Contribuições são bem-vindas! Por favor, leia o [CONTRIBUTING.md](CONTRIBUTING.md) para detalhes sobre o processo de contribuição.

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes. 