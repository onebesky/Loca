<?php

/**
 *
 * EDMSAuthManager.php
 *
 * Integrates the extension mongodbauthmanager
 * @link http://www.yiiframework.com/extension/mongodbauthmanager/
 *
 * Usage: Install as component in config/main.php

 'authManager'=>array(
				 'class'=>'CMongoDbAuthManager',
				 //'connectionId'=>'edms', (default)
				 //'authFile' => 'edms_authmanager' (default, is now the collection name)
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

class EDMSAuthManager extends CPhpAuthManager
{
	const DEFAULT_CONFIG = 'default';

	/**
	 * @var string the component id of the connection component
	 * @see EDMSConnection
	 * default is 'edms' if set to null
	 */
	public $connectionId = null;

	/**
	 * @var string the database name of the connection
	 * if you want to override the one from the connection component
	 * default is the dbName from the connection component if set to null
	 * @see EDMSConnection.php
	 */
	public $dbName = null;

	//The authFile property is the collection name
	public $authFile = 'edms_authmanager';

	private $_id;  //the MongoId
	private $_configId;


	/**
	 * MongoCmsAuthManager::__construct()
	 *
	 * @param mixed $configId
	 */
	public function __construct($configId = null)
	{
		$this->configId = $configId;
	}

	/**
	 * Set the configId
	 * Set configId to 'default' if is empty
	 *
	 * @param string $configId
	 */
	public function setConfigId($configId)
	{
		$this->_configId = empty($configId) ? self::DEFAULT_CONFIG : $configId;
	}

	/**
	 * Get the configId
	 *
	 * @return string
	 */
	public function getConfigId()
	{
		return $this->_configId;
	}

	/**
	 * Switch to another config
	 *
	 * @param mixed $configId
	 */
	public function switchConfig($configId = null, $loadData = false)
	{
		$this->configId = $configId;
		$this->_id = null;

		if ($loadData)
			$this->init(); //load from mongodb
	}

	/**
	 * Returns current edmsMongoCollection object
	 * By default this method use {@see authFile}
	 *
	 * @return edmsMongoCollection
	 */
	public function getCollection($collectionName = null)
	{
		if ($collectionName)
			$collectionName = $this->authFile;

		return Yii::app()->edmsMongoCollection($collectionName,$this->dbName,$this->connectionId);
	}

	/**
	 * Loads the authorization data from mongo db
	 *
	 * @param string $file is the collection name
	 * @return array the authorization data
	 * @see saveToFile
	 */
	protected function loadFromFile($file)
	{
		$collection = $this->getCollection($file);
		$criteria = array('configId' => $this->configId);
		$data = $collection->findOne($criteria);

		if (empty($data))
			return array();

		// remove _id from data, because it's not an AuthItem
		if (isset($data['_id']))
		{
			$this->_id = $data['_id'];
			unset($data['_id']);
		}

		// remove configId from data, because it's not an AuthItem
		if (isset($data['configId']))
		{
			$this->configId = $data['configId'];
			unset($data['configId']);
		}

		return $data;
	}

	/**
	 * Saves the authorization data from the collection 'file'
	 *
	 * @param array $data the authorization data
	 * @param string $file the collection name
	 * @see loadFromFile
	 */
	protected function saveToFile($data, $file)
	{
		$collection = $this->getCollection($file);

		//have to set the _id for scenario update
		if (isset($this->_id))
			$data['_id'] = new MongoId($this->_id);

		$data['configId'] = $this->configId;

		$collection->save($data);

		//if this is a new record the _id value is created
		//assign $this->_id is important when authManager->save() is called more than once
		$this->_id = $data['_id'];
	}
}