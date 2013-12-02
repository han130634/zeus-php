<?php
namespace zeus\cache;

class APC
{
    private $_lifetime;

	public function __construct($lifetime = 86400)
	{
        $this->_lifetime = (int) $lifetime;
	}

	public function set($id, $data, $lifetime = null)
	{
		$lifetime = (null !== $lifetime) ? (int) $lifetime : $this->_lifetime;
		return apc_store($id, $data, $lifetime);
	}
    
	public function get($id)
	{
		return apc_fetch($id);
	}

	public function remove($id)
	{
		return apc_delete($id);
	}
}
