<?php
/**
 * 日志
 * @author nathena
 *
 */
namespace zeus\utils;

use zeus\Zeus;

class Log
{
    const ERR    = 'ERR';       
    const WARN   = 'WARN';      
    const NOTICE = 'NOTICE';    
    const INFO   = 'INFO';      
    const DEBUG  = 'DEBUG';
    
    private static function save($message, $level = self::INFO)
    {
        $file = LOG .'/'. getmypid().'_'.date('Ymd_') . strtolower($level) . '.log';
        $log = sprintf('[IP] %s [TIME] %s [MSG] %s',ip(), date('H:i:s'), $message) . "\n";
        
        file_put_contents($file, $log, FILE_APPEND);
    }
    
    public static function debug($message)
    {
    	if( 0 >= Zeus::getInstance()->getConfig()->logLevel() )
    	{
    		self::save($message,self::DEBUG);
    	}
    }
    
    public static function info($message)
    {
    	if( 1 >= Zeus::getInstance()->getConfig()->logLevel() )
    	{
        	self::save($message,self::INFO);
    	}
    }
    
    public static function warn($type, $code, $message, $file, $line)
    {
        if( 2 >= Zeus::getInstance()->getConfig()->logLevel() )
        {
            $message .= 'type   = '.$type."\n".
                        'code    = '.$code."\n".
                        'message = '.$message."\n".
                        'file    = '.$file."\n".
                        'line    = '.$line."\n";
            
            self::save($message,self::WARN);
        }
    }
    
    public static function error($type, $code, $message, $file, $line)
    {
        if( 3 >= Zeus::getInstance()->getConfig()->logLevel() )
        {
            $message .= 'type   = '.$type."\n".
                        'code    = '.$code."\n".
                        'message = '.$message."\n".
                        'file    = '.$file."\n".
                        'line    = '.$line."\n";
            
             self::save($message,self::ERR);
        }
    }
}