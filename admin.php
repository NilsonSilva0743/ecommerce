<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;


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


 ?>