<?php
/************************************************************************/
/* AChecker                                                             */
/************************************************************************/
/* Copyright (c) 2008 - 2018                                            */
/* Inclusive Design Institute                                           */
/*                                                                      */
/* This program is free software. You can redistribute it and/or        */
/* modify it under the terms of the GNU General Public License          */
/* as published by the Free Software Foundation.                        */
/************************************************************************/
// $Id$

/**
* Root data access object
* Each table has a DAO class, all inherits from this class
* @access	public
* @author	Cindy Qi Li
* @package	DAO
*/

class DAO {

	protected $db;      // global database connection
	protected $is_sqlite = false;

	function __construct($db_host = NULL, $db_user = NULL, $db_pass = NULL, $db_name = NULL, $db_port = NULL)
	{
		if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
			$this->is_sqlite = true;
			if (!isset($this->db)) {
				try {
					$this->db = new PDO("sqlite:" . SQLITE_PATH);
					$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				} catch (PDOException $e) {
					die('Unable to connect to SQLite db: ' . $e->getMessage());
				}
			}
			return;
		}

		if(!isset($db_host) || !isset($db_user) || !isset($db_pass) || !isset($db_name) || !isset($db_port))
		{	
			$db_host = DB_HOST;
			$db_user = DB_USER;
			$db_pass = DB_PASSWORD;
			$db_name = DB_NAME;
			$db_port = DB_PORT;
		}

		if (!isset($this->db))
		{
			$this->db = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
			if (!$this->db) 
			{
				die('Unable to connect to db.');
			}
			if (!mysqli_select_db($this->db, $db_name)) 
			{
				die('DB connection established, but database "'.$db_name.'" cannot be selected.');
			}
		}
	}
	
	function execute($sql)
	{
		$sql = trim($sql);
		
		if ($this->is_sqlite) {
			try {
				$stmt = $this->db->query($sql);
				if (!$stmt) return true;
				
				// Check if it's a SELECT query
				if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'SHOW') === 0 || stripos($sql, 'DESCRIBE') === 0) {
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
					return count($rows) > 0 ? $rows : false;
				}
				return true;
			} catch (Exception $e) {
				die($sql . "<br />" . $e->getMessage());
			}
		}

		$result = mysqli_query($this->db, $sql) or die($sql . "<br />". mysqli_error($this->db));

		if ($result !== true && $result !== false) 
		{
			$rows = false;
			while ($row = mysqli_fetch_assoc($result))
			{
				if (!$rows) $rows = array();
			    $rows[] = $row;
			}
			mysqli_free_result($result);
			return $rows;
		}
		return true;
	}

	function addSlashes($string)
	{
		if ( function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() == 1 ) 
		{
			$string = stripslashes($string);
		} 

		if ($this->is_sqlite) {
			// PDO quote() adds the quotes, we need to remove them for AChecker compatibility
			$quoted = $this->db->quote($string);
			return substr($quoted, 1, -1);
		}

		return mysqli_real_escape_string($this->db, $string);
	}

	function getInsertID()
	{
		if ($this->is_sqlite) {
			return $this->db->lastInsertId();
		}
		return mysqli_insert_id($this->db);
	}
}
?>
