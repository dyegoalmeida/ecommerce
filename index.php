<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
    
	$page = new Page();
	$page->setTpl('index');
});

$app->get('/admin', function() {
    
	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl('index');
});

$app->get('/admin/login', function() {

	//O header e o footer não é padrão na pagina de Login, por isso precisamos desativá-los
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("login");

});

$app->post('/admin/login', function() {

	User::login($_POST["login"], $_POST["password"]);
	header("Location: /admin");
	exit;
});

$app->get('/admin/logout', function () {

	User::logout();
	header("Location: /admin/login");
	exit;

});

$app->run();

?>