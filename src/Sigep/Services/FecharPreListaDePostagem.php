<?php
namespace PhpSigep\Services;

use Sigep\Model\Destinatario;
use Sigep\Model\Destino;
use Sigep\Model\DestinoInternacional;
use Sigep\Model\DestinoNacional;
use Sigep\Model\Dimensao;
use Sigep\Model\ObjetoPostal;
use Sigep\Model\PreListaDePostagem;
use Sigep\Model\ServicoAdicional;

/**
 * @author: Stavarengo
 */
class FecharPreListaDePostagem
{

	/**
	 * @param PreListaDePostagem $params
	 *
	 * @return int
	 */
	public function execute(\PhpSigep\Model\PreListaDePostagem $params)
	{

		$soap = SoapClient::getInstance();
		return $soap->fechaPlpVariosServicos($params, $this->getPlpXml($params));
	}

	private function getPlpXml(PreListaDePostagem $data)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndentString("");
		$writer->setIndent(false);
		if (isset($_GET['xml'])) {
//		$writer->setIndentString("   ");
//		$writer->setIndent(true);
		}
		$writer->startDocument('1.0', 'ISO-8859-1');

		$writer->startElement('correioslog');
		$writer->writeElement('tipo_arquivo', 'Postagem');
		$writer->writeElement('versao_arquivo', '2.3');
		$this->writePlp($writer, $data);
		$this->writeRemetente($writer, $data);
		$this->writeFormaPagamento($writer, $data);
		foreach ($data->getEncomendas() as $objetoPostal) {
			$this->writeObjetoPostal($writer, $objetoPostal);
		}
		$writer->endElement();

		return $writer;
	}

	private function writePlp(\XMLWriter $writer, PreListaDePostagem $data)
	{
		$writer->startElement('plp');
		$writer->writeElement('id_plp');
		$writer->writeElement('valor_global');
		$writer->writeElement('mcu_unidade_postagem');
		$writer->writeElement('nome_unidade_postagem');
		$writer->writeElement('cartao_postagem', $data->getAccessData()->getCartaoPostagem());
		$writer->endElement();
	}

	private function writeRemetente(\XMLWriter $writer, PreListaDePostagem $data)
	{
		$writer->startElement('remetente');
		$writer->writeElement('numero_contrato', $data->getAccessData()->getNumeroContrato());
		$writer->writeElement('numero_diretoria', $data->getRemetente()->getDiretoria());
		$writer->writeElement('codigo_administrativo', $data->getAccessData()->getCodAdministrativo());
		$writer->startElement('nome_remetente');
		$writer->writeCData($this->_($data->getRemetente()->getNome(), 50));
		$writer->endElement();
		$writer->startElement('logradouro_remetente');
		$writer->writeCdata($this->_($data->getRemetente()->getLogradouro(), 40));
		$writer->endElement();
		$writer->startElement('numero_remetente');
		$numero_remetente = $data->getRemetente()->getNumero();
		$writer->writeCdata($this->_(($numero_remetente ? $numero_remetente : 's/n'), 6));
		$writer->endElement();
		$writer->startElement('complemento_remetente');
		$writer->writeCdata($this->_($data->getRemetente()->getComplemento(), 20));
		$writer->endElement();
		$writer->startElement('bairro_remetente');
		$writer->writeCdata($this->_($data->getRemetente()->getBairro(), 20));
		$writer->endElement();
		$writer->startElement('cep_remetente');
		$writer->writeCdata($this->_(preg_replace('/[^\d]/', '', $data->getRemetente()->getCep()), 8));
		$writer->endElement();
		$writer->startElement('cidade_remetente');
		$writer->writeCdata($this->_($data->getRemetente()->getCidade(), 30));
		$writer->endElement();
		$writer->writeElement('uf_remetente', $this->_($data->getRemetente()->getUf(), 2, false));
		$writer->startElement('telefone_remetente');
		$writer->writeCdata($this->_(preg_replace('/[^\d]/', '', $data->getRemetente()->getTelefone()), 12));
		$writer->endElement();
		$writer->startElement('fax_remetente');
		$writer->writeCdata($this->_(preg_replace('/[^\d]/', '', $data->getRemetente()->getFax()), 12));
		$writer->endElement();
		$writer->startElement('email_remetente');
		$writer->writeCdata($this->_($data->getRemetente()->getEmail(), 50));
		$writer->endElement();
		$writer->endElement();
	}

	private function writeFormaPagamento(\XMLWriter $writer, PreListaDePostagem $data)
	{
		$writer->writeElement('forma_pagamento');
	}

	private function writeObjetoPostal(\XMLWriter $writer, ObjetoPostal $objetoPostal)
	{
		$writer->startElement('objeto_postal');
		$writer->writeElement('numero_etiqueta', $objetoPostal->getEtiqueta()->getEtiquetaComDv());
		$writer->writeElement('codigo_objeto_cliente');
		$writer->writeElement('codigo_servico_postagem', $objetoPostal->getServicoDePostagem()->getCodigo());
		$writer->writeElement('cubagem', (float)$objetoPostal->getCubagem());
		$writer->writeElement('peso', (float)$objetoPostal->getPeso() * 1000);
		$writer->writeElement('rt1');
		$writer->writeElement('rt2');
		$this->writeDestinatario($writer, $objetoPostal->getDestinatario());
		$this->writeDestino($writer, $objetoPostal->getDestino());
		$this->writeServicoAdicional($writer, (array)$objetoPostal->getServicosAdicionais());
		$this->writeDimensaoObjeto($writer, $objetoPostal->getDimensao());
		$writer->writeElement('data_postagem_sara');
		$writer->writeElement('status_processamento', 0);
		$writer->writeElement('numero_comprovante_postagem');
		$writer->writeElement('valor_cobrado');
		$writer->endElement();
	}

	private function _($str, $maxLength, $cdata = true, $trim = true)
	{
		if ($str === null) {
			return $str;
		}
		if ($trim) {
			$str = trim($str);
		}
		if ($maxLength) {
			$str = substr($str, 0, $maxLength);
		}
		if ($cdata) {
			//$str = $this->getCData($str);
		}
		return $str;
	}

	private function getCData($str)
	{
		return "<![CDATA[$str]]>";
	}

	private function writeDestinatario(\XMLWriter $writer, Destinatario $destinatario)
	{
		$writer->startElement('destinatario');
		$writer->startElement('nome_destinatario');
		$writer->writeCdata($this->_($destinatario->getNome(), 50));
		$writer->endElement();
		$writer->startElement('telefone_destinatario');
		$writer->writeCdata($this->_(preg_replace('/[^\d]/', '', $destinatario->getTelefone()), 12));
		$writer->endElement();
		$writer->startElement('celular_destinatario');
		$writer->writeCdata($this->_(preg_replace('/[^\d]/', '', $destinatario->getCelular()), 12));
		$writer->endElement();
		$writer->startElement('email_destinatario');
		$writer->writeCdata($this->_($destinatario->getEmail(), 50));
		$writer->endElement();
		$writer->startElement('logradouro_destinatario');
		$writer->writeCdata($this->_($destinatario->getLogradouro(), 50));
		$writer->endElement();
		$writer->startElement('complemento_destinatario');
		$writer->writeCdata($this->_($destinatario->getComplemento(), 30));
		$writer->endElement();
		$writer->startElement('numero_end_destinatario');
		$writer->writeCdata($this->_($destinatario->getNumero(), 6));
		$writer->endElement();
		$writer->endElement();
	}

	private function writeDestino(\XMLWriter $writer, Destino $destino)
	{
		if ($destino instanceof DestinoNacional) {
			$writer->startElement('nacional');
			$writer->startElement('bairro_destinatario');
			$writer->writeCdata($this->_($destino->getBairro(), 30));
			$writer->endElement();
			$writer->startElement('cidade_destinatario');
			$writer->writeCdata($this->_($destino->getCidade(), 30));
			$writer->endElement();
			$writer->writeElement('uf_destinatario', $this->_($destino->getUf(), 2, false));
			$writer->startElement('cep_destinatario');
			$writer->writeCdata($this->_(preg_replace('/[^\d]/', '', $destino->getCep()), 8));
			$writer->endElement();
			$writer->writeElement('codigo_usuario_postal');
			$writer->writeElement('centro_custo_cliente');
			$writer->writeElement('numero_nota_fiscal', $destino->getNumeroNotaFiscal());
			$writer->writeElement('serie_nota_fiscal', $this->_($destino->getSerieNotaFiscal(), 20));
			$writer->writeElement('valor_nota_fiscal', $destino->getValorNotaFiscal());
			$writer->writeElement('natureza_nota_fiscal', $this->_($destino->getNaturezaNotaFiscal(), 20));
			$writer->startElement('descricao_objeto');
			$writer->writeCdata($this->_($destino->getDescricaoObjeto(), 20));
			$writer->endElement();
			$writer->writeElement('valor_a_cobrar', (float)$destino->getValorACobrar());
			$writer->endElement();
		} else if ($destino instanceof DestinoInternacional) {
			$writer->startElement('internacional');
			$writer->endElement();
		}
	}

	/**
	 * @param \XMLWriter $writer
	 * @param ServicoAdicional[] $servicosAdicionais
	 */
	private function writeServicoAdicional(\XMLWriter $writer, array $servicosAdicionais)
	{
		$writer->startElement('servico_adicional');

		// De acordo com o manual este serviço é obrigatório 
		$writer->writeElement('codigo_servico_adicional', ServicoAdicional::SERVICE_REGISTRO);

		foreach ($servicosAdicionais as $servicoAdicional) {
			if ($servicoAdicional->getCodigoServicoAdicional() != ServicoAdicional::SERVICE_REGISTRO) {
				$writer->writeElement('codigo_servico_adicional', $servicoAdicional->getCodigoServicoAdicional());
				if ($servicoAdicional->getCodigoServicoAdicional() == ServicoAdicional::SERVICE_VALOR_DECLARADO()) {
					$writer->writeElement('valor_declarado', (float)$servicoAdicional->getValorDeclarado());
				}
			}
		}
		$writer->writeElement('valor_declarado');

		$writer->endElement();
	}

	private function writeDimensaoObjeto(\XMLWriter $writer, Dimensao $dimensao)
	{
		$writer->startElement('dimensao_objeto');
		$writer->writeElement('tipo_objeto', $dimensao->getTipo());
		$writer->writeElement('dimensao_altura', $dimensao->getAltura());
		$writer->writeElement('dimensao_largura', $dimensao->getLargura());
		$writer->writeElement('dimensao_comprimento', $dimensao->getComprimento() + 10);
		if (!$dimensao->getDiametro()) {
			$writer->writeElement('dimensao_diametro', 0);
		} else {
			$writer->writeElement('dimensao_diametro', $dimensao->getDiametro());
		}
		$writer->endElement();
	}
}