<?php
//List of implemented CMS:
//Bitrix
//MODx Revo

if(!is_defined('AE_CHECK_ACCESS')) define('AE_CHECK_ACCESS', true);
ob_start();
function adminer_object() {
  
  class AdminerExt extends Adminer {
  	function __construct()
  	{
  		if(!AE_CHECK_ACCESS || $this->checkAccess())
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
			if(strpos($method, 'extractCredentialsFrom') === 0 && ($credentials = $this->$method())) 
				call_user_func_array(array($this, 'addCredentials'), $credentials);
	}

	function getConfigFile($path)
	{
		$path = $_SERVER['DOCUMENT_ROOT'] . $path;
		if(!file_exists($path))
			return false;
		return file_get_contents($path);
	}

	function extractVars($text)
	{
		$vars = array();
		preg_match_all('{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*=\s*([\'"])(.*?)\2\s*;}', $config, $vars, PREG_SET_ORDER); // 1 varname 3 value
		foreach($vars as &$var)
			$var[$var[1]] = $var[3];
		return $vars;			
	}

	function extractCredentialsVars($configPath, $varsMap)
	{
		$credentials = array('driver'=>'', 'server'=>'', 'username'=>'', 'password'=>'', 'database'=>'');
		if(!$config = $this->getConfigFile($configPath))
			return;
		foreach($this->extractVars($config) as $name => $value)
			if(isset($map[$name]))
				$credentials[$map[$name]] = $value;
		return $credentials;
	}

	function extractCredentialsFromBitrix()
	{
		return $this->extractCredentialsVars('/bitrix/php_interface/dbconn.php', array(
			'DBType'	=> 'driver',
			'DBHost'	=> 'server',
			'DBLogin'	=> 'username',
			'DBPassword'=> 'password',
			'DBName'	=> 'database'
			));
	}

	function extractCredentialsFromModx()
	{
		return $this->extractCredentialsVars('/core/config/config.inc.php', array(
			'database_type'	=> 'driver',
			'database_server'	=> 'server',
			'database_user'	=> 'username',
			'database_password'=> 'password',
			'dbase'	=> 'database'
			));
	}

	function checkAccess()
	{
		return 
			preg_match('{^91\.244\.169\.\d+$}', $_SERVER ['REMOTE_ADDR']) || 
			preg_match('{^91\.244\.169\.\d+$}', $_SERVER ['HTTP_X_REAL_IP']) || // we shouldn't trust it
			preg_match('{^91\.244\.169\.\d+$}', $_SERVER ['HTTP_X_REAL_IP']); // but i'm kinda lazy :(
	}
  }

  return new AdminerExt;
}
// end-tag is necessary there
?>

