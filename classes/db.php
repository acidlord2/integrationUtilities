<?php
/**
 *
 * @class Db
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class Db
{
	private static $db = null;
	
	public static function get_connection()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		// if connection exists
		if (self::$db != null)
			return self::$db;
		// else
		
		// Create connection
		$connection = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		// Check connection
		if (!$connection)
			die("Connection failed: " . mysqli_connect_error());
		
		mysqli_set_charset($connection,"utf8");
		
		self::$db = $connection;
		return $connection;
	}
	
	public static function close_connection()
	{
		// if connection exists
		if (self::$db == null)
		{
			mysqli_close(self::$db);
			return true;
		}
		else
			return false;
	}

	public static function exec_query($sql)
	{
		//require_once('classes/log.php');
		//$logger = new Log ('tmp.log');
		//$logger -> write ($sql);
		if (self::$db == null)
			self::$db = self::get_connection();
		self::next_result();
		$result = mysqli_query(self::$db, $sql);
		return $result;
	}
	
	public static function exec_query_array($sql)
	{
		//require_once('classes/log.php');
		//$logger = new Log ('tmp.log');
		//$logger -> write ($sql);
		if (self::$db == null)
			self::$db = self::get_connection();
		$result = mysqli_query(self::$db, $sql);
		
		if($result)
		{
			 // Cycle through results
			while ($row = $result->fetch_assoc()){
				$return[] = $row;
			}
			// Free result set
			$result->close();
			self::next_result();
		}
		return isset ($return) ? $return : array();
	}


	public static function next_result()
	{
		if (self::$db != null && mysqli_more_results(self::$db))
			mysqli_next_result (self::$db);
	}
}
?>