<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model {

	const SESSION_ERROR = "AddressError";

	public static function getCEP($nrcep){

		//Retirar o -, pois precisamos enviar somente números para o WS
		$nrcep = str_replace("-", "", $nrcep);
		
		$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$nrcep/json/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		//JSON_ENCODE - Retorna um array ao invés do JSON
		$data = json_decode(curl_exec($ch), true);
		/*
		Senão fechar o curl, toda vez que dar um F5, que chamar o método ele ficará criando mais instâncias na memória ficando pesado.
		*/
		curl_close($ch);

		return $data;
	}

	public function loadfromCEP($nrcep){

		$data = Address::getCEP($nrcep);

		if (isset($data['logradouro']) && $data['logradouro']){

			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setnrzipcode($nrcep);

		}
	}

	public function save(){

		$sql = new Sql();
		$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", [
			':idaddress'=>$this->getidaddress(),
			':idperson'=>$this->getidperson(),
			':desaddress'=>utf8_decode($this->getdesaddress()),
			':desnumber'=>$this->getdesnumber(),
			':descomplement'=>$this->getdescomplement(),
			':descity'=>$this->getdescity(),
			':desstate'=>$this->getdesstate(),
			':descountry'=>$this->getdescountry(),
			':deszipcode'=>$this->getdeszipcode(),
			':desdistrict'=>$this->getdesdistrict(),
		]);

		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	public static function setMsgErro($msg){

		$_SESSION[Address::SESSION_ERROR]  = $msg;

	}

	public static function getMsgError(){

		return (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";
		Address::clearMsgError();
		return $msg;

	}

	public static function clearMsgError(){

		$_SESSION[Address::SESSION_ERROR] = NULL;

	}	
}

?>