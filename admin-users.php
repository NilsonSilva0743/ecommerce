<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get("/adm/users/:iduser/password", function($iduser){

    User::verifyLogin();

    $user = new User();

    $user->get((int) $iduser);

    $page = new PageAdmin();

    $page->setTpl("users-password", [
        'user' => $user->getValues(),
        'msgError' => User::getError(),
        'msgSuccess' => User::getSuccess()
    ]);

}); 

$app->post("/adm/users/:iduser/password", function($iduser){

    User::verifyLogin();

    if (!isset($_POST['despassword']) || $_POST['despassword'] === '' ) {
        User::setError("Preencha a nova senha.");
        header("Location: /adm/users/$iduser/password");
        exit;
    }

    if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === '' ) {
        User::setError("Preencha a confirmação da nova senha.");
        header("Location: /adm/users/$iduser/password");
        exit;
    }

    if ($_POST['despassword'] != $_POST['despassword-confirm']) {
        User::setError("Confirme corretamente as senhas.");
        header("Location: /adm/users/$iduser/password");
        exit;
    }

    $user = new User();

    $user->get((int) $iduser);

    $user->setPassword(User::getPasswordHash($_POST['despassword']));

    User::setSuccess("Senha alterada com sucesso.");
    header("Location: /adm/users/$iduser/password");
    exit;
    

}); 

$app->get('/adm/users', function() {
    
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';

    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    if ($search != ''){
        $pagination = User::getPageSearch($search, $page);
    } else {
        $pagination = User::getPage($page);
    }

    

    $pages = [];

    for ($i=0; $i < $pagination['pages'] ; $i++) {

        array_push($pages,  [
            'href' => '/adm/users?'.http_build_query([
                'page' => $i + 1,
                'search' => $search
            ]),
            'text' => $i + 1
        ]);
    }

	$page = new PageAdmin();

	$page->setTpl("users", array(
			"users" => $pagination['data'],
            "search" => $search,
            "pages" => $pages
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



 ?>