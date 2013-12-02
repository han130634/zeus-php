<?php
namespace zeus\cache;

use zeus\Zeus;
use zeus\config\ConfigInterface;
use zeus\utils\mkdir;

class Lite
{
    private $_cacheDir = '';
    private $_lifetime;
    private $_caching = true;
    private $_fileLocking = true;
    private $_refreshTime;
    private $_file;
    private $_fileName;
    private $_writeControl = true;
    private $_readControl = true;
    private $_readControlType = 'crc32';
    private $_pearErrorMode = 1;
    private $_id;
    private $_group;
    private $_memoryCaching = false;
    private $_onlyMemoryCaching = false;
    private $_memoryCachingArray = array();
    private $_memoryCachingCounter = 0;
    private $_memoryCachingLimit = 1000;
    private $_fileNameProtection = true;
    private $_automaticSerialization = false;
    private $_automaticCleaningFactor = 0;
    private $_hashedDirectoryLevel = 0;
    private $_hashedDirectoryUmask = 0700;
    private $_errorHandlingAPIBreak = false;
    
    public function __construct($lifetime = 86400, $cacheDir = '')
    {
    	$litcacheCfg = Zeus::getInstance()->getConfig()->litecacheCfg();
    	
        $cacheDir = $cacheDir ? $cacheDir : isset($litcacheCfg['dir'])?$litcacheCfg['dir']:LOG;
        
        mkdir($cacheDir, 0777);
        
        $options = array('cacheDir' => $cacheDir, 'lifetime' => $lifetime);

        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    public function setOption($name, $value) 
    {
        $availableOptions = array(
            'errorHandlingAPIBreak', 'hashedDirectoryUmask', 'hashedDirectoryLevel', 
            'automaticCleaningFactor', 'automaticSerialization', 'fileNameProtection', 'memoryCaching', 
            'onlyMemoryCaching', 'memoryCachingLimit', 'cacheDir', 'caching', 'lifetime', 'fileLocking', 
            'writeControl', 'readControl', 'readControlType', 'pearErrorMode'
        );
        if (in_array($name, $availableOptions)) {
            $property = '_' . $name;
            $this->$property = $value;
        }
    }

    public function get($id, $group = 'default', $doNotTestCacheValidity = false)
    {
        $this->_id = $id;
        $this->_group = $group;
        $data = false;
        if ($this->_caching) {
            $this->_setRefreshTime();
            $this->_setFileName($id, $group);
            clearstatcache();
            if ($this->_memoryCaching) {
                if (isset($this->_memoryCachingArray[$this->_file])) {
                    if ($this->_automaticSerialization) {
                        return unserialize($this->_memoryCachingArray[$this->_file]);
                    }
                    return $this->_memoryCachingArray[$this->_file];
                }
                if ($this->_onlyMemoryCaching) {
                    return false;
                }                
            }
            if (($doNotTestCacheValidity) || (is_null($this->_refreshTime))) {
                if (file_exists($this->_file)) {
                    $data = $this->_read();
                }
            } else {
                if ((file_exists($this->_file)) && (@filemtime($this->_file) > $this->_refreshTime)) {
                    $data = $this->_read();
                }
            }
            if (($data) and ($this->_memoryCaching)) {
                $this->_memoryCacheAdd($data);
            }
            if (($this->_automaticSerialization) and (is_string($data))) {
                $data = unserialize($data);
            }
            return $data;
        }
        return false;
    }

    public function set($id, $data, $group = 'default')
    {
        if ($this->_caching) {
            if ($this->_automaticSerialization) {
                $data = serialize($data);
            }
            if (isset($id)) {
                $this->_setFileName($id, $group);
            }
            if ($this->_memoryCaching) {
                $this->_memoryCacheAdd($data);
                if ($this->_onlyMemoryCaching) {
                    return true;
                }
            }
            if ($this->_automaticCleaningFactor>0) {
                $rand = rand(1, $this->_automaticCleaningFactor);
                if ($rand==1) {
                    $this->clean(false, 'old');
                }
            }
            if ($this->_writeControl) {
                $res = $this->_writeAndControl($data);
                if (is_bool($res)) {
                    if ($res) {
                        return true;  
                    }
                    @touch($this->_file, time() - 2*abs($this->_lifetime));
                    return false;
                }            
            } else {
                $res = $this->_write($data);
            }
            if (is_object($res)) {
                if (!($this->_errorHandlingAPIBreak)) {   
                    return false; 
                }
            }
            return $res;
        }
        return false;
    }

    public function remove($id, $group = 'default', $checkBeforeUnlink = false)
    {
        $this->_setFileName($id, $group);
        if ($this->_memoryCaching) {
            if (isset($this->_memoryCachingArray[$this->_file])) {
                unset($this->_memoryCachingArray[$this->_file]);
                $this->_memoryCachingCounter = $this->_memoryCachingCounter - 1;
            }
            if ($this->_onlyMemoryCaching) {
                return true;
            }
        }
        if ($checkBeforeUnlink) {
            if (!file_exists($this->_file)) return true;
        }
        return $this->_unlink($this->_file);
    }

    public function clean($group = false, $mode = 'ingroup')
    {
        return $this->_cleanDir($this->_cacheDir, $group, $mode);
    }
    
    public function setToDebug()
    {
        $this->setOption('pearErrorMode', 8);
    }

    public function setLifetime($newLifetime)
    {
        $this->_lifetime = $newLifetime;
        $this->_setRefreshTime();
    }

    public function saveMemoryCachingState($id, $group = 'default')
    {
        if ($this->_caching) {
            $array = array(
                'counter' => $this->_memoryCachingCounter,
                'array' => $this->_memoryCachingArray
            );
            $data = serialize($array);
            $this->set($id, $data, $group);
        }
    }

    public function getMemoryCachingState($id, $group = 'default', $doNotTestCacheValidity = false)
    {
        if ($this->_caching) {
            if ($data = $this->get($id, $group, $doNotTestCacheValidity)) {
                $array = unserialize($data);
                $this->_memoryCachingCounter = $array['counter'];
                $this->_memoryCachingArray = $array['array'];
            }
        }
    }
    
    public function lastModified() 
    {
        return @filemtime($this->_file);
    }
    
    public function raiseError($msg, $code)
    {
        print $msg;
        return $code;
    }
    
    public function extendLife()
    {
        @touch($this->_file);
    }
    
    protected function _setRefreshTime() 
    {
        if (is_null($this->_lifetime)) {
            $this->_refreshTime = null;
        } else {
            $this->_refreshTime = time() - $this->_lifetime;
        }
    }
    
    protected function _unlink($file)
    {
        if (!@unlink($file)) {
            return $this->raiseError('Cache_Lite : Unable to remove cache !', -3);
        }
        return true;        
    }

    protected function _cleanDir($dir, $group = false, $mode = 'ingroup')     
    {
        if ($this->_fileNameProtection) {
            $motif = ($group) ? 'cache_'.md5($group).'_' : 'cache_';
        } else {
            $motif = ($group) ? 'cache_'.$group.'_' : 'cache_';
        }
        if ($this->_memoryCaching) {
	    foreach($this->_memoryCachingArray as $key => $v) {
                if (strpos($key, $motif) !== false) {
                    unset($this->_memoryCachingArray[$key]);
                    $this->_memoryCachingCounter = $this->_memoryCachingCounter - 1;
                }
            }
            if ($this->_onlyMemoryCaching) {
                return true;
            }
        }
        if (!($dh = opendir($dir))) {
            return $this->raiseError('Cache_Lite : Unable to open cache directory !', -4);
        }
        $result = true;
        while ($file = readdir($dh)) {
            if (($file != '.') && ($file != '..')) {
                if (substr($file, 0, 6)=='cache_') {
                    $file2 = $dir . $file;
                    if (is_file($file2)) {
                        switch (substr($mode, 0, 9)) {
                            case 'old':
                                if (!is_null($this->_lifetime)) {
                                    if ((mktime() - @filemtime($file2)) > $this->_lifetime) {
                                        $result = ($result and ($this->_unlink($file2)));
                                    }
                                }
                                break;
                            case 'notingrou':
                                if (strpos($file2, $motif) === false) {
                                    $result = ($result and ($this->_unlink($file2)));
                                }
                                break;
                            case 'callback_':
                                $func = substr($mode, 9, strlen($mode) - 9);
                                if ($func($file2, $group)) {
                                    $result = ($result and ($this->_unlink($file2)));
                                }
                                break;
                            case 'ingroup':
                            default:
                                if (strpos($file2, $motif) !== false) {
                                    $result = ($result and ($this->_unlink($file2)));
                                }
                                break;
                        }
                    }
                    if ((is_dir($file2)) and ($this->_hashedDirectoryLevel>0)) {
                        $result = ($result and ($this->_cleanDir($file2 . '/', $group, $mode)));
                    }
                }
            }
        }
        return $result;
    }

    protected function _memoryCacheAdd($data)
    {
        $this->_memoryCachingArray[$this->_file] = $data;
        if ($this->_memoryCachingCounter >= $this->_memoryCachingLimit) {
            list($key, ) = each($this->_memoryCachingArray);
            unset($this->_memoryCachingArray[$key]);
        } else {
            $this->_memoryCachingCounter = $this->_memoryCachingCounter + 1;
        }
    }

    protected function _setFileName($id, $group)
    { 
        if ($this->_fileNameProtection) {
            $suffix = 'cache_'.md5($group).'_'.md5($id);
        } else {
            $suffix = 'cache_'.$group.'_'.$id;
        }
        $root = $this->_cacheDir;
        if ($this->_hashedDirectoryLevel>0) {
            $hash = md5($suffix);
            for ($i=0 ; $i<$this->_hashedDirectoryLevel ; $i++) {
                $root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
            }   
        }
        $this->_fileName = $suffix;
        $this->_file = $root.$suffix;
    }
    
    protected function _read()
    {
        $fp = @fopen($this->_file, "rb");
        if ($this->_fileLocking) @flock($fp, LOCK_SH);
        if ($fp) {
            clearstatcache();
            $length = @filesize($this->_file);
            $mqr = get_magic_quotes_runtime();
            set_magic_quotes_runtime(0);
            if ($this->_readControl) {
                $hashControl = @fread($fp, 32);
                $length = $length - 32;
            } 
            if ($length) {
                $data = @fread($fp, $length);
            } else {
                $data = '';
            }
            set_magic_quotes_runtime($mqr);
            if ($this->_fileLocking) @flock($fp, LOCK_UN);
            @fclose($fp);
            if ($this->_readControl) {
                $hashData = $this->_hash($data, $this->_readControlType);
                if ($hashData != $hashControl) {
                    if (!(is_null($this->_lifetime))) {
                        @touch($this->_file, time() - 2*abs($this->_lifetime)); 
                    } else {
                        @unlink($this->_file);
                    }
                    return false;
                }
            }
            return $data;
        }
        return $this->raiseError('Cache_Lite : Unable to read cache !', -2); 
    }
    
    protected function _write($data)
    {
        if ($this->_hashedDirectoryLevel > 0) {
            $hash = md5($this->_fileName);
            $root = $this->_cacheDir;
            for ($i=0 ; $i<$this->_hashedDirectoryLevel ; $i++) {
                $root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
                if (!(@is_dir($root))) {
                    @mkdir($root, $this->_hashedDirectoryUmask);
                }
            }
        }
        $fp = @fopen($this->_file, "wb");
        if ($fp) {
            if ($this->_fileLocking) @flock($fp, LOCK_EX);
            if ($this->_readControl) {
                @fwrite($fp, $this->_hash($data, $this->_readControlType), 32);
            }
            $mqr = get_magic_quotes_runtime();
            set_magic_quotes_runtime(0);
            @fwrite($fp, $data);
            set_magic_quotes_runtime($mqr);
            if ($this->_fileLocking) @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }      
        return $this->raiseError('Cache_Lite : Unable to write cache file : '.$this->_file, -1);
    }
    
    protected function _writeAndControl($data)
    {
        $result = $this->_write($data);
        if (is_object($result)) {
            return $result; 
        }
        $dataRead = $this->_read();
        if (is_object($dataRead)) {
            return $dataRead; 
        }
        if ((is_bool($dataRead)) && (!$dataRead)) {
            return false; 
        }
        return ($dataRead==$data);
    }

    protected function _hash($data, $controlType)
    {
        switch ($controlType) {
        case 'md5':
            return md5($data);
        case 'crc32':
            return sprintf('% 32d', crc32($data));
        case 'strlen':
            return sprintf('% 32d', strlen($data));
        default:
            return $this->raiseError('Unknown controlType', -5);
        }
    }
}
