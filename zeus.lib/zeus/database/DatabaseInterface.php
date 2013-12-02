<?php
namespace zeus\database;

interface DatabaseInterface
{
	public function open(array $cfg);
	
	public function close();
	
	public function selectdb();
	
	public function query($sql);
	
	public function affected();
	
	public function uuid();
	
	public function execute($sql, $result_type = 'ALL');
	
	public function ping();
	
	public function startTransaction();
	
	public function commit();
	
	public function rollback();
	
	public function startXaTransaction();
	
	public function xaCommit();
	
	public function xaRollback();
	
	public function log();
}