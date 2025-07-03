<?php

namespace Hardsystem\Correios;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Hardsystem\Correios\Exceptions\ConsultaCepException;
use Hardsystem\Correios\Exceptions\CalculoFreteException;

class Correios
{
    private Client $client;
    private string $usuario;
    private string $senha;
    private string $cartaoPostal;
    private ?string $token = null;
    private string $baseUrl;

    public function __construct(
        string $usuario = '',
        string $senha = '',
        string $cartaoPostal = '',
        string $baseUrl = 'https://api.correios.com.br'
    ) {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false
        ]);

        $this->usuario = $usuario;
        $this->senha = $senha;
        $this->cartaoPostal = $cartaoPostal;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Obtém o token de autenticação
     *
     * @return array
     * @throws CalculoFreteException
     */
    private function getToken(): array
    {
        try {
            $postagem = [
                "numero" => $this->cartaoPostal,
            ];
            $cartaoPostagemJSON = json_encode($postagem);

            $response = $this->client->post($this->baseUrl . '/token/v1/autentica/cartaopostagem', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':' . $this->senha),
                    'Content-Type' => 'application/json'
                ],
                'body' => $cartaoPostagemJSON
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['token'])) {
                throw CalculoFreteException::erroNoCalculo('Token não encontrado na resposta');
            }

            return [
                'token' => $data['token'],
                'emissao' => $data['emissao'] ?? null
            ];
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            throw CalculoFreteException::erroNoCalculo('Erro ao obter token: ' . $responseBody);
        } catch (GuzzleException $e) {
            throw CalculoFreteException::erroNoCalculo('Erro ao obter token: ' . $e->getMessage());
        }
    }

    /**
     * Calcula o frete para uma encomenda
     *
     * @param array $dados Dados da encomenda
     * @return array
     * @throws CalculoFreteException
     */
    public function calcularFrete(array $dados): array
    {
        if (empty($this->usuario) || empty($this->senha) || empty($this->cartaoPostal)) {
            throw CalculoFreteException::credenciaisInvalidas();
        }

        $token = $this->getToken();
        $servicos = $dados['servicos'] ?? ['04014', '04510']; // SEDEX e PAC
        $resultados = [];
        $pesoGramas = $dados['peso'] * 1000; // Convertendo peso para gramas

        foreach ($servicos as $servico) {
            try {
                $response = $this->client->get($this->baseUrl . '/preco/v1/nacional/' . $servico, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token['token'],
                        'Accept' => 'application/json'
                    ],
                    'query' => [
                        'cepDestino' => $dados['cepDestino'],
                        'cepOrigem' => $dados['cepOrigem'],
                        'psObjeto' => $pesoGramas,
                        'comprimento' => $dados['comprimento'],
                        'largura' => $dados['largura'],
                        'altura' => $dados['altura'],
                        'diametro' => $dados['diametro'] ?? 0
                    ]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $resultados[] = $data;
            } catch (ClientException $e) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                throw CalculoFreteException::erroNoCalculo($responseBody);
            } catch (GuzzleException $e) {
                throw CalculoFreteException::erroNoCalculo($e->getMessage());
            }
        }

        return $resultados;
    }

    /**
     * Consulta informações de um CEP
     *
     * @param string $cep CEP a ser consultado (apenas números)
     * @return array
     * @throws ConsultaCepException
     */
    public function consultarCep(string $cep): array
    {
        // Remove caracteres não numéricos
        $cep = preg_replace('/[^0-9]/', '', $cep);

        // Valida o formato do CEP
        if (strlen($cep) !== 8) {
            throw ConsultaCepException::cepInvalido($cep);
        }

        try {
            $token = $this->getToken();
            $response = $this->client->get($this->baseUrl . '/cep/v2/enderecos/' . $cep, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token['token'],
                    'Accept' => 'application/json'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($response->getStatusCode() === 404) {
                throw ConsultaCepException::cepNaoEncontrado($cep);
            }
            throw ConsultaCepException::erroNaConsulta($e->getMessage());
        } catch (GuzzleException $e) {
            throw ConsultaCepException::erroNaConsulta($e->getMessage());
        }
    }

    /**
     * Consulta o prazo de entrega
     *
     * @param string $cepOrigem CEP de origem
     * @param string $cepDestino CEP de destino
     * @param string $coProduto Código do produto
     * @return array
     * @throws CalculoFreteException
     */
    public function consultarPrazo(string $cepOrigem, string $cepDestino, string $coProduto): array
    {
        try {
            $token = $this->getToken();
            $response = $this->client->get($this->baseUrl . '/prazo/v1/nacional/' . $coProduto, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token['token'],
                    'Accept' => 'application/json'
                ],
                'query' => [
                    'cepOrigem' => $cepOrigem,
                    'cepDestino' => $cepDestino
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            throw CalculoFreteException::erroNoCalculo($responseBody);
        } catch (GuzzleException $e) {
            throw CalculoFreteException::erroNoCalculo($e->getMessage());
        }
    }

    /**
     * Rastreia uma encomenda
     *
     * @param string $codigoObjeto Código de rastreamento
     * @param string $resultado Tipo de resultado
     * @return array
     * @throws CalculoFreteException
     */
    public function rastrearEncomenda(string $codigoObjeto, string $resultado = 'T'): array
    {
        try {
            $token = $this->getToken();
            $response = $this->client->get($this->baseUrl . '/srorastro/v1/objetos/' . $codigoObjeto, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token['token'],
                    'Accept' => 'application/json'
                ],
                'query' => [
                    'resultado' => $resultado
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            throw CalculoFreteException::erroNoCalculo($responseBody);
        } catch (GuzzleException $e) {
            throw CalculoFreteException::erroNoCalculo($e->getMessage());
        }
    }

    /**
     * Gera uma etiqueta para envio
     *
     * @param array $dados Dados para geração da etiqueta
     * @return string
     * @throws GuzzleException
     */
    public function gerarEtiqueta(array $dados): string
    {
        // TODO: Implementar geração de etiqueta
        return '';
    }
} 