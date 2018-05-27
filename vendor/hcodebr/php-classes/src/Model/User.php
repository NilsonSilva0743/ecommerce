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
	const ERROR_REGISTER = "UserErrorRegister";
	const RECOVERY_REG = "RecoveryRegister";
	const KEY_FIRST = "oYiCltBZbqBM+H/l05Fx0uOinj3blibiVwHDx6r5plk=";
	const KEY_SECOND = "9vZACn2TJnFbkhGFaH7eo6DOOgnOX+kUDdWDtGRARqEEljuzseZcp8TU/SDkORSbO1E5ygt/YthT0avJD2t+kQ==";

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

		if(!User::checkLogin($inadmin)){

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

	public static function encrypt_decrypt($action, $string) {
	    $output = false;
	    $encrypt_method = "AES-256-CBC";
	    $secret_key = 'This is my secret key';
	    $secret_iv = 'This is my secret iv';
	    // hash
	    $key = hash('sha256', $secret_key);
	    
	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);
	    if ( $action == 'encrypt' ) {
	        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	        $output = base64_encode($output);
	    } else if( $action == 'decrypt' ) {
	        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	    }
	    return $output;
	}

	

	public static function getForgot($email, $inadmin = true){

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

			throw new \Exception("Não foi possível recuperara a senha ");			

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

				//$codigo = $dataRecovery['idrecovery'];

				$code = self::encrypt_decrypt('encrypt',$dataRecovery['idrecovery']);

				//$first_key = base64_decode(User::KEY_FIRST);
				//$second_key = base64_decode(User::KEY_SECOND);  

				//$iv_length = openssl_cipher_iv_length($method = "AES-128-CBC");
				//$iv = openssl_random_pseudo_bytes($iv_length);  

				//$code_raw = openssl_encrypt($codigo, $method, User::KEY_SECOND ,$options=OPENSSL_RAW_DATA, $iv);
				//$code = base64_encode($iv.$code_raw);
				
				if ($inadmin === true){
					$link = "http://www.hcodecommerce.com.br/adm/forgot/reset?code=$code";					
				} else {
					$link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
				}

				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Hcode Store", "forgot", array(
						"name" => $data["desperson"],
						"link" => $link
				));

				$mailer->send();

				return $data;

			}

		}

	}
		
	public static function validForgotDecrypt($code)
	{

		$data = self::encrypt_decrypt('decrypt',$code);

		//$data = '89';

		//var_dump($data);
		$sql = new Sql();

		$results = $sql->select("SELECT * from 
								tb_userspasswordsrecoveries a
								inner join tb_users b using(iduser)
								inner join tb_persons c using(idperson)
								where a.idrecovery = :idrecovery
								and a.dtrecovery is null
								AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()", [
													':idrecovery' => $data 
					]);
		if (count($results) === 0){
			throw new \Exception("Não foi possível recuperar a senha.");			
		}else{
			return $results[0];
		}

		$data = null;

	} 

	public static function setForgotUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", [':idrecovery' => $idrecovery]);

	}

	public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", [
			':password' => $password,
			':iduser' => $this->getiduser()
		]);

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

	public static function setErrorRegister($msg)
	{

		$_SESSION[User::ERROR_REGISTER] = $msg;

	}

	public static function getErrorRegister()
	{

		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '' ;

		User::clearErrorRegister();

		return $msg;

	}

	public static function clearErrorRegister()
	{

		$_SESSION[User::ERROR_REGISTER] = NULL;

	}

	public static function checkLoginExists($login)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
			':deslogin' => $login
		]);

		return (count($results) > 0);

	}

	public static function setRecoveryReg($code)
	{

		$_SESSION[User::RECOVERY_REG] = $code;

	}

	public static function getRecoveryReg()
	{

		$msg = (isset($_SESSION[User::RECOVERY_REG]) && $_SESSION[User::RECOVERY_REG]) ? $_SESSION[User::RECOVERY_REG] : '' ;

		User::clearRecoveryReg();

		return $msg;

	}

	public static function clearRecoveryReg()
	{

		$_SESSION[User::RECOVERY_REG] = NULL;

	}




}


 ?>