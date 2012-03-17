<?php
/**
 * EDMSHttpSession.php
 *
 * Integrated version of the extension mongodbhttpsession by aoyagikouhei
 * @link http://www.yiiframework.com/extension/mongodbhttpsession/
 *
 * Auther aoyagikouhei
 *
 * 2011/08/02 ver 1.2
 * Modify destroySession
 *
 * * 2011/08/01 ver 1.1
 * Add fsync, safe, timeout options
 *
 * 2011/07/05 ver 1.0
 * First release
 *
 *
 * Changed config by joblo:
 *
 * In config/main.php:
     'session'=>array(
                'class'=>'ext.directmongosuite.components.EDMSHttpSession',
    ),
 *
 * More options:
 * connectionId     : default null - (means 'edms' from the EDMSBehavior)
 * dbName           : default database name     : default null
 * collectionName   : collaction name     		: default 'emds_httpsession'
 * idColumn         : id column name      		: default id
 * dataColumn       : data column name    		: default dada
 * expireColumn     : expire column name  		: default expire
 * fsync            : fsync flag          		: defalut false
 * safe             : safe flag           		: defalut false
 * timeout          : timeout miliseconds 		: defalut null i.e. MongoCursor::$timeout
 *
 * PHP version 5.2+
 *
 * @author aoyagikouhei (modified by joblo)
 * @copyright aoyagikouhei
 * @license New BSD License
 * @category Database
 * @package directmongosuite
 * @version 0.1
 * @since 0.1
 */
class EDMSHttpSession extends CHttpSession
{
	/**
	 * @var string Collection name
	 */
	public $collectionName="emds_httpsession";

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

	/**
	 * @var string id column name
	 */
	public $idColumn = 'id';

	/**
	 * @var string level data name
	 */
	public $dataColumn="data";

	/**
	 * @var string expire column name
	 */
	public $expireColumn="expire";

	/**
	 * @var boolean forces the update to be synced to disk before returning success.
	 */
	public $fsync = false;

	/**
	 * @var boolean the program will wait for the database response.
	 */
	public $safe = false;

	/**
	 * @var boolean if "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response.
	 */
	public $timeout = null;

	/**
	 * @var edmsMongo mongo Db collection
	 */
	private $collection;

	/**
	 * @var array insert options
	 */
	private $options;

	/**
	 * Initializes the route.
	 * This method is invoked after the route is created by the route manager.
	 */
	public function init()
	{
		$this->collection = Yii::app()->edmsMongoCollection($this->collectionName,$this->dbName,$this->connectionId);
		$this->options = array(
		  'fsync' => $this->fsync
		  ,'safe' => $this->safe
		);
		if (!is_null($this->timeout)) {
			$this->options['timeout'] = $this->timeout;
		}
		parent::init();
	}

	protected function getData($id) {
		return $this->collection->findOne(array($this->idColumn => $id), array($this->dataColumn));
	}

	protected function getExipireTime() {
		return time() + $this->getTimeout();
	}

	/**
	 * Returns a value indicating whether to use custom session storage.
	 * This method overrides the parent implementation and always returns true.
	 * @return boolean whether to use custom storage.
	 */
	public function getUseCustomStorage()
	{
		return true;
	}

	/**
	 * Session open handler.
	 * Do not call this method directly.
	 * @param string $savePath session save path
	 * @param string $sessionName session name
	 * @return boolean whether session is opened successfully
	 */
	public function openSession($savePath,$sessionName)
	{
		$this->gcSession(0);
	}

	/**
	 * Session read handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @return string the session data
	 */
	public function readSession($id)
	{
		$row = $this->getData($id);
		return is_null($row) ? '' : $row[$this->dataColumn];
	}

	/**
	 * Session write handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @param string $data session data
	 * @return boolean whether session write is successful
	 */
	public function writeSession($id,$data)
	{
		$opts = $this->options;
		$opts['upsert'] = true;
		return $this->collection->update(
		array($this->idColumn => $id)
		,array(
		  $this->dataColumn => $data
		  ,$this->expireColumn => $this->getExipireTime()
		  ,$this->idColumn => $id
		)
		,$opts
		);
	}

	/**
	 * Session destroy handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @return boolean whether session is destroyed successfully
	 */
	public function destroySession($id)
	{
		return $this->collection->remove(
		  array($this->idColumn => $id), $this->options);
	}

	/**
	 * Session GC (garbage collection) handler.
	 * Do not call this method directly.
	 * @param integer $maxLifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
	 * @return boolean whether session is GCed successfully
	 */
	public function gcSession($maxLifetime)
	{
		return $this->collection->remove(
		  array($this->expireColumn => array('$lt' => time())), $this->options);
	}

	/**
	 * Updates the current session id with a newly generated one .
	 * Please refer to {@link http://php.net/session_regenerate_id} for more details.
	 * @param boolean $deleteOldSession Whether to delete the old associated session file or not.
	 * @since 1.1.8
	 */
	public function regenerateID($deleteOldSession=false)
	{
		$oldId = session_id();;
		parent::regenerateID(false);
		$newId = session_id();
		$row = $this->getData($oldId);
		if (is_null($row)) {
			$this->collection->insert(array(
			  $this->idColumn => $newId
			  ,$this->expireColumn => $this->getExipireTime()
			), $this->options);
		} else if ($deleteOldSession) {
			$this->collection->update(
			  array($this->idColumn => $oldId)
			  ,array($this->idColumn => $newId)
			  ,$this->options
			);
		} else {
			$row[$this->idColumn] = $newId;
			unset($row['_id']);
			$this->collection->insert($row, $this->options);
		}
	}
}
