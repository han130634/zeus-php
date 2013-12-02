<?php
namespace zeus\utils;

class I18n 
{
	private static $language = array();
	private static $lang;
	
	public static function init($lang)
	{
        $file = IL8N.'/'.strtolower($lang).'.php';
        if (file_exists($file)) 
        {
            self::$lang = $lang;
            self::$language = include $file;
        } 
        else 
        {
            throw new Exception("Language file '$file' was not found or have no access");
        }
	}
	
	public static function lang()
	{
		return self::$lang;
	}
	
	public static function get($name)
	{
        return isset(self::$language[$name]) ? self::$language[$name] : "!$name!";
	}
}
