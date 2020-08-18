<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Order extends Model{

	public function save(){

		$sql = new Sql();
								 
		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
			':idorder'=>$this->getidorder(),
			':idcart'=>$this->getidcart(),
			':iduser'=>$this->getiduser(),
			':idstatus'=>$this->getidstatus(),
			':idaddress'=>$this->getidaddress(),
			':vltotal'=>$this->getvltotal(),
		]);

		if (count($results) > 0){
			$this->setData($results[0]);
		}
	}

	public function get($idorder){

		$sql = new Sql();

		$results = $sql->select("
						SELECT *
						FROM tb_orders A
						INNER JOIN tb_ordersstatus B USING(IDSTATUS)
						INNER JOIN tb_carts C USING(IDCART)
						INNER JOIN TB_USERS D ON D.IDUSER = A.iduser
						INNER JOIN tb_addresses E USING(IDADDRESS)
						INNER JOIN tb_persons F ON F.idperson = D.idperson
						WHERE A.idorder = :idorder
						", [
							  ':idorder'=>$idorder
						]);

		if (count($results) > 0){
			$this->setData($results[0]);
		}
	}	

}

?>