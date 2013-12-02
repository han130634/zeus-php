<?php
namespace zeus\session;

class Cookie
{
	private static $_token = 'zeus';
	
    private static $cookie = array();
    
    private static function _decrypt($text) 
    {
        return $text;
    }

    private static function _encrypt($text) 
    {
        return $text;
    }

    public static function set($var, $value, $life=0, $path='', $domain='') 
    {
        $life = $life ? (time() + $life) : 0;
        $path = $path ? $path : '/';
        
        if(empty($domain))
        {
        	$domain = ".{$_SERVER['HTTP_HOST']}";
        }
        
        setcookie(self::$_token . $var, self::_encrypt($value), $life, $path, $domain, $_SERVER['SERVER_PORT'] == 443 ? 1 : 0);

        self::$cookie[$var] = $value;
    }

    public static function get($var) 
    {
        $value =  isset(self::$cookie[$var]) ? self::$cookie[$var] : '';
        if( empty($value) )return $value;

        return self::_decrypt($value);
    }

    public static function delete($var, $path='', $domain='') 
    {
        self::set($var, '', -86400 * 365, $path, $domain);

        self::$cookie[$var] = '';
        unset(self::$cookie[$var]);
    }

    public static function clear()
    {
        foreach (self::$cookie as $key => $value)
        {
            self::delete($key);
        }
        self::$cookie = array();
    }
	
    public static function getCookies()
    {
    	return self::$cookie;
    }
    
    public static function start() 
    {
        $prelength = strlen(self::$_token);
        foreach ($_COOKIE as $key => $val) 
        {
            if (substr($key, 0, $prelength) == self::$_token) 
            {
                self::$cookie[(substr($key, $prelength))] = $val;
            }
        }
    }
}
