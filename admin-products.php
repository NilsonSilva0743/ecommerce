<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;


$app->get("/adm/products", function(){

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    if ($search != ''){
        $pagination = Product::getPageSearch($search, $page);
    } else {
        $pagination = Product::getPage($page);
    }

    

    $pages = [];

    for ($i=0; $i < $pagination['pages'] ; $i++) {

        array_push($pages,  [
            'href' => '/adm/products?'.http_build_query([
                'page' => $i + 1,
                'search' => $search
            ]),
            'text' => $i + 1
        ]);
    }

	$page = new PageAdmin();

	$page->setTpl("products", [		
		'products' => $pagination['data'],
		'search' => $search,
		'pages' => $pages
	]);

});

$app->get("/adm/products/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("products-create");

});

$app->post("/adm/products/create", function(){

	User::verifyLogin();

	$product = new Product();

	$product->setData($_POST);

	$product->save();

	header("Location: /adm/products");
	exit;

});

$app->get("/adm/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$page = new PageAdmin();

	$page->setTpl("products-update", ['product' => $product->getValues()]);

});

$app->post("/adm/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$product->setData($_POST);

	$product->save();

	$product->setPhoto($_FILES["file"]);

	header("Location: /adm/products");
	exit;
	

});

$app->get("/adm/products/:idproduct/delete", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$product->delete();

	header("Location: /adm/products");
	exit;
	

});

 ?>