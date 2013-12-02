<?php
namespace zeus\database;

class Mysqlc implements DatabaseInterface
{
	private $conn;
	private $host;
	private $user;
	private $pwd;
	private $dbname;
	private $new;
	private $charset;
	private $sql = array();
	
	public function __construct($cfg) {
		$this->open($cfg);
		//取保连接关闭
		register_shutdown_function(array($this, 'close'));
	}
	
	public function open($cfg) {
		if (is_resource($this->conn))
			return;
	
		/**
		 * @abstract
		 * 尽量不使用mysql_pconnect
		 * mysql_pconnect,必须保证参数及其回话一致才可能使用同一个连接
		 */
		if (!$this->conn = mysql_connect($cfg['host'], $cfg['user'], $cfg['pwd'], $cfg['new'])) {
			throw new DatabaseException(sprintf('Unable to connect to MySQL server %s %s %s $s.', $cfg['host'], $cfg['user'], $cfg['pwd'],mysql_error()), 004);
		}
	
		$this->host = $cfg['host'];
		$this->user = $cfg['user'];
		$this->pwd = $cfg['pwd'];
		$this->new = $cfg['new'];
		$this->dbname = $cfg['name'];
		$this->charset = $cfg['charset'];
	}
	
	public function close() {
		//lock
		$this->log();
	
		if (is_resource($this->conn)) {
			mysql_close($this->conn);
	
			unset($this->conn);
		}
	}
	
	public function selectdb() {
		mysql_query('SET NAMES ' . $this->charset, $this->conn);
		if (!mysql_select_db($this->dbname, $this->conn)) {
			throw new LaunchException(sprintf('Unable to select %s .', $this->dbname), 004);
		}
	}
	
	public function query($sql) {
		//$func = $type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query') ? 'mysql_unbuffered_query' : 'mysql_query';
	
		if (!($query = mysql_query($sql, $this->conn) )) {
			throw new LaunchException(sprintf('Unable to query %s '
					, mysql_error($this->conn)));
		}
	
		$this->sql[] = $sql;
	
		return $query;
	}
	
	public function affected() {
		return mysql_affected_rows($this->conn);
	}
	
	public function uuid() {
		return ($id = mysql_insert_id($this->conn)) >= 0 ? $id : $this->getResult("SELECT last_insert_id()", 0);
	}
	
	public function execute($sql, $result_type = 'ALL') 
	{
		if (is_numeric($result_type)) 
		{
			return $this->getResult($sql, $result_type);
		} 
		else if ('one' == strtolower($result_type)) 
		{
			return $this->fetch($sql);
		} 
		else 
		{
			return $this->fetchAll($sql);
		}
	}
	
	public function ping() 
	{
		return mysql_ping($this->conn);
	}
	
	private function fetch($sql) 
	{
		$query = $this->query($sql);
		$result = mysql_fetch_array($query, MYSQL_ASSOC);
		mysql_free_result($query);
	
		return $result;
	}
	
	private function fetchAll($sql) 
	{
		$query = $this->query($sql);
		$rows = array();
		while (($row = mysql_fetch_array($query, MYSQL_ASSOC))) 
		{
			$rows[] = $row;
		}
		mysql_free_result($query);
		return $rows;
	}
	
	private function getResult($sql, $index = 0) 
	{
		$query = $this->query($sql);
		$result = mysql_fetch_array($query, MYSQL_NUM);
		mysql_free_result($query);
	
		return (!empty($result) && is_array($result)) ? $result[$index] : null;
	}
	
	public function startTransaction() 
	{
		$this->query('START TRANSACTION');
	}
	
	public function commit() {
		$this->query('COMMIT');
	}
	
	public function rollback() {
		$this->query('ROLLBACK');
	}
	
	public function startXaTransaction()
	{
		
	}
	
	public function xaCommit()
	{
		
	}
	
	public function xaRollback()
	{
		
	}
	
	public function log() {
		return $this->sql;
	}
}