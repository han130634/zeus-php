<?php
namespace {
	
	use zeus\Zeus;
	use zeus\config\ConfigInterface;
	
	/**
	 * 获取当前客户端IP
	 * @return Ambigous <string, unknown>
	 */
	function ip()
	{
		static $_ip;
		if( !isset($_ip) )
		{
			$cip = getenv('HTTP_CLIENT_IP');
			$xip = getenv('HTTP_X_FORWARDED_FOR');
			$rip = getenv('REMOTE_ADDR');
			$srip = $_SERVER['REMOTE_ADDR'];
			if($cip && strcasecmp($cip, 'unknown'))
			{
				$_ip = $cip;
			}
			elseif($xip && strcasecmp($xip, 'unknown'))
			{
				$_ip = $xip;
			}
			elseif($rip && strcasecmp($rip, 'unknown'))
			{
				$_ip = $rip;
			}
			elseif($srip && strcasecmp($srip, 'unknown'))
			{
				$_ip = $srip;
			}
			else
			{
				$_ip = "";
			}
		}
		return $_ip;
	}
	
	/**
	 * 安全字符
	 * @param string $str
	 * @return string
	 */
	function escape($str)
	{
		return htmlspecialchars($str);
	}
	
	/**
	 * 重定向
	 * @param String $url
	 * @param String $message
	 * @param String $delay
	 */
	function sendRedirect($url, $message = '', $delay = 1)
	{
		ob_end_clean();
		if (!empty($message) && $delay > 0)
		{
			echo <<<HTML
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta http-equiv="refresh" content="{$delay};URL={$url}" />
</head>
<body>
<div style="width:800px;border:1px solid #FF0000; position:absolute; left:50%; top:50%;
margin-left:-400px; margin-top:-15px; z-index:1; background-color:#FFF2E9; text-align:center; padding:8px;
font:12px Verdana, Lucida, Helvetica, Arial, sans-serif;">{$message}</div>
</body>
</html>
HTML;
		}
		else if (!headers_sent())
		{
			header('Location:' . $url);
		}
		else
		{
			echo <<<HTML
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta http-equiv="refresh" content="0;URL={$url}" />
</head>
<body>
</body>
</html>
HTML;
		}
		exit;
	}
	
	function url($url)
	{
		return Zeus::getInstance()->getConfig()->rewrite($url);
	}
	
	/**
	 * 输出Debug信息
	 * @author nathena
	 */
	function debugZeus()
	{
		echo '<br />'.PHP_EOL;
		echo 'Used Time ' . ( microtime(true) - ZEUS_TIME ) . '<br />' . PHP_EOL;
		echo 'Used memory ' . memory_get_usage() . '<br />' . PHP_EOL;
	
		$files = get_included_files();
	
		foreach( $files as $file )
		{
			echo $file . '<br />' . PHP_EOL;
		}
	}
	
	function tpl( $tpl )
	{
		$config = Zeus::getInstance()->getConfig();
		
		$_tpl = realpath(TEMPLATE.DS.$config->app().DS.$config->theme().DS.$tpl.'.php');
		if(!$_tpl)
		{
			$_tpl = realpath(TEMPLATE.DS.$config->theme().DS.$tpl.'.php');
		}
		
		if(!$_tpl)
		{
			$_tpl = realpath(TEMPLATE.DS.$tpl.'.php');
		}
		
		unset($config,$tpl);
		
		return $_tpl;
	}
	
}//namesspace



namespace zeus\utils{
	
	/**
	 * utf8 trim
	 * @return type
	 */
	function utf8Trim($str)
	{
		$len = strlen($str);
		for ($i = $len - 1; $i >= 0; $i--)
		{
			$hex .= ' ' . ord($str[$i]);
			$ch = ord($str[$i]);
			if (($ch & 128) == 0)
			{
				return substr($str, 0, $i);
			}
			if (($ch & 192) == 192)
			{
				return substr($str, 0, $i);
			}
		}
		return($str . $hex);
	}
	
	/**
	 *获取新的字符串，按一定长度截取
	 * @param String $str
	 * @param int  $length
	 * @param String $dot
	 * @param String $charset
	 * @return String
	 */
	function str($str, $length, $dot = '...', $charset = 'utf-8')
	{
		if ($length && strlen($str) > $length)
		{
			if ('utf-8' != $charset)
			{
				$retstr = '';
				for ($i = 0; $i < $length - 2; $i++)
				{
					$retstr .= ord($str[$i]) > 127 ? $str[$i] . $str[++$i] : $str[$i];
				}
				return $retstr . $dot;
			}
			return utf8Trim(substr($str, 0, $length)) . $dot;
		}
		return $str;
	}
	
	/**
	 *
	 * @param String $path
	 * @return mixed
	 */
	function realpath($path)
	{
		return str_replace(array('/', '\\', '//', '\\\\'), DS, $path);
	}
	
	/**
	 * mkdir新型
	 * @param String $dir
	 * @param int $mode
	 * @return String
	 */
	function mkdir($dir, $mode = 0777)
	{
		if(!file_exists($dir))
		{
			$old = umask(0);
			\mkdir($dir, $mode,true);
			umask($old);
		}
		return $dir;
	}
	
	/**
	 * 是否空文件夹
	 * @param Stirng $dir
	 */
	function emptyDir($dir)
	{
		$handle = opendir($dir);
		while (false !== ($file = readdir($handle)))
		{
			if ($file != '.' && $file != '..')
			{
				closedir($handle);
				return false;
			}
		}
		closedir($handle);
		return true;
	}
	
	/**
	 * 删除目录
	 * @param String $dir
	 * @return boolean
	 */
	function unlink($dir)
	{
		$dir = trimPath($dir);
		$handle = opendir($dir);
		while (false !== ($file = readdir($handle)))
		{
			if ($file == '.' || $file == '..') continue;
			$filename = $dir . DS . $file;
			if (filetype($filename) == 'dir')
			{
				unlink($filename);
			}
			else
			{
				\unlink($filename);
			}
		}
		closedir($handle);
		if(emptyDir($dir))
		{
			rmdir($dir);
		}
		return true;
	}
	
	/**
	 * 复制目录
	 * @param String $source
	 * @param String $dest
	 * @return boolean
	 */
	function copyDir($source, $dest)
	{
		$source = rtrim($source, '\\/') . DS;
		$dest = rtrim($dest, '\\/') . DS;
		mkdir($dest, 0755);
		$handle = opendir($source);
		while (false !== ($filename = readdir($handle)))
		{
			$file = $source . $filename;
			if (is_file($file))
			{
				copy($file, $dest . $filename);
			}
		}
		closedir($handle);
		return true;
	}
	
	/**
	 * 文件后缀
	 * @param String $file
	 * @return String
	 */
	function fileExt($file)
	{
		return end(explode(".", $file));
	}
	
	/**
	 * filter gpc
	 */
	function filterData()
	{
		$_GET = xss($_GET);
		$_POST = xss($_POST);
		$_COOKIE = xss($_COOKIE);
	
		if (!get_magic_quotes_gpc())
		{
			$_GET = xaddslashes($_GET, 1);
			$_POST = xaddslashes($_POST);
			$_COOKIE = xaddslashes($_COOKIE);
		}
	}
	
	/**
	 * 使用反斜线引用字符串
	 * @param String/array $string
	 * @param boolean $strtolower
	 * @return String/array
	 */
	function xaddslashes($string, $strtolower = 0)
	{
		if (is_array($string))
		{
			foreach ($string as $key => $val)
			{
				$string[$key] = xaddslashes($val);
			}
		}
		else
		{
			if ($strtolower)
			{
				$string = strtolower($string); //不区分大小写
			}
			$string = addslashes($string);
		}
		return $string;
	}
	
	/**
	 * xaddslashes的反函数
	 * @param unknown_type $string
	 * @return string
	 */
	function unaddslashes($string)
	{
		if (is_array($string))
		{
			foreach ($string as $key => $val)
			{
				$string[$key] = unaddslashes($val);
			}
		}
		else
		{
			$string = stripslashes($string);
		}
		return $string;
	}
	
	/**
	 * xss
	 * @param String/array $string
	 */
	function xss($string)
	{
		if (is_array($string))
		{
			foreach ($string as $key => $val)
			{
				$string[$key] = xss($val);
			}
		}
		else
		{
			$string = preg_replace('/<script[^>]*?>[\s\S]*?<\/script>/i', '', $string);
			$string = preg_replace('/<iframe[^>]*?>([\s\S]*?<\/iframe>)?/i', '', $string);
	
			if(strtolower($_SERVER['REQUEST_METHOD']) == 'get'){
				$string = rawurldecode($string);
			}
		}
	
		return $string;
	}
}//zeus\utils








//this is the  zeus framework implicit expression namespace
namespace _this_is_the_zeus_framework_implicit_expression_namespace_{

	use zeus\Zeus;
	use zeus\utils\Log;
	
	function __forName($class)
	{
		if( class_exists($class,false) || interface_exists($class,false) )
		{
			return;
		}
		
		include_once( $class.'.php' );
	}
	
	//set_exception_handler&set_error_handler
	function __exception_handler($exception, $message = NULL, $file = NULL, $line = NULL)
	{
		$PHP_ERROR = (func_num_args() === 5);
	
		if($PHP_ERROR AND (error_reporting() & $exception) === 0)
			return;
	
		if ($PHP_ERROR)
		{
			$code     = $exception;
			$type     = 'PHP Error';
	
			$message  = $type.'  '.$message.'  '.$file.'  '.$line;
		}
		else
		{
			$code     = $exception->getCode();
			$type     = get_class($exception);
			$message  = $exception->getMessage()."\n".$exception->getTraceAsString();
			$file     = $exception->getFile();
			$line     = $exception->getLine();
		}
	
		Log::error($type, $code, $message, $file, $line);
	
		if( !Zeus::getInstance()->getConfig()->isDebug() )
		{
			$_file = tpl("error");
			if (file_exists($_file))
			{
				ob_end_clean();
				include $_file;
			}
			else
			{
				sendRedirect('/',$message);
			}
		}
		else
		{
			$str = '<style>body {font-size:12px;}</style>';
			$str .= '<h1>操作失败！</h1><br />';
			$str .= '<strong>错误信息：<strong><font color="red">' . $message . '</font><br />';
	
			echo $str;
		}
	
		exit($code);
	}
	
	//启动自动加载
	spl_autoload_register(__NAMESPACE__.'\__forName');
	//异常处理
	set_error_handler(__NAMESPACE__.'\__exception_handler');
	set_exception_handler(__NAMESPACE__.'\__exception_handler');
}

