<?php
namespace zeus;

if ( strnatcasecmp(phpversion(),'5.3') <= 0 )
{
	exit(" Must use php 5.3+");
}

define('ZEUS_TIME',microtime(true));
define('ZEUS', 'zeus @ nathena');
define('VER', 'v0.0.1');
define("DS",DIRECTORY_SEPARATOR);

define("ZEUS_LIB", dirname(__DIR__));
define("ROOT", dirname(ZEUS_LIB));
define("IL8N", ROOT.DS."il8n");
define("LOG", ROOT.DS."log");
define("APPLICATION", ROOT.DS."application");
define("TEMPLATE", ROOT.DS."template");

set_include_path('.'.PATH_SEPARATOR.ZEUS_LIB.PATH_SEPARATOR.APPLICATION);

include 'utils/lib.php';

use zeus\config\ConfigInterface;
use zeus\config\ZeusConfig;
use zeus\web\Controller;

final class Zeus
{
	private $_config;
	
	private static $_self;
	
	private function __construct(ConfigInterface $config)
	{
		$this->_config = $config;
	}
	
	public static function getInstance(ConfigInterface $config = null)
	{
		if(!isset(self::$_self))
		{
			if(!is_object($config) && !$config instanceof ConfigInterface)
			{
				$config = new ZeusConfig();
				//时区
				date_default_timezone_set($config->timeZone());
			}
			
			self::$_self = new self($config);
		}	
		
		return self::$_self;
	}
	
	public function getConfig()
	{
		return $this->_config;
	}
	
	public function setConfig(ConfigInterface $config)
	{
		$this->_config = $config;
	}
	
	public function dispath()
	{
		ob_start();
		
		$this->setHttpHeader();
		
		$this->_config->router();
		
		$app = trim($this->_config->app());
		$controller = trim($this->_config->controller());
		$action = strtolower(trim($this->_config->action()));
		
		$controller = array($app,$controller);
		$controller = strtolower(implode($controller, "\\"));
		
		$controller = new $controller();
		if( $controller instanceof Controller && method_exists($controller, $action) )
		{
			try 
			{
				$controller->{$action}();
			}
			catch(\Exception $e)
			{
				$controller->exceptionHandle($e);
			}
		}
		else
		{
			throw new \Exception(get_class($controller)."->{$action} not found ");
		}
		
		$this->debug();
		
		ob_end_flush();
		ob_flush();
	}
	
	private function setHttpHeader()
	{
		header("Content-Type: ".$this->_config->contentType()."; charset=".$this->_config->charset(),true);
			
		if(!$this->_config->isCacheControl())
		{
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ,true);
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' ,true);
			header( 'Pragma: no-cache' );
		}
		
		header("X-Powered-By: ".ZEUS.'/'.VER,true);
	}
	
	private function debug()
	{
		if($this->_config->isDebug())
		{
			debugZeus();
		}	
	}
}
