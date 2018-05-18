<?php 

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;


$app->config('debug', true);

$app->get('/', function() {

	$code = "123456";
    //$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, "12", MCRYPT_MODE_ECB));
    //echo $code;

    $products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", ['products' => Product::checkList($products)]); 

});


$app->get("/categories/:idcategory", function($idcategory){
	
	

	$category = new Category();

	$pag = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;	

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($pag);

	$pages = [];

	for ($i=1; $i <= $pagination['pages'] ; $i++) { 
			array_push($pages, [
				'link' => '/categories/'.$category->getidcategory().'?page='.$i,
				'page'=>$i
			]);
	}

	$page = new Page();	

	$page->setTpl("category", [
					'category' => $category->getValues(),
					'products' => $pagination["data"],
					'pages' => $pages
					]);

});





 ?>