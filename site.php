<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;

$app->get('/', function() {
    
	$products = Product::listAll();

	$page = new Page();
	$page->setTpl('index', [
		'products'=>Product::checkList($products)
	]);
});

$app->get("/categories/:idcategory", function($idcategory){

	//Se caso a page não tenha valor atribui 1
	//$page = (isset($GET['page'])) ? (int)$_GET['page'] : 1;
	//Pelo GET não estava recebendo a página correta, sempre era 1, coloquei esse código
	//que achei no forum do curso.
 	$urlCode = $_SERVER['REQUEST_URI'];
    $codeUrl = explode('page=', $urlCode);
    $page = (isset($codeUrl[1]))? (int) $codeUrl[1] : 1;	

    echo $page;	

	$category = new Category();
	$category->get((int)$idcategory);

	/*
	Como o segundo parametros já tem definido valor padrão, passamos em branco.
	Porém se o número de itens por página fosse definido pelo usuário, você passaria
	aqui o GET dele.
	*/
	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages'] ; $i++) { 
		array_push($pages, [
			'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
			'page'=>$i
		]);
	}

	$page = new Page();
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);

});

$app->get("/products/:desurl", function($desurl){

	$product = new Product();
	$product->getFromURL($desurl);

	$page = new Page();
	$page->setTpl("product-detail",[
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
});

?>