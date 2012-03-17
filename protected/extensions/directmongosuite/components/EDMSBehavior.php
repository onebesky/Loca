<?php

/**
 * EDMSBehavior.php
 *
 * Install this application behavior in config/main.php
 *
 *
   'behaviors' => array(
      'edms' => array(
 	            'class'=>'EDMSBehavior',
 	            //'debug'=>true
   	            )
   ),

 *
 * PHP version 5.2+
 *
 * @author Joe Blocher <yii@myticket.at>
 * @copyright 2011 myticket it-solutions gmbh
 * @license New BSD License
 * @category Database
 * @package directmongosuite
 * @version 0.1
 * @since 0.1
 */

class EDMSBehavior extends CBehavior
{

	public $connectionId = 'edms';
	public $debug = false;

	protected static $_connection;
	protected static $_collection;
	protected static $_db;


	/**
	 * EDMS::edmsMongo()
	 *
	 * @param string $connectionId
	 * @return
	 */
	public function edmsMongo($connectionId = null)
	{
		if (!isset(self::$_connection))
			self::$_connection = array();

		if (empty($connectionId))
			$connectionId = $this->edmsGetConnectionId();

		if (!isset(self::$_connection[$connectionId]))
		{
		   if ($this->edmsIsDebugMode())
		      self::edmsLog("Connecting to connectionId: $connectionId","edms.debug.connection.$connectionId");

		   self::$_connection[$connectionId] = EDMSConnection::instance($connectionId)->getMongo();
		}

		return self::$_connection[$connectionId];
	}

	/**
	 * Get edmsMongoDb instance
	 * @since v1.0
	 */
	public function edmsMongoDb($dbName=null,$connectionId = null)
	{
		if (empty($dbName))
			$dbName = $this->edmsGetDbName($connectionId);

		if (empty($connectionId))
			$connectionId = $this->edmsGetConnectionId();

		if (!isset(self::$_db))
			self::$_db = array();

		if (!isset(self::$_db[$connectionId][$dbName]))
		{
			if ($this->edmsIsDebugMode())
			   self::edmsLog("Selecting db: $connectionId.$dbName","edms.debug.db.$dbName.$connectionId");

			self::$_db[$connectionId][$dbName] = $this->edmsMongo($connectionId)->selectDB($dbName);
		}

		return self::$_db[$connectionId][$dbName];
	}

	/**
	 * Returns current edmsMongoCollection object
	 *
	 * @return edmsMongoCollection
	 */
	public function edmsMongoCollection($collectionName,$dbName=null,$connectionId = null)
	{
		if (empty($dbName))
			$dbName = $this->edmsGetDbName($connectionId);

		if (empty($connectionId))
			$connectionId = $this->edmsGetConnectionId();


		if (!isset(self::$_collection))
			self::$_collection = array();


		if (!isset(self::$_collection[$connectionId][$dbName][$collectionName]))
		{
			if ($this->edmsIsDebugMode())
		 	    self::edmsLog("Selecting collection: $collectionName.$dbName.$connectionId",
		 	    	            "edms.debug.collection.$connectionId.$dbName.$collectionName");

			$collection = $this->edmsMongoDb($dbName,$connectionId)->selectCollection($collectionName);
			self::$_collection[$connectionId][$dbName][$collectionName] = $collection;
		}


		return self::$_collection[$connectionId][$dbName][$collectionName];
	}

	/**
	 * Returns current edmsMongoCollection object
	 *
	 * @return edmsMongoCollection
	 */
	public function edmsQuery($collectionName,$dbName=null,$connectionId=null)
	{
		if ($this->edmsIsDebugMode())
			self::edmsLog("Creating edmsQuery: $connectionId/$dbName/$collectionName",
				           "edms.debug.query.$connectionId.$dbName.$collectionName");

		return new EDMSQuery($collectionName,$dbName,$connectionId);
	}

	/**
	 * EDMSBehavior::edmsSetConnectionId()
	 *
	 * @param mixed $connectionId
	 * @return
	 */
	public function edmsGetConnectionId()
	{
	  return $this->connectionId;
	}

	/**
	 * EDMSBehavior::edmsSetConnectionId()
	 *
	 * @param mixed $connectionId
	 * @return
	 */
	public function edmsSetConnectionId($connectionId)
	{
		if (!empty($connectionId) && $this->edmsGetConnectionId() != $connectionId)
		    $this->connectionId = $connectionId;
	}

	/**
	 * EDMSBehavior::edmsGetDbName()
	 *
	 * @param mixed $connectionId
	 * @return
	 */
	public function edmsGetDbName($connectionId = null)
	{
		if (empty($connectionId))
			$connectionId = $this->edmsGetConnectionId();

		$connection = EDMSConnection::instance($connectionId);
		return $connection->dbName;
	}

	/**
	 * EDMSBehavior::edmsGetDbName()
	 *
	 * @param mixed $connectionId
	 * @return
	 */
	public function edmsSetDbName($dbName,$connectionId = null)
	{
		$this->edmsSetConnectionId($connectionId);

		if (!empty($dbName) && $this->edmsGetDbName() != $dbName)
			EDMSConnection::instance($this->edmsGetConnectionId())->dbName = $dbName;
	}

	/**
	 * EDMSBehavior::log()
	 *
	 * @param mixed $msg
	 * @return
	 */
	public static function edmsLog($msg,$category='info',$level='edms')
	{
		Yii::log($msg,$level,$category);
	}

	/**
	 * EDMSBehavior::edmsIsDebugMode()
	 *
	 * @param mixed $msg
	 * @return
	 */
	public function edmsIsDebugMode()
	{
		return $this->debug;
	}



}