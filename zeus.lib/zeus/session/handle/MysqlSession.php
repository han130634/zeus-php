<?php
namespace zeus\session\handle;

class MysqlSession
{
	//mysql的主机地址，需要第三方指定ip地址 
    private $db_host;

    //数据库用户名，需要第三方指定自己的用户名
    private $db_user;

    //数据库密码，需要第三方指定自己的库据库密码
    private $db_pwd;

    //数据库，需要第三方指定数据库
    private $db_name;

    //数据库表，需要第三方指定数据表
    private $db_table;

    //数据库表，需要第三方指定连接字符集
    private $db_charset = "utf8";

    //mysql-handle
    private $db_handle;
    //session-lifetime
    private $lifeTime;
    
    public function __construct($cfg)
    {
    	list($db_host,$db_user,$db_pwd,$db_name,$db_table,$db_charset) = $cfg;
    	
    	$db_charset or $db_charset ='utf8';
    	
    	$this->lifeTime = get_cfg_var("session.gc_maxlifetime");
    	$this->db_host = $db_host;
    	$this->db_user = $db_user;
    	$this->db_pwd = $db_pwd;
    	$this->db_name = $db_name;
    	$this->db_table = $db_table;
    	$this->db_charset = $db_charset;
    	
    	//取保连接关闭
    	register_shutdown_function(array($this,'_close'));
    }
    
    public function _close()
    {
    	if( $this->db_handle )
    	{
	    	@mysql_close($this->db_handle);
	    	$this->db_handle = NULL;
    	}
    	
    	return true;
    }
    
    public function open($savePath, $sessName) 
    {
    	$this->db_handle = mysql_connect($this->db_host,$this->db_user,$this->db_pwd, true);
        if ($this->db_handle) 
        {
            mysql_query('SET NAMES ' . self::db_charset, $this->db_handle);
            mysql_select_db(self::db_name, $this->db_handle);
            
            return true;
        }
        return false;
    }

    public function close() 
    {
        $this->gc($this->lifeTime);
        
        return $this->_close();
    }

    public function read($sessID) 
    {
        // fetch session-data
        $res = @mysql_query("SELECT session_data AS d FROM " . self::db_table . " 
            WHERE session_id = '$sessID'
            AND session_expires > " . time(), $this->db_handle);

        // return data or an empty string at failure
        if ($row = @mysql_fetch_assoc($res)) {
            return $row['d'];
        }
        return "";
    }

    public function write($sessID, $sessData) 
    {
        $ip = ip();
        $time = time();

        // new session-expire-time
        $newExp = time() + $this->lifeTime;

        // is a session with this id in the database?
        $res = @mysql_query("SELECT * FROM " . self::db_table . " 
            WHERE session_id = '$sessID'", $this->db_handle);

        $sessData = mysql_real_escape_string($sessData);

        // if yes,
        if (mysql_num_rows($res)) 
        {
            // ...update session-data
            mysql_query("UPDATE " . self::db_table . " 
                SET session_expires = '$newExp',
                session_data = '$sessData',
                activeip = '$ip' 
                WHERE session_id = '$sessID'", $this->db_handle);

            // if something happened, return true
            if (@mysql_affected_rows($this->db_handle)) 
            {
                return true;
            }
        } 
        else 
        { // if no session-data was found,
            // create a new row
            @mysql_query("INSERT INTO " . self::db_table . " (
                session_id,
                platform,
                createtime,
                createip,
                activeip,
                session_expires,
                session_data) 
                VALUES (
                    '$sessID',
                    'php',
                    '$time',
                    '$ip',
                    '$ip',
                    '$newExp',
                    '$sessData')", $this->db_handle);

            // if row was created, return true
            if (@mysql_affected_rows($this->db_handle)) 
            {
                return true;
            }
        }

        // an unknown error occured
        return false;
    }

    public function destroy($sessID) 
    {
        // delete session-data
        @mysql_query("DELETE FROM " . self::db_table . " WHERE session_id = '$sessID'", $this->db_handle);

        // if session was deleted, return true,
        if (@mysql_affected_rows($this->db_handle))
            return true;

        // ...else return false
        return false;
    }

    public function gc($sessMaxLifeTime) 
    {
        // delete old sessions
        @mysql_query("DELETE FROM " . self::db_table . " WHERE session_expires < " . time(), $this->db_handle);

        // return affected rows
        return @mysql_affected_rows($this->db_handle);
    }
}
