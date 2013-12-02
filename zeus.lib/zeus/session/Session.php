<?php
namespace zeus\session\Session;

use zeus\Zeus;
use zeus\config\ConfigInterface;
use zeus\session\handle\MysqlSession;

class Session
{
    public static $sessHandle;

    public static function init()
    {
    	$config = Zeus::getInstance()->getConfig();
    	
        $handler = $config->sessionHandler();
        $lifetime = $config->sesionLifetime();
        
        if ($handler == 'mysql') 
        {
//             if (!is_object(self::$sessHandle)) 
//             {
//                 self::$sessHandle = new MysqlSession($lifetime);
//             }
            
//             session_set_save_handler(
//                 array(&self::$sessHandle, 'open'), 
//                 array(&self::$sessHandle, 'close'), 
//                 array(&self::$sessHandle, 'read'), 
//                 array(&self::$sessHandle, 'write'), 
//                 array(&self::$sessHandle, 'destroy'), 
//                 array(&self::$sessHandle, 'gc')
//             );
        } 
        else 
        {
            ini_set('session.save_handler', 'files');
            ini_set('session.gc_maxlifetime', $lifetime);
            
			session_save_path(Zeus::get("log"));
        }
        session_start();
    }

    public static function generateSessId()
    {
        return md5(time() . substr(uniqid(rand()), -6));
    }

    public static function setSessId($sessId)
    {
        session_id($sessId);
    }

    public static function getSessId()
    {
        return session_id();
    }

    public static function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public static function get($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    public static function remove($name)
    {
        if (isset($_SESSION[$name])) 
        {
            unset($_SESSION[$name]);
        }
    }

    public static function destroy()
    {
        session_unset();
        session_destroy();
    }
}
