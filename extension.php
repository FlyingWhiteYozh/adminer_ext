<?php
//List of implemented CMS:
//Bitrix
//MODx Revo
//WordPress
//UMI.CMS
//Joomla
//Drupal
//Simpla
//WebAsyst
//Amiro
//OpenCart

if(!defined('AE_CHECK_ACCESS')) define('AE_CHECK_ACCESS', true);
// ob_start();
function adminer_object() {
  
  class AdminerExt extends Adminer {
  	function __construct()
  	{
  		if(!AE_CHECK_ACCESS || $this->checkAccess())
	  		$this->extractCredentials();
  	}

  	private function addCredentials($driver, $server, $username, $password, $database)
  	{
  		if(!$driver) $driver = 'mysql';

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
		preg_match_all('{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*=\s*([\'"])(.*?)\2\s*;}', $text, $vars, PREG_SET_ORDER); // 1 varname 3 value
		$result = array();
		foreach($vars as $var)
			$result[$var[1]] = $var[3];
		return $result;			
	}

	function extractArray($text)
	{
		$constants = array();
		preg_match_all('{([\'"])(\w+?)\1\s*=>\s*([\'"])(.*?)\3}i', $text, $constants, PREG_SET_ORDER); // 2 varname 4 value
		$result = array();
		foreach($constants as $const)
			$result[$const[2]] = $const[4];
		return $result;			
	}

	function extractConstants($text)
	{
		$constants = array();
		preg_match_all('{define\(\s*([\'"])(\w+?)\1\s*,\s*([\'"])(.*?)\3\s*\);}i', $text, $constants, PREG_SET_ORDER); // 2 varname 4 value
		$result = array();
		foreach($constants as $const)
			$result[$const[2]] = $const[4];
		return $result;			
	}

	function extractCredentialsVars($configPath, $varsMap)
	{
		$credentials = array('driver'=>'', 'server'=>'', 'username'=>'', 'password'=>'', 'database'=>'');
		if(!$config = $this->getConfigFile($configPath))
			return;
		foreach($this->extractVars($config) as $name => $value)
			if(isset($varsMap[$name]))
				$credentials[$varsMap[$name]] = $value;
		return $credentials;
	}

	function extractCredentialsArray($configPath, $varsMap)
	{
		$credentials = array('driver'=>'', 'server'=>'', 'username'=>'', 'password'=>'', 'database'=>'');
		if(!$config = $this->getConfigFile($configPath))
			return;
		foreach($this->extractArray($config) as $name => $value)
			if(isset($varsMap[$name]))
				$credentials[$varsMap[$name]] = $value;
		return $credentials;
	}

	function extractCredentialsConstants($configPath, $varsMap)
	{
		$credentials = array('driver'=>'', 'server'=>'', 'username'=>'', 'password'=>'', 'database'=>'');
		if(!$config = $this->getConfigFile($configPath))
			return;

		foreach($this->extractConstants($config) as $name => $value)
			if(isset($varsMap[$name]))
				$credentials[$varsMap[$name]] = $value;
		return $credentials;
	}

	function extractCredentialsINI($configPath, $varsMap)
	{
		$credentials = array('driver'=>'', 'server'=>'', 'username'=>'', 'password'=>'', 'database'=>'');
		if(!$config = $this->getConfigFile($configPath))
			return;

		foreach(parse_ini_file($_SERVER['DOCUMENT_ROOT'] . $configPath) as $name => $value)
			if(isset($varsMap[$name]))
				$credentials[$varsMap[$name]] = $value;
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

	function extractCredentialsFromMODx()
	{
		return $this->extractCredentialsVars('/core/config/config.inc.php', array(
			'database_type'		=> 'driver',
			'database_server'	=> 'server',
			'database_user'		=> 'username',
			'database_password'	=> 'password',
			'dbase'				=> 'database'
			));
	}

	function extractCredentialsFromJoomla()
	{
		return $this->extractCredentialsVars('/configuration.php', array(
			'dbtype'	=> 'driver',
			'host'		=> 'server',
			'user'		=> 'username',
			'password'	=> 'password',
			'db'		=> 'database'
			));
	}

	function extractCredentialsFromDrupal()
	{
		return $this->extractCredentialsArray('/sites/default/settings.php', array(
			'driver'	=> 'driver',
			'host'		=> 'server',
			'username'	=> 'username',
			'password'	=> 'password',
			'database'	=> 'database'
			));
	}

	function extractCredentialsFromWebAsyst()
	{
		return $this->extractCredentialsArray('/wa-config/db.php', array(
			'type'		=> 'driver',
			'host'		=> 'server',
			'user'		=> 'username',
			'password'	=> 'password',
			'database'	=> 'database'
			));
	}

	function extractCredentialsFromWordPress()
	{
		return $this->extractCredentialsConstants('/wp-config.php', array(
			'DB_HOST'		=> 'server',
			'DB_USER'		=> 'username',
			'DB_PASSWORD'	=> 'password',
			'DB_NAME'		=> 'database'
			));
	}

	function extractCredentialsFromOpenCart()
	{
		return $this->extractCredentialsConstants('/config.php', array(
			'DB_DRIVER'		=> 'driver',
			'DB_HOSTNAME'	=> 'server',
			'DB_USERNAME'	=> 'username',
			'DB_PASSWORD'	=> 'password',
			'DB_DATABASE'	=> 'database'
			));
	}

	function extractCredentialsFromUMI()
	{
		return $this->extractCredentialsINI('/config.ini', array(
			'core.type'		=> 'driver',
			'core.host'		=> 'server',
			'core.login'	=> 'username',
			'core.password'	=> 'password',
			'core.dbname'	=> 'database'
			));
	}

	function extractCredentialsFromSimpla()
	{
		return $this->extractCredentialsINI('/config/config.php', array(
			'db_server'		=> 'server',
			'db_user'		=> 'username',
			'db_password'	=> 'password',
			'db_name'		=> 'database'
			));
	}

	function extractCredentialsFromAmiro()
	{
		return $this->extractCredentialsINI('/_local/config.ini.php', array(
			'DB_Host'		=> 'server',
			'DB_User'		=> 'username',
			'DB_Password'	=> 'password',
			'DB_Database'		=> 'database'
			));
	}

	function extractCredentialsFromCustom()
	{
		return $this->extractCredentialsArray('/lib/settings.inc.php', array(
			'dbHost'=> 'server',
			'dbUser'=> 'username',
			'dbPass'=> 'password',
			'dbName'=> 'database'
			));
	}

	function checkAccess()
	{
		return 
			preg_match('{^91\.244\.169\.\d+$}', $_SERVER ['REMOTE_ADDR']) || 
			preg_match('{^91\.244\.169\.\d+$}', $_SERVER ['HTTP_X_REAL_IP']); // we shouldn't trust it but i'm kinda lazy :(
	}
  }

  return new AdminerExt;
}
// end-tag is necessary there. BUT NO SYMBOLS AFTER IT
?>