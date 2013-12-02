<?php
namespace zeus\config;

class ZeusConfig implements ConfigInterface
{
	protected $_app;
	protected $_controller;
	protected $_action;
	
	
	/**
	 * document_root路径
	 */
	public function webRoot()
	{
		return ROOT.DS.'web';
	}
	
	public function dataRoot()
	{
		return ROOT.DS.'web'.DS.'Data';
	}
	
	/**
	 * 系统日志输出级别
	*/
	public function logLevel()
	{
		return 0;
	}
	
	/**
	 * 是否开启Debug信息
	*/
	public function isDebug()
	{
		return true;
	}
	
	/**
	 * 系统运行的时间时区
	*/
	public function timeZone()
	{
		return 'Asia/shanghai';
	}
	
	/**
	 * 当前运行app名称
	*/
	public final function app()
	{
		return "helloword";
	}
	
	public final function controller()
	{
		return $this->_controller;
	}
	
	public function action()
	{
		return $this->_action;
	}
	
	/**
	 * 是否开启web url 重写
	*/
	public function urlMode()
	{
		return 0;
	}
	
	/**
	 * controller 请求 访问器
	*/
	public function controllerAccessor()
	{
		return "c";
	}
	
	/**
	 * action 请求访问器
	*/
	public function actionAccessor()
	{
		return "a";
	}
	
	/**
	 *
	*/
	public function charset()
	{
		return "utf-8";
	}
	
	public function contentType()
	{
		return "text/html";
	}
	
	public function isCacheControl()
	{
		return true;
	}
	
	public function router()
	{
		$this->_app = "helloword";
		$this->_controller = "index";
		$this->_action = "index";
	}
	
	public function rewrite($url)
	{
		return $url;
	}
	
	public function theme()
	{
		return "";
	}
	
	public function sessionHandler()
	{
		return "file";
	}
	
	public function sesionLifetime()
	{
		return 36000;
	}
	
	public function memcacheCfg()
	{
		
	}
	
	public function databaseCfg()
	{
		
	}
	
	public function litecacheCfg()
	{
		
	}
}