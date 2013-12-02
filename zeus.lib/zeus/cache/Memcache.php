<?php
namespace zeus\cache;

use zeus\Zeus;
use zeus\config\ConfigInterface;

class Memcache
{
    private $_mem;
    private $_options = array();

    public function __construct($options = array())
    {
        if (!extension_loaded('memcache')) {
            throw new Exception('The memcache extension must be loaded before use');
        }

        if (empty($options)) {
        	
        	$options = Zeus::getInstance()->getConfig()->memcacheCfg();
        } 
        
        $this->_options['host'] = isset($options['host']) ? $options['host'] : 'localhost';
        $this->_options['port'] = isset($options['port']) ? $options['port'] : 11211;
        $this->_options['pconnect'] = isset($options['pconnect']) ? $options['pconnect'] : false;
        $this->_options['lifetime'] = isset($options['lifetime']) ? $options['lifetime'] : 86400;
        $this->_options['compressed'] = isset($options['compressed']) ? ($options['compressed'] ? MEMCACHE_COMPRESSED : 0) : 0;

        $this->_mem = new \Memcache();
        $func = ($this->_options['pconnect']) ? 'pconnect' : 'connect';
        $conn = $this->_mem->$func($this->_options['host'], $this->_options['port']);
        if (!$conn) {
            throw new \Exception('Connect memcache server failed');
        }
    }

    public function set($id, $data, $lifetime = null)
    {
        $lifetime = ($lifetime === null) ? $this->_options['lifetime'] : (int) $lifetime;
        $this->_mem->set($id, $data, $this->_options['compressed'], $lifetime);
    }

    public function get($id)
    {
        return $this->_mem->get($id);
    }

    public function replace($id, $data, $lifetime = null)
    {
        $lifetime = ($lifetime === null) ? $this->_options['lifetime'] : (int) $lifetime;
        $this->_mem->replace($id, $data, $this->_options['compressed'], $lifetime);
    }

    public function remove($id)
    {
        return $this->_mem->delete($id);
    }

    public function clean()
    {
        return $this->_mem->flush();
    }

    public function close()
    {
        return $this->_mem->close();
    }
}
