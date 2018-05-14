<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {

	$code = "123456";
    //$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, "12", MCRYPT_MODE_ECB));
    //echo $code;

	$page = new Page();

	$page->setTpl("index"); 

});

$app->get('/adm', function() {
    
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index"); 

});

$app->get('/adm/login', function() {
    
	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("login"); 

});

$app->post('/adm/login', function() {
    
	User::login($_POST['login'], $_POST['password']);

	header("Location: /adm");
	exit; 

});

$app->get('/adm/logout', function() {
    
	User::logout();
	header("Location: /adm/login");
	exit; 

});

$app->get('/adm/users', function() {
    
    User::verifyLogin();

    $users = User::listAll();

	$page = new PageAdmin();

	$page->setTpl("users", array(
			"users" => $users
	));

});

$app->get('/adm/users/create', function() {
    
    User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");

});

$app->get('/adm/users/:iduser/delete', function($iduser) {
    
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $user->delete();

    header("Location: /adm/users");
    exit;
	

});


$app->get('/adm/users/:iduser', function($iduser) {
    
    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array("user"=> $user->getValues()));

});

$app->post('/adm/users/create', function() {
    
    User::verifyLogin();    

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"]))? 1 : 0;

    $user->setData($_POST);

    $user->save();

    header("Location: /adm/users");
    exit;

});

$app->post('/adm/users/:iduser', function($iduser) {
    
    User::verifyLogin();

    $user = new User();

    $_POST["inadmin"] = (isset($_POST["inadmin"]))? 1 : 0;

    $user->get((int)$iduser);

    $user->setData($_POST);

    $user->update();

    header("Location: /adm/users");
    exit;

});

$app->get("/adm/forgot", function(){

	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("forgot");

});

$app->post("/adm/forgot", function(){
	
	$user = User::getForgot($_POST["email"]);

	header("Location: /adm/forgot/sent");
	exit;

});

$app->get("/adm/forgot/sent", function(){

	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("forgot-sent");

});

$app->get("/adm/forgot/reset", function(){

	$user = User::validForgotDecrypt();

	$page = new PageAdmin([
		"header" => false,
		"footer" => false
	]);

	$page->setTpl("forgot-rest");

});


$app->get("/adm/categories", function(){

	User::verifyLogin();

	$categories = Category::listAll();

	$page = new PageAdmin();

	$page->setTpl("categories", [
		'categories' => $categories
	]);

});


$app->get("/adm/categories/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");

});

$app->post("/adm/categories/create", function(){

	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST);

	$category->save();

	header("Location: /adm/categories");
	exit;

}); 


$app->get('/adm/categories/:idcategory/delete', function($idcategory) {
    
    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->delete();

    header("Location: /adm/categories");
    exit;
	

});

$app->get('/adm/categories/:iduser', function($idcategory) { 

	User::verifyLogin();   

    $category = new Category();

    $category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-update", ['category' => $category->getValues()]);

});

$app->post('/adm/categories/:iduser', function($idcategory) { 

	User::verifyLogin();   

    $category = new Category();

    $category->get((int)$idcategory);

    $category->setData($_POST);

    $category->save();

    header("Location: /adm/categories");
    exit;
	

});


$app->run();

 ?>