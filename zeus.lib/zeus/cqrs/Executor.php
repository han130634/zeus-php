<?php
namespace zeus\cqrs;

use zeus\Zeus;

class Executor
{
	private static $pool = array();
	private static $db;
	private static $key;
	
	public static function pool()
	{
		return self::$pool;
	}
	
	public static function log()
	{
		return self::$db->log();
	}
	
	public static function open( $key = 'default')
	{
		if( $key == self::$key )
			return self::$db;
		
		$cfg = Zeus::get("store_{$key}");
		if(!is_array($cfg))
		{
			throw new \Exception("Not Found store configure");
		}
		
		if (!isset(self::$pool[$key])) {
			self::$pool[$key] = new Mysqlc($cfg);
		}
		
		self::$key = $key;
		self::$db = self::$pool[$key];
		self::$db->selectdb();
		
		return self::$db;
	}
	
	public final static function insert($table, $fields) 
	{
		$insertkeysql = $insertvaluesql = $comma = '';
		foreach ($fields as $insert_key => $insert_value) {
			$insertkeysql .= $comma . '`' . $insert_key . '`';
			if (self::is_numeric($insert_value)) {
				$insertvaluesql .= $comma . $insert_value;
			} else {
				$insertvaluesql .= $comma . '\'' . self::formatSprintf($insert_value) . '\'';
			}
			$comma = ', ';
		}
	
		$sql = sprintf('INSERT INTO `%s` ( %s ) VALUES ( %s )', $table, $insertkeysql, $insertvaluesql);
		//echo $sql;exit;
		self::$db->query($sql);
	
		return self::$db->autoincrment();
	}
	
	public final static function update($table, $fields, $wheresql) 
	{
		$setsql = $comma = '';
		foreach ($fields as $set_key => $set_value) {
			if (self::is_numeric($set_value)) {
				$setsql .= $comma . '`' . $set_key . '`' . '=' . $set_value;
			} else {
				$setsql .= $comma . '`' . $set_key . '`' . '=\'' . self::formatSprintf($set_value) . '\'';
			}
			$comma = ', ';
		}
	
		$where = $_where = $comma = '';
		if (is_array($wheresql)) 
		{
			foreach ($wheresql as $key => $value) 
			{
				if (self::is_numeric($value)) {
					$_where .= $comma . '`' . $key . '`' . '=' . $value;
				} else {
					$_where .= $comma . '`' . $key . '`' . '=\'' . self::formatSprintf($value) . '\'';
				}
				$comma = ' AND ';
			}
		} else {
			$_where = $wheresql;
		}
	
		if (!empty($_where)) {
			$where = ' where ' . $_where;
		}
	
		$sql = sprintf('update `%s` set %s %s', $table, $setsql, $where);
		self::$db->query($sql);
	
		return self::$db->affected();
	}
	
	public final static function replace($table, $fields) {
		$insertkeysql = $insertvaluesql = $comma = '';
		foreach ($fields as $insert_key => $insert_value) {
			$insertkeysql .= $comma . '`' . $insert_key . '`';
			if (self::is_numeric($insert_value)) {
				$insertvaluesql .= $comma . $insert_value;
			} else {
				$insertvaluesql .= $comma . '\'' . self::formatSprintf($insert_value) . '\'';
			}
			$comma = ', ';
		}
	
		$sql = sprintf('REPLACE INTO `%s` ( %s ) VALUES ( %s )', $table, $insertkeysql, $insertvaluesql);
		self::$db->query($sql);
	
		return self::$db->affected();
	}
	
	public final static function delete($table, $wheresql = '') {
		$where = $_where = $comma = '';
		if (is_array($wheresql)) {
			foreach ($wheresql as $key => $value) {
				if (self::is_numeric($value)) {
					$_where .= $comma . '`' . $key . '`' . '=' . $value;
				} else {
					$_where .= $comma . '`' . $key . '`' . '=\'' . self::formatSprintf($value) . '\'';
				}
				$comma = ' AND ';
			}
		} else {
			$_where = $wheresql;
		}
	
		if (!empty($_where)) {
			$where = ' where ' . $_where;
		}
	
		$sql = sprintf('DELETE FROM `%s` %s ', $table, $where);
		self::$db->query($sql);
	
		return self::$db->affected();
	}
	
	public final static function inserts($table, $fieldsArr) {
		if (count($fieldsArr) < 1)
			return 0;
	
		$insertkeysql = $insertvaluesql = $comma = $out = '';
		$keys = array_keys(current($fieldsArr));
	
		foreach ($keys as $insert_key) {
			$insertkeysql .= $comma . '`' . $insert_key . '`';
			$comma = ', ';
		}
	
		foreach ($fieldsArr as $fields) {
			$in = '';
			$insertvaluesql.=$out . '(';
			foreach ($fields as $insert_value) {
				if (self::is_numeric($insert_value)) {
					$insertvaluesql .= $in . $insert_value;
				} else {
					$insertvaluesql .= $in . '\'' . self::formatSprintf($insert_value) . '\'';
				}
				$in = ',';
			}
			$insertvaluesql.=')';
			$out = ',';
		}
	
		$sql = sprintf('INSERT INTO `%s` ( %s ) VALUES %s ', $table, $insertkeysql, $insertvaluesql);
		self::$db->query($sql);
		return self::$db->affected();
	}
	
	public final static function replaces($table, $fieldsArr) {
		if (count($fieldsArr) < 1)
			return 0;
	
		$insertkeysql = $insertvaluesql = $comma = $out = '';
		$keys = array_keys($fieldsArr[0]);
	
		foreach ($keys as $insert_key) {
			$insertkeysql .= $comma . '`' . $insert_key . '`';
			$comma = ', ';
		}
	
		foreach ($fieldsArr as $fields) {
			$in = '';
			$insertvaluesql.=$out . '(';
			foreach ($fields as $insert_value) {
				if (self::is_numeric($insert_value)) {
					$insertvaluesql .= $in . $insert_value;
				} else {
					$insertvaluesql .= $in . '\'' . self::formatSprintf($insert_value) . '\'';
				}
				$in = ',';
			}
			$insertvaluesql.=')';
			$out = ',';
		}
	
		$sql = sprintf('REPLACE INTO `%s` ( %s ) VALUES %s ', $table, $insertkeysql, $insertvaluesql);
	
		self::$db->query($sql);
		return self::$db->affected();
	}
	
	public final static function result($sql, $index = 0) {
		$result = self::$db->execute($sql, $index);
		return $result;
	}
	
	public final static function execute($statement, $paramArray = array()) {
	
		foreach ($paramArray as $value) {
			if (is_string($value)) {
				$statement = preg_replace('/\?/', '\'' . self::formatSprintf($value) . '\'', $statement, 1);
			} else {
				$statement = preg_replace('/\?/', $value, $statement, 1);
			}
		}
	
		if (!empty($statement)) {
			self::$db->query($statement);
		}
	}
	
	/**
	 * 执行数据库查询，并获得结果集第一行记录
	 *
	 * @param String $sql
	 * @param int $result_type
	 * @return unknown
	 */
	public static function fetch($sql) {
		return self::$db->execute($sql, 'one');
	}
	
	/**
	 *
	 * @param String $sql
	 */
	public static function fetchAll($sql) {
		return self::$db->execute($sql, 'ALL');
	}
	
	
	private static function is_numeric($value){
		if( is_numeric($value) && isset($value[0]) && '0' != $value[0] ){
			return true;
		}
		return false;
	}
	
	private static function formatSprintf($str){
		return mysql_real_escape_string(stripslashes($str));
	}
}