<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;


/**
* 
* Formata número inteiro para decimal com duas casas e com separador de milhar
*
* @param integer $vlprice inteiro a ser formatado
*
* @return string
*
**/
function formatPrice($vlprice){

	if (!$vlprice > 0) $vlprice = 0;

	return number_format($vlprice, 2, ",", ".");

}

function formatDate($date){

	return date('d/m/Y', strtotime($date));

}

function checkLogin($indamin = true){

	return User::checkLogin($indamin);

}

function getUserName(){

	$user = User::getFromSession();
	return $user->getdesperson();

}

function getCartNrQtd(){

	$cart = Cart::getFromSession();
	$totals = $cart->getProductsTotals();
	return $totals['nrqtd'];

}

function getCartVlSubTotal(){

	$cart = Cart::getFromSession();
	$totals = $cart->getProductsTotals();
	return formatPrice($totals['vlprice']);

}
?>