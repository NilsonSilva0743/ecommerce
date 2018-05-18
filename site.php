<?php 

use \Hcode\Page;


$app->config('debug', true);

$app->get('/', function() {

	$code = "123456";
    //$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, "12", MCRYPT_MODE_ECB));
    //echo $code;

	$page = new Page();

	$page->setTpl("index"); 

});


$app->get("/categories/:idcategory", function($idcategory){
	
	$category = new Category();

	$category->get((int)$idcategory);

	$page = new Page();

	$page->setTpl("category", ['category' => $category->getValues(),
					'products' => []
					]);

});





 ?>