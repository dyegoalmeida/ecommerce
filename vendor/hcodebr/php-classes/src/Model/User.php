<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {

	const SESSION 		 = "User";
	const SECRET  		 = "HcodePhp7_Secret";
	const ERROR   		 = "UserError";
	const ERROR_REGISTER = "UserErrorRegister";
	const SUCCESS = "UserSucess";

	public static function getFromSession(){

		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {

			$user = new User();
			$user->setData($_SESSION[User::SESSION]);

		}

		return $user;
	}

	public static function checkLogin($inadmin = true){

		if (
			//Se a const SESSION não for definida
			!isset($_SESSION[User::SESSION])
			||
			//Se a const SESSION for vazia
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {

			return false;

		} else {

			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){

				return true;

			} else if ($inadmin === false) {

				return true;

			} else {

				return false;
			}
		}
	}

	public static function login($login, $password){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a 
			INNER JOIN tb_persons b ON a.idperson = b.idperson
			WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0){
				throw new \Exception("Usuário inexistente ou senha inválida!");
		}
		
		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true){
			
			$user = new User();
			
			$data['desperson'] = utf8_encode($data['desperson']);

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues() ;

			return $user;

		} else {
			throw new \Exception("Usuário inexistente ou senha inválida!");
		}
	}	

	public static function verifyLogin($inadmin = true){
		           
		if (!User::checkLogin($inadmin)){
			
			if ($inadmin) {
				header("Location: /admin/login");	
			} else {
				header("Location: login");	
			}
			exit;
		}
	}

	public static function logout(){

		$_SESSION[User::SESSION] = NULL;

	}

	public static function listAll(){

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a 
			                 INNER JOIN tb_persons b USING(idperson) 
			                 ORDER BY b.desperson");

	}

	public function get($iduser)
	{
	 
		$sql = new Sql();
		 
		$results = $sql->select("SELECT * FROM tb_users a 
		   					     INNER JOIN tb_persons b USING(idperson)
		 						 WHERE a.iduser = :iduser;", array(":iduser"=>$iduser));
		 
 		$data = $results[0];
    	$data['desperson'] = utf8_encode($data['desperson']);
		$this->setData($data);

	}

	public function save() {

		$sql = new Sql();

		/*
		Ordem dos parametros da procedure
		pdesperson VARCHAR(64), 
		pdeslogin VARCHAR(64), 
		pdespassword VARCHAR(256), 
		pdesemail VARCHAR(128), 
		pnrphone BIGINT, 
		pinadmin TINYINT
		*/
		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, 											:desemail, :nrphone, :inadmin)", array(
									":desperson"=>utf8_decode($this->getdesperson()),
									":deslogin"=>$this->getdeslogin(),
									":despassword"=>User::getPasswordHash($this->getdespassword()),
									":desemail"=>$this->getdesemail(),
									":nrphone"=>$this->getnrphone(),
									":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function update() {

		$sql = new Sql();

		/*
		Ordem dos parametros da procedure
		pdesperson VARCHAR(64), 
		pdeslogin VARCHAR(64), 
		pdespassword VARCHAR(256), 
		pdesemail VARCHAR(128), 
		pnrphone BIGINT, 
		pinadmin TINYINT
		*/
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", 
		    array(
					":iduser"=>$this->getiduser(),
					":desperson"=>utf8_decode($this->getdesperson()),
					":deslogin"=>$this->getdeslogin(),
					":despassword"=>User::getPasswordHash($this->getdespassword()),
					":desemail"=>$this->getdesemail(),
					":nrphone"=>$this->getnrphone(),
					":inadmin"=>$this->getinadmin()
		));

		$this->setData($results);

	}

	public function delete(){

		$sql = new Sql();

		$results = $sql->select("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
			));
	}

	public static function getForgot($email, $inadmin = true){

		$sql = new Sql();

		$results = $sql-> select("SELECT *
								  FROM tb_persons a
								  INNER JOIN tb_users b USING(idperson)
								  WHERE a.desemail = :email;
			", array(
					":email"=>$email));

		if (count($results) === 0){
			throw new \Exception("Não foi possível recuperar a senha!");
		} else {
			$data = $results[0];
			//var_dump($data);
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",				 array(
									":iduser"=>$data["iduser"],
									":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if (count($results2) === 0) {
				throw new \Exception("Não foi possível recuperar a senha!");
			} else {
				$dataRecovery = $results2[0];

				define('SECRET_IV', pack ('a16', 'senha'));
				$code = base64_encode(openssl_encrypt($dataRecovery["idrecovery"],
																   'AES-128-CBC', 
									                                User::SECRET,
									                                0,
									                                SECRET_IV));

				if ($inadmin === true){
					$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";	
				} else {
					$link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";	
				}

				$mailer = new Mailer($data["desemail"],
									 $data["desperson"],
									 "Redefinir Senha da Hcode Store",
									 "forgot",
									 array(
									 	"name" => $data{"desperson"},
									 	"link" => $link));
				$mailer->send();

				return $data;
			}
		}
	}

	public static function validForgotDecrypt($code){

		define('SECRET_IV', pack ('a16', 'senha'));
		$idrecovery = openssl_decrypt(base64_decode($code), 
											 'AES-128-CBC', 
											  User::SECRET, 
											 			 0, 
											 	 SECRET_IV);
		$sql = new Sql();

		$results = $sql->select("SELECT * 
								 FROM tb_userspasswordsrecoveries A
								 INNER JOIN TB_USERS B USING(IDUSER)
								 INNER JOIN TB_PERSONS C USING(IDPERSON)
								 WHERE
							 		A.IDRECOVERY = :idrecovery
								    AND
								    A.dtrecovery IS NULL
								    AND
								    DATE_ADD(A.DTREGISTER, INTERVAL 1 HOUR) >= NOW();",
								    array(
								    	":idrecovery"=>$idrecovery));

		if (count($results) === 0){
			throw new \Exception("Não foi possível recuperar a senha!");
		} else {
			return $results[0];
		}
	}

	public static function setForgotUsed($idrecovery){

		$sql = new Sql();
		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW()
					 WHERE idrecovery = :idrecovery", array(
					 	":idrecovery"=>$idrecovery
					 ));
	}

	public function setPassword ($password){

		$sql = new Sql();
		$sql->query("UPDATE tb_users SET despassword = :password
					 WHERE iduser = :iduser", array(
					 	":password"=>$password,
					 	":iduser"=>$this->getiduser()
					 ));
	}


	public static function setError($msg){

		$_SESSION[Cart::SESSION_ERROR]  = $msg;

	}

	public static function getError(){

		return (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";
		User::clearMsgError();
		return $msg;

	}

	public static function clearError(){

		$_SESSION[Cart::SESSION_ERROR] = NULL;

	}

	public static function setErrorRegister($msg){

		$_SESSION[User::ERROR_REGISTER] = $msg;

	}

	public static function getErrorRegister(){

		/*
		Verifica se já está na sessão, se ele existe e se não está vazio. Se existir retorno ele senão retorno uma string vazia
		*/
		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && 
			          $_SESSION[User::ERROR_REGISTER]) ?
			          $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();
		return $msg;
	}

	public static function clearErrorRegister(){

		$_SESSION[User::ERROR_REGISTER] = NULL;

	}

		public static function setSuccess($msg){

		$_SESSION[User::SUCCESS]  = $msg;

	}

	public static function getSuccess(){

		return (isset($_SESSION[User::SUCCESS])) ? $_SESSION[User::SUCCESS] : "";
		User::clearMsgError();
		return $msg;

	}

	public static function clearSuccess(){

		$_SESSION[User::SUCCESS] = NULL;

	}

	public static function checkLoginExist($login){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
			':deslogin'=>$login
		]);

		return (count($results) > 0);
	}	

	public static function getPasswordHash($password){

		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12]);

	}

	public function getOrders($idorder){

		$sql = new Sql();

		$results = $sql->select("
						SELECT *
						FROM tb_orders A
						INNER JOIN tb_ordersstatus B USING(IDSTATUS)
						INNER JOIN tb_carts C USING(IDCART)
						INNER JOIN TB_USERS D ON D.IDUSER = A.iduser
						INNER JOIN tb_addresses E USING(IDADDRESS)
						INNER JOIN tb_persons F ON F.idperson = D.idperson
						WHERE A.iduser = :iduser
						", [
							  ':iduser'=>$this->getiduser()
						]);

		if (count($results) > 0){
			$this->setData($results[0]);
		}

		return $results;
	}

}
?>