<?php

namespace Hcode;

use Rain\tpl;

class Page {

	private $tpl;
	private $options = [];
	private $defaults = [
		"data"=>[]
	];
 
	public function __construct($opts = array()){
		
		/*
	      array_merge: Funde os elementos de dois ou mais arrays de forma que os elementos de um são 
		  colocados no final do array anterior. Retorna o array resultante da fusão
		*/
		$this->$options = array_merge($this->defaults, $opts);
		$config = array(
						//Pega os arquivos HTML
						"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/", 
						"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
						"debug"         => false // set to false to improve the speed
					   );

		Tpl::configure($config);

		$this->tpl = new Tpl;
		$this->setData($this->options["data"]);
		$this->tpl->draw("header");
	}

	public function setTpl($name, $data = array(), $returnHTML = false){

		$this->setData($data)

		return $this->tpl->draw($name, $returnHTML);

	}

	private function setData($data = array()){
		
		foreach ($data as $key => $value) {
			$this->tpl->assign($key, $value);
		}

	}

	public function __destruct(){

		$thistpl->draw("footer");

	}
}

?>