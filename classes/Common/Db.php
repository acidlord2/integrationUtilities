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
	
	public function __construct($dbHostname = null, $dbUsername = null, $dbPassword = null, $dbDatabase = null)
	{
	    $docroot = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
		require_once($docroot . '/docker-config.php');
	    require_once($docroot . '/classes/Common/Log.php');
	    
	    // Use provided parameters or fall back to constants
	    $dbHostname = $dbHostname ?? DB_HOSTNAME;
	    $dbUsername = $dbUsername ?? DB_USERNAME;
	    $dbPassword = $dbPassword ?? DB_PASSWORD;
	    $dbDatabase = $dbDatabase ?? DB_DATABASE;
	    
		$fileName = str_replace(['/', '\\'], ' - ', str_replace($docroot, '', __FILE__));
	    $fileName = ltrim($fileName, " -") . '.log';
	    $this->log = new Log ($fileName);
	    
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
	    try{
			$this->result = mysqli_query($this->connection, $sql);
			
			// Log MySQL errors if query failed
			if (!$this->result) {
				$this->log->write (__LINE__ . ' execQuery.sql - ' . $sql);
				$this->log->write (__LINE__ . ' execQuery.mysql_error - ' . $this->connection->error);
				$this->log->write (__LINE__ . ' execQuery.mysql_errno - ' . $this->connection->errno);
			}
			
			if ($this->result)
				$this->lastId = $this->connection->insert_id;
				return $this->result;
		}
		catch (Exception $e){
			$this->log->write (__LINE__ . ' Exception: ' . $e->getMessage());
			return false;
		}
	    return false;
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
	
	public function getLastInsertId()
	{
	    return $this->lastId;
	}
	
	/**
	 * Get the last MySQL error message
	 * @return string MySQL error message
	 */
	public function getLastError()
	{
		return $this->connection->error;
	}
	
	/**
	 * Get the last MySQL error number
	 * @return int MySQL error number
	 */
	public function getLastErrorNumber()
	{
		return $this->connection->errno;
	}
	
	/**
	 * Check if there was a MySQL error
	 * @return bool True if there was an error
	 */
	public function hasError()
	{
		return !empty($this->connection->error);
	}
}
?>