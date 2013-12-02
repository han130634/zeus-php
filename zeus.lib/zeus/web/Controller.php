<?php
/**
 * web页面控制器 抽象类
 * 实现的控制器方法第一个参数必须为array $viewParam参数，例如
 * <pre>
 *  
 *  
 *  #注意：controller　类名与方法名称必须为小写。
 * </pre>
 * 
 * 
 * @author nathena
 *
 */
namespace zeus\web;

use zeus\session\Cookie;

abstract class Controller
{
	public function __construct()
	{
		Cookie::start();
		
		register_shutdown_function(array($this, '__afterComplete'));
	}

	public function __destruct()
	{
		
	}
	
	public function __afterComplete()
	{
		
	}
	
	public function exceptionHandle(\Exception $e)
	{
		throw new WebException("",-1,$e);
	}
}