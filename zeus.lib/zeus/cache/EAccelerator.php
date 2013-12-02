<?php
namespace zeus\cache;

class EAccelerator
{
    private $_lifetime;

	public function __construct($lifetime = 86400)
	{
        $this->_lifetime = (int) $lifetime;
	}

	public function set($id, $data, $lifetime = null)
	{
		$lifetime = (null !== $lifetime) ? (int) $lifetime : $this->_lifetime;
        return eaccelerator_put($id, $data, $lifetime);
	}
    
	public function get($id)
    {
        return eaccelerator_get($id);
	}

	public function remove($id)
    {
        return eaccelerator_rm($id);
	}
}
