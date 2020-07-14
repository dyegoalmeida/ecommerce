<?php

use \Hcode\Model\User;

function formatPrice(float $vlprice){

	return number_format($vlprice, 2, ",", ".");

}

function checkLogin($indamin = true){

	return User::checkLogin($indamin);

}

function getUserName(){

	$user = User::getFromSession();
	return $user->getdesperson();

}

?>