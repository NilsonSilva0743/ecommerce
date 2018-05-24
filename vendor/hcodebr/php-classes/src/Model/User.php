<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;

use \Hcode\Model;

use \Hcode\Mailer;

class User extends Model

{
	const SESSION = "User";
	const SECRET = "HcodePhp7_Secret"; // no mínimo 16 caracteres
	const ERROR = "UserError";

	public static function getFromSession()
	{	
		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){	

			$user->setData($_SESSION[User::SESSION]);
		}

		return $user;

	}

	public static function checkLogin($inadmin = true)
	{

		if(!isset($_SESSION[User::SESSION]) ||
			 !$_SESSION[User::SESSION] ||
			 !(int)$_SESSION[User::SESSION]["iduser"] > 0 			 
			){
			// Não está logado
			return false;
		} else {

			if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){
				return true;
			}else if($inadmin === false){
				return true;
			}else{
				return false;
			}

		}

	}


	public static function login($login, $password){

		$sql = new Sql();

		$result = $sql->select("SELECT a.iduser, a.idperson, a.deslogin,
								a.despassword, a.inadmin, b.desperson FROM tb_users a 
								INNER JOIN tb_persons b ON
								a.idperson = b.idperson
								WHERE deslogin = :LOGIN", array(
			":LOGIN" => $login,
		));

		if(count($result) === 0){
			throw new \Exception("Usuario inexistente ou senha inválida");
		}

		$data = $result[0];

		if(password_verify($password, $data["despassword"]) === true){

			$user = new User();

			$data['desperson'] = utf8_encode($data['desperson']);

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		}else{

			throw new \Exception("Usuario inexistente ou senha inválida");

		}

	}

	public static function verifyLogin($inadmin = true){

		if(User::checkLogin($inadmin)){

			if ($inadmin){
				header("Location: /adm/login");
			} else {
				header("Location: /login");
			}
			exit;
		}
		

	}

	public static function logout(){
		$_SESSION[User::SESSION] = NULL;
	}

	public static function listAll(){

		$sql = new Sql();
		 return $sql->select("SELECT * FROM tb_users a 
					 INNER JOIN tb_persons b USING(idperson) 
					 ORDER BY b.desperson" );

	}

	public function save(){

		$sql = new Sql();

		echo $this->getdesperson();

		$result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"     => utf8_decode($this->getdesperson()),
			":deslogin"      => $this->getdeslogin(),
			":despassword"   => User::getPasswordHash($this->getdespassword()),
			":desemail"      => $this->getdesemail(),
			":nrphone"       => $this->getnrphone(),
			":inadmin"       => $this->getinadmin()
		));


		$this->setData($result[0]);

	}

	public function get($iduser){

		$sql = new Sql();

		$result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(":iduser" => $iduser));

		$data = $result[0];

		$data['desperson'] = utf8_encode($data['desperson']);

		$this->setData($data);
	}

	public function update(){

		$sql = new Sql();		

		$result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"        => $this->getiduser(),
			":desperson"     => utf8_decode($this->getdesperson()),
			":deslogin"      => $this->getdeslogin(),
			":despassword"   => User::getPasswordHash($this->getdespassword()),
			":desemail"      => $this->getdesemail(),
			":nrphone"       => $this->getnrphone(),
			":inadmin"       => $this->getinadmin()
		));

		$this->setData($result[0]);
	}

	public function delete(){

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
				":iduser" => $this->getiduser()
		));

	}

	public static function getForgot($email){

		$sql = new Sql();

		$result = $sql->select("
			SELECT * 
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
		", array(

			":email" => $email

		));

		if(count($result) === 0){

			throw new \Exception("Não foi possível recuperara a senha");			

		} else {

			$data = $result[0];			

			$result2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser" => $data["iduser"],
				":desip"  => $_SERVER["REMOTE_ADDR"]
			));

			if(count($result2) === 0){

				throw new \Exception("Não foi possível recuperara a senha");			

			} else {

				$dataRecovery = $result2[0];

				//$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));

				$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, "12", MCRYPT_MODE_ECB));

				//$code = "123456";


				$link = "http://www.hcodecommerce.com.br/adm/forgot/reset?code=$code";

				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Hcode Store", "forgot", array(
						"name" => $data["desperson"],
						"link" => $link
				));

				$mailer->send();

				return $data;

			}

		}

	}

	public static function setError($msg)
	{

		$_SESSION[User::ERROR] = $msg;

	}

	public static function getError()
	{

		$msg = (isset($_SESSION[User::ERROR])) ? $_SESSION[User::ERROR] : '';

		User::clearError();

		return $msg;

	}

	public static function clearError()
	{

		$_SESSION[User::ERROR] = NULL;

	}


	public static function getPasswordHash($password)
	{

		return password_hash($password, PASSWORD_DEFAULT, [
			'cost' => 12
		]);

	}


}


 ?>