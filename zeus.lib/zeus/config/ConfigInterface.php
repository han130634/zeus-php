<?php
/**
 * 配置文件接口
 *
 * 让配置文件通过编程完成，单系统过于复杂的时候，可通过修饰模式适配不同需要的配置信息。
 *
 * @author nathena@qq.com
 */
namespace zeus\config;

interface ConfigInterface
{
	/**
	 * document_root路径
	 */
	public function webRoot();


	/**
	 * 系统日志输出级别
	*/
	public function logLevel();

	/**
	 * 是否开启Debug信息
	*/
	public function isDebug();

	/**
	 * 系统运行的时间时区
	*/
	public function timeZone();

	/**
	 * 当前运行app名称
	*/
	public function app();
	
	public function controller();
	
	public function action();

	/**
	 * 是否开启web url 重写
	*/
	public function urlMode();

	/**
	 * controller 请求 访问器
	*/
	public function controllerAccessor();

	/**
	 * action 请求访问器
	*/
	public function actionAccessor();
	
	/**
	 *
	*/
	public function charset();

	public function contentType();

	public function isCacheControl();

	public function urlRouter();

	public function urlRewrite($url);
	
	public function theme();

	public function sessionHandler();

	public function sesionLifetime();

	public function memcacheCfg();

	public function databaseCfg();

	public function litecacheCfg();
}