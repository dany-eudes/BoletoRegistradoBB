<?php

namespace Vmartins;
    
/**
 * Class BoletoRegistradoBB
 *
 * Gera boleto registrado do Banco do Brasil.
 * Baseado em http://www.bb.com.br/docs/pub/emp/empl/dwn/Orientacoes.pdf (Versão 23.2 / Julho de 2016)
 */
class BoletoRegistradoBB
{
    /**
     * Código do convênio de Comércio Eletrônicofornecido pelo Banco.
     *
     * @var int
     */
    protected $idConv;

    /**
     * Número atribuído, gerado e controlado pelo Convenente, que identifica
     * o pedido de compra em todas as fases do processo de pagamento.
     * A cada nova transação deverá ser gerado outro número refTran,
     * não podendo ser reutilizado, inclusive os números utilizados nos testes.
     * As 17 posições são livres quando não houver o meio de pagamento de Cobrança
     * vinculado ao convênio de Comércio Eletrônico ou quando o convênio de cobrança
     * tiver 6 (seis) posições. Ex: Convênio de Cobrança nº 123456.
     * Caso possua convênio de cobrança com 7 (sete) posições vinculado ao convênio
     * de Comércio Eletrônico, solicite o número desse convênio para sua agência e
     * informe a refTran da seguinte forma:
     *    CCCCCCCNNNNNNNNNN, onde:
     *    CCCCCCC = número do convênio de cobrança
     *    NNNNNNNNNN = posições livres
     *
     * Ex: Convênio de Cobrança nº 1234567, variável refTran 12345671111111111
     * Importante: sempre que tiver Convênio de Cobrança de 7 posições a refTran
     * deverá seguir o padrão acima, mesmo que o meio de pagamento selecionado seja
     * débito em conta via internet ou BB Crediário.
     * 
     * @var int
     */
    protected $refTran;
    
    /**
     * Valor total da compra em Reais, com centavos, sem formatação.
     *   Exemplo: para R$ 195,72 informe 1957
     * 
     * @var int
     */
    protected $valor;
    
    /**
     * Variável exclusiva para o Programa de Relacionamento do BB.
     * Quantidade de pontos que serão resgatados no programa de Relacionamento.
     *
     * @var int
     */
    protected $qtdPontos;

    /**
     * Data de vencimento do pagamento, no formato DDMMAAAA.
     * OBS: Aceita data futura somente para emissão de Boleto Bancário.
     *
     * @var string
     */
    protected $dtVenc;

    /**
     * Conforme a modalidade de pagamento:
     *    0 - Todas as modalidades contratadas pelo convenente
     *    2 - Boleto bancário
     *    21 - 2ª Via de boleto bancário, já gerado anteriormente
     *    3 - Débito em Conta via Internet – PF e PJ
     *    5 - BB Crediário Internet
     *    7 - Débito em Conta via Internet PF
     *
     * @var int
     */
    protected $tpPagamento = 21;

    /**
     * É o número do CPF ou CNPJ do comprador.
     * Não deve ser informado com máscara (sinais de "." e/ou "-").
     * É obrigatório para emissão de boleto.
     *
     * @var int
     */
    protected $cpfCnpj;

    /**
     * Indica que o nº enviado na variável cpf/Cnpj é de
     * uma pessoa física = 1 ou uma pessoa jurídica = 2.
     * É obrigatório para emissão de boleto.
     *
     * @var int
     */
    protected $indicadorPessoa;

    /**
     * Valor do desconto em Reais, com centavos, sem formatação.
     * Exemplo: para R$ 45,26 informe 4526 
     * Utilizado opcionalmente para emissão de boletos.
     *
     * @var int
     */
    protected $valorDesconto;
    
    /**
     * Data de vencimento do pagamento, no formato DDMMAAAA.
     * Utilizado opcionalmente para emissão de boletos.
     * É obrigatório quanto informado valorDesconto.
     * Aceita data futura que pode ser menor ou igual a
     * data de vencimento do Boleto Bancário - variável dtVenc
     *
     * @var int
     */
    protected $dataLimiteDesconto;

    /**
     * Informa o tipo de título que originará o boleto:
     *   DM – Duplicata Mercantil – utilizado quando forem vendidas mercadorias/produtos;
     *   DS – Duplicata de serviços – quando a loja virtual vender a prestação de serviços.
     * É obrigatório para emissão de boleto que seja informado DM ou DS.
     *
     * @var string
     */
    protected $tpDuplicata = 'DM';

    /**
     * Complemento de endereço (URL) que será acionado, indicando que uma
     * transação foi finalizada no site do BB, cabendo ao convenente acionar
     * o Formulário Sonda para confirmar a liquidação financeira da compra
     * O endereço acionado (URL) é composto pela concatenação de duas partes:
     *    - parte1: cadastrada na agência. Exemplo: https://www.teste.com.br
     *    - parte2: será o complemento da parte fixa, que será informada nessa
     *              variável (urlInforma). Exemplo: "/InformaBB.asp?1358568"
     * Nesse exemplo, seria acionado o seguinte endereço:
     *    https://www.teste.com.br/InformaBB.asp?1358568
     * Importante: O acionamento do formulário Informa não significa, de maneira
     * alguma, a liquidação do compromisso. O convenente deverá acionar o formulário
     * Sonda para obter essa confirmação ou aguardar a disponibilização de arquivo
     * retorno no dia útil seguinte ao pagamento.
     *
     * @var string
     */
    protected $urlRetorno = '/';

    /**
     * Endereço (URL) para o qual o cliente será direcionado, através do formulário
     * Retorno, caso deseje voltar identificado ao site do convenente, a partir da
     * última página do processo de pagamento, clicando em botão disponível nessa página.
     * Composto pela concatenação de duas partes:
     *    - parte1: cadastrada na agência. Exemplo: https://www.teste.com.br
     *    - parte2: será o complemento da parte cadastrada na agência, informada nessa
     *              variável (urlRetorno). Exemplo: /RetornoBB.asp?1358568
     * Nesse exemplo, o cliente seria direcionado ao seguinte site:
     *    https://www.teste.com.br/RetornoBB.asp?1358568
     * Importante: O envio do formulário Retorno ao site especificado não significa,
     * de maneira alguma, a liquidação do compromisso. O convenente deverá acionar o
     * formulário Sonda para efetuar essa confirmação ou aguardar a disponibilização
     * de arquivo retorno no dia útil seguinte ao pagamento.
     *
     * @var string
     */
    protected $urlInforma = '/';

    /**
     * Nome do comprador, que será apresentado no boleto de cobrança.
     * São aceitos como caracteres válidos:
     * - as letras de A a Z (MAIÚSCULAS);
     * - caracteres especiais de conjunção: hífen (-), apóstrofo (').
     *   Quando utilizados não pode conter espaços entre as letras;
     *   Exemplos corretos: D'EL-REI, D'ALCORTIVO, SANT'ANA
     *   Exemplos incorretos: D'EL - REI
     * - até um espaço em branco entre palavras.
     *
     * @var string
     */
    protected $nome;

    /**
     * Endereço do comprador, que será apresentado no boleto de cobrança.
     * São aceitos como caracteres válidos:
     * - as letras de A a Z (MAIÚSCULAS);
     * - caracteres especiais de conjunção: hífen (-), apóstrofo (').
     *   Quando utilizados não pode conter espaços entre as letras;
     *   Exemplos corretos: D'EL-REI, D'ALCORTIVO, SANT'ANA
     *   Exemplos incorretos: D'EL - REI
     * - até um espaço em branco entre palavras.
     *
     * @var string
     */
    protected $endereco;

    /**
     * Cidade do comprador, que será apresentada no boleto de cobrança.
     * São aceitos como caracteres válidos:
     * - as letras de A a Z (MAIÚSCULAS);
     * - caracteres especiais de conjunção: hífen (-), apóstrofo (').
     *   Quando utilizados não pode conter espaços entre as letras;
     *   Exemplos corretos: D'EL-REI, D'ALCORTIVO, SANT'ANA
     *   Exemplos incorretos: D'EL - REI
     * - até um espaço em branco entre palavras.
     *
     * @var string
     */
    protected $cidade;

    /**
     * Estado do comprador, que será apresentado no boleto de cobrança.
     * Deve ser a UF correspondente ao Cep informado.
     *
     * @var string
     */
    protected $uf;

    /**
     * CEP do comprador, sem hífen, que será apresentado no boleto de cobrança.
     * É necessário ser um Cep válido (conforme www.correios.com.br)
     * e a variável UF deve corresponder a UF do Cep informado.
     *
     * @var string
     */
    protected $cep;

    /**
     * Instruções do cedente, que serão apresentadas no boleto de cobrança.
     *
     * @var string
     */
    protected $msgLoja;

    /**
     * Erros ocorridos ao enviar o formulário.
     *
     * @var array
     */
    protected $erros;

    /**
     * Tipo de envio utilizado na submissão do formulário de pagamento.
     *  - auto: escolha automática
     *  - html: via formulário HTML
     *  - curl: via requisição por cURL
     *
     * @var string
     */
    protected $tipoEnvio = 'auto';

    /**
     * URL de submissão do formulário do Banco do Brasil.
     *
     * @var string
     */
    protected $url = 'https://mpag.bb.com.br/site/mpag/';

    /**
     * Parâmetros de submissão do formulário do Banco do Brasil.
     *
     * @var array
     */
    protected $parametros = array();

    /**
     * Resultado da sumissão (disponível apenas para o tipo de envio por cURL).
     *
     * @var array
     */
    protected $resultado = array();

    /**
     * Obtém o código do convênio.
     *
     * @return int
     */
    public function getIdConv()
    {
        return $this->idConv;
    }

    /**
     * Define o código do convênio.
     *
     * @param int $idConv
     * @return BoletoRegistradoBB
     */
    public function setIdConv($idConv)
    {
        $this->idConv = $idConv;
        return $this;
    }

    /**
     * Obtém a referência da transação.
     *
     * @return string
     */
    public function getRefTran()
    {
        return str_pad($this->refTran, 17, "0", STR_PAD_LEFT);
    }

    /**
     * Obtém a referência da transação original.
     *
     * @return int
     */
    public function getRefTranOriginal()
    {
        return $this->refTran;
    }

    /**
     * Define a referência da transação.
     *
     * @param int $refTran
     * @return BoletoRegistradoBB
     */
    public function setRefTran($refTran)
    {
        $this->refTran = $refTran;
        return $this;
    }

    /**
     * Gera a referência da transação, utilizando o
     * "nosso número" e o número da carteira de cobrança.
     *
     * @param int $nossoNumero
     * @param int $carteiraCobranca
     * @return string
     */
    public function gerarRefTran($nossoNumero, $carteiraCobranca=null)
    {
        if (strlen($carteiraCobranca) == 7) {
            return $carteiraCobranca . str_pad($nossoNumero, 10, "0", STR_PAD_LEFT);
        }
        else {
            return str_pad($nossoNumero, 17, "0", STR_PAD_LEFT);
        }
    }

    /**
     * Obtém o valor.
     *
     * @return string
     */
    public function getValor()
    {
        if ($this->valor) {
            return str_replace(',', '', $this->valor);
        }

        return '';
    }

    /**
     * Obtém o valor original.
     *
     * @return int
     */
    public function getValorOriginal()
    {
        return $this->valor;
    }

    /**
     * Define o valor.
     *
     * @param int $valor
     * @return BoletoRegistradoBB
     */
    public function setValor($valor)
    {
        $this->valor = $valor;
        return $this;
    }

    /**
     * Obtém a quantidade de pontos.
     *
     * @return int
     */
    public function getQtdPontos()
    {
        return $this->qtdPontos;
    }

    /**
     * Define a quantidade de pontos.
     *
     * @param int $qtdPontos
     * @return BoletoRegistradoBB
     */
    public function setQtdPontos($qtdPontos)
    {
        $this->qtdPontos = $qtdPontos;
        return $this;
    }

    /**
     * Obtém a data de vencimento.
     *
     * @return string
     */
    public function getDtVenc()
    {
        if ($this->dtVenc) {
            return str_replace('/', '', $this->dtVenc);
        }

        return '';
    }

    /**
     * Obtém a data de vencimento original.
     *
     * @return string
     */
    public function getDtVencOriginal()
    {
        return $this->dtVenc;
    }

    /**
     * Define a data de vencimento.
     *
     * @param string $dtVenc
     * @return BoletoRegistradoBB
     */
    public function setDtVenc($dtVenc)
    {
        $this->dtVenc = $dtVenc;
        return $this;
    }

    /**
     * Obtém o tipo de pagamento.
     *
     * @return int
     */
    public function getTpPagamento()
    {
        return $this->tpPagamento;
    }

    /**
     * Define o tipo de pagamento.
     *
     * @param $tpPagamento
     * @return BoletoRegistradoBB
     */
    public function setTpPagamento($tpPagamento)
    {
        $this->tpPagamento = $tpPagamento;
        return $this;
    }

    /**
     * Obtém o CPF ou CNPJ.
     *
     * @return string
     */
    public function getCpfCnpj()
    {
        return preg_replace('/[^0-9]/', '', $this->cpfCnpj);
    }

    /**
     * Obtém o CPF ou CNPJ original.
     *
     * @return string
     */
    public function getCpfCnpjOriginal()
    {
        return $this->cpfCnpj;
    }

    /**
     * Define o CPF/CNPJ.
     *
     * @param string $cpfCnpj
     * @return BoletoRegistradoBB
     */
    public function setCpfCnpj($cpfCnpj)
    {
        $this->cpfCnpj = $cpfCnpj;
        return $this;
    }

    /**
     * Obtém o indicador da pessoa.
     *
     * @return int
     */
    public function getIndicadorPessoa()
    {
        $indicadorPessoa = $this->indicadorPessoa;

        if ($indicadorPessoa) {
            return (int) $indicadorPessoa;
        } else {
            $cpfCnpj = $this->getCpfCnpj();
            if (strlen($cpfCnpj) == 14) {
                return 2;
            } else {
                return 1; //Padrão=PF
            }
        }
    }

    /**
     * Define o indicador da pessoa.
     *
     * @param int $indicadorPessoa
     * @return BoletoRegistradoBB
     */
    public function setIndicadorPessoa($indicadorPessoa)
    {
        $this->indicadorPessoa = $indicadorPessoa;
        return $this;
    }

    /**
     * Obtém o valor de desconto.
     *
     * @return string
     */
    public function getValorDesconto()
    {
        if ($this->valorDesconto) {
            return str_replace(',', '', $this->valorDesconto);
        }

        return '';
    }

    /**
     * Obtém o valor de desconto original.
     *
     * @return int
     */
    public function getValorDescontoOriginal()
    {
        return $this->valorDesconto;
    }

    /**
     * Define o valor de desconto.
     *
     * @param int $valorDesconto
     * @return BoletoRegistradoBB
     */
    public function setValorDesconto($valorDesconto)
    {
        $this->valorDesconto = $valorDesconto;
        return $this;
    }

    /**
     * Obtém a data limite de desconto.
     *
     * @return string
     */
    public function getDataLimiteDesconto()
    {
        if ($this->dataLimiteDesconto) {
            return str_replace('/', '', $this->dataLimiteDesconto);
        }

        return '';
    }

    /**
     * Obtém a data limite de desconto original.
     *
     * @return int
     */
    public function getDataLimiteDescontoOriginal()
    {
        return $this->dataLimiteDesconto;
    }

    /**
     * Define a data limite de desconto.
     *
     * @param int $dataLimiteDesconto
     * @return BoletoRegistradoBB
     */
    public function setDataLimiteDesconto($dataLimiteDesconto)
    {
        $this->dataLimiteDesconto = $dataLimiteDesconto;
        return $this;
    }

    /**
     * Obtém o tipo de duplicata.
     *
     * @return string
     */
    public function getTpDuplicata()
    {
        return $this->tpDuplicata;
    }

    /**
     * Define o tipo de duplicata.
     *
     * @param $tpDuplicata
     * @return $this
     */
    public function setTpDuplicata($tpDuplicata)
    {
        $this->tpDuplicata = $tpDuplicata;
        return $this;
    }

    /**
     * Obtém a URL de retorno.
     *
     * @return string
     */
    public function getUrlRetorno()
    {
        return $this->urlRetorno;
    }

    /**
     * Define a URL de retorno.
     *
     * @param string $urlRetorno
     * @return BoletoRegistradoBB
     */
    public function setUrlRetorno($urlRetorno)
    {
        $this->urlRetorno = $urlRetorno;
        return $this;
    }

    /**
     * Obtém a URL informa.
     *
     * @return string
     */
    public function getUrlInforma()
    {
        return $this->urlInforma;
    }

    /**
     * Define a URL informa.
     *
     * @param string $urlInforma
     * @return BoletoRegistradoBB
     */
    public function setUrlInforma($urlInforma)
    {
        $this->urlInforma = $urlInforma;
        return $this;
    }

    /**
     * Obtém o nome.
     *
     * @return string
     */
    public function getNome()
    {
        return $this->alfa($this->nome);
    }

    /**
     * Obtém nome original.
     *
     * @return string
     */
    public function getNomeOriginal()
    {
        return $this->nome;
    }

    /**
     * Define o nome.
     *
     * @param string $nome
     * @return BoletoRegistradoBB
     */
    public function setNome($nome)
    {
        $this->nome = $nome;
        return $this;
    }

    /**
     * Obtém o endereço.
     *
     * @return string
     */
    public function getEndereco()
    {
        return $this->alfa($this->endereco);
    }

    /**
     * Obtém endereço original.
     *
     * @return string
     */
    public function getEnderecoOriginal()
    {
        return $this->endereco;
    }

    /**
     * Define o endereço.
     *
     * @param string $endereco
     * @return BoletoRegistradoBB
     */
    public function setEndereco($endereco)
    {
        $this->endereco = $endereco;
        return $this;
    }

    /**
     * Obtém a cidade.
     *
     * @return string
     */
    public function getCidade()
    {
        return $this->alfa($this->cidade);
    }

    /**
     * Obtém a cidade original.
     *
     * @return string
     */
    public function getCidadeOriginal()
    {
        return $this->cidade;
    }

    /**
     * Define a cidade.
     *
     * @param string $cidade
     * @return BoletoRegistradoBB
     */
    public function setCidade($cidade)
    {
        $this->cidade = $cidade;
        return $this;
    }

    /**
     * Obtém a unidade federativa.
     *
     * @return string
     */
    public function getUf()
    {
        return $this->uf;
    }

    /**
     * Define a unidade federativa.
     *
     * @param string $uf
     * @return BoletoRegistradoBB
     */
    public function setUf($uf)
    {
        $this->uf = $uf;
        return $this;
    }

    /**
     * Obtém o CEP.
     *
     * @return string
     */
    public function getCep()
    {
        return preg_replace('/[^0-9]/', '', $this->cep);
    }
    
    /**
     * Obtém o CEP original.
     *
     * @return string
     */
    public function getCepOriginal()
    {
        return $this->cep;
    }

    /**
     * Define o CEP.
     *
     * @param string $cep
     * @return BoletoRegistradoBB
     */
    public function setCep($cep)
    {
        $this->cep = $cep;
        return $this;
    }

    /**
     * Obtém a mensagem da loja.
     *
     * @return string
     */
    public function getMsgLoja()
    {
        return $this->msgLoja;
    }

    /**
     * Define a mensagem da loja.
     *
     * @param string $msgLoja
     * @return BoletoRegistradoBB
     */
    public function setMsgLoja($msgLoja)
    {
        $this->msgLoja = $msgLoja;
        return $this;
    }

    /**
     * Obtém os erros.
     *
     * @return array
     */
    public function getErros()
    {
        return $this->erros;
    }

    /**
     * Verifica se existem erros.
     *
     * @return bool
     */
    public function hasErros()
    {
        $erros = $this->getErros();
        return !empty($erros);
    }

    /**
     * Define os erros.
     *
     * @param array $erros
     * @return BoletoRegistradoBB
     */
    protected function setErros($erros)
    {
        $this->erros = $erros;
        return $this;
    }

    /**
     * Acrecenta um erro aos erros.
     *
     * @param string $erro
     * @return BoletoRegistradoBB
     */
    protected function appendErro($erro)
    {
        $this->erros[] = $erro;
        return $this;
    }

    /**
     * Obtém o tipo de envio do formulário.
     *
     * @return string
     */
    public function getTipoEnvio()
    {
        return $this->tipoEnvio;
    }

    /**
     * Define o tipo de envio utilizado para submter
     * o formulário de pagamento.
     *
     * @param string $tipoEnvio
     * @return BoletoRegistradoBB
     */
    public function setTipoEnvio($tipoEnvio)
    {
        $tipos = ['auto', 'html', 'curl', 'file_get_contents'];
        $tipoEnvio = strtolower($tipoEnvio);
        if (in_array($tipoEnvio, $tipos)) {
            $this->tipoEnvio = $tipoEnvio;
        }

        return $this;
    }

    /**
     * Obtém a URL de submissão.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Define a URL de submissão.
     *
     * @param string $url
     * @return BoletoRegistradoBB
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Obtém os parâmetros de submissão.
     *
     * @return array
     */
    public function getParametros()
    {
        return $this->parametros;
    }

    /**
     * Define os parâmetros de submissão.
     *
     * @param array $parametros
     * @return BoletoRegistradoBB
     */
    public function setParametros($parametros)
    {
        $this->parametros = $parametros;
        return $this;
    }

    /**
     * Obtém o resultado da submissão via cURL.
     *
     * @return array
     */
    public function getResultado()
    {
        return $this->resultado;
    }

    /**
     * Define o resutado da submissão via cURL.
     *
     * @param array $resultado
     * @return BoletoRegistradoBB
     */
    public function setResultado($resultado)
    {
        $this->resultado = $resultado;

        if (empty($resultado['content'])) {
            $this->appendErro('Nenhum conteúdo obtido na requisição!');
        } else {
            if ($resultado['content_type'] != 'application/pdf') {
                preg_match_all("#<\s*?li\b[^>]*>(.*?)</li\b[^>]*>#s", $resultado['content'], $matches);
                if (isset($matches[1]) && !empty($matches[1])) {
                    $erros = array_map('strip_tags', $matches[1]);
                    $this->setErros($erros);
                }
            }
        }

        return $this;
    }

    /**
     * Formata uma string do tipo 'Alfa' para o padrão aceito pelo Banco do Brasil.
     *
     *  - letras de A a Z (MAIÚSCULAS);
     *  - caracteres especiais de conjunção: hífen (-), apóstrofo (')
     *    Quando utilizados não pode conter espaços entre as letras;
     *    Exemplos corretos: D'EL-REI, D'ALCORTIVO, SANT'ANA
     *    Exemplos incorretos: D'EL - REI
     *  - até um espaço em branco entre palavras.
     *
     * @param string $texto
     * @return string
     */
    private function alfa($texto)
    {
        $textoFormatado = $texto;
        $textoFormatado = preg_replace('/\s+/', ' ', $textoFormatado); //remove os múltiplos espaços em branco
        $textoFormatado = str_replace('- ', '-', $textoFormatado); //remove os espaços entre os hífens
        $textoFormatado = str_replace(' -', '-', $textoFormatado); //remove os espaços entre os hífens
        $textoFormatado = str_replace(" '", "'", $textoFormatado); //remove os espaços entre os apóstrofos
        $textoFormatado = str_replace("' ", "'", $textoFormatado); //remove os espaços entre os apóstrofos
        $textoFormatado = mb_strtoupper($textoFormatado); //converte para maiúsculas

        return $textoFormatado;
    }

    /**
     * Processa o Boleto Registrado.
     *
     * @return BoletoRegistradoBB
     */
    public function processar()
    {
        $this->setParametros(array(
            'idConv'                => $this->getIdConv(),
            'refTran'               => $this->getRefTran(),
            'valor'                 => $this->getValor(),
            'qtdPontos'             => $this->getQtdPontos(),
            'dtVenc'                => $this->getDtVenc(),
            'tpPagamento'           => $this->getTpPagamento(),
            'cpfCnpj'               => $this->getCpfCnpj(),
            'indicadorPessoa'       => $this->getIndicadorPessoa(),
            'valorDesconto'         => $this->getValorDesconto(),
            'dataLimiteDesconto'    => $this->getDataLimiteDesconto(),
            'tpDuplicata'           => $this->getTpDuplicata(),
            'urlRetorno'            => $this->getUrlRetorno(),
            'urlInforma'            => $this->getUrlInforma(),
            'nome'                  => $this->getNome(),
            'endereco'              => $this->getEndereco(),
            'cidade'                => $this->getCidade(),
            'uf'                    => $this->getUf(),
            'cep'                   => $this->getCep(),
            'msgLoja'               => $this->getMsgLoja()
        ));

        if ($this->getTipoEnvio() == 'html') {
            return $this->enviarFormularioViaHtml();
        } else if ($this->getTipoEnvio() == 'curl') {
            return $this->enviarFormularioViaCurl();
        } else if ($this->getTipoEnvio() == 'file_get_contents') {
            return $this->enviarFormularioViaFileGetContents();
        } else { //auto
            if (extension_loaded('curl')) {
                return $this->enviarFormularioViaCurl();
            } else if (ini_get('allow_url_fopen')) {
                return $this->enviarFormularioViaFileGetContents();
            } else {
                return $this->enviarFormularioViaHtml();
            }
        }
    }

    /**
     * Monta e submete o Formulário de Pagamento do Banco do Brasil
     * (via HTML).
     *
     * return void
     */
    protected function enviarFormularioViaHtml()
    {
        $url = $this->getUrl();
        $parametros = $this->getParametros();

        $inputs = '';
        foreach ($parametros as $nome => $valor) {
            $valor = utf8_decode($valor);

            $inputs .= "<input type='hidden' name='{$nome}' value='{$valor}'>";
        }

        $info = [];
        $info['content_type'] = 'text/html; charset=ISO-8859-1';
        $info['content'] = <<<END
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta charset="utf-8"> <script type="text/javascript">function gerar(){document.forms["redirecionar_via_post"].submit();} setTimeout(function(){document.getElementById('redirecionar').style.display='inline';document.getElementById('gerando').style.display='none';},5000);</script> <style>.centerDiv{position:absolute;top:50%;left:50%;width:520px;margin-left:-260px;height:50px;margin-top:-25px;border-radius:5px;background:#ccc;padding:10px}h1{text-align:center;margin-top:3px}#redirecionar{font-size:1em;margin-top:-5px;display:none}p{text-align:center}#circleG{width:45px;margin:auto}.circleG{background-color:rgb(255,255,255);float:left;height:10px;margin-left:5px;width:10px;animation-name:bounce_circleG;-o-animation-name:bounce_circleG;-ms-animation-name:bounce_circleG;-webkit-animation-name:bounce_circleG;-moz-animation-name:bounce_circleG;animation-duration:2.24s;-o-animation-duration:2.24s;-ms-animation-duration:2.24s;-webkit-animation-duration:2.24s;-moz-animation-duration:2.24s;animation-iteration-count:infinite;-o-animation-iteration-count:infinite;-ms-animation-iteration-count:infinite;-webkit-animation-iteration-count:infinite;-moz-animation-iteration-count:infinite;animation-direction:normal;-o-animation-direction:normal;-ms-animation-direction:normal;-webkit-animation-direction:normal;-moz-animation-direction:normal;border-radius:6px;-o-border-radius:6px;-ms-border-radius:6px;-webkit-border-radius:6px;-moz-border-radius:6px}#circleG_1{animation-delay:0.45s;-o-animation-delay:0.45s;-ms-animation-delay:0.45s;-webkit-animation-delay:0.45s;-moz-animation-delay:0.45s}#circleG_2{animation-delay:1.05s;-o-animation-delay:1.05s;-ms-animation-delay:1.05s;-webkit-animation-delay:1.05s;-moz-animation-delay:1.05s}#circleG_3{animation-delay:1.35s;-o-animation-delay:1.35s;-ms-animation-delay:1.35s;-webkit-animation-delay:1.35s;-moz-animation-delay:1.35s}@keyframes bounce_circleG{0%{}50%{background-color:rgb(0,0,0)}100%{}}@-o-keyframes bounce_circleG{0%{}50%{background-color:rgb(0,0,0)}100%{}}@-ms-keyframes bounce_circleG{0%{}50%{background-color:rgb(0,0,0)}100%{}}@-webkit-keyframes bounce_circleG{0%{}50%{background-color:rgb(0,0,0)}100%{}}@-moz-keyframes bounce_circleG{0%{}50%{background-color:rgb(0,0,0)}100%{}}</style></head><body onload="gerar();"><div id="centerDiv" class="centerDiv"><h1> <span id="gerando"> Gerando boleto<div id="circleG"><div id="circleG_1" class="circleG"></div><div id="circleG_2" class="circleG"></div><div id="circleG_3" class="circleG"></div></div> </span>
<form name="redirecionar_via_post" method="post" action="$url">$inputs <input type="submit" onClick="gerar()" value="Clique aqui para gerar o boleto" id="redirecionar" /></form></h1></div></body></html>
END;
        $this->setResultado($info);

        return $this;
    }

    /**
     * Monta e submete o Formulário de Pagamento do Banco do Brasil
     * (via curl).
     *
     * @return BoletoRegistradoBB
     */
    protected function enviarFormularioViaCurl()
    {
        if (!extension_loaded('curl')) {
            $this->appendErro('Biblioteca curl não instalada!');
            return false;
        }

        $url = $this->getUrl();
        $parametros = $this->getParametros();

        $ch = curl_init();
        $campos = '';
        foreach($parametros as $nome => $valor) {
            $campos .= $nome . "=" . utf8_decode($valor) . "&";
        }

        curl_setopt($ch, CURLOPT_URL,               $url);
        curl_setopt($ch, CURLOPT_POST,              1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,        $campos);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    3);
        curl_setopt($ch, CURLOPT_TIMEOUT,           20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,    0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,    0);
        curl_setopt($ch, CURLOPT_HTTPHEADER,        array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,    1);
        curl_setopt($ch, CURLOPT_USERAGENT,         $_SERVER['HTTP_USER_AGENT']);

        $resposta = curl_exec($ch);
        $info = curl_getinfo($ch);
        $erro = curl_error($ch);
        curl_close ($ch);

        if ($erro) {
            $this->appendErro($erro);
        }

        $info['content'] = $resposta;
        $this->setResultado($info);

        return $this;
    }

    /**
     * Monta e submete o Formulário de Pagamento do Banco do Brasil
     * (via file_get_contents).
     *
     * @param bool $exibir Via cURL, indica se irá exibir ou retornar o resultado
     * @return void|bool|array
     */
    protected function enviarFormularioViaFileGetContents($exibir=false)
    {
        if (!ini_get('allow_url_fopen')) {
            $this->appendErro('Diretiva allow_url_fopen não habilitada!');
            return false;
        }

        $url = $this->getUrl();
        $parametros = $this->getParametros();

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        });

        try {
            $resposta = file_get_contents($url, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'timeout' => 20,
                    'header' => ['Content-Type: application/x-www-form-urlencoded'],
                    'content' => http_build_query($parametros),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'follow_location' => 1,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]));
        } catch (\Exception $e) {
            $this->appendErro($e->getMessage());
        }

        restore_error_handler();

        $info = array_reduce($http_response_header, function($info, $item) {
            list($nome, $valor) = explode(':', $item, 2);
            if ($valor) {
                $i = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '_', preg_replace('/\s+/u', '', ucwords($nome))));
                $info[$i] = trim($valor);
            }
            return $info;
        }, []);

        $info['content'] = $resposta;
        $this->setResultado($info);

        return $this;
    }

    /**
     * Exibe a resposta da requisição.
     * 
     * @return void
     */
    public function exibir()
    {
        $resultado = $this->getResultado();

        if (array_key_exists('download_content_length', $resultado)) {
            $tamanho = $resultado['download_content_length'];
        } else if (array_key_exists('content_length', $resultado)) {
            $tamanho = $resultado['download_content_length'];
        } else {
            $tamanho = 0;
        }

        ob_clean();
        header("Content-type: {$resultado['content_type']}");
        if ($tamanho) {
            header("Content-Length: {$tamanho}");
        }

        if ($resultado['content_type'] == 'application/pdf') {
            header('Content-Disposition: inline; filename="boleto.pdf"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
        }

        exit($resultado['content']);
    }
}
