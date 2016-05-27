<?php
//List of implemented CMS:
//Bitrix
//MODx Revo
ob_start();
function adminer_object() {
  
  class AdminerExt extends Adminer {
  	function __construct()
  	{
  		if($this->checkAccess())
	  		$this->extractCredentials();
  	}

  	private function addCredentials($driver, $server, $username, $password, $database)
  	{
  		stripos($driver, 'mysql') === 0 && $driver = 'server';

  		if(isset($_SESSION['pwds'][$driver][$server][$username])) return;
  		if(!empty($_COOKIE["adminer_key"]))
  			$adminer_key = $_COOKIE["adminer_key"];
  		else {
  			$adminer_key = rand_string();
  			$params = session_get_cookie_params();
			cookie("adminer_key", $adminer_key, $params["lifetime"]);
  			$_COOKIE["adminer_key"] = $adminer_key;
  		}
  		
  		$_SESSION['pwds'][$driver][$server][$username] = array(encrypt_string($password, $adminer_key));
  		$_SESSION['db'][$driver][$server][$username][$database] = true;
  	}

	private function extractCredentials()
	{
		$methods = get_class_methods($this);
		foreach($methods as $method)
			if(strpos($method, 'extractCredentialsFrom') === 0) 
				call_user_func_array(array($this, 'addCredentials'), $this->$method());
	}

	function extractCredentialsFromBitrix()
	{
		$configPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/dbconn.php';
		if(!file_exists($configPath))
			return;
		$config = file_get_contents($configPath);
		$vars = array();
		preg_match_all('{\$(\w+)\s*=\s*([\'"])(.*?)\2\s*;}', $config, $vars, PREG_SET_ORDER); // 1 varname 3 value
		$credentials = array('driver'=>'', 'server'=>'', 'username'=>'', 'password'=>'', 'database'=>'');
		$map = array(
			'DBType'	=> 'driver',
			'DBHost'	=> 'server',
			'DBLogin'	=> 'username',
			'DBPassword'=> 'password',
			'DBName'	=> 'database'
			);
		foreach($vars as $var)
			if(isset($map[$var[1]]))
				$credentials[$map[$var[1]]] = $var[3];

		return $credentials;
	}

	function extractCredentialsFromModx()
	{
		$configPath = $_SERVER['DOCUMENT_ROOT'] . '/core/config/config.inc.php';
		if(!file_exists($configPath))
			return;
		$config = file_get_contents($configPath);
		$vars = array();
		preg_match_all('{\$(\w+)\s*=\s*([\'"])(.*?)\2\s*;}', $config, $vars, PREG_SET_ORDER); // 1 varname 3 value
		$credentials = array('driver'=>'', 'server'=>'', 'username'=>'', 'password'=>'', 'database'=>'');
		$map = array(
			'database_type'	=> 'driver',
			'database_server'	=> 'server',
			'database_user'	=> 'username',
			'database_password'=> 'password',
			'dbase'	=> 'database'
			);
		foreach($vars as $var)
			if(isset($map[$var[1]]))
				$credentials[$map[$var[1]]] = $var[3];

		return $credentials;
	}

	function checkAccess()
	{
		return preg_match('{^91\.244\.169\.\d+$}', $_SERVER ['REMOTE_ADDR']);
	}
  }

  return new AdminerExt;
}
// end-tag is necessary there
?>

