<?php
namespace zeus\cache;

class XCache
{
    private $_lifetime;

	public function __construct($lifetime = 86400)
	{
        $this->_lifetime = (int) $lifetime;
	}

	public function set($id, $data, $lifetime = null)
	{
		$lifetime = (null !== $lifetime) ? (int) $lifetime : $this->_lifetime;
        return xcache_set($id, $data, $lifetime);
	}
    
	public function get($id)
    {
        if (xcache_isset($id)) {
            return xcache_get($id);
        }
        return false;
	}

	public function remove($id)
    {
        return xcache_unset($id);
	}
}
