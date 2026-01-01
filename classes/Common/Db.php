<?php
namespace Classes\Common;
use Classes;

/**
 *
 * @class Db
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Db
{
	private $connection;
	private $lastId;
	private $result;
	private $log;
	
	public function __construct($dbHostname=DB_HOSTNAME, $dbUsername=DB_USERNAME, $dbPassword=DB_PASSWORD, $dbDatabase=DB_DATABASE)
	{
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
	    require_once($_SERVER['DOCUMENT_ROOT'] . '/docker-config.php');
	    $this->log = new Log ('classes - Common - Db.log');
	    
	    $this->connection = mysqli_connect($dbHostname, $dbUsername, $dbPassword, $dbDatabase);
	    if (!$this->connection){
	        die("Connection failed: " . mysqli_connect_error());
	    }
	    $this->connection->set_charset('utf8');
	}
	
	public function __destruct()
	{
	    mysqli_close($this->connection);
	}
	
	public function execQuery($sql)
	{
		//require_once('classes/log.php');
		//$logger = new Log ('tmp.log');
		//$logger -> write ($sql);
	    
	    $this->result = mysqli_query($this->connection, $sql);
	    $this->lastId = $this->connection->insert_id;
	    
	    if ($this->result)
    	    return $this->result;
	}
	
	public function execQueryArray($sql)
	{
	    $return = array();
		//require_once('classes/log.php');
		//$logger = new Log ('tmp.log');
		//$logger -> write ($sql);
	    $this->result = mysqli_query($this->connection, $sql);
		
	    if($this->result)
		{
			 // Cycle through results
		    while ($row = $this->result->fetch_assoc()){
				$return[] = $row;
			}
			// Free result set
			$this->result->close();
		}
		return $return;
	}
	
	public function insert ($table, $fields, $values)
	{
	    $this->log->write (__LINE__ . ' insert.table - ' . $table);
	    $this->log->write (__LINE__ . ' insert.fields - ' . json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
//	    $this->log->write (__LINE__ . ' insert.values - ' . json_encode($values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    if (count($fields) != count ($values))
	        return false;
	    
	    $sql = 'insert into '. $table;
	    $sql .= ' ('. implode(',', $fields) . ')';
	    $sql .= ' values ';
	    $tmpValues = array();
	    foreach ($values as $value)
	    {
	        if(gettype($value) == 'string')
	            $tmpValues[] = '"' . $value . '"';
	        else
	            $tmpValues[] = $value;
	    }
	    $sql .= '('. implode(',', $tmpValues) . ')';
	    
//	    $this->log->write (__LINE__ . ' sql - ' . $sql);
	    $this->result = $this->execQuery($sql);
	    
	    $this->log->write (__LINE__ . ' insert.result - ' . json_encode($this->result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $this->log->write (__LINE__ . ' insert.error - ' . json_encode($this->connection->error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $this->lastId;
	}
	
	public function truncate ($table)
	{
	    $this->log->write (__LINE__ . ' truncate.table - ' . $table);
	    $sql = 'truncate table '. $table;
	    $this->result = $this->execQuery($sql);
	    $this->log->write (__LINE__ . ' truncate.result - ' . json_encode($this->result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    $this->log->write (__LINE__ . ' truncate.error - ' . json_encode($this->connection->error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	    return $this->result;
	}
}
?>